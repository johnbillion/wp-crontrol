# PHP cron events

WP Crontrol includes a feature that allows administrative users to create events in the WP-Cron system that execute PHP code, subject to the restrictive security permissions documented below.

## Which users can manage PHP cron events?

Only users with the `edit_files` capability can manage PHP cron events in WP Crontrol. If a user does not have the capability to edit files in the WordPress admin area -- i.e. via the Plugin Editor or Theme Editor menu -- then they cannot add, edit, or delete a PHP cron event.

By default only Administrators have this capability, and with Multisite enabled only Super Admins have this capability.

If file editing has been disabled via [the `DISALLOW_FILE_MODS` or `DISALLOW_FILE_EDIT` configuration constants in WordPress](https://developer.wordpress.org/apis/wp-config-php/#disable-the-plugin-and-theme-file-editor) then adding, editing, or deleting a PHP cron event will not be permitted.

## Is this feature dangerous?

The user capability required to create and execute PHP code via WP Crontrol exactly matches that which is required to edit PHP files in a standard WordPress installation. Therefore, the user access level required to execute arbitrary PHP code does not change with WP Crontrol activated.

If you wish to prevent PHP cron events from being added or edited on your site then you can [add the `DISALLOW_FILE_EDIT` configuration constant to your `wp-config.php` file](https://developer.wordpress.org/apis/wp-config-php/#disable-the-plugin-and-theme-file-editor).

## How do I create a new PHP cron event?

In the Tools → Cron Events admin panel, click on "Add New". In the form that appears, select "PHP Cron Event" and enter the schedule and next run time. In the "Hook code" area, enter the PHP code that should be run when your cron event is executed. Don't include the PHP opening tag (`<?php`).

## Can I "lock" a PHP cron event so that other users cannot edit it?

Yes. You can create or edit a PHP cron event, save it, and then set the `DISALLOW_FILE_EDIT` constant to true (as documented above) to prevent further changes to the event from the Cron Events admin panel. The event will continue to run at its scheduled interval as expected.

If you need to edit the event in the future, you can temporarily remove the `DISALLOW_FILE_EDIT` constant, make your required changes to the event, and then restore the constant to re-lock it.

## How can I create a cron event that requests a URL?

From the Tools → Cron Events → Add New screen, create a PHP cron event that includes PHP that fetches the URL using the WordPress HTTP API. For example:

```php
wp_remote_get( 'http://example.com' );
```
