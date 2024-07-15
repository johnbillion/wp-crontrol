# PHP default timezone is not set to UTC

If you're seeing an error message saying **PHP default timezone is not set to UTC** it means the timezone that PHP uses on your server has been changed from UTC to another timezone. This is a misconfiguration that you'll need to fix.

## Why is this a problem?

The default timezone used by PHP should not be changed even when a non-UTC timezone is selected on the General Settings screen in WordPress. Doing so can cause timezone offset calculations in WordPress, plugins, and themes to be doubled up or cancelled out.

WP Crontrol highlights this problem because it can cause:

* Cron events to be missed completely or incorrectly scheduled
* Scheduled posts to be published at the wrong time or missed completely
* Cron events that you edit to use the wrong timezone

This is _not_ a problem specific to WP Crontrol, and its effects are not confined to cron events. WordPress itself highlights this as a problem on the Site Health screen.

## What's the cause?

Almost certainly the cause is some code in a plugin, a theme, or in `wp-config.php` which is calling the `date_default_timezone_set` function with a timezone value other than UTC.

## How do I fix it?

You'll need to identify where the call to `date_default_timezone_set` is and remove it, after determining what other functionality on your site might be relying on its value. How you do that depends entirely on your site and is outside the scope of this help page.

## Further reading

* [Timezone documentation from WordPress VIP](https://wpvip.com/documentation/vip-go/use-current_time-not-date_default_timezone_set/)
* [Date/Time component improvements in WordPress 5.3](https://make.wordpress.org/core/2019/09/23/date-time-improvements-wp-5-3/)
