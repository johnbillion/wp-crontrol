/**
 * Functionality related to Crontrol.
 */

let hashtimer = null;

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

			if ( wp && wp.a11y && wp.a11y.speak ) {
				wp.a11y.speak( jQuery( '#crontrol-hash-message' ).text() );
			}

			if ( hashtimer ) {
				clearInterval( hashtimer );
			}
		}
	} );
}

jQuery(function($){
	$('#crontrol_next_run_date_local_custom_date,#crontrol_next_run_date_local_custom_time').on('change', function() {
		$('#crontrol_next_run_date_local_custom').prop('checked',true);
	});

	if ( $('input[value="new_php_cron"]').length ) {
		$('input[value="new_cron"]').on('click',function(){
			$('.crontrol-edit-event').removeClass('crontrol-edit-event-php').addClass('crontrol-edit-event-standard');
			$('#crontrol_hookname').attr('required',true);
		});
		$('input[value="new_php_cron"]').on('click',function(){
			$('.crontrol-edit-event').removeClass('crontrol-edit-event-standard').addClass('crontrol-edit-event-php');
			$('#crontrol_hookname').attr('required',false);
			if ( ! $('#crontrol_hookcode').hasClass('crontrol-editor-initialized') ) {
				wp.codeEditor.initialize( 'crontrol_hookcode', window.wpCrontrol.codeEditor );
			}
			$('#crontrol_hookcode').addClass('crontrol-editor-initialized');
		});
	} else if ( $('#crontrol_hookcode').length ) {
		wp.codeEditor.initialize( 'crontrol_hookcode', window.wpCrontrol.codeEditor );
	}
});
