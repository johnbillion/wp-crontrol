<?php declare(strict_types = 1);

namespace Crontrol\Tests;

use Crontrol\Exception\DuplicateScheduleException;
use Crontrol\Schedule;
use Crontrol\Schedule\Schedule as ScheduleClass;
use Crontrol\Schedule\CoreSchedule;
use Crontrol\Schedule\CustomSchedule;

class ScheduleTest extends Test {
	/**
	 * @covers \Crontrol\Schedule\add
	 */
	public function testAddScheduleThrowsExceptionForDuplicateCoreSchedule(): void {
		self::expectException( DuplicateScheduleException::class );

		// Attempt to add a schedule with a name that already exists (hourly is a core schedule)
		Schedule\add( 'hourly', 3600, 'Test Hourly' );
	}

	/**
	 * @covers \Crontrol\Schedule\add
	 */
	public function testAddScheduleThrowsExceptionForDuplicateCustomSchedule(): void {
		$schedule_name = 'test_duplicate_schedule_' . time();

		// First, add a custom schedule
		Schedule\add( $schedule_name, 1800, 'Test Schedule' );

		// Verify it was added
		$schedules = Schedule\get();
		self::assertArrayHasKey( $schedule_name, $schedules );

		// Now attempt to add a schedule with the same name
		self::expectException( DuplicateScheduleException::class );

		Schedule\add( $schedule_name, 3600, 'Another Test Schedule' );
	}

	/**
	 * @covers \Crontrol\Schedule\add
	 */
	public function testAddScheduleSucceedsForNewSchedule(): void {
		$schedule_name = 'test_schedule_' . time();

		// This should not throw an exception
		Schedule\add( $schedule_name, 7200, 'Test Schedule' );

		// Verify the schedule was added
		$schedules = Schedule\get();
		self::assertArrayHasKey( $schedule_name, $schedules );
		$schedule = $schedules[ $schedule_name ];
		self::assertInstanceOf( CustomSchedule::class, $schedule );
		self::assertEquals( 7200, $schedule->interval );
		self::assertEquals( 'Test Schedule', $schedule->display );
	}

	/**
	 * @covers \Crontrol\Schedule\Schedule::create
	 */
	public function testCreatesCoreScheduleForCoreSchedules(): void {
		$schedule = ScheduleClass::create( 'hourly', 3600, 'Once Hourly' );

		self::assertInstanceOf( CoreSchedule::class, $schedule );
		self::assertSame( 'hourly', $schedule->name );
		self::assertSame( 3600, $schedule->interval );
		self::assertSame( 'Once Hourly', $schedule->display );
		self::assertTrue( $schedule->is_core_schedule() );
		self::assertTrue( $schedule->is_protected() );
		self::assertFalse( $schedule->is_custom_schedule() );
	}

	/**
	 * @covers \Crontrol\Schedule\Schedule::create
	 */
	public function testCreatesCustomScheduleForNonCoreSchedules(): void {
		$schedule = ScheduleClass::create( 'custom_schedule', 7200, 'Every Two Hours' );

		self::assertInstanceOf( CustomSchedule::class, $schedule );
		self::assertSame( 'custom_schedule', $schedule->name );
		self::assertSame( 7200, $schedule->interval );
		self::assertSame( 'Every Two Hours', $schedule->display );
		self::assertFalse( $schedule->is_core_schedule() );
		self::assertFalse( $schedule->is_custom_schedule() ); // Not a WP Crontrol custom schedule
	}

	/**
	 * @covers \Crontrol\Schedule\Schedule::is_too_frequent
	 */
	public function testDetectsTooFrequentSchedules(): void {
		// Create a schedule with interval less than WP_CRON_LOCK_TIMEOUT
		$fast_schedule = ScheduleClass::create( 'very_fast', 30, 'Every 30 Seconds' );

		self::assertTrue( $fast_schedule->is_too_frequent() );

		// Create a schedule with interval greater than WP_CRON_LOCK_TIMEOUT
		$slow_schedule = ScheduleClass::create( 'slow', 3600, 'Every Hour' );

		self::assertFalse( $slow_schedule->is_too_frequent() );
	}

	/**
	 * @covers \Crontrol\Schedule\Schedule::is_custom_schedule
	 */
	public function testDetectsCustomSchedulesManagedByWPCrontrol(): void {
		// Add a custom schedule via WP Crontrol
		Schedule\add( 'wp_crontrol_custom', 1800, 'Custom Schedule' );

		$schedules = Schedule\get();
		$schedule = $schedules['wp_crontrol_custom'];

		self::assertTrue( $schedule->is_custom_schedule() );
		self::assertInstanceOf( CustomSchedule::class, $schedule );
	}

	/**
	 * @covers \Crontrol\Schedule\Schedule::is_in_use
	 * @covers \Crontrol\Schedule\Schedule::is_protected
	 */
	public function testDetectsSchedulesInUse(): void {
		// Add a custom schedule
		Schedule\add( 'test_schedule_in_use', 3600, 'Test Schedule' );

		// Add an event that uses this schedule
		$timestamp = time() + 3600;
		wp_schedule_event( $timestamp, 'test_schedule_in_use', 'test_hook_for_schedule' );

		$schedules = Schedule\get();
		$schedule = $schedules['test_schedule_in_use'];

		self::assertTrue( $schedule->is_in_use() );
		self::assertTrue( $schedule->is_protected() ); // Protected because it's in use
	}

	/**
	 * @covers \Crontrol\Schedule\Schedule::is_in_use
	 * @covers \Crontrol\Schedule\Schedule::is_protected
	 */
	public function testDetectsSchedulesNotInUse(): void {
		// Add a custom schedule but don't use it
		Schedule\add( 'test_schedule_not_in_use', 3600, 'Unused Schedule' );

		$schedules = Schedule\get();
		$schedule = $schedules['test_schedule_not_in_use'];

		self::assertFalse( $schedule->is_in_use() );
		self::assertFalse( $schedule->is_protected() ); // Not protected since it's not in use
	}

	/**
	 * @covers \Crontrol\Schedule\Schedule::is_protected
	 */
	public function testCustomScheduleIsProtectedWhenNotManagedByWPCrontrol(): void {
		// Simulate a schedule added by another plugin
		add_filter( 'cron_schedules', function ( $schedules ) {
			$schedules['external_plugin_schedule'] = array(
				'interval' => 7200,
				'display'  => 'External Plugin Schedule',
			);
			return $schedules;
		} );

		$schedules = Schedule\get();
		$schedule = $schedules['external_plugin_schedule'];

		self::assertInstanceOf( CustomSchedule::class, $schedule );
		self::assertFalse( $schedule->is_custom_schedule() ); // Not a WP Crontrol schedule
		self::assertTrue( $schedule->is_protected() ); // Protected because it's from another plugin
	}

	/**
	 * @return array<string, array{string}>
	 */
	public static function dataCoreScheduleNames(): array {
		return array(
			'hourly' => array( 'hourly' ),
			'twicedaily' => array( 'twicedaily' ),
			'daily' => array( 'daily' ),
			'weekly' => array( 'weekly' ),
		);
	}

	/**
	 * @dataProvider dataCoreScheduleNames
	 * @covers \Crontrol\Schedule\CoreSchedule::is_protected
	 */
	public function testCoreScheduleIsAlwaysProtected( string $name ): void {
		$schedules = Schedule\get();
		$schedule = $schedules[ $name ];

		self::assertTrue( $schedule->is_core_schedule(), "Schedule '{$name}' should be a core schedule" );
		self::assertTrue( $schedule->is_protected(), "Schedule '{$name}' should be protected" );
		self::assertInstanceOf( CoreSchedule::class, $schedule );
	}
}
