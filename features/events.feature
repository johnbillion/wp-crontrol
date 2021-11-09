Feature: List cron events
	As an administrator
	I need to be able to list pending cron events
	In order to see what's up

	Background:
		Given the "wp-crontrol/wp-crontrol.php" plugin is active
		And there are users:
			| user_login    | display_name  | user_email                | user_pass | role          |
			| administrator | Administrator | administrator@example.com | password  | administrator |

	Scenario: List cron events
		Given I am logged in as admin
		When I go to the dashboard
		And I go to the "Tools > Cron Events" menu
		Then I should see "Cron Events"
