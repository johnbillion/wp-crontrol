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
	 * Check if this is a WordPress core schedule.
	 *
	 * @return bool True if this is a WordPress core schedule, false otherwise.
	 */
	#[\Override]
	public function is_core_schedule(): bool {
		return true;
	}

	/**
	 * Check if this schedule is protected (cannot be deleted).
	 *
	 * Core schedules are always protected.
	 *
	 * @return bool True if the schedule is protected, false otherwise.
	 */
	#[\Override]
	public function is_protected(): bool {
		return true;
	}
}
