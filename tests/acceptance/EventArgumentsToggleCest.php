<?php
/**
 * Acceptance tests for event arguments toggle functionality.
 */

/**
 * Test class.
 */
class EventArgumentsToggleCest {
	public function _before( AcceptanceTester $I ): void {
		$I->loginAsAdmin();
	}

	public function EventArgumentsAreCollapsible( AcceptanceTester $I ): void {
		// Create an event with arguments
		$hook_name = 'test_event_with_args';
		$args = '["arg1","arg2"]';
		$row = $I->amWorkingWithANewCronEvent( $hook_name, $args );

		// Check that the event row contains the collapsible arguments toggle
		$I->see( 'View arguments', $row );

		// The arguments should not be visible initially (collapsed)
		$I->dontSee( 'arg1', $row );
		$I->dontSee( 'arg2', $row );

		// Click the summary to expand (use JavaScript since Codeception has issues with details/summary)
		$I->executeJS( "document.querySelector('details summary').click();" );

		// Now we should see the arguments content in the row
		$I->see( 'arg1', $row );
		$I->see( 'arg2', $row );
	}

	public function EventsWithoutArgumentsDontShowToggle( AcceptanceTester $I ): void {
		// Create two events - one with args and one without
		$hook_with_args = 'event_with_args';
		$hook_without_args = 'event_without_args';

		// First create an event with arguments
		$row_with_args = $I->amWorkingWithANewCronEvent( $hook_with_args, '["test"]' );

		// Then create an event without arguments
		$row_without_args = $I->amWorkingWithANewCronEvent( $hook_without_args );

		// The event with arguments should have the toggle
		$I->see( $hook_with_args, $row_with_args );
		$I->see( 'View arguments', $row_with_args );

		// The event without arguments should NOT have the toggle
		$I->see( $hook_without_args, $row_without_args );
		$I->dontSee( 'View arguments', $row_without_args );
	}
}
