$(document).ready(function()
{
	$('#paymill-card-number').keyup(function() 
	{
		paymillShowCardIcon();
	});
	
	/**
	* Event Handler for the display of the card icons
	*/
   function paymillShowCardIcon()
   {
	    var creditCard = new BrandDetection();
	    var brand = detectCreditcardBranding($('#paymill-card-number').val());
	    brand = brand.toLowerCase();
	    $('#paymill-card-number')[0].className =  $('#paymill-card-number')[0].className.replace(/paymill-card-number-.*/g, '');
		if (brand !== 'unknown') {
		    if (brand === 'american express') {
				brand = 'amex';
		    }

			$('#paymill-card-number').addClass("paymill-card-number-" + brand);
			$('#paymill-card-number').addClass("greyscale");
		}
		
		if (creditCard.validate($('#paymill-card-number').val())) {
			$('#paymill-card-number').removeClass('greyscale');
		}
    }
	function detectCreditcardBranding(creditcardNumber) 
	{
		var creditCard = new BrandDetection();
		return creditCard.detect(creditcardNumber)
	}

	function paymillCcResponseHandler(error, result)
	{
		paymillDebug('Paymill: Start response handler');
		if (error) {
			paymillDebug('An API error occured:' + error.apierror);
			$("#payment-errors-cc").text(lang['PAYMILL_' + error.apierror]);
			$("#payment-errors-cc").css('display', 'block');
		} else {
			preventDefault = false;
			paymillDebug('Received a token: ' + result.token);
			$("#payment-errors-cc").text("");
			$("#payment-errors-cc").css('display', 'none');
			$('form[name^="process"]').append("<input type='hidden' name='paymillToken' value='" + result.token + "'/>");
			$('form[name^="process"]').submit();
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

		if (!paymill.validateExpiry($('#Paymill_Month').val(), $('#Paymill_Year').val())) {
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
			exp_month: $('#Paymill_Month').val(),
			exp_year: $('#Paymill_Year').val(),
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

	$('#Paymill_Month').focus(function() {
		fastCheckoutCc = 'false';
	});

	$('#Paymill_Year').focus(function() {
		fastCheckoutCc = 'false';
	});

	$('#paymill-card-cvc').focus(function() {
		fastCheckoutCc = 'false';
	});

	$('#paymill-card-holdername').focus(function() {
		fastCheckoutCc = 'false';
	});

	$('form[name^="process"]').submit(function(event) {
		if (preventDefault) {
			event.preventDefault();
			if (fastCheckoutCc === 'false') {
				paymillDebug('Paymill Creditcard: Payment method triggered');
				return paymillCc();
			} else {
				preventDefault = false;
				$('form[name^="process"]').append("<input type='hidden' name='paymillToken' value='dummyToken'/>");
				$('form[name^="process"]').submit();
			}
		}
	});
});