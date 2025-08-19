<?php declare(strict_types = 1);

namespace Crontrol\Tests;

use Crontrol\Exception\MissingHashException;
use Crontrol\Exception\InvalidHashException;

class URLEventTest extends Test {
	public function testMissingHashTriggersException(): void {
		$this->expectException( MissingHashException::class );

		do_action(
			'crontrol_url_cron_job',
			[
				'url' => 'http://example.com',
				'method' => 'GET',
			]
		);
	}

	public function testInvalidHashTriggersException(): void {
		$this->expectException( InvalidHashException::class );

		do_action(
			'crontrol_url_cron_job',
			[
				'url' => 'http://example.com',
				'method' => 'GET',
				'hash' => 'invalidhash',
			]
		);
	}

}
