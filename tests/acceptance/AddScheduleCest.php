<?php
/**
 * Acceptance tests for adding cron schedules.
 */

/**
 * Test class.
 */
class AddScheduleCest {
	public function _before( AcceptanceTester $I ) {
		$I->loginAsAdmin();
	}

	public function NavigatingToTheAddCronSchedulesScreen( AcceptanceTester $I ) {
		$I->amOnCronScheduleListingPage();
		$I->see( 'Cron Schedules', '#crontrol-header' );
		$I->see( 'Cron Schedules', 'h1' );
		$I->see( 'Add Cron Schedule', 'h2' );
	}

	public function AddingANewSchedule( AcceptanceTester $I ) {
		$I->amOnCronScheduleListingPage();
		$I->fillField( 'Internal Name', 'my_schedule_name' );
		$I->fillField( 'Interval (seconds)', '123' );
		$I->fillField( 'Display Name', 'My Schedule Name' );
		$I->click( 'Add Cron Schedule' );
		$I->see( 'Cron Schedules', '#crontrol-header' );
		$I->see( 'Cron Schedules', 'h1' );
		$I->seeAdminSuccessNotice( 'Added the cron schedule my_schedule_name.' );
	}
}
