# WP Crontrol

[![](https://img.shields.io/wordpress/plugin/installs/wp-crontrol?style=flat-square)](https://wordpress.org/plugins/wp-crontrol/)
[![](https://img.shields.io/github/actions/workflow/status/johnbillion/wp-crontrol/acceptance-tests.yml?branch=develop&style=flat-square)](https://github.com/johnbillion/wp-crontrol/actions)

WP Crontrol enables you to take control of the scheduled cron events on your WordPress website or WooCommerce store. From the admin screens you can:

 * View all scheduled cron events along with their arguments, schedule, callback functions, and when they are next due.
 * Edit, delete, pause, resume, and immediately run cron events.
 * Add new cron events.
 * Bulk delete cron events.
 * Add and remove custom cron schedules.
 * Export and download cron event lists as a CSV file.

WP Crontrol is aware of timezones, will alert you to events that have no actions or that have missed their schedule, and will show you a helpful warning message if it detects any problems with your cron system.

## Usage

1. Go to the `Tools → Cron Events` menu to manage cron events.
2. Go to the `Settings → Cron Schedules` menu to manage cron schedules.

## User Permissions

WP Crontrol uses fine-grained capabilities for managing different aspects of cron events:

* `view_cron_events` - View the list of cron events
* `edit_cron_event` - Edit existing cron events
* `delete_cron_event` - Delete cron events
* `create_cron_event` - Add new standard cron events
* `create_url_cron_event` - Add new URL cron events
* `create_php_cron_event` - Add new PHP cron events
* `run_cron_event` - Run cron events manually
* `pause_cron_event` - Pause or resume cron events
* `export_cron_events` - Export cron events

By default, all of these capabilities are granted to users with the `manage_options` capability (typically Administrators). The `create_php_cron_event` capability requires the `edit_files` capability instead.

### Customizing Capabilities

Developers can use the `user_has_cap` filter to customize these capabilities. For example, to allow only Network Admins to manage cron events on a multisite installation:

```php
add_filter( 'user_has_cap', function( $user_caps, $required_caps, $args, $user ) {
    $cron_caps = [
        'view_cron_events',
        'edit_cron_event',
        'delete_cron_event',
        'create_cron_event',
        'create_url_cron_event',
        'run_cron_event',
        'pause_cron_event',
        'export_cron_events'
    ];

    if ( in_array( $args[0], $cron_caps, true ) && user_can( $user, 'manage_network_options' ) ) {
        $user_caps[ $args[0] ] = true;
    }

    return $user_caps;
}, 10, 4 );
```

## Documentation

[Extensive documentation on how to use WP Crontrol and how to get help for error messages that it shows is available on the WP Crontrol website](https://wp-crontrol.com/docs/how-to-use/).

## Frequently Asked Questions

[See the FAQ on the WordPress.org plugin page for WP Crontrol](https://wordpress.org/plugins/wp-crontrol/#faq).

## Other Plugins

I maintain several other plugins for developers. Check them out:

* [Query Monitor](https://wordpress.org/plugins/query-monitor/) is the developer tools panel for WordPress.
* [User Switching](https://wordpress.org/plugins/user-switching/) provides instant switching between user accounts in WordPress.

## Privacy Statement

WP Crontrol is private by default and always will be. It does not send data to any third party, nor does it include any third party resources. [WP Crontrol's full privacy statement can be found here](https://wp-crontrol.com/privacy/).

## Accessibility Statement

WP Crontrol aims to be fully accessible to all of its users. [WP Crontrol's full accessibility statement can be found here](https://wp-crontrol.com/accessibility/).

## Sponsors

<p align="center">The time that I spend maintaining this plugin and others is in part sponsored by:</p>

<p align="center"><a href="https://automattic.com"><img src="https://cdn.jsdelivr.net/gh/johnbillion/johnbillion@latest/assets/sponsors/automattic.svg" alt="Automattic" width="50%"></a></p>

<p align="center"><a href="https://servmask.com"><img src="https://cdn.jsdelivr.net/gh/johnbillion/johnbillion@latest/assets/sponsors/servmask.svg" alt="ServMask" width="25%"></a></p>

<p align="center">Plus all my kind sponsors on GitHub:</p>

<p align="center"><a href="https://github.com/sponsors/johnbillion"><img src="https://cdn.jsdelivr.net/gh/johnbillion/johnbillion@latest/sponsors.svg" alt="Sponsors"></p>

<p align="center"><a href="https://github.com/sponsors/johnbillion">Click here to find out about supporting my open source tools and plugins</a>.</p>

## Screenshots

1. Cron events can be modified, deleted, and executed<br>![](.wordpress-org/screenshot-1.png)

2. New cron events can be added<br>![](.wordpress-org/screenshot-2.png)

3. New cron schedules can be added, giving plugin developers more options when scheduling events<br>![](.wordpress-org/screenshot-3.png)
