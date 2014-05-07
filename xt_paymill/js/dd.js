pmQuery(document).ready(function()
{	
	preventDefault = true;
	
	var oldFieldData = getFormData(true);
	
	function paymillElvResponseHandler(error, result)
	{
		paymillDebug('Paymill: Start response handler');
		if (error) {
			paymillDebug('An API error occured:' + error.apierror);
			pmQuery("#payment-errors-elv").text(pmQuery('<div/>').html(lang['PAYMILL_' + error.apierror]).text());
			pmQuery("#payment-errors-elv").css('display', 'block');
		} else {
			preventDefault = false;
			paymillDebug('Received a token: ' + result.token);
			pmQuery("#payment-errors-elv").text("");
			pmQuery("#payment-errors-elv").css('display', 'none');
			pmQuery('form[name^="process"]').append("<input type='hidden' name='paymillToken' value='" + result.token + "'/>");
			pmQuery('form[name^="process"]').submit();
		}
	}

	function paymillElv()
	{
		paymillDebug('Paymill ELV: Start form validation');

		hideErrorBoxes('elv', 3);

		var elvErrorFlag = true;

		if (!paymill.validateAccountNumber(pmQuery('#paymill-account-number').val())) {
			pmQuery("#payment-error-elv-1").text(pmQuery('<div/>').html(lang['account_number_invalid']).text());
			pmQuery("#payment-error-elv-1").css('display', 'block');
			elvErrorFlag = false;
		}

		if (!paymill.validateBankCode(pmQuery('#paymill-bank-code').val())) {
			pmQuery("#payment-error-elv-2").text(pmQuery('<div/>').html(lang['sort_code_invalid']).text());
			pmQuery("#payment-error-elv-2").css('display', 'block');
			elvErrorFlag = false;
		}

		if (pmQuery('#paymill-bank-owner').val() === "") {
			pmQuery("#payment-error-elv-3").text(pmQuery('<div/>').html(lang['account_owner_invalid']).text());
			pmQuery("#payment-error-elv-3").css('display', 'block');
			elvErrorFlag = false;
		}

		if (!elvErrorFlag) {
			return elvErrorFlag;
		}

		paymill.createToken({
			number: pmQuery('#paymill-account-number').val(),
			bank: pmQuery('#paymill-bank-code').val(),
			accountholder: pmQuery('#paymill-bank-owner').val()
		}, paymillElvResponseHandler);

		return false;
	}
	
	function paymillSepa()
	{
		paymillDebug('Paymill SEPA: Start form validation');

		hideErrorBoxes('elv', 3);

		var elvErrorFlag = true;
		iban = new Iban();
		if (!iban.validate(pmQuery('#paymill-account-number').val())) {
			pmQuery("#payment-error-elv-1").text(pmQuery('<div/>').html(lang['iban_invalid']).text());
			pmQuery("#payment-error-elv-1").css('display', 'block');
			elvErrorFlag = false;
		}

		if (pmQuery('#paymill-bank-code').val() === "") {
			pmQuery("#payment-error-elv-2").text(pmQuery('<div/>').html(lang['bic_invalid']).text());
			pmQuery("#payment-error-elv-2").css('display', 'block');
			elvErrorFlag = false;
		}

		if (pmQuery('#paymill-bank-owner').val() === "") {
			pmQuery("#payment-error-elv-3").text(pmQuery('<div/>').html(lang['account_owner_invalid']).text());
			pmQuery("#payment-error-elv-3").css('display', 'block');
			elvErrorFlag = false;
		}

		if (!elvErrorFlag) {
			return elvErrorFlag;
		}
		
		paymill.createToken({
			iban: pmQuery('#paymill-account-number').val(),
			bic: pmQuery('#paymill-bank-code').val(),
			accountholder: pmQuery('#paymill-bank-owner').val()
		}, paymillElvResponseHandler);


		return false;
	}
	
	function getFormData(ignoreEmptyValues) 
	{
		var array = new Array();
		pmQuery('#paymill-cc-inputs :input').not('[type=hidden]').each(function() 
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

	pmQuery('form[name^="process"]').submit(function(event) {
		if (preventDefault) {
			event.preventDefault();
			var newFieldData = getFormData();
			if (oldFieldData.toString() === newFieldData.toString()) {
				preventDefault = false;
				pmQuery('form[name^="process"]').append("<input type='hidden' name='paymillToken' value='dummyToken'/>");
				pmQuery('form[name^="process"]').submit();
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