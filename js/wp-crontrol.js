/**
 * Functionality related to Crontrol.
 *
 * @package wp-crontrol
 */

const header = document.getElementById( 'crontrol-header' );
const wpbody = document.getElementById( 'wpbody-content' );
let hashtimer = null;

if ( header && wpbody ) {
	wpbody.prepend( header );
}

if ( window.wpCrontrol && window.wpCrontrol.eventsHash && window.wpCrontrol.eventsHashInterval ) {
	hashtimer = setInterval( crontrolCheckHash, ( 1000 * window.wpCrontrol.eventsHashInterval ) );
}

function crontrolCheckHash() {
	jQuery.ajax( {
		url: window.ajaxurl,
		type: 'post',
		data: {
			action: 'crontrol_checkhash',
		},
		dataType: 'json',
	} ).done( function( response ) {
		if ( response.success && response.data && response.data !== window.wpCrontrol.eventsHash ) {
			jQuery( '#crontrol-hash-message' ).slideDown();

			if ( hashtimer ) {
				clearInterval( hashtimer );
			}
		}
	} );
}
