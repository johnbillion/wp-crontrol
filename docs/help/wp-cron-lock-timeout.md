# This interval is less than the WP_CRON_LOCK_TIMEOUT constant

If you're seeing an error message saying **This interval is less than the WP_CRON_LOCK_TIMEOUT constant** it means the interval at which events with this schedule are _due_ to run is less than the interval at which WordPress core _actually_ runs events.

## Why is this a problem?

While WordPress core runs a cron event it prevents further events from running to avoid multiple processes accidentally running the same event twice. The maximum duration of this "lock" is set by the `WP_CRON_LOCK_TIMEOUT` constant which by default is set to 60 seconds.

If an event takes longer than this duration to run, and events are scheduled with an interval of less than this duration, then those events will not run according to their schedule. They will run late.

## Do I need to fix this?

You may not need to fix anything, but you should remain aware of the potential late-running problem above. If you notice events with this short interval are missing their schedules then you may need to address it.

## How do I fix this?

Firstly, take a look at the [Cron events that have missed their schedule](https://wp-crontrol.com/help/missed-cron-events/) page, there's lots of useful information on there.

Secondly, you should either adjust the interval at which the cron events run so they run less frequently, or decrease the value of `WP_CRON_LOCK_TIMEOUT`. The effects of changing this constant are a bit beyond the scope of this document but I'll try to add some more info here in the future.
