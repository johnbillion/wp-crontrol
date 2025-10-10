<?php
/**
 * Represents a URL cron event.
 */

namespace Crontrol\Event;

/**
 * Represents a URL cron event.
 */
class URLCronEvent extends Event {
	/**
	 * The hook name for URL cron events.
	 */
	public const HOOK_NAME = 'crontrol_url_cron_job';

	public function is_url_cron(): bool {
		return true;
	}

	public function is_crontrol_event(): bool {
		return true;
	}

	public function integrity_failed(): bool {
		$args = $this->args[0] ?? array();
		return ! \Crontrol\Event\check_integrity( $args['url'] ?? null, $args['hash'] ?? null );
	}

	public function has_error(): bool {
		if ( isset( $this->args[0]['url_error_message'] ) ) {
			return true;
		}

		return $this->integrity_failed();
	}

	/**
	 * Check if this event is protected.
	 *
	 * @return bool True if the event is protected, false otherwise.
	 */
	public function is_protected(): bool {
		return true;
	}
}
