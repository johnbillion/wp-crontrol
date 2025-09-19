<?php
/**
 * Acceptance tests for editing cron events.
 */

/**
 * Test class.
 */
class EditEventCest {
	public function _before( AcceptanceTester $I ): void {
		$I->loginAsAdmin();
	}

	/**
	 * Test editing an existing event via the modal.
	 */
	public function EditingAnEventViaModal( AcceptanceTester $I ): void {
		// First create an event to edit
		$I->amOnCronEventListingPage();
		$I->click( 'Add New Cron Event', '#wpbody' );
		$I->wait(1);

		// Add event via modal first
		$I->seeElement('.crontrol-modal');
		$I->fillField( '.crontrol-modal #crontrol_hookname', 'test_event_to_edit' );
		$I->selectOption( '.crontrol-modal input[name="crontrol_next_run_date_local"]', '+1 day' );
		$I->click( 'Add Event', '.crontrol-modal' );
		$I->see( 'Cron Events', 'h1' );

		// Find and click the edit link for our event
		$row = $I->amWorkingWithAnExistingCronEvent( 'test_event_to_edit' );
		$I->click( 'Edit', $row );

		// Wait for modal to appear
		$I->wait(1);

		// Verify modal is present with edit form
		$I->seeElement('.crontrol-modal');
		$I->see( 'Edit Cron Event', '.crontrol-modal h2' );

		// Modify the hook name
		$I->fillField( '.crontrol-modal #crontrol_hookname', 'test_event_edited' );
		$I->click( 'Update Event', '.crontrol-modal' );

		// Verify success
		$I->see( 'Cron Events', 'h1' );
		$I->seeAdminSuccessNotice( 'Saved the cron event test_event_edited.' );
		$I->see( 'test_event_edited' );
	}

	/**
	 * Test editing an event by directly navigating to the edit URL.
	 */
	public function EditingAnEventViaDirectURL( AcceptanceTester $I ): void {
		// First create an event to edit using the direct form
		$I->amOnAdminPage( 'tools.php?page=wp-crontrol&crontrol_action=new-cron' );
		$I->fillField( 'Hook Name', 'test_event_direct_edit' );
		$I->selectOption( 'input[name="crontrol_next_run_date_local"]', 'Tomorrow' );
		$I->click( 'Add Event' );
		$I->see( 'Cron Events', 'h1' );

		// Find the edit link for our event
		$I->see( 'test_event_direct_edit' );
		$editUrl = $I->grabAttributeFrom( '//tr[contains(., "test_event_direct_edit")]//a[contains(text(), "Edit")]', 'href' );

		// Navigate directly to the edit URL
		$I->amOnUrl( $editUrl );

		// Verify we're on the server-side edit form
		$I->dontSeeElement('.crontrol-modal');
		$I->see( 'Edit Cron Event', 'h1' );
		$I->see( 'Edit Cron Event', 'span.nav-tab-active' );

		// Modify the hook name
		$I->fillField( 'Hook Name', 'test_event_direct_edited' );
		$I->click( 'Update Event' );

		// Verify success
		$I->see( 'Cron Events', 'h1' );
		$I->seeAdminSuccessNotice( 'Saved the cron event test_event_direct_edited.' );
		$I->see( 'test_event_direct_edited' );
	}
}
