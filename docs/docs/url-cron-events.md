# URL cron events

::: tip New
This feature is new in WP Crontrol 1.17
:::

WP Crontrol includes a feature that allows users to create events in the WP-Cron system that request a URL. This is a convenience wrapper around functionality that you would otherwise need to write PHP in order to achieve.

## How do I create a cron event that requests a URL?

From the Tools → Cron Events → Add New Cron Event screen, select the "Request a URL" option under the "Event Type" list. Fill out the rest of the details as required and press the "Add Event" button.

## Which HTTP method should I use?

If you're not sure what an HTTP method is, then stick with the default `GET` method.

## Can I send headers or a body with the request?

Not yet. These features might be added in the future.

## Can I do something with the response?

No, this feature is only for sending a request. If you need to do something with the response then you'll need to use a regular cron event (or a PHP cron event) and write the PHP that performs the logic that you need.
