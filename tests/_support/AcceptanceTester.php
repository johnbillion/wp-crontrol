<?php
/**
 * Acceptance testing actor.
 *
 * @package wp-crontrol
 */

/**
 * Inherited Methods
 *
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 */
class AcceptanceTester extends \Codeception\Actor {
	use _generated\AcceptanceTesterActions;

	/**
	 * Checks that the current page contains an admin success notice.
	 *
	 * @param string $text The message text to search for.
	 */
	public function seeAdminSuccessNotice( string $text ) {
		return $this->see( $text, '.notice-success' );
	}

	/**
	 * Checks that the current page contains an admin success notice.
	 *
	 * @param string $text The message text to search for.
	 */
	public function seeAdminWarningNotice( string $text ) {
		return $this->see( $text, '.notice-warning' );
	}

	/**
	 * Checks that the current page contains an admin success notice.
	 *
	 * @param string $text The message text to search for.
	 */
	public function seeAdminErrorNotice( string $text ) {
		return $this->see( $text, '.notice-error' );
	}

	/**
	 * Checks that the current page contains an admin success notice.
	 *
	 * @param string $text The message text to search for.
	 */
	public function seeAdminInfoNotice( string $text ) {
		return $this->see( $text, '.notice-info' );
	}

	/**
	 * Fill out lines of code in the PHP editor field.
	 *
	 * @example
	 *
	 * ```php
	 * $I->fillPHPEditorField("if ( function_exists( 'foo' ) {", "\tfoo();", "}");
	 * ```
	 *
	 * @param string ...$values Individual lines to fill in.
	 * @return void
	 */
	public function fillPHPEditorField( string ...$values ) {
		$this->executeJS( 'document.getElementsByClassName("CodeMirror")[0].CodeMirror.setValue(Array.prototype.join.call(arguments, "\n"));', $values );
	}
}
