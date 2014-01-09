$(document).ready(function()
{
	var submitFlag = false;

	$('#paymill-card-number').keyup(function() 
	{
		paymillShowCardIcon()
	});
	
	/**
	* Event Handler for the display of the card icons
	*/
   function paymillShowCardIcon()
   {
	    var brand = detectCreditcardBranding($('#paymill-card-number').val());
	    brand = brand.toLowerCase();
	    $('#paymill-card-number')[0].className = $('#paymill-card-number')[0].className.replace(/paymill-card-number-.*/g, '');
		if (brand !== 'unknown') {
		    if (brand === 'american express') {
				brand = 'amex';
		    }

			$('#paymill-card-number').addClass("paymill-card-number-" + brand);
		}
    }
	function detectCreditcardBranding(creditcardNumber) 
	{
		var brand = 'unknown';
		if (creditcardNumber.match(/^\d{6}/)) {
			switch (true) {
				case /^(415006|497|407497|513)/.test(creditcardNumber):
					brand = "carte bleue";
					break;
				case /^(45399[78]|432913|5255)/.test(creditcardNumber):
					brand = "carta si";
					break;
				case /^(4571|5019)/.test(creditcardNumber):
					brand = "dankort";
					break;
				case /^(62|88)/.test(creditcardNumber):
					brand = "china unionpay";
					break;
				case /^6(011|5)/.test(creditcardNumber):
					brand = "discover";
					break;
				case /^3(0[0-5]|[68])/.test(creditcardNumber):
					brand = "diners club";
					break;
				case /^(5018|5020|5038|5893|6304|6759|6761|6762|6763|0604|6390)/.test(creditcardNumber):
					brand = "maestro";
					break;
				case /^(2131|1800|35)/.test(creditcardNumber):
					brand = "jcb";
					break;
				case /^(3[47])/.test(creditcardNumber):
					brand = "amex";
					break;
				case /^(5[1-5])/.test(creditcardNumber):
					brand = "mastercard";
					break;
				case /^(4)/.test(creditcardNumber):
					brand = "visa";
					break;
			}
		}
		return brand;
	}

	function paymillCcResponseHandler(error, result)
	{
		if (flag) {
			paymillDebug('Paymill: Start response handler');
			if (error) {
				flag = false;
				paymillDebug('An API error occured:' + error.apierror);
				$("#payment-errors-cc").text(lang['PAYMILL_' + error.apierror]);
				$("#payment-errors-cc").css('display', 'block');
			} else {
				flag = false;
				paymillDebug('Received a token: ' + result.token);
				$("#payment-errors-cc").text("");
				$("#payment-errors-cc").css('display', 'none');
				$('form[name^="process"]').append("<input type='hidden' name='paymillToken' value='" + result.token + "'/>");
				$('form[name^="process"]').submit();
			}
		}
	}

	function paymillCc()
	{
		paymillDebug('Paymill Creditcard: Start form validation');

		hideErrorBoxes('cc', 4);

		var ccErrorFlag = true;

		if (!paymill.validateCardNumber($('#paymill-card-number').val())) {
			$("#payment-error-cc-1").text(lang['card_number_invalid']);
			$("#payment-error-cc-1").css('display', 'block');
			ccErrorFlag = false;
		}

		if (!paymill.validateExpiry($('select[name="Paymill_Month"]').val(), $('select[name="Paymill_Year"]').val())) {
			$("#payment-error-cc-4").text(lang['expiration_date_invalid']);
			$("#payment-error-cc-4").css('display', 'block');
			ccErrorFlag = false;
		}

		if (!paymill.validateCvc($('#paymill-card-cvc').val()) && detectCreditcardBranding($('#paymill-card-number').val()).toLowerCase() !== 'maestro') {
			$("#payment-error-cc-2").text(lang['verfication_number_invalid']);
			$("#payment-error-cc-2").css('display', 'block');
			ccErrorFlag = false;
		}

		if (!paymill.validateHolder($('#paymill-card-holdername').val())) {
			$("#payment-error-cc-3").text(lang['card_holder_invalid']);
			$("#payment-error-cc-3").css('display', 'block');
			ccErrorFlag = false;
		}

		if (!ccErrorFlag) {
			return ccErrorFlag;
		}

		var cvc = '000';

		if ($('#paymill-card-cvc').val() !== '') {
			cvc = $('#paymill-card-cvc').val();
		}

		paymill.createToken({
			number: $('#paymill-card-number').val(),
			exp_month: $('select[name="Paymill_Month"]').val(),
			exp_year: $('select[name="Paymill_Year"]').val(),
			cvc: cvc,
			cardholder: $('#paymill-card-holdername').val(),
			amount_int: amount,
			currency: currency
		}, paymillCcResponseHandler);

		return false;
	}

	$('#paymill-card-number').focus(function() {
		fastCheckoutCc = 'false';
	});

	$('select[name="Paymill_Month"]').focus(function() {
		fastCheckoutCc = 'false';
	});

	$('select[name="Paymill_Year"]').focus(function() {
		fastCheckoutCc = 'false';
	});

	$('#paymill-card-cvc').focus(function() {
		fastCheckoutCc = 'false';
	});

	$('#paymill-card-holdername').focus(function() {
		fastCheckoutCc = 'false';
	});

	$('form[name^="process"]').submit(function(event) {
		if (!submitFlag) {
			event.preventDefault();
			submitFlag = true;
			if (fastCheckoutCc === 'false') {
				paymillDebug('Paymill Creditcard: Payment method triggered');
				return paymillCc();
			} else {
				$('form[name^="process"]').append("<input type='hidden' name='paymillToken' value='dummyToken'/>");
				$('form[name^="process"]').submit();
			}
		}
	});
});