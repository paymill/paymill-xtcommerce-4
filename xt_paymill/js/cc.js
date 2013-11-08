$(document).ready(function()
{
	var submitFlag = false;
	
	var cssClass = "paymill-card-number-";

	$('#paymill-card-number').keyup(function() {

		switch (paymill.cardType($('#paymill-card-number').val()).toLowerCase()) {
			case 'visa':
				$('#paymill-card-number').removeClass();
				$('#paymill-card-number').addClass('paymill-input ' + cssClass + 'visa');
				break;
			case 'mastercard':
				$('#paymill-card-number').removeClass();
				$('#paymill-card-number').addClass('paymill-input ' + cssClass + 'mastercard');
				break;
			case 'american express':
				$('#paymill-card-number').removeClass();
				$('#paymill-card-number').addClass('paymill-input ' + cssClass + 'american');
				break;
			case 'jcb':
				$('#paymill-card-number').removeClass();
				$('#paymill-card-number').addClass('paymill-input ' + cssClass + 'jcb');
				break;
			case 'maestro':
				$('#paymill-card-number').removeClass();
				$('#paymill-card-number').addClass('paymill-input ' + cssClass + 'maestro');
				break;
			case 'diners club':
				$('#paymill-card-number').removeClass();
				$('#paymill-card-number').addClass('paymill-input ' + cssClass + 'diners');
				break;
			case 'discover':
				$('#paymill-card-number').removeClass();
				$('#paymill-card-number').addClass('paymill-input ' + cssClass + 'discover');
				break;
			case 'unionpay':
				$('#paymill-card-number').removeClass();
				$('#paymill-card-number').addClass('paymill-input ' + cssClass + 'unionpay');
				break;
		}
	});	

	function paymillCcResponseHandler(error, result)
	{
		if (flag) {
			paymillDebug('Paymill: Start response handler');
			if (error) {
				flag = false;
				paymillDebug('An API error occured:' + error.apierror);
				$("#payment-errors-cc").text(error.apierror);
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

		if (!paymill.validateCvc($('#paymill-card-cvc').val()) && paymill.cardType($('#paymill-card-number').val()).toLowerCase() !== 'maestro') {
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
		$('#paymill-card-number').val('');
	});

	$('select[name="Paymill_Month"]').focus(function() {
		fastCheckoutCc = 'false';
	});

	$('select[name="Paymill_Year"]').focus(function() {
		fastCheckoutCc = 'false';
	});

	$('#paymill-card-cvc').focus(function() {
		fastCheckoutCc = 'false';
		$('#paymill-card-cvc').val('');
	});

	$('#paymill-card-holdername').focus(function() {
		fastCheckoutCc = 'false';
		$('#paymill-card-holdername').val('');
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