
function paymillDebug(message)
{
	if (debug === 'true') {
		console.log(message);
	}
}

function hideErrorBoxes(payment, limit)
{
	for (i = 0; i <= limit; i++) {
		$("#payment-error-" + payment + "-" + i).css('display', 'none');
	}
}
	
	/*
	$('form[name^="process"]').submit(function(event) {
		if ($("input[name='selected_payment']:checked").val() === 'xt_paymill:cc') {
			if (fastCheckoutCc === 'false') {
				paymillDebug('Paymill Creditcard: Payment method triggered');
				return paymillCc();
			} else {
				$('#paymill_selector_cc').val('xt_paymill:' + '_cc_dummyToken');
			}
		} else if ($("input[name='selected_payment']:checked").val() === 'xt_paymill:elv') {
			if (fastCheckoutElv === 'false') {
				paymillDebug('Paymill ELV: Payment method triggered');
				return paymillElv();
			} else {
				$('#paymill_selector_elv').val('xt_paymill:' + '_dd_dummyToken');
			}
		}

		$('form[name^="payment"]').get(0).submit();
	});
	*/
