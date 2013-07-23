
$(document).ready(function () 
{
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
                $('form[name^="payment"]').append("<input type='hidden' name='paymill_token' value='" + result.token + "'/>");
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
                $('form[name^="payment"]').append("<input type='hidden' id='paymill_token' name='paymill_token' value='" + result.token + "'/>");
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
            cardholdername : $('#paymill-card-holdername').val(),
            amount_int : amount,
            currency : currency
        }, paymillCcResponseHandler);
        
        return false;
    }
    
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
            }
        } else if($("input[name='selected_payment']:checked").val() === 'xt_paymill:elv') {
            if (fastCheckoutElv == 'false') {
                paymillDebug('Paymill ELV: Payment method triggered');
                return paymillElv();
            }
        }
        
        $('form[name^="payment"]').get(0).submit();
    });
});
