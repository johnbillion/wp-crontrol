<?php
/**
 * Represents a WordPress core cron event.
 */

namespace Crontrol\Event;

use Crontrol\Context;

use function Crontrol\get_persistent_core_hooks;
use function Crontrol\json_output;

/**
 * Represents a WordPress core cron event.
 */
final class CoreCronEvent extends Event {
	/**
	 * Check if this event's hook name can be edited.
	 *
	 * @return bool Whether the event's hook name can be edited.
	 */
	#[\Override]
	public function hook_name_editable(): bool {
		return false;
	}

	/**
	 * Core events are persistent if they're in the persistent core hooks list.
	 *
	 * @return bool Whether this event is persistent.
	 */
	#[\Override]
	public function persistent(): bool {
		return in_array( $this->hook, get_persistent_core_hooks(), true );
	}

	/**
	 * Gets the message explaining why this event is persistent.
	 *
	 * @return string The persistent reason message.
	 */
	#[\Override]
	public function get_persistent_message(): string {
		return __( 'This is a WordPress core event and cannot be deleted', 'wp-crontrol' );
	}

	#[\Override]
	public function editable( Context $context ): bool {
		return true;
	}

	#[\Override]
	public function runnable( Context $context ): bool {
		return ! $this->has_error() && ! $this->is_paused();
	}

	#[\Override]
	public function deleteable( Context $context ): bool {
		return ! $this->persistent();
	}

	#[\Override]
	public function pausable(): bool {
		return true;
	}

	#[\Override]
	public function get_args_display(): string {
		if ( empty( $this->args ) ) {
			return '';
		}
		return json_output( $this->args, false );
	}

	#[\Override]
	public function is_enabled( Context $context ): bool {
		return true;
	}
}
