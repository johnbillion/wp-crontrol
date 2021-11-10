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
	/**
	 * Navigates to the given page on wp-admin.
	 *
	 * @param string $page The page name.
	 * @return void
	 */
	public function go( $page ) {
		$this->path = '/wp-admin/' . $page;

		$this->open();
	}

}
