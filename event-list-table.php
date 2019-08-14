<?php

namespace Crontrol;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Event_List_Table extends \WP_List_Table {

	public function prepare_items() {
		$this->items = get_cron_events();

		$count    = count( $this->items );
		$per_page = 50;

		$this->set_pagination_args( array(
			'total_items' => $count,
			'per_page'    => $per_page,
			'total_pages' => ceil( $count / $per_page ),
		) );
	}

	public function get_columns() {
		return array();
	}

}
