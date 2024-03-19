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

In the Tools → Cron Events admin panel, click on "Add New". In the form that appears, select "PHP cron event" and enter the schedule and next run time. In the "Hook code" area, enter the PHP code that should be run when your cron event is executed. Don't include the PHP opening tag (`<?php`).

## Can I "lock" PHP cron events so that other users cannot edit them?

Yes. You can create or edit a PHP cron event, save it, and then define either the `DISALLOW_FILE_MODS` or `DISALLOW_FILE_EDIT` configuration constant as documented above to prevent further changes to the event from the Cron Events admin panel. The event will continue to run at its scheduled interval as expected but it will not be editable.

If you need to edit the event in the future, you can temporarily remove the relevant configuration constant, make your required changes to the event, and then reinstate the constant to re-lock it.

## How can I create a cron event that requests a URL?

From the Tools → Cron Events → Add New screen, create a PHP cron event that includes PHP that fetches the URL using the WordPress HTTP API. For example:

```php
wp_remote_get( 'http://example.com' );
```

## Can the code in PHP cron events be tampered with?

The PHP code that's saved in a PHP cron event is protected with an integrity check which prevents it from being executed if the code is tampered with.

At the point where the PHP cron event gets saved, the PHP code is hashed and this hash is stored alongside the event. Before the PHP code gets executed when the event runs, the hash is checked to ensure the integrity of the PHP code and confirm that it has not been tampered with. WP Crontrol will not execute PHP that does not pass the integrity check, and will show you a message on the Cron Events listing screen in the admin area asking you to check the event.

This process prevents an attacker with database-level access from modifying the PHP code in order to execute arbitrary code.

## Why do I see "Needs checking" next to my PHP cron events?

The integrity checking mechanism documented above was introduced in WP Crontrol 1.16.2 in March 2024. If you have PHP cron events stored on your site prior to updating to this version or later then you'll need to check and re-save your PHP cron events so the integrity hash can be generated and saved alongside the PHP code.

Otherwise, if WP Crontrol is showing you a message saying your PHP cron events need to be checked, this could either mean there is a real problem caused by tampering of the PHP code in the events, or it could be caused by your security salts having been changed.

If the PHP code in an event *has* been tampered with externally then WP Crontrol will refuse to execute the PHP code when the event runs in order to keep your site secure. You should carefully and fully check the PHP code in the affected events. [Consult the "My site was hacked" page on the WordPress.org documentation site if you find unexpected code](https://wordpress.org/documentation/article/faq-my-site-was-hacked/).

If the security salts in the `wp-config.php` file on your site have been changed then this invalidates the stored hashes for the PHP code in all of your PHP cron events. Note that changing or rotating the security salts on your site will have several effects, including but not limited to:

  - Logging out all users and invalidating all security nonces
  - Invalidating hashes used within functionality such as comment moderation, Customizer controls, and widget management

Your security salts may also change if you move your site from one server to another and the salts in the `wp-config.php` file differ, or if you move your site from a staging environment to a production environment (or vice versa) and the salts differ.

In all of these cases, you should edit the affected PHP cron events, make absolutely sure that the code remains as intended, and save it again. This will cause the integrity hash to be regenerated and saved alongside the PHP code, and the event will begin working as expected once again.
