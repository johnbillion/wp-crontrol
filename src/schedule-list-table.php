<?php
/**
 * List table for cron schedules.
 */

namespace Crontrol;

require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

/**
 * Cron schedule list table class.
 */
class Schedule_List_Table extends \WP_List_Table {

	/**
	 * Array of cron event schedules that are added by WordPress core.
	 *
	 * @var array<int,string> Array of schedule names.
	 */
	protected static $core_schedules;

	/**
	 * Array of cron event schedule names that are in use by events.
	 *
	 * @var array<int,string> Array of schedule names.
	 */
	protected static $used_schedules;

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct( array(
			'singular' => 'crontrol-schedule',
			'plural'   => 'crontrol-schedules',
			'ajax'     => false,
			'screen'   => 'crontrol-schedules',
		) );
	}

	/**
	 * Gets the name of the primary column.
	 *
	 * @return string The name of the primary column.
	 */
	protected function get_primary_column_name() {
		return 'crontrol_name';
	}

	/**
	 * Prepares the list table items and arguments.
	 *
	 * @return void
	 */
	public function prepare_items() {
		$schedules = Schedule\get();
		$count     = count( $schedules );

		self::$core_schedules = get_core_schedules();
		self::$used_schedules = array_unique( wp_list_pluck( Event\get(), 'schedule' ) );

		$this->items = $schedules;

		$this->set_pagination_args( array(
			'total_items' => $count,
			'per_page'    => -1,
			'total_pages' => 1,
		) );
	}

	/**
	 * Returns an array of column names for the table.
	 *
	 * @return array<string,string> Array of column names keyed by their ID.
	 */
	public function get_columns() {
		return array(
			'crontrol_icon'     => '',
			'crontrol_name'     => __( 'Internal Name', 'wp-crontrol' ),
			'crontrol_interval' => __( 'Interval', 'wp-crontrol' ),
			'crontrol_display'  => __( 'Display Name', 'wp-crontrol' ),
		);
	}

	/**
	 * Returns an array of CSS class names for the table.
	 *
	 * @return array<int,string> Array of class names.
	 */
	protected function get_table_classes() {
		return array( 'widefat', 'fixed', 'striped', 'table-view-list', $this->_args['plural'] );
	}

	/**
	 * Generates and displays row action links for the table.
	 *
	 * @phpstan-param array{
	 *   interval: int,
	 *   display?: string,
	 *   name: string,
	 *   is_too_frequent: bool,
	 * } $schedule
	 * @param mixed[] $schedule    The schedule for the current row.
	 * @param string  $column_name Current column name.
	 * @param string  $primary     Primary column name.
	 * @return string The row actions HTML.
	 */
	protected function handle_row_actions( $schedule, $column_name, $primary ) {
		if ( $primary !== $column_name ) {
			return '';
		}

		$links = array();
		/** @var array<string,int|string> */
		$new_scheds = get_option( 'crontrol_schedules', array() );

		if ( in_array( $schedule['name'], self::$core_schedules, true ) ) {
			$links[] = "<span class='crontrol-in-use'>" . esc_html__( 'This is a WordPress core schedule and cannot be deleted', 'wp-crontrol' ) . '</span>';
		} elseif ( ! isset( $new_scheds[ $schedule['name'] ] ) ) {
			$links[] = "<span class='crontrol-in-use'>" . esc_html__( 'This schedule is added by another plugin and cannot be deleted', 'wp-crontrol' ) . '</span>';
		} elseif ( in_array( $schedule['name'], self::$used_schedules, true ) ) {
			$links[] = "<span class='crontrol-in-use'>" . esc_html__( 'This custom schedule is in use and cannot be deleted', 'wp-crontrol' ) . '</span>';
		} else {
			$link = add_query_arg( array(
				'page'            => 'crontrol_admin_options_page',
				'crontrol_action' => 'delete-schedule',
				'crontrol_id'     => rawurlencode( $schedule['name'] ),
			), admin_url( 'options-general.php' ) );
			$link = wp_nonce_url( $link, 'crontrol-delete-schedule_' . $schedule['name'] );

			$links[] = "<span class='delete'><a href='" . esc_url( $link ) . "'>" . esc_html__( 'Delete', 'wp-crontrol' ) . '</a></span>';
		}

		return $this->row_actions( $links );
	}

	/**
	 * Returns the output for the icon cell of a table row.
	 *
	 * @phpstan-param array{
	 *   interval: int,
	 *   display?: string,
	 *   name: string,
	 *   is_too_frequent: bool,
	 * } $schedule
	 * @param mixed[] $schedule The schedule for the current row.
	 * @return string The cell output.
	 */
	protected function column_crontrol_icon( array $schedule ) {
		if ( in_array( $schedule['name'], self::$core_schedules, true ) ) {
			return sprintf(
				'<span class="dashicons dashicons-wordpress" aria-hidden="true"></span>
				<span class="screen-reader-text">%s</span>',
				esc_html__( 'This is a WordPress core schedule and cannot be deleted', 'wp-crontrol' )
			);
		}

		return '';
	}

	/**
	 * Returns the output for the schdule name cell of a table row.
	 *
	 * @phpstan-param array{
	 *   interval: int,
	 *   display?: string,
	 *   name: string,
	 *   is_too_frequent: bool,
	 * } $schedule
	 * @param mixed[] $schedule The schedule for the current row.
	 * @return string The cell output.
	 */
	protected function column_crontrol_name( array $schedule ) {
		return esc_html( $schedule['name'] );
	}

	/**
	 * Returns the output for the interval cell of a table row.
	 *
	 * @phpstan-param array{
	 *   interval: int,
	 *   display?: string,
	 *   name: string,
	 *   is_too_frequent: bool,
	 * } $schedule
	 * @param mixed[] $schedule The schedule for the current row.
	 * @return string The cell output.
	 */
	protected function column_crontrol_interval( array $schedule ) {
		$interval = sprintf(
			'%s (%s)',
			esc_html( "{$schedule['interval']}" ),
			esc_html( interval( $schedule['interval'] ) )
		);

		if ( $schedule['is_too_frequent'] ) {
			$interval .= sprintf(
				'<span class="status-crontrol-warning"><br><span class="dashicons dashicons-warning" aria-hidden="true"></span> %s</span>',
				sprintf(
					/* translators: 1: The name of the configuration constant, 2: The value of the configuration constant */
					esc_html__( 'This interval is less than the %1$s constant which is set to %2$s seconds. Events that use it may not run on time.', 'wp-crontrol' ),
					'<code>WP_CRON_LOCK_TIMEOUT</code>',
					intval( WP_CRON_LOCK_TIMEOUT )
				)
			);
		}

		return $interval;
	}

	/**
	 * Returns the output for the display name cell of a table row.
	 *
	 * @param mixed[] $schedule The schedule for the current row.
	 *
	 * @phpstan-param array{
	 *   interval: int,
	 *   display?: string,
	 *   name: string,
	 *   is_too_frequent: bool,
	 * } $schedule
	 * @return string The cell output.
	 */
	protected function column_crontrol_display( array $schedule ) {
		return esc_html( isset( $schedule['display'] ) ? $schedule['display'] : $schedule['name'] );
	}

	/**
	 * Outputs a message when there are no items to show in the table.
	 *
	 * @return void
	 */
	public function no_items() {
		esc_html_e( 'There are no schedules.', 'wp-crontrol' );
	}

}
