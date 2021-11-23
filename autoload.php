<?php
/**
 * Autoloader for WordPress.
 *
 * @package CompanyProject
 * @link https://make.wordpress.org/core/handbook/best-practices/coding-standards/php/#naming-conventions
 * @link https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader-examples.md#class-example
 */

declare( strict_types = 1 );

namespace Crontrol;

use function spl_autoload_register;

spl_autoload_register(
	/**
	 * Closure of the autoloader.
	 *
	 * @param class-string $class_name The fully-qualified class name.
	 * @return void
	 */
	static function ( $class_name ) {
		if ( 'WP_List_Table' === $class_name ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
			return;
		}

		/**
		 * __NAMESPACE__ could be an empty string.
		 *
		 * @phpstan-ignore-next-line
		 */
		$project_namespace = '' === __NAMESPACE__ ? '' : __NAMESPACE__ . '\\';
		$length            = strlen( $project_namespace );

		// Class is not in our namespace.
		if ( 0 !== strncmp( $project_namespace, $class_name, $length ) ) {
			return;
		}

		// E.g. Model\Item.
		$relative_class_name = substr( $class_name, $length );
		$name_parts = str_replace( '\\', '/', $relative_class_name );

		$file = sprintf(
			'%1$s/src/%2$s.php',
			__DIR__,
			$name_parts
		);

		if ( ! is_file( $file ) ) {
			return;
		}

		require $file;
	}
);

require __DIR__ . '/src/Event/functions.php';
require __DIR__ . '/src/Schedule/functions.php';
