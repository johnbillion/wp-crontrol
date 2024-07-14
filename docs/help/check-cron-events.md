# Checking PHP and URL cron events

In WP Crontrol version 1.16.2 a security hardening feature was added which prevents the code in a PHP cron event from being tampered with. [You can read about the feature here](/docs/php-cron-events/).

If you have PHP cron events in place prior to upgrading to this version then *you will need to re-save your PHP cron events* so the required security hash can be generated and stored alongside the PHP code and the events can continue functioning.

In WP Crontrol version 1.17 the same security feature was added to the new URL. [You can read about the feature here](/docs/php-cron-events/).

If you don't have any PHP cron events in place on your site then you don't need to do anything.

## What do I need to do?

1. Visit the Tools → Cron Events screen in the admin area of your site
2. Click the "PHP events" filter at the top of the list

If you don't have any PHP cron events on your site then this filter won't appear. You don't need to do anything further as your site is unaffected by this change.

If you do have PHP cron events in place on your site:

4. Click the "Check and edit" link below each event that shows a "Needs checking" message
5. Confirm that the PHP code in the event is as expected
6. Click the "Update Event" button

Repeat this for any further PHP cron events that show a "Needs checking" message.

## Will I need to do this again?

This is a one-off action that's required after updating to version 1.16.2 or later from a prior version.

## What if I no longer need one of the affected events?

You can delete an unwanted event by clicking the "Delete" link below the event.

## What if I don't have permission to edit a PHP cron event?

[See here for full information about PHP cron events](/docs/php-cron-events/). You'll need to temporarily enable the ability to edit PHP cron events in order to save the affected events.

## What if the "Needs checking" message appears again?

If WP Crontrol is showing you a message saying one or more of your URL or PHP cron events need to be checked and you haven't just updated WP Crontrol from a version prior to 1.16.2, then it could either mean there is a real problem caused by tampering of the events, or it could be caused by your security salts having been changed.

If the PHP code in an event *has* been tampered with externally then WP Crontrol will refuse to execute the PHP code when the event runs in order to keep your site secure. You should carefully and fully check the PHP code in the affected events. [Consult the "My site was hacked" page on the WordPress.org documentation site if you find unexpected code](https://wordpress.org/documentation/article/faq-my-site-was-hacked/).

If the security salts in the `wp-config.php` file on your site have been changed then this invalidates the stored hashes for the PHP code in all of your PHP cron events. Note that changing or rotating the security salts on your site will have several effects, including but not limited to:

  - Logging out all users and invalidating all security nonces
  - Invalidating hashes used within functionality such as comment moderation, Customizer controls, and widget management

Your security salts may also change if you move your site from one server to another and the salts in the `wp-config.php` file differ, or if you move your site from a staging environment to a production environment (or vice versa) and the salts differ.

In all of these cases, you should edit the affected PHP cron events, make absolutely sure that the code remains as intended, and save it again. This will cause the hash to be regenerated and saved alongside the PHP code, and the event will begin working as expected once again.
