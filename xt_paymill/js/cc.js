pmQuery(document).ready(function()
{
	preventDefault = true;

	pmQuery('#paymill-card-number').keyup(function()
	{
		paymillShowCardIcon();
	});

	/**
	 * Event Handler for the display of the card icons
	 */
	function paymillShowCardIcon()
	{
		var creditCard = new BrandDetection();
		var brand = detectCreditcardBranding(pmQuery('#paymill-card-number').val());
		brand = brand.toLowerCase();
		pmQuery('#paymill-card-number')[0].className = pmQuery('#paymill-card-number')[0].className.replace(/paymill-card-number-.*/g, '');
		if (brand !== 'unknown') {
			if (brand === 'american express') {
				brand = 'amex';
			}
			
			if (logos[brand] || allBrandsDisabled) {
				pmQuery('#paymill-card-number').addClass("paymill-card-number-" + brand);
				pmQuery('#paymill-card-number').addClass("greyscale");
			}
		}
		
		if (logos[brand] || allBrandsDisabled) {
			if (creditCard.validate(pmQuery('#paymill-card-number').val())) {
				pmQuery('#paymill-card-number').removeClass('greyscale');
			}
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
			pmQuery("#payment-errors-cc").text(pmQuery('<div/>').html(lang['PAYMILL_' + error.apierror]).text());
			pmQuery("#payment-errors-cc").css('display', 'block');
		} else {
			preventDefault = false;
			paymillDebug('Received a token: ' + result.token);
			pmQuery("#payment-errors-cc").text("");
			pmQuery("#payment-errors-cc").css('display', 'none');
			pmQuery('form[name^="process"]').append("<input type='hidden' name='paymillToken' value='" + result.token + "'/>");
			pmQuery('form[name^="process"]').submit();
		}
	}

	function paymillCc()
	{
		paymillDebug('Paymill Creditcard: Start form validation');

		hideErrorBoxes('cc', 4);

		var ccErrorFlag = true;

		if (!paymill.validateCardNumber(pmQuery('#paymill-card-number').val())) {
			pmQuery("#payment-error-cc-1").text(pmQuery('<div/>').html(lang['card_number_invalid']).text());
			pmQuery("#payment-error-cc-1").css('display', 'block');
			ccErrorFlag = false;
		}

		if (!paymill.validateExpiry(pmQuery('#Paymill_Month').val(), pmQuery('#Paymill_Year').val())) {
			pmQuery("#payment-error-cc-4").text(pmQuery('<div/>').html(lang['expiration_date_invalid']).text());
			pmQuery("#payment-error-cc-4").css('display', 'block');
			ccErrorFlag = false;
		}

		if (!paymill.validateCvc(pmQuery('#paymill-card-cvc').val()) && detectCreditcardBranding(pmQuery('#paymill-card-number').val()).toLowerCase() !== 'maestro') {
			pmQuery("#payment-error-cc-2").text(pmQuery('<div/>').html(lang['verfication_number_invalid']).text());
			pmQuery("#payment-error-cc-2").css('display', 'block');
			ccErrorFlag = false;
		}

		if (!paymill.validateHolder(pmQuery('#paymill-card-holdername').val())) {
			pmQuery("#payment-error-cc-3").text(pmQuery('<div/>').html(lang['card_holder_invalid']).text());
			pmQuery("#payment-error-cc-3").css('display', 'block');
			ccErrorFlag = false;
		}

		if (!ccErrorFlag) {
			return ccErrorFlag;
		}

		var cvc = '000';

		if (pmQuery('#paymill-card-cvc').val() !== '') {
			cvc = pmQuery('#paymill-card-cvc').val();
		}

		paymill.createToken({
			number: pmQuery('#paymill-card-number').val(),
			exp_month: pmQuery('#Paymill_Month').val(),
			exp_year: pmQuery('#Paymill_Year').val(),
			cvc: cvc,
			cardholder: pmQuery('#paymill-card-holdername').val(),
			amount_int: amount,
			currency: currency
		}, paymillCcResponseHandler);

		return false;
	}

	pmQuery('#paymill-card-number').focus(function() {
		fastCheckoutCc = 'false';
	});

	pmQuery('#Paymill_Month').focus(function() {
		fastCheckoutCc = 'false';
	});

	pmQuery('#Paymill_Year').focus(function() {
		fastCheckoutCc = 'false';
	});

	pmQuery('#paymill-card-cvc').focus(function() {
		fastCheckoutCc = 'false';
	});

	pmQuery('#paymill-card-holdername').focus(function() {
		fastCheckoutCc = 'false';
	});

	pmQuery('form[name^="process"]').submit(function(event) {
		if (preventDefault) {
			event.preventDefault();
			if (fastCheckoutCc === 'false') {
				paymillDebug('Paymill Creditcard: Payment method triggered');
				return paymillCc();
			} else {
				preventDefault = false;
				pmQuery('form[name^="process"]').append("<input type='hidden' name='paymillToken' value='dummyToken'/>");
				pmQuery('form[name^="process"]').submit();
			}
		}
	});
});