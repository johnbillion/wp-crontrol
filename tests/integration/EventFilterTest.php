<?php declare(strict_types = 1);

namespace Crontrol\Tests;

use Crontrol;
use Crontrol\Event\Table;

class EventFilterTest extends Test {
	/**
	 * Test that get_filtered_events correctly filters 'all' event type
	 */
	public function testGetFilteredEventsFiltersAllEvents(): void {
		// Schedule a test event
		$timestamp = time() + 3600;
		$hook = 'test_filter_all_events_hook';
		wp_schedule_single_event( $timestamp, $hook );

		$all_events = Crontrol\Event\get();
		$filtered = Table::get_filtered_events( $all_events );

		// Should include our test event and any existing core events
		self::assertGreaterThan( 0, count( $filtered['all'] ) );

		// Verify our test event is included
		$hook_names = array_column( $filtered['all'], 'hook' );
		self::assertContains( $hook, $hook_names, 'Test event not found in "all" filter' );
	}

	/**
	 * Test that get_filtered_events correctly filters 'custom' event type
	 */
	public function testGetFilteredEventsFiltersCustomEvents(): void {
		// Schedule a custom (non-core) event
		$timestamp = time() + 3600;
		$hook = 'test_custom_filter_event_hook';
		wp_schedule_single_event( $timestamp, $hook );

		$all_events = Crontrol\Event\get();
		$filtered = Table::get_filtered_events( $all_events );

		// Should include our custom event
		$hook_names = array_column( $filtered['custom'], 'hook' );
		self::assertContains( $hook, $hook_names, 'Custom event not found in "custom" filter' );

		// Should not include core events like wp_version_check in custom filter
		self::assertNotContains( 'wp_version_check', $hook_names, 'Core event should not be in "custom" filter' );
	}

	/**
	 * Test that get_filtered_events correctly filters 'core' event type
	 */
	public function testGetFilteredEventsFiltersCoreEvents(): void {
		// Schedule a core event (if not already scheduled)
		if ( ! wp_next_scheduled( 'wp_version_check' ) ) {
			wp_schedule_event( time() + 3600, 'twicedaily', 'wp_version_check' );
		}

		// Schedule a custom event
		$timestamp = time() + 7200;
		$custom_hook = 'test_custom_not_in_core_filter';
		wp_schedule_single_event( $timestamp, $custom_hook );

		$all_events = Crontrol\Event\get();
		$filtered = Table::get_filtered_events( $all_events );

		// Should include core events
		$hook_names = array_column( $filtered['core'], 'hook' );
		self::assertContains( 'wp_version_check', $hook_names, 'Core event wp_version_check not found in "core" filter' );

		// Should not include custom events
		self::assertNotContains( $custom_hook, $hook_names, 'Custom event should not be in "core" filter' );
	}

	/**
	 * Test that get_filtered_events correctly filters 'php' event type
	 */
	public function testGetFilteredEventsFiltersPhpEvents(): void {
		// Schedule a PHP cron job
		$timestamp = time() + 3600;
		$hook = 'crontrol_cron_job';
		$args = array(
			array(
				'code' => 'echo "test";',
				'name' => 'Test PHP Job for Filter',
				'hash' => wp_hash( 'echo "test";' ),
			),
		);
		wp_schedule_single_event( $timestamp, $hook, $args );

		$all_events = Crontrol\Event\get();
		$filtered = Table::get_filtered_events( $all_events );

		// Should only include PHP cron jobs
		$hook_names = array_column( $filtered['php'], 'hook' );
		$unique_hooks = array_unique( $hook_names );
		self::assertSame( array( 'crontrol_cron_job' ), $unique_hooks );

		// Should include our test PHP job
		self::assertGreaterThan( 0, count( $filtered['php'] ), 'No PHP events found in filter' );
	}

	/**
	 * Test that get_filtered_events correctly filters 'noaction' event type
	 */
	public function testGetFilteredEventsFiltersNoActionEvents(): void {
		// Create an event with no registered callbacks
		$timestamp = time() + 3600;
		$hook = 'test_event_with_no_callbacks_filter_' . uniqid();
		wp_schedule_single_event( $timestamp, $hook );

		$all_events = Crontrol\Event\get();
		$filtered = Table::get_filtered_events( $all_events );

		// Should include our event with no callbacks
		$hook_names = array_column( $filtered['noaction'], 'hook' );
		self::assertContains( $hook, $hook_names, 'Event with no callbacks not found in "noaction" filter' );

		// All events in this filter should have no actions
		foreach ( $filtered['noaction'] as $event ) {
			$hook_callbacks = \Crontrol\get_hook_callbacks( $event->hook );
			self::assertEmpty( $hook_callbacks );
		}
	}

	/**
	 * Test that get_filtered_events correctly filters 'url' event type
	 */
	public function testGetFilteredEventsFiltersUrlEvents(): void {
		// Schedule a URL cron job
		$timestamp = time() + 3600;
		$hook = 'crontrol_url_cron_job';
		$url = 'https://example.com/webhook';
		$args = array(
			array(
				'url' => $url,
				'name' => 'Test URL Job for Filter',
				'hash' => wp_hash( $url ),
			),
		);
		wp_schedule_single_event( $timestamp, $hook, $args );

		$all_events = Crontrol\Event\get();
		$filtered = Table::get_filtered_events( $all_events );

		// Should only include URL cron jobs
		$hook_names = array_column( $filtered['url'], 'hook' );
		$unique_hooks = array_unique( $hook_names );
		self::assertSame( array( 'crontrol_url_cron_job' ), $unique_hooks );

		// Should include our test URL job
		self::assertGreaterThan( 0, count( $filtered['url'] ), 'No URL events found in filter' );
	}

	/**
	 * Test that get_filtered_events correctly filters 'paused' event type
	 */
	public function testGetFilteredEventsFiltersPausedEvents(): void {
		// Schedule an event and then pause it
		$timestamp = time() + 3600;
		$hook = 'test_paused_filter_event_hook';
		wp_schedule_single_event( $timestamp, $hook );

		// Pause the event
		Crontrol\Event\pause( $hook );

		$all_events = Crontrol\Event\get();
		$filtered = Table::get_filtered_events( $all_events );

		// Should include our paused event
		$hook_names = array_column( $filtered['paused'], 'hook' );
		self::assertContains( $hook, $hook_names, 'Paused event not found in "paused" filter' );

		// All events in this filter should be paused
		foreach ( $filtered['paused'] as $event ) {
			self::assertTrue( Crontrol\Event\is_paused( $event ) );
		}
	}
}
