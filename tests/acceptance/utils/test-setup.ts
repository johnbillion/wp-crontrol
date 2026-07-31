import { test as base, expect, Page } from '@playwright/test';
import { CrontrolUtils } from './wp-crontrol.js';
import { CrontrolGlobalUtils } from './wp-crontrol-global-utils.js';
import { captureHtmlOnFailure } from '@johnbillion/plugin-infrastructure/acceptance';

class Admin {
	private page: Page;

	constructor( page: Page ) {
		this.page = page;
	}

	async visitAdminPage( path: string = '', queryString: string = '' ) {
		const url = `/wp-admin/${path}${queryString ? '?' + queryString : ''}`;
		await this.page.goto( url );
	}
}

type CrontrolFixtures = {
	admin: Admin;
	Crontrol: CrontrolUtils;
	globalUtils: CrontrolGlobalUtils;
};

export const test = base.extend<CrontrolFixtures>( {
	page: async ( { page }, use ) => {
		/*
		 * WordPress 7.0 enables cross-document view transitions in the admin area, which
		 * suspend rendering of the incoming document. Under a headless browser that never
		 * resolves, so no frames get produced and Playwright's element stability check can
		 * never settle. Core gates that CSS behind `prefers-reduced-motion: no-preference`.
		 */
		await page.emulateMedia( { reducedMotion: 'reduce' } );
		await use( page );
	},
	admin: async ( { page }, use ) => {
		const admin = new Admin( page );
		await use( admin );
	},
	Crontrol: async ( { page, admin, globalUtils }, use ) => {
		const Crontrol = new CrontrolUtils( page, admin, globalUtils );
		await use( Crontrol );
	},
	globalUtils: async ( {}, use, testInfo ) => {
		const baseURL = testInfo.project.use.baseURL!;
		const globalUtils = new CrontrolGlobalUtils( { baseURL, pluginSlug: 'wp-crontrol' } );
		await use( globalUtils );
	},
} );

captureHtmlOnFailure( test );

export { expect };
