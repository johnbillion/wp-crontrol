<?php declare(strict_types = 1);

namespace Crontrol\Tests;

use Crontrol;
use Crontrol\Event\Event;
use Crontrol\Event\StandardEvent;
use Crontrol\Event\PHPCronEvent;
use Crontrol\Event\URLCronEvent;
use Crontrol\Event\CoreCronEvent;
use Crontrol\Event\ActionSchedulerEvent;

class EventTest extends Test {
	/**
	 * Test that Event::create() creates StandardEvent for unknown hooks
	 */
	public function testCreateReturnsStandardEventForUnknownHooks(): void {
		$hook = 'custom_test_hook';
		$timestamp = time() + 3600;
		$sig = 'test_sig';
		$args = array( 'test' => 'value' );
		$schedule = null;
		$interval = null;

		$event = Event::create( $hook, $timestamp, $sig, $args, $schedule, $interval );

		self::assertInstanceOf( StandardEvent::class, $event );
		self::assertSame( $hook, $event->hook );
		self::assertSame( $timestamp, $event->timestamp );
		self::assertSame( $sig, $event->sig );
		self::assertSame( $args, $event->args );
		self::assertNull( $event->schedule );
		self::assertNull( $event->interval );
	}

	/**
	 * Test that Event::create() creates PHPCronEvent for PHP cron hooks
	 */
	public function testCreateReturnsPHPCronEventForPHPHook(): void {
		$hook = PHPCronEvent::HOOK_NAME;
		$timestamp = time() + 3600;
		$sig = 'php_sig';
		$args = array(
			array(
				'code' => 'echo "test";',
				'name' => 'Test PHP Job',
				'hash' => wp_hash( 'echo "test";' ),
			),
		);
		$schedule = null;
		$interval = null;

		$event = Event::create( $hook, $timestamp, $sig, $args, $schedule, $interval );

		self::assertInstanceOf( PHPCronEvent::class, $event );
		self::assertTrue( $event->is_php_cron() );
		self::assertTrue( $event->is_crontrol_event() );
		self::assertTrue( $event->is_protected() );
	}

	/**
	 * Test that Event::create() creates URLCronEvent for URL cron hooks
	 */
	public function testCreateReturnsURLCronEventForURLHook(): void {
		$hook = URLCronEvent::HOOK_NAME;
		$timestamp = time() + 3600;
		$sig = 'url_sig';
		$args = array(
			array(
				'url' => 'https://example.com/webhook',
				'name' => 'Test URL Job',
				'hash' => wp_hash( 'https://example.com/webhook' ),
			),
		);
		$schedule = 'hourly';
		$interval = 3600;

		$event = Event::create( $hook, $timestamp, $sig, $args, $schedule, $interval );

		self::assertInstanceOf( URLCronEvent::class, $event );
		self::assertTrue( $event->is_url_cron() );
		self::assertTrue( $event->is_crontrol_event() );
		self::assertTrue( $event->is_protected() );
	}

	/**
	 * Test that Event::create() creates CoreCronEvent for WordPress core hooks
	 */
	public function testCreateReturnsCoreCronEventForCoreHook(): void {
		$hook = 'wp_version_check';
		$timestamp = time() + 3600;
		$sig = 'core_sig';
		$args = array();
		$schedule = 'twicedaily';
		$interval = 43200;

		$event = Event::create( $hook, $timestamp, $sig, $args, $schedule, $interval );

		self::assertInstanceOf( CoreCronEvent::class, $event );
		self::assertTrue( $event->is_core_cron() );
		self::assertTrue( $event->is_protected() );
		self::assertFalse( $event->is_php_cron() );
		self::assertFalse( $event->is_url_cron() );
	}

	/**
	 * Test that Event::create() creates ActionSchedulerEvent for Action Scheduler hooks
	 */
	public function testCreateReturnsActionSchedulerEventForActionSchedulerHook(): void {
		$hook = ActionSchedulerEvent::HOOK_NAME;
		$timestamp = time() + 300;
		$sig = 'as_sig';
		$args = array();
		$schedule = 'every_minute';
		$interval = 60;

		$event = Event::create( $hook, $timestamp, $sig, $args, $schedule, $interval );

		self::assertInstanceOf( ActionSchedulerEvent::class, $event );
		self::assertTrue( $event->is_action_scheduler_cron() );
		self::assertFalse( $event->is_protected() );
	}

	/**
	 * Test that Event::create_new() returns a StandardEvent instance with default values
	 */
	public function testCreateNewReturnsStandardEventWithDefaults(): void {
		$before_time = time();
		$event = Event::create_new();
		$after_time = time();

		self::assertInstanceOf( StandardEvent::class, $event );
		self::assertSame( '', $event->hook );
		self::assertGreaterThanOrEqual( $before_time, $event->timestamp );
		self::assertLessThanOrEqual( $after_time, $event->timestamp );
		self::assertSame( '', $event->sig );
		self::assertSame( array(), $event->args );
		self::assertNull( $event->schedule );
		self::assertNull( $event->interval );
	}

	/**
	 * Test that Event::create_immediate() creates an immediate event
	 */
	public function testCreateImmediateCreatesImmediateEvent(): void {
		$hook = 'test_immediate_hook';
		$args = array( 'key' => 'value', 'number' => 123 );

		$event = Event::create_immediate( $hook, $args );

		self::assertInstanceOf( StandardEvent::class, $event );
		self::assertSame( $hook, $event->hook );
		self::assertSame( 1, $event->timestamp );
		self::assertSame( '', $event->sig );
		self::assertSame( $args, $event->args );
		self::assertNull( $event->schedule );
		self::assertNull( $event->interval );
		self::assertTrue( $event->is_immediate() );
	}

	/**
	 * Test that Event::create_immediate() works with empty args
	 */
	public function testCreateImmediateWorksWithEmptyArgs(): void {
		$hook = 'test_immediate_no_args';

		$event = Event::create_immediate( $hook );

		self::assertInstanceOf( StandardEvent::class, $event );
		self::assertSame( $hook, $event->hook );
		self::assertSame( 1, $event->timestamp );
		self::assertSame( '', $event->sig );
		self::assertSame( array(), $event->args );
		self::assertTrue( $event->is_immediate() );
	}

	/**
	 * Test that Event::create_immediate() creates correct subclass for special hooks
	 */
	public function testCreateImmediateCreatesCorrectSubclassForSpecialHooks(): void {
		// Test PHP cron hook
		$php_event = Event::create_immediate( PHPCronEvent::HOOK_NAME, array( array( 'code' => 'echo "test";' ) ) );
		self::assertInstanceOf( PHPCronEvent::class, $php_event );
		self::assertTrue( $php_event->is_immediate() );

		// Test URL cron hook
		$url_event = Event::create_immediate( URLCronEvent::HOOK_NAME, array( array( 'url' => 'https://example.com' ) ) );
		self::assertInstanceOf( URLCronEvent::class, $url_event );
		self::assertTrue( $url_event->is_immediate() );

		// Test core hook
		$core_event = Event::create_immediate( 'wp_version_check' );
		self::assertInstanceOf( CoreCronEvent::class, $core_event );
		self::assertTrue( $core_event->is_immediate() );
	}

	/**
	 * Test is_recurring() for one-time vs recurring events
	 */
	public function testIsRecurringForOneTimeVsRecurringEvents(): void {
		// Test recurring event
		$recurring_event = Event::create( 'test_recurring', time(), 'sig2', array(), 'hourly', 3600 );

		// Test one-time event
		$one_time_event = Event::create( 'test_one_time', time(), 'sig1', array(), null, null );

		// Test that create_new() creates a non-recurring event
		$new_event = Event::create_new();

		self::assertTrue( $recurring_event->is_recurring() );
		self::assertFalse( $one_time_event->is_recurring() );
		self::assertFalse( $new_event->is_recurring() );
	}

	/**
	 * Test is_late() for events past their scheduled time
	 */
	public function testIsLateForEventsPastScheduledTime(): void {
		// Event scheduled for the future - not late
		$future_event = Event::create( 'test_future', time() + 3600, 'sig1', array(), null, null );
		self::assertFalse( $future_event->is_late() );

		// Event scheduled for 5 minutes ago - not late (within 10-minute threshold)
		$recent_event = Event::create( 'test_recent', time() - ( 5 * MINUTE_IN_SECONDS ), 'sig2', array(), null, null );
		self::assertFalse( $recent_event->is_late() );

		// Event scheduled for 15 minutes ago - late
		$late_event = Event::create( 'test_late', time() - ( 15 * MINUTE_IN_SECONDS ), 'sig3', array(), null, null );
		self::assertTrue( $late_event->is_late() );

		// Event scheduled for an hour ago - definitely late
		$very_late_event = Event::create( 'test_very_late', time() - HOUR_IN_SECONDS, 'sig4', array(), null, null );
		self::assertTrue( $very_late_event->is_late() );
	}

	/**
	 * Test is_immediate() for events with timestamp = 1
	 */
	public function testIsImmediateForEventsWithTimestampOne(): void {
		// Normal event - not immediate
		$normal_event = Event::create( 'test_normal', time() + 3600, 'sig1', array(), null, null );
		self::assertFalse( $normal_event->is_immediate() );

		// Immediate event with timestamp = 1
		$immediate_event = Event::create( 'test_immediate', 1, 'sig2', array(), null, null );
		self::assertTrue( $immediate_event->is_immediate() );

		// Event with timestamp = 2 - not immediate
		$not_immediate = Event::create( 'test_not_immediate', 2, 'sig3', array(), null, null );
		self::assertFalse( $not_immediate->is_immediate() );

		// Test that create_new() does not create an immediate event
		$new_event = Event::create_new();
		self::assertFalse( $new_event->is_immediate() );
	}

	/**
	 * Test is_paused() with paused hooks
	 */
	public function testIsPausedWithPausedHooks(): void {
		$hook = 'test_paused_hook_' . uniqid();
		$event = Event::create( $hook, time() + 3600, 'sig1', array(), null, null );

		// Initially not paused
		self::assertFalse( $event->is_paused() );

		// Pause the hook
		Crontrol\Event\pause( $hook );

		// Now it should be paused
		self::assertTrue( $event->is_paused() );

		// Create another event with the same hook - should also be paused
		$another_event = Event::create( $hook, time() + 7200, 'sig2', array(), 'hourly', 3600 );
		self::assertTrue( $another_event->is_paused() );

		// Create event with different hook - should not be paused
		$different_hook = 'test_not_paused_hook_' . uniqid();
		$different_event = Event::create( $different_hook, time() + 3600, 'sig3', array(), null, null );
		self::assertFalse( $different_event->is_paused() );

		// Resume the original hook
		Crontrol\Event\resume( $hook );

		// Should no longer be paused
		self::assertFalse( $event->is_paused() );
		self::assertFalse( $another_event->is_paused() );
	}
}
