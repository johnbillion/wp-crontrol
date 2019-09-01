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
	protected $old_exception_handler = null;
	public static $post_type = 'crontrol_log';
	public static $taxonomy  = 'crontrol_log_hook';

	public function init() {
		foreach ( Event\count_by_hook() as $hook => $count ) {
			add_action( $hook, array( $this, 'log_start' ), -9999, 50 );
			add_action( $hook, array( $this, 'log_end' ), 9999, 50 );
		}

		add_filter( 'disable_months_dropdown', array( $this, 'filter_disable_months_dropdown' ), 10, 2 );
		add_filter( 'wpcom_async_transition_post_status_schedule_async', array( $this, 'filter_wpcom_async_transition' ), 10, 2 );
		add_filter( 'manage_crontrol_log_posts_columns',       array( $this, 'columns' ) );
		add_action( 'manage_crontrol_log_posts_custom_column', array( $this, 'column' ), 10, 2 );

		register_post_type( self::$post_type, array(
			'public'  => false,
			'show_ui' => true,
			'show_in_admin_bar' => false,
			'labels'  => array(
				'name'                     => 'Cron Logs',
				'singular_name'            => 'Cron Log',
				'menu_name'                => 'Cron Logs',
				'name_admin_bar'           => 'Cron Log',
				'view_item'                => 'View Cron Log',
				'view_items'               => 'View Cron Logs',
				'search_items'             => 'Search Cron Logs',
				'not_found'                => 'No cron logs found.',
				'not_found_in_trash'       => 'No cron logs found in trash.',
				'all_items'                => 'Cron Logs',
				'archives'                 => 'Cron Log Archives',
				'attributes'               => 'Cron Log Attributes',
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

		register_taxonomy( self::$taxonomy, self::$post_type, array(
			'public' => false,
			'capabilities' => array(
				'manage_terms' => 'do_not_allow',
				'edit_terms'   => 'do_not_allow',
				'delete_terms' => 'do_not_allow',
				'assign_terms' => 'do_not_allow',
			),
			'labels' => array(
				'menu_name'                  => 'Hooks',
				'name'                       => 'Hooks',
				'singular_name'              => 'Hook',
				'search_items'               => 'Search Hooks',
				'popular_items'              => 'Popular Hooks',
				'all_items'                  => 'All Hooks',
				'view_item'                  => 'View Hook',
				'not_found'                  => 'No hooks found',
				'no_terms'                   => 'No hooks',
				'items_list_navigation'      => 'Hooks list navigation',
				'items_list'                 => 'Hooks list',
				'most_used'                  => 'Most Used',
				'back_to_items'              => '&larr; Back to Hooks',
			),
		) );
	}

	public function filter_wpcom_async_transition( $schedule, array $args ) {
		if ( self::$post_type === get_post_type( $args['post_id'] ) ) {
			return false;
		}

		return $schedule;
	}

	public function filter_disable_months_dropdown( $disable, $post_type ) {
		if ( self::$post_type === $post_type ) {
			return true;
		}

		return $disable;
	}

	/**
	 * Debugging action for the HTTP API.
	 *
	 * @param mixed  $response A parameter which varies depending on $action.
	 * @param string $action   The debug action. Currently one of 'response' or 'transports_list'.
	 * @param string $class    The HTTP transport class name.
	 * @param array  $args     HTTP request arguments.
	 * @param string $url      The request URL.
	 */
	public function action_http_api_debug( $response, $action, $class, $args, $url ) {
		if ( 'response' !== $action ) {
			return;
		}

		if ( is_wp_error( $response ) ) {
			$response = $response->get_error_message();
		} elseif ( ! $args['blocking'] ) {
			/* translators: A non-blocking HTTP API request */
			$response = __( 'Non-blocking', 'wp-crontrol' );
		} else {
			$code = wp_remote_retrieve_response_code( $response );
			$msg  = wp_remote_retrieve_response_message( $response );

			$response = $code . ' ' . $msg;
		}

		$this->data['https'][] = array(
			'method'   => $args['method'],
			'url'      => $url,
			'response' => $response,
		);
	}

	public function columns( array $columns ) {

		unset( $columns['date'] );

		$columns['title']   = 'Hook';
		$columns['ran']     = 'Date';
		$columns['actions'] = 'Actions';
		$columns['time']    = 'Time (s)';
		$columns['queries'] = 'Database Queries';
		$columns['https']   = 'HTTP Requests';
		$columns['error']   = 'Error';

		return $columns;
	}

	public function column( $name, $post_id ) {
		$post    = get_post( $post_id );
		$hook    = wp_list_pluck( get_the_terms( $post_id, self::$taxonomy ), 'slug' )[0];
		$actions = get_post_meta( $post_id, 'crontrol_log_actions', true );
		$queries = get_post_meta( $post_id, 'crontrol_log_queries', true );
		$https   = get_post_meta( $post_id, 'crontrol_log_https', true );
		$time    = get_post_meta( $post_id, 'crontrol_log_time', true );

		if ( empty( $actions ) ) {
			$actions = array();
		}

		switch ( $name ) {

			case 'ran':
				echo esc_html( mysql2date( 'Y-m-d H:i:s', $post->post_date ) );
				break;

			case 'time':
				echo esc_html( number_format_i18n( $time, 4 ) );
				break;

			case 'error':
				$error = get_post_meta( $post_id, 'crontrol_log_exception', true );
				if ( ! empty( $error ) ) {
					printf(
						'<span style="color:#c00"><span class="dashicons dashicons-warning" aria-hidden="true"></span> %1$s</span><br>%2$s:%3$s',
						esc_html( $error['message'] ),
						esc_html( str_replace( array( WP_CONTENT_DIR . '/', ABSPATH . '/' ), '', $error['file'] ) ),
						esc_html( $error['line'] )
					);
				}
				break;

			case 'actions':
				if ( 'crontrol_cron_job' === $hook ) {
					echo '<em>' . esc_html__( 'WP Crontrol', 'wp-crontrol' ) . '</em>';
				} elseif ( ! empty( $actions ) ) {
					$actions = array_map( 'esc_html', $actions );
					echo '<code>';
					echo implode( '</code><br><code>', $actions );
					echo '</code>';
				}
				break;

			case 'queries':
				echo esc_html( number_format_i18n( $queries ) );
				break;

			case 'https':
				if ( ! empty( $https ) ) {
					echo '<ol>';
					foreach ( $https as $key => $http ) {
						printf(
							'<li>%1$s %2$s<br>%3$s</li>',
							esc_html( $http['method'] ),
							esc_html( $http['url'] ),
							esc_html( $http['response'] )
						);
					}
					echo '</ol>';
				}
				break;

		}
	}

	/**
	 * Exception handler.
	 *
	 * In PHP >= 7 this will catch Throwable objects.
	 * In PHP < 7 it will catch Exception objects.
	 *
	 * @param Throwable|Exception $e The error or exception.
	 * @throws Exception Re-thrown when necessary.
	 */
	public function exception_handler( $e ) {
		$this->data['exception'] = $e;

		$this->log_end();

		if ( $this->old_exception_handler ) {
			call_user_func( $this->old_exception_handler, $e );
		} else {
			throw new Exception( $e->getMessage(), $e->getCode(), $e );
		}
	}

	public function log_start() {
		global $wpdb;

		$this->data = array();

		$this->old_exception_handler = set_exception_handler( array( $this, 'exception_handler' ) );

		$this->data['actions'] = array();
		$this->data['args']    = func_get_args();
		$this->data['hook']    = current_filter();
		$this->data['https']   = array();

		foreach ( get_hook_callbacks( $this->data['hook'] ) as $action ) {
			$this->data['actions'][] = $action['callback']['name'];
		}

		add_action( 'http_api_debug', array( $this, 'action_http_api_debug' ), 9999, 5 );

		$this->data['start_memory']  = memory_get_usage();
		$this->data['start_time']    = microtime( true );
		$this->data['start_queries'] = $wpdb->num_queries;
	}

	public function log_end() {
		global $wpdb;

		$this->data['end_memory']  = memory_get_usage();
		$this->data['end_time']    = microtime( true );
		$this->data['end_queries'] = $wpdb->num_queries;

		remove_action( 'http_api_debug', array( $this, 'action_http_api_debug' ), 9999 );

		set_exception_handler( $this->old_exception_handler );

		$post_id = wp_insert_post( array(
			'post_type'    => self::$post_type,
			'post_title'   => $this->data['hook'],
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
			'crontrol_log_actions' => $this->data['actions'],
			'crontrol_log_https'   => $this->data['https'],
		);

		if ( ! empty( $this->data['exception'] ) ) {
			$metas['crontrol_log_exception'] = array(
				'message' => $this->data['exception']->getMessage(),
				'file'    => $this->data['exception']->getFile(),
				'line'    => $this->data['exception']->getLine(),
			);
		}

		foreach ( $metas as $meta_key => $meta_value ) {
			add_post_meta( $post_id, $meta_key, wp_slash( $meta_value ), true );
		}

		wp_set_post_terms( $post_id, array( $this->data['hook'] ), self::$taxonomy, true );
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
