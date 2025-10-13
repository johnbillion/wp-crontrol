<?php
/**
 * Base class for cron schedules.
 */

namespace Crontrol\Schedule;

/**
 * Base class for cron schedules.
 */
abstract class Schedule {
	/**
	 * The internal name of the schedule.
	 *
	 * @var string
	 */
	public string $name;

	/**
	 * The interval between executions in seconds.
	 *
	 * @var int
	 */
	public int $interval;

	/**
	 * The display name of the schedule.
	 *
	 * @var string
	 */
	public string $display;

	/**
	 * Constructor.
	 *
	 * @param string $name     The internal name of the schedule.
	 * @param int    $interval The interval between executions in seconds.
	 * @param string $display  The display name of the schedule.
	 */
	protected function __construct( string $name, int $interval, string $display ) {
		$this->name = $name;
		$this->interval = $interval;
		$this->display = $display;
	}

	/**
	 * Factory method to create appropriate Schedule instance.
	 *
	 * @param string $name     The internal name of the schedule.
	 * @param int    $interval The interval between executions in seconds.
	 * @param string $display  The display name of the schedule.
	 * @return self The appropriate Schedule instance.
	 */
	public static function create( string $name, int $interval, string $display ): self {
		if ( in_array( $name, \Crontrol\get_core_schedules(), true ) ) {
			return new CoreSchedule( $name, $interval, $display );
		}

		return new CustomSchedule( $name, $interval, $display );
	}

	/**
	 * Check if this schedule's interval is too frequent (less than WP_CRON_LOCK_TIMEOUT).
	 *
	 * @return bool True if the schedule is too frequent, false otherwise.
	 */
	public function is_too_frequent(): bool {
		return $this->interval < WP_CRON_LOCK_TIMEOUT;
	}

	/**
	 * Check if this is a WordPress core schedule.
	 *
	 * @return bool True if this is a WordPress core schedule, false otherwise.
	 */
	public function is_core_schedule(): bool {
		return false;
	}

	/**
	 * Check if this schedule is custom (added by WP Crontrol).
	 *
	 * @return bool True if this is a custom schedule, false otherwise.
	 */
	public function is_custom_schedule(): bool {
		return false;
	}

	/**
	 * Check if this schedule is protected (cannot be deleted).
	 *
	 * A schedule is protected if it's a core schedule, added by another plugin,
	 * or is currently in use by events.
	 *
	 * @return bool True if the schedule is protected, false otherwise.
	 */
	public function is_protected(): bool {
		return false;
	}

	/**
	 * Check if this schedule is currently in use by any events.
	 *
	 * @return bool True if the schedule is in use, false otherwise.
	 */
	public function is_in_use(): bool {
		$events = \Crontrol\Event\get();

		foreach ( $events as $event ) {
			if ( $event->schedule === $this->name ) {
				return true;
			}
		}

		return false;
	}
}
