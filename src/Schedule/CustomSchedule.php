<?php
/**
 * Represents a custom cron schedule.
 */

namespace Crontrol\Schedule;

/**
 * Represents a custom cron schedule.
 */
final class CustomSchedule extends Schedule {
	/**
	 * Check if this schedule is custom (added by WP Crontrol).
	 *
	 * @return bool True if this is a custom schedule managed by WP Crontrol, false otherwise.
	 */
	#[\Override]
	public function is_custom_schedule(): bool {
		/** @var array<string,int|string> */
		$custom_schedules = get_option( 'crontrol_schedules', array() );

		return isset( $custom_schedules[ $this->name ] );
	}

	/**
	 * Check if this schedule is protected (cannot be deleted).
	 *
	 * Custom schedules are protected if they're not managed by WP Crontrol
	 * or if they're currently in use by events.
	 *
	 * @return bool True if the schedule is protected, false otherwise.
	 */
	#[\Override]
	public function is_protected(): bool {
		// If it's not a WP Crontrol custom schedule, it's protected
		if ( ! $this->is_custom_schedule() ) {
			return true;
		}

		// If it's in use, it's protected
		return $this->is_in_use();
	}
}
