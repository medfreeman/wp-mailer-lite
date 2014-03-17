(function ( $ ) {
	"use strict";

	$(function () {
		$('.mailerlite_container').find('form').not('.processed').addClass('processed').validate({
		  errorClass: 'invalid',
		  submitHandler: function(form) {
			if ( mailerLite.loadingClass !== '' ) {
				$('body').addClass(mailerLite.loadingClass);
			}
			$(form).ajaxSubmit({
				url: mailerLite.ajaxUrl,
				data: { action: mailerLite.ajaxHandler },
				dataType: 'json',
				error: function( jqXHR, textStatus, errorThrown ) {
					$form.find('.mailerlite_messages').html( mailerLite.errorMessage );
					$('body').removeClass(mailerLite.loadingClass);
				},
				success: function( response, statusText, jqXHR, $form ) {
					$form.find('.mailerlite_messages').html( response.messages );
					$('body').removeClass(mailerLite.loadingClass);
				}
			});
		  }
		});
	});

}(jQuery));
