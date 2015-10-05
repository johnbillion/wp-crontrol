=== WP Crontrol ===
Contributors: johnbillion, scompt
Tags: admin, cron, plugin, control, wp-cron, crontrol
Requires at least: 4.0
Tested up to: 4.3
Stable tag: 1.3

WP Crontrol lets you view and control what's happening in the WP-Cron system.

== Description ==

WP Crontrol lets you view and control what's happening in the WP-Cron system. From the admin screens you can:

 * View all cron events along with their arguments, recurrence, and when they are next due.
 * Edit, delete, and immediately run any cron events.
 * Add new cron events.
 * Add, edit, and remove custom cron schedules.

The admin screen will show you a warning message if your cron system doesn't appear to be working (for example if your server can't connect to itself to fire scheduled cron events).

= Usage =

1. Go to the Tools -> Cron Events menu to manage cron events.
2. Go to the Settings -> Cron Schedules menu to manage cron schedules.

== Installation ==

You can install this plugin directly from your WordPress dashboard:

 1. Go to the *Plugins* menu and click *Add New*.
 2. Search for *WP Crontrol*.
 3. Click *Install Now* next to the *WP Crontrol* plugin.
 4. Activate the plugin.

Alternatively, see the guide to [Manually Installing Plugins](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

= Usage =

1. Go to the Tools -> Cron Events menu to manage cron events.
2. Go to the Settings -> Cron Schedules menu to manage cron schedules.

== Frequently Asked Questions ==

= What's the use of adding new cron schedules? =

Cron schedules are used by WordPress and plugins for scheduling events to be executed at regular intervals. Intervals must be provided by the WordPress core or a plugin in order to be used. As an example, many backup plugins provide support for periodic backups. In order to do a weekly backup, a weekly cron schedule must be entered into WP Crontrol first and then a backup plugin can take advantage of it as an interval.

= How do I create a new PHP cron event? =

In the Tools -> Cron Events admin panel, click on the "Add PHP Cron Event" tab underneath the cron event table. In the form that appears, enter the schedule and next run time in the boxes. The event schedule is how often your event will be executed. If you don't see a good interval, then add one in the Settings -> Cron Schedules admin panel. In the "Hook code" area, enter the PHP code that should be run when your cron event is executed. You don't need to provide the PHP opening tag (`<?php`).

= How do I create a new regular cron event? =

There are two steps to getting a functioning cron event that executes regularly. The first step is telling WordPress about the hook. This is the part that WP Crontrol was created to provide. The second step is calling a function when your hook is executed.

*Step One: Adding the hook*

In the Tools -> Cron Events admin panel, enter the details of the hook. You're best off having a hookname that conforms to normal PHP variable naming conventions. The event schedule is how often your hook will be executed. If you don't see a good interval, then add one in the Settings -> Cron Schedules admin panel.

*Step Two: Writing the function*

This part takes place in PHP code (for example, in the `functions.php` file from your theme). To execute your hook, WordPress runs an [action](https://codex.wordpress.org/Plugin_API#Actions). For this reason, we need to tell WordPress which function to execute when this action is run. The following line accomplishes that:

`add_action( 'my_hookname', 'my_function' );`

The next step is to write your function. Here's a simple example:

`function my_function() {
	wp_mail( 'hello@example.com', 'WP Crontrol', 'WP Crontrol rocks!' );
}`

= Are any WP-CLI commands available? =

The cron commands which were previously included in WP Crontrol are now part of WP-CLI (since 0.16), so this plugin no longer provides any WP-CLI commands. See `wp help cron` for more info.

== Screenshots ==

1. New cron events can be added, modified, deleted, and executed.
2. New cron schedules can be added, giving plugin developers more options when scheduling events.

== Changelog ==

= 1.3 =
* Improvements to the UI.
* More error detection when testing WP-Cron functionality.
* Improve the capability checks for single site and multisite.
* Lots of escaping and sanitising.
* Fix various issues with multiple events with the same hook name.
* Removed the WP-CLI commands, as these have now been added to WP-CLI core (see `wp help cron` for more info)

= 1.2.3 =
* Tweaks to i18n and date and args formatting
* Properly escape the `crontrol_message` query var (props Julio Potier)

= 1.2.2 =
* Added `wp crontrol run-event` and `wp crontrol delete-event` WP-CLI commands
* Clarify language regarding hooks/entries/events

= 1.2.1 =
* Correctly display the local time when listing cron events
* Remove a PHP notice
* Pass the WP-Cron spawn check through the same filter as the actual spawner

= 1.2 =
* Added support for [WP-CLI](http://wp-cli.org/)
* Removed some PHP4 code that's no longer relevant

= 1.1 =
* Bug fixes for running cron events and adding cron schedules
* Added a cron spawn test to check for errors when spawning cron
* Various small tweaks
* WordPress 3.4 compatibility

= 1.0 =
* Input of PHP code for cron events
* Non-repeating cron events
* Handles cron events with arguments

= 0.3 =
* Internationalization
* Editing/deleting/execution of cron events
* More text, status messages, etc.
* Allow a user to enter a schedule event in a human manner
* Looks better on WordPress 2.5

= 0.2 =
* Fully documented the code.
* Fixed the bug that the activate action wouldn't be run if the plugin wasn't in a subdirectory.
* Now will play nicely in case any other plugins specify additional cron schedules.
* Minor cosmetic fixes.

= 0.1 =
* Super basic, look at what's in WP-Cron functionality.
