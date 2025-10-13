<?php
/**
 * Represents an Action Scheduler cron event.
 */

namespace Crontrol\Event;

/**
 * Represents an Action Scheduler cron event.
 */
final class ActionSchedulerEvent extends Event {
	/**
	 * The hook name for Action Scheduler events.
	 */
	public const HOOK_NAME = 'action_scheduler_run_queue';

	/**
	 * Check if this is an Action Scheduler cron event.
	 *
	 * @return bool True if this is an Action Scheduler cron event, false otherwise.
	 */
	#[\Override]
	public function is_action_scheduler_cron(): bool {
		return true;
	}
}
