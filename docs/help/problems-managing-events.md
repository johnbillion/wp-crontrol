# Problems adding or editing WP Cron events

Unfortunately the WP-Cron system in WordPress isn't totally reliable when editing, deleting, or adding new events, particularly on a site with many events so that one may be running right around the time you save your event, and on sites with a persistent object cache.

This problem is not specific to WP Crontrol and affects any WP-Cron management plugin.

If you try to add, delete, or edit a cron event and the changes aren't saved, there are a few things you can try to fix the issue.

## How can I fix this?

* Ensure you're running WordPress 5.7 or later so WP Crontrol can show you more specific error messages. Prior to 5.7 there wasn't a way for cron management plugins to know the details about every error.
* Try again. Often the problem is sporadic and making the change a second time will work. For example this problem can occur if you make a change right at the time when a cron event is being run by WordPress.
* Ensure the event is not scheduled within 10 minutes of another event with the same hook. If the event is not a recurring event, WordPress core will block this "duplicate" event and the error message may not indicate this.
* Try deactivating any plugins that provide a persistent object cache, for example Redis or Memcached. This is not ideal of course, but it can help you get to the root of the problem.
* Read through the [Cron events that have missed their schedule](/help/missed-cron-events/) page. Much of the information there applies to creating and editing events too.
