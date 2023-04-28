<?php
/**
 * Functions related to cron events.
 */

namespace Crontrol\Event;

use stdClass;
use Crontrol\Schedule;
use WP_Error;

use const Crontrol\PAUSED_OPTION;

/**
 * Executes a cron event immediately.
 *
 * Executes an event by scheduling a new single event with the same arguments.
 *
 * @param string $hookname The hook name of the cron event to run.
 * @param string $sig      The cron event signature.
 * @return true|WP_Error True if the execution was successful, WP_Error if not.
 */
function run( $hookname, $sig ) {
	$crons = get_core_cron_array();

	foreach ( $crons as $time => $cron ) {
		if ( isset( $cron[ $hookname ][ $sig ] ) ) {
			$event = $cron[ $hookname ][ $sig ];

			$event['hook'] = $hookname;
			$event['timestamp'] = $time;

			$event = (object) $event;

			delete_transient( 'doing_cron' );
			$scheduled = force_schedule_single_event( $hookname, $event->args ); // UTC

			if ( is_wp_error( $scheduled ) ) {
				return $scheduled;
			}

			add_filter( 'cron_request', function( array $cron_request_array ) {
				$cron_request_array['url'] = add_query_arg( 'crontrol-single-event', 1, $cron_request_array['url'] );
				return $cron_request_array;
			} );

			spawn_cron();

			sleep( 1 );

			/**
			 * Fires after a cron event is scheduled to run manually.
			 *
			 * @param stdClass $event {
			 *     An object containing the event's data.
			 *
			 *     @type string       $hook      Action hook to execute when the event is run.
			 *     @type int          $timestamp Unix timestamp (UTC) for when to next run the event.
			 *     @type string|false $schedule  How often the event should subsequently recur.
			 *     @type array        $args      Array containing each separate argument to pass to the hook's callback function.
			 *     @type int          $interval  The interval time in seconds for the schedule. Only present for recurring events.
			 * }
			 */
			do_action( 'crontrol/ran_event', $event );

			return true;
		}
	}

	return new WP_Error(
		'not_found',
		sprintf(
			/* translators: 1: The name of the cron event. */
			__( 'The cron event %s could not be found.', 'wp-crontrol' ),
			$hookname
		)
	);
}

/**
 * Forcibly schedules a single event for the purpose of manually running it.
 *
 * This is used instead of `wp_schedule_single_event()` to avoid the duplicate check that's otherwise performed.
 *
 * @param string  $hook Action hook to execute when the event is run.
 * @param mixed[] $args Optional. Array containing each separate argument to pass to the hook's callback function.
 * @return true|WP_Error True if event successfully scheduled. WP_Error on failure.
 */
function force_schedule_single_event( $hook, $args = array() ) {
	$event = (object) array(
		'hook'      => $hook,
		'timestamp' => 1,
		'schedule'  => false,
		'args'      => $args,
	);
	$crons = get_core_cron_array();
	$key   = md5( serialize( $event->args ) );

	$crons[ $event->timestamp ][ $event->hook ][ $key ] = array(
		'schedule' => $event->schedule,
		'args'     => $event->args,
	);
	ksort( $crons );

	$result = _set_cron_array( $crons );

	// Not using the WP_Error from `_set_cron_array()` here so we can provide a more specific error message.
	if ( false === $result ) {
		return new WP_Error(
			'could_not_add',
			sprintf(
				/* translators: 1: The name of the cron event. */
				__( 'Failed to schedule the cron event %s.', 'wp-crontrol' ),
				$hook
			)
		);
	}

	return true;
}

/**
 * Adds a new cron event.
 *
 * @param string  $next_run_local The time that the event should be run at, in the site's timezone.
 * @param string  $schedule       The recurrence of the cron event.
 * @param string  $hook           The name of the hook to execute.
 * @param mixed[] $args           Arguments to add to the cron event.
 * @return true|WP_error True if the addition was successful, WP_Error otherwise.
 */
function add( $next_run_local, $schedule, $hook, array $args ) {
	/**
	 * @var int
	 */
	$current_time = current_time( 'timestamp' );
	$next_run_local = strtotime( $next_run_local, $current_time );

	if ( false === $next_run_local ) {
		return new WP_Error(
			'invalid_timestamp',
			__( 'Invalid timestamp provided.', 'wp-crontrol' )
		);
	}

	$next_run_utc = (int) get_gmt_from_date( gmdate( 'Y-m-d H:i:s', $next_run_local ), 'U' );

	if ( ! is_array( $args ) ) {
		$args = array();
	}

	if ( 'crontrol_cron_job' === $hook && ! empty( $args['code'] ) && class_exists( '\ParseError' ) ) {
		try {
			// phpcs:ignore Squiz.PHP.Eval.Discouraged
			eval( sprintf(
				'return true; %s',
				$args['code']
			) );
		// phpcs:ignore PHPCompatibility.Classes.NewClasses.parseerrorFound
		} catch ( \ParseError $e ) {
			$args['syntax_error_message'] = $e->getMessage();
			$args['syntax_error_line']    = $e->getLine();
		}
	}

	if ( '_oneoff' === $schedule || '' === $schedule ) {
		/**
		 * @var bool|null|\WP_Error $result
		 */
		$result = wp_schedule_single_event( $next_run_utc, $hook, $args, true );
	} else {
		/**
		 * @var bool|null|\WP_Error $result
		 */
		$result = wp_schedule_event( $next_run_utc, $schedule, $hook, $args, true );
	}

	/**
	 * Possible return values of `wp_schedule_*()` as called above:
	 *
	 *   - 5.7+ Success: true, Failure: WP_Error
	 *   - 5.1+ Success: true, Failure: false
	 *   - <5.1 Success: null, Failure: false
	 */

	if ( is_wp_error( $result ) ) {
		return $result;
	}

	if ( false === $result ) {
		return new WP_Error(
			'could_not_add',
			sprintf(
				/* translators: 1: The name of the cron event. */
				__( 'Failed to schedule the cron event %s.', 'wp-crontrol' ),
				$hook
			)
		);
	}

	return true;
}

/**
 * Deletes a cron event.
 *
 * @param string $hook         The hook name of the event to delete.
 * @param string $sig          The cron event signature.
 * @param string $next_run_utc The UTC time that the event would be run at.
 * @return true|WP_Error True if the deletion was successful, WP_Error otherwise.
 */
function delete( $hook, $sig, $next_run_utc ) {
	$event = get_single( $hook, $sig, $next_run_utc );

	if ( is_wp_error( $event ) ) {
		return $event;
	}

	/**
	 * @var bool|null|\WP_Error $unscheduled
	 */
	$unscheduled = wp_unschedule_event( $event->timestamp, $event->hook, $event->args, true );

	/**
	 * Possible return values of `wp_unschedule_*()` as called above:
	 *
	 *   - 5.7+ Success: true, Failure: WP_Error
	 *   - 5.1+ Success: true, Failure: false
	 *   - <5.1 Success: null, Failure: false
	 */

	if ( is_wp_error( $unscheduled ) ) {
		return $unscheduled;
	}

	if ( false === $unscheduled ) {
		return new WP_Error(
			'could_not_delete',
			sprintf(
				/* translators: 1: The name of the cron event. */
				__( 'Failed to the delete the cron event %s.', 'wp-crontrol' ),
				$hook
			)
		);
	}

	return true;
}

/**
 * Pauses a cron event.
 *
 * @param string $hook The hook name of the event to pause.
 * @return true|WP_Error True if the pause was successful, WP_Error otherwise.
 */
function pause( $hook ) {
	$paused = get_option( PAUSED_OPTION, array() );

	if ( ! is_array( $paused ) ) {
		$paused = array();
	}

	$paused[ $hook ] = true;

	$result = update_option( PAUSED_OPTION, $paused, true );

	if ( false === $result ) {
		return new WP_Error(
			'could_not_pause',
			sprintf(
				/* translators: 1: The name of the cron event. */
				__( 'Failed to pause the cron event %s.', 'wp-crontrol' ),
				$hook
			)
		);
	}

	return true;
}

/**
 * Resumes a paused cron event.
 *
 * @param string $hook The hook name of the event to resume.
 * @return true|WP_Error True if the resumption was successful, WP_Error otherwise.
 */
function resume( $hook ) {
	$paused = get_option( PAUSED_OPTION );

	if ( ! is_array( $paused ) || ( count( $paused ) === 0 ) ) {
		return true;
	}

	unset( $paused[ $hook ] );

	$result = update_option( PAUSED_OPTION, $paused, true );

	if ( false === $result ) {
		return new WP_Error(
			'could_not_resume',
			sprintf(
				/* translators: 1: The name of the cron event. */
				__( 'Failed to resume the cron event %s.', 'wp-crontrol' ),
				$hook
			)
		);
	}

	return true;
}

/**
 * Returns a flattened array of cron events.
 *
 * @return array<string,stdClass> An array of cron event objects keyed by unique signature.
 */
function get() {
	$crons  = get_core_cron_array();
	$events = array();

	if ( empty( $crons ) ) {
		return array();
	}

	foreach ( $crons as $time => $cron ) {
		foreach ( $cron as $hook => $dings ) {
			foreach ( $dings as $sig => $data ) {

				// This is a prime candidate for a Crontrol_Event class but I'm not bothering currently.
				$events[ "$hook-$sig-$time" ] = (object) array(
					'hook'     => $hook,
					'timestamp' => $time, // UTC
					'sig'      => $sig,
					'args'     => $data['args'],
					'schedule' => $data['schedule'],
					'interval' => isset( $data['interval'] ) ? $data['interval'] : null,
				);

			}
		}
	}

	// Ensure events are always returned in date descending order.
	// External cron runners such as Cavalcade don't guarantee events are returned in order of time.
	uasort( $events, 'Crontrol\Event\uasort_order_events' );

	return $events;
}

/**
 * Gets a single cron event.
 *
 * @param string     $hook         The hook name of the event.
 * @param string     $sig          The event signature.
 * @param string|int $next_run_utc The UTC time that the event would be run at.
 * @return stdClass|WP_Error A cron event object, or a WP_Error if it's not found.
 */
function get_single( $hook, $sig, $next_run_utc ) {
	$crons = get_core_cron_array();
	$next_run_utc = (int) $next_run_utc;

	if ( isset( $crons[ $next_run_utc ][ $hook ][ $sig ] ) ) {
		$event = $crons[ $next_run_utc ][ $hook ][ $sig ];

		$event['hook'] = $hook;
		$event['timestamp'] = $next_run_utc;

		$event = (object) $event;

		return $event;
	}

	return new WP_Error(
		'not_found',
		sprintf(
			/* translators: 1: The name of the cron event. */
			__( 'The cron event %s could not be found.', 'wp-crontrol' ),
			$hook
		)
	);
}

/**
 * Returns an array of the number of events for each hook.
 *
 * @return array<string,int> Array of number of events for each hook, keyed by the hook name.
 */
function count_by_hook() {
	$crons  = get_core_cron_array();
	$events = array();

	if ( empty( $crons ) ) {
		return array();
	}

	foreach ( $crons as $time => $cron ) {
		foreach ( $cron as $hook => $dings ) {
			if ( ! isset( $events[ $hook ] ) ) {
				$events[ $hook ] = 0;
			}

			$events[ $hook ] += count( $dings );
		}
	}

	return $events;
}

/**
 * Returns the schedule display name for a given event.
 *
 * @param stdClass $event A WP-Cron event.
 * @return string|WP_Error The interval display name, or a WP_Error object if no such schedule exists.
 */
function get_schedule_name( stdClass $event ) {
	$schedules = Schedule\get();

	if ( isset( $schedules[ $event->schedule ] ) ) {
		return isset( $schedules[ $event->schedule ]['display'] ) ? $schedules[ $event->schedule ]['display'] : $schedules[ $event->schedule ]['name'];
	}

	return new WP_Error( 'unknown_schedule', sprintf(
		/* translators: %s: Schedule name */
		__( 'Unknown (%s)', 'wp-crontrol' ),
		$event->schedule
	) );
}

/**
 * Determines whether the schedule for an event means it runs too frequently to be reliable.
 *
 * @param stdClass $event A WP-Cron event.
 * @return bool Whether the event scheduled is too frequent.
 */
function is_too_frequent( stdClass $event ) {
	$schedules = Schedule\get();

	if ( ! isset( $schedules[ $event->schedule ] ) ) {
		return false;
	}

	return $schedules[ $event->schedule ]['is_too_frequent'];
}

/**
 * Determines whether an event is late.
 *
 * An event which has missed its schedule by more than 10 minutes is considered late.
 *
 * @param stdClass $event The event.
 * @return bool Whether the event is late.
 */
function is_late( stdClass $event ) {
	$until = (int) $event->timestamp - time();

	return ( $until < ( 0 - ( 10 * MINUTE_IN_SECONDS ) ) );
}

/**
 * Determines whether an event is paused.
 *
 * @param stdClass $event The event.
 * @return bool Whether the event is paused.
 */
function is_paused( stdClass $event ) {
	$paused = get_option( PAUSED_OPTION );

	if ( ! is_array( $paused ) ) {
		return false;
	}

	return array_key_exists( $event->hook, $paused );
}

/**
 * Initialises and returns the list table for events.
 *
 * @return Table The list table.
 */
function get_list_table() {
	static $table = null;

	if ( ! $table ) {
		$table = new Table();
		$table->prepare_items();

	}

	return $table;
}

/**
 * Order events function.
 *
 * The comparison function returns an integer less than, equal to, or greater than zero if the first argument is
 * considered to be respectively less than, equal to, or greater than the second.
 *
 * @param stdClass $a The first event to compare.
 * @param stdClass $b The second event to compare.
 * @return int
 */
function uasort_order_events( $a, $b ) {
	$orderby = ( ! empty( $_GET['orderby'] ) ) ? sanitize_text_field( $_GET['orderby'] ) : 'crontrol_next';
	$order   = ( ! empty( $_GET['order'] ) ) ? sanitize_text_field( $_GET['order'] ) : 'desc';
	$compare = 0;

	switch ( $orderby ) {
		case 'crontrol_hook':
			if ( 'desc' === $order ) {
				$compare = strcmp( $a->hook, $b->hook );
			} else {
				$compare = strcmp( $b->hook, $a->hook );
			}
			break;
		default:
			if ( $a->timestamp === $b->timestamp ) {
				$compare = 0;
			} else {
				if ( 'desc' === $order ) {
					$compare = ( $a->timestamp > $b->timestamp ) ? 1 : -1;
				} else {
					$compare = ( $a->timestamp < $b->timestamp ) ? 1 : -1;
				}
			}
			break;
	}

	return $compare;
}

/**
 * Fetches the list of cron events from WordPress core.
 *
 * @return array<int,array<string,array<string,array<string,mixed[]>>>>
 * @phpstan-return array<int,array<string,array<string,array<string,array{
 *     args: mixed[],
 *     schedule: string|false,
 *     interval?: int,
 * }>>>>
 */
function get_core_cron_array() {
	$crons = _get_cron_array();

	if ( empty( $crons ) ) {
		$crons = array();
	}

	return $crons;
}
