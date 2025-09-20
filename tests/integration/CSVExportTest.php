<?php declare(strict_types = 1);

namespace Crontrol\Tests;

use Crontrol;

class CSVExportTest extends Test {
	/**
	 * Export events to CSV and return all rows
	 *
	 * @param string $type Event type to export
	 * @return array Array of CSV rows
	 */
	private function exportEventsToArray( string $type = 'all' ): array {
		$stream = fopen( 'php://memory', 'w+' );

		if ( ! is_resource( $stream ) ) {
			self::fail( 'Failed to open memory stream for CSV export' );
		}

		Crontrol\export_events_csv( $type, $stream );
		rewind( $stream );

		$rows = array();
		while ( ( $row = fgetcsv( $stream ) ) !== false ) {
			$rows[] = $row;
		}

		fclose( $stream );
		return $rows;
	}

	/**
	 * Export events to CSV and return only the header row
	 *
	 * @param string $type Event type to export
	 * @return array Header row
	 */
	private function exportEventsHeaders( string $type = 'all' ): array {
		$rows = $this->exportEventsToArray( $type );
		return $rows[0];
	}

	/**
	 * Export events to CSV and return only the data rows (excluding headers)
	 *
	 * @param string $type Event type to export
	 * @return array Data rows without headers
	 */
	private function exportEventsDataRows( string $type = 'all' ): array {
		$rows = $this->exportEventsToArray( $type );
		return array_slice( $rows, 1 );
	}

	/**
	 * Find a specific event row by hook name
	 *
	 * @param array $rows CSV rows to search
	 * @param string $hook Hook name to find
	 * @return array|null The matching row or null if not found
	 */
	private function findEventRow( array $rows, string $hook ): ?array {
		foreach ( $rows as $row ) {
			if ( $row[0] === $hook ) {
				return $row;
			}
		}

		return null;
	}

	/**
	 * Get and assert an event row exists
	 *
	 * @param string $hook Hook name to find
	 * @param string $type Event type to export
	 * @return array The event row
	 */
	private function getEventRow( string $hook, string $type = 'all' ): array {
		$data_rows = $this->exportEventsDataRows( $type );
		$event_row = $this->findEventRow( $data_rows, $hook );

		if ( $event_row === null ) {
			self::fail( "Event with hook '$hook' not found in CSV export" );
		}

		return $event_row;
	}

	/**
	 * Test that CSV export produces correct headers
	 */
	public function testCSVExportHeaders(): void {
		$headers = $this->exportEventsHeaders( 'all' );

		$expected_headers = array(
			'hook',
			'arguments',
			'next_run',
			'next_run_gmt',
			'action',
			'schedule',
			'interval',
		);

		self::assertSame( $expected_headers, $headers );
	}

	/**
	 * Test that CSV export includes scheduled events
	 */
	public function testCSVExportIncludesScheduledEvents(): void {
		// Schedule a test event
		$timestamp = time() + 123;
		$hook = 'test_csv_export_hook';
		$args = array( 'test_arg' => 'test_value' );

		wp_schedule_single_event( $timestamp, $hook, array( $args ) );

		$test_event_row = $this->getEventRow( $hook, 'all' );

		// Arguments
		self::assertSame( '[{"test_arg":"test_value"}]', $test_event_row[1] );
		// Schedule
		self::assertSame( 'Non-repeating', $test_event_row[5] );
		// Interval
		self::assertSame( '0', $test_event_row[6] );
	}

	/**
	 * Test that CSV export includes recurring events
	 */
	public function testCSVExportIncludesRecurringEvents(): void {
		// Schedule a recurring test event
		$timestamp = time() + 123;
		$hook = 'test_csv_export_recurring_hook';
		$args = array();
		$recurrence = 'hourly';

		wp_schedule_event( $timestamp, $recurrence, $hook, $args );

		$test_event_row = $this->getEventRow( $hook, 'all' );

		// Arguments
		self::assertSame( '', $test_event_row[1] );
		// Schedule
		self::assertSame( 'Once Hourly', $test_event_row[5] );
		// Interval
		self::assertSame( '3600', $test_event_row[6] );
	}

	/**
	 * Test that CSV export handles PHP cron jobs correctly
	 */
	public function testCSVExportHandlesPHPCronJobs(): void {
		// Schedule a PHP cron job
		$timestamp = time() + 123;
		$hook = 'crontrol_cron_job';
		$php = 'echo "test";';
		$args = array(
			array(
				'code' => $php,
				'name' => 'Test PHP Job',
				'hash' => wp_hash( $php ),
			),
		);

		wp_schedule_single_event( $timestamp, $hook, $args );

		$php_job_row = $this->getEventRow( $hook, 'all' );

		// Arguments
		self::assertSame( 'PHP Code', $php_job_row[1] );
		// Action
		self::assertSame( 'WP Crontrol', $php_job_row[4] );
	}

	/**
	 * Test that CSV export handles events with no arguments
	 */
	public function testCSVExportHandlesEventsWithNoArguments(): void {
		// Schedule an event with no arguments
		$timestamp = time() + 123;
		$hook = 'test_csv_no_args_hook';

		wp_schedule_single_event( $timestamp, $hook );

		$test_event_row = $this->getEventRow( $hook, 'all' );

		// Arguments
		self::assertSame( '', $test_event_row[1] );
	}

	/**
	 * Test that CSV export handles invalid schedule names
	 */
	public function testCSVExportHandlesInvalidScheduleNames(): void {
		// Create an event with a custom/invalid schedule
		$timestamp = time() + 123;
		$hook = 'test_csv_invalid_schedule';
		$key = md5( serialize( array() ) );

		// Manually add an event with an invalid schedule using the proper structure
		$crons = _get_cron_array();
		$crons[ $timestamp ][ $hook ][ $key ] = array(
			'schedule' => 'non_existent_schedule',
			'args' => array(),
			'interval' => 9999,
		);
		_set_cron_array( $crons );

		$test_event_row = $this->getEventRow( $hook, 'all' );

		// Schedule name should show the error message for invalid schedule
		self::assertSame( 'Unknown (non_existent_schedule)', $test_event_row[5] );
		// Interval should still be correct
		self::assertSame( '9999', $test_event_row[6] );
	}
}
