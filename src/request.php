<?php
/**
 * Request handler.
 */

namespace Crontrol;

/**
 * Class Request
 */
class Request {
	/**
	 * Description.
	 *
	 * @var string
	 */
	public $args = '';

	/**
	 * Description.
	 *
	 * @var string
	 */
	public $next_run_date_local = '';

	/**
	 * Description.
	 *
	 * @var string
	 */
	public $next_run_date_local_custom_date = '';

	/**
	 * Description.
	 *
	 * @var string
	 */
	public $next_run_date_local_custom_time = '';

	/**
	 * Description.
	 *
	 * @var string
	 */
	public $schedule = '';

	/**
	 * Description.
	 *
	 * @var string
	 */
	public $hookname = '';

	/**
	 * Description.
	 *
	 * @var string
	 */
	public $hookcode = '';

	/**
	 * Description.
	 *
	 * @var string
	 */
	public $eventname = '';

	/**
	 * Description.
	 *
	 * @var string
	 */
	public $url = '';

	/**
	 * Description.
	 *
	 * @var string
	 */
	public $method = '';

	/**
	 * Description.
	 *
	 * @var string
	 */
	public $original_hookname = '';

	/**
	 * Description.
	 *
	 * @var string
	 */
	public $original_sig = '';

	/**
	 * Description.
	 *
	 * @var string
	 */
	public $original_next_run_utc = '';

	/**
	 * Crontrol_Request constructor.
	 *
	 * @param array<string,mixed> $props Properties.
	 * @return Request This object.
	 */
	public function init( array $props ) {
		foreach ( $props as $name => $value ) {
			$prop = (string) preg_replace( '#^crontrol_#', '', $name );

			if ( property_exists( $this, $prop ) ) {
				$this->$prop = $value;
			}
		}

		return $this;
	}
}
