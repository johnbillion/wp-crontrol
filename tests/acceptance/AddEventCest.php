<?php
/**
 * Acceptance tests for adding cron events.
 */

/**
 * Test class.
 */
class AddEventCest {
	public function _before( AcceptanceTester $I ): void {
		$I->loginAsAdmin();
	}

	/**
	 * Test adding a new event via the modal (progressive enhancement).
	 */
	public function AddingANewEventViaModal( AcceptanceTester $I ): void {
		$I->amOnCronEventListingPage();

		// Click the Add New button which should trigger the modal
		$I->click( 'Add New Cron Event', '#wpbody' );

		// Wait for modal to appear
		$I->wait(1);

		// Verify modal is present
		$I->seeElement('.crontrol-modal');
		$I->see( 'Add New Cron Event', '.crontrol-modal h2' );

		// Fill in the form in the modal
		$I->dontSee( 'PHP Code', '.crontrol-modal th' );
		$I->seeOptionIsSelected( '.crontrol-modal input[name="crontrol_event_type"]', 'standard' );
		$I->fillField( '.crontrol-modal #crontrol_hookname', 'my_modal_hookname' );
		$I->click( 'Add Event', '.crontrol-modal' );

		// Verify we're back on the listing page with success message
		$I->see( 'Cron Events', 'h1' );
		$I->seeAdminSuccessNotice( 'Saved the cron event my_modal_hookname.' );
	}

	/**
	 * Test adding a new event by directly navigating to the add page (server-side form).
	 */
	public function AddingANewEventViaDirectURL( AcceptanceTester $I ): void {
		// Navigate directly to the add event page
		$I->amOnAdminPage( 'tools.php?page=wp-crontrol&crontrol_action=new-cron' );

		// Verify we're on the server-side form page
		$I->dontSeeElement('.crontrol-modal');
		$I->see( 'Add Cron Event', 'h1' );
		$I->see( 'Add Cron Event', '#crontrol-header' );

		// Fill in the form
		$I->dontSee( 'PHP Code', '#crontrol_form th' );
		$I->seeOptionIsSelected( 'input[name="crontrol_action"]', 'Standard cron event' );
		$I->fillField( 'Hook Name', 'my_direct_hookname' );
		$I->click( 'Add Event' );

		// Verify success
		$I->see( 'Cron Events', 'h1' );
		$I->seeAdminSuccessNotice( 'Saved the cron event my_direct_hookname.' );
	}


	/**
	 * Test adding a URL event via modal.
	 */
	public function AddingANewURLEventViaModal( AcceptanceTester $I ): void {
		$I->amOnCronEventListingPage();
		$I->click( 'Add New Cron Event', '#wpbody' );
		$I->wait(1);

		// Work within the modal
		$I->seeElement('.crontrol-modal');
		$I->see( 'Add New Cron Event', '.crontrol-modal h2' );
		$I->dontSee( 'PHP Code', '.crontrol-modal th' );
		$I->dontSee( 'URL', '.crontrol-modal th' );
		$I->dontSee( 'HTTP Method', '.crontrol-modal th' );
		$I->selectOption( '.crontrol-modal input[name="crontrol_event_type"]', 'url' );
		$I->dontSee( 'PHP Code', '.crontrol-modal th' );
		$I->see( 'URL', '.crontrol-modal th' );
		$I->see( 'HTTP Method', '.crontrol-modal th' );
		$I->fillField( '.crontrol-modal #crontrol_url', 'https://example.org/' );
		$I->click( 'Add Event', '.crontrol-modal' );
		$I->see( 'Cron Events', 'h1' );
		$I->seeAdminSuccessNotice( 'URL cron event saved.' );
		$I->see( 'https://example.org/' );
	}

	/**
	 * Test adding a URL event via direct URL.
	 */
	public function AddingANewURLEventViaDirectURL( AcceptanceTester $I ): void {
		$I->amOnAdminPage( 'tools.php?page=wp-crontrol&crontrol_action=new-cron' );

		// Work with the server-side form
		$I->dontSeeElement('.crontrol-modal');
		$I->see( 'Add Cron Event', 'h1' );
		$I->dontSee( 'PHP Code', 'th' );
		$I->dontSee( 'URL', 'th' );
		$I->dontSee( 'HTTP Method', 'th' );
		$I->selectOption( 'input[name="crontrol_action"]', 'new_url_cron' );
		$I->dontSee( 'PHP Code', 'th' );
		$I->see( 'URL', 'th' );
		$I->see( 'HTTP Method', 'th' );
		$I->fillField( '#crontrol_url', 'https://example.com/' );
		$I->click( 'Add Event' );
		$I->see( 'Cron Events', 'h1' );
		$I->seeAdminSuccessNotice( 'URL cron event saved.' );
		$I->see( 'https://example.com/' );
	}

	/**
	 * Test adding URL event with disallowed URL via modal.
	 */
	public function AddingANewURLEventWithDisallowedURLViaModal( AcceptanceTester $I ): void {
		$I->amOnCronEventListingPage();
		$I->click( 'Add New Cron Event', '#wpbody' );
		$I->wait(1);

		$I->seeElement('.crontrol-modal');
		$I->selectOption( '.crontrol-modal input[name="crontrol_event_type"]', 'url' );
		$I->fillField( '.crontrol-modal #crontrol_url', 'http://localhost:22' );
		$I->click( 'Add Event', '.crontrol-modal' );
		$I->see( 'Cron Events', 'h1' );
		$I->seeAdminErrorNotice( 'The cron event was saved but contains an error: The URL "http://localhost:22" is not allowed' );

		$row = $I->amWorkingWithAnExistingCronEvent( 'http://localhost:22' );
		$I->see( 'Edit', $row );
		$I->see( 'Delete', $row );
		$I->dontSee( 'Run now', $row );
	}

	/**
	 * Test adding a PHP event via modal.
	 */
	public function AddingANewPHPEventViaModal( AcceptanceTester $I ): void {
		$I->amOnCronEventListingPage();
		$I->click( 'Add New Cron Event', '#wpbody' );
		$I->wait(1);

		$I->seeElement('.crontrol-modal');
		$I->see( 'Add New Cron Event', '.crontrol-modal h2' );
		$I->dontSee( 'PHP Code', '.crontrol-modal th' );
		$I->dontSee( 'URL', '.crontrol-modal th' );
		$I->dontSee( 'HTTP Method', '.crontrol-modal th' );
		$I->selectOption( '.crontrol-modal input[name="crontrol_event_type"]', 'php' );
		$I->see( 'PHP Code', '.crontrol-modal th' );
		$I->dontSee( 'URL', '.crontrol-modal th' );
		$I->dontSee( 'HTTP Method', '.crontrol-modal th' );
		$I->fillPHPEditorField( 'amazing();' );
		$I->click( 'Add Event', '.crontrol-modal' );
		$I->see( 'Cron Events', 'h1' );
		$I->seeAdminSuccessNotice( 'PHP cron event saved.' );
		$I->see( 'amazing();' );
	}

	/**
	 * Test adding a PHP event via direct URL.
	 */
	public function AddingANewPHPEventViaDirectURL( AcceptanceTester $I ): void {
		$I->amOnAdminPage( 'tools.php?page=wp-crontrol&crontrol_action=new-cron' );

		$I->dontSeeElement('.crontrol-modal');
		$I->see( 'Add Cron Event', 'h1' );
		$I->dontSee( 'PHP Code', 'th' );
		$I->dontSee( 'URL', 'th' );
		$I->dontSee( 'HTTP Method', 'th' );
		$I->selectOption( 'input[name="crontrol_action"]', 'new_php_cron' );
		$I->see( 'PHP Code', 'th' );
		$I->dontSee( 'URL', 'th' );
		$I->dontSee( 'HTTP Method', 'th' );
		$I->fillPHPEditorField( 'wonderful();' );
		$I->click( 'Add Event' );
		$I->see( 'Cron Events', 'h1' );
		$I->seeAdminSuccessNotice( 'PHP cron event saved.' );
		$I->see( 'wonderful();' );
	}
}
