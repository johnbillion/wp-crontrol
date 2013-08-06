<?php

/*
@todo add-event
*/

class Crontrol_Command extends WP_CLI_Command {

	protected $crontrol = null;

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
	 * @synopsis [--format=<format>]
	 */
	public function list_events( $args, $assoc_args ) {

		$defaults = array(
			'format' => 'table',
		);
		$values = wp_parse_args( $assoc_args, $defaults );

		$events = $this->crontrol->get_cron_events();

        if ( is_wp_error( $events ) ) {
			WP_CLI::line( WP_CLI::error_to_string( $events ) );
			die();
        }

        $events = array_map( array( $this, '_map_event' ), $events );

        $fields = array(
        	'hook',
        	'next_run',
        	'recurrence'
        );

    	\WP_CLI\Utils\format_items( $values['format'], $events, $fields );

	}

	/**
	 * Run the next scheduled cron event for the given hook.
	 *
	 * @since 1.2.2
	 *
	 * @synopsis <hook>
	 * @subcommand run-event
	 */
	public function run_event( $args, $assoc_args ) {

		$hook   = $args[0];
        $result = false;
		$events = $this->crontrol->get_cron_events();

        if ( is_wp_error( $events ) )
			WP_CLI::error( $events );

        foreach ( $events as $id => $event ) {
        	if ( $event->hook == $hook ) {
        		$result = $this->crontrol->run_cron( $event->hook, $event->sig );
        		break;
        	}
        }

        if ( $result )
        	WP_CLI::success( sprintf( __( 'Successfully executed the cron event %s', 'crontrol' ), "'" . $hook . "'" ) );
        else
        	WP_CLI::error( sprintf( __( 'Failed to the execute the cron event %s', 'crontrol' ), "'" . $hook . "'" ) );

	}

	/**
	 * Delete the next scheduled cron event for the given hook.
	 *
	 * @since 1.2.2
	 *
	 * @synopsis <hook>
	 * @subcommand delete-event
	 */
	public function delete_event( $args, $assoc_args ) {

		$hook   = $args[0];
        $result = false;
		$events = $this->crontrol->get_cron_events();

        if ( is_wp_error( $events ) )
			WP_CLI::error( $events );

        foreach ( $events as $id => $event ) {
        	if ( $event->hook == $hook ) {
        		$result = $this->crontrol->delete_cron( $event->hook, $event->sig, $event->time );
        		break;
        	}
        }

        if ( $result )
        	WP_CLI::success( sprintf( __( 'Successfully deleted the cron event %s', 'crontrol' ), "'" . $hook . "'" ) );
        else
        	WP_CLI::error( sprintf( __( 'Failed to the delete the cron event %s', 'crontrol' ), "'" . $hook . "'" ) );

	}

	/**
	 * List available cron schedules.
	 *
	 * @since 1.2
	 *
	 * @subcommand list-schedules
	 * @synopsis [--format=<format>]
	 */
	public function list_schedules( $args, $assoc_args ) {

		$defaults = array(
			'format' => 'table',
		);
		$values = wp_parse_args( $assoc_args, $defaults );

		$schedules = $this->crontrol->get_schedules();
        $schedules = array_map( array( $this, '_map_schedule' ), $schedules, array_keys( $schedules ) );

        $fields = array(
        	'name',
        	'display',
        	'interval'
        );

    	\WP_CLI\Utils\format_items( $values['format'], $schedules, $fields );

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

	protected function _map_event( $event ) {
        $time_format = 'Y/m/d H:i:s';
		$event->next_run = get_date_from_gmt(date('Y-m-d H:i:s',$event->time),$time_format) . " (".$this->crontrol->time_since(time(), $event->time).")";
		$event->recurrence = ($event->schedule ? $this->crontrol->interval($event->interval) : __('Non-repeating', 'crontrol'));
		return $event;
	}

	protected function _map_schedule( $schedule, $name ) {
		$schedule = (object) $schedule;
		$schedule->name = $name;
		return $schedule;
	}

}

WP_CLI::add_command( 'crontrol', 'Crontrol_Command' );
