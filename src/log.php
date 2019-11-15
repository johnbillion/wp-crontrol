<?php
/**
 * Functions related to logging cron events.
 *
 * @package WP Crontrol
 */

namespace Crontrol;

use Throwable;
use Exception;
use WP_Post;

/**
 * Main class which encapsulates cron event logging functionality.
 */
class Log {

	/**
	 * Array of data logged for a cron event.
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * The old exception handler, if one was registered.
	 *
	 * @var callable
	 */
	protected $old_exception_handler = null;

	/**
	 * The post type name for the cron event log.
	 *
	 * @var string
	 */
	public static $post_type = 'crontrol_log';

	/**
	 * The taxonomy name for the cron event log hook.
	 *
	 * @var string
	 */
	public static $taxonomy = 'crontrol_log_hook';

	/**
	 * Sets up actions and filters for the cron event logging.
	 */
	public function init() {
		$post_type = self::$post_type;

		add_filter( 'disable_months_dropdown',                   array( $this, 'filter_disable_months_dropdown' ), 10, 2 );
		add_filter( "manage_{$post_type}_posts_columns",         array( $this, 'columns' ) );
		add_filter( "manage_edit-{$post_type}_sortable_columns", array( $this, 'sortable_columns' ), 10, 2 );
		add_action( "manage_{$post_type}_posts_custom_column",   array( $this, 'column' ), 10, 2 );
		add_filter( 'page_row_actions',                          array( $this, 'remove_actions' ), 10, 2 );
		add_filter( "bulk_actions-edit-{$post_type}",            array( $this, 'remove_quick_edit_menu' ) );
		add_filter( 'display_post_states',                       array( $this, 'filter_post_state' ), 20, 2 );
		add_action( 'load-edit.php',                             array( $this, 'default_sort' ) );

		register_setting( 'crontrol_group', 'crontrol_log' );

		// WordPress.com VIP specific functionality:
		add_filter( 'wpcom_async_transition_post_status_schedule_async', array( $this, 'filter_wpcom_async_transition' ), 10, 2 );

		$this->setup_hooks();

		register_post_type( self::$post_type, array(
			'public'            => false,
			'show_ui'           => true,
			'show_in_admin_bar' => false,
			'hierarchical'      => true,
			'labels'            => array(
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
			'show_in_menu'      => 'tools.php',
			'map_meta_cap'      => true,
			'capabilities'      => array(
				'edit_posts'             => 'manage_options',
				'edit_others_posts'      => 'manage_options',
				'publish_posts'          => 'do_not_allow',
				'read_private_posts'     => 'do_not_allow',
				'read'                   => 'manage_options',
				'delete_posts'           => 'manage_options',
				'delete_private_posts'   => 'manage_options',
				'delete_published_posts' => 'manage_options',
				'delete_others_posts'    => 'manage_options',
				'edit_private_posts'     => 'do_not_allow',
				'edit_published_posts'   => 'manage_options',
				'create_posts'           => 'do_not_allow',
			),
		) );

		register_taxonomy( self::$taxonomy, self::$post_type, array(
			'public'       => false,
			'capabilities' => array(
				'manage_terms' => 'do_not_allow',
				'edit_terms'   => 'do_not_allow',
				'delete_terms' => 'do_not_allow',
				'assign_terms' => 'do_not_allow',
			),
			'labels'       => array(
				'menu_name'             => 'Hooks',
				'name'                  => 'Hooks',
				'singular_name'         => 'Hook',
				'search_items'          => 'Search Hooks',
				'popular_items'         => 'Popular Hooks',
				'all_items'             => 'All Hooks',
				'view_item'             => 'View Hook',
				'not_found'             => 'No hooks found',
				'no_terms'              => 'No hooks',
				'items_list_navigation' => 'Hooks list navigation',
				'items_list'            => 'Hooks list',
				'most_used'             => 'Most Used',
				'back_to_items'         => '&larr; Back to Hooks',
			),
		) );
	}

	/**
	 * Sets up the hooks needed to log cront events as they run.
	 */
	public function setup_hooks() {
		$logged = get_option( 'crontrol_log', array() );

		if ( empty( $logged ) ) {
			return;
		}

		array_map( array( $this, 'observe' ), $logged );
	}

	/**
	 * Initialises the observance of the given hook.
	 *
	 * @param string $hook The hook name.
	 */
	public function observe( $hook ) {
		add_action( $hook, array( $this, 'log_start' ), -9999, 50 );
		add_action( $hook, array( $this, 'log_end' ), 9999, 50 );
	}

	/**
	 * Prevents WordPress.com VIP from firing async transition hooks for the cron log post type.
	 *
	 * This avoids an infinite loop of cron events on VIP.
	 *
	 * @param bool  $schedule Whether to schedule the cron event.
	 * @param array $args     {
	 *     Array of arguments.
	 *
	 *     @type int    $post_id    The post ID.
	 *     @type string $new_status The new post status.
	 *     @type string $old_status The old post status.
	 * @return bool Whether to schedule the cron event.
	 */
	public function filter_wpcom_async_transition( $schedule, array $args ) {
		if ( get_post_type( $args['post_id'] ) === self::$post_type ) {
			return false;
		}

		return $schedule;
	}

	/**
	 * Removes all visible states from the log post type listing screen.
	 *
	 * Some plugins, such as Classic Editor, add states here that aren't desirable.
	 *
	 * @param string[] $states The post state links as HTML.
	 * @param \WP_Post $post   The post object.
	 * @return string[] The updated post state links.
	 */
	public function filter_post_state( array $states, \WP_Post $post ) {
		if ( get_post_type( $post ) === self::$post_type ) {
			$states = array();
		}

		return $states;
	}

	/**
	 * Disables the "All dates" dropdown filter on the log post type listing screen.
	 *
	 * @param bool   $disable   Whether to disable the dropdown.
	 * @param string $post_type The post type.
	 * @return bool Whether to disable the dropdown.
	 */
	public function filter_disable_months_dropdown( $disable, $post_type ) {
		if ( self::$post_type === $post_type ) {
			return true;
		}

		return $disable;
	}

	/**
	 * Removes all actions from the post row actions. None of them are needed.
	 *
	 * @param string[] $actions Array of post actions.
	 * @param WP_Post  $post    The current post object.
	 * @return string[] Array of updated post actions.
	 */
	public function remove_actions( array $actions, WP_Post $post ) {
		if ( self::$post_type !== $post->post_type ) {
			return $actions;
		}

		return array();
	}

	/**
	 * Removes the Quick Edit link from the bulk actions menu.
	 *
	 * @param string[] $actions Array of bulk actions.
	 * @return string[] Array of updated bulk actions.
	 */
	public function remove_quick_edit_menu( array $actions ) {
		unset( $actions['edit'] );

		return $actions;
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

	/**
	 * Updates the list of columns on the log post type listing screen.
	 *
	 * @param string[] $columns Array of column headings keyed by column name.
	 * @return string[] Updated array of columns.
	 */
	public function columns( array $columns ) {
		unset( $columns['date'], $columns['title'] );

		$columns['hook']    = esc_html__( 'Hook', 'wp-crontrol' );
		$columns['ran']     = esc_html__( 'Date', 'wp-crontrol' );
		$columns['args']    = esc_html__( 'Args', 'wp-crontrol' );
		$columns['actions'] = esc_html__( 'Actions', 'wp-crontrol' );
		$columns['time']    = esc_html__( 'Time (s)', 'wp-crontrol' );
		$columns['queries'] = esc_html__( 'Database Queries', 'wp-crontrol' );
		$columns['https']   = esc_html__( 'HTTP Requests', 'wp-crontrol' );
		$columns['error']   = esc_html__( 'Fatal Errors', 'wp-crontrol' );

		return $columns;
	}

	/**
	 * Sets the sortable columns for the log post type listing screen.
	 *
	 * @param array $columns List screen columns.
	 * @return array Updated columns.
	 */
	public function sortable_columns( array $columns ) {
		return array(
			'hook' => 'title',
			'ran'  => array( 'date', true ),
		);
	}

	/**
	 * Sets the default sort field and order for the log post type listing screen.
	 */
	public function default_sort() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		if ( 'edit' !== get_current_screen()->base ) {
			return;
		}

		if ( get_current_screen()->post_type !== self::$post_type ) {
			return;
		}

		// If we've already ordered the screen, bail out:
		if ( isset( $_GET['orderby'] ) ) {
			return;
		}

		// Specify the default sort column:
		$_GET['orderby'] = 'date';
		$_GET['order']   = 'desc';
	}

	/**
	 * Handles the output for the given column on the log post type listing screen.
	 *
	 * @param string $name    The column name.
	 * @param int    $post_id The post ID.
	 */
	public function column( $name, $post_id ) {
		$post = get_post( $post_id );

		switch ( $name ) {

			case 'hook':
				the_title();
				break;

			case 'ran':
				echo esc_html( mysql2date( 'Y-m-d H:i:s', $post->post_date ) );
				break;

			case 'time':
				$time = get_post_meta( $post_id, 'crontrol_log_time', true );

				echo esc_html( number_format_i18n( $time, 4 ) );
				break;

			case 'args':
				$args = $post->post_content;

				if ( '[]' === $args ) {
					printf(
						'<em>%s</em>',
						esc_html__( 'None', 'wp-crontrol' )
					);
				} else {
					printf(
						'<pre>%s</pre>',
						esc_html( json_output( json_decode( $args ) ) )
					);
				}
				break;

			case 'error':
				$error = get_post_meta( $post->ID, 'crontrol_log_exception', true );
				if ( ! empty( $error ) ) {
					if ( 'Exception' === $error['type'] ) {
						$message = sprintf(
							/* translators: %s: PHP error message */
							__( 'Uncaught Exception: %s', 'wp-crontrol' ),
							$error['message']
						);
					} else {
						$message = sprintf(
							/* translators: %s: PHP error message */
							__( 'Uncaught Error: %s', 'wp-crontrol' ),
							$error['message']
						);
					}

					printf(
						'<span style="color:#c00"><span class="dashicons dashicons-warning" aria-hidden="true"></span> %1$s</span><br>%2$s:%3$s',
						esc_html( $message ),
						esc_html( str_replace( array( WP_CONTENT_DIR . '/', ABSPATH . '/' ), '', $error['file'] ) ),
						esc_html( $error['line'] )
					);
				} else {
					printf(
						'<em>%s</em>',
						esc_html__( 'None', 'wp-crontrol' )
					);
				}
				break;

			case 'actions':
				$actions = get_post_meta( $post_id, 'crontrol_log_actions', true );
				$hook    = '';
				$terms   = get_the_terms( $post_id, self::$taxonomy );

				if ( is_array( $terms ) ) {
					$hooks = wp_list_pluck( $terms, 'slug' );
					if ( $hooks ) {
						$hook = $hooks[0];
					}
				}

				if ( 'crontrol_cron_job' === $hook ) {
					echo '<em>' . esc_html__( 'WP Crontrol', 'wp-crontrol' ) . '</em>';
				} elseif ( ! empty( $actions ) ) {
					echo '<code>';
					echo implode( '</code><br><code>', array_map( 'esc_html', $actions ) );
					echo '</code>';
				} else {
					printf(
						'<em>%s</em>',
						esc_html__( 'None', 'wp-crontrol' )
					);
				}
				break;

			case 'queries':
				$queries = get_post_meta( $post_id, 'crontrol_log_queries', true );

				if ( ! empty( $queries ) ) {
					echo esc_html( number_format_i18n( $queries ) );
				} else {
					printf(
						'<em>%s</em>',
						esc_html__( 'None', 'wp-crontrol' )
					);
				}
				break;

			case 'https':
				$https = get_post_meta( $post_id, 'crontrol_log_https', true );

				if ( ! empty( $https ) ) {
					echo '<ol>';
					foreach ( $https as $http ) {
						printf(
							'<li>%1$s %2$s<br>%3$s</li>',
							esc_html( $http['method'] ),
							esc_html( $http['url'] ),
							esc_html( $http['response'] )
						);
					}
					echo '</ol>';
				} else {
					printf(
						'<em>%s</em>',
						esc_html__( 'None', 'wp-crontrol' )
					);
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

	/**
	 * Starts the logging for the current cron event.
	 */
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

	/**
	 * Ends the logging for the current cron event.
	 */
	public function log_end() {
		global $wpdb;

		$this->data['end_memory']  = memory_get_usage();
		$this->data['end_time']    = microtime( true );
		$this->data['end_queries'] = $wpdb->num_queries;
		$this->data['num_queries'] = ( $this->data['end_queries'] - $this->data['start_queries'] );

		remove_action( 'http_api_debug', array( $this, 'action_http_api_debug' ), 9999 );

		set_exception_handler( $this->old_exception_handler );

		$post_id = wp_insert_post( wp_slash( array(
			'post_type'    => self::$post_type,
			'post_title'   => $this->data['hook'],
			'post_date'    => get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $this->data['start_time'] ), 'Y-m-d H:i:s' ),
			'post_status'  => 'publish',
			'post_content' => wp_json_encode( $this->data['args'] ),
			'post_name'    => uniqid(),
		) ), true );

		if ( is_wp_error( $post_id ) ) {
			return; // ¯\_(ツ)_/¯
		}

		$metas = array(
			'crontrol_log_memory'  => ( $this->data['end_memory'] - $this->data['start_memory'] ),
			'crontrol_log_time'    => ( $this->data['end_time'] - $this->data['start_time'] ),
			'crontrol_log_queries' => $this->data['num_queries'],
			'crontrol_log_actions' => $this->data['actions'],
			'crontrol_log_https'   => $this->data['https'],
		);

		if ( ! empty( $this->data['exception'] ) ) {
			$metas['crontrol_log_exception'] = array(
				'message' => $this->data['exception']->getMessage(),
				'file'    => $this->data['exception']->getFile(),
				'line'    => $this->data['exception']->getLine(),
				'type'    => is_a( $this->data['exception'], 'Exception' ) ? 'Exception' : 'Throwable',
			);
		}

		foreach ( $metas as $meta_key => $meta_value ) {
			add_post_meta( $post_id, $meta_key, wp_slash( $meta_value ), true );
		}

		wp_set_post_terms( $post_id, array( $this->data['hook'] ), self::$taxonomy, true );
	}

	/**
	 * Returns an array of all known cron event hooks. Hooks that are logged have a value or true, all others false.
	 *
	 * @return bool[] Array of logged status, keyed by hook name.
	 */
	public static function get_logged_hooks() {
		$events = Event\count_by_hook();
		$events = array_fill_keys( array_keys( $events ), false );
		$logged = get_option( 'crontrol_log', array() );

		if ( empty( $logged ) ) {
			$logged = array();
		}

		$logged = array_fill_keys( $logged, true );
		$all    = array_merge( $events, $logged );

		ksort( $all );

		return $all;
	}

	/**
	 * Shows the event log related options panel.
	 */
	public static function show_options() {
		$all = self::get_logged_hooks();
		?>
		<form action="options.php" method="POST" class="crontrol-log-form">
			<fieldset>
				<legend><?php esc_html_e( 'Enabled Logging For:', 'wp-crontrol' ); ?></legend>
				<div class="crontrol-log-options">
					<?php
					settings_fields( 'crontrol_group' );

					foreach ( $all as $hook => $logged ) {
						printf(
							'<label><input type="checkbox" name="crontrol_log[]" value="%1$s" %2$s />%3$s</label>',
							esc_attr( $hook ),
							checked( $logged, true, false ),
							esc_html( $hook )
						);
					}
					?>
				</div>
			</fieldset>
			<p><input type="submit" value="<?php esc_attr_e( 'Save', 'wp-crontrol' ); ?>" class="button button-primary"></p>
		</form>
		<?php
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
