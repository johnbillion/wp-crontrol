import { defineConfig } from 'vitepress'
import { RSSOptions, RssPlugin } from 'vitepress-plugin-rss'

const wpURL = 'https://wordpress.org/plugins/wp-crontrol/';
const ghURL = 'https://github.com/johnbillion/wp-crontrol';
const siteURL = 'https://wp-crontrol.com';
const year = new Date().getFullYear();

const RSS: RSSOptions = {
	title: 'WP Crontrol',
	baseUrl: siteURL,
	copyright: `Copyright (c) 2012-${year}, John Blackbourn`,
	description: 'Take control of WP-Cron',
	filename: 'feed',
}

export default defineConfig({
	title: 'WP Crontrol',
	description: 'Take control of WP-Cron',
	rewrites: {
		'help/:page.md': 'help/:page/index.md',
		'docs/:page.md': 'docs/:page/index.md',
		'about.md': 'about/index.md',
		'accessibility.md': 'accessibility/index.md',
		'privacy.md': 'privacy/index.md',
		'security.md': 'security/index.md',
	},
	head: [
		[
			'link',
			{
				rel: 'icon',
				href: '/icon.svg',
			},
		],
		[
			'link',
			{
				rel: 'alternate',
				type: 'application/rss+xml',
				title: 'WP Crontrol',
				href: `${siteURL}/feed`,
			},
		]
	],
	themeConfig: {
		logo: '/icon.svg',

		nav: [
			{
				text: 'Home',
				link: '/',
			},
			{
				text: 'Download',
				link: wpURL,
			},
		],

		sidebar: [
			{
				text: 'Docs',
				collapsed: false,
				items: [
					{
						text: 'How to use WP Crontrol',
						link: '/docs/how-to-use/',
					},
					{
						text: 'URL cron events',
						link: '/docs/url-cron-events/',
					},
					{
						text: 'PHP cron events',
						link: '/docs/php-cron-events/',
					},
					{
						text: 'What happens if I deactivate WP Crontrol?',
						link: '/docs/deactivation/',
					},
					{
						text: 'About the author',
						link: '/docs/about/',
					},
				],
			},
			{
				text: 'Help',
				collapsed: false,
				items: [
					{
						text: 'Cron events that have missed their schedule',
						link: '/help/missed-cron-events/',
					},
					{
						text: 'Problems spawning a call to the WP Cron system',
						link: '/help/problems-spawning-wp-cron/',
					},
					{
						text: 'Problems adding or editing WP Cron events',
						link: '/help/problems-managing-events/',
					},
					{
						text: 'PHP default timezone is not set to UTC',
						link: '/help/php-default-timezone/',
					},
					{
						text: 'This interval is less than the cron lock timeout',
						link: '/help/wp-cron-lock-timeout/',
					},
					{
						text: 'URL and PHP cron events that need to be checked',
						link: '/help/check-cron-events/',
					},
				],
			},
			{
				text: 'GitHub Project',
				link: ghURL,
			},
			{
				text: 'Download on WordPress.org',
				link: wpURL,
			},
			{
				text: 'Privacy statement',
				link: '/privacy/',
			},
			{
				text: 'Accessibility statement',
				link: '/accessibility/',
			},
			{
				text: 'Security policy',
				link: '/security/',
			},
		],

		socialLinks: [
			{
				icon: 'github',
				link: ghURL,
				ariaLabel: 'WP Crontrol on GitHub',
			},
			{
				icon: 'twitter',
				link: 'https://twitter.com/johnbillion',
				ariaLabel: 'WP Crontrol\'s author on Twitter',
			},
		],

		editLink: {
			pattern: 'https://github.com/johnbillion/wp-crontrol/edit/develop/docs/:path',
			text: 'Edit this page on GitHub',
		},

		search: {
			provider: 'local',
		},

		footer: {
			copyright: `© 2012-${year}, <a href="/docs/about/">John Blackbourn</a>. WP Crontrol is not associated with WordPress.`,
		},
	},
	lastUpdated: true,
	sitemap: {
		hostname: siteURL,
	},
	vite: {
		plugins: [
			RssPlugin(RSS),
		],
	},
})
