<?php declare(strict_types = 1);

namespace Crontrol\Tests;

use Crontrol\Exception\DuplicateScheduleException;
use Crontrol\Schedule;

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
		self::assertEquals( 7200, $schedule['interval'] );
		self::assertArrayHasKey( 'display', $schedule );
		self::assertEquals( 'Test Schedule', $schedule['display'] );
	}
}
