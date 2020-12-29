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

jQuery(function($){
	$('#next_run_date_local_custom_date,#next_run_date_local_custom_time').on('change', function() {
		$('#next_run_date_local_custom').prop('checked',true);
	});

	if ( $('input[value="new_php_cron"]').length ) {
		$('input[value="new_cron"]').on('click',function(){
			$('.crontrol-edit-event').removeClass('crontrol-edit-event-php').addClass('crontrol-edit-event-standard');
			$('#hookname').attr('required',true);
		});
		$('input[value="new_php_cron"]').on('click',function(){
			$('.crontrol-edit-event').removeClass('crontrol-edit-event-standard').addClass('crontrol-edit-event-php');
			$('#hookname').attr('required',false);
			if ( ! $('#hookcode').hasClass('crontrol-editor-initialized') ) {
				wp.codeEditor.initialize( 'hookcode', window.wpCrontrol.codeEditor );
			}
			$('#hookcode').addClass('crontrol-editor-initialized');
		});
	} else if ( $('#hookcode').length ) {
		wp.codeEditor.initialize( 'hookcode', window.wpCrontrol.codeEditor );
	}
});
