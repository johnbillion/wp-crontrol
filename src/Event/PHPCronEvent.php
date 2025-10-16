<?php
/**
 * Represents a PHP cron event.
 */

namespace Crontrol\Event;

use Crontrol\Context;

/**
 * Represents a PHP cron event.
 */
final class PHPCronEvent extends CrontrolEvent {
	/**
	 * The hook name for PHP cron events.
	 */
	public const HOOK_NAME = 'crontrol_cron_job';

	#[\Override]
	public function integrity_failed(): bool {
		$args = $this->args[0] ?? array();

		// This is a PHP cron event saved prior to WP Crontrol 1.16.2.
		if ( isset( $this->args['code'] ) ) {
			return true;
		}

		return ! check_integrity( $args['code'] ?? null, $args['hash'] ?? null );
	}

	#[\Override]
	public function has_error(): bool {
		if ( isset( $this->args[0]['syntax_error_message'] ) ) {
			return true;
		}

		return $this->integrity_failed();
	}

	#[\Override]
	public function get_args_display(): string {
		return __( 'PHP Code', 'wp-crontrol' );
	}

	#[\Override]
	public function is_enabled( Context $context ): bool {
		return $context->php_crons_enabled();
	}
}
