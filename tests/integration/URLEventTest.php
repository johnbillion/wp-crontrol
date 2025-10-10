<?php declare(strict_types = 1);

namespace Crontrol\Tests;

use Crontrol\Event\URLCronEvent;
use Crontrol\Exception\MissingURLException;
use Crontrol\Exception\MissingHashException;
use Crontrol\Exception\InvalidHashException;
use Crontrol\Exception\HTTPFailedException;
use Crontrol\Exception\UnexpectedHTTPCodeException;

class URLEventTest extends Test {
	public function testMissingURLTriggersException(): void {
		$this->expectException( MissingURLException::class );

		do_action(
			URLCronEvent::HOOK_NAME,
			[]
		);
	}

	public function testMissingHashTriggersException(): void {
		$this->expectException( MissingHashException::class );

		do_action(
			URLCronEvent::HOOK_NAME,
			[
				'url' => 'http://example.com',
				'method' => 'GET',
			]
		);
	}

	public function testInvalidHashTriggersException(): void {
		$this->expectException( InvalidHashException::class );

		do_action(
			URLCronEvent::HOOK_NAME,
			[
				'url' => 'http://example.com',
				'method' => 'GET',
				'hash' => 'invalidhash',
			]
		);
	}

	public function testInvalidURLTriggersException(): void {
		$this->expectException( HTTPFailedException::class );

		$url = 'http://localhost:22';

		do_action(
			URLCronEvent::HOOK_NAME,
			[
				'url' => $url,
				'method' => 'GET',
				'hash' => wp_hash( $url ),
			]
		);
	}

	public function test404ResponseTriggersException(): void {
		$this->expectException( UnexpectedHTTPCodeException::class );

		$url = 'http://httpbin/status/404';

		do_action(
			URLCronEvent::HOOK_NAME,
			[
				'url' => $url,
				'hash' => wp_hash( $url ),
			]
		);
	}

	public function testSuccesfulRequestWorksAsExpected(): void {
		$url = 'http://httpbin/status/200';

		/**
		 * @param array|WP_Error $response    HTTP response or WP_Error object.
		 * @param string         $context     Context under which the hook is fired.
		 * @param string         $class       HTTP transport used.
		 * @param array          $parsed_args HTTP request arguments.
		 * @param string         $url         The request URL.
		 */
		add_action( 'http_api_debug', function( $response, $context, $class, $args, $url_called ) use ( $url ) {
			self::assertSame( $url, $url_called );
			self::assertNotWPError( $response );
			self::assertSame( 200, wp_remote_retrieve_response_code( $response ) );
		}, 10, 5 );

		do_action(
			URLCronEvent::HOOK_NAME,
			[
				'url' => $url,
				'hash' => wp_hash( $url ),
			]
		);
	}
}
