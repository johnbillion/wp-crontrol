<?php
/**
 * Represents a cron schedule added by another plugin or theme.
 */

namespace Crontrol\Schedule;

/**
 * Represents a cron schedule added by another plugin or theme.
 */
final class ThirdPartySchedule extends Schedule {
	/**
	 * Check if this schedule can be deleted.
	 *
	 * Third-party schedules cannot be deleted.
	 */
	#[\Override]
	public function deleteable(): bool {
		return false;
	}

	/**
	 * Get the reason why this schedule cannot be deleted.
	 */
	#[\Override]
	public function get_locked_reason(): string {
		return __( 'This schedule is added by another plugin and cannot be deleted', 'wp-crontrol' );
	}
}
