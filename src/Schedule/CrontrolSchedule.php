<?php
/**
 * Represents a custom cron schedule added by WP Crontrol.
 */

namespace Crontrol\Schedule;

/**
 * Represents a custom cron schedule added by WP Crontrol.
 */
final class CrontrolSchedule extends Schedule {
	/**
	 * Check if this schedule can be deleted.
	 *
	 * WP Crontrol schedules cannot be deleted if they're currently in use by events.
	 */
	#[\Override]
	public function deletable(): bool {
		return ! $this->is_in_use();
	}

	/**
	 * Get the reason why this schedule cannot be deleted, if applicable.
	 */
	#[\Override]
	public function get_locked_reason(): string {
		if ( $this->is_in_use() ) {
			return __( 'This custom schedule is in use and cannot be deleted', 'wp-crontrol' );
		}

		return '';
	}
}
