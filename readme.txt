=== WP Crontrol ===
Contributors: scompt, johnbillion
Tags: admin, cron, plugin, control, wp-cron, crontrol, wp-cli
Requires at least: 3.0
Tested up to: 3.6
Stable tag: 1.2.1

WP Crontrol lets you view and control what's happening in the WP-Cron system.

== Description ==

WP Crontrol lets you view and control what's happening in the WP-Cron system. From the admin screen you can:

 * View all cron entries along with their arguments, recurrence and when they are next due.
 * Edit, delete, and immediately run any cron entries.
 * Add new cron entries.

The admin screen will show you a warning message if your cron system doesn't appear to be working (for example if your server can't connect to itself to fire scheduled cron entries).

From the settings screen you can also add, edit and remove cron schedues.

Now supports [wp-cli](http://wp-cli.org/)!

== Installation ==

You can install this plugin directly from your WordPress dashboard:

 1. Go to the *Plugins* menu and click *Add New*.
 2. Search for *WP Crontrol*.
 3. Click *Install Now* next to the *WP Crontrol* plugin.
 4. Activate the plugin.

Alternatively, see the guide to [Manually Installing Plugins](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

= Usage =

1. Go to the Tools -> Crontrol menu to see what cron entries are scheduled and to add some new ones.
2. Go to the Settings -> Cron Schedules menu to add new cron schedules.

== Frequently Asked Questions ==

= What's the use of adding new cron schedules? =

Cron schedules are used by WordPress and WordPress plugins to allow you to schedule commands to be executed at regular intervals.  Intervals must be provided by the WordPress core or a plugin in order to be used.  An example of a plugin that uses these schedules is [WordPress Database Backup](http://www.ilfilosofo.com/blog/wp-db-backup/).  Out of the box, only daily and hourly backups are supported.  In order to do a weekly backup, a weekly cron schedule must be entered into WP Crontrol first and then the backup plugin can take advantage of it as an interval.

= How do I create a new PHP cron entry? =

In the Tools -> Crontrol admin panel, click on the "add new PHP entry" link underneath the cron entry table.  In the form that appears, enter the schedule and next run time in the boxes.  Next run is the next time that the hook will execute.  This can be entered in using [GNU Date Input Formats](http://www.gnu.org/software/tar/manual/html_node/tar_113.html), but often *now* is good enough.  The entry schedule is how often your hook will be executed.  If you don't see a good interval, then add one in the Settings -> Crontrol admin panel.  In the "Hook code" area, enter the PHP code that should be run when your cron entry is executed.  You don't need to provide the PHP opening tag (`<?php`).

= How do I create a new regular cron entry? =

There are two steps to getting a functioning cron entry that executes regularly.  The first step is telling WordPress about the hook.  This is the part that WP Crontrol was created to provide.  The second step is calling your function when your hook is executed.  You've got to do that on your own, but I'll explain how below.

*Step One: Adding the hook*

In the Tools -> Crontrol admin panel, enter the details of the hook.  You're best off having a hookname that conforms to normal PHP variable naming conventions.  This could save you trouble later.  Other than that, the hookname can be whatever you want it to be.  Next run is the next time that the hook will execute.  This can be entered in using [GNU Date Input Formats](http://www.gnu.org/software/tar/manual/html_node/tar_113.html), but often *now* is good enough.  The entry schedule is how often your hook will be executed.  If you don't see a good interval, then add one in the Settings -> Crontrol admin panel.

*Step Two: Writing the function*

This part takes place in PHP code (for example, in the `functions.php` file from your theme).  To execute your hook, WordPress runs an [action](http://codex.wordpress.org/Plugin_API#Actions).  For this reason, we need to now tell WordPress which function to execute when this action is run.  The following line accomplishes that:

`add_action('my_hookname', 'my_function');`

The next step is to write your function.  Here's a simple example:

`function my_function() {
        wp_mail('hello@example.com', 'WP Crontrol', 'WP Crontrol rocks!');
}`

= Do I really need the entire `wp-crontrol` directory? =

No, you can get rid of the whole directory and just use `wp-crontrol.php` if you wish. If you want to use wp-cli then you'll need to include `class-wp-cli.php` too.

= Which wp-cli commands are available? =

 * `wp crontrol list` Lists the scheduled events on your site.
 * `wp crontrol test` Performs a WP-Cron spawning test to make sure WP-Cron can function as expected.
 * `wp crontrol list-schedules` Lists the available WP-Cron schedules on your site.

Note that wp-cli support was only recently added. This will be improved over time. Feedback welcome!

== Screenshots ==

1. New cron entries can be added, modified, and deleted.  In addition, they can be executed on-demand.
1. New cron schedules can be added to WordPress, giving plugin developers more options when scheduling commands.

== Upgrade Notice ==

= 1.2.1 =
* Correctly display the local time when listing cron entries

== Changelog ==

= 1.2.1 =
* Correctly display the local time when listing cron entries
* Remove a PHP notice
* Pass the WP-Cron spawn check through the same filter as the actual spawner.

= 1.2 =
* Added support for [wp-cli](http://wp-cli.org/)
* Removed some PHP4 code that's no longer relevant

= 1.1 =
* Bug fixes for running cron jobs and adding cron schedules
* Added a cron spawn test to check for errors when spawning cron
* Various small tweaks
* WordPress 3.4 compatibility

= 1.0 =
* Input of PHP code for cron entries
* Non-repeating cron entries
* Handles cron entries with arguments

= 0.3 =
* Internationalization
* Editing/deleting/execution of cron entries
* More text, status messages, etc.
* Allow a user to enter a schedule entry in a human manner
* Looks better on WordPress 2.5

= 0.2 =
* Fully documented the code.
* Fixed the bug that the activate action wouldn't be run if the plugin wasn't in a subdirectory.
* Now will play nicely in case any other plugins specify additional cron schedules.
* Minor cosmetic fixes.

= 0.1 =
* Super basic, look at what's in WP-Cron functionality.

