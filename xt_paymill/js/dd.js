$(document).ready(function()
{	
	preventDefault = true;
	
	var oldFieldData = getFormData(true);
	
	function paymillElvResponseHandler(error, result)
	{
		paymillDebug('Paymill: Start response handler');
		if (error) {
			paymillDebug('An API error occured:' + error.apierror);
			$("#payment-errors-elv").text($('<div/>').html(lang['PAYMILL_' + error.apierror]).text());
			$("#payment-errors-elv").css('display', 'block');
		} else {
			preventDefault = false;
			paymillDebug('Received a token: ' + result.token);
			$("#payment-errors-elv").text("");
			$("#payment-errors-elv").css('display', 'none');
			$('form[name^="process"]').append("<input type='hidden' name='paymillToken' value='" + result.token + "'/>");
			$('form[name^="process"]').submit();
		}
	}

	function paymillElv()
	{
		paymillDebug('Paymill ELV: Start form validation');

		hideErrorBoxes('elv', 3);

		var elvErrorFlag = true;

		if (!paymill.validateAccountNumber($('#paymill-account-number').val())) {
			$("#payment-error-elv-1").text($('<div/>').html(lang['account_number_invalid']).text());
			$("#payment-error-elv-1").css('display', 'block');
			elvErrorFlag = false;
		}

		if (!paymill.validateBankCode($('#paymill-bank-code').val())) {
			$("#payment-error-elv-2").text($('<div/>').html(lang['sort_code_invalid']).text());
			$("#payment-error-elv-2").css('display', 'block');
			elvErrorFlag = false;
		}

		if ($('#paymill-bank-owner').val() === "") {
			$("#payment-error-elv-3").text($('<div/>').html(lang['account_owner_invalid']).text());
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
		iban = new Iban();
		if (!iban.validate($('#paymill-account-number').val())) {
			$("#payment-error-elv-1").text($('<div/>').html(lang['iban_invalid']).text());
			$("#payment-error-elv-1").css('display', 'block');
			elvErrorFlag = false;
		}

		if ($('#paymill-bank-code').val() === "") {
			$("#payment-error-elv-2").text($('<div/>').html(lang['bic_invalid']).text());
			$("#payment-error-elv-2").css('display', 'block');
			elvErrorFlag = false;
		}

		if ($('#paymill-bank-owner').val() === "") {
			$("#payment-error-elv-3").text($('<div/>').html(lang['account_owner_invalid']).text());
			$("#payment-error-elv-3").css('display', 'block');
			elvErrorFlag = false;
		}

		if (!elvErrorFlag) {
			return elvErrorFlag;
		}
		
		paymill.createToken({
			iban: $('#paymill-account-number').val(),
			bic: $('#paymill-bank-code').val(),
			accountholder: $('#paymill-bank-owner').val()
		}, paymillElvResponseHandler);


		return false;
	}
	
	function getFormData(ignoreEmptyValues) 
	{
		var array = new Array();
		$('#paymill-cc-inputs :input').not('[type=hidden]').each(function() 
		{
			
			if ($(this).val() === "" && ignoreEmptyValues) {
				return;
			}
			
			array.push($(this).val());
		});
		
		return array;
	}
	
	function isSepa() 
	{
		var reg = new RegExp(/^\D{2}/);
		return reg.test($('#paymill-account-number').val());
	}

	$('form[name^="process"]').submit(function(event) {
		if (preventDefault) {
			event.preventDefault();
			var newFieldData = getFormData();
			if (oldFieldData.toString() === newFieldData.toString()) {
				preventDefault = false;
				$('form[name^="process"]').append("<input type='hidden' name='paymillToken' value='dummyToken'/>");
				$('form[name^="process"]').submit();
			} else {
				paymillDebug('Paymill ELV: Payment method triggered');
				if (!isSepa()) {
					return paymillElv();
				} else {
					return paymillSepa();
				}

			}
		}
	});
});