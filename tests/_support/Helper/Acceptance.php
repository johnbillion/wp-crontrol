<?php
/**
 * Acceptance testing helper.
 */

namespace Helper;

class Acceptance extends \Codeception\Module {
	private string $debugLogFile = '';

	public function _beforeSuite($settings = []) {
		$this->debugLogFile = sprintf(
			'%s/tests/wordpress/wp-content/debug.log',
			getcwd()
		);

		if ( file_exists( $this->debugLogFile ) ) {
			unlink( $this->debugLogFile );
		}
	}

	public function _afterStep(\Codeception\Step $step) {
		if ( ! file_exists( $this->debugLogFile ) ) {
			return;
		}

		$contents = trim( file_get_contents( $this->debugLogFile ) );

		unlink( $this->debugLogFile );

		if ( empty( $contents ) ) {
			return;
		}

		if ( $step->hasFailed() ) {
			return;
		}

		throw new \PHPUnit\Framework\ExpectationFailedException(
			sprintf(
				"PHP errors were logged during this step:\n\n%s",
				$contents
			)
		);
	}
}
