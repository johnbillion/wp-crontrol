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

## Can URL cron events be tampered with?

The URL that's saved in a URL cron event is protected with an integrity check which prevents it from being fetched if the URL is tampered with.

URL cron events are secured via an integrity check that makes use of an HMAC to store a hash of the URL alongside it when the event is saved. When the event runs, the hash is checked to ensure the integrity of the URL and confirm that it has not been tampered with. WP Crontrol will not fetch the URL if the hashes do not match or if a stored hash is not present.

If an attacker with database-level access were to modify the URL in an event in an attempt to fetch an arbitrary URL (for example to perform an SSRF), the HTTP request would not be performed.

The same anti-tampering feature protects [PHP cron events](/docs/php-cron-events/) too.

## Why do I see "Needs checking" next to my cron events?

If WP Crontrol is showing you a message saying your URL or PHP cron events need to be checked, this could either mean there is a real problem caused by tampering of the events, or it could be caused by your security salts having been changed.

[See here for complete information about cron events which show "Needs checking"](/help/check-cron-events/).
