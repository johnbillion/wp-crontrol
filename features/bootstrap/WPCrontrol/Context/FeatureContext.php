<?php
/**
 * This is the Behat context file that's used for the WordHat functional tests.
 *
 * @package wp-crontrol
 */

namespace WPCrontrol\Context;

use WordHat\Extension\Context\RawWordpressContext as WordPressContext;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends WordPressContext {
	/**
	 * Click on the element with the provided CSS Selector
	 *
	 * @When /^I click on the element with CSS selector "([^"]*)"$/
	 *
	 * @throws \InvalidArgumentException When the element cannot be found.
	 * @param string $css_selector The selector.
	 * @return void
	 */
	public function iClickOnTheElementWithCSSSelector( $css_selector ) {
		$session = $this->getSession();
		$element = $session->getPage()->find(
			'xpath',
			$session->getSelectorsHandler()->selectorToXpath( 'css', $css_selector )
		);

		if ( null === $element ) {
			throw new \InvalidArgumentException(
				sprintf(
					'Could not evaluate CSS Selector: "%s"',
					$css_selector
				)
			);
		}

		$element->click();
	}
}
