<?php
/**
 * Represents a WordPress core cron schedule.
 */

namespace Crontrol\Schedule;

/**
 * Represents a WordPress core cron schedule.
 */
final class CoreSchedule extends Schedule {
	/**
	 * Check if this schedule can be deleted.
	 *
	 * Core schedules cannot be deleted.
	 */
	#[\Override]
	public function deletable(): bool {
		return false;
	}

	/**
	 * Get the reason why this schedule cannot be deleted.
	 */
	#[\Override]
	public function get_locked_reason(): string {
		return __( 'This is a WordPress core schedule and cannot be deleted', 'wp-crontrol' );
	}
}
