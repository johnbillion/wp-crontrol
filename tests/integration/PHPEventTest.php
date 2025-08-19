<?php declare(strict_types = 1);

namespace Crontrol\Tests;

use Crontrol\Exception\MissingHashException;
use Crontrol\Exception\InvalidHashException;

class PHPEventTest extends Test {
	public function testMissingHashTriggersException(): void {
		$this->expectException( MissingHashException::class );

		do_action(
			'crontrol_cron_job',
			[
				'code' => 'echo "Hello, World!";',
			]
		);
	}

	public function testInvalidHashTriggersException(): void {
		$this->expectException( InvalidHashException::class );

		do_action(
			'crontrol_cron_job',
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
			'crontrol_cron_job',
			[
				'code' => $code,
				'hash' => wp_hash( $code ),
			]
		);

		$output = ob_get_clean();

		$this->assertEquals( 'Hello, World!', $output );
	}
}
