jQuery(document).ready(function($) {

	//! Update topic status
	if($('.ss-status-form').length) {
		$('.ss-status-form').submit(function(event) {
			// Remove update class
			$('.ss-status-form').removeClass('ss-status-updated');
			// AJAX request
			$.ajax({
				type: 'POST',
				url: bandit.ajaxurl,
				data: $(this).serialize() + '&action=update_topic_status',
				dataType: 'json',
				success: function(result) {
					if(true==result.success) {
						$('.ss-status-form').addClass('ss-status-updated');
						$('.ss-status-icon-thread').attr('src',result.topic_icon);
						$('.ss-status-select-field').val(result.topic_status);
					}
				}
			});
			event.preventDefault();
		});
	}

});