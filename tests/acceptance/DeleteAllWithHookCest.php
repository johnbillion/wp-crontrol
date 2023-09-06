<?php
/**
 * Acceptance tests for deleting all cron events with a given hook name.
 */

/**
 * Test class.
 */
class DeleteAllWithHookCest {
	public function _before( AcceptanceTester $I ) {
		$I->loginAsAdmin();
	}

	public function DeletingAHook( AcceptanceTester $I ) {
		$I->amWorkingWithACronEvent( 'example_hook', '[1]' );
		$I->amWorkingWithACronEvent( 'example_hook', '[2]' );
		$row = $I->amWorkingWithACronEvent( 'example_hook', '[3]' );

		$I->click( 'Delete all events with this hook (3)', $row );
		$I->seeAdminSuccessNotice( 'Deleted all example_hook cron events.' );

		$I->dontSee( 'example_hook', '.crontrol-events' );
	}
}
