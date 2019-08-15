<?php

namespace Crontrol;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Event_List_Table extends \WP_List_Table {

	protected static $core_hooks;
	protected static $can_edit_files;

	public function __construct() {
		parent::__construct( [
			'singular' => 'crontrol-event',
			'plural'   => 'crontrol-events',
			'ajax'     => false,
			'screen'   => 'crontrol-events',
		] );

		self::$core_hooks     = get_core_hooks();
		self::$can_edit_files = current_user_can( 'edit_files' );
	}

	public function prepare_items() {
		$events   = get_cron_events();
		$count    = count( $events );
		$per_page = 50;
		$offset   = ( $this->get_pagenum() - 1 ) * $per_page;

		$this->items = array_slice( $events, $offset, $per_page );

		$this->set_pagination_args( array(
			'total_items' => $count,
			'per_page'    => $per_page,
			'total_pages' => ceil( $count / $per_page ),
		) );
	}

	public function get_columns() {
		return array(
			'cb'         => '<input type="checkbox" />',
			'hook'       => __( 'Hook Name', 'wp-crontrol' ),
			'args'       => __( 'Arguments', 'wp-crontrol' ),
			'actions'    => __( 'Actions', 'wp-crontrol' ),
			'next'       => __( 'Next Run', 'wp-crontrol' ),
			'recurrance' => __( 'Recurrence', 'wp-crontrol' ),
		);
	}

	/**
	 * Generates and display row actions links for the list table.
	 *
	 * @param object $event        The event being acted upon.
	 * @param string $column_name Current column name.
	 * @param string $primary     Primary column name.
	 * @return string The row actions HTML.
	 */
	protected function handle_row_actions( $event, $column_name, $primary ) {
		if ( $primary !== $column_name ) {
			return '';
		}

		$links = array();

		if ( ( 'crontrol_cron_job' !== $event->hook ) || $can_edit_files ) {
			$link = array(
				'page'     => 'crontrol_admin_manage_page',
				'action'   => 'edit-cron',
				'id'       => rawurlencode( $event->hook ),
				'sig'      => rawurlencode( $event->sig ),
				'next_run' => rawurlencode( $event->time ),
			);
			$link = add_query_arg( $link, admin_url( 'tools.php' ) ) . '#crontrol_form';
			$links[] = "<a href='" . esc_url( $link ) . "'>" . esc_html__( 'Edit', 'wp-crontrol' ) . '</a>';
		}

		$link = array(
			'page'     => 'crontrol_admin_manage_page',
			'action'   => 'run-cron',
			'id'       => rawurlencode( $event->hook ),
			'sig'      => rawurlencode( $event->sig ),
			'next_run' => rawurlencode( $event->time ),
		);
		$link = add_query_arg( $link, admin_url( 'tools.php' ) );
		$link = wp_nonce_url( $link, "run-cron_{$event->hook}_{$event->sig}" );
		$links[] = "<a href='" . esc_url( $link ) . "'>" . esc_html__( 'Run Now', 'wp-crontrol' ) . '</a>';

		if ( ! in_array( $event->hook, self::$core_hooks, true ) && ( ( 'crontrol_cron_job' !== $event->hook ) || self::$can_edit_files ) ) {
			$link = array(
				'page'     => 'crontrol_admin_manage_page',
				'action'   => 'delete-cron',
				'id'       => rawurlencode( $event->hook ),
				'sig'      => rawurlencode( $event->sig ),
				'next_run' => rawurlencode( $event->time ),
			);
			$link = add_query_arg( $link, admin_url( 'tools.php' ) );
			$link = wp_nonce_url( $link, "delete-cron_{$event->hook}_{$event->sig}_{$event->time}" );
			$links[] = "<span class='delete'><a href='" . esc_url( $link ) . "'>" . esc_html__( 'Delete', 'wp-crontrol' ) . '</a></span>';
		}

		return $this->row_actions( $links );
	}

	protected function column_hook( $event ) {
		if ( 'crontrol_cron_job' === $event->hook ) {
			if ( ! empty( $event->args['name'] ) ) {
				/* translators: 1: The name of the PHP cron event. */
				return '<em>' . esc_html( sprintf( __( 'PHP Cron (%s)', 'wp-crontrol' ), $event->args['name'] ) ) . '</em>';
			} else {
				return '<em>' . esc_html__( 'PHP Cron', 'wp-crontrol' ) . '</em>';
			}
		} else {
			return esc_html( $event->hook );
		}
	}

	public function no_items() {
		esc_html_e( 'There are currently no scheduled cron events.', 'wp-crontrol' );
	}

}
