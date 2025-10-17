<?php
/**
 * Application context for WP Crontrol.
 *
 * This class encapsulates the runtime state of the plugin, including user capabilities
 * and feature enablement flags.
 *
 * @package wp-crontrol
 */

namespace Crontrol;

use Crontrol\Event\Event;

/**
 * Abstract context class for managing plugin state.
 */
abstract class Context {
	/**
	 * Get whether the user can edit a specific cron event.
	 *
	 * This checks both user capabilities and whether the event type is enabled.
	 *
	 * @param Event $event The event to check.
	 */
	abstract public function can_edit_event( Event $event ): bool;

	/**
	 * Get whether the user can delete a specific cron event.
	 *
	 * @param Event $event The event to check.
	 */
	abstract public function can_delete_event( Event $event ): bool;

	/**
	 * Get whether the user can run a specific cron event.
	 *
	 * @param Event $event The event to check.
	 */
	abstract public function can_run_event( Event $event ): bool;

	/**
	 * Get whether PHP cron events are enabled globally on the site.
	 */
	abstract public function php_crons_enabled(): bool;

	/**
	 * Get whether URL cron events are enabled globally on the site.
	 */
	abstract public function url_crons_enabled(): bool;
}
