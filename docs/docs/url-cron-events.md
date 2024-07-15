# URL cron events

::: tip New
This feature is new in WP Crontrol 1.17
:::

WP Crontrol allows you to create events in the WP-Cron system that send an HTTP request to a URL. This is a convenience wrapper around functionality that you would otherwise need to write PHP in order to achieve.

When the cron event runs, a request is sent by your server to the provided URL using the WordPress HTTP API.

## How do I create a cron event that sends a request to a URL?

From the Tools → Cron Events menu, click Add New Cron Event. Select the "URL cron event" option, fill out the details as required, and press the "Add Event" button.

## Which HTTP method should I use?

If you're not sure what an HTTP method is, then stick with the default `GET` method.

## Can I send headers or a body with the request?

Not yet. These features might be added in the future.

## Can I do something with the response?

If the request fails or it responds with an HTTP status code outside the 2xx range then a PHP exception will be thrown. These exceptions can be seen in the PHP error log on your server.

Otherwise, this feature is only intended for sending a request. If you need to do something with the response then you'll need to use a regular cron event (or a PHP cron event) and write the PHP that performs the logic that you need.

## Can URL cron events be tampered with?

The URL that's saved in a URL cron event is protected with an integrity check which prevents it from being fetched if the URL is tampered with.

URL cron events are secured via an integrity check that makes use of an HMAC to store a hash of the URL alongside it when the event is saved. When the event runs, the hash is checked to ensure the integrity of the URL and confirm that it has not been tampered with. WP Crontrol will not fetch the URL if the hashes do not match or if a stored hash is not present.

If an attacker with database-level access were to modify the URL in an event in an attempt to fetch an arbitrary URL (for example to perform an SSRF), the HTTP request would not be performed.

The same anti-tampering feature protects [PHP cron events](/docs/php-cron-events/) too.

## Why do I see "Needs checking" next to my cron events?

If WP Crontrol is showing you a message saying your URL or PHP cron events need to be checked, this could either mean there is a real problem caused by tampering of the events, or it could be caused by the security salts on your site having been changed.

[See here for complete information about cron events which show "Needs checking"](/help/check-cron-events/).
