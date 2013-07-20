<?php

/*

@todo add, delete, run, etc

*/

class Crontrol_Command extends WP_CLI_Command {

	public $crontrol = null;

	public function __construct() {

		$this->crontrol = Crontrol::init();

	}

	/**
	 * List scheduled cron events.
	 *
	 * @since 1.2
	 *
	 * @alias list
	 * @subcommand list-events
	 */
	public function list_events() {

		$events = $this->crontrol->get_cron_events();

        if ( is_wp_error( $events ) ) {
			WP_CLI::line( WP_CLI::error_to_string( $events ) );
			die();
        }

        $events = array_map( 'self::_map_event', $events );

        $fields = array(
        	'hook',
        	'next_run',
        	'recurrence'
        );

    	\WP_CLI\Utils\format_items( 'table', $events, $fields );

	}

	/**
	 * List available cron schedules.
	 *
	 * @since 1.2
	 *
	 * @subcommand list-schedules
	 */
	public function list_schedules() {

		$schedules = $this->crontrol->get_schedules();

        $schedules = array_map( 'self::_map_schedule', $schedules );

        $fields = array(
        	'display',
        	'interval'
        );

    	\WP_CLI\Utils\format_items( 'table', $schedules, $fields );

	}

	/**
	 * Test the WP Cron spawning system and report back any errors.
	 *
	 * @since 1.2
	 */
	public function test() {

		$status = $this->crontrol->test_cron_spawn( false );

        if ( is_wp_error( $status ) )
			WP_CLI::error( $status );
		else
			WP_CLI::success( __( 'WP-Cron is working as expected.', 'crontrol' ) );

	}

	protected static function _map_event( $event ) {
		$event->next_run = get_date_from_gmt(date('Y-m-d H:i:s',$event->time),$time_format) . " (".$this->crontrol->time_since(time(), $event->time).")";
		$event->recurrence = ($event->schedule ? $this->crontrol->interval($event->interval) : __('Non-repeating', 'crontrol'));
		return $event;
	}

	protected static function _map_schedule( $schedule ) {
		return (object) $schedule;
	}

}

WP_CLI::add_command( 'crontrol', 'Crontrol_Command' );
