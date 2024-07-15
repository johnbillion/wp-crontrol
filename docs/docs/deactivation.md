# What happens if I deactivate the WP Crontrol plugin?

If you deactivate or delete the WP Crontrol plugin, your existing cron events will remain in place and will mostly continue to run as they would normally, with some exceptions detailed below.

## Paused cron hooks

Any cron hooks that you've paused through WP Crontrol will be resumed because the paused functionality is provided by WP Crontrol. If you reactivate WP Crontrol, they will become paused again.

## URL cron events and PHP cron events

If you've created a URL cron event or PHP cron event with WP Crontrol, these events will remain in place after you deactivate WP Crontrol but they will _cease to operate_ because these events are processed by WP Crontrol. If you reactivate WP Crontrol, they will resume operating as normal.

## Custom schedules

If you've created a custom cron schedule from the Settings → Cron Schedules screen, these schedules will no longer be available for use by cron events on your site because they get added by WP Crontrol.

Any cron event which uses a custom schedule will run at its next scheduled time as normal but will _not_ subsequently get rescheduled and will disappear.

If you reactivate WP Crontrol, your custom schedules will become available for use again.

## Edits to events

If you've edited a cron event and changed its arguments, next run time, or schedule, these changes will persist because this data is stored in the standard WP-Cron system in WordPress.
