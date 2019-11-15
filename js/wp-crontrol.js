/**
 * Functionality related to Crontrol.
 *
 * @package wp-crontrol
 */

const header = document.getElementById( 'crontrol-header' );
const wpbody = document.getElementById( 'wpbody-content' );

if ( header && wpbody ) {
	wpbody.prepend( header );
}
