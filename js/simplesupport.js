jQuery(document).ready(function($) {

	//! Update topic status
	if($('.ss_status_form').length) {
		$('.ss_status_form').submit(function(event) {
			// Remove update class
			$('.ss_status_form').removeClass('ss_status_updated');
			// AJAX request
			$.ajax({
				type: 'POST',
				url: bandit.ajaxurl,
				data: $(this).serialize() + '&action=update_topic_status',
				dataType: 'json',
				success: function(result) {
					alert(result.success);
					if(true==result.success) {
						$('.ss_status_form').addClass('ss_status_updated');
						$('.ss_status_select_field').val(result.topic_status);
					}
				}
			});
			event.preventDefault();
		});
	}

});