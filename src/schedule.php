<?php

namespace Crontrol\Schedule;

/**
 * Adds a new custom cron schedule.
 *
 * @param string $name     The internal name of the schedule.
 * @param int    $interval The interval between executions of the new schedule.
 * @param string $display  The display name of the schedule.
 */
function add_schedule( $name, $interval, $display ) {
	$old_scheds = get_option( 'crontrol_schedules', array() );
	$old_scheds[ $name ] = array(
		'interval' => $interval,
		'display'  => $display,
	);
	update_option( 'crontrol_schedules', $old_scheds );
}

/**
 * Deletes a custom cron schedule.
 *
 * @param string $name The internal_name of the schedule to delete.
 */
function delete_schedule( $name ) {
	$scheds = get_option( 'crontrol_schedules', array() );
	unset( $scheds[ $name ] );
	update_option( 'crontrol_schedules', $scheds );
}
