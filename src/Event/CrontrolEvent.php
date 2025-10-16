<?php
/**
 * Base class for WP Crontrol managed events (PHP and URL cron events).
 */

namespace Crontrol\Event;

use Crontrol\Context;

/**
 * Abstract base class for WP Crontrol managed events.
 *
 * Provides common logic for PHP and URL cron events.
 */
abstract class CrontrolEvent extends Event {
	#[\Override]
	final public function hook_name_editable(): bool {
		return false;
	}

	#[\Override]
	final public function pausable(): bool {
		return false;
	}

	/**
	 * Check if this event is editable.
	 *
	 * @param Context $context The application context.
	 */
	#[\Override]
	final public function editable( Context $context ): bool {
		return $context->can_edit_event( $this );
	}

	/**
	 * Check if this event can be run.
	 *
	 * @param Context $context The application context.
	 */
	#[\Override]
	final public function runnable( Context $context ): bool {
		return $context->can_run_event( $this ) && ! $this->has_error() && ! $this->is_paused();
	}

	/**
	 * Check if this event can be deleted.
	 *
	 * @param Context $context The application context.
	 */
	#[\Override]
	final public function deleteable( Context $context ): bool {
		return $context->can_delete_event( $this );
	}
}
