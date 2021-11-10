Feature: Add a cron event
	As an administrator
	I need to be able to add a new cron event
	In order to run an action on a schedule

	Background:
		Given the "wp-crontrol/wp-crontrol.php" plugin is active

	Scenario: Add a cron event
		Given I am logged in as admin
		When I go to the dashboard
		And I go to the "Tools > Cron Events" menu
		Then I should see "Add New" in the "#wpbody" element
		When I click on the "Add New" link in the header
		Then I should see "Add Cron Event" in the "#crontrol-header" element
		And I should be on the "Add Cron Event" screen
