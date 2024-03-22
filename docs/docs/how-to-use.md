# How to use WP Crontrol

WP Crontrol enables you to view and control what’s happening in the WP-Cron system. From the admin screens you can:

* View all cron events along with their arguments, recurrence, callback functions, and when they are next due.
* Edit, delete, pause, resume, and immediately run cron events.
* Add new cron events.
* Bulk delete cron events.
* Add and remove custom cron schedules.
* Export and download cron event lists as a CSV file.

## Installation

Install and activate WP Crontrol as you would any other WordPress plugin. [You can download it here on WordPress.org](https://wordpress.org/plugins/wp-crontrol/).

## Manage cron events

Go to the Tools → Cron Events menu to manage cron events.

From here you can see a list of all the currently scheduled cron events. If there is a large number of events, you can search, filter, and page through the list.

## Edit cron events

Click the Edit link next to an event to edit details such as its hook name, arguments, next run date, and recurrence.

## Add a new cron event

There are two steps to creating a functioning cron event that executes regularly. The first step is telling WordPress about the hook. This is the part that WP Crontrol was created to provide. The second step is calling a function when your hook is executed.

### Step one: Adding the hook

In the Tools → Cron Events admin panel, click on "Add New" and enter the details of the hook. You're best off using a hook name that conforms to normal PHP variable naming conventions. The event schedule is how often your hook will be executed. If you don't see a good interval, then add one first in the Settings → Cron Schedules admin panel.

### Step two: Writing the function

This part takes place in PHP code (for example, in the `functions.php` file from your theme). To execute your hook, WordPress runs an action. For this reason, we need to tell WordPress which function to execute when this action is run. The following line accomplishes that:

```php
add_action( 'my_hookname', 'my_function' );
```

The next step is to write your function. Here's a simple example:

```php
function my_function() {
	wp_mail( 'hello@example.com', 'WP Crontrol', 'WP Crontrol rocks!' );
}
```

## Add a new PHP cron event

[See here for full information about PHP cron events](/docs/php-cron-events/).

## Pause cron events

Pausing a cron event is a way to prevent an event from running. This is more reliable than deleting an event because many plugins will immediately recreate events that are missing.

Pausing an event will disable all actions attached to the event's hook. The event itself will remain in place and will run according to its schedule, but all actions attached to its hook will be disabled. This renders the event inoperative but keeps it scheduled so as to remain fully compatible with events which would otherwise get automatically rescheduled when they're missing.

As pausing an event actually pauses its hook, all events that use the same hook will be paused or resumed when pausing and resuming an event. This is much more useful and reliable than pausing individual events separately.

## Delete cron events

Click the Delete link next to an event to delete it. If no Delete link is shown, it usually means the event is created by the WordPress core software and therefore cannot be deleted. If you want to remove the functionality, you can pause the event instead (see above).

If more than one event exists with a given hook name, you can delete all of them at once via the "Delete all events with this hook" link next to one of the events.

## Bulk delete cron events

You can delete multiple cron events by checking the checkboxes next to the events you wish to delete, then choosing the Delete option from the Bulk Actions menu, and clicking Apply. Events without a checkbox are events created by the WordPress core software and cannot be deleted.

## Export cron events

A CSV file of the event list can be exported and downloaded via the "Export" button. This file can be opened in any spreadsheet application.

## Manage cron schedules

In order to run at a recurring interval, a cron event needs to use a cron schedule. If the built-in cron schedules don't suit your needs then you can use WP Crontrol to add new schedules.

Go to the Settings → Cron Schedules menu to manage cron schedules.
