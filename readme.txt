=== WP-Crontrol ===
Contributors: scompt
Donate link: http://scompt.com/projects/wp-crontrol
Tags: admin, cron, plugin, control
Requires at least: 2.1
Tested up to: 2.3
Stable tag: 0.2

WP-Crontrol lets you take control over what's happening in the WP-Cron system.

== Description ==

WP-Crontrol lets you take control over what's happening in the WP-Cron system.

== Installation ==

1. Upload the `wp-crontrol` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Goto the Options->Crontrol panel to add some new cron schedules.
1. Goto the Manage->Crontrol panel to see what cron entries are scheduled and to add some new ones.

== Frequently Asked Questions ==

= How do I ask a frequently asked question? =

Email [me](mailto:scompt@scompt.com).

== Screenshots ==

1. None yet

== Future Plans ==

* Make better

== Version History ==

= Version 0.1 =

* Super basic, look at what's in WP-Cron functionality.

= Version 0.2 =

* Fully documented the code.
* Fixed the bug that the activate action wouldn't be run if the plugin wasn't in a subdirectory.
* Now will play nicely in case any other plugins specify additional cron schedules.
* Minor cosmetic fixes.