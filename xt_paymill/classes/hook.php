<?php

/**
 * Processes the payment against the paymill API
 * @param $params array The settings array
 * @return boolean
 */
function processPayment($params) {  
    
    // setup the logger
    $logger = $params['loggerCallback'];
    
    // setup client params
    $clientParams = array(
        'email' => $params['email'],
        'description' => $params['name']
    );

    // setup credit card params
    $creditcardParams = array(
        'token' => $params['token']
    );

    // setup transaction params
    $transactionParams = array(
        'amount' => $params['amount'],
        'currency' => $params['currency'],
        'description' => $params['description']
    );
            
    require_once $params['libBase'] . 'Services/Paymill/Transactions.php';
    require_once $params['libBase'] . 'Services/Paymill/Clients.php';
    require_once $params['libBase'] . 'Services/Paymill/Payments.php';

    $clientsObject = new Services_Paymill_Clients(
        $params['privateKey'], $params['apiUrl']
    );
    $transactionsObject = new Services_Paymill_Transactions(
        $params['privateKey'], $params['apiUrl']
    );
    $creditcardsObject = new Services_Paymill_Payments(
        $params['privateKey'], $params['apiUrl']
    );
    
    // perform conection to the Paymill API and trigger the payment
    try {

        // create card
        $creditcard = $creditcardsObject->create($creditcardParams);
        if (!isset($creditcard['id'])) {
            call_user_func_array($logger, array("No creditcard created: " . var_export($creditcard, true)));
            return false;
        } else {
            call_user_func_array($logger, array("Creditcard created: " . $creditcard['id']));
        }

        // create client
        $clientParams['creditcard'] = $creditcard['id'];
        $client = $clientsObject->create($clientParams);
        if (!isset($client['id'])) {
            call_user_func_array($logger, array("No client created" . var_export($client, true)));
            return false;
        } else {
            call_user_func_array($logger, array("Client created: " . $client['id']));
        }

        // create transaction
        $transactionParams['client'] = $client['id'];
        $transactionParams['payment'] = $creditcard['id'];
        $transaction = $transactionsObject->create($transactionParams);
        if (!isset($transaction['id'])) {
            call_user_func_array($logger, array("No transaction created" . var_export($transaction, true)));
            return false;
        } else {
            call_user_func_array($logger, array("Transaction created: " . $transaction['id']));
        }

        // check result
        if (is_array($transaction) && array_key_exists('status', $transaction)) {
            if ($transaction['status'] == "closed") {
                // transaction was successfully issued
                return true;
            } elseif ($transaction['status'] == "open") {
                // transaction was issued but status is open for any reason
                call_user_func_array($logger, array("Status is open."));
                return false;
            } else {
                // another error occured
                call_user_func_array($logger, array("Unknown error." . var_export($transaction, true)));
                return false;
            }
        } else {
            // another error occured
            call_user_func_array($logger, array("Transaction could not be issued."));
            return false;
        }

    } catch (Services_Paymill_Exception $ex) {
        // paymill wrapper threw an exception
        call_user_func_array($logger, array("Exception thrown from paymill wrapper: " . $ex->getMessage()));
        return false;
    }        
    
    return true;
}

// logger
function logAction($message) {
    $logfile = SRV_WEBROOT . '/plugins/xt_paymill/classes/paymill/log.txt';
    if (file_exists($logfile ) && is_writable($logfile)) {
        $handle = fopen($logfile, 'a');
        fwrite($handle, "[" . date(DATE_RFC822) . "] " . $message . "\n");
        fclose($handle);
    }
}

// name 
$name = $_SESSION['customer']->customer_payment_address['customers_lastname'] 
    . ', ' 
    . $_SESSION['customer']->customer_payment_address['customers_firstname'];

$result = processPayment(array(
    'libVersion' => 'v2',
    'token' => $subpayment_code,
    'amount' => ((int)($_SESSION['cart']->total['plain'] * 100)),
    'currency' => 'EUR',
    'name' => $name,
    'email' => $_SESSION['customer']->customer_info['customers_email_address'],
    'description' => 'Order by ' . $name,
    'libBase' => _SRV_WEBROOT . '/plugins/xt_paymill/classes/paymill/v2/lib/',
    'privateKey' => XT_PAYMILL_PRIVATE_API_KEY,
    'apiUrl' => XT_PAYMILL_API_URL,
    'loggerCallback' => 'logAction'
));

if ($result !== true) {
    $info->_addInfoSession("Payment could not be processed.");
    $tmp_link  = $xtLink->_link(array('page'=>'checkout', 'paction'=>'confirmation', 'conn'=>'SSL'));
    $xtLink->_redirect($tmp_link);
}

?>