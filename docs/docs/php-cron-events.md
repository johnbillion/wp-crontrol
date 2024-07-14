# PHP cron events

WP Crontrol includes a feature that allows administrative users to create events in the WP-Cron system that execute PHP code, subject to the restrictive security permissions documented below.

## Which users can manage PHP cron events?

Only users with the `edit_files` capability can manage PHP cron events in WP Crontrol. If a user does not have the capability to edit files in the WordPress admin area -- i.e. via the Plugin Editor or Theme Editor menu -- then they cannot add, edit, or delete a PHP cron event.

By default only Administrators have this capability, and with Multisite enabled only Super Admins have this capability.

If file editing has been disabled via [either the `DISALLOW_FILE_MODS` or `DISALLOW_FILE_EDIT` configuration constant in WordPress](https://developer.wordpress.org/apis/wp-config-php/#disable-the-plugin-and-theme-file-editor) then adding, editing, or deleting a PHP cron event will not be permitted.

## Is this feature dangerous?

The user capability required to create, edit, and execute PHP code via WP Crontrol exactly matches that which is required to edit PHP files in a standard WordPress installation. Therefore, the user access level required to execute arbitrary PHP code does not change with WP Crontrol activated.

If you wish to prevent PHP cron events from being added or edited on your site then you can [define either the `DISALLOW_FILE_MODS` or `DISALLOW_FILE_EDIT` configuration constant in your `wp-config.php` file](https://developer.wordpress.org/apis/wp-config-php/#disable-the-plugin-and-theme-file-editor).

## How do I create a new PHP cron event?

In the Tools → Cron Events admin panel, click on "Add New Cron Event". In the form that appears, select the "PHP cron event" option under the "Event Type" list and enter the schedule and next run time. In the "PHP Code" area, enter the PHP code that should be run when your cron event is executed. Don't include the PHP opening tag (`<?php`).

## Can I "lock" PHP cron events so that other users cannot edit them?

Yes. You can create or edit a PHP cron event, save it, and then define either the `DISALLOW_FILE_MODS` or `DISALLOW_FILE_EDIT` configuration constant as documented above to prevent further changes to the event from the Cron Events admin panel. The event will continue to run at its scheduled interval as expected but it will not be editable.

If you need to edit the event in the future, you can temporarily remove the relevant configuration constant, make your required changes to the event, and then reinstate the constant to re-lock it.

## How can I create a cron event that requests a URL?

You don't need to use a PHP cron event for this. From the Tools → Cron Events → Add New Cron Event screen, select the "Request a URL" option under the "Event Type" list. Fill out the rest of the details as required and press the "Add Event" button.

## Can the code in PHP cron events be tampered with?

The PHP code that's saved in a PHP cron event is protected with an integrity check which prevents it from being executed if the code is tampered with.

PHP cron events are secured via an integrity check that makes use of an HMAC to store a hash of the PHP code alongside the code when the event is saved. When the event runs, the hash is checked to ensure the integrity of the PHP code and confirm that it has not been tampered with. WP Crontrol will not execute the PHP code if the hashes do not match or if a stored hash is not present.

If an attacker with database-level access were to modify the PHP code in an event in an attempt to execute arbitrary code, the code would no longer execute.

The same anti-tampering feature protects [URL cron events](/docs/url-cron-events/) too.

## Why do I see "Needs checking" next to my cron events?

The integrity checking mechanism documented above was introduced in WP Crontrol 1.16.2 in March 2024. If you have PHP cron events stored on your site prior to updating to this version or later then you'll need to check and re-save your PHP cron events so the hash can be generated and saved alongside the PHP code.

Otherwise, if WP Crontrol is showing you a message saying your PHP or URL cron events need to be checked, this could either mean there is a real problem caused by tampering of the events, or it could be caused by your security salts having been changed.

[See here for complete information about cron events which show "Needs checking"](/help/check-cron-events/).
