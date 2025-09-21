<?php
/**
 * Represents a WordPress core cron event.
 */

namespace Crontrol\Event;

/**
 * Represents a WordPress core cron event.
 */
class CoreCronEvent extends Event {
	/**
	 * Check if this is a WordPress core cron event.
	 *
	 * @return bool True if this is a WordPress core cron event, false otherwise.
	 */
	public function is_core_cron(): bool {
		return true;
	}

	/**
	 * Check if this event is protected.
	 *
	 * @return bool True if the event is protected, false otherwise.
	 */
	public function is_protected(): bool {
		return true;
	}

	/**
	 * Check if this event is a persistent WordPress core hook.
	 *
	 * @return bool True if this is a persistent core hook, false otherwise.
	 */
	public function is_persistent_core_hook(): bool {
		return in_array( $this->hook, \Crontrol\get_persistent_core_hooks(), true );
	}
}
