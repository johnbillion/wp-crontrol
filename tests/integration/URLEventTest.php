<?php declare(strict_types = 1);

namespace Crontrol\Tests;

use Crontrol\Event\URLCronEvent;
use Crontrol\Exception\MissingURLException;
use Crontrol\Exception\MissingHashException;
use Crontrol\Exception\InvalidHashException;
use Crontrol\Exception\HTTPFailedException;

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

}
