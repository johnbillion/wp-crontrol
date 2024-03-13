<?php
/**
 * Acceptance tests for adding cron events.
 */

/**
 * Test class.
 */
class AddEventCest {
	public function _before( AcceptanceTester $I ) {
		$I->loginAsAdmin();
	}

	public function NavigatingToTheAddCronEventScreen( AcceptanceTester $I ) {
		$I->amOnCronEventListingPage();
		$I->click( 'Add New Cron Event', '#wpbody' );
		$I->see( 'Add Cron Event', 'h1' );
		$I->see( 'Add Cron Event', '#crontrol-header' );
	}

	public function AddingANewEvent( AcceptanceTester $I ) {
		$I->amOnCronEventListingPage();
		$I->click( 'Add New Cron Event', '#wpbody' );
		$I->fillField( 'Hook Name', 'my_hookname' );
		$I->click( 'Add Event' );
		$I->see( 'Cron Events', 'h1' );
		$I->seeAdminSuccessNotice( 'Created the cron event my_hookname.' );
	}

	public function AddingANewURLEvent( AcceptanceTester $I ) {
		$I->amOnCronEventListingPage();
		$I->click( 'Add New', '#wpbody' );
		$I->dontSee( 'PHP Code' );
		$I->dontSee( 'URL' );
		$I->dontSee( 'HTTP Method' );
		$I->selectOption( 'input[name="crontrol_action"]', 'Request a URL' );
		$I->dontSee( 'PHP Code' );
		$I->see( 'URL' );
		$I->see( 'HTTP Method' );
		$I->fillField( 'URL', 'https://example.org/' );
		$I->click( 'Add Event' );
		$I->see( 'Cron Events', 'h1' );
		$I->seeAdminSuccessNotice( 'Created the cron event URL Cron.' );
		$I->see( 'https://example.org/' );
	}

	public function AddingANewPHPEvent( AcceptanceTester $I ) {
		$I->amOnCronEventListingPage();
		$I->click( 'Add New Cron Event', '#wpbody' );
		$I->dontSee( 'PHP Code' );
		$I->dontSee( 'URL' );
		$I->dontSee( 'HTTP Method' );
		$I->selectOption( 'input[name="crontrol_action"]', 'Execute PHP' );
		$I->see( 'PHP Code' );
		$I->dontSee( 'URL' );
		$I->dontSee( 'HTTP Method' );
		$I->fillPHPEditorField( 'amazing();' );
		$I->click( 'Add Event' );
		$I->see( 'Cron Events', 'h1' );
		$I->seeAdminSuccessNotice( 'Created the cron event PHP Cron.' );
		$I->see( 'amazing();' );
	}
}
