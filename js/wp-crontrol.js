/**
 * Functionality related to Crontrol.
 */

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
