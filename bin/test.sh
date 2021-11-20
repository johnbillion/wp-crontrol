#!/usr/bin/env bash

# -e          Exit immediately if a pipeline returns a non-zero status
# -o pipefail Produce a failure return code if any command errors
set -eo pipefail

# Specify the directory where the WordPress installation lives:
WP_CORE_DIR="${PWD}/tests/wordpress"

# Specify the URL for the site:
WP_URL="localhost:8000"

# Shorthand:
WP="./vendor/bin/wp --color --path=$WP_CORE_DIR --url=http://$WP_URL"

# Start the PHP server:
php -S "$WP_URL" -t "$WP_CORE_DIR" -d disable_functions=mail 2>/dev/null &

# Reset or install the test database:
$WP db reset --yes

# Install WordPress:
$WP core install --title="Example" --admin_user="admin" --admin_password="admin" --admin_email="admin@example.com"

# Run the functional tests:
./vendor/bin/codecept run "$1" \
	|| TESTS_EXIT_CODE=$? && kill $! && exit $TESTS_EXIT_CODE

# Stop the PHP web server:
kill $!
