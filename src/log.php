<?php
/**
 * Functions related to logging cron events.
 *
 * @package WP Crontrol
 */

namespace Crontrol;

use ParseError;
use Error;
use Throwable;
use Exception;

class Log {

	protected $data = array();

	public function init() {
		foreach ( Event\count_by_hook() as $hook => $count ) {
			add_action( $hook, array( $this, 'log_start' ), -9999, 50 );
			add_action( $hook, array( $this, 'log_end' ), 9999, 50 );
		}

		register_post_type( 'crontrol_log', array(
			'public'  => false,
			'show_ui' => true,
			'show_in_admin_bar' => false,
			'labels'  => array(
				'name'                     => 'Cron Logs',
				'singular_name'            => 'Cron Log',
				'menu_name'                => 'Cron Logs',
				'name_admin_bar'           => 'Cron Log',
				'add_new'                  => 'Add New',
				'add_new_item'             => 'Add New Cron Log',
				'edit_item'                => 'Edit Cron Log',
				'new_item'                 => 'New Cron Log',
				'view_item'                => 'View Cron Log',
				'view_items'               => 'View Cron Logs',
				'search_items'             => 'Search Cron Logs',
				'not_found'                => 'No cron logs found.',
				'not_found_in_trash'       => 'No cron logs found in trash.',
				'parent_item_colon'        => 'Parent Cron Log:',
				'all_items'                => 'Cron Logs',
				'archives'                 => 'Cron Log Archives',
				'attributes'               => 'Cron Log Attributes',
				'insert_into_item'         => 'Insert into cron log',
				'uploaded_to_this_item'    => 'Uploaded to this cron log',
				'filter_items_list'        => 'Filter cron logs list',
				'items_list_navigation'    => 'Cron logs list navigation',
				'items_list'               => 'Cron logs list',
				'item_published'           => 'Cron log published.',
				'item_published_privately' => 'Cron log published privately.',
				'item_reverted_to_draft'   => 'Cron log reverted to draft.',
				'item_scheduled'           => 'Cron log scheduled.',
				'item_updated'             => 'Cron log updated.',
			),
			'show_in_menu' => 'tools.php',
			'map_meta_cap' => true,
			'capabilities' => array(
				'edit_posts'             => 'manage_options',
				'edit_others_posts'      => 'do_not_allow',
				'publish_posts'          => 'do_not_allow',
				'read_private_posts'     => 'manage_options',
				'read'                   => 'manage_options',
				'delete_posts'           => 'manage_options',
				'delete_private_posts'   => 'manage_options',
				'delete_published_posts' => 'manage_options',
				'delete_others_posts'    => 'manage_options',
				'edit_private_posts'     => 'do_not_allow',
				'edit_published_posts'   => 'do_not_allow',
				'create_posts'           => 'do_not_allow',
			),
		) );

		register_taxonomy( 'crontrol_log_action', 'crontrol_log', array(
			'public' => false,
			'show_admin_column' => true,
			'capabilities' => array(
				'manage_terms' => 'do_not_allow',
				'edit_terms'   => 'do_not_allow',
				'delete_terms' => 'do_not_allow',
				'assign_terms' => 'do_not_allow',
			),
			'labels' => array(
				'menu_name'                  => 'Actions',
				'name'                       => 'Actions',
				'singular_name'              => 'Action',
				'search_items'               => 'Search Actions',
				'popular_items'              => 'Popular Actions',
				'all_items'                  => 'All Actions',
				'parent_item'                => 'Parent Action',
				'parent_item_colon'          => 'Parent Action:',
				'edit_item'                  => 'Edit Action',
				'view_item'                  => 'View Action',
				'update_item'                => 'Update Action',
				'add_new_item'               => 'Add New Action',
				'new_item_name'              => 'New Action Name',
				'separate_items_with_commas' => 'Separate actions with commas',
				'add_or_remove_items'        => 'Add or remove actions',
				'choose_from_most_used'      => 'Choose from most used actions',
				'not_found'                  => 'No actions found',
				'no_terms'                   => 'No actions',
				'items_list_navigation'      => 'Actions list navigation',
				'items_list'                 => 'Actions list',
				'most_used'                  => 'Most Used',
				'back_to_items'              => '&larr; Back to Actions',
			),
		) );
	}

	public function log_start() {
		global $wpdb;

		$this->data['start_memory']  = memory_get_usage();
		$this->data['start_time']    = microtime( true );
		$this->data['start_queries'] = $wpdb->num_queries;
		$this->data['args']          = func_get_args();
		$this->data['action']        = current_filter();
	}

	public function log_end() {
		global $wpdb;

		$this->data['end_memory']  = memory_get_usage();
		$this->data['end_time']    = microtime( true );
		$this->data['end_queries'] = $wpdb->num_queries;

		$post_id = wp_insert_post( array(
			'post_type'    => 'crontrol_log',
			'post_title'   => $this->data['action'],
			'post_date'    => get_date_from_gmt( date( 'Y-m-d H:i:s', $this->data['start_time'] ), 'Y-m-d H:i:s' ),
			'post_status'  => 'publish',
			'post_content' => wp_json_encode( $this->data['args'] ),
			'post_name'    => uniqid(),
		), true );

		if ( is_wp_error( $post_id ) ) {
			return; // ¯\_(ツ)_/¯
		}

		$metas = array(
			'crontrol_log_memory'  => ( $this->data['end_memory'] - $this->data['start_memory'] ),
			'crontrol_log_time'    => ( $this->data['end_time'] - $this->data['start_time'] ),
			'crontrol_log_queries' => ( $this->data['end_queries'] - $this->data['start_queries'] ),
		);

		foreach ( $metas as $meta_key => $meta_value ) {
			add_post_meta( $post_id, $meta_key, $meta_value, true );
		}

		wp_set_post_terms( $post_id, $this->data['action'], 'crontrol_log_action' );
	}

	/**
	 * Singleton instantiator.
	 *
	 * @return Log Logger instance.
	 */
	public static function get_instance() {
		static $instance;

		if ( ! isset( $instance ) ) {
			$instance = new Log();
		}

		return $instance;
	}

	/**
	 * Private class constructor. Use `get_instance()` to get the instance.
	 */
	final private function __construct() {}

}
