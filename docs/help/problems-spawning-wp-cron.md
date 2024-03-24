# Problems with spawning a call to the WP Cron system

If you're seeing an error message saying **there was a problem spawning a call to the WP-Cron system on your site**, this indicates a problem that is preventing cron events from running. If you can see cron events that have missed their schedule by more than a few minutes, this is almost certainly a problem you need to investigate.

## What should I do?

Firstly, **please do not open a support thread on the WP Crontrol plugin forum** as nobody will be able to assist you there. There are better places and ways to get support for this problem.

1. **Reload the page a few times** to determine if the error is persistent. If the error only appears once then it may have been a temporary network connectivity problem.
2. If the error persists, make a note of the error message. **Common errors are listed below** with more information about what they mean.
3. Try **deactivating the other plugins on your site one by one**. It could be that one of them is causing a problem.
4. If your website is hosted on a managed WordPress service, **contact your web host** and send them the error message. They are best positioned to assist you and they've probably seen the same problem before.
5. If you're not using a managed WordPress hosting service, try **searching for the error message** using your favourite search engine or on the support forums of your web host.
6. If you cannot diagnose the problem, post the error message in a new thread on **the main wordpress.org support forums** where hopefully a volunteer can assist you.

## Common errors

### cURL error 6: Could not resolve domain

This means there is a problem with the DNS configuration of your domain or your server. The domain name is not pointing to a valid IP address, or your server does not have up to date DNS information.

### cURL error 7: Failed to connect: Connection refused

This is a bit of a mystery error. It could relate to your HTTPS configuration, or it could be an access restriction problem (see below). This may be a temporary network connectivity problem.

### cURL error 28: Operation timed out after 3000 milliseconds with 0 bytes received

This means there is a network connectivity problem preventing your server from performing "loopback" requests to itself.

### cURL error 35: sslv3 alert handshake failure

This means there's a problem with your HTTPS configuration. Your server cannot securely connect to itself.

### Unexpected HTTP response code: 401 or 403

This means an access control restriction such as BasicAuth, a firewall, a security or privacy plugin, some form of password protection, or an `.htaccess` rule is preventing your server from accessing `wp-cron.php`.

### Unexpected HTTP response code: 404

This means the `wp-cron.php` file in the root of your website has been deleted. Try reinstalling WordPress from the Dashboard → Updates screen.

### Unexpected HTTP response code: 500 or higher

This means an error has occurred on your server which is preventing the cron spawner from running. Check the error logs on your server.
