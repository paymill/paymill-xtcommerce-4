<?php

// logger
function logAction($message)
{
    $logfile = _SRV_WEBROOT . '/plugins/xt_paymill/classes/paymill/log.txt';
    if (file_exists($logfile) && is_writable($logfile)) {
        $handle = fopen($logfile, 'a+');
        fwrite($handle, "[" . date(DATE_RFC822) . "] " . $message . "\n");
        fclose($handle);
    }
}

/**
 * Processes the payment against the paymill API
 * @param $params array The settings array
 * @return boolean
 */
function processPayment($params)
{
        // reformat paramters
        $params['currency'] = strtolower($params['currency']);
        // setup client params
        $client_params = array(
            'email' => $params['email'],
            'description' => $params['name']
        );
        // setup credit card params
        $payment_params = array(
            'token' => $params['token']
        );
        // setup transaction params
        $transactionParams = array(
            'amount' => $params['authorizedAmount'],
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

        $paymentObject = new Services_Paymill_Payments(
                        $params['privateKey'], $params['apiUrl']
        );
        // perform conection to the Paymill API and trigger the payment
        try {
            $payment = $paymentObject->create($payment_params);
            if (!isset($payment['id'])) {
                logAction('No Payment created: ' . var_export($payment, true));
                return false;
            } else {
                logAction('Payment created: ' . $payment['id']);
            }
            // create client
            $client_params['creditcard'] = $payment['id'];
            $client = $clientsObject->create($client_params);
            if (!isset($client['id'])) {
                logAction('No client created: ' . var_export($client, true));
                return false;
            } else {
                logAction('Client created: ' . $client['id']);
            }
            // create transaction
            $transactionParams['client'] = $client['id'];
            $transactionParams['payment'] = $payment['id'];
            $transaction = $transactionsObject->create($transactionParams);
            if (!confirmTransaction($transaction)) {
                return false;
            }

            if ($params['authorizedAmount'] !== $params['amount']) {
                if ($params['authorizedAmount'] > $params['amount']) {
                    require_once $params['libBase'] . 'Services/Paymill/Refunds.php';
                    // basketamount is lower than the authorized amount
                    $refundObject = new Services_Paymill_Refunds(
                                    $params['privateKey'], $params['apiUrl']
                    );
                    $refundTransaction = $refundObject->create(
                            array(
                                'transactionId' => $transaction['id'],
                                'params' => array(
                                    'amount' => $params['authorizedAmount'] - $params['amount']
                                )
                            )
                    );
                    if (isset($refundTransaction['data']['response_code']) && $refundTransaction['data']['response_code'] !== 20000) {
                        logAction("An Error occured: " . var_export($refundTransaction, true));
                        return false;
                    }
                    if (!isset($refundTransaction['data']['id'])) {
                        logAction("No Refund created" . var_export($refundTransaction, true));
                        return false;
                    } else {
                        logAction("Refund created: " . $refundTransaction['data']['id']);
                    }
                } else {
                    // basketamount is higher than the authorized amount (paymentfee etc.)
                    $secoundTransactionParams = array(
                        'amount' => $params['amount'] - $params['authorizedAmount'],
                        'currency' => $params['currency'],
                        'description' => $params['description']
                    );
                    $secoundTransactionParams['client'] = $client['id'];
                    $secoundTransactionParams['payment'] = $payment['id'];
                    if (!confirmTransaction($transactionsObject->create($secoundTransactionParams))) {
                        return false;
                    }
                }
            }
            return true;
    } catch (Services_Paymill_Exception $ex) {
        // paymill wrapper threw an exception
        call_user_func_array($logger, array("Exception thrown from paymill wrapper: " . $ex->getMessage()));
        return false;
    }
    return true;
}

function confirmTransaction($transaction)
    {
        if (isset($transaction['data']['response_code'])) {
            logAction("An Error occured: " . var_export($transaction, true));
            return false;
        }
        if (!isset($transaction['id'])) {
            logAction("No transaction created: " . var_export($transaction, true));
            return false;
        } else {
            logAction("Transaction created: " . $transaction['id']);
        }

        // check result
        if (is_array($transaction) && array_key_exists('status', $transaction)) {
            if ($transaction['status'] == "open") {
                // transaction was issued but status is open for any reason
                logAction("Status is open.");
                return false;
            } elseif ($transaction['status'] != "closed") {
                // another error occured
                logAction("Unknown error." . var_export($transaction, true));
                return false;
            }
        } else {
            // another error occured
            logAction("Transaction could not be issued.");
            return false;
        }
        return true;
    }

// name
$name = $_SESSION['customer']->customer_payment_address['customers_lastname']
        . ', '
        . $_SESSION['customer']->customer_payment_address['customers_firstname'];

$result = processPayment(array(
    'libVersion' => 'v2',
    'token' => $subpayment_code,
    'amount' => round($_SESSION['cart']->total_physical['plain'] * 100),
    'authorizedAmount' => $_SESSION['pigmbhPaymill']['authorizedAmount'],
    'currency' => 'EUR',
    'name' => $name,
    'email' => $_SESSION['customer']->customer_info['customers_email_address'],
    'description' => 'Order by ' . $name,
    'libBase' => _SRV_WEBROOT . '/plugins/xt_paymill/classes/paymill/v2/lib/',
    'privateKey' => XT_PAYMILL_PRIVATE_API_KEY,
    'apiUrl' => XT_PAYMILL_API_URL,
        ));

if ($result !== true) {
    $info->_addInfoSession("Payment could not be processed.");
    $tmp_link = $xtLink->_link(array('page' => 'checkout', 'paction' => 'confirmation', 'conn' => 'SSL'));
    $xtLink->_redirect($tmp_link);
}
?>