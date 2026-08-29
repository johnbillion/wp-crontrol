<?php

// The autofocus timer on wp-login.php races Playwright filling in the login form.
add_filter( 'enable_login_autofocus', '__return_false' );
