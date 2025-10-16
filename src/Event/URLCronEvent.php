<?php
/**
 * Represents a URL cron event.
 */

namespace Crontrol\Event;

use Crontrol\Context;

/**
 * Represents a URL cron event.
 */
final class URLCronEvent extends CrontrolEvent {
	/**
	 * The hook name for URL cron events.
	 */
	public const HOOK_NAME = 'crontrol_url_cron_job';

	#[\Override]
	public function integrity_failed(): bool {
		$args = $this->args[0] ?? array();
		return ! check_integrity( $args['url'] ?? null, $args['hash'] ?? null );
	}

	#[\Override]
	public function has_error(): bool {
		if ( isset( $this->args[0]['url_error_message'] ) ) {
			return true;
		}

		return $this->integrity_failed();
	}

	#[\Override]
	public function get_args_display(): string {
		return $this->args[0]['method'] . ' ' . $this->args[0]['url'];
	}

	#[\Override]
	public function is_enabled( Context $context ): bool {
		return $context->url_crons_enabled();
	}
}
