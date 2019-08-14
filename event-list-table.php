<?php

namespace Crontrol;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Event_List_Table extends \WP_List_Table {

	public function __construct() {
		parent::__construct( [
			'singular' => 'crontrol-event',
			'plural'   => 'crontrol-events',
			'ajax'     => false,
			'screen'   => 'crontrol-events',
		] );
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

	public function no_items() {
		esc_html_e( 'There are currently no scheduled cron events.', 'wp-crontrol' );
	}

}
