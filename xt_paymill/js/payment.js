
function paymillDebug(message)
{
    if (debug === 'true') {
        console.log(message);
    }
}

function hideErrorBoxes(payment, limit)
{
    for (i = 0; i <= limit; i++) {
        $("#payment-error-" + payment + "-" + i).css('display', 'none');
    }
}