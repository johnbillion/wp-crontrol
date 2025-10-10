<?php declare(strict_types = 1);

namespace Crontrol\Tests;

use Crontrol\Event\PHPCronEvent;
use Crontrol\Exception\MissingHashException;
use Crontrol\Exception\InvalidHashException;

class PHPEventTest extends Test {
	public function testMissingHashTriggersException(): void {
		$this->expectException( MissingHashException::class );

		do_action(
			PHPCronEvent::HOOK_NAME,
			[
				'code' => 'echo "Hello, World!";',
			]
		);
	}

	public function testInvalidHashTriggersException(): void {
		$this->expectException( InvalidHashException::class );

		do_action(
			PHPCronEvent::HOOK_NAME,
			[
				'code' => 'echo "Hello, World!";',
				'hash' => 'invalid_hashinvalidhash',
			]
		);
	}

	public function testValidPHPIsExecuted(): void {
		$code = 'echo "Hello, World!";';

		ob_start();

		do_action(
			PHPCronEvent::HOOK_NAME,
			[
				'code' => $code,
				'hash' => wp_hash( $code ),
			]
		);

		$output = ob_get_clean();

		self::assertSame( 'Hello, World!', $output );
	}
}
