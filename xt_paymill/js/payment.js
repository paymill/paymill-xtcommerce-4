
$(document).ready(function () 
{
    $('#paymill-card-number').keyup(function() {
        switch(paymill.cardType($('#paymill-card-number').val()).toLowerCase()){
            case 'visa':
                $('.card-icon').html('<img src="plugins/xt_paymill/images/32x20_visa.png" >');
                $('.card-icon').show();
                break;
            case 'mastercard':
                $('.card-icon').html('<img src="plugins/xt_paymill/images/32x20_mastercard.png" >');
                $('.card-icon').show();
                break;
            case 'american express':
                $('.card-icon').html('<img src="plugins/xt_paymill/images/32x20_amex.png" >');
                $('.card-icon').show();
                break;
            case 'jcb':
                $('.card-icon').html('<img src="plugins/xt_paymill/images/32x20_jcb.png" >');
                $('.card-icon').show();
                break;
            case 'maestro':
                $('.card-icon').html('<img src="plugins/xt_paymill/images/32x20_maestro.png" >');
                $('.card-icon').show();
                break;
            case 'diners club':
                $('.card-icon').html('<img src="plugins/xt_paymill/images/32x20_dinersclub.png" >');
                $('.card-icon').show();
                break;
            case 'discover':
                $('.card-icon').html('<img src="plugins/xt_paymill/images/32x20_discover.png" >');
                $('.card-icon').show();
                break;
            case 'unionpay':
                $('.card-icon').html('<img src="plugins/xt_paymill/images/32x20_unionpay.png" >');
                $('.card-icon').show();
                break;
            case 'unknown':
            default:
                $('.card-icon').hide();
                break;
        }
        
        $('.card-icon :first-child').css('position','absolute');
    });

    
    
    function paymillElvResponseHandler(error, result) 
    {
        if (flag) {
            paymillDebug('Paymill: Start response handler');
            if (error) {
                paymillDebug('An API error occured:' + error.apierror);
                $("#payment-errors-elv").text(error.apierror);
                $("#payment-errors-elv").css('display', 'block');
            } else {
                flag = false;
                paymillDebug('Received a token: ' + result.token);
                $("#payment-errors-elv").text("");
                $("#payment-errors-elv").css('display', 'none');
                $('#paymill_selector_elv').val('xt_paymill:' + '_dd_' + result.token);
                $('form[name^="payment"]').get(1).submit();
            }
        }
    }
    
    function paymillCcResponseHandler(error, result) 
    {
        if (flag) {
            paymillDebug('Paymill: Start response handler');
            if (error) {
                paymillDebug('An API error occured:' + error.apierror);
                $("#payment-errors-cc").text(error.apierror);
                $("#payment-errors-cc").css('display', 'block');
            } else {
                flag = false;
                paymillDebug('Received a token: ' + result.token);
                $("#payment-errors-cc").text("");
                $("#payment-errors-cc").css('display', 'none');
                $('#paymill_selector_cc').val('xt_paymill:' + '_cc_' + result.token);
                $('form[name^="payment"]').get(1).submit();
            }
        }
    }
    
    function hideErrorBoxes(payment, limit)
    {
        for (i = 0; i <= limit; i++) {
            $("#payment-error-" + payment + "-" + i).css('display', 'none');
        }
    }
    
    function paymillCc()
    {
        paymillDebug('Paymill Creditcard: Start form validation');
        
        hideErrorBoxes('cc', 4);
        
        var ccErrorFlag = true;
        
        if (false === paymill.validateCardNumber($('#paymill-card-number').val())) {
            $("#payment-error-cc-1").text(lang['card_number_invalid']);
            $("#payment-error-cc-1").css('display', 'block');
            ccErrorFlag = false;
        }
        

        if (false === paymill.validateExpiry($('select[name="Paymill_Month"]').val(), $('select[name="Paymill_Year"]').val())) {
            $("#payment-error-cc-4").text(lang['expiration_date_invalid']);
            $("#payment-error-cc-4").css('display', 'block');
            ccErrorFlag = false;
        }
        
        if (false === paymill.validateCvc($('#paymill-card-cvc').val())) {
            $("#payment-error-cc-2").text(lang['verfication_number_invalid']);
            $("#payment-error-cc-2").css('display', 'block');
            ccErrorFlag = false;
        }
        
        if ($('#paymill-card-holdername').val() === "") {
            $("#payment-error-cc-3").text(lang['card_holder_invalid']);
            $("#payment-error-cc-3").css('display', 'block');
            ccErrorFlag = false;
        }
        
        if (!ccErrorFlag) {
            return ccErrorFlag;
        }
        
        paymill.createToken({
            number : $('#paymill-card-number').val(),
            exp_month : $('select[name="Paymill_Month"]').val(),
            exp_year : $('select[name="Paymill_Year"]').val(),
            cvc : $('#paymill-card-cvc').val(),
            cardholder : $('#paymill-card-holdername').val(),
            amount_int : amount,
            currency : currency
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
    
    function paymillElv()
    {
        paymillDebug('Paymill ELV: Start form validation');
        
        hideErrorBoxes('elv', 3);
        
        var elvErrorFlag = true;
        
        if (false === paymill.validateAccountNumber($('#paymill-account-number').val())) {
            $("#payment-error-elv-1").text(lang['account_number_invalid']);
            $("#payment-error-elv-1").css('display', 'block');
            elvErrorFlag = false;
        }
        
        if (false === paymill.validateBankCode($('#paymill-bank-code').val())) {
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
            number:        $('#paymill-account-number').val(),
            bank:          $('#paymill-bank-code').val(),
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
    
    function paymillDebug(message)
    {
        if (debug === 'true') {
            console.log(message);
        }
    }
    
    $('form[name^="payment"]').submit(function(event) {
        if ($("input[name='selected_payment']:checked").val() === 'xt_paymill:cc') {
            if (fastCheckoutCc == 'false') {
                paymillDebug('Paymill Creditcard: Payment method triggered');
                return paymillCc();
            } else {
                $('#paymill_selector_cc').val('xt_paymill:' + '_cc_dummyToken');
            }
        } else if($("input[name='selected_payment']:checked").val() === 'xt_paymill:elv') {
            if (fastCheckoutElv == 'false') {
                paymillDebug('Paymill ELV: Payment method triggered');
                return paymillElv();
            } else {
                $('#paymill_selector_elv').val('xt_paymill:' + '_dd_dummyToken');
            }
        }
        
        $('form[name^="payment"]').get(0).submit();
    });
});
