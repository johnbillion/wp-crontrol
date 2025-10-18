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
	 * Core schedules are persistent.
	 *
	 * @return true Always returns true - this schedule is persistent.
	 */
	#[\Override]
	public function persistent(): bool {
		return true;
	}

	/**
	 * Gets the message explaining why this schedule is persistent.
	 *
	 * @return string The persistent reason message.
	 */
	#[\Override]
	public function get_persistent_message(): string {
		return __( 'This is a WordPress core schedule and cannot be deleted', 'wp-crontrol' );
	}

	/**
	 * Check if this schedule can be deleted.
	 *
	 * Core schedules cannot be deleted.
	 *
	 * @return false Always returns false - this schedule cannot be deleted.
	 */
	#[\Override]
	public function deleteable(): bool {
		return false;
	}
}
