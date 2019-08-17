<?php
/**
 * Plugin Name: WP Crontrol
 * Plugin URI:  https://wordpress.org/plugins/wp-crontrol/
 * Description: WP Crontrol lets you view and control what's happening in the WP-Cron system.
 * Author:      John Blackbourn & crontributors
 * Author URI:  https://github.com/johnbillion/wp-crontrol/graphs/contributors
 * Version:     1.7.1
 * Text Domain: wp-crontrol
 * Domain Path: /languages/
 * Requires PHP: 5.3.6
 * License:     GPL v2 or later
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
 * @package    WP Crontrol
 * @author     Edward Dale <scompt@scompt.com> & John Blackbourn <john@johnblackbourn.com>
 * @copyright  Copyright 2008 Edward Dale, 2012-2019 John Blackbourn
 * @license    http://www.gnu.org/licenses/gpl.txt GPL 2.0
 * @link       https://wordpress.org/plugins/wp-crontrol/
 * @since      0.2
 */

namespace Crontrol;

use WP_Error;

defined( 'ABSPATH' ) || die();

require_once __DIR__ . '/src/event.php';
require_once __DIR__ . '/src/schedule.php';
require_once __DIR__ . '/src/log.php';

/**
 * Hook onto all of the actions and filters needed by the plugin.
 */
function init_hooks() {
	$plugin_file = plugin_basename( __FILE__ );

	add_action( 'init',                               __NAMESPACE__ . '\action_init' );
	add_action( 'init',                               __NAMESPACE__ . '\action_handle_posts' );
	add_action( 'admin_menu',                         __NAMESPACE__ . '\action_admin_menu' );
	add_filter( "plugin_action_links_{$plugin_file}", __NAMESPACE__ . '\plugin_action_links', 10, 4 );
	add_filter( 'removable_query_args',               __NAMESPACE__ . '\filter_removable_query_args' );

	add_action( 'load-tools_page_crontrol_admin_manage_page', __NAMESPACE__ . '\enqueue_code_editor' );

	add_filter( 'cron_schedules',        __NAMESPACE__ . '\filter_cron_schedules' );
	add_action( 'crontrol_cron_job',     __NAMESPACE__ . '\action_php_cron_event' );
	add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\enqueue_styles' );

	add_action( 'init', array( Log::get_instance(), 'init' ) );
}

/**
 * Evaluates the provided code using eval.
 *
 * @param string $code The PHP code to evaluate.
 */
function action_php_cron_event( $code ) {
	// phpcs:ignore Squiz.PHP.Eval.Discouraged
	eval( $code );
}

/**
 * Run using the 'init' action.
 */
function action_init() {
	load_plugin_textdomain( 'wp-crontrol', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

/**
 * Handles any POSTs made by the plugin. Run using the 'init' action.
 */
function action_handle_posts() {
	if ( isset( $_POST['new_cron'] ) ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to add new cron events.', 'wp-crontrol' ), 401 );
		}
		check_admin_referer( 'new-cron' );
		extract( wp_unslash( $_POST ), EXTR_PREFIX_ALL, 'in' );
		if ( 'crontrol_cron_job' === $in_hookname && ! current_user_can( 'edit_files' ) ) {
			wp_die( esc_html__( 'You are not allowed to add new PHP cron events.', 'wp-crontrol' ), 401 );
		}
		$in_args  = json_decode( $in_args, true );
		$next_run = ( 'custom' === $in_next_run_date ) ? $in_next_run_date_custom : $in_next_run_date;
		Event\add( $next_run, $in_schedule, $in_hookname, $in_args );
		$redirect = array(
			'page'             => 'crontrol_admin_manage_page',
			'crontrol_message' => '5',
			'crontrol_name'    => rawurlencode( $in_hookname ),
		);
		wp_safe_redirect( add_query_arg( $redirect, admin_url( 'tools.php' ) ) );
		exit;

	} elseif ( isset( $_POST['new_php_cron'] ) ) {
		if ( ! current_user_can( 'edit_files' ) ) {
			wp_die( esc_html__( 'You are not allowed to add new PHP cron events.', 'wp-crontrol' ), 401 );
		}
		check_admin_referer( 'new-cron' );
		extract( wp_unslash( $_POST ), EXTR_PREFIX_ALL, 'in' );
		$next_run = ( 'custom' === $in_next_run_date ) ? $in_next_run_date_custom : $in_next_run_date;
		$args     = array(
			'code' => $in_hookcode,
			'name' => $in_eventname,
		);
		Event\add( $next_run, $in_schedule, 'crontrol_cron_job', $args );
		$hookname = ( ! empty( $in_eventname ) ) ? $in_eventname : __( 'PHP Cron', 'wp-crontrol' );
		$redirect = array(
			'page'             => 'crontrol_admin_manage_page',
			'crontrol_message' => '5',
			'crontrol_name'    => rawurlencode( $hookname ),
		);
		wp_safe_redirect( add_query_arg( $redirect, admin_url( 'tools.php' ) ) );
		exit;

	} elseif ( isset( $_POST['edit_cron'] ) ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to edit cron events.', 'wp-crontrol' ), 401 );
		}

		extract( wp_unslash( $_POST ), EXTR_PREFIX_ALL, 'in' );
		check_admin_referer( "edit-cron_{$in_original_hookname}_{$in_original_sig}_{$in_original_next_run}" );

		if ( 'crontrol_cron_job' === $in_hookname && ! current_user_can( 'edit_files' ) ) {
			wp_die( esc_html__( 'You are not allowed to edit PHP cron events.', 'wp-crontrol' ), 401 );
		}

		$in_args = json_decode( $in_args, true );
		Event\delete( $in_original_hookname, $in_original_sig, $in_original_next_run );
		$next_run = ( 'custom' === $in_next_run_date ) ? $in_next_run_date_custom : $in_next_run_date;
		Event\add( $next_run, $in_schedule, $in_hookname, $in_args );
		$redirect = array(
			'page'             => 'crontrol_admin_manage_page',
			'crontrol_message' => '4',
			'crontrol_name'    => rawurlencode( $in_hookname ),
		);
		wp_safe_redirect( add_query_arg( $redirect, admin_url( 'tools.php' ) ) );
		exit;

	} elseif ( isset( $_POST['edit_php_cron'] ) ) {
		if ( ! current_user_can( 'edit_files' ) ) {
			wp_die( esc_html__( 'You are not allowed to edit PHP cron events.', 'wp-crontrol' ), 401 );
		}

		extract( wp_unslash( $_POST ), EXTR_PREFIX_ALL, 'in' );
		check_admin_referer( "edit-cron_{$in_original_hookname}_{$in_original_sig}_{$in_original_next_run}" );
		$args = array(
			'code' => $in_hookcode,
			'name' => $in_eventname,
		);
		Event\delete( $in_original_hookname, $in_original_sig, $in_original_next_run );
		$next_run = ( 'custom' === $in_next_run_date ) ? $in_next_run_date_custom : $in_next_run_date;
		Event\add( $next_run, $in_schedule, 'crontrol_cron_job', $args );
		$hookname = ( ! empty( $in_eventname ) ) ? $in_eventname : __( 'PHP Cron', 'wp-crontrol' );
		$redirect = array(
			'page'             => 'crontrol_admin_manage_page',
			'crontrol_message' => '4',
			'crontrol_name'    => rawurlencode( $hookname ),
		);
		wp_safe_redirect( add_query_arg( $redirect, admin_url( 'tools.php' ) ) );
		exit;

	} elseif ( isset( $_POST['new_schedule'] ) ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to add new cron schedules.', 'wp-crontrol' ), 401 );
		}
		check_admin_referer( 'new-sched' );
		$name     = wp_unslash( $_POST['internal_name'] );
		$interval = wp_unslash( $_POST['interval'] );
		$display  = wp_unslash( $_POST['display_name'] );

		// The user entered something that wasn't a number.
		// Try to convert it with strtotime.
		if ( ! is_numeric( $interval ) ) {
			$now    = time();
			$future = strtotime( $interval, $now );
			if ( false === $future || $now > $future ) {
				$redirect = array(
					'page'             => 'crontrol_admin_options_page',
					'crontrol_message' => '7',
					'crontrol_name'    => rawurlencode( $interval ),
				);
				wp_safe_redirect( add_query_arg( $redirect, admin_url( 'options-general.php' ) ) );
				exit;
			}
			$interval = $future - $now;
		} elseif ( $interval <= 0 ) {
			$redirect = array(
				'page'             => 'crontrol_admin_options_page',
				'crontrol_message' => '7',
				'crontrol_name'    => rawurlencode( $interval ),
			);
			wp_safe_redirect( add_query_arg( $redirect, admin_url( 'options-general.php' ) ) );
			exit;
		}

		Schedule\add( $name, $interval, $display );
		$redirect = array(
			'page'             => 'crontrol_admin_options_page',
			'crontrol_message' => '3',
			'crontrol_name'    => rawurlencode( $name ),
		);
		wp_safe_redirect( add_query_arg( $redirect, admin_url( 'options-general.php' ) ) );
		exit;

	} elseif ( isset( $_GET['action'] ) && 'delete-sched' === $_GET['action'] ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to delete cron schedules.', 'wp-crontrol' ), 401 );
		}
		$id = wp_unslash( $_GET['id'] );
		check_admin_referer( "delete-sched_{$id}" );
		Schedule\delete( $id );
		$redirect = array(
			'page'             => 'crontrol_admin_options_page',
			'crontrol_message' => '2',
			'crontrol_name'    => rawurlencode( $id ),
		);
		wp_safe_redirect( add_query_arg( $redirect, admin_url( 'options-general.php' ) ) );
		exit;

	} elseif ( isset( $_POST['delete_crons'] ) ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to delete cron events.', 'wp-crontrol' ), 401 );
		}
		check_admin_referer( 'bulk-delete-crons' );

		if ( empty( $_POST['delete'] ) ) {
			return;
		}

		$delete  = wp_unslash( $_POST['delete'] );
		$deleted = 0;

		foreach ( $delete as $next_run => $events ) {
			foreach ( $events as $id => $sig ) {
				if ( 'crontrol_cron_job' === $id && ! current_user_can( 'edit_files' ) ) {
					continue;
				}
				if ( Event\delete( urldecode( $id ), $sig, $next_run ) ) {
					$deleted++;
				}
			}
		}

		$redirect = array(
			'page'             => 'crontrol_admin_manage_page',
			'crontrol_name'    => $deleted,
			'crontrol_message' => '9',
		);
		wp_safe_redirect( add_query_arg( $redirect, admin_url( 'tools.php' ) ) );
		exit;

	} elseif ( isset( $_GET['action'] ) && 'delete-cron' === $_GET['action'] ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to delete cron events.', 'wp-crontrol' ), 401 );
		}
		$id       = wp_unslash( $_GET['id'] );
		$sig      = wp_unslash( $_GET['sig'] );
		$next_run = intval( $_GET['next_run'] );
		check_admin_referer( "delete-cron_{$id}_{$sig}_{$next_run}" );

		if ( 'crontrol_cron_job' === $id && ! current_user_can( 'edit_files' ) ) {
			wp_die( esc_html__( 'You are not allowed to delete PHP cron events.', 'wp-crontrol' ), 401 );
		}

		if ( Event\delete( $id, $sig, $next_run ) ) {
			$redirect = array(
				'page'             => 'crontrol_admin_manage_page',
				'crontrol_message' => '6',
				'crontrol_name'    => rawurlencode( $id ),
			);
			wp_safe_redirect( add_query_arg( $redirect, admin_url( 'tools.php' ) ) );
			exit;
		} else {
			$redirect = array(
				'page'             => 'crontrol_admin_manage_page',
				'crontrol_message' => '7',
				'crontrol_name'    => rawurlencode( $id ),
			);
			wp_safe_redirect( add_query_arg( $redirect, admin_url( 'tools.php' ) ) );
			exit;

		};

	} elseif ( isset( $_GET['action'] ) && 'delete-hook' === $_GET['action'] ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to delete cron events.', 'wp-crontrol' ), 401 );
		}
		$id      = wp_unslash( $_GET['id'] );
		$deleted = false;
		check_admin_referer( "delete-hook_{$id}" );

		if ( 'crontrol_cron_job' === $id ) {
			wp_die( esc_html__( 'You are not allowed to delete PHP cron events.', 'wp-crontrol' ), 401 );
		}

		if ( function_exists( 'wp_unschedule_hook' ) ) {
			$deleted = wp_unschedule_hook( $id );
		}

		if ( 0 === $deleted ) {
			$redirect = array(
				'page'             => 'crontrol_admin_manage_page',
				'crontrol_message' => '3',
				'crontrol_name'    => rawurlencode( $id ),
			);
			wp_safe_redirect( add_query_arg( $redirect, admin_url( 'tools.php' ) ) );
			exit;
		} elseif ( $deleted ) {
			$redirect = array(
				'page'             => 'crontrol_admin_manage_page',
				'crontrol_message' => '2',
				'crontrol_name'    => rawurlencode( $id ),
			);
			wp_safe_redirect( add_query_arg( $redirect, admin_url( 'tools.php' ) ) );
			exit;
		} else {
			$redirect = array(
				'page'             => 'crontrol_admin_manage_page',
				'crontrol_message' => '7',
				'crontrol_name'    => rawurlencode( $id ),
			);
			wp_safe_redirect( add_query_arg( $redirect, admin_url( 'tools.php' ) ) );
			exit;
		}
	} elseif ( isset( $_GET['action'] ) && 'run-cron' === $_GET['action'] ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to run cron events.', 'wp-crontrol' ), 401 );
		}
		$id  = wp_unslash( $_GET['id'] );
		$sig = wp_unslash( $_GET['sig'] );
		check_admin_referer( "run-cron_{$id}_{$sig}" );
		if ( Event\run( $id, $sig ) ) {
			$redirect = array(
				'page'             => 'crontrol_admin_manage_page',
				'crontrol_message' => '1',
				'crontrol_name'    => rawurlencode( $id ),
			);
			wp_safe_redirect( add_query_arg( $redirect, admin_url( 'tools.php' ) ) );
			exit;
		} else {
			$redirect = array(
				'page'             => 'crontrol_admin_manage_page',
				'crontrol_message' => '8',
				'crontrol_name'    => rawurlencode( $id ),
			);
			wp_safe_redirect( add_query_arg( $redirect, admin_url( 'tools.php' ) ) );
			exit;
		}
	}
}

/**
 * Adds options & management pages to the admin menu.
 *
 * Run using the 'admin_menu' action.
 */
function action_admin_menu() {
	add_options_page( esc_html__( 'Cron Schedules', 'wp-crontrol' ), esc_html__( 'Cron Schedules', 'wp-crontrol' ), 'manage_options', 'crontrol_admin_options_page', __NAMESPACE__ . '\admin_options_page' );
	add_management_page( esc_html__( 'Cron Events', 'wp-crontrol' ), esc_html__( 'Cron Events', 'wp-crontrol' ), 'manage_options', 'crontrol_admin_manage_page', __NAMESPACE__ . '\admin_manage_page' );
}

/**
 * Adds items to the plugin's action links on the Plugins listing screen.
 *
 * @param string[] $actions     Array of action links.
 * @param string   $plugin_file Path to the plugin file relative to the plugins directory.
 * @param array    $plugin_data An array of plugin data.
 * @param string   $context     The plugin context.
 * @return string[] Array of action links.
 */
function plugin_action_links( $actions, $plugin_file, $plugin_data, $context ) {
	$actions['crontrol-events']    = sprintf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'tools.php?page=crontrol_admin_manage_page' ) ),
		esc_html__( 'Cron Events', 'wp-crontrol' )
	);
	$actions['crontrol-schedules'] = sprintf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'options-general.php?page=crontrol_admin_options_page' ) ),
		esc_html__( 'Cron Schedules', 'wp-crontrol' )
	);

	return $actions;
}

/**
 * Gives WordPress the plugin's set of cron schedules.
 *
 * Called by the `cron_schedules` filter.
 *
 * @param array[] $scheds Array of cron schedule arrays. Usually empty.
 * @return array[] Array of modified cron schedule arrays.
 */
function filter_cron_schedules( $scheds ) {
	$new_scheds = get_option( 'crontrol_schedules', array() );
	return array_merge( $new_scheds, $scheds );
}

/**
 * Displays the options page for the plugin.
 */
function admin_options_page() {
	$schedules        = Schedule\get();
	$events           = Event\get();
	$custom_schedules = get_option( 'crontrol_schedules', array() );
	$custom_keys      = array_keys( $custom_schedules );

	$used_schedules = array_unique( wp_list_pluck( $events, 'schedule' ) );

	$messages = array(
		/* translators: 1: The name of the cron schedule. */
		'2' => __( 'Successfully deleted the cron schedule %s.', 'wp-crontrol' ),
		/* translators: 1: The name of the cron schedule. */
		'3' => __( 'Successfully added the cron schedule %s.', 'wp-crontrol' ),
		/* translators: 1: The name of the cron schedule. */
		'7' => __( 'Cron schedule not added because there was a problem parsing %s.', 'wp-crontrol' ),
	);
	if ( isset( $_GET['crontrol_message'] ) && isset( $_GET['crontrol_name'] ) && isset( $messages[ $_GET['crontrol_message'] ] ) ) {
		$hook    = wp_unslash( $_GET['crontrol_name'] );
		$message = wp_unslash( $_GET['crontrol_message'] );
		$msg     = sprintf(
			esc_html( $messages[ $message ] ),
			'<strong>' . esc_html( $hook ) . '</strong>'
		);

		printf(
			'<div id="message" class="updated notice is-dismissible"><p>%s</p></div>',
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$msg
		);
	}

	?>
	<div class="wrap">
	<h1><?php esc_html_e( 'WP-Cron Schedules', 'wp-crontrol' ); ?></h1>
	<p><?php esc_html_e( 'WP-Cron schedules are the time intervals that are available for scheduling events. You can only delete custom schedules.', 'wp-crontrol' ); ?></p>
	<table class="widefat striped">
	<thead>
		<tr>
			<th scope="col"><?php esc_html_e( 'Name', 'wp-crontrol' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Interval', 'wp-crontrol' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Display Name', 'wp-crontrol' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Delete', 'wp-crontrol' ); ?></th>
		</tr>
	</thead>
	<tbody>
	<?php
	if ( empty( $schedules ) ) {
		?>
		<tr colspan="4"><td><?php esc_html_e( 'You currently have no schedules. Add one below.', 'wp-crontrol' ); ?></td></tr>
		<?php
	} else {
		foreach ( $schedules as $name => $data ) {
			printf(
				'<tr id="sched-%s">',
				esc_attr( $name )
			);
			printf(
				'<td>%s</td>',
				esc_html( $name )
			);

			if ( $data['interval'] < 600 ) {
				printf(
					'<td>%s (%s)<br><span style="color:#c00"><span class="dashicons dashicons-warning" aria-hidden="true"></span>%s</span></td>',
					esc_html( $data['interval'] ),
					esc_html( interval( $data['interval'] ) ),
					esc_html__( 'An interval of less than 10 minutes may be unreliable.', 'wp-crontrol' )
				);
			} else {
				printf(
					'<td>%s (%s)</td>',
					esc_html( $data['interval'] ),
					esc_html( interval( $data['interval'] ) )
				);
			}

			printf(
				'<td>%s</td>',
				esc_html( $data['display'] )
			);

			echo '<td>';
			if ( in_array( $name, $custom_keys, true ) ) {
				if ( in_array( $name, $used_schedules, true ) ) {
					esc_html_e( 'This custom schedule is in use and cannot be deleted', 'wp-crontrol' );
				} else {
					$url = add_query_arg( array(
						'page'   => 'crontrol_admin_options_page',
						'action' => 'delete-sched',
						'id'     => rawurlencode( $name ),
					), admin_url( 'options-general.php' ) );
					$url = wp_nonce_url( $url, 'delete-sched_' . $name );
					printf( '<span class="row-actions visible"><span class="delete"><a href="%s">%s</a></span></span>',
						esc_url( $url ),
						esc_html__( 'Delete', 'wp-crontrol' )
					);
				}
			} else {
				echo '&nbsp;';
			}
			echo '</td>';
			echo '</tr>';
		}
	}
	?>
	</tbody>
	</table>
	</div>
	<div class="wrap">
		<p class="description">
			<?php
				printf(
					'<a href="%s">%s</a>',
					esc_url( admin_url( 'tools.php?page=crontrol_admin_manage_page' ) ),
					esc_html__( 'Manage Cron Events', 'wp-crontrol' )
				);
			?>
		</p>
	</div>
	<div class="wrap narrow">
		<h2 class="title"><?php esc_html_e( 'Add Cron Schedule', 'wp-crontrol' ); ?></h2>
		<p><?php esc_html_e( 'Adding a new cron schedule will allow you to schedule events that re-occur at the given interval.', 'wp-crontrol' ); ?></p>
		<form method="post" action="options-general.php?page=crontrol_admin_options_page">
			<table class="form-table">
				<tbody>
				<tr>
					<th valign="top" scope="row"><label for="cron_internal_name"><?php esc_html_e( 'Internal name', 'wp-crontrol' ); ?></label></th>
					<td><input type="text" class="regular-text" value="" id="cron_internal_name" name="internal_name" required/></td>
				</tr>
				<tr>
					<th valign="top" scope="row"><label for="cron_interval"><?php esc_html_e( 'Interval (seconds)', 'wp-crontrol' ); ?></label></th>
					<td><input type="number" class="regular-text" value="" id="cron_interval" name="interval" min="1" step="1" required/></td>
				</tr>
				<tr>
					<th valign="top" scope="row"><label for="cron_display_name"><?php esc_html_e( 'Display name', 'wp-crontrol' ); ?></label></th>
					<td><input type="text" class="regular-text" value="" id="cron_display_name" name="display_name" required/></td>
				</tr>
			</tbody></table>
			<p class="submit"><input id="schedadd-submit" type="submit" class="button-primary" value="<?php esc_attr_e( 'Add Cron Schedule', 'wp-crontrol' ); ?>" name="new_schedule"/></p>
			<?php wp_nonce_field( 'new-sched' ); ?>
		</form>
	</div>
	<?php
}

/**
 * Gets the status of WP-Cron functionality on the site by performing a test spawn. Cached for one hour when all is well.
 *
 * @param bool $cache Whether to use the cached result from previous calls.
 * @return true|WP_Error Boolean true if the cron spawner is working as expected, or a `WP_Error` object if not.
 */
function test_cron_spawn( $cache = true ) {
	global $wp_version;

	if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
		/* translators: 1: The name of the PHP constant that is set. */
		return new WP_Error( 'crontrol_info', sprintf( __( 'The %s constant is set to true. WP-Cron spawning is disabled.', 'wp-crontrol' ), 'DISABLE_WP_CRON' ) );
	}

	if ( defined( 'ALTERNATE_WP_CRON' ) && ALTERNATE_WP_CRON ) {
		/* translators: 1: The name of the PHP constant that is set. */
		return new WP_Error( 'crontrol_info', sprintf( __( 'The %s constant is set to true.', 'wp-crontrol' ), 'ALTERNATE_WP_CRON' ) );
	}

	$cached_status = get_transient( 'crontrol-cron-test-ok' );

	if ( $cache && $cached_status ) {
		return true;
	}

	$sslverify     = version_compare( $wp_version, 4.0, '<' );
	$doing_wp_cron = sprintf( '%.22F', microtime( true ) );

	$cron_request = apply_filters( 'cron_request', array(
		'url'  => site_url( 'wp-cron.php?doing_wp_cron=' . $doing_wp_cron ),
		'key'  => $doing_wp_cron,
		'args' => array(
			'timeout'   => 3,
			'blocking'  => true,
			'sslverify' => apply_filters( 'https_local_ssl_verify', $sslverify ),
		),
	) );

	$cron_request['args']['blocking'] = true;

	$result = wp_remote_post( $cron_request['url'], $cron_request['args'] );

	if ( is_wp_error( $result ) ) {
		return $result;
	} elseif ( wp_remote_retrieve_response_code( $result ) >= 300 ) {
		return new WP_Error( 'unexpected_http_response_code', sprintf(
			/* translators: 1: The HTTP response code. */
			__( 'Unexpected HTTP response code: %s', 'wp-crontrol' ),
			intval( wp_remote_retrieve_response_code( $result ) )
		) );
	} else {
		set_transient( 'crontrol-cron-test-ok', 1, 3600 );
		return true;
	}

}

/**
 * Shows the status of WP-Cron functionality on the site. Only displays a message when there's a problem.
 */
function show_cron_status() {
	$status = test_cron_spawn();

	if ( is_wp_error( $status ) ) {
		if ( 'crontrol_info' === $status->get_error_code() ) {
			?>
			<div id="cron-status-notice" class="notice notice-info">
				<p><?php echo esc_html( $status->get_error_message() ); ?></p>
			</div>
			<?php
		} else {
			?>
			<div id="cron-status-error" class="error">
				<p>
					<?php
					printf(
						/* translators: 1: Error message text. */
						esc_html__( 'There was a problem spawning a call to the WP-Cron system on your site. This means WP-Cron events on your site may not work. The problem was: %s', 'wp-crontrol' ),
						'<br><strong>' . esc_html( $status->get_error_message() ) . '</strong>'
					);
					?>
				</p>
			</div>
			<?php
		}
	}
}

/**
 * Get the display name for the site's timezone.
 *
 * @return string The name and UTC offset for the site's timezone.
 */
function get_timezone_name() {
	$timezone_string = get_option( 'timezone_string', '' );
	$gmt_offset      = get_option( 'gmt_offset', 0 );

	if ( $gmt_offset >= 0 ) {
		$gmt_offset = '+' . $gmt_offset;
	}

	if ( '' === $timezone_string ) {
		$name = sprintf( 'UTC%s', $gmt_offset );
	} else {
		$name = sprintf( '%s (UTC%s)', str_replace( '_', ' ', $timezone_string ), $gmt_offset );
	}

	return $name;
}

/**
 * Shows the form used to add/edit cron events.
 *
 * @param bool  $is_php   Whether this is a PHP cron event.
 * @param mixed $existing An array of existing values for the cron event, or null.
 */
function show_cron_form( $is_php, $existing ) {
	$new_tabs    = array(
		'cron'     => __( 'Add Cron Event', 'wp-crontrol' ),
		'php-cron' => __( 'Add PHP Cron Event', 'wp-crontrol' ),
	);
	$modify_tabs = array(
		'cron'     => __( 'Edit Cron Event', 'wp-crontrol' ),
		'php-cron' => __( 'Edit PHP Cron Event', 'wp-crontrol' ),
	);
	$new_links   = array(
		'cron'     => admin_url( 'tools.php?page=crontrol_admin_manage_page&action=new-cron' ) . '#crontrol_form',
		'php-cron' => admin_url( 'tools.php?page=crontrol_admin_manage_page&action=new-php-cron' ) . '#crontrol_form',
	);

	$display_args = '';

	if ( $is_php ) {
		$helper_text = esc_html__( 'Cron events trigger actions in your code. Enter the schedule of the event, as well as the PHP code to execute when the action is triggered.', 'wp-crontrol' );
	} else {
		$helper_text = sprintf(
			/* translators: %s: A file name */
			esc_html__( 'Cron events trigger actions in your code. A cron event needs a corresponding action hook somewhere in code, e.g. the %1$s file in your theme.', 'wp-crontrol' ),
			'<code>functions.php</code>'
		);
	}

	if ( is_array( $existing ) ) {
		$other_fields  = wp_nonce_field( "edit-cron_{$existing['hookname']}_{$existing['sig']}_{$existing['next_run']}", '_wpnonce', true, false );
		$other_fields .= sprintf( '<input name="original_hookname" type="hidden" value="%s" />',
			esc_attr( $existing['hookname'] )
		);
		$other_fields .= sprintf( '<input name="original_sig" type="hidden" value="%s" />',
			esc_attr( $existing['sig'] )
		);
		$other_fields .= sprintf( '<input name="original_next_run" type="hidden" value="%s" />',
			esc_attr( $existing['next_run'] )
		);
		if ( ! empty( $existing['args'] ) ) {
			$display_args = wp_json_encode( $existing['args'] );
		}
		$action        = $is_php ? 'edit_php_cron' : 'edit_cron';
		$button        = $is_php ? $modify_tabs['php-cron'] : $modify_tabs['cron'];
		$show_edit_tab = true;
		$next_run_date = get_date_from_gmt( date( 'Y-m-d H:i:s', $existing['next_run'] ), 'Y-m-d H:i:s' );
	} else {
		$other_fields = wp_nonce_field( 'new-cron', '_wpnonce', true, false );
		$existing     = array(
			'hookname' => '',
			'args'     => array(),
			'next_run' => 'now',
			'schedule' => false,
		);

		$action        = $is_php ? 'new_php_cron' : 'new_cron';
		$button        = $is_php ? $new_tabs['php-cron'] : $new_tabs['cron'];
		$show_edit_tab = false;
		$next_run_date = '';
	}

	if ( $is_php ) {
		if ( ! isset( $existing['args']['code'] ) ) {
			$existing['args']['code'] = '';
		}
		if ( ! isset( $existing['args']['name'] ) ) {
			$existing['args']['name'] = '';
		}
	}

	$allowed = ( ! $is_php || current_user_can( 'edit_files' ) );
	?>
	<div id="crontrol_form" class="wrap narrow">
		<h2 class="nav-tab-wrapper">
			<a href="<?php echo esc_url( $new_links['cron'] ); ?>" class="nav-tab<?php echo ( ! $show_edit_tab && ! $is_php ) ? ' nav-tab-active' : ''; ?>"><?php echo esc_html( $new_tabs['cron'] ); ?></a>
			<a href="<?php echo esc_url( $new_links['php-cron'] ); ?>" class="nav-tab<?php echo ( ! $show_edit_tab && $is_php ) ? ' nav-tab-active' : ''; ?>"><?php echo esc_html( $new_tabs['php-cron'] ); ?></a>
			<?php if ( $show_edit_tab ) { ?>
				<span class="nav-tab nav-tab-active"><?php echo esc_html( $button ); ?></span>
			<?php } ?>
		</h2>
		<?php
		if ( $allowed ) {
			printf(
				'<p>%s</p>',
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$helper_text
			);
			?>
		<form method="post" action="<?php echo esc_url( admin_url( 'tools.php?page=crontrol_admin_manage_page' ) ); ?>">
			<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $other_fields;
			?>
			<table class="form-table"><tbody>
				<?php if ( $is_php ) : ?>
					<tr>
						<th valign="top" scope="row"><label for="hookcode"><?php esc_html_e( 'PHP Code', 'wp-crontrol' ); ?></label></th>
						<td>
							<p class="description">
								<?php
									printf(
										/* translators: The PHP tag name */
										esc_html__( 'The opening %s tag must not be included.', 'wp-crontrol' ),
										'<code>&lt;?php</code>'
									);
								?>
							</p>
							<p><textarea class="large-text code" rows="10" cols="50" id="hookcode" name="hookcode"><?php echo esc_textarea( $existing['args']['code'] ); ?></textarea></p>
						</td>
					</tr>
					<tr>
						<th valign="top" scope="row"><label for="eventname"><?php esc_html_e( 'Event Name (optional)', 'wp-crontrol' ); ?></label></th>
						<td><input type="text" class="regular-text" id="eventname" name="eventname" value="<?php echo esc_attr( $existing['args']['name'] ); ?>"/></td>
					</tr>
				<?php else : ?>
					<tr>
						<th valign="top" scope="row"><label for="hookname"><?php esc_html_e( 'Hook Name', 'wp-crontrol' ); ?></label></th>
						<td><input type="text" class="regular-text" id="hookname" name="hookname" value="<?php echo esc_attr( $existing['hookname'] ); ?>" required /></td>
					</tr>
					<tr>
						<th valign="top" scope="row"><label for="args"><?php esc_html_e( 'Arguments (optional)', 'wp-crontrol' ); ?></label></th>
						<td>
							<input type="text" class="regular-text" id="args" name="args" value="<?php echo esc_attr( $display_args ); ?>"/>
							<p class="description">
								<?php
									printf(
										/* translators: 1, 2, and 3: Example values for an input field. */
										esc_html__( 'Use a JSON encoded array, e.g. %1$s, %2$s, or %3$s', 'wp-crontrol' ),
										'<code>[25]</code>',
										'<code>["asdf"]</code>',
										'<code>["i","want",25,"cakes"]</code>'
									);
								?>
							</p>
						</td>
					</tr>
				<?php endif; ?>
				<tr>
					<th valign="top" scope="row"><label for="next_run_date"><?php esc_html_e( 'Next Run', 'wp-crontrol' ); ?></label></th>
					<td>
						<ul>
							<li>
								<label>
									<input type="radio" name="next_run_date" value="now()" checked>
									<?php esc_html_e( 'Now', 'wp-crontrol' ); ?>
								</label>
							</li>
							<li>
								<label>
									<input type="radio" name="next_run_date" value="+1 day">
									<?php esc_html_e( 'Tomorrow', 'wp-crontrol' ); ?>
								</label>
							</li>
							<li>
								<label>
									<input type="radio" name="next_run_date" value="custom" id="next_run_date_custom" <?php checked( $show_edit_tab ); ?>>
									<?php
									printf(
										/* translators: %s: An input field for specifying a date and time */
										esc_html__( 'At: %s', 'wp-crontrol' ),
										sprintf(
											'<input type="text" name="next_run_date_custom" value="%s" class="regular-text" onfocus="jQuery(\'#next_run_date_custom\').prop(\'checked\',true);" />',
											esc_attr( $next_run_date )
										)
									);
									?>
								</label>
							</li>
						</ul>

						<p class="description">
							<?php
								printf(
									/* translators: 1: Date/time format for an input field, 2: PHP function name. */
									esc_html__( 'Format: %1$s or anything accepted by %2$s', 'wp-crontrol' ),
									'<code>YYYY-MM-DD HH:MM:SS</code>',
									'<a href="https://www.php.net/manual/en/function.strtotime.php"><code>strtotime()</code></a>'
								);
							?>
						</p>
						<p class="description">
							<?php
								printf(
									/* translators: %s Timezone name. */
									esc_html__( 'Timezone: %s', 'wp-crontrol' ),
									'<code>' . esc_html( get_timezone_name() ) . '</code>'
								);
							?>
						</p>
					</td>
				</tr><tr>
					<th valign="top" scope="row"><label for="schedule"><?php esc_html_e( 'Recurrence', 'wp-crontrol' ); ?></label></th>
					<td>
						<?php Schedule\dropdown( $existing['schedule'] ); ?>
						<p class="description">
							<?php
							printf(
								'<a href="%s">%s</a>',
								esc_url( admin_url( 'options-general.php?page=crontrol_admin_options_page' ) ),
								esc_html__( 'Manage Cron Schedules', 'wp-crontrol' )
							);
							?>
						</p>
					</td>
				</tr>
			</tbody></table>
			<p class="submit"><input type="submit" class="button-primary" value="<?php echo esc_attr( $button ); ?>" name="<?php echo esc_attr( $action ); ?>"/></p>
		</form>
		<?php } else { ?>
			<div class="error inline">
				<p><?php esc_html_e( 'You cannot add, edit, or delete PHP cron events because your user account does not have the ability to edit files.', 'wp-crontrol' ); ?></p>
			</div>
		<?php } ?>
	</div>
	<?php
}

/**
 * Displays the manage page for the plugin.
 */
function admin_manage_page() {
	require_once __DIR__ . '/src/event-list-table.php';

	$table = new Event_List_Table();

	$table->prepare_items();

	$messages = array(
		/* translators: 1: The name of the cron event. */
		'1' => __( 'Successfully executed the cron event %s.', 'wp-crontrol' ),
		/* translators: 1: The name of the cron event. */
		'2' => __( 'Successfully deleted all %s cron events.', 'wp-crontrol' ),
		/* translators: 1: The name of the cron event. */
		'3' => __( 'There are no %s cron events to delete.', 'wp-crontrol' ),
		/* translators: 1: The name of the cron event. */
		'4' => __( 'Successfully edited the cron event %s.', 'wp-crontrol' ),
		/* translators: 1: The name of the cron event. */
		'5' => __( 'Successfully created the cron event %s.', 'wp-crontrol' ),
		/* translators: 1: The name of the cron event. */
		'6' => __( 'Successfully deleted the cron event %s.', 'wp-crontrol' ),
		/* translators: 1: The name of the cron event. */
		'7' => __( 'Failed to the delete the cron event %s.', 'wp-crontrol' ),
		/* translators: 1: The name of the cron event. */
		'8' => __( 'Failed to the execute the cron event %s.', 'wp-crontrol' ),
		'9' => __( 'Successfully deleted the selected cron events.', 'wp-crontrol' ),
	);

	if ( isset( $_GET['crontrol_name'] ) && isset( $_GET['crontrol_message'] ) && isset( $messages[ $_GET['crontrol_message'] ] ) ) {
		$hook    = wp_unslash( $_GET['crontrol_name'] );
		$message = wp_unslash( $_GET['crontrol_message'] );
		$msg     = sprintf( esc_html( $messages[ $message ] ), '<strong>' . esc_html( $hook ) . '</strong>' );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		printf( '<div id="message" class="updated notice is-dismissible"><p>%s</p></div>', $msg );
	}

	$events         = $table->items;
	$doing_edit     = ( isset( $_GET['action'] ) && 'edit-cron' === $_GET['action'] ) ? wp_unslash( $_GET['id'] ) : false;
	$time_format    = 'Y-m-d H:i:s';
	$can_edit_files = current_user_can( 'edit_files' );

	$core_hooks = array(
		'wp_version_check',
		'wp_update_plugins',
		'wp_update_themes',
		'wp_scheduled_delete',
		'wp_scheduled_auto_draft_delete',
		'update_network_counts',
		'delete_expired_transients',
		'wp_privacy_delete_old_export_files',
		'recovery_mode_clean_expired_keys',
	);

	show_cron_status();

	?>
	<div class="wrap">
	<h1><?php esc_html_e( 'WP-Cron Events', 'wp-crontrol' ); ?></h1>

	<?php $table->views(); ?>

	<form method="post" action="tools.php?page=crontrol_admin_manage_page">

	<div class="table-responsive">
	<?php $table->display(); ?>

	<?php

	if ( ! empty( $events ) ) {
		foreach ( $events as $id => $event ) {
			if ( $doing_edit && $doing_edit === $event->hook && intval( $_GET['next_run'] ) === $event->time && $event->sig === $_GET['sig'] ) {
				$doing_edit = array(
					'hookname' => $event->hook,
					'next_run' => $event->time,
					'schedule' => ( $event->schedule ? $event->schedule : '_oneoff' ),
					'sig'      => $event->sig,
					'args'     => $event->args,
				);
			}
		}
	}

	?>
	</tbody>
	</table>
	</div>
	<p style="float:right">
		<?php
			echo esc_html( sprintf(
				/* translators: %s: The current date and time */
				__( 'Site time: %s', 'wp-crontrol' ),
				date_i18n( 'Y-m-d H:i:s' )
			) );
			echo '<br>';
			echo esc_html( sprintf(
				/* translators: %s: The timezone */
				__( 'Site timezone: %s', 'wp-crontrol' ),
				get_timezone_name()
			) );
		?>
	</p>
	<?php
	wp_nonce_field( 'bulk-delete-crons' );
	submit_button(
		__( 'Delete Selected Events', 'wp-crontrol' ),
		'primary large',
		'delete_crons'
	);
	?>
	</form>

	</div>
	<?php

	if ( is_array( $doing_edit ) ) {
		show_cron_form( 'crontrol_cron_job' === $doing_edit['hookname'], $doing_edit );
	} else {
		show_cron_form( ( isset( $_GET['action'] ) && 'new-php-cron' === $_GET['action'] ), false );
	}
}

/**
 * Returns an array of the callback functions that are attached to the given hook name.
 *
 * @param string $name The hook name.
 * @return array[] Array of callbacks attached to the hook.
 */
function get_action_callbacks( $name ) {
	global $wp_filter;

	$actions = array();

	if ( isset( $wp_filter[ $name ] ) ) {
		// See http://core.trac.wordpress.org/ticket/17817.
		$action = $wp_filter[ $name ];

		foreach ( $action as $priority => $callbacks ) {
			foreach ( $callbacks as $callback ) {
				$callback = populate_callback( $callback );

				if ( isset( $callback['function'] ) && is_array( $callback['function'] ) ) {
					if ( $callback['function'][0] instanceof Log ) {
						continue;
					}
				}

				$actions[] = array(
					'priority' => $priority,
					'callback' => $callback,
				);
			}
		}
	}

	return $actions;
}

/**
 * Populates the details of the given callback function.
 *
 * @param array $callback A callback entry.
 * @return array The updated callback entry.
 */
function populate_callback( array $callback ) {
	// If Query Monitor is installed, use its rich callback analysis.
	if ( method_exists( '\QM_Util', 'populate_callback' ) ) {
		return \QM_Util::populate_callback( $callback );
	}

	if ( is_string( $callback['function'] ) && ( false !== strpos( $callback['function'], '::' ) ) ) {
		$callback['function'] = explode( '::', $callback['function'] );
	}

	if ( is_array( $callback['function'] ) ) {
		if ( is_object( $callback['function'][0] ) ) {
			$class  = get_class( $callback['function'][0] );
			$access = '->';
		} else {
			$class  = $callback['function'][0];
			$access = '::';
		}

		$callback['name'] = $class . $access . $callback['function'][1] . '()';
	} elseif ( is_object( $callback['function'] ) ) {
		if ( is_a( $callback['function'], 'Closure' ) ) {
			$callback['name'] = 'Closure';
		} else {
			$class = get_class( $callback['function'] );

			$callback['name'] = $class . '->__invoke()';
		}
	} else {
		$callback['name'] = $callback['function'] . '()';
	}

	return $callback;
}

/**
 * Returns a user-friendly representation of the callback function.
 *
 * @param array $callback The callback entry.
 * @return string The displayable version of the callback name.
 */
function output_callback( array $callback ) {
	$qm   = WP_PLUGIN_DIR . '/query-monitor/query-monitor.php';
	$html = plugin_dir_path( $qm ) . 'output/Html.php';

	// If Query Monitor is installed, use its rich callback output.
	if ( class_exists( '\QueryMonitor' ) && file_exists( $html ) ) {
		require_once $html;

		if ( class_exists( '\QM_Output_Html' ) ) {
			return \QM_Output_Html::output_filename(
				$callback['callback']['name'],
				$callback['callback']['file'],
				$callback['callback']['line']
			);
		}
	}

	return $callback['callback']['name'];
}

/**
 * Pretty-prints the difference in two times.
 *
 * @param int $older_date Unix timestamp.
 * @param int $newer_date Unix timestamp.
 * @return string The pretty time_since value
 * @link http://binarybonsai.com/code/timesince.txt
 */
function time_since( $older_date, $newer_date ) {
	return interval( $newer_date - $older_date );
}

/**
 * Converts a period of time in seconds into a human-readable format representing the interval.
 *
 * Example:
 *
 *     echo \Crontrol\interval( 90 );
 *     // 1 minute 30 seconds
 *
 * @param  int $since A period of time in seconds.
 * @return string An interval represented as a string.
 */
function interval( $since ) {
	// Array of time period chunks.
	$chunks = array(
		/* translators: 1: The number of years in an interval of time. */
		array( 60 * 60 * 24 * 365, _n_noop( '%s year', '%s years', 'wp-crontrol' ) ),
		/* translators: 1: The number of months in an interval of time. */
		array( 60 * 60 * 24 * 30, _n_noop( '%s month', '%s months', 'wp-crontrol' ) ),
		/* translators: 1: The number of weeks in an interval of time. */
		array( 60 * 60 * 24 * 7, _n_noop( '%s week', '%s weeks', 'wp-crontrol' ) ),
		/* translators: 1: The number of days in an interval of time. */
		array( 60 * 60 * 24, _n_noop( '%s day', '%s days', 'wp-crontrol' ) ),
		/* translators: 1: The number of hours in an interval of time. */
		array( 60 * 60, _n_noop( '%s hour', '%s hours', 'wp-crontrol' ) ),
		/* translators: 1: The number of minutes in an interval of time. */
		array( 60, _n_noop( '%s minute', '%s minutes', 'wp-crontrol' ) ),
		/* translators: 1: The number of seconds in an interval of time. */
		array( 1, _n_noop( '%s second', '%s seconds', 'wp-crontrol' ) ),
	);

	if ( $since <= 0 ) {
		return __( 'now', 'wp-crontrol' );
	}

	/**
	 * We only want to output two chunks of time here, eg:
	 * x years, xx months
	 * x days, xx hours
	 * so there's only two bits of calculation below:
	 */
	$j = count( $chunks );

	// Step one: the first chunk.
	for ( $i = 0; $i < $j; $i++ ) {
		$seconds = $chunks[ $i ][0];
		$name    = $chunks[ $i ][1];

		// Finding the biggest chunk (if the chunk fits, break).
		$count = floor( $since / $seconds );
		if ( $count ) {
			break;
		}
	}

	// Set output var.
	$output = sprintf( translate_nooped_plural( $name, $count, 'wp-crontrol' ), $count );

	// Step two: the second chunk.
	if ( $i + 1 < $j ) {
		$seconds2 = $chunks[ $i + 1 ][0];
		$name2    = $chunks[ $i + 1 ][1];
		$count2   = floor( ( $since - ( $seconds * $count ) ) / $seconds2 );
		if ( $count2 ) {
			// Add to output var.
			$output .= ' ' . sprintf( translate_nooped_plural( $name2, $count2, 'wp-crontrol' ), $count2 );
		}
	}

	return $output;
}

/**
 * Enqueues the editor UI that's used for the PHP cron event code editor.
 */
function enqueue_code_editor() {
	if ( ! function_exists( 'wp_enqueue_code_editor' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_files' ) ) {
		return;
	}

	$settings = wp_enqueue_code_editor( array(
		'type' => 'text/x-php',
	) );

	if ( false === $settings ) {
		return;
	}

	wp_add_inline_script( 'code-editor', sprintf(
		'jQuery( function( $ ) {
			if ( $( "#hookcode" ).length ) {
				wp.codeEditor.initialize( "hookcode", %s );
			}
		} );',
		wp_json_encode( $settings )
	) );
}

/**
 * Registers the stylesheet for the admin area.
 *
 * @param string $id The admin screen ID.
 */
function enqueue_styles( $id ) {
	if ( 'tools_page_crontrol_admin_manage_page' !== $id ) {
		return;
	}

	$ver = filemtime( plugin_dir_path( __FILE__ ) . 'css/wp-crontrol.css' );

	wp_enqueue_style( 'wp-crontrol', plugin_dir_url( __FILE__ ) . 'css/wp-crontrol.css', array(), $ver );
}

/**
 * Filters the list of query arguments which get removed from admin area URLs in WordPress.
 *
 * @link https://core.trac.wordpress.org/ticket/23367
 *
 * @param string[] $args List of removable query arguments.
 * @return string[] Updated list of removable query arguments.
 */
function filter_removable_query_args( array $args ) {
	return array_merge( $args, array(
		'crontrol_message',
		'crontrol_name',
	) );
}

/**
 * Returns an array of cron event hooks that are added by WordPress core.
 *
 * @return string[] Array of hook names.
 */
function get_core_hooks() {
	return array(
		'wp_version_check',
		'wp_update_plugins',
		'wp_update_themes',
		'wp_scheduled_delete',
		'wp_scheduled_auto_draft_delete',
		'update_network_counts',
		'delete_expired_transients',
		'wp_privacy_delete_old_export_files',
		'recovery_mode_clean_expired_keys',
	);
}

// Get this show on the road.
init_hooks();
