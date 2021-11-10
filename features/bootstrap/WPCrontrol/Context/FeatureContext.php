<?php
/**
 * This is the Behat context file that's used for the WordHat functional tests.
 *
 * @package wp-crontrol
 */

namespace WPCrontrol\Context;

use WordHat\Extension\Context\RawWordpressContext as WordPressContext;
use WPCrontrol\PageObject\DashboardPage;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends WordPressContext {
	/**
	 * Dashboard page object.
	 *
	 * @var DashboardPage
	 */
	protected $dashboard_page;

	/**
	 * Constructor.
	 *
	 * @param DashboardPage $dashboard_page Dashboard page.
	 */
	public function __construct( DashboardPage $dashboard_page ) {
		parent::__construct();

		$this->dashboard_page = $dashboard_page;
	}

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

	/**
	 * @When I am on the Add Cron Event screen
	 *
	 * @return void
	 */
	public function iAmOnTheAddCronEventScreen() {
		$this->dashboard_page->go( 'tools.php?page=crontrol_admin_manage_page&crontrol_action=new-cron' );
	}

	/**
	 * Check the specified notification is on-screen.
	 *
	 * Example: Then I should see a success notice that says "Post published"
	 *
	 * @Then /^I should see an? (success|warning|error|info) notice that says "([^"]+)"$/
	 *
	 * @param string $type    Notice type. One of success, warning, error, or info.
	 * @param string $message Text to search for.
	 * @return void
	 */
	public function iShouldSeeNoticeThatSays( $type, $message ) {
		$selector = '.notice-' . $type;

		$this->assertSession()->elementTextContains( 'css', $selector, $message );
	}
}
