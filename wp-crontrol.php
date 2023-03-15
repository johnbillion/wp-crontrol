<?php
/**
 * Plugin Name:  WP Crontrol
 * Plugin URI:   https://wordpress.org/plugins/wp-crontrol/
 * Description:  WP Crontrol enables you to view and control what's happening in the WP-Cron system.
 * Author:       John Blackbourn & crontributors
 * Author URI:   https://github.com/johnbillion/wp-crontrol/graphs/contributors
 * Version:      1.15.2
 * Text Domain:  wp-crontrol
 * Domain Path:  /languages/
 * Requires PHP: 5.6
 * License:      GPL v2 or later
 *
 * LICENSE
 * This file is part of WP Crontrol.
 *
 * WP Crontrol is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @package    wp-crontrol
 * @author     John Blackbourn <john@johnblackbourn.com> & Edward Dale <scompt@scompt.com>
 * @copyright  Copyright 2008 Edward Dale, 2012-2023 John Blackbourn
 * @license    https://www.gnu.org/licenses/old-licenses/gpl-2.0.txt GPL 2.0
 * @link       https://wordpress.org/plugins/wp-crontrol/
 */

namespace Crontrol;

const PLUGIN_FILE = __FILE__;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! version_compare( PHP_VERSION, '5.6', '>=' ) ) {
	return;
}

$autoload = __DIR__ . '/vendor/autoload.php';

if ( ! file_exists( $autoload ) ) {
	return;
}

require_once $autoload;
require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/event.php';
require_once __DIR__ . '/src/schedule.php';

// Get this show on the road.
init_hooks();
