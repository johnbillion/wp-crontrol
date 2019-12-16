<?php
/**
 * Class for logging extra data to crontrol event logs.
 *
 * @package wp-crontrol
 */

namespace Crontrol;

use Exception;
use InvalidArgumentException;

/**
 * PSR-3 compatible logger which encapsulates the extra logging functionality during events.
 */
class Logger {

	const EMERGENCY = 'emergency';
	const ALERT     = 'alert';
	const CRITICAL  = 'critical';
	const ERROR     = 'error';
	const WARNING   = 'warning';
	const NOTICE    = 'notice';
	const INFO      = 'info';
	const DEBUG     = 'debug';

	protected $logs = array();

	public function __construct() {
		foreach ( self::get_levels() as $level ) {
			add_action( "crontrol/{$level}", array( $this, $level ), 10, 2 );
		}

		add_action( 'crontrol/log', array( $this, 'log' ), 10, 3 );
	}

	public function emergency( $message, array $context = array() ) {
		$this->store( self::EMERGENCY, $message, $context );
	}

	public function alert( $message, array $context = array() ) {
		$this->store( self::ALERT, $message, $context );
	}

	public function critical( $message, array $context = array() ) {
		$this->store( self::CRITICAL, $message, $context );
	}

	public function error( $message, array $context = array() ) {
		$this->store( self::ERROR, $message, $context );
	}

	public function warning( $message, array $context = array() ) {
		$this->store( self::WARNING, $message, $context );
	}

	public function notice( $message, array $context = array() ) {
		$this->store( self::NOTICE, $message, $context );
	}

	public function info( $message, array $context = array() ) {
		$this->store( self::INFO, $message, $context );
	}

	public function debug( $message, array $context = array() ) {
		$this->store( self::DEBUG, $message, $context );
	}

	public function log( $level, $message, array $context = array() ) {
		if ( ! in_array( $level, self::get_levels(), true ) ) {
			throw new InvalidArgumentException( __( 'Unsupported log level', 'wp-crontrol' ) );
		}

		$this->store( $level, $message, $context );
	}

	public function get_logs() {
		return $this->logs;
	}

	protected function store( $level, $message, array $context = array() ) {
		if ( is_wp_error( $message ) ) {
			$message = sprintf(
				'WP_Error: %s (%s)',
				$message->get_error_message(),
				$message->get_error_code()
			);
		}

		if ( $message instanceof Exception ) {
			$message = get_class( $message ) . ': ' . $message->getMessage();
		}

		if ( ! is_string( $message ) ) {
			$message = json_output( $message );
		}

		$this->logs[] = array(
			'message' => self::interpolate( $message, $context ),
			'context' => $context,
			'level'   => $level,
		);
	}

	protected static function interpolate( $message, array $context = array() ) {
		// build a replacement array with braces around the context keys
		$replace = array();

		foreach ( $context as $key => $val ) {
			// check that the value can be casted to string
			if ( is_bool( $val ) ) {
				$replace[ "{{$key}}" ] = ( $val ? 'true' : 'false' );
			} elseif ( is_scalar( $val ) || is_string( $val ) ) {
				$replace[ "{{$key}}" ] = $val;
			}
		}

		// interpolate replacement values into the message and return
		return strtr( $message, $replace );
	}

	public static function get_levels() {
		return array(
			self::EMERGENCY,
			self::ALERT,
			self::CRITICAL,
			self::ERROR,
			self::WARNING,
			self::NOTICE,
			self::INFO,
			self::DEBUG,
		);
	}

	public static function get_warning_levels() {
		return array(
			self::EMERGENCY,
			self::ALERT,
			self::CRITICAL,
			self::ERROR,
			self::WARNING,
		);
	}

}
