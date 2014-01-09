
$(document).ready(function()
{
	var submitFlag = false;
	
	function paymillElvResponseHandler(error, result)
	{
		if (flag) {
			paymillDebug('Paymill: Start response handler');
			if (error) {
				flag = false;
				paymillDebug('An API error occured:' + error.apierror);
				$("#payment-errors-elv").text(error.apierror);
				$("#payment-errors-elv").css('display', 'block');
			} else {
				flag = false;
				paymillDebug('Received a token: ' + result.token);
				$("#payment-errors-elv").text("");
				$("#payment-errors-elv").css('display', 'none');
				$('form[name^="process"]').append("<input type='hidden' name='paymillToken' value='" + result.token + "'/>");
				$('form[name^="process"]').submit();
			}
		}
	}

	function paymillElv()
	{
		paymillDebug('Paymill ELV: Start form validation');

		hideErrorBoxes('elv', 3);

		var elvErrorFlag = true;

		if (!paymill.validateAccountNumber($('#paymill-account-number').val())) {
			$("#payment-error-elv-1").text(lang['account_number_invalid']);
			$("#payment-error-elv-1").css('display', 'block');
			elvErrorFlag = false;
		}

		if (!paymill.validateBankCode($('#paymill-bank-code').val())) {
			$("#payment-error-elv-2").text(lang['sort_code_invalid']);
			$("#payment-error-elv-2").css('display', 'block');
			elvErrorFlag = false;
		}

		if ($('#paymill-bank-owner').val() === "") {
			$("#payment-error-elv-3").text(lang['account_owner_invalid']);
			$("#payment-error-elv-3").css('display', 'block');
			elvErrorFlag = false;
		}

		if (!elvErrorFlag) {
			return elvErrorFlag;
		}

		paymill.createToken({
			number: $('#paymill-account-number').val(),
			bank: $('#paymill-bank-code').val(),
			accountholder: $('#paymill-bank-owner').val()
		}, paymillElvResponseHandler);

		return false;
	}
	
	function paymillSepa()
	{
		paymillDebug('Paymill SEPA: Start form validation');

		hideErrorBoxes('elv', 3);

		var elvErrorFlag = true;

		if ($('#paymill-iban').val() === "") {
			$("#payment-error-elv-1").text(lang['iban_invalid']);
			$("#payment-error-elv-1").css('display', 'block');
			elvErrorFlag = false;
		}

		if ($('#paymill-bic').val() === "") {
			$("#payment-error-elv-2").text(lang['bic_invalid']);
			$("#payment-error-elv-2").css('display', 'block');
			elvErrorFlag = false;
		}

		if ($('#paymill-bank-owner').val() === "") {
			$("#payment-error-elv-3").text(lang['account_owner_invalid']);
			$("#payment-error-elv-3").css('display', 'block');
			elvErrorFlag = false;
		}

		if (!elvErrorFlag) {
			return elvErrorFlag;
		}

		paymill.createToken({
			iban: $('#paymill-iban').val(),
			bic: $('#paymill-bic').val(),
			accountholder: $('#paymill-bank-owner').val()
		}, paymillElvResponseHandler);

		return false;
	}

	$('#paymill-account-number').focus(function() {
		fastCheckoutElv = 'false';
		$('#paymill-account-number').val('');
	});

	$('#paymill-bank-code').focus(function() {
		fastCheckoutElv = 'false';
		$('#paymill-bank-code').val('');
	});

	$('#paymill-bank-owner').focus(function() {
		fastCheckoutElv = 'false';
		$('#paymill-bank-owner').val('');
	});
	
	$('form[name^="process"]').submit(function(event) {
		if (!submitFlag) {
			event.preventDefault();
			submitFlag = true;
			if (fastCheckoutElv === 'false') {
				paymillDebug('Paymill ELV: Payment method triggered');
				if (sepa === 'false') {
					return paymillElv();
				} else if(sepa === "true") {
					return paymillSepa();
				}
				
			} else {
				$('form[name^="process"]').append("<input type='hidden' name='paymillToken' value='dummyToken'/>");
				$('form[name^="process"]').submit();
			}
		}
	});
});