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
		$I->dontSee( 'PHP Code', '#crontrol_form th' );
		$I->dontSee( 'URL', '#crontrol_form th' );
		$I->dontSee( 'HTTP Method', '#crontrol_form th' );
		$I->seeOptionIsSelected( 'input[name="crontrol_action"]', 'Standard cron event' );
		$I->fillField( 'Hook Name', 'my_hookname' );
		$I->click( 'Add Event' );
		$I->see( 'Cron Events', 'h1' );
		$I->seeAdminSuccessNotice( 'Created the cron event my_hookname.' );
	}

	public function AddingANewURLEvent( AcceptanceTester $I ) {
		$I->amOnCronEventListingPage();
		$I->click( 'Add New Cron Event', '#wpbody' );
		$I->dontSee( 'PHP Code', '#crontrol_form th' );
		$I->dontSee( 'URL', '#crontrol_form th' );
		$I->dontSee( 'HTTP Method', '#crontrol_form th' );
		$I->selectOption( 'input[name="crontrol_action"]', 'URL cron event' );
		$I->dontSee( 'PHP Code', '#crontrol_form th' );
		$I->see( 'URL', '#crontrol_form th' );
		$I->see( 'HTTP Method', '#crontrol_form th' );
		$I->fillField( '#crontrol_url', 'https://example.org/' );
		$I->click( 'Add Event' );
		$I->see( 'Cron Events', 'h1' );
		$I->seeAdminSuccessNotice( 'Created the cron event URL Cron.' );
		$I->see( 'https://example.org/' );
	}

	public function AddingANewPHPEvent( AcceptanceTester $I ) {
		$I->amOnCronEventListingPage();
		$I->click( 'Add New Cron Event', '#wpbody' );
		$I->dontSee( 'PHP Code', '#crontrol_form th' );
		$I->dontSee( 'URL', '#crontrol_form th' );
		$I->dontSee( 'HTTP Method', '#crontrol_form th' );
		$I->selectOption( 'input[name="crontrol_action"]', 'PHP cron event' );
		$I->see( 'PHP Code', '#crontrol_form th' );
		$I->dontSee( 'URL', '#crontrol_form th' );
		$I->dontSee( 'HTTP Method', '#crontrol_form th' );
		$I->fillPHPEditorField( 'amazing();' );
		$I->click( 'Add Event' );
		$I->see( 'Cron Events', 'h1' );
		$I->seeAdminSuccessNotice( 'Created the cron event PHP Cron.' );
		$I->see( 'amazing();' );
	}
}
