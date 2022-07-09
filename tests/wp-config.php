<?php
/**
 * This is the configuration file that's used for the acceptance tests and WP-CLI commands.
 */

mysqli_report( MYSQLI_REPORT_OFF );

// Force the wp-admin area to use 'mobile' mode so list table row actions are shown.
$GLOBALS['wp_filter'] = array(
	'admin_body_class' => array(
		10 => array(
			array(
				'accepted_args' => 1,
				'function' => function( $classes ) {
					return $classes .= ' mobile';
				},
			),
		),
	),
);

define( 'WP_DEBUG', ! empty( getenv( 'WORDPRESS_DEBUG' ) ) );

// Prevent WP-Cron doing its thing during testing.
define( 'DISABLE_WP_CRON', true );

// WARNING WARNING WARNING!
// These tests will DROP ALL TABLES in the database with the prefix named below.
// DO NOT use a production database or one that is shared with something else.
define( 'DB_NAME',     getenv( 'WORDPRESS_DB_NAME' ) );
define( 'DB_USER',     getenv( 'WORDPRESS_DB_USER' ) );
define( 'DB_PASSWORD', getenv( 'WORDPRESS_DB_PASSWORD' ) );
define( 'DB_HOST',     getenv( 'WORDPRESS_DB_HOST' ) );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_acceptance_';

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
