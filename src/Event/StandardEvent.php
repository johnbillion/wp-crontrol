<?php
/**
 * Represents a standard WordPress cron event.
 */

namespace Crontrol\Event;

use Crontrol\Context;

use function Crontrol\json_output;

/**
 * Represents a standard WordPress cron event.
 */
final class StandardEvent extends Event {
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
		return true;
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
