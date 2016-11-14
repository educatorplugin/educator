(function($) {
	'use strict';

	var countryField = $('#billing-country');
	var taxRequest = null;
	var taxRequestTimeout = null;
	var statesRequest = null;

	var disableSubmit = function() {
		$('#submit-payment-form').attr('disabled', 'disabled');
	};

	var enableSubmit = function() {
		$('#submit-payment-form').removeAttr('disabled');
	};

	var sendTaxRequest = function() {
		var data = {
			action: 'edr_calculate_tax',
			object_id: $('#payment-object-id').val(),
			country: countryField.val(),
			state: document.getElementById('billing-state').value
		};

		if (taxRequest) {
			taxRequest.abort();
		}

		disableSubmit();

		taxRequest = $.ajax({
			type: 'GET',
			url: edrPaymentVars.ajaxurl,
			dataType: 'html',
			data: data,
			complete: function() {
				enableSubmit();
			},
			success: function(response) {
				if (response) {
					$('#edr-payment-info').html(response);
				}
			}
		});
	};

	var calculateTax = function() {
		if (taxRequestTimeout) {
			clearTimeout(taxRequestTimeout);
		}

		taxRequestTimeout = setTimeout(sendTaxRequest, 500);
	}

	var getStates = function() {
		var data = {
			action: 'edr_get_states',
			country: countryField.val(),
			_wpnonce: edrPaymentVars.get_states_nonce
		};

		if (statesRequest) {
			statesRequest.abort();
		}

		disableSubmit();

		statesRequest = $.ajax({
			type: 'GET',
			url: edrPaymentVars.ajaxurl,
			dataType: 'json',
			data: data,
			complete: function() {
				enableSubmit();
			},
			success: function(response) {
				var field, i;

				if (response && response.length) {
					// Select field.
					field = $('<select id="billing-state" name="billing_state"></select>');
					field.append('<option value=""></option>');

					for (i = 0; i < response.length; ++i) {
						field.append('<option value="' + response[i].code + '">' + response[i].name + '</option>');
					}

					$('#billing-state').replaceWith(field);
				} else {
					// Text field.
					$('#billing-state').replaceWith('<input type="text" id="billing-state" name="billing_state">');
				}

				calculateTax();
			}
		});
	};

	countryField.on('change', function() {
		getStates();
	});

	$('body').on('keyup change', '#billing-state', function() {
		calculateTax();
	});
})(jQuery);
