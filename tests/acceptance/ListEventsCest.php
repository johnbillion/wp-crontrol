<?php
/**
 * Acceptance tests for listing cron events.
 */

/**
 * Test class.
 */
class ListEventsCest {
	public function _before( AcceptanceTester $I ) {
		$I->loginAsAdmin();
		$I->amOnPluginsPage();
		$I->activatePlugin( 'wp-crontrol' );
	}

	public function ListCronEvents( AcceptanceTester $I ) {
		$I->amOnCronEventListingPage();
		$I->see( 'Cron Events', 'h1' );
		$I->see( 'Cron Events', '#crontrol-header' );
		$I->seeElement( 'table.crontrol-events' );
	}
}
