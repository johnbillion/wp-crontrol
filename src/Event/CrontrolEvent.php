<?php
/**
 * Base class for WP Crontrol managed events (PHP and URL cron events).
 */

namespace Crontrol\Event;

use Crontrol\Context\UserContext;
use Crontrol\Context\FeatureContext;

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
	 * Subclasses must implement to check specific capabilities.
	 *
	 * @param UserContext $user User capability context.
	 * @param FeatureContext $features Feature flag context.
	 */
	#[\Override]
	abstract public function editable( UserContext $user, FeatureContext $features ): bool;

	/**
	 * Check if this event can be run.
	 *
	 * Subclasses must implement to check specific capabilities.
	 *
	 * @param UserContext $user User capability context.
	 * @param FeatureContext $features Feature flag context.
	 */
	#[\Override]
	abstract public function runnable( UserContext $user, FeatureContext $features ): bool;

	/**
	 * Check if this event can be deleted.
	 *
	 * Subclasses must implement to check specific capabilities.
	 *
	 * @param UserContext $user User capability context.
	 * @param FeatureContext $features Feature flag context.
	 */
	#[\Override]
	abstract public function deletable( UserContext $user, FeatureContext $features ): bool;
}
