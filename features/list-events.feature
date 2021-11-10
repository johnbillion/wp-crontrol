Feature: List cron events
	As an administrator
	I need to be able to list pending cron events
	In order to see what's up

	Background:
		Given the "wp-crontrol/wp-crontrol.php" plugin is active

	Scenario: List cron events
		Given I am logged in as admin
		When I go to the dashboard
		And I go to the "Tools > Cron Events" menu
		Then I should see "Cron Events" in the "#crontrol-header" element
		And I should be on the "Cron Events" screen
		And I should see a "table.crontrol-events" element
