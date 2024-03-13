# Cron events that have missed their schedule

The WP-Cron system in WordPress is not a "real" cron system, which means events may not run exactly according to their schedule because the system relies on regular traffic to the website in order to trigger scheduled events.

The WP Crontrol plugin does _not_ alter the way that WordPress core runs or stores cron events.

## Reasons WP-Cron events can miss their schedule

* Low traffic websites may not trigger the event runner often enough
* A fatal error caused by a plugin or theme may break the event runner
* A plugin or theme may intentionally or unintentionally break the event runner
* BasicAuth, a firewall, password protection, or other access restrictions may block the event runner
* A problem with your web hosting or web server may break the event runner
* Long-running events may temporarily block the event runner
* High traffic websites may suffer from sequential processing issues that block the event runner
* The `DISABLE_WP_CRON` configuration constant is set but no alternative cron runner has been put in place
* An alternative cron runner is in place but is not working as expected or has stalled

## How can I fix this?

There's no single solution to this problem. Your best approach is to:

1. **Reload the page a few times** to determine if the problem is persistent. If the issue only appears occasionally then it could be that low levels of traffic to your site is the cause. Continue reading for more info.
2. Read the [Problems spawning a call to the WP-Cron system](https://wp-crontrol.com/help/problems-spawning-wp-cron) page.
3. **Deactivate other plugins on your site one by one** to see if any of them are causing things to break. Start with ones that you've recently updated or recently installed.
4. **Contact your web hosting provider** as they commonly have experience dealing with problems with WP-Cron. They'll often recommend setting up a server-level cron job to trigger the WP-Cron event runner.
5. Read the sections below on how to **set up a server-level cron job** or **reliably run WP-Cron events at a large scale**.

## Articles with more information

* [WordPress Handbook: Hooking WP-Cron Into the System Task Scheduler](https://developer.wordpress.org/plugins/cron/hooking-wp-cron-into-the-system-task-scheduler/)
* [SpinupWP: Understanding WP-Cron](https://spinupwp.com/doc/understanding-wp-cron/)

## Host-specific instructions

* [**SiteGround**: How to replace WP-Cron with a real cron job](https://www.siteground.com/tutorials/wordpress/real-cron-job/)
* [**Pantheon**: Configuring and optimizing the WP-Cron feature](https://pantheon.io/docs/wordpress-cron)
* [**WP Engine**: WP-Cron and WordPress event scheduling](https://wpengine.co.uk/support/wp-cron-wordpress-scheduling/)
* [**Kinsta**: How to Disable WP-Cron for Faster Performance](https://kinsta.com/knowledgebase/disable-wp-cron/)
* [**DreamHost**: Disabling WP-Cron to Improve Overall Site Performance](https://help.dreamhost.com/hc/en-us/articles/360048323291-Disabling-WP-CRON-to-Improve-Overall-Site-Performance)
* [**Rocket.net**: How Do I Disable WP-Cron In WordPress?](https://rocket.net/blog/how-do-i-disable-wp-cron-in-wordpress/)
* [**Altis**](https://www.altis-dxp.com/): No need to do anything, [cron is handled by Cavalcade](https://www.altis-dxp.com/how-wordpress-and-altis-handle-scheduled-tasks-with-wp-cron/)
* [**WordPress.com VIP**](https://wpvip.com/): No need to do anything, [cron is handled by Cron Control](https://docs.wpvip.com/technical-references/tools-for-site-management/cron-control/)

## Running events via WP-CLI

If you have access to [WP-CLI](https://wp-cli.org/) and Crontab on your server, you can set up a real schedule in Crontab and use it to run all pending WP-Cron events via WP-CLI. Here are some articles on this topic:

* [Better wp-cron using linux's crontab](https://easyengine.io/tutorials/wordpress/wp-cron-crontab/)
* [How to run WordPress cron with WP CLI via crontab](https://silicondales.com/tutorials/linux/how-to-run-wordpress-cron-with-wp-cli-via-crontab-on-cloudways/)

## WP-Cron at scale

If you need to process a large number of cron events, your events are long-running, you need parallel processing, or you require high reliability for your cron events, you should consider one of the following:

* [Cavalcade](https://github.com/humanmade/Cavalcade)
* [Cron Control](https://github.com/Automattic/Cron-Control)

## Background processing

If you need to perform long-running actions in a background process, try one of these:

* [Action Scheduler](https://actionscheduler.org/)
* [WP Queued Jobs](https://github.com/SebKay/wp-queued-jobs)

If you need an approach that doesn't use WP-Cron, try one of these:

* [WP Background Processing](https://github.com/deliciousbrains/wp-background-processing)
* [TLC Transients](https://github.com/markjaquith/WP-TLC-Transients)
* [Async Transients](https://github.com/10up/Async-Transients)
* [DFM Transients](https://github.com/dfmedia/DFM-Transients)
