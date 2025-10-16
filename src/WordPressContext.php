<?php
/**
 * WordPress environment context implementation.
 *
 * @package wp-crontrol
 */

namespace Crontrol;

use Crontrol\Event\Event;
use Crontrol\Event\PHPCronEvent;
use Crontrol\Event\URLCronEvent;

/**
 * Context implementation that reads from the WordPress environment.
 *
 * This is the production implementation used in the plugin.
 */
class WordPressContext extends Context {
	/**
	 * Get whether the user can edit a specific cron event.
	 *
	 * @param Event $event The event to check.
	 */
	#[\Override]
	public function can_edit_event( Event $event ): bool {
		if ( $event instanceof PHPCronEvent ) {
			return current_user_can_edit_php_cron_events() && php_cron_events_enabled();
		}

		if ( $event instanceof URLCronEvent ) {
			return current_user_can_edit_url_cron_events() && url_cron_events_enabled();
		}

		// Standard, core, and Action Scheduler events - check default capability
		return current_user_can( 'manage_options' );
	}

	/**
	 * Get whether the user can delete a specific cron event.
	 *
	 * @param Event $event The event to check.
	 */
	#[\Override]
	public function can_delete_event( Event $event ): bool {
		if ( $event instanceof PHPCronEvent ) {
			return current_user_can_edit_php_cron_events();
		}

		if ( $event instanceof URLCronEvent ) {
			return current_user_can_edit_url_cron_events();
		}

		// Standard, core, and Action Scheduler events - check default capability
		return current_user_can( 'manage_options' );
	}

	/**
	 * Get whether the user can run a specific cron event.
	 *
	 * @param Event $event The event to check.
	 */
	#[\Override]
	public function can_run_event( Event $event ): bool {
		if ( $event instanceof PHPCronEvent ) {
			return php_cron_events_enabled();
		}

		if ( $event instanceof URLCronEvent ) {
			return url_cron_events_enabled();
		}

		// Standard, core, and Action Scheduler events can be run
		return true;
	}

	/**
	 * Get whether PHP cron events are enabled globally on the site.
	 */
	#[\Override]
	public function php_crons_enabled(): bool {
		return php_cron_events_enabled();
	}

	/**
	 * Get whether URL cron events are enabled globally on the site.
	 */
	#[\Override]
	public function url_crons_enabled(): bool {
		return url_cron_events_enabled();
	}
}
