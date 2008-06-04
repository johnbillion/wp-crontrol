=== WP-Crontrol ===
Contributors: scompt
Donate link: http://scompt.com/projects/wp-crontrol
Tags: admin, cron, plugin, control
Requires at least: 2.1
Tested up to: 2.5.1
Stable tag: 0.3

WP-Crontrol lets you take control over what's happening in the WP-Cron system.

== Description ==

WP-Crontrol lets you take control over what's happening in the WP-Cron system.

== Installation ==

1. Upload the `wp-crontrol` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Goto the Options->Crontrol panel to add some new cron schedules.
1. Goto the Manage->Crontrol panel to see what cron entries are scheduled and to add some new ones.

== Frequently Asked Questions ==

= What's the use of adding new cron schedules? =

Cron schedules are used by WordPress and WordPress plugins to allow you to schedule commands to be executed at regular intervals.  Intervals must be provided by the WordPress core or a plugin in order to be used.  An example of a plugin that uses these schedules is [WordPress Database Backup](http://www.ilfilosofo.com/blog/wp-db-backup/).  Out of the box, only daily and hourly backups are supported.  In order to do a weekly backup, a weekly cron schedule must be entered into WP-Crontrol first and then the backup plugin can take advantage of it as an interval.

= How do I create a new PHP cron entry? =

In the Manage->Crontrol admin panel, click on the "add new PHP entry" link underneath the cron entry table.  In the form that appears, enter the schedule and next run time in the boxes.  Next run is the next time that the hook will execute.  This can be entered in using [GNU Date Input Formats](http://www.gnu.org/software/tar/manual/html_node/tar_113.html), but often *now* is good enough.  The entry schedule is how often your hook will be executed.  If you don't see a good interval, then add one in the Options->Crontrol admin panel.  In the "Hook code" area, enter the PHP code that should be run when your cron entry is executed.  You don't need to provide the PHP opening tag (`<?php`).

= How do I create a new regular cron entry? =

There are two steps to getting a functioning cron entry that executes regularly.  The first step is telling WordPress about the hook.  This is the part that WP-Crontrol was created to provide.  The second step is calling your function when your hook is executed.  You've got to do that on your own, but I'll explain how below.

*Step One: Adding the hook*

In the Manage->Crontrol admin panel, enter the details of the hook.  You're best off having a hookname that conforms to normal PHP variable naming conventions.  This could save you trouble later.  Other than that, the hookname can be whatever you want it to be.  Next run is the next time that the hook will execute.  This can be entered in using [GNU Date Input Formats](http://www.gnu.org/software/tar/manual/html_node/tar_113.html), but often *now* is good enough.  The entry schedule is how often your hook will be executed.  If you don't see a good interval, then add one in the Options->Crontrol admin panel.

*Step Two: Writing the function*

This part takes place in PHP code (for example, in the `functions.php` file from your theme).  To execute your hook, WordPress runs an [action](http://codex.wordpress.org/Plugin_API#Actions).  For this reason, we need to now tell WordPress which function to execute when this action is run.  The following line accomplishes that:

`add_action('my_hookname', 'my_function');`

The next step is to write your function.  Here's a simple example:

`function my_function() {
        wp_mail('scompt@scompt.com', 'WP-Crontrol', 'WP-Crontrol rocks!');
}`

= Do I really need the entire `wp-crontrol` directory? =

Maybe... The most important file is `wp-crontrol.php`.  If your server is running PHP4, you'll need `JSON.php` also.  If you're on PHP5, then you can get rid of the whole directory and just use `wp-crontrol.php`.

= How do I ask a frequently asked question? =

Email [me](mailto:scompt@scompt.com).

== Screenshots ==

1. New cron entries can be added, modified, and deleted.  In addition, they can be executed on-demand.
1. New cron schedules can be added to WordPress, giving plugin developers more options when scheduling commands.

== Version History ==

= Version 0.1 =

* Super basic, look at what's in WP-Cron functionality.

= Version 0.2 =

* Fully documented the code.
* Fixed the bug that the activate action wouldn't be run if the plugin wasn't in a subdirectory.
* Now will play nicely in case any other plugins specify additional cron schedules.
* Minor cosmetic fixes.

= Version 0.3 =

* Internationalization
* Editing/deleting/execution of cron entries
* More text, status messages, etc.
* Allow a user to enter a schedule entry in a human manner
* Looks better on WordPress 2.5

= Version 1.0 =

* Input of PHP code for cron entries
* Non-repeating cron entries
* Handles cron entries with arguments
