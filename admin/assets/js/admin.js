(function ( $ ) {
	"use strict";

	$(function () {
		$('#mailerlite_lists__0').change(function() {
			var selectedListID = $(this).find(":selected").val();
			$('#mailerlite_shortcode__0').val('[mailerlite id="' + selectedListID + '"]');
		});
		$('#mailerlite_lists__0').trigger('change');
	});

}(jQuery));
