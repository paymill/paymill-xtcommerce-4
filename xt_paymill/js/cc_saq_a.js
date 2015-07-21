$(document).ready(function ()
{
    preventDefault = true;
    if (fastCheckoutCc === 'false') {
        paymillEmbedFrame();
    }

    $('form[name^="process"]').submit(function (event)
    {
        if (preventDefault) {
            event.preventDefault();
            console.log(fastcheckoutChange);
            if (!fastcheckoutChange) {
                preventDefault = false;
                $('form[name^="process"]').append("<input type='hidden' name='paymillToken' value='dummyToken'/>");
                $('form[name^="process"]').submit();
            } else {
                paymillDebug('Paymill Creditcard: Payment method triggered');
                paymill.createTokenViaFrame({
                    amount_int: amount,
                    currency: currency
                }, paymillCcResponseHandler);
                return false;
            }
        }
    });

    $('#paymill_fast_checkout_iframe_change').click(function (event) {
        $("#paymill_fast_checkout_box").remove();
        paymillEmbedFrame();
    });
});

function PaymillFrameResponseHandler(error, result)
{
    if (error) {
        paymillDebug("iFrame load failed with " + error.apierror + error.message);
    } else {
        paymillDebug("iFrame successfully loaded");
    }
}

function paymillEmbedFrame()
{
    fastcheckoutChange = true;
    paymill.embedFrame('paymill-cc-inputs', {lang: lang['iframe_lang']}, PaymillFrameResponseHandler);
}

function paymillCcResponseHandler(error, result)
{
    paymillDebug('Paymill: Start response handler');
    if (error) {
        paymillDebug('An API error occured:' + error.apierror);
    } else {
        preventDefault = false;
        paymillDebug('Received a token: ' + result.token);
        $('form[name^="process"]').append("<input type='hidden' name='paymillToken' value='" + result.token + "'/>");
        $('form[name^="process"]').submit();
    }
}