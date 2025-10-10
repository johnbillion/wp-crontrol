import { test, expect } from './utils/test-setup';

test.describe( 'Listing Cron Events', () => {
	test.beforeAll( async ( { globalUtils } ) => {
		// Install WordPress fresh for this test suite
		globalUtils.installWordPress();
	} );

	test.beforeEach( async ( { Crontrol } ) => {
		// Login as admin before each test
		await Crontrol.loginViaPage( 'admin', 'password' );
	} );

	test( 'List cron events', {
		annotation: {
			type: 'user-story',
			description: 'As an administrator, I need to be able to view the list of cron events'
		}
	}, async ( {
		page,
		Crontrol,
	} ) => {
		await Crontrol.amOnCronEventListingPage();
		await expect( page.locator( 'h1' ) ).toContainText( 'Cron Events' );
		await expect( page.locator( '#crontrol-header' ) ).toContainText( 'Cron Events' );
		await expect( page.locator( 'table.crontrol-events' ) ).toBeVisible();
	} );
} );
