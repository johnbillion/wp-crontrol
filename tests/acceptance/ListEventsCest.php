<?php
/**
 * Acceptance tests for listing cron events.
 *
 * @package wp-crontrol
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
		$I->amOnAdminPage( 'tools.php?page=crontrol_admin_manage_page' );
		$I->see( 'Cron Events', 'h1' );
		$I->see( 'Cron Events', '#crontrol-header' );
		$I->seeElement( 'table.crontrol-events' );
	}
}
