<?php
/**
 * Represents a PHP cron event.
 */

namespace Crontrol\Event;

/**
 * Represents a PHP cron event.
 */
class PHPCronEvent extends Event {
	/**
	 * The hook name for PHP cron events.
	 */
	public const HOOK_NAME = 'crontrol_cron_job';

	public function is_php_cron(): bool {
		return true;
	}

	public function is_crontrol_event(): bool {
		return true;
	}

	public function integrity_failed(): bool {
		$args = $this->args[0] ?? array();

		// This is a PHP cron event saved prior to WP Crontrol 1.16.2.
		if ( isset( $this->args['code'] ) ) {
			return true;
		}

		return ! \Crontrol\Event\check_integrity( $args['code'] ?? null, $args['hash'] ?? null );
	}

	public function has_error(): bool {
		if ( isset( $this->args[0]['syntax_error_message'] ) ) {
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
