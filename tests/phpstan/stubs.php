<?php

// These stub definitions are used during PHPStan static analysis.

// WP core constants:
define( 'WP_CRON_LOCK_TIMEOUT', 123 );

class QM_Util {
	/**
	 * @return array<string,mixed> The updated callback entry.
	 */
	public static function populate_callback( array $callback ) {}
}

class QM_Output_Html {
	/**
	 * @return string
	 */
	public static function output_filename( string $text, string $file, int $line = 0, bool $is_filename = false ) {}
}
