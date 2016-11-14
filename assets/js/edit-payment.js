(function($) {
	'use strict';

	var statesRequest = null;

	var getStates = function() {
		var data = {
			action:   'edr_get_states',
			country:  $('#payment-country').val(),
			_wpnonce: $('#edr-get-states-nonce').val()
		};

		if (statesRequest) {
			statesRequest.abort();
		}

		statesRequest = $.ajax({
			type: 'GET',
			url: ajaxurl,
			dataType: 'json',
			data: data,
			success: function(response) {
				var field, i;

				if (response && response.length) {
					field = $('<select id="payment-state" name="state"></select>');
					field.append('<option value=""></option>');

					for (i = 0; i < response.length; ++i) {
						field.append('<option value="' + response[i].code + '">' + response[i].name + '</option>');
					}

					$('#payment-state').replaceWith(field);
				} else {
					$('#payment-state').replaceWith('<input type="text" id="payment-state" name="state" class="regular-text">');
				}
			}
		});
	};

	$('#payment-country').on('change', function() {
		getStates();
	});
})(jQuery);
