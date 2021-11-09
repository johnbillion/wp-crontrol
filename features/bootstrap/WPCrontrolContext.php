<?php

use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ElementHtmlException;
use Behat\Mink\Exception\ElementTextException;
use Behat\Mink\Exception\ExpectationException;
use WordHat\Extension\Context\RawWordpressContext as WordPressContext;
use WordHat\Extension\Context\Traits\UserAwareContextTrait as UserContext;
use PHPUnit\Framework\Assert;

/**
 * Defines application features from the specific context.
 */
class WPCrontrolContext extends WordPressContext {
	use UserContext;

}
