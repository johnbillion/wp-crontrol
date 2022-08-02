<?php
/**
 * Acceptance tests for pausing and resuming cron events.
 */

/**
 * Test class.
 */
class PauseEventCest {
	public function _before( AcceptanceTester $I ) {
		$I->loginAsAdmin();
	}

	public function PausingAnEvent( AcceptanceTester $I ) {
		$row = $I->amWorkingWithACronEvent( 'pause_me_soon' );

		$I->click( 'Pause', $row );
		$I->seeAdminSuccessNotice( 'Paused the pause_me_soon hook.' );
		$I->see( 'Paused', $row );

		$I->click( 'Paused events (1)' );
		$I->see( 'Paused', $row );
		$I->seeLink( 'Edit', $row );
		$I->dontSeeLink( 'Run Now', $row );
		$I->dontSeeLink( 'Pause', $row );
		$I->seeLink( 'Resume', $row );
		$I->seeLink( 'Delete', $row );

		$I->click( 'Resume', $row );
		$I->seeAdminSuccessNotice( 'Resumed the pause_me_soon hook.' );
		$I->seeLink( 'Edit', $row );
		$I->seeLink( 'Run Now', $row );
		$I->seeLink( 'Pause', $row );
		$I->dontSeeLink( 'Resume', $row );
		$I->seeLink( 'Delete', $row );
	}
}
