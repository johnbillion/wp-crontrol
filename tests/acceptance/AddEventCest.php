<?php
/**
 * Acceptance tests for adding cron events.
 *
 * @package wp-crontrol
 */

/**
 * Test class.
 */
class AddEventCest {
	public function _before( AcceptanceTester $I ) {
		$I->loginAsAdmin();
		$I->amOnPluginsPage();
		$I->activatePlugin( 'wp-crontrol' );
	}

	public function NavigatingToTheAddCronEventScreen( AcceptanceTester $I ) {
		$I->amOnCronEventListingPage();
		$I->click( 'Add New', '#wpbody' );
		$I->see( 'Add Cron Event', 'h1' );
		$I->see( 'Add Cron Event', '#crontrol-header' );
	}

	public function AddingANewEvent( AcceptanceTester $I ) {
		$I->amOnCronEventListingPage();
		$I->click( 'Add New', '#wpbody' );
		$I->fillField( 'Hook Name', 'my_hookname' );
		$I->click( 'Add Event' );
		$I->see( 'Cron Events', 'h1' );
		$I->seeAdminSuccessNotice( 'Created the cron event my_hookname.' );
	}

	public function AddingANewPHPEvent( AcceptanceTester $I ) {
		$I->amOnCronEventListingPage();
		$I->click( 'Add New', '#wpbody' );
		$I->dontSee( 'PHP Code' );
		$I->selectOption( 'input[name="crontrol_action"]', 'PHP cron event' );
		$I->see( 'PHP Code' );
		$I->fillPHPEditorField( 'amazing();' );
		$I->click( 'Add Event' );
		$I->see( 'Cron Events', 'h1' );
		$I->seeAdminSuccessNotice( 'Created the cron event PHP Cron.' );
		$I->see( 'amazing();' );
	}
}
