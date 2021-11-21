#!/usr/bin/env bash

# -e          Exit immediately if a pipeline returns a non-zero status
# -o pipefail Produce a failure return code if any command errors
set -eo pipefail

set -o allexport
source ./tests/.env
set +o allexport

# Specify the directory where the WordPress installation lives:
WP_CORE_DIR="${PWD}/${WP_ROOT_FOLDER}"

# Shorthand:
WP="./vendor/bin/wp --color --path=$WP_CORE_DIR --url=http://$TEST_SITE_WP_DOMAIN"

# Reset or install the test database:
$WP db reset --yes

# Install WordPress:
$WP core install --title="Example" --admin_user="${TEST_SITE_ADMIN_USERNAME}" --admin_password="${TEST_SITE_ADMIN_PASSWORD}" --admin_email="${TEST_SITE_ADMIN_EMAIL}"

# Run the functional tests:
./vendor/bin/codecept run --steps "$1"
