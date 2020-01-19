<?php
/**
 * Functions related to schedules.
 *
 * @package wp-crontrol
 */

namespace Crontrol\Schedule;

/**
 * Adds a new custom cron schedule.
 *
 * @param string $name     The internal name of the schedule.
 * @param int    $interval The interval between executions of the new schedule.
 * @param string $display  The display name of the schedule.
 */
function add( $name, $interval, $display ) {
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
function delete( $name ) {
	$scheds = get_option( 'crontrol_schedules', array() );
	unset( $scheds[ $name ] );
	update_option( 'crontrol_schedules', $scheds );
}

/**
 * Gets a sorted (according to interval) list of the cron schedules
 *
 * @return array[] Array of cron schedule arrays.
 */
function get() {
	$schedules = wp_get_schedules();
	uasort( $schedules, function( array $a, array $b ) {
		return ( $a['interval'] - $b['interval'] );
	} );

	array_walk( $schedules, function( array &$schedule, $name ) {
		$schedule['name'] = $name;
	} );

	return $schedules;
}

/**
 * Displays a dropdown filled with the possible schedules, including non-repeating.
 *
 * @param bool $current The currently selected schedule.
 */
function dropdown( $current = false ) {
	$schedules = get();
	?>
	<select class="postform" name="schedule" id="schedule" required>
	<option <?php selected( $current, '_oneoff' ); ?> value="_oneoff"><?php esc_html_e( 'Non-repeating', 'wp-crontrol' ); ?></option>
	<?php foreach ( $schedules as $sched_name => $sched_data ) { ?>
		<option <?php selected( $current, $sched_name ); ?> value="<?php echo esc_attr( $sched_name ); ?>">
			<?php
			printf(
				'%s (%s)',
				esc_html( $sched_data['display'] ),
				esc_html( $sched_name )
			);
			?>
		</option>
	<?php } ?>
	</select>
	<?php
}
