<?php
/**
 * Test context implementations for WP Crontrol.
 *
 * @package wp-crontrol
 */

namespace Crontrol\Tests;

use Crontrol\Context;
use Crontrol\Event\Event;
use Crontrol\Event\PHPCronEvent;
use Crontrol\Event\URLCronEvent;

/**
 * Base test context with all permissions and features enabled.
 *
 * This is the default "everything works" context that other test contexts
 * can extend and override specific methods.
 */
class TestContext extends Context {
	/**
	 * Get whether the user can edit a specific cron event.
	 */
	#[\Override]
	public function can_edit_event( Event $event ): bool {
		if ( $event instanceof PHPCronEvent ) {
			return $this->php_crons_enabled();
		}

		if ( $event instanceof URLCronEvent ) {
			return $this->url_crons_enabled();
		}

		return true;
	}

	/**
	 * Get whether the user can delete a specific cron event.
	 */
	#[\Override]
	public function can_delete_event( Event $event ): bool {
		return true;
	}

	/**
	 * Get whether the user can run a specific cron event.
	 */
	#[\Override]
	public function can_run_event( Event $event ): bool {
		if ( $event instanceof PHPCronEvent ) {
			return $this->php_crons_enabled();
		}

		if ( $event instanceof URLCronEvent ) {
			return $this->url_crons_enabled();
		}

		return true;
	}

	/**
	 * Get whether PHP cron events are enabled.
	 */
	#[\Override]
	public function php_crons_enabled(): bool {
		return true;
	}

	/**
	 * Get whether URL cron events are enabled.
	 */
	#[\Override]
	public function url_crons_enabled(): bool {
		return true;
	}
}

/**
 * Context where user cannot edit PHP cron events.
 */
class CannotManagePHPCronsContext extends TestContext {
	#[\Override]
	public function can_edit_event( Event $event ): bool {
		if ( $event instanceof PHPCronEvent ) {
			return false;
		}

		return parent::can_edit_event( $event );
	}

	#[\Override]
	public function can_delete_event( Event $event ): bool {
		if ( $event instanceof PHPCronEvent ) {
			return false;
		}

		return parent::can_delete_event( $event );
	}
}

/**
 * Context where user cannot edit URL cron events.
 */
class CannotManageURLCronsContext extends TestContext {
	#[\Override]
	public function can_edit_event( Event $event ): bool {
		if ( $event instanceof URLCronEvent ) {
			return false;
		}

		return parent::can_edit_event( $event );
	}

	#[\Override]
	public function can_delete_event( Event $event ): bool {
		if ( $event instanceof URLCronEvent ) {
			return false;
		}

		return parent::can_delete_event( $event );
	}
}

/**
 * Context where PHP cron events are disabled.
 */
class PHPCronsDisabledContext extends TestContext {
	#[\Override]
	public function php_crons_enabled(): bool {
		return false;
	}
}

/**
 * Context where URL cron events are disabled.
 */
class URLCronsDisabledContext extends TestContext {
	#[\Override]
	public function url_crons_enabled(): bool {
		return false;
	}
}

/**
 * Context where user has no permissions to edit any cron events.
 */
class NoPermissionsContext extends TestContext {
	#[\Override]
	public function can_edit_event( Event $event ): bool {
		if ( $event instanceof PHPCronEvent || $event instanceof URLCronEvent ) {
			return false;
		}

		return parent::can_edit_event( $event );
	}

	#[\Override]
	public function can_delete_event( Event $event ): bool {
		if ( $event instanceof PHPCronEvent || $event instanceof URLCronEvent ) {
			return false;
		}

		return parent::can_delete_event( $event );
	}
}

/**
 * Context where all cron event types are disabled.
 */
class AllCronsDisabledContext extends TestContext {
	#[\Override]
	public function php_crons_enabled(): bool {
		return false;
	}

	#[\Override]
	public function url_crons_enabled(): bool {
		return false;
	}
}

/**
 * Context where everything is disabled - no permissions and no features enabled.
 */
class NothingEnabledContext extends TestContext {
	#[\Override]
	public function can_edit_event( Event $event ): bool {
		return false;
	}

	#[\Override]
	public function can_delete_event( Event $event ): bool {
		return false;
	}

	#[\Override]
	public function can_run_event( Event $event ): bool {
		return false;
	}

	#[\Override]
	public function php_crons_enabled(): bool {
		return false;
	}

	#[\Override]
	public function url_crons_enabled(): bool {
		return false;
	}
}
