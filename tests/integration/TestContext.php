<?php
/**
 * Test context implementations for WP Crontrol.
 *
 * @package wp-crontrol
 */

namespace Crontrol\Tests;

use Crontrol\Context\UserContext;
use Crontrol\Context\FeatureContext;

/**
 * Base test user context with all permissions enabled.
 *
 * This is the default "everything works" context that other test contexts
 * can extend and override specific methods.
 */
class TestUserContext implements UserContext {
	#[\Override]
	public function can_create_php_cron_events(): bool {
		return true;
	}

	#[\Override]
	public function can_edit_php_cron_events(): bool {
		return true;
	}

	#[\Override]
	public function can_delete_php_cron_events(): bool {
		return true;
	}

	#[\Override]
	public function can_run_php_cron_events(): bool {
		return true;
	}

	#[\Override]
	public function can_create_url_cron_events(): bool {
		return true;
	}

	#[\Override]
	public function can_edit_url_cron_events(): bool {
		return true;
	}

	#[\Override]
	public function can_delete_url_cron_events(): bool {
		return true;
	}

	#[\Override]
	public function can_run_url_cron_events(): bool {
		return true;
	}

	#[\Override]
	public function can_create_standard_cron_events(): bool {
		return true;
	}

	#[\Override]
	public function can_edit_standard_cron_events(): bool {
		return true;
	}

	#[\Override]
	public function can_delete_standard_cron_events(): bool {
		return true;
	}

	#[\Override]
	public function can_run_standard_cron_events(): bool {
		return true;
	}
}

/**
 * Base test feature context with all features enabled.
 */
class TestFeatureContext implements FeatureContext {
	#[\Override]
	public function php_crons_enabled(): bool {
		return true;
	}

	#[\Override]
	public function url_crons_enabled(): bool {
		return true;
	}
}

/**
 * User context where user cannot edit files (no edit_files capability).
 */
class CannotEditFilesUserContext extends TestUserContext {
	#[\Override]
	public function can_create_php_cron_events(): bool {
		return false;
	}

	#[\Override]
	public function can_edit_php_cron_events(): bool {
		return false;
	}

	#[\Override]
	public function can_delete_php_cron_events(): bool {
		return false;
	}
}

/**
 * Feature context where PHP cron events are disabled.
 */
class PHPCronsDisabledFeatureContext extends TestFeatureContext {
	#[\Override]
	public function php_crons_enabled(): bool {
		return false;
	}
}

/**
 * Feature context where URL cron events are disabled.
 */
class URLCronsDisabledFeatureContext extends TestFeatureContext {
	#[\Override]
	public function url_crons_enabled(): bool {
		return false;
	}
}

/**
 * User context where user has no permissions to create/edit/delete PHP/URL cron events.
 *
 * Note: User can still RUN events and can create/edit/delete/run standard events.
 */
class NoPermissionsUserContext extends TestUserContext {
	#[\Override]
	public function can_create_php_cron_events(): bool {
		return false;
	}

	#[\Override]
	public function can_edit_php_cron_events(): bool {
		return false;
	}

	#[\Override]
	public function can_delete_php_cron_events(): bool {
		return false;
	}

	#[\Override]
	public function can_create_url_cron_events(): bool {
		return false;
	}

	#[\Override]
	public function can_edit_url_cron_events(): bool {
		return false;
	}

	#[\Override]
	public function can_delete_url_cron_events(): bool {
		return false;
	}
}

/**
 * Feature context where all cron event types are disabled.
 */
class AllCronsDisabledFeatureContext extends TestFeatureContext {
	#[\Override]
	public function php_crons_enabled(): bool {
		return false;
	}

	#[\Override]
	public function url_crons_enabled(): bool {
		return false;
	}
}
