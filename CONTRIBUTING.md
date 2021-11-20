# Contributing to WP Crontrol

Bug reports, code contributions, and general feedback are very welcome. These should be submitted through [the GitHub repository](https://github.com/johnbillion/wp-crontrol). Development happens in the `develop` branch, and any pull requests should be made against that branch please.

* [Reviews on WordPress.org](#reviews-on-wordpressorg)
* [Reporting Security Issues](#reporting-security-issues)
* [Inclusivity and Code of Conduct](#inclusivity-and-code-of-conduct)
* [Setting up Locally](#setting-up-locally)
* [Running the Tests](#running-the-tests)
* [Releasing a New Version](#releasing-a-new-version)

## Reviews on WordPress.org

If you enjoy using WP Crontrol I would greatly appreciate it <a href="https://wordpress.org/support/plugin/wp-crontrol/reviews/">if you left a positive review on the WordPress.org Plugin Directory</a>. This is the fastest and easiest way to contribute to WP Crontrol 😄.

## Reporting Security Issues

If you discover a security issue in WP Crontrol, please report it to [the security program on HackerOne](https://hackerone.com/johnblackbourn). Do not report security issues on GitHub or the WordPress.org support forums. Thank you.

## Inclusivity and Code of Conduct

Contributions to WP Crontrol are welcome from anyone. Whether you are new to Open Source or a seasoned veteran, all constructive contribution is welcome and I'll endeavour to support you when I can.

This project is released with <a href="https://github.com/johnbillion/wp-crontrol/blob/develop/CODE_OF_CONDUCT.md">a contributor code of conduct</a> and by participating in this project you agree to abide by its terms. The code of conduct is nothing to worry about, if you are a respectful human being then all will be good.

## Setting up Locally

You can clone this repo and activate it like a normal WordPress plugin. If you want to contribute to WP Crontrol, you should install the developer dependencies in order to run the tests.

### Prerequisites

* [Composer](https://getcomposer.org/)
* [Node](https://nodejs.org/)

### Setup

1. Install the PHP dependencies:

       composer install

2. Install the Node dependencies:

       npm install

## Running the Tests

To run the whole test suite which includes PHPCS code sniffs, PHPStan static analysis, and WPBrowser functional tests:

	composer test

To run just the code sniffs:

	composer test:cs

To run just the static analysis:

	composer test:phpstan

To run just the functional tests:

	composer test:ft

## Releasing a New Version

WP Crontrol gets automatically deployed to the WordPress.org Plugin Directory whenever a new release is published on GitHub.

Assets such as screenshots and banners are stored in the `.wordpress-org` directory. These get deployed as part of the automated release process too.

In order to deploy only changes to assets, push the change to the `deploy` branch and they will be deployed if they're the only changes in the branch since the last release. This allows for the "Tested up to" value to be bumped as well as assets to be updated in between releases.
