<?php
/**
 * Plugin Name:  WP Crontrol
 * Plugin URI:   https://wordpress.org/plugins/wp-crontrol/
 * Description:  WP Crontrol enables you to view and control what's happening in the WP-Cron system.
 * Author:       John Blackbourn & crontributors
 * Author URI:   https://github.com/johnbillion/wp-crontrol/graphs/contributors
 * Version:      1.9.0
 * Text Domain:  wp-crontrol
 * Domain Path:  /languages/
 * Requires PHP: 5.3.6
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
 * @copyright  Copyright 2008 Edward Dale, 2012-2020 John Blackbourn
 * @license    http://www.gnu.org/licenses/gpl.txt GPL 2.0
 * @link       https://wordpress.org/plugins/wp-crontrol/
 * @since      0.2
 */

namespace Crontrol;

use WP_Error;

defined( 'ABSPATH' ) || die();

require_once __DIR__ . '/src/event.php';
require_once __DIR__ . '/src/schedule.php';

/**
 * Hook onto all of the actions and filters needed by the plugin.
 */
function init_hooks() {
	$plugin_file = plugin_basename( __FILE__ );

	add_action( 'init',                               __NAMESPACE__ . '\action_init' );
	add_action( 'init',                               __NAMESPACE__ . '\action_handle_posts' );
	add_action( 'admin_menu',                         __NAMESPACE__ . '\action_admin_menu' );
	add_action( 'wp_ajax_crontrol_checkhash',         __NAMESPACE__ . '\ajax_check_events_hash' );
	add_filter( "plugin_action_links_{$plugin_file}", __NAMESPACE__ . '\plugin_action_links', 10, 4 );
	add_filter( 'removable_query_args',               __NAMESPACE__ . '\filter_removable_query_args' );
	add_filter( 'in_admin_header',                    __NAMESPACE__ . '\do_tabs' );
	add_filter( 'pre_unschedule_event',               __NAMESPACE__ . '\maybe_clear_doing_cron' );
	add_filter( 'plugin_row_meta',                    __NAMESPACE__ . '\filter_plugin_row_meta', 10, 4 );

	add_action( 'load-tools_page_crontrol_admin_manage_page', __NAMESPACE__ . '\setup_manage_page' );

	add_filter( 'cron_schedules',        __NAMESPACE__ . '\filter_cron_schedules' );
	add_action( 'crontrol_cron_job',     __NAMESPACE__ . '\action_php_cron_event' );
	add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\enqueue_assets' );
	add_action( 'crontrol/tab-header',   __NAMESPACE__ . '\show_cron_status', 20 );
}

/**
 * Filters the array of row meta for each plugin in the Plugins list table.
 *
 * @param string[] $plugin_meta An array of the plugin's metadata.
 * @param string   $plugin_file Path to the plugin file relative to the plugins directory.
 * @return string[] An array of the plugin's metadata.
 */
function filter_plugin_row_meta( array $plugin_meta, $plugin_file ) {
	if ( 'wp-crontrol/wp-crontrol.php' !== $plugin_file ) {
		return $plugin_meta;
	}

	$plugin_meta[] = sprintf(
		'<a href="%1$s"><span class="dashicons dashicons-star-filled" aria-hidden="true" style="font-size:14px;line-height:1.3"></span>%2$s</a>',
		'https://github.com/sponsors/johnbillion',
		esc_html_x( 'Sponsor', 'verb', 'wp-crontrol' )
	);

	return $plugin_meta;
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
	if ( isset( $_POST['action'] ) && ( 'new_cron' === $_POST['action'] ) ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to add new cron events.', 'wp-crontrol' ), 401 );
		}
		check_admin_referer( 'new-cron' );
		extract( wp_unslash( $_POST ), EXTR_PREFIX_ALL, 'in' );
		if ( 'crontrol_cron_job' === $in_hookname && ! current_user_can( 'edit_files' ) ) {
			wp_die( esc_html__( 'You are not allowed to add new PHP cron events.', 'wp-crontrol' ), 401 );
		}
		$in_args = json_decode( $in_args, true );

		if ( empty( $in_args ) ) {
			$in_args = array();
		}

		$next_run_local = ( 'custom' === $in_next_run_date_local ) ? $in_next_run_date_local_custom_date . ' ' . $in_next_run_date_local_custom_time : $in_next_run_date_local;

		add_filter( 'schedule_event', function( $event ) {
			if ( ! $event ) {
				return $event;
			}

			/**
			 * Fires after a new cron event is added.
			 *
			 * @param object $event {
			 *     An object containing the event's data.
			 *
			 *     @type string       $hook      Action hook to execute when the event is run.
			 *     @type int          $timestamp Unix timestamp (UTC) for when to next run the event.
			 *     @type string|false $schedule  How often the event should subsequently recur.
			 *     @type array        $args      Array containing each separate argument to pass to the hook's callback function.
			 *     @type int          $interval  The interval time in seconds for the schedule. Only present for recurring events.
			 * }
			 */
			do_action( 'crontrol/added_new_event', $event );

			return $event;
		}, 99 );

		$added = Event\add( $next_run_local, $in_schedule, $in_hookname, $in_args );

		$redirect = array(
			'page'             => 'crontrol_admin_manage_page',
			'crontrol_message' => '5',
			'crontrol_name'    => rawurlencode( $in_hookname ),
		);

		if ( false === $added ) {
			$redirect['crontrol_message'] = '10';
		}

		wp_safe_redirect( add_query_arg( $redirect, admin_url( 'tools.php' ) ) );
		exit;

	} elseif ( isset( $_POST['action'] ) && ( 'new_php_cron' === $_POST['action'] ) ) {
		if ( ! current_user_can( 'edit_files' ) ) {
			wp_die( esc_html__( 'You are not allowed to add new PHP cron events.', 'wp-crontrol' ), 401 );
		}
		check_admin_referer( 'new-cron' );
		extract( wp_unslash( $_POST ), EXTR_PREFIX_ALL, 'in' );
		$next_run_local = ( 'custom' === $in_next_run_date_local ) ? $in_next_run_date_local_custom_date . ' ' . $in_next_run_date_local_custom_time : $in_next_run_date_local;
		$args           = array(
			'code' => $in_hookcode,
			'name' => $in_eventname,
		);

		add_filter( 'schedule_event', function( $event ) {
			if ( ! $event ) {
				return $event;
			}

			/**
			 * Fires after a new PHP cron event is added.
			 *
			 * @param object $event {
			 *     An object containing the event's data.
			 *
			 *     @type string       $hook      Action hook to execute when the event is run.
			 *     @type int          $timestamp Unix timestamp (UTC) for when to next run the event.
			 *     @type string|false $schedule  How often the event should subsequently recur.
			 *     @type array        $args      Array containing each separate argument to pass to the hook's callback function.
			 *     @type int          $interval  The interval time in seconds for the schedule. Only present for recurring events.
			 * }
			 */
			do_action( 'crontrol/added_new_php_event', $event );

			return $event;
		}, 99 );

		$added = Event\add( $next_run_local, $in_schedule, 'crontrol_cron_job', $args );

		$hookname = ( ! empty( $in_eventname ) ) ? $in_eventname : __( 'PHP Cron', 'wp-crontrol' );
		$redirect = array(
			'page'             => 'crontrol_admin_manage_page',
			'crontrol_message' => '5',
			'crontrol_name'    => rawurlencode( $hookname ),
		);

		if ( false === $added ) {
			$redirect['crontrol_message'] = '10';
		}

		wp_safe_redirect( add_query_arg( $redirect, admin_url( 'tools.php' ) ) );
		exit;

	} elseif ( isset( $_POST['action'] ) && ( 'edit_cron' === $_POST['action'] ) ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to edit cron events.', 'wp-crontrol' ), 401 );
		}

		extract( wp_unslash( $_POST ), EXTR_PREFIX_ALL, 'in' );
		check_admin_referer( "edit-cron_{$in_original_hookname}_{$in_original_sig}_{$in_original_next_run_utc}" );

		if ( 'crontrol_cron_job' === $in_hookname && ! current_user_can( 'edit_files' ) ) {
			wp_die( esc_html__( 'You are not allowed to edit PHP cron events.', 'wp-crontrol' ), 401 );
		}

		$in_args = json_decode( $in_args, true );

		if ( empty( $in_args ) ) {
			$in_args = array();
		}

		$original = Event\get_single( $in_original_hookname, $in_original_sig, $in_original_next_run_utc );
		Event\delete( $in_original_hookname, $in_original_sig, $in_original_next_run_utc );

		$next_run_local = ( 'custom' === $in_next_run_date_local ) ? $in_next_run_date_local_custom_date . ' ' . $in_next_run_date_local_custom_time : $in_next_run_date_local;

		add_filter( 'schedule_event', function( $event ) use ( $original ) {
			if ( ! $event || ! $original ) {
				return $event;
			}

			/**
			 * Fires after a cron event is edited.
			 *
			 * @param object $event {
			 *     An object containing the new event's data.
			 *
			 *     @type string       $hook      Action hook to execute when the event is run.
			 *     @type int          $timestamp Unix timestamp (UTC) for when to next run the event.
			 *     @type string|false $schedule  How often the event should subsequently recur.
			 *     @type array        $args      Array containing each separate argument to pass to the hook's callback function.
			 *     @type int          $interval  The interval time in seconds for the schedule. Only present for recurring events.
			 * }
			 * @param object $original {
			 *     An object containing the original event's data.
			 *
			 *     @type string       $hook      Action hook to execute when the event is run.
			 *     @type int          $timestamp Unix timestamp (UTC) for when to next run the event.
			 *     @type string|false $schedule  How often the event should subsequently recur.
			 *     @type array        $args      Array containing each separate argument to pass to the hook's callback function.
			 *     @type int          $interval  The interval time in seconds for the schedule. Only present for recurring events.
			 * }
			 */
			do_action( 'crontrol/edited_event', $event, $original );

			return $event;
		}, 99 );

		$added = Event\add( $next_run_local, $in_schedule, $in_hookname, $in_args );

		$redirect = array(
			'page'             => 'crontrol_admin_manage_page',
			'crontrol_message' => '4',
			'crontrol_name'    => rawurlencode( $in_hookname ),
		);

		if ( false === $added ) {
			$redirect['crontrol_message'] = '10';
		}

		wp_safe_redirect( add_query_arg( $redirect, admin_url( 'tools.php' ) ) );
		exit;

	} elseif ( isset( $_POST['action'] ) && ( 'edit_php_cron' === $_POST['action'] ) ) {
		if ( ! current_user_can( 'edit_files' ) ) {
			wp_die( esc_html__( 'You are not allowed to edit PHP cron events.', 'wp-crontrol' ), 401 );
		}

		extract( wp_unslash( $_POST ), EXTR_PREFIX_ALL, 'in' );
		check_admin_referer( "edit-cron_{$in_original_hookname}_{$in_original_sig}_{$in_original_next_run_utc}" );
		$args = array(
			'code' => $in_hookcode,
			'name' => $in_eventname,
		);

		$original = Event\get_single( $in_original_hookname, $in_original_sig, $in_original_next_run_utc );
		Event\delete( $in_original_hookname, $in_original_sig, $in_original_next_run_utc );

		$next_run_local = ( 'custom' === $in_next_run_date_local ) ? $in_next_run_date_local_custom_date . ' ' . $in_next_run_date_local_custom_time : $in_next_run_date_local;

		add_filter( 'schedule_event', function( $event ) use ( $original ) {
			if ( ! $event || ! $original ) {
				return $event;
			}

			/**
			 * Fires after a PHP cron event is edited.
			 *
			 * @param object $event {
			 *     An object containing the new event's data.
			 *
			 *     @type string       $hook      Action hook to execute when the event is run.
			 *     @type int          $timestamp Unix timestamp (UTC) for when to next run the event.
			 *     @type string|false $schedule  How often the event should subsequently recur.
			 *     @type array        $args      Array containing each separate argument to pass to the hook's callback function.
			 *     @type int          $interval  The interval time in seconds for the schedule. Only present for recurring events.
			 * }
			 * @param object $original {
			 *     An object containing the original event's data.
			 *
			 *     @type string       $hook      Action hook to execute when the event is run.
			 *     @type int          $timestamp Unix timestamp (UTC) for when to next run the event.
			 *     @type string|false $schedule  How often the event should subsequently recur.
			 *     @type array        $args      Array containing each separate argument to pass to the hook's callback function.
			 *     @type int          $interval  The interval time in seconds for the schedule. Only present for recurring events.
			 * }
			 */
			do_action( 'crontrol/edited_php_event', $event, $original );

			return $event;
		}, 99 );

		$added = Event\add( $next_run_local, $in_schedule, 'crontrol_cron_job', $args );

		$hookname = ( ! empty( $in_eventname ) ) ? $in_eventname : __( 'PHP Cron', 'wp-crontrol' );
		$redirect = array(
			'page'             => 'crontrol_admin_manage_page',
			'crontrol_message' => '4',
			'crontrol_name'    => rawurlencode( $hookname ),
		);

		if ( false === $added ) {
			$redirect['crontrol_message'] = '10';
		}

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
		$schedule = wp_unslash( $_GET['id'] );
		check_admin_referer( "delete-sched_{$schedule}" );
		Schedule\delete( $schedule );
		$redirect = array(
			'page'             => 'crontrol_admin_options_page',
			'crontrol_message' => '2',
			'crontrol_name'    => rawurlencode( $schedule ),
		);
		wp_safe_redirect( add_query_arg( $redirect, admin_url( 'options-general.php' ) ) );
		exit;

	} elseif ( ( isset( $_POST['action'] ) && 'delete_crons' === $_POST['action'] ) || ( isset( $_POST['action2'] ) && 'delete_crons' === $_POST['action2'] ) ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to delete cron events.', 'wp-crontrol' ), 401 );
		}
		check_admin_referer( 'bulk-crontrol-events' );

		if ( empty( $_POST['delete'] ) ) {
			return;
		}

		$delete  = wp_unslash( $_POST['delete'] );
		$deleted = 0;

		foreach ( $delete as $next_run_utc => $events ) {
			foreach ( $events as $hook => $sig ) {
				if ( 'crontrol_cron_job' === $hook && ! current_user_can( 'edit_files' ) ) {
					continue;
				}

				$event = Event\get_single( urldecode( $hook ), $sig, $next_run_utc );

				if ( Event\delete( urldecode( $hook ), $sig, $next_run_utc ) ) {
					$deleted++;

					/** This action is documented in wp-crontrol.php */
					do_action( 'crontrol/deleted_event', $event );
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
		$hook         = wp_unslash( $_GET['id'] );
		$sig          = wp_unslash( $_GET['sig'] );
		$next_run_utc = intval( $_GET['next_run_utc'] );
		check_admin_referer( "delete-cron_{$hook}_{$sig}_{$next_run_utc}" );

		if ( 'crontrol_cron_job' === $hook && ! current_user_can( 'edit_files' ) ) {
			wp_die( esc_html__( 'You are not allowed to delete PHP cron events.', 'wp-crontrol' ), 401 );
		}

		$event = Event\get_single( $hook, $sig, $next_run_utc );
		$deleted = Event\delete( $hook, $sig, $next_run_utc );
		$redirect = array(
			'page'             => 'crontrol_admin_manage_page',
			'crontrol_message' => '6',
			'crontrol_name'    => rawurlencode( $hook ),
		);

		if ( false === $deleted ) {
			$redirect['crontrol_message'] = '7';
		} else {
			/**
			 * Fires after a cron event is deleted.
			 *
			 * @param object $event {
			 *     An object containing the event's data.
			 *
			 *     @type string       $hook      Action hook to execute when the event is run.
			 *     @type int          $timestamp Unix timestamp (UTC) for when to next run the event.
			 *     @type string|false $schedule  How often the event should subsequently recur.
			 *     @type array        $args      Array containing each separate argument to pass to the hook's callback function.
			 *     @type int          $interval  The interval time in seconds for the schedule. Only present for recurring events.
			 * }
			 */
			do_action( 'crontrol/deleted_event', $event );
		}

		wp_safe_redirect( add_query_arg( $redirect, admin_url( 'tools.php' ) ) );
		exit;

	} elseif ( isset( $_GET['action'] ) && 'delete-hook' === $_GET['action'] ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to delete cron events.', 'wp-crontrol' ), 401 );
		}
		$hook    = wp_unslash( $_GET['id'] );
		$deleted = false;
		check_admin_referer( "delete-hook_{$hook}" );

		if ( 'crontrol_cron_job' === $hook ) {
			wp_die( esc_html__( 'You are not allowed to delete PHP cron events.', 'wp-crontrol' ), 401 );
		}

		if ( function_exists( 'wp_unschedule_hook' ) ) {
			$deleted = wp_unschedule_hook( $hook );
		}

		if ( 0 === $deleted ) {
			$redirect = array(
				'page'             => 'crontrol_admin_manage_page',
				'crontrol_message' => '3',
				'crontrol_name'    => rawurlencode( $hook ),
			);
			wp_safe_redirect( add_query_arg( $redirect, admin_url( 'tools.php' ) ) );
			exit;
		} elseif ( $deleted ) {
			/**
			 * Fires after all cron events with the given hook are deleted.
			 *
			 * @param string $hook    The hook name.
			 * @param int    $deleted The number of events that were deleted.
			 */
			do_action( 'crontrol/deleted_all_with_hook', $hook, $deleted );

			$redirect = array(
				'page'             => 'crontrol_admin_manage_page',
				'crontrol_message' => '2',
				'crontrol_name'    => rawurlencode( $hook ),
			);
			wp_safe_redirect( add_query_arg( $redirect, admin_url( 'tools.php' ) ) );
			exit;
		} else {
			$redirect = array(
				'page'             => 'crontrol_admin_manage_page',
				'crontrol_message' => '7',
				'crontrol_name'    => rawurlencode( $hook ),
			);
			wp_safe_redirect( add_query_arg( $redirect, admin_url( 'tools.php' ) ) );
			exit;
		}
	} elseif ( isset( $_GET['action'] ) && 'run-cron' === $_GET['action'] ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to run cron events.', 'wp-crontrol' ), 401 );
		}
		$hook = wp_unslash( $_GET['id'] );
		$sig = wp_unslash( $_GET['sig'] );
		check_admin_referer( "run-cron_{$hook}_{$sig}" );

		$ran = Event\run( $hook, $sig );

		$redirect = array(
			'page'             => 'crontrol_admin_manage_page',
			'crontrol_message' => '1',
			'crontrol_name'    => rawurlencode( $hook ),
		);

		if ( false === $ran ) {
			$redirect['crontrol_message'] = '8';
		}

		wp_safe_redirect( add_query_arg( $redirect, admin_url( 'tools.php' ) ) );
		exit;
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
	$new = array(
		'crontrol-events'    => sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'tools.php?page=crontrol_admin_manage_page' ) ),
			esc_html__( 'Events', 'wp-crontrol' )
		),
		'crontrol-schedules' => sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'options-general.php?page=crontrol_admin_options_page' ) ),
			esc_html__( 'Schedules', 'wp-crontrol' )
		),
	);

	return array_merge( $new, $actions );
}

/**
 * Gives WordPress the plugin's set of cron schedules.
 *
 * Called by the `cron_schedules` filter.
 *
 * @param array[] $scheds Array of cron schedule arrays. Usually empty.
 * @return array[] Array of modified cron schedule arrays.
 */
function filter_cron_schedules( array $scheds ) {
	$new_scheds = get_option( 'crontrol_schedules', array() );
	return array_merge( $new_scheds, $scheds );
}

/**
 * Displays the options page for the plugin.
 */
function admin_options_page() {
	$messages = array(
		'2' => array(
			/* translators: 1: The name of the cron schedule. */
			__( 'Deleted the cron schedule %s.', 'wp-crontrol' ),
			'success',
		),
		'3' => array(
			/* translators: 1: The name of the cron schedule. */
			__( 'Added the cron schedule %s.', 'wp-crontrol' ),
			'success',
		),
		'7' => array(
			/* translators: 1: The name of the cron schedule. */
			__( 'Cron schedule not added because there was a problem parsing %s.', 'wp-crontrol' ),
			'error',
		),
	);
	if ( isset( $_GET['crontrol_message'] ) && isset( $_GET['crontrol_name'] ) && isset( $messages[ $_GET['crontrol_message'] ] ) ) {
		$hook    = wp_unslash( $_GET['crontrol_name'] );
		$message = wp_unslash( $_GET['crontrol_message'] );

		printf(
			'<div id="crontrol-message" class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $messages[ $message ][1] ),
			sprintf(
				esc_html( $messages[ $message ][0] ),
				'<strong>' . esc_html( $hook ) . '</strong>'
			)
		);
	}

	require_once __DIR__ . '/src/schedule-list-table.php';

	$table = new Schedule_List_Table();

	$table->prepare_items();

	?>
	<div class="wrap">

	<h1><?php esc_html_e( 'Cron Schedules', 'wp-crontrol' ); ?></h1>

	<?php $table->views(); ?>

	<div id="col-container" class="wp-clearfix">
		<div id="col-left">
			<div class="col-wrap">
				<div class="form-wrap">
					<h2><?php esc_html_e( 'Add Cron Schedule', 'wp-crontrol' ); ?></h2>
					<p><?php esc_html_e( 'Adding a new cron schedule will allow you to schedule events that re-occur at the given interval.', 'wp-crontrol' ); ?></p>
					<form method="post" action="options-general.php?page=crontrol_admin_options_page">
						<div class="form-field form-required">
							<label for="cron_internal_name">
								<?php esc_html_e( 'Internal Name', 'wp-crontrol' ); ?>
							</label>
							<input type="text" value="" id="cron_internal_name" name="internal_name" required/>
						</div>
						<div class="form-field form-required">
							<label for="cron_interval">
								<?php esc_html_e( 'Interval (seconds)', 'wp-crontrol' ); ?>
							</label>
							<input type="number" value="" id="cron_interval" name="interval" min="1" step="1" required/>
						</div>
						<div class="form-field form-required">
							<label for="cron_display_name">
								<?php esc_html_e( 'Display Name', 'wp-crontrol' ); ?>
							</label>
							<input type="text" value="" id="cron_display_name" name="display_name" required/>
						</div>
						<p class="submit">
							<input id="schedadd-submit" type="submit" class="button button-primary" value="<?php esc_attr_e( 'Add Cron Schedule', 'wp-crontrol' ); ?>" name="new_schedule"/>
						</p>
						<?php wp_nonce_field( 'new-sched' ); ?>
					</form>
				</div>
			</div>
		</div>
		<div id="col-right">
			<div class="col-wrap">
				<?php $table->display(); ?>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Clears the doing cron status when an event is unscheduled.
 *
 * What on earth does this function do, and why?
 *
 * Good question. The purpose of this function is to prevent other overdue cron events from firing when an event is run
 * manually with the "Run Now" action. WP Crontrol works very hard to ensure that when cron event runs manually that it
 * runs in the exact same way it would run as part of its schedule - via a properly spawned cron with a queued event in
 * place. It does this by queueing an event at time `1` (1 second into 1st January 1970) and then immediately spawning
 * cron (see the `Event\run()` function).
 *
 * The problem this causes is if other events are due then they will all run too, and this isn't desirable because if a
 * site has a large number of stuck events due to a problem with the cron runner then it's not desirable for all those
 * events to run when another is manually run. This happens because WordPress core will attempt to run all due events
 * whenever cron is spawned.
 *
 * The code in this function prevents multiple events from running by changing the value of the `doing_cron` transient
 * when an event gets unscheduled during a manual run, which prevents wp-cron.php from iterating more than one event.
 *
 * The `pre_unschedule_event` filter is used for this because it's just about the only hook available within this loop.
 *
 * Refs:
 * - https://core.trac.wordpress.org/browser/trunk/src/wp-cron.php?rev=47198&marks=127,141#L122
 *
 * @param mixed $pre The pre-flight value of the event unschedule short-circuit. Not used.
 * @return mixed Thee unaltered pre-flight value.
 */
function maybe_clear_doing_cron( $pre ) {
	if ( defined( 'DOING_CRON' ) && DOING_CRON && isset( $_GET['crontrol-single-event'] ) ) {
		delete_transient( 'doing_cron' );
	}

	return $pre;
}

/**
 * Ajax handler which outputs a hash of the current list of scheduled events.
 */
function ajax_check_events_hash() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( null, 403 );
	}

	wp_send_json_success( md5( json_encode( Event\get_list_table()->items ) ) );
}

/**
 * Gets the status of WP-Cron functionality on the site by performing a test spawn if necessary. Cached for one hour when all is well.
 *
 * @param bool $cache Whether to use the cached result from previous calls.
 * @return true|WP_Error Boolean true if the cron spawner is working as expected, or a `WP_Error` object if not.
 */
function test_cron_spawn( $cache = true ) {
	global $wp_version;

	$cron_runner_plugins = array(
		'\HM\Cavalcade\Plugin\Job'    => 'Cavalcade',
		'\Automattic\WP\Cron_Control' => 'Cron Control',
	);

	foreach ( $cron_runner_plugins as $class => $plugin ) {
		if ( class_exists( $class ) ) {
			return new WP_Error( 'crontrol_info', sprintf(
				/* translators: 1: The name of the plugin that controls the running of cron events. */
				__( 'WP-Cron spawning is being managed by the %s plugin.', 'wp-crontrol' ),
				$plugin
			) );
		}
	}

	if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
		return new WP_Error( 'crontrol_info', sprintf(
			/* translators: 1: The name of the PHP constant that is set. */
			__( 'The %s constant is set to true. WP-Cron spawning is disabled.', 'wp-crontrol' ),
			'DISABLE_WP_CRON'
		) );
	}

	if ( defined( 'ALTERNATE_WP_CRON' ) && ALTERNATE_WP_CRON ) {
		return new WP_Error( 'crontrol_info', sprintf(
			/* translators: 1: The name of the PHP constant that is set. */
			__( 'The %s constant is set to true.', 'wp-crontrol' ),
			'ALTERNATE_WP_CRON'
		) );
	}

	$cached_status = get_transient( 'crontrol-cron-test-ok' );

	if ( $cache && $cached_status ) {
		return true;
	}

	$sslverify     = version_compare( $wp_version, 4.0, '<' );
	$doing_wp_cron = sprintf( '%.22F', microtime( true ) );

	$cron_request = apply_filters( 'cron_request', array(
		'url'  => add_query_arg( 'doing_wp_cron', $doing_wp_cron, site_url( 'wp-cron.php' ) ),
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
 * Determines whether the given feature is enabled.
 *
 * The feature directly corresponds to one of WP Crontrol's tabs. Currently the only feature
 * that's not enabled by default is "logs" which are provided by WP Crontrol Pro.
 *
 * @param string $feature The feature name.
 * @return bool Whether the specified tab is active.
 */
function is_feature_enabled( $feature ) {
	$enabled = ( 'logs' !== $feature );
	return apply_filters( "crontrol/enabled/{$feature}", $enabled );
}

/**
 * Shows the status of WP-Cron functionality on the site. Only displays a message when there's a problem.
 *
 * @param string $tab The tab name.
 */
function show_cron_status( $tab ) {
	if ( ! is_feature_enabled( $tab ) ) {
		return;
	}

	if ( 'UTC' !== date_default_timezone_get() ) {
		?>
		<div id="crontrol-timezone-warning" class="notice notice-warning">
			<?php
				printf(
					'<p>%1$s</p><p><a href="%2$s">%3$s</a></p>',
					/* translators: %s: Help page URL. */
					esc_html__( 'PHP default timezone is not set to UTC. This may cause issues with cron event timings.', 'wp-crontrol' ),
					'https://github.com/johnbillion/wp-crontrol/wiki/PHP-default-timezone-is-not-set-to-UTC',
					esc_html__( 'More information', 'wp-crontrol' )
				);
			?>
		</div>
		<?php
	}

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
				<?php
				printf(
					'<p>%1$s</p><p><a href="%2$s">%3$s</a></p>',
					sprintf(
						/* translators: 1: Error message text. */
						esc_html__( 'There was a problem spawning a call to the WP-Cron system on your site. This means WP-Cron events on your site may not work. The problem was: %s', 'wp-crontrol' ),
						'</p><p><strong>' . esc_html( $status->get_error_message() ) . '</strong>'
					),
					'https://github.com/johnbillion/wp-crontrol/wiki/Problems-with-spawning-a-call-to-the-WP-Cron-system',
					esc_html__( 'More information', 'wp-crontrol' )
				);
				?>
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

	if ( 'UTC' === $timezone_string || ( empty( $gmt_offset ) && empty( $timezone_string ) ) ) {
		return 'UTC';
	}

	if ( '' === $timezone_string ) {
		return get_utc_offset();
	}

	return sprintf(
		'%s, %s',
		str_replace( '_', ' ', $timezone_string ),
		get_utc_offset()
	);
}

/**
 * Returns a display value for a UTC offset.
 *
 * Examples:
 *   - UTC
 *   - UTC+4
 *   - UTC-6
 *
 * @return string The UTC offset display value.
 */
function get_utc_offset() {
	$offset = get_option( 'gmt_offset', 0 );

	if ( empty( $offset ) ) {
		return 'UTC';
	}

	if ( 0 <= $offset ) {
		$formatted_offset = '+' . (string) $offset;
	} else {
		$formatted_offset = (string) $offset;
	}
	$formatted_offset = str_replace(
		array( '.25', '.5', '.75' ),
		array( ':15', ':30', ':45' ),
		$formatted_offset
	);
	return 'UTC' . $formatted_offset;
}

/**
 * Shows the form used to add/edit cron events.
 *
 * @param bool $editing Whether the form is for the event editor.
 * @return void
 */
function show_cron_form( $editing ) {
	$display_args = '';
	$edit_id      = null;
	$existing     = false;

	if ( $editing && ! empty( $_GET['id'] ) ) {
		$edit_id = wp_unslash( $_GET['id'] );

		foreach ( Event\get() as $event ) {
			if ( $edit_id === $event->hook && intval( $_GET['next_run_utc'] ) === $event->time && $event->sig === $_GET['sig'] ) {
				$existing = array(
					'hookname' => $event->hook,
					'next_run' => $event->time, // UTC
					'schedule' => ( $event->schedule ? $event->schedule : '_oneoff' ),
					'sig'      => $event->sig,
					'args'     => $event->args,
				);
				break;
			}
		}

		if ( empty( $existing ) ) {
			?>
			<div id="crontrol-event-not-found" class="notice notice-error">
				<?php
				printf(
					'<p>%1$s</p>',
					esc_html__( 'The event you are trying to edit does not exist.', 'wp-crontrol' )
				);
				?>
			</div>
			<?php
			return;
		}
	}

	$is_editing_php = ( $existing && 'crontrol_cron_job' === $existing['hookname'] );

	if ( $is_editing_php ) {
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
		$other_fields .= sprintf( '<input name="original_next_run_utc" type="hidden" value="%s" />',
			esc_attr( $existing['next_run'] )
		);
		if ( ! empty( $existing['args'] ) ) {
			$display_args = wp_json_encode( $existing['args'] );
		}
		$action        = $is_editing_php ? 'edit_php_cron' : 'edit_cron';
		$button        = __( 'Update Event', 'wp-crontrol' );
		$next_run_gmt  = gmdate( 'Y-m-d H:i:s', $existing['next_run'] );
		$next_run_date_local = get_date_from_gmt( $next_run_gmt, 'Y-m-d' );
		$next_run_time_local = get_date_from_gmt( $next_run_gmt, 'H:i:s' );
	} else {
		$other_fields = wp_nonce_field( 'new-cron', '_wpnonce', true, false );
		$existing     = array(
			'hookname' => '',
			'args'     => array(),
			'next_run' => 'now', // UTC
			'schedule' => false,
		);

		$button        = __( 'Add Event', 'wp-crontrol' );
		$next_run_date_local = '';
		$next_run_time_local = '';
	}

	if ( $is_editing_php ) {
		if ( ! isset( $existing['args']['code'] ) ) {
			$existing['args']['code'] = '';
		}
		if ( ! isset( $existing['args']['name'] ) ) {
			$existing['args']['name'] = '';
		}
	}

	$can_add_php = current_user_can( 'edit_files' ) && ! $editing;
	$allowed = ( ! $is_editing_php || current_user_can( 'edit_files' ) );
	?>
	<div id="crontrol_form" class="wrap narrow">
		<?php
		if ( $allowed ) {
			if ( $editing ) {
				$heading = __( 'Edit Cron Event', 'wp-crontrol' );
			} else {
				$heading = __( 'Add Cron Event', 'wp-crontrol' );
			}

			printf(
				'<h1>%s</h1>',
				esc_html( $heading )
			);
			printf(
				'<p>%s</p>',
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$helper_text
			);
			?>
		<form method="post" action="<?php echo esc_url( admin_url( 'tools.php?page=crontrol_admin_manage_page' ) ); ?>" class="crontrol-edit-event crontrol-edit-event-<?php echo ( $is_editing_php ) ? 'php' : 'standard'; ?>">
			<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $other_fields;
			?>
			<table class="form-table"><tbody>
				<?php
				if ( $editing ) {
					printf(
						'<input type="hidden" name="action" value="%s"/>',
						esc_attr( $action )
					);
				} elseif ( $can_add_php ) {
					?>
					<tr class="hide-if-no-js">
						<th valign="top" scope="row">
							<?php esc_html_e( 'Event Type', 'wp-crontrol' ); ?>
						</th>
						<td>
							<p><label><input type="radio" name="action" value="new_cron" checked>Standard cron event</label></p>
							<p><label><input type="radio" name="action" value="new_php_cron">PHP cron event</label></p>
						</td>
					</tr>
					<?php
				}

				if ( $is_editing_php || $can_add_php ) {
					?>
					<tr class="crontrol-event-php">
						<th valign="top" scope="row">
							<label for="hookcode">
								<?php esc_html_e( 'PHP Code', 'wp-crontrol' ); ?>
							</label>
						</th>
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
							<p><textarea class="large-text code" rows="10" cols="50" id="hookcode" name="hookcode"><?php echo esc_textarea( $editing ? $existing['args']['code'] : '' ); ?></textarea></p>
						</td>
					</tr>
					<tr class="crontrol-event-php">
						<th valign="top" scope="row">
							<label for="eventname">
								<?php esc_html_e( 'Event Name (optional)', 'wp-crontrol' ); ?>
							</label>
						</th>
						<td>
							<input type="text" class="regular-text" id="eventname" name="eventname" value="<?php echo esc_attr( $editing ? $existing['args']['name'] : '' ); ?>"/>
						</td>
					</tr>
					<?php
				}

				if ( ! $is_editing_php ) {
					?>
					<tr class="crontrol-event-standard">
						<th valign="top" scope="row">
							<label for="hookname">
								<?php esc_html_e( 'Hook Name', 'wp-crontrol' ); ?>
							</label>
						</th>
						<td>
							<input type="text" autocorrect="off" autocapitalize="off" spellcheck="false" class="regular-text" id="hookname" name="hookname" value="<?php echo esc_attr( $existing['hookname'] ); ?>" required />
						</td>
					</tr>
					<tr class="crontrol-event-standard">
						<th valign="top" scope="row">
							<label for="args">
								<?php esc_html_e( 'Arguments (optional)', 'wp-crontrol' ); ?>
							</label>
						</th>
						<td>
							<input type="text" autocorrect="off" autocapitalize="off" spellcheck="false" class="regular-text code" id="args" name="args" value="<?php echo esc_attr( $display_args ); ?>"/>
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
					<?php
				}
				?>
				<tr>
					<th valign="top" scope="row">
						<label for="next_run_date_local">
							<?php esc_html_e( 'Next Run', 'wp-crontrol' ); ?>
						</label>
					</th>
					<td>
						<ul>
							<li>
								<label>
									<input type="radio" name="next_run_date_local" value="now" checked>
									<?php esc_html_e( 'Now', 'wp-crontrol' ); ?>
								</label>
							</li>
							<li>
								<label>
									<input type="radio" name="next_run_date_local" value="+1 day">
									<?php esc_html_e( 'Tomorrow', 'wp-crontrol' ); ?>
								</label>
							</li>
							<li>
								<label>
									<input type="radio" name="next_run_date_local" value="custom" id="next_run_date_local_custom" <?php checked( $editing ); ?>>
									<?php
									printf(
										/* translators: %s: An input field for specifying a date and time */
										esc_html__( 'At: %s', 'wp-crontrol' ),
										sprintf(
											'<br>
											<input type="date" autocorrect="off" autocapitalize="off" spellcheck="false" name="next_run_date_local_custom_date" id="next_run_date_local_custom_date" value="%1$s" placeholder="yyyy-mm-dd" pattern="\d{4}-\d{2}-\d{2}" />
											<input type="time" autocorrect="off" autocapitalize="off" spellcheck="false" name="next_run_date_local_custom_time" id="next_run_date_local_custom_time" value="%2$s" step="1" placeholder="hh:mm:ss" pattern="\d{2}:\d{2}:\d{2}" />',
											esc_attr( $next_run_date_local ),
											esc_attr( $next_run_time_local )
										)
									);
									?>
								</label>
							</li>
						</ul>

						<p class="description">
							<?php
								printf(
									/* translators: %s Timezone name. */
									esc_html__( 'Timezone: %s', 'wp-crontrol' ),
									esc_html( get_timezone_name() )
								);
							?>
						</p>
					</td>
				</tr>
				<tr>
					<th valign="top" scope="row">
						<label for="schedule">
							<?php esc_html_e( 'Recurrence', 'wp-crontrol' ); ?>
						</label>
					</th>
					<td>
						<?php Schedule\dropdown( $existing['schedule'] ); ?>
					</td>
				</tr>
			</tbody></table>
			<p class="submit">
				<input type="submit" class="button button-primary" value="<?php echo esc_attr( $button ); ?>"/>
			</p>
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
	$messages = array(
		'1'  => array(
			/* translators: 1: The name of the cron event. */
			__( 'Scheduled the cron event %s to run now.', 'wp-crontrol' ),
			'success',
			true,
		),
		'2'  => array(
			/* translators: 1: The name of the cron event. */
			__( 'Deleted all %s cron events.', 'wp-crontrol' ),
			'success',
			false,
		),
		'3'  => array(
			/* translators: 1: The name of the cron event. */
			__( 'There are no %s cron events to delete.', 'wp-crontrol' ),
			'info',
			false,
		),
		'4'  => array(
			/* translators: 1: The name of the cron event. */
			__( 'Saved the cron event %s.', 'wp-crontrol' ),
			'success',
			false,
		),
		'5'  => array(
			/* translators: 1: The name of the cron event. */
			__( 'Created the cron event %s.', 'wp-crontrol' ),
			'success',
			false,
		),
		'6'  => array(
			/* translators: 1: The name of the cron event. */
			__( 'Deleted the cron event %s.', 'wp-crontrol' ),
			'success',
			false,
		),
		'7'  => array(
			/* translators: 1: The name of the cron event. */
			__( 'Failed to the delete the cron event %s.', 'wp-crontrol' ),
			'error',
			false,
		),
		'8'  => array(
			/* translators: 1: The name of the cron event. */
			__( 'Failed to the execute the cron event %s.', 'wp-crontrol' ),
			'error',
			false,
		),
		'9'  => array(
			__( 'Deleted the selected cron events.', 'wp-crontrol' ),
			'success',
			false,
		),
		'10' => array(
			/* translators: 1: The name of the cron event. */
			__( 'Failed to save the cron event %s.', 'wp-crontrol' ),
			'error',
			false,
		),
	);

	if ( isset( $_GET['crontrol_name'] ) && isset( $_GET['crontrol_message'] ) && isset( $messages[ $_GET['crontrol_message'] ] ) ) {
		$hook    = wp_unslash( $_GET['crontrol_name'] );
		$message = wp_unslash( $_GET['crontrol_message'] );
		$link    = '';

		printf(
			'<div id="crontrol-message" class="notice notice-%1$s is-dismissible"><p>%2$s%3$s</p></div>',
			esc_attr( $messages[ $message ][1] ),
			sprintf(
				esc_html( $messages[ $message ][0] ),
				'<strong>' . esc_html( $hook ) . '</strong>'
			),
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$link
		);
	}

	$tabs  = get_tab_states();
	$table = Event\get_list_table();

	switch ( true ) {
		case $tabs['events']:
			?>
			<div class="wrap">
				<h1 class="wp-heading-inline"><?php esc_html_e( 'Cron Events', 'wp-crontrol' ); ?></h1>

				<?php echo '<a href="' . esc_url( admin_url( 'tools.php?page=crontrol_admin_manage_page&action=new-cron' ) ) . '" class="page-title-action">' . esc_html__( 'Add New', 'wp-crontrol' ) . '</a>'; ?>

				<hr class="wp-header-end">

				<?php $table->views(); ?>

				<form id="posts-filter" method="get" action="tools.php">
					<input type="hidden" name="page" value="crontrol_admin_manage_page" />
					<?php $table->search_box( __( 'Search Hook Names', 'wp-crontrol' ), 'cron-event' ); ?>
				</form>

				<form method="post" action="tools.php?page=crontrol_admin_manage_page">
					<div class="table-responsive">
						<?php $table->display(); ?>
					</div>
				</form>

				<p>
					<?php
						echo esc_html( sprintf(
							/* translators: 1: Date and time, 2: Timezone */
							__( 'Site time: %1$s (%2$s)', 'wp-crontrol' ),
							date_i18n( 'Y-m-d H:i:s' ),
							get_timezone_name()
						) );
					?>
				</p>
			</div>
			<?php

			break;

		case $tabs['add-event']:
			show_cron_form( false );
			break;

		case $tabs['edit-event']:
			show_cron_form( true );
			break;

	}

}

/**
 * Get the states of the various cron-related tabs.
 *
 * @return bool[] Array of states keyed by tab name.
 */
function get_tab_states() {
	return array(
		'events'        => ( ! empty( $_GET['page'] ) && 'crontrol_admin_manage_page' === $_GET['page'] && empty( $_GET['action'] ) ),
		'schedules'     => ( ! empty( $_GET['page'] ) && 'crontrol_admin_options_page' === $_GET['page'] ),
		'add-event'     => ( ! empty( $_GET['action'] ) && 'new-cron' === $_GET['action'] ),
		'edit-event'    => ( ! empty( $_GET['action'] ) && 'edit-cron' === $_GET['action'] ),
	);
}

/**
 * Output the cron-related tabs if we're on a cron-related admin screen.
 */
function do_tabs() {
	$tabs = get_tab_states();
	$tab  = array_filter( $tabs );

	if ( ! $tab ) {
		return;
	}

	$tab   = array_keys( $tab );
	$tab   = reset( $tab );
	$links = array(
		'events'    => array(
			'tools.php?page=crontrol_admin_manage_page',
			__( 'Cron Events', 'wp-crontrol' ),
		),
		'schedules' => array(
			'options-general.php?page=crontrol_admin_options_page',
			__( 'Cron Schedules', 'wp-crontrol' ),
		),
	);

	?>
	<div id="crontrol-header">
		<nav class="nav-tab-wrapper">
			<?php
			foreach ( $links as $id => $link ) {
				if ( $tabs[ $id ] ) {
					printf(
						'<a href="%s" class="nav-tab nav-tab-active">%s</a>',
						esc_url( $link[0] ),
						esc_html( $link[1] )
					);
				} else {
					printf(
						'<a href="%s" class="nav-tab">%s</a>',
						esc_url( $link[0] ),
						esc_html( $link[1] )
					);
				}
			}

			if ( $tabs['add-event'] ) {
				printf(
					'<span class="nav-tab nav-tab-active">%s</span>',
					esc_html__( 'Add Cron Event', 'wp-crontrol' )
				);
			} elseif ( $tabs['edit-event'] ) {
				printf(
					'<span class="nav-tab nav-tab-active">%s</span>',
					esc_html__( 'Edit Cron Event', 'wp-crontrol' )
				);
			}
			?>
		</nav>
		<?php
		do_action( 'crontrol/tab-header', $tab, $tabs );
		?>
	</div>
	<?php
}

/**
 * Returns an array of the callback functions that are attached to the given hook name.
 *
 * @param string $name The hook name.
 * @return array[] Array of callbacks attached to the hook.
 */
function get_hook_callbacks( $name ) {
	global $wp_filter;

	$actions = array();

	if ( isset( $wp_filter[ $name ] ) ) {
		// See http://core.trac.wordpress.org/ticket/17817.
		$action = $wp_filter[ $name ];

		foreach ( $action as $priority => $callbacks ) {
			foreach ( $callbacks as $callback ) {
				$callback = populate_callback( $callback );

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
			if ( ! empty( $callback['callback']['error'] ) ) {
				$return  = '<code>' . $callback['callback']['name'] . '</code>';
				$return .= '<br><span class="status-crontrol-error"><span class="dashicons dashicons-warning" aria-hidden="true"></span> ';
				$return .= esc_html( $callback['callback']['error']->get_error_message() );
				$return .= '</span>';
				return $return;
			}

			return \QM_Output_Html::output_filename(
				$callback['callback']['name'],
				$callback['callback']['file'],
				$callback['callback']['line']
			);
		}
	}

	return '<code>' . $callback['callback']['name'] . '</code>';
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
 * Sets up the Events listing screen.
 */
function setup_manage_page() {
	// Initialise the list table
	Event\get_list_table();

	// Add the initially hidden admin notice about the out of date events list
	add_action( 'admin_notices', function() {
		printf(
			'<div id="crontrol-hash-message" class="notice notice-warning"><p>%s</p></div>',
			esc_html__( 'The scheduled cron events have changed since you first opened this page. Reload the page to see the up to date list.', 'wp-crontrol' )
		);
	} );
}

/**
 * Registers the stylesheet and JavaScript for the admin areas.
 *
 * @param string $hook_suffix The admin screen ID.
 */
function enqueue_assets( $hook_suffix ) {
	$tab = get_tab_states();

	if ( ! array_filter( $tab ) ) {
		return;
	}

	$ver = filemtime( plugin_dir_path( __FILE__ ) . 'css/wp-crontrol.css' );
	wp_enqueue_style( 'wp-crontrol', plugin_dir_url( __FILE__ ) . 'css/wp-crontrol.css', array( 'dashicons' ), $ver );

	$ver = filemtime( plugin_dir_path( __FILE__ ) . 'js/wp-crontrol.js' );
	wp_enqueue_script( 'wp-crontrol', plugin_dir_url( __FILE__ ) . 'js/wp-crontrol.js', array( 'jquery' ), $ver, true );

	$vars = array();

	if ( ! empty( $tab['events'] ) ) {
		$vars['eventsHash'] = md5( json_encode( Event\get_list_table()->items ) );
		$vars['eventsHashInterval'] = 20;
	}

	if ( ! empty( $tab['add-event'] ) || ! empty( $tab['edit-event'] ) ) {
		if ( function_exists( 'wp_enqueue_code_editor' ) && current_user_can( 'edit_files' ) ) {
			$settings = wp_enqueue_code_editor( array(
				'type' => 'text/x-php',
			) );

			if ( false !== $settings ) {
				$vars['codeEditor'] = $settings;
			}
		}
	}

	wp_localize_script( 'wp-crontrol', 'wpCrontrol', $vars );
}

/**
 * Filters the list of query arguments which get removed from admin area URLs in WordPress.
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
 * Returns an array of cron event hooks that are persistently added by WordPress core.
 *
 * @return string[] Array of hook names.
 */
function get_persistent_core_hooks() {
	return array(
		'delete_expired_transients',
		'recovery_mode_clean_expired_keys',
		'update_network_counts',
		'wp_privacy_delete_old_export_files',
		'wp_scheduled_auto_draft_delete',
		'wp_scheduled_delete',
		'wp_site_health_scheduled_check',
		'wp_update_plugins',
		'wp_update_themes',
		'wp_version_check',
	);
}

/**
 * Returns an array of all cron event hooks that are added by WordPress core.
 *
 * @return string[] Array of hook names.
 */
function get_all_core_hooks() {
	return array_merge(
		get_persistent_core_hooks(),
		array(
			'do_pings',
			'importer_scheduled_cleanup',
			'publish_future_post',
			'upgrader_scheduled_cleanup',
			'wp_maybe_auto_update',
			'wp_split_shared_term_batch',
			'wp_update_comment_type_batch',
		)
	);
}

/**
 * Returns an array of cron schedules that are added by WordPress core.
 *
 * @return string[] Array of schedule names.
 */
function get_core_schedules() {
	return array(
		'hourly',
		'twicedaily',
		'daily',
		'weekly',
	);
}

/**
 * Encodes some input as JSON for output.
 *
 * @param mixed $input The input.
 * @return string The JSON-encoded output.
 */
function json_output( $input ) {
	$json_options = 0;

	if ( defined( 'JSON_UNESCAPED_SLASHES' ) ) {
		// phpcs:ignore PHPCompatibility.Constants.NewConstants.json_unescaped_slashesFound
		$json_options |= JSON_UNESCAPED_SLASHES;
	}
	if ( defined( 'JSON_PRETTY_PRINT' ) ) {
		$json_options |= JSON_PRETTY_PRINT;
	}

	return wp_json_encode( $input, $json_options );
}

/**
 * Evaluates the code in a PHP cron event using eval.
 *
 * Security: Only users with the `edit_files` capability can manage PHP cron events. This means if a user cannot edit
 * files on the site (eg. through the Plugin Editor or Theme Editor) then they cannot edit or add a PHP cron event. By
 * default, only Administrators have this capability, and with Multisite enabled only Super Admins have this capability.
 *
 * If file editing has been disabled via the `DISALLOW_FILE_MODS` or `DISALLOW_FILE_EDIT` configuration constants then
 * no user will have the `edit_files` capability, which means editing or adding a PHP cron event will not be permitted.
 *
 * Therefore, the user access level required to execute arbitrary PHP code does not change with WP Crontrol activated.
 *
 * @param string $code The PHP code to evaluate.
 */
function action_php_cron_event( $code ) {
	// phpcs:ignore Squiz.PHP.Eval.Discouraged
	eval( $code );
}

// Get this show on the road.
init_hooks();
