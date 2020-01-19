<?php
/**
 * Functions related to logging cron events.
 *
 * @package wp-crontrol
 */

namespace Crontrol;

use Throwable;
use Exception;
use WP_Error;
use WP_Post;
use WP_Query;

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
	 * PSR-3 compatible logger instance.
	 *
	 * @var Logger
	 */
	protected $logger = null;

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
	public static $taxonomy_hook = 'crontrol_log_hook';

	/**
	 * The name for the status when a logged event had no action.
	 *
	 * @var string
	 */
	public static $status_no_action = 'crontrol-no-action';

	/**
	 * The name for the status when an event log is running.
	 *
	 * @var string
	 */
	public static $status_running = 'crontrol-running';

	/**
	 * The name for the status when an event log is complete.
	 *
	 * @var string
	 */
	public static $status_complete = 'crontrol-complete';

	/**
	 * The name for the status when an event log contains a warning.
	 *
	 * @var string
	 */
	public static $status_warning = 'crontrol-warning';

	/**
	 * The name for the status when an event log contains an error.
	 *
	 * @var string
	 */
	public static $status_error = 'crontrol-error';

	/**
	 * The name for the status when an event has stalled.
	 *
	 * This is a psuedo-status only shown in the UI.
	 *
	 * @var string
	 */
	public static $status_stalled = 'crontrol-stalled';

	/**
	 * Sets up actions and filters for the cron event logging.
	 */
	public function init() {
		$post_type = self::$post_type;

		add_filter( 'disable_months_dropdown',                   array( $this, 'filter_disable_months_dropdown' ), 10, 2 );
		add_filter( "manage_{$post_type}_posts_columns",         array( $this, 'columns' ), 999 );
		add_filter( "manage_edit-{$post_type}_sortable_columns", array( $this, 'sortable_columns' ), 10, 2 );
		add_action( "manage_{$post_type}_posts_custom_column",   array( $this, 'column' ), 10, 2 );
		add_filter( 'page_row_actions',                          array( $this, 'remove_actions' ), 10, 2 );
		add_filter( "bulk_actions-edit-{$post_type}",            array( $this, 'remove_quick_edit_menu' ) );
		add_filter( 'display_post_states',                       array( $this, 'filter_post_state' ), 20, 2 );
		add_action( 'load-edit.php',                             array( $this, 'default_sort' ) );
		add_filter( 'post_class',                                array( $this, 'filter_post_class' ), 10, 3 );
		add_action( 'pre_get_posts',                             array( $this, 'action_pre_get_posts' ) );
		add_action( 'add_meta_boxes',                            array( $this, 'action_meta_boxes' ), 10, 2 );
		add_action( 'admin_notices',                             array( $this, 'action_admin_notices' ) );
		add_filter( 'screen_layout_columns',                     array( $this, 'filter_layout_columns' ) );
		add_filter( "get_user_option_screen_layout_{$post_type}", array( $this, 'filter_layout_option' ) );
		add_action( 'do_meta_boxes',                             array( $this, 'remove_publish_meta_box' ) );
		add_action( 'restrict_manage_posts',                     array( $this, 'action_restrict_manage_posts' ), 10, 2 );

		register_setting( 'crontrol_group', 'crontrol_log' );

		// WordPress.com VIP specific functionality:
		add_filter( 'wpcom_async_transition_post_status_schedule_async', array( $this, 'filter_wpcom_async_transition' ), 10, 2 );
		add_action( 'a8c_cron_control_event_threw_catchable_error', array( $this, 'action_cron_control_error', 10, 2 ) );

		$this->setup_hooks();

		register_post_type( self::$post_type, array(
			'public'            => false,
			'show_ui'           => true,
			'supports'          => false,
			'show_in_admin_bar' => false,
			'hierarchical'      => true,
			'labels'            => array(
				'name'                  => __( 'Cron Logs', 'wp-crontrol' ),
				'singular_name'         => __( 'Cron Log', 'wp-crontrol' ),
				'edit_item'             => __( 'Cron Log', 'wp-crontrol' ),
				'search_items'          => __( 'Search Cron Logs', 'wp-crontrol' ),
				'not_found'             => __( 'No cron logs found.', 'wp-crontrol' ),
				'not_found_in_trash'    => __( 'No cron logs found in trash.', 'wp-crontrol' ),
				'all_items'             => __( 'Cron Logs', 'wp-crontrol' ),
				'filter_items_list'     => __( 'Filter cron logs list', 'wp-crontrol' ),
				'items_list_navigation' => __( 'Cron logs list navigation', 'wp-crontrol' ),
				'items_list'            => __( 'Cron logs list', 'wp-crontrol' ),
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

		register_taxonomy( self::$taxonomy_hook, self::$post_type, array(
			'public'       => false,
			'capabilities' => array(
				'manage_terms' => 'do_not_allow',
				'edit_terms'   => 'do_not_allow',
				'delete_terms' => 'do_not_allow',
				'assign_terms' => 'do_not_allow',
			),
			'labels'       => array(
				'all_items' => __( 'All Hooks', 'wp-crontrol' ),
			),
		) );

		$status_args = array(
			'exclude_from_search'       => true,
			'public'                    => false,
			'internal'                  => true,
			'protected'                 => false,
			'private'                   => false,
			'publicly_queryable'        => false,
			'show_in_admin_status_list' => true,
			'show_in_admin_all_list'    => true,
			'date_floating'             => false,
		);

		register_post_status(
			self::$status_no_action,
			$status_args + array(
				/* translators: Label for a cron log status */
				'label'       => __( 'No Action', 'wp-crontrol' ),
				/* translators: %s: Number of running cron logs. */
				'label_count' => _n_noop(
					'No Action <span class="count">(%s)</span>',
					'No Action <span class="count">(%s)</span>',
					'wp-crontrol'
				),
			)
		);

		register_post_status(
			self::$status_running,
			$status_args + array(
				/* translators: Label for a cron log status */
				'label'       => __( 'Running', 'wp-crontrol' ),
				/* translators: %s: Number of running cron logs. */
				'label_count' => _n_noop(
					'Running <span class="count">(%s)</span>',
					'Running <span class="count">(%s)</span>',
					'wp-crontrol'
				),
			)
		);

		register_post_status(
			self::$status_complete,
			$status_args + array(
				/* translators: Label for a cron log status */
				'label'       => __( 'Complete', 'wp-crontrol' ),
				/* translators: %s: Number of complete cron logs. */
				'label_count' => _n_noop(
					'Complete <span class="count">(%s)</span>',
					'Complete <span class="count">(%s)</span>',
					'wp-crontrol'
				),
			)
		);

		register_post_status(
			self::$status_warning,
			$status_args + array(
				/* translators: Label for a cron log status */
				'label'       => __( 'Warning', 'wp-crontrol' ),
				/* translators: %s: Number of cron logs with warnings. */
				'label_count' => _n_noop(
					'Warning <span class="count">(%s)</span>',
					'Warning <span class="count">(%s)</span>',
					'wp-crontrol'
				),
			)
		);

		register_post_status(
			self::$status_error,
			$status_args + array(
				/* translators: Label for a cron log status */
				'label'       => __( 'Error', 'wp-crontrol' ),
				/* translators: %s: Number of cron logs with errors. */
				'label_count' => _n_noop(
					'Error <span class="count">(%s)</span>',
					'Error <span class="count">(%s)</span>',
					'wp-crontrol'
				),
			)
		);
	}

	/**
	 * Sets the status parameter for the event logs query. Mainly for the 'All' view on the list table.
	 *
	 * @param WP_Query $wp_query The current query.
	 */
	public function action_pre_get_posts( WP_Query $wp_query ) {
		if ( $wp_query->get( 'post_type' ) !== self::$post_type ) {
			return;
		}

		if ( $wp_query->get( 'post_status' ) ) {
			return;
		}

		// Show all WP Crontrol statuses by default.
		$wp_query->set( 'post_status', array(
			self::$status_no_action,
			self::$status_running,
			self::$status_complete,
			self::$status_warning,
			self::$status_error,
		) );
	}

	function filter_layout_columns( array $columns ) {
		$columns[ self::$post_type ] = 1;

		return $columns;
	}

	function filter_layout_option() {
		return 1;
	}

	function remove_publish_meta_box() {
		remove_meta_box( 'submitdiv', self::$post_type, 'side' );
	}

	function action_restrict_manage_posts( $post_type, $which ) {
		global $wp_query;

		if ( self::$post_type !== $post_type ) {
			return;
		}

		$tax_obj = get_taxonomy( self::$taxonomy_hook );

		$options[] = sprintf(
			'<option>%s</option>',
			esc_html( $tax_obj->labels->all_items )
		);

		$terms = get_terms( array(
			'taxonomy'   => self::$taxonomy_hook,
			'hide_empty' => false,
		) );

		if ( empty( $terms ) ) {
			return;
		}

		foreach ( $terms as $term ) {
			$options[] = sprintf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $term->slug ),
				selected( $term->name, $wp_query->get( self::$taxonomy_hook ), false ),
				esc_html( $term->name )
			);
		}

		// Can't use wp_dropdown_categories() here because it outputs term IDs, not slugs
		printf(
			'<select name="%1$s" class="postform">%2$s</select>',
			esc_attr( sanitize_title( self::$taxonomy_hook ) ),
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			implode( '', $options )
		);
	}

	/**
	 * Adds our custom meta box.
	 *
	 * @param string $object_type The object type (eg. the post type).
	 * @param mixed  $object      The object (eg. a WP_Post object).
	 */
	public function action_meta_boxes( $object_type, $object ) {
		add_meta_box(
			'crontrol-log-details',
			esc_html__( 'Details', 'wp-crontrol' ),
			array( $this, 'do_meta_box_details' ),
			self::$post_type,
			'normal',
			'high'
		);

		add_meta_box(
			'crontrol-log-logging',
			esc_html__( 'Extra Logging', 'wp-crontrol' ),
			array( $this, 'do_meta_box_logging' ),
			self::$post_type,
			'normal',
			'high'
		);

		add_meta_box(
			'crontrol-log-output',
			esc_html__( 'Output', 'wp-crontrol' ),
			array( $this, 'do_meta_box_output' ),
			self::$post_type,
			'normal',
			'high'
		);

		remove_meta_box(
			'slugdiv',
			self::$post_type,
			'normal'
		);
	}

	/**
	 * Displays the Details meta box on the post editing screen.
	 *
	 * @param WP_Post $post     The post object.
	 * @param array   $meta_box The meta box arguments.
	 */
	public function do_meta_box_details( WP_Post $post, array $meta_box ) {
		$status = get_post_status( $post );
		$args   = get_post_meta( $post->ID, 'crontrol_log_args', true );
		$hook   = $post->post_title;

		?>
		<dl>
			<dt><?php esc_html_e( 'Hook', 'wp-crontrol' ); ?></dt>
			<?php
			if ( 'crontrol_cron_job' === $hook ) {
				if ( ! empty( $args[1] ) ) {
					printf(
						'<dd><em>%s</em></dd>',
						esc_html( sprintf(
							/* translators: 1: The name of the PHP cron event. */
							__( 'PHP Cron (%s)', 'wp-crontrol' ),
							$args[1]
						) )
					);
				} else {
					printf(
						'<dd><em>%s</em></dd>',
						esc_html__( 'PHP Cron', 'wp-crontrol' )
					);
				}

				printf(
					'<dt>%s</dt>',
					esc_html__( 'PHP Code', 'wp-crontrol' )
				);

				if ( empty( $args[0] ) ) {
					printf(
						'<dd><em>%s</em></dd>',
						esc_html__( 'None', 'wp-crontrol' )
					);
				} else {
					printf(
						'<dd><pre><code>%s</code></pre></dd>',
						esc_html( $args[0] )
					);
				}

			} else {
				printf(
					'<dd>%s</dd>',
					esc_html( $hook )
				);
				printf(
					'<dt>%s</dt>',
					esc_html__( 'Arguments', 'wp-crontrol' )
				);

				if ( empty( $args ) ) {
					printf(
						'<dd><em>%s</em></dd>',
						esc_html__( 'None', 'wp-crontrol' )
					);
				} else {
					printf(
						'<dd><pre>%s</pre></dd>',
						esc_html( json_output( $args ) )
					);
				}
			}
			?>

			<dt>
				<?php
				echo esc_html( sprintf(
					/* translators: %s: GMT timezone offset */
					__( 'Started (%s)', 'wp-crontrol' ),
					get_utc_offset()
				) );
				?>
			</dt>
			<dd>
				<?php
					$date_utc   = gmdate( 'Y-m-d\TH:i:s+00:00', strtotime( $post->post_date_gmt ) );
					$date_local = get_date_from_gmt( $post->post_date_gmt, 'Y-m-d H:i:s' );

					printf(
						'%s<br>%s',
						sprintf(
							'<time datetime="%1$s">%2$s</time>',
							esc_attr( $date_utc ),
							esc_html( $date_local )
						),
						sprintf(
							/* translators: %s: Time period */
							esc_html__( '%s ago', 'wp-crontrol' ),
							esc_html( time_since( strtotime( $post->post_date_gmt ), time() ) )
						)
					);
				?>
			</dd>
			<dt><?php esc_html_e( 'Action', 'wp-crontrol' ); ?></dt>
			<dd>
				<?php
				$actions = get_post_meta( $post->ID, 'crontrol_log_actions', true );
				$hook    = '';
				$terms   = get_the_terms( $post->ID, self::$taxonomy_hook );

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
				?>
			</dd>
			<dt><?php esc_html_e( 'Status', 'wp-crontrol' ); ?></dt>
			<dd class="status-<?php echo esc_attr( $status ); ?>">
				<?php

				switch ( $status ) {
					case self::$status_no_action:
						printf(
							'<span class="dashicons dashicons-warning" aria-hidden="true"></span> %s',
							esc_html__( 'No Action', 'wp-crontrol' )
						);
						break;
					case self::$status_running:
						if ( self::has_stalled( $post ) ) {
							printf(
								'<span class="status-crontrol-stalled"><span class="dashicons dashicons-clock crontrol-rotating" aria-hidden="true"></span> %s</span>',
								esc_html__( 'Running - Stalled?', 'wp-crontrol' )
							);
						} else {
							printf(
								'<span class="dashicons dashicons-clock crontrol-rotating" aria-hidden="true"></span> %s',
								esc_html__( 'Running', 'wp-crontrol' )
							);
						}
						break;
					case self::$status_complete:
						printf(
							'<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span> %s',
							esc_html__( 'Complete', 'wp-crontrol' )
						);
						break;
					case self::$status_warning:
						printf(
							'<span class="dashicons dashicons-warning" aria-hidden="true"></span> %s',
							esc_html__( 'Warning', 'wp-crontrol' )
						);
						break;
					case self::$status_error:
						$error = get_post_meta( $post->ID, 'crontrol_log_exception', true );

						if ( empty( $error ) ) {
							$message = __( 'Error', 'wp-crontrol' );
						} elseif ( 'exception' === $error['type'] ) {
							$message = sprintf(
								/* translators: %s: Error message */
								__( 'Uncaught Exception: %s', 'wp-crontrol' ),
								$error['message']
							);
						} else {
							$message = sprintf(
								/* translators: %s: Error message */
								__( 'Fatal Error: %s', 'wp-crontrol' ),
								$error['message']
							);
						}

						printf(
							'<span class="dashicons dashicons-warning" aria-hidden="true"></span> %s',
							nl2br( esc_html( $message ) )
						);

						if ( ! empty( $error ) && ! empty( $error['file'] ) && ! empty( $error['line'] ) ) {
							$file = str_replace( ABSPATH, '', $error['file'] );
							printf(
								'<br>%s:%s',
								esc_html( $file ),
								esc_html( $error['line'] )
							);
						}

						break;
				}

				?>
			</dd>
			<dt><?php esc_html_e( 'Time (s)', 'wp-crontrol' ); ?></dt>
			<dd>
				<?php
				$time = get_post_meta( $post->ID, 'crontrol_log_time', true );

				if ( '' !== $time ) {
					echo esc_html( number_format_i18n( floatval( $time ), 4 ) );
				}
				?>
			</dd>
			<dt><?php esc_html_e( 'Database Queries', 'wp-crontrol' ); ?></dt>
			<dd>
				<?php
				$queries = get_post_meta( $post->ID, 'crontrol_log_queries', true );

				if ( ! empty( $queries ) ) {
					echo esc_html( number_format_i18n( $queries ) );
				} elseif ( '' !== $queries ) {
					printf(
						'<em>%s</em>',
						esc_html__( 'None', 'wp-crontrol' )
					);
				}
				?>
			</dd>
			<dt><?php esc_html_e( 'HTTP Requests', 'wp-crontrol' ); ?></dt>
			<dd>
				<?php
				$https = get_post_meta( $post->ID, 'crontrol_log_https', true );

				if ( ! empty( $https ) ) {
					echo '<ol>';
					foreach ( $https as $http ) {
						$class    = '';
						$dashicon = 'yes-alt';
						$end      = '';

						if ( ! empty( $http['warning'] ) ) {
							$class = 'status-crontrol-warning';
							$dashicon = 'warning';
						}

						if ( isset( $http['end'] ) ) {
							$end = number_format_i18n( $http['end'] - $http['start'], 4 );
						}

						printf(
							'<li class="%1$s">%2$s %3$s<br><span class="dashicons dashicons-%4$s" aria-hidden="true"></span> %5$s<br>%6$s</li>',
							esc_attr( $class ),
							esc_html( $http['args']['method'] ),
							esc_html( $http['url'] ),
							esc_attr( $dashicon ),
							esc_html( $http['response'] ),
							esc_html( $end )
						);
					}
					echo '</ol>';
				} else {
					printf(
						'<em>%s</em>',
						esc_html__( 'None', 'wp-crontrol' )
					);
				}
				?>
			</dd>
		</dl>
		<?php
	}

	/**
	 * Displays the Logging meta box on the post editing screen.
	 *
	 * @param WP_Post $post     The post object.
	 * @param array   $meta_box The meta box arguments.
	 */
	public function do_meta_box_logging( WP_Post $post, array $meta_box ) {
		$logging = get_post_meta( $post->ID, 'crontrol_log_logs', true );

		if ( ! empty( $logging ) ) {
			echo '<ul>';
			foreach ( $logging as $log ) {
				$class    = 'status-crontrol-' . $log['level'];
				$dashicon = 'marker';

				if ( in_array( $log['level'], Logger::get_error_levels(), true ) || in_array( $log['level'], Logger::get_warning_levels(), true ) ) {
					$dashicon = 'warning';
				}

				printf(
					'<li class="%1$s"><span class="dashicons dashicons-%2$s" aria-hidden="true"></span> %3$s: %4$s</li>',
					esc_attr( $class ),
					esc_attr( $dashicon ),
					esc_html( ucwords( $log['level'] ) ),
					esc_html( $log['message'] )
				);
			}
			echo '</ul>';
		} else {
			printf(
				'<em>%s</em>',
				esc_html__( 'None', 'wp-crontrol' )
			);
		}

	}

	/**
	 * Displays the Output meta box on the post editing screen.
	 *
	 * @param WP_Post $post     The post object.
	 * @param array   $meta_box The meta box arguments.
	 */
	public function do_meta_box_output( WP_Post $post, array $meta_box ) {
		$output = $post->post_content;

		if ( '' !== trim( $output ) ) {
			printf(
				'<pre>%s</pre>',
				esc_html( $output )
			);
		} else {
			printf(
				'<em>%s</em>',
				esc_html__( 'None', 'wp-crontrol' )
			);
		}
	}

	public function action_admin_notices() {
		global $wp_query;

		$screen = get_current_screen();

		if ( self::$post_type !== $screen->post_type ) {
			return;
		}

		$hook = $wp_query->get( self::$taxonomy_hook );

		if ( ! $hook || self::is_hook_logged( $hook ) ) {
			return;
		}

		printf(
			'<div id="cron-hook-notice" class="notice notice-info"><p>%s</p></div>',
			sprintf(
				/* translators: %s: The event name */
				esc_html__( 'The %s event is not currently being logged.', 'wp-crontrol' ),
				'<strong>' . esc_html( $hook ) . '</strong>'
			)
		);
	}

	/**
	 * Sets up the hooks needed to log cront events as they run.
	 */
	public function setup_hooks() {
		$logged = array_filter( self::get_logged_hooks() );

		if ( empty( $logged ) ) {
			return;
		}

		array_map( array( $this, 'observe' ), array_keys( $logged ) );
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
	 * The Cron Control runner catches fatal errors and uncaught exceptions itself, so this logs them in the Crontrol log.
	 *
	 * @param object              $event The Cron Control event.
	 * @param Throwable|Exception $error The exception or error.
	 */
	public function action_cron_control_error( $event, $error ) {
		do_action( 'crontrol/error', $error );
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
	 * Adjusts the post row actions.
	 *
	 * @param string[] $actions Array of post actions.
	 * @param WP_Post  $post    The current post object.
	 * @return string[] Array of updated post actions.
	 */
	public function remove_actions( array $actions, WP_Post $post ) {
		if ( self::$post_type !== $post->post_type ) {
			return $actions;
		}

		// Remove quick edit:
		unset( $actions['inline'], $actions['inline hide-if-no-js'] );

		if ( isset( $actions['edit'] ) ) {
			// Rename edit:
			$actions['edit'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( get_edit_post_link( $post ) ),
				esc_html__( 'Details', 'wp-crontrol' )
			);
		}

		return $actions;
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
	 * Filters the post classes to add a class for the log status.
	 *
	 * @param string[] $classes An array of post class names.
	 * @param string[] $class   An array of additional class names added to the post.
	 * @param int      $post_id The post ID.
	 * @return string[] The updated class names.
	 */
	public function filter_post_class( array $classes, $class, $post_id ) {
		if ( get_post_type( $post_id ) !== self::$post_type ) {
			return $classes;
		}

		$post   = get_post( $post_id );
		$status = get_post_status( $post->ID );

		$classes[] = $status;

		if ( self::has_stalled( $post ) ) {
			$classes[] = self::$status_stalled;
		}

		return $classes;
	}

	/**
	 * Determines whether a running job has stalled.
	 *
	 * A job which has been running for longer than thirty minutes is considered stalled.
	 *
	 * @param WP_Post $log The event log.
	 * @return bool Whether the event has stalled.
	 */
	public static function has_stalled( WP_Post $log ) {
		if ( get_post_status( $log ) !== self::$status_running ) {
			return false;
		}

		$log_date_utc = strtotime( $log->post_date_gmt );
		$now_date_utc = time();

		if ( ( $now_date_utc - $log_date_utc ) > ( HOUR_IN_SECONDS / 2 ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Filter the arguments used in an HTTP request.
	 *
	 * Used to log the request, and to add the logging key to the arguments array.
	 *
	 * @param  array  $args HTTP request arguments.
	 * @param  string $url  The request URL.
	 * @return array        HTTP request arguments.
	 */
	public function filter_http_request_args( array $args, $url ) {
		$start = microtime( true );
		$key   = microtime( true ) . $url;

		$this->data['https'][ $key ] = array(
			'url'      => $url,
			'args'     => $args,
			'start'    => $start,
			'response' => new WP_Error( 'crontrol_who_knows', __( 'Request failed', 'wp-crontrol' ) ),
		);
		$args['_crontrol_key'] = $key;

		return $args;
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

		$this->data['https'][ $args['_crontrol_key'] ]['end']      = microtime( true );
		$this->data['https'][ $args['_crontrol_key'] ]['response'] = $response;
	}

	/**
	 * Updates the list of columns on the log post type listing screen.
	 *
	 * @param string[] $columns Array of column headings keyed by column name.
	 * @return string[] Updated array of columns.
	 */
	public function columns( array $columns ) {
		$ran = sprintf(
			/* translators: %s: GMT timezone offset */
			__( 'Started (%s)', 'wp-crontrol' ),
			get_utc_offset()
		);

		$columns = array();

		$columns['cb']      = '<input type="checkbox" />';
		$columns['hook']    = esc_html__( 'Hook', 'wp-crontrol' );
		$columns['args']    = esc_html__( 'Arguments', 'wp-crontrol' );
		$columns['ran']     = esc_html( $ran );
		$columns['actions'] = esc_html__( 'Action', 'wp-crontrol' );
		$columns['status']  = esc_html__( 'Status', 'wp-crontrol' );
		$columns['time']    = esc_html__( 'Time (s)', 'wp-crontrol' );
		$columns['queries'] = esc_html__( 'Database Queries', 'wp-crontrol' );
		$columns['https']   = esc_html__( 'HTTP Requests', 'wp-crontrol' );

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
		$args = get_post_meta( $post->ID, 'crontrol_log_args', true );
		$hook = $post->post_title;

		switch ( $name ) {

			case 'hook':
				if ( 'crontrol_cron_job' === $hook ) {
					if ( ! empty( $args[1] ) ) {
						printf(
							'<em>%s</em>',
							esc_html( sprintf(
								/* translators: 1: The name of the PHP cron event. */
								__( 'PHP Cron (%s)', 'wp-crontrol' ),
								$args[1]
							) )
						);
					} else {
						printf(
							'<em>%s</em>',
							esc_html__( 'PHP Cron', 'wp-crontrol' )
						);
					}
				} else {
					echo esc_html( $hook );
				}
				break;

			case 'ran':
				$date_utc   = gmdate( 'Y-m-d\TH:i:s+00:00', strtotime( $post->post_date_gmt ) );
				$date_local = get_date_from_gmt( $post->post_date_gmt, 'Y-m-d H:i:s' );

				printf(
					'%s<br>%s',
					sprintf(
						'<time datetime="%1$s">%2$s</time>',
						esc_attr( $date_utc ),
						esc_html( $date_local )
					),
					sprintf(
						/* translators: %s: Time period */
						esc_html__( '%s ago', 'wp-crontrol' ),
						esc_html( time_since( strtotime( $post->post_date_gmt ), time() ) )
					)
				);
				break;

			case 'time':
				$time = get_post_meta( $post->ID, 'crontrol_log_time', true );

				if ( '' !== $time ) {
					echo esc_html( number_format_i18n( floatval( $time ), 4 ) );
				}
				break;

			case 'args':
				if ( 'crontrol_cron_job' === $hook ) {
					printf(
						'<em>%s</em>',
						esc_html__( 'PHP Code', 'wp-crontrol' )
					);

					if ( ! empty( $args[0] ) ) {
						$lines = explode( "\n", trim( $args[0] ) );
						$code  = reset( $lines );
						$code  = substr( $code, 0, 50 );

						printf(
							'<br><code>%s</code>&hellip;',
							esc_html( $code )
						);
					}
				} elseif ( empty( $args ) ) {
					printf(
						'<em>%s</em>',
						esc_html__( 'None', 'wp-crontrol' )
					);
				} else {
					printf(
						'<pre>%s</pre>',
						esc_html( json_output( $args ) )
					);
				}
				break;

			case 'status':
				$status = get_post_status( $post );

				switch ( $status ) {
					case self::$status_no_action:
						printf(
							'<span class="dashicons dashicons-warning" aria-hidden="true"></span> %s',
							esc_html__( 'No Action', 'wp-crontrol' )
						);
						break;
					case self::$status_running:
						if ( self::has_stalled( $post ) ) {
							printf(
								'<span class="dashicons dashicons-clock crontrol-rotating" aria-hidden="true"></span> %s',
								esc_html__( 'Running - Stalled?', 'wp-crontrol' )
							);
						} else {
							printf(
								'<span class="dashicons dashicons-clock crontrol-rotating" aria-hidden="true"></span> %s',
								esc_html__( 'Running', 'wp-crontrol' )
							);
						}
						break;
					case self::$status_complete:
						printf(
							'<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span> %s',
							esc_html__( 'Complete', 'wp-crontrol' )
						);
						break;
					case self::$status_warning:
						printf(
							'<span class="dashicons dashicons-warning" aria-hidden="true"></span> %s',
							esc_html__( 'Warning', 'wp-crontrol' )
						);
						break;
					case self::$status_error:
						$error = get_post_meta( $post->ID, 'crontrol_log_exception', true );

						if ( empty( $error ) ) {
							$message = __( 'Error', 'wp-crontrol' );
						} elseif ( 'exception' === $error['type'] ) {
							$message = __( 'Uncaught Exception', 'wp-crontrol' );
						} else {
							$message = __( 'Fatal Error', 'wp-crontrol' );
						}

						printf(
							'<span class="dashicons dashicons-warning" aria-hidden="true"></span> %s',
							esc_html( $message )
						);
						break;
					case 'trash':
						esc_html_e( 'Trash', 'wp-crontrol' );
						break;
				}

				break;

			case 'actions':
				$actions = get_post_meta( $post->ID, 'crontrol_log_actions', true );
				$hook    = $post->post_title;

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
				$queries = get_post_meta( $post->ID, 'crontrol_log_queries', true );

				if ( '' !== $queries ) {
					echo esc_html( number_format_i18n( $queries ) );
				}
				break;

			case 'https':
				$https = get_post_meta( $post->ID, 'crontrol_log_https', true );

				if ( '' !== $https ) {
					echo esc_html( number_format_i18n( count( $https ) ) );
				}
				break;

		}
	}

	/**
	 * Exception handler.
	 *
	 * In PHP >= 7 this will catch Throwable objects, which are fatals and uncaught exceptions.
	 * In PHP < 7 it will catch Exception objects, which are uncaight exceptions.
	 * This doesn't catch fatal errors in PHP < 7.
	 *
	 * @param Throwable|Exception $e The error or exception.
	 * @throws Exception Re-thrown when necessary.
	 */
	public function exception_handler( $e ) {
		$this->data['exception'] = array(
			'message' => $e->getMessage(),
			'file'    => $e->getFile(),
			'line'    => $e->getLine(),
			'code'    => $e->getCode(),
			'type'    => is_a( $e, 'Exception' ) ? 'exception' : 'throwable',
		);

		$this->log_end();

		if ( $this->old_exception_handler ) {
			call_user_func( $this->old_exception_handler, $e );
		} else {
			throw new Exception( $e->getMessage(), $e->getCode(), $e );
		}
	}

	public function shutdown_handler() {
		if ( ! empty( $this->data['end_time'] ) ) {
			return;
		}

		$e = error_get_last();

		$fatals = ( E_ERROR | E_PARSE | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR );

		if ( $e && ( $e['type'] & $fatals ) ) {
			$this->data['exception'] = array(
				'message' => $e['message'],
				'file'    => $e['file'],
				'line'    => $e['line'],
				'code'    => $e['type'],
				'type'    => 'error',
			);
		}

		$this->log_end();
	}

	/**
	 * Starts the logging for the current cron event.
	 */
	public function log_start() {
		global $wpdb;

		$this->data = array();

		$this->old_exception_handler = set_exception_handler( array( $this, 'exception_handler' ) );

		register_shutdown_function( array( $this, 'shutdown_handler' ) );

		$this->data['actions'] = array();
		$this->data['args']    = func_get_args();
		$this->data['hook']    = current_filter();
		$this->data['https']   = array();

		foreach ( get_hook_callbacks( $this->data['hook'] ) as $action ) {
			$this->data['actions'][] = $action['callback']['name'];
		}

		$metas = array(
			'crontrol_log_actions' => $this->data['actions'],
			'crontrol_log_args'    => $this->data['args'],
		);

		$post_id = wp_insert_post( wp_slash( array(
			'post_type'    => self::$post_type,
			'post_title'   => $this->data['hook'],
			'post_status'  => self::$status_running,
			'post_name'    => uniqid(),
		) ), true );

		if ( is_wp_error( $post_id ) ) {
			$message = sprintf(
				/* translators: 1: Hook name, 2: Error message */
				__( 'Crontrol event log for %1$s: %2$s', 'wp-crontrol' ),
				$this->data['hook'],
				$post_id->get_error_message()
			);
			trigger_error( esc_html( $message ), E_USER_WARNING );
			return;
		}

		$this->data['log_id'] = $post_id;

		foreach ( $metas as $meta_key => $meta_value ) {
			add_post_meta( $post_id, $meta_key, wp_slash( $meta_value ), true );
		}

		wp_set_post_terms( $post_id, array( $this->data['hook'] ), self::$taxonomy_hook, true );

		add_filter( 'http_request_args', array( $this, 'filter_http_request_args' ), 9999, 2 );
		add_action( 'http_api_debug', array( $this, 'action_http_api_debug' ), 9999, 5 );

		$this->logger = new Logger();
		$this->logger->init();

		$this->data['start_time']    = microtime( true );
		$this->data['start_queries'] = $wpdb->num_queries;

		if ( defined( 'CAVALCADE_JOB_ID' ) ) {
			$message = sprintf(
				/* translators: %s: Job ID */
				__( 'Cavalcade job ID: %s', 'wp-crontrol' ),
				'{id}'
			);
			do_action( 'crontrol/debug', $message, array(
				'id' => constant( 'CAVALCADE_JOB_ID' ),
			) );
		}

		$this->buffered = ob_start();
	}

	/**
	 * Ends the logging for the current cron event.
	 */
	public function log_end() {
		global $wpdb;

		$output = '';

		if ( $this->buffered ) {
			$output = ob_get_flush();
		}

		$this->data['end_time']    = microtime( true );
		$this->data['end_queries'] = $wpdb->num_queries;
		$this->data['num_queries'] = ( $this->data['end_queries'] - $this->data['start_queries'] );

		remove_action( 'http_api_debug', array( $this, 'action_http_api_debug' ), 9999 );

		set_exception_handler( $this->old_exception_handler );

		if ( empty( $this->data['log_id'] ) ) {
			$message = sprintf(
				/* translators: 1: Hook name, 2: Error message */
				__( 'Crontrol event log for %1$s: %2$s', 'wp-crontrol' ),
				$this->data['hook'],
				__( 'No event log ID present', 'wp-crontrol' )
			);
			trigger_error( esc_html( $message ), E_USER_WARNING );
		}

		$status = self::$status_complete;

		if ( empty( $this->data['actions'] ) ) {
			$status = self::$status_no_action;
		}

		foreach ( $this->data['https'] as $i => $http ) {
			$warning = false;

			if ( is_wp_error( $http['response'] ) ) {
				$response = $http['response']->get_error_message();
				$status   = self::$status_warning;
				$warning  = true;
			} elseif ( ! $http['args']['blocking'] ) {
				/* translators: A non-blocking HTTP API request */
				$response = __( 'Non-blocking', 'wp-crontrol' );
			} else {
				$code = intval( wp_remote_retrieve_response_code( $http['response'] ) );
				$msg  = wp_remote_retrieve_response_message( $http['response'] );

				$response = $code . ' ' . $msg;

				if ( $code >= 400 ) {
					$status  = self::$status_warning;
					$warning = true;
				}
			}

			$this->data['https'][ $i ]['response'] = $response;
			$this->data['https'][ $i ]['warning']  = $warning;
		}

		$metas = array(
			'crontrol_log_time'    => ( $this->data['end_time'] - $this->data['start_time'] ),
			'crontrol_log_queries' => $this->data['num_queries'],
			'crontrol_log_https'   => $this->data['https'],
		);

		$logs = $this->logger->get_logs();

		if ( ! empty( $logs ) ) {
			$metas['crontrol_log_logs'] = $logs;
		}

		if ( $this->logger->has_warning() ) {
			$status = self::$status_warning;
		}

		if ( $this->logger->has_error() ) {
			$status = self::$status_error;
		}

		if ( ! empty( $this->data['exception'] ) ) {
			$metas['crontrol_log_exception'] = $this->data['exception'];
			$status = self::$status_error;
		}

		$post_id = wp_update_post( wp_slash( array(
			'ID'          => $this->data['log_id'],
			'post_status' => $status,
			'post_content' => $output,
		) ), true );

		if ( is_wp_error( $post_id ) ) {
			$message = sprintf(
				/* translators: 1: Hook name, 2: Error message */
				__( 'Crontrol event log for %1$s: %2$s', 'wp-crontrol' ),
				$this->data['hook'],
				$post_id->get_error_message()
			);
			trigger_error( esc_html( $message ), E_USER_WARNING );
			return;
		}

		foreach ( $metas as $meta_key => $meta_value ) {
			add_post_meta( $post_id, $meta_key, wp_slash( $meta_value ), true );
		}
	}

	/**
	 * Returns an array of all known cron event hooks. Hooks that are logged have a value of true, all others false.
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

		// Ensure `crontrol_cron_job` is always present in the list of available events:
		if ( ! isset( $all['crontrol_cron_job'] ) ) {
			$all['crontrol_cron_job'] = false;
		}

		ksort( $all );

		return $all;
	}

	public static function is_hook_logged( $hook ) {
		$logged = array_filter( self::get_logged_hooks() );

		return isset( $logged[ $hook ] );
	}

	public static function set_logging_for_hook( $hook, $log = true ) {
		$logged = get_option( 'crontrol_log', array() );

		if ( $log ) {
			$logged[] = $hook;
		} else {
			$i = array_search( $hook, $logged, true );
			if ( false !== $i ) {
				unset( $logged[ $i ] );
			}
		}

		update_option( 'crontrol_log', array_unique( $logged ) );
	}

	/**
	 * Shows the event log related options panel.
	 */
	public static function show_options() {
		$all = self::get_logged_hooks();
		?>
		<form action="options.php" method="POST" class="crontrol-log-form">
			<fieldset>
				<legend><?php esc_html_e( 'Enable Logging For:', 'wp-crontrol' ); ?></legend>
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
