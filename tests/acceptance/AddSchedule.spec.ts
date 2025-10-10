import { test, expect } from './utils/test-setup';

test.describe( 'Adding Cron Schedules', () => {
	test.beforeAll( async ( { globalUtils } ) => {
		// Install WordPress fresh for this test suite
		globalUtils.installWordPress();
	} );

	test.beforeEach( async ( { Crontrol } ) => {
		// Login as admin before each test
		await Crontrol.loginViaPage( 'admin', 'password' );
	} );

	test( 'Navigating to the Add Cron Schedules screen', {
		annotation: {
			type: 'user-story',
			description: 'As an administrator, I need to be able to navigate to the Cron Schedules screen'
		}
	}, async ( {
		page,
		Crontrol,
	} ) => {
		await Crontrol.amOnCronScheduleListingPage();
		await expect( page.locator( '#crontrol-header' ) ).toContainText( 'Cron Schedules' );
		await expect( page.locator( 'h1' ) ).toContainText( 'Cron Schedules' );
		await expect( page.locator( 'h2' ) ).toContainText( 'Add Cron Schedule' );
	} );

	test( 'Adding a new schedule', {
		annotation: {
			type: 'user-story',
			description: 'As an administrator, I need to be able to add a custom cron schedule'
		}
	}, async ( {
		page,
		Crontrol,
	} ) => {
		await Crontrol.amOnCronScheduleListingPage();

		// Fill in the form fields
		await page.getByLabel( 'Internal Name' ).fill( 'my_schedule_name' );
		await page.getByLabel( 'Interval (seconds)' ).fill( '123' );
		await page.getByLabel( 'Display Name' ).fill( 'My Schedule Name' );

		// Submit the form
		await page.getByRole( 'button', { name: 'Add Cron Schedule' } ).click();

		// Verify success
		await expect( page.locator( '#crontrol-header' ) ).toContainText( 'Cron Schedules' );
		await expect( page.locator( 'h1' ) ).toContainText( 'Cron Schedules' );
		await Crontrol.seeAdminSuccessNotice( 'Added the cron schedule my_schedule_name.' );
	} );
} );
