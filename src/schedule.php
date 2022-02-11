<?php
/**
 * Functions related to schedules.
 */

namespace Crontrol\Schedule;

/**
 * Adds a new custom cron schedule.
 *
 * @param string $name     The internal name of the schedule.
 * @param int    $interval The interval between executions of the new schedule.
 * @param string $display  The display name of the schedule.
 * @return void
 */
function add( $name, $interval, $display ) {
	/** @var array<string,int|string> */
	$old_scheds = get_option( 'crontrol_schedules', array() );

	$old_scheds[ $name ] = array(
		'interval' => $interval,
		'display'  => $display,
	);
	update_option( 'crontrol_schedules', $old_scheds );

	/**
	 * Fires after a new cron schedule is added.
	 *
	 * @param string $name     The internal name of the schedule.
	 * @param int    $interval The interval between executions of the new schedule.
	 * @param string $display  The display name of the schedule.
	 */
	do_action( 'crontrol/added_new_schedule', $name, $interval, $display );
}

/**
 * Deletes a custom cron schedule.
 *
 * @param string $name The internal name of the schedule to delete.
 * @return void
 */
function delete( $name ) {
	/** @var array<string,int|string> */
	$scheds = get_option( 'crontrol_schedules', array() );
	unset( $scheds[ $name ] );
	update_option( 'crontrol_schedules', $scheds );

	/**
	 * Fires after a cron schedule is deleted.
	 *
	 * @param string $name The internal name of the schedule.
	 */
	do_action( 'crontrol/deleted_schedule', $name );
}

/**
 * Gets a sorted (according to interval) list of the cron schedules
 *
 * @return array<string,array<string,(int|string)>> Array of cron schedule arrays.
 * @phpstan-return array<string,array{
 *   interval: int,
 *   display: string,
 *   name: string,
 *   is_too_frequent: bool,
 * }>
 */
function get() {
	/**
	 * @phpstan-var array<string,array{
	 *   interval: int,
	 *   display: string,
	 * }> $schedules
	 */
	$schedules = wp_get_schedules();
	uasort( $schedules, function( array $a, array $b ) {
		return ( $a['interval'] - $b['interval'] );
	} );

	array_walk( $schedules, function( array &$schedule, $name ) {
		$schedule['name'] = $name;
		$schedule['is_too_frequent'] = ( $schedule['interval'] < WP_CRON_LOCK_TIMEOUT );
	} );

	/**
	 * @phpstan-var array<string,array{
	 *   interval: int,
	 *   display: string,
	 *   name: string,
	 *   is_too_frequent: bool,
	 * }> $schedules
	 */
	return $schedules;
}

/**
 * Displays a dropdown filled with the possible schedules, including non-repeating.
 *
 * @param string|false $current The currently selected schedule, or false for none.
 * @return void
 */
function dropdown( $current = false ) {
	$schedules = get();
	?>
	<select class="postform" name="crontrol_schedule" id="crontrol_schedule" required>
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
