<?php
/**
 * List table for cron events.
 *
 * @package wp-crontrol
 */

namespace Crontrol\Event;

use stdClass;

require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

/**
 * Cron event list table class.
 */
class Table extends \WP_List_Table {

	/**
	 * Array of cron event hooks that are persistently added by WordPress core.
	 *
	 * @var string[] Array of hook names.
	 */
	protected static $persistent_core_hooks;

	/**
	 * Whether the current user has the capability to edit files.
	 *
	 * @var bool Whether the user can edit files.
	 */
	protected static $can_edit_files;

	/**
	 * Array of the count of each hook.
	 *
	 * @var int[] Array of count of each hooked, keyed by hook name.
	 */
	protected static $count_by_hook;

	/**
	 * Array of all cron events.
	 *
	 * @var stdClass[] Array of event objects.
	 */
	protected $all_events = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct( array(
			'singular' => 'crontrol-event',
			'plural'   => 'crontrol-events',
			'ajax'     => false,
			'screen'   => 'crontrol-events',
		) );
	}

	/**
	 * Prepares the list table items and arguments.
	 *
	 * @return void
	 */
	public function prepare_items() {
		self::$persistent_core_hooks = \Crontrol\get_persistent_core_hooks();
		self::$can_edit_files        = current_user_can( 'edit_files' );
		self::$count_by_hook         = count_by_hook();

		$events = get();
		$this->all_events = $events;

		if ( ! empty( $_GET['s'] ) ) {
			$s = sanitize_text_field( wp_unslash( $_GET['s'] ) );

			$events = array_filter( $events, function( $event ) use ( $s ) {
				return ( false !== strpos( $event->hook, $s ) );
			} );
		}

		if ( ! empty( $_GET['crontrol_hooks_type'] ) ) {
			$hooks_type = sanitize_text_field( $_GET['crontrol_hooks_type'] );
			$filtered = self::get_filtered_events( $events );

			if ( isset( $filtered[ $hooks_type ] ) ) {
				$events = $filtered[ $hooks_type ];
			}
		}

		$count    = count( $events );
		$per_page = 50;
		$offset   = ( $this->get_pagenum() - 1 ) * $per_page;

		$this->items = array_slice( $events, $offset, $per_page );

		$has_late = (bool) array_filter( array_map( __NAMESPACE__ . '\\is_late', $this->items ) );

		if ( $has_late ) {
			add_action( 'admin_notices', function() {
				printf(
					'<div id="crontrol-late-message" class="notice notice-warning"><p>%1$s</p><p><a href="%2$s">%3$s</a></p></div>',
					/* translators: %s: Help page URL. */
					esc_html__( 'One or more cron events have missed their schedule.', 'wp-crontrol' ),
					'https://github.com/johnbillion/wp-crontrol/wiki/Cron-events-that-have-missed-their-schedule',
					esc_html__( 'More information', 'wp-crontrol' )
				);
			} );
		}

		$this->set_pagination_args( array(
			'total_items' => $count,
			'per_page'    => $per_page,
			'total_pages' => ceil( $count / $per_page ),
		) );
	}

	/**
	 * Returns events filtered by various parameters
	 *
	 * @param stdClass[] $events The list of all events.
	 * @return stdClass[][] Array of filtered events keyed by filter name.
	 */
	public static function get_filtered_events( array $events ) {
		$all_core_hooks = \Crontrol\get_all_core_hooks();
		$filtered = array(
			'all' => $events,
		);

		$filtered['noaction'] = array_filter( $events, function( $event ) {
			$hook_callbacks = \Crontrol\get_hook_callbacks( $event->hook );
			return empty( $hook_callbacks );
		} );

		$filtered['core'] = array_filter( $events, function( $event ) use ( $all_core_hooks ) {
			return ( in_array( $event->hook, $all_core_hooks, true ) );
		} );

		$filtered['custom'] = array_filter( $events, function( $event ) use ( $all_core_hooks ) {
			return ( ! in_array( $event->hook, $all_core_hooks, true ) );
		} );

		/**
		 * Filters the available filtered events on the cron event listing screen.
		 *
		 * See the corresponding `crontrol/filter-types` filter to adjust the list of filter types.
		 *
		 * @since 1.11.0
		 *
		 * @param array[]    $filtered Array of filtered event arrays keyed by filter name.
		 * @param stdClass[] $events   Array of all events.
		 */
		$filtered = apply_filters( 'crontrol/filtered-events', $filtered, $events );

		return $filtered;
	}

	/**
	 * Returns an array of column names for the table.
	 *
	 * @return string[] Array of column names keyed by their ID.
	 */
	public function get_columns() {
		return array(
			'cb'                  => '<input type="checkbox" />',
			'crontrol_hook'       => __( 'Hook', 'wp-crontrol' ),
			'crontrol_args'       => __( 'Arguments', 'wp-crontrol' ),
			'crontrol_next'       => sprintf(
				/* translators: %s: UTC offset */
				__( 'Next Run (%s)', 'wp-crontrol' ),
				\Crontrol\get_utc_offset()
			),
			'crontrol_actions'    => __( 'Action', 'wp-crontrol' ),
			'crontrol_recurrence' => __( 'Recurrence', 'wp-crontrol' ),
		);
	}

	/**
	 * Columns to make sortable.
	 *
	 * @return array<string,array<int,(string|bool)>>
	 */
	public function get_sortable_columns() {
		return array(
			'crontrol_hook' => array( 'crontrol_hook', true ),
			'crontrol_next' => array( 'crontrol_next', false ),
		);
	}

	/**
	 * Returns an array of CSS class names for the table.
	 *
	 * @return array<int,string> Array of class names.
	 */
	protected function get_table_classes() {
		return array( 'widefat', 'striped', $this->_args['plural'] );
	}

	/**
	 * Get an associative array ( option_name => option_title ) with the list
	 * of bulk actions available on this table.
	 *
	 * @since 3.1.0
	 *
	 * @return array<string,string>
	 */
	protected function get_bulk_actions() {
		return array(
			'crontrol_delete_crons' => esc_html__( 'Delete', 'wp-crontrol' ),
		);
	}

	/**
	 * Display the list of hook types.
	 *
	 * @return array<string,string>
	 */
	public function get_views() {
		$filtered = self::get_filtered_events( $this->all_events );

		$views = array();
		$hooks_type = ( ! empty( $_GET['crontrol_hooks_type'] ) ? $_GET['crontrol_hooks_type'] : 'all' );

		$types = array(
			'all'      => __( 'All events', 'wp-crontrol' ),
			'noaction' => __( 'Events with no action', 'wp-crontrol' ),
			'core'     => __( 'WordPress core events', 'wp-crontrol' ),
			'custom'   => __( 'Custom events', 'wp-crontrol' ),
		);

		/**
		 * Filters the filter types on the cron event listing screen.
		 *
		 * See the corresponding `crontrol/filtered-events` filter to adjust the filtered events.
		 *
		 * @since 1.11.0
		 *
		 * @param string[] $types      Array of filter names keyed by filter name.
		 * @param string   $hooks_type The current filter name.
		 */
		$types = apply_filters( 'crontrol/filter-types', $types, $hooks_type );

		$url = admin_url( 'tools.php?page=crontrol_admin_manage_page' );

		/**
		 * @var array<string,string> $types
		 */
		foreach ( $types as $key => $type ) {
			if ( ! isset( $filtered[ $key ] ) ) {
				continue;
			}

			$link = ( 'all' === $key ) ? $url : add_query_arg( 'crontrol_hooks_type', $key, $url );

			$views[ $key ] = sprintf(
				'<a href="%1$s"%2$s>%3$s <span class="count">(%4$s)</span></a>',
				esc_url( $link ),
				$hooks_type === $key ? ' class="current"' : '',
				esc_html( $type ),
				number_format_i18n( count( $filtered[ $key ] ) )
			);
		}

		return $views;
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination.
	 *
	 * @param string $which One of 'top' or 'bottom' to indicate the position on the screen.
	 *
	 * @return void
	 */
	protected function extra_tablenav( $which ) {
		wp_nonce_field( 'crontrol-export-event-csv', 'crontrol_nonce' );
		printf(
			'<input type="hidden" name="crontrol_hooks_type" value="%s"/>',
			esc_attr( isset( $_GET['crontrol_hooks_type'] ) ? sanitize_text_field( wp_unslash( $_GET['crontrol_hooks_type'] ) ) : 'all' )
		);
		printf(
			'<input type="hidden" name="s" value="%s"/>',
			esc_attr( isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '' )
		);
		printf(
			'<button class="button" type="submit" name="crontrol_action" value="export-event-csv">%s</button>',
			esc_html__( 'Export', 'wp-crontrol' )
		);
	}

	/**
	 * Generates content for a single row of the table.
	 *
	 * @param stdClass $event The current event.
	 * @return void
	 */
	public function single_row( $event ) {
		$classes = array();

		if ( ( 'crontrol_cron_job' === $event->hook ) && ! empty( $event->args['syntax_error_message'] ) ) {
			$classes[] = 'crontrol-error';
		}

		$schedule_name = ( $event->interval ? get_schedule_name( $event ) : false );

		if ( is_wp_error( $schedule_name ) ) {
			$classes[] = 'crontrol-error';
		}

		$callbacks = \Crontrol\get_hook_callbacks( $event->hook );

		if ( ! $callbacks ) {
			$classes[] = 'crontrol-warning';
		} else {
			foreach ( $callbacks as $callback ) {
				if ( ! empty( $callback['callback']['error'] ) ) {
					$classes[] = 'crontrol-error';
					break;
				}
			}
		}

		if ( is_late( $event ) || is_too_frequent( $event ) ) {
			$classes[] = 'crontrol-warning';
		}

		printf(
			'<tr class="%s">',
			esc_attr( implode( ' ', $classes ) )
		);

		$this->single_row_columns( $event );
		echo '</tr>';
	}

	/**
	 * Generates and displays row action links for the table.
	 *
	 * @param stdClass $event       The cron event for the current row.
	 * @param string   $column_name Current column name.
	 * @param string   $primary     Primary column name.
	 * @return string The row actions HTML.
	 */
	protected function handle_row_actions( $event, $column_name, $primary ) {
		if ( $primary !== $column_name ) {
			return '';
		}

		$links = array();

		if ( ( 'crontrol_cron_job' !== $event->hook ) || self::$can_edit_files ) {
			$link = array(
				'page'                  => 'crontrol_admin_manage_page',
				'crontrol_action'       => 'edit-cron',
				'crontrol_id'           => rawurlencode( $event->hook ),
				'crontrol_sig'          => rawurlencode( $event->sig ),
				'crontrol_next_run_utc' => rawurlencode( $event->time ),
			);
			$link = add_query_arg( $link, admin_url( 'tools.php' ) );

			$links[] = "<a href='" . esc_url( $link ) . "'>" . esc_html__( 'Edit', 'wp-crontrol' ) . '</a>';
		}

		$link = array(
			'page'                  => 'crontrol_admin_manage_page',
			'crontrol_action'       => 'run-cron',
			'crontrol_id'           => rawurlencode( $event->hook ),
			'crontrol_sig'          => rawurlencode( $event->sig ),
			'crontrol_next_run_utc' => rawurlencode( $event->time ),
		);
		$link = add_query_arg( $link, admin_url( 'tools.php' ) );
		$link = wp_nonce_url( $link, "crontrol-run-cron_{$event->hook}_{$event->sig}" );

		$links[] = "<a href='" . esc_url( $link ) . "'>" . esc_html__( 'Run Now', 'wp-crontrol' ) . '</a>';

		if ( ! in_array( $event->hook, self::$persistent_core_hooks, true ) && ( ( 'crontrol_cron_job' !== $event->hook ) || self::$can_edit_files ) ) {
			$link = array(
				'page'                  => 'crontrol_admin_manage_page',
				'crontrol_action'       => 'delete-cron',
				'crontrol_id'           => rawurlencode( $event->hook ),
				'crontrol_sig'          => rawurlencode( $event->sig ),
				'crontrol_next_run_utc' => rawurlencode( $event->time ),
			);
			$link = add_query_arg( $link, admin_url( 'tools.php' ) );
			$link = wp_nonce_url( $link, "crontrol-delete-cron_{$event->hook}_{$event->sig}_{$event->time}" );

			$links[] = "<span class='delete'><a href='" . esc_url( $link ) . "'>" . esc_html__( 'Delete', 'wp-crontrol' ) . '</a></span>';
		}

		if ( function_exists( 'wp_unschedule_hook' ) && ! in_array( $event->hook, self::$persistent_core_hooks, true ) && ( 'crontrol_cron_job' !== $event->hook ) ) {
			if ( self::$count_by_hook[ $event->hook ] > 1 ) {
				$link = array(
					'page'            => 'crontrol_admin_manage_page',
					'crontrol_action' => 'delete-hook',
					'crontrol_id'     => rawurlencode( $event->hook ),
				);
				$link = add_query_arg( $link, admin_url( 'tools.php' ) );
				$link = wp_nonce_url( $link, "crontrol-delete-hook_{$event->hook}" );

				$text = sprintf(
					/* translators: %s: Number of events with a given name */
					_n( 'Delete All %s', 'Delete All %s', self::$count_by_hook[ $event->hook ], 'wp-crontrol' ),
					number_format_i18n( self::$count_by_hook[ $event->hook ] )
				);
				$links[] = "<span class='delete'><a href='" . esc_url( $link ) . "'>" . esc_html( $text ) . '</a></span>';
			}
		}

		return $this->row_actions( $links );
	}

	/**
	 * Outputs the checkbox cell of a table row.
	 *
	 * @param stdClass $event The cron event for the current row.
	 * @return string The cell output.
	 */
	protected function column_cb( $event ) {
		$id = sprintf(
			'crontrol-delete-%1$s-%2$s-%3$s',
			$event->time,
			rawurlencode( $event->hook ),
			$event->sig
		);

		if ( in_array( $event->hook, self::$persistent_core_hooks, true ) ) {
			return sprintf(
				'<span class="dashicons dashicons-wordpress" aria-hidden="true"></span>
				<span class="screen-reader-text">%s</span>',
				esc_html__( 'This is a WordPress core event and cannot be deleted', 'wp-crontrol' )
			);
		} elseif ( ( 'crontrol_cron_job' !== $event->hook ) || self::$can_edit_files ) {
			return sprintf(
				'<label class="screen-reader-text" for="%1$s">%2$s</label>
				<input type="checkbox" name="crontrol_delete[%3$s][%4$s]" value="%5$s" id="%1$s">',
				esc_attr( $id ),
				esc_html__( 'Select this row', 'wp-crontrol' ),
				esc_attr( $event->time ),
				esc_attr( rawurlencode( $event->hook ) ),
				esc_attr( $event->sig )
			);
		}

		return '';
	}

	/**
	 * Returns the output for the hook name cell of a table row.
	 *
	 * @param stdClass $event The cron event for the current row.
	 * @return string The cell output.
	 */
	protected function column_crontrol_hook( $event ) {
		if ( 'crontrol_cron_job' === $event->hook ) {
			if ( ! empty( $event->args['name'] ) ) {
				/* translators: 1: The name of the PHP cron event. */
				return '<em>' . esc_html( sprintf( __( 'PHP Cron (%s)', 'wp-crontrol' ), $event->args['name'] ) ) . '</em>';
			} else {
				return '<em>' . esc_html__( 'PHP Cron', 'wp-crontrol' ) . '</em>';
			}
		}

		return esc_html( $event->hook );
	}

	/**
	 * Returns the output for the arguments cell of a table row.
	 *
	 * @param stdClass $event The cron event for the current row.
	 * @return string The cell output.
	 */
	protected function column_crontrol_args( $event ) {
		if ( 'crontrol_cron_job' === $event->hook ) {
			$return = '<em>' . esc_html__( 'PHP Code', 'wp-crontrol' ) . '</em>';

			if ( ! empty( $event->args['syntax_error_message'] ) ) {
				$return .= '<br><span class="status-crontrol-error"><span class="dashicons dashicons-warning" aria-hidden="true"></span> ';
				$return .= sprintf(
					/* translators: 1: Line number, 2: Error message text */
					esc_html__( 'Line %1$s: %2$s', 'wp-crontrol' ),
					esc_html( number_format_i18n( $event->args['syntax_error_line'] ) ),
					esc_html( $event->args['syntax_error_message'] )
				);
				$return .= '</span>';
			}

			if ( ! empty( $event->args['code'] ) ) {
				$lines = explode( "\n", trim( $event->args['code'] ) );
				$code  = reset( $lines );
				$code  = substr( $code, 0, 50 );

				$return .= '<br>';
				$return .= sprintf(
					'<code>%s</code>&hellip;',
					esc_html( $code )
				);
			}

			return $return;
		} else {
			if ( empty( $event->args ) ) {
				return sprintf(
					'<em>%s</em>',
					esc_html__( 'None', 'wp-crontrol' )
				);
			} else {
				return sprintf(
					'<pre>%s</pre>',
					esc_html( \Crontrol\json_output( $event->args ) )
				);
			}
		}
	}

	/**
	 * Returns the output for the actions cell of a table row.
	 *
	 * @param stdClass $event The cron event for the current row.
	 * @return string The cell output.
	 */
	protected function column_crontrol_actions( $event ) {
		$hook_callbacks = \Crontrol\get_hook_callbacks( $event->hook );

		if ( 'crontrol_cron_job' === $event->hook ) {
			return '<em>' . esc_html__( 'WP Crontrol', 'wp-crontrol' ) . '</em>';
		} elseif ( ! empty( $hook_callbacks ) ) {
			$callbacks = array();

			foreach ( $hook_callbacks as $callback ) {
				$callbacks[] = \Crontrol\output_callback( $callback );
			}

			return implode( '<br>', $callbacks ); // WPCS:: XSS ok.
		} else {
			return sprintf(
				'<span class="status-crontrol-warning"><span class="dashicons dashicons-warning" aria-hidden="true"></span> %s</span>',
				esc_html__( 'None', 'wp-crontrol' )
			);
		}
	}

	/**
	 * Returns the output for the next run cell of a table row.
	 *
	 * @param stdClass $event The cron event for the current row.
	 * @return string The cell output.
	 */
	protected function column_crontrol_next( $event ) {
		$date_local_format = 'Y-m-d H:i:s';
		$offset_site = get_date_from_gmt( 'now', 'P' );
		$offset_event = get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $event->time ), 'P' );

		if ( $offset_site !== $offset_event ) {
			$date_local_format .= ' P';
		}

		$date_utc   = gmdate( 'c', $event->time );
		$date_local = get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $event->time ), $date_local_format );

		$time = sprintf(
			'<time datetime="%1$s">%2$s</time>',
			esc_attr( $date_utc ),
			esc_html( $date_local )
		);

		$until = $event->time - time();
		$late  = is_late( $event );

		if ( $late ) {
			// Show a warning for events that are late.
			$ago = sprintf(
				/* translators: %s: Time period, for example "8 minutes" */
				__( '%s ago', 'wp-crontrol' ),
				\Crontrol\interval( abs( $until ) )
			);
			return sprintf(
				'%s<br><span class="status-crontrol-warning"><span class="dashicons dashicons-warning" aria-hidden="true"></span> %s</span>',
				$time,
				esc_html( $ago )
			);
		}

		return sprintf(
			'%s<br>%s',
			$time,
			esc_html( \Crontrol\interval( $until ) )
		);
	}

	/**
	 * Returns the output for the recurrence cell of a table row.
	 *
	 * @param stdClass $event The cron event for the current row.
	 * @return string The cell output.
	 */
	protected function column_crontrol_recurrence( $event ) {
		if ( $event->schedule ) {
			$schedule_name = get_schedule_name( $event );
			if ( is_wp_error( $schedule_name ) ) {
				return sprintf(
					'<span class="status-crontrol-error"><span class="dashicons dashicons-warning" aria-hidden="true"></span> %s</span>',
					esc_html( $schedule_name->get_error_message() )
				);
			} elseif ( is_too_frequent( $event ) ) {
				return sprintf(
					'%1$s<span class="status-crontrol-warning"><br><span class="dashicons dashicons-warning" aria-hidden="true"></span> %2$s</span>',
					esc_html( $schedule_name ),
					sprintf(
						/* translators: 1: The name of the configuration constant, 2: The value of the configuration constant */
						esc_html__( 'This interval is less than the %1$s constant which is set to %2$s seconds. Events that use it may not run on time.', 'wp-crontrol' ),
						'<code>WP_CRON_LOCK_TIMEOUT</code>',
						intval( WP_CRON_LOCK_TIMEOUT )
					)
				);
			} else {
				return esc_html( $schedule_name );
			}
		} else {
			return esc_html__( 'Non-repeating', 'wp-crontrol' );
		}
	}

	/**
	 * Outputs a message when there are no items to show in the table.
	 *
	 * @return void
	 */
	public function no_items() {
		if ( empty( $_GET['s'] ) && empty( $_GET['crontrol_hooks_type'] ) ) {
			esc_html_e( 'There are currently no scheduled cron events.', 'wp-crontrol' );
		} else {
			esc_html_e( 'No matching cron events.', 'wp-crontrol' );
		}
	}

}
