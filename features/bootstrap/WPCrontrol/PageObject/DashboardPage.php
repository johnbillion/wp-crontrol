<?php
/**
 * ...
 *
 * @package wp-crontrol
 */

namespace WPCrontrol\PageObject;

/**
 * Defines application features from the specific context.
 */
class DashboardPage extends \WordHat\Extension\PageObject\DashboardPage {
	public function go( string $page ) {
		$this->path = '/wp-admin/' . $page;

		$this->open();
	}

}
