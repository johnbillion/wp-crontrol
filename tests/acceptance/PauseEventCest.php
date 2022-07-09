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
	}
}
