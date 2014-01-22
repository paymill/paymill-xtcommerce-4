<?php

defined('_VALID_CALL') or die('Direct Access is not allowed.');

require_once(dirname(__FILE__) . '/lib/Services/Paymill/PaymentProcessor.php');
require_once(dirname(__FILE__) . '/lib/Services/Paymill/LoggingInterface.php');
require_once(dirname(__FILE__) . '/lib/Services/Paymill/Clients.php');
require_once(dirname(__FILE__) . '/lib/Services/Paymill/Payments.php');
require_once(dirname(__FILE__) . '/lib/Services/Paymill/Transactions.php');
require_once(dirname(__FILE__) . '/helpers/FastCheckout.php');

class xt_paymill implements Services_Paymill_LoggingInterface
{
    /**
     * Version
     * @var string
     */
    public $version = '2.1.0';

    /**
     * @var boolean
     */
    public $subpayments = true;

    /**
     * Allowed subpayments (cc, dd)
     * @var array
     */
    public $allowed_subpayments;

    /**
     * Template data
     * @var array
     */
    public $data = array();

    /**
     * @var \Services_Paymill_PaymentProcessor
     */
    private $_paymentProcessor;

    /**
     * @var \Services_Paymill_Clients
     */
    private $_clients;

    /**
     * @var \Services_Paymill_Clients
     */
    private $_payments;

    /**
     * @var \Services_Paymill_Transactions
     */
    private $_transactions;
    
    /**
     * Api endpoint
     * @var string
     */
    private $_apiUrl = 'https://api.paymill.com/v2/';

    public function __construct()
    {
        global $page;

        $this->_fastCheckout = new FastCheckout();

        $this->_transactions = new Services_Paymill_Transactions(
            trim($this->_getPaymentConfig('PRIVATE_API_KEY')), $this->_apiUrl
        );

        $this->_payments = new Services_Paymill_Payments(
            trim($this->_getPaymentConfig('PRIVATE_API_KEY')), $this->_apiUrl
        );

        $this->_clients = new Services_Paymill_Clients(
            trim($this->_getPaymentConfig('PRIVATE_API_KEY')), $this->_apiUrl
        );

        if ($page->page_name == 'checkout' && $page->page_action == 'confirmation') {
            $this->_setCheckoutData();
        }

        $this->_paymentProcessor = new Services_Paymill_PaymentProcessor();
        $this->_paymentProcessor->setApiUrl($this->_apiUrl);
        $this->_paymentProcessor->setLogger($this);
        $this->_paymentProcessor->setPrivateKey(trim($this->_getPaymentConfig('PRIVATE_API_KEY')));
        $this->_paymentProcessor->setSource($this->version . '_xt:Commerce_' . _SYSTEM_VERSION);
        $this->allowed_subpayments = array('cc', 'dd');

        if ($page->page_name == 'checkout' && $page->page_action == 'success') {
            $this->_success();
        }
        
        if ($page->page_name == 'checkout' && $page->page_action == 'payment') {  
            if (array_key_exists('xt_paymill_cc_error', $_SESSION)) {
                $this->data['xt_paymill']['error_cc'] = $_SESSION['xt_paymill_cc_error'];
                unset($_SESSION['xt_paymill_cc_error']);
            }

            if (array_key_exists('xt_paymill_dd_error', $_SESSION)) {
                $this->data['xt_paymill']['error_elv'] = $_SESSION['xt_paymill_dd_error'];
                unset($_SESSION['xt_paymill_dd_error']);
            }
        }
    }
    
    /**
     * Return message for the given error code
     * 
     * @param string $code
     * @return string
     */
    private function _getErrorMessage($code)
    {
        $langKey = 'PAYMILL_' . $code;
        if (!defined($langKey)) {
            $langKey = 'PAYMILL_10001';
        }
        
        return constant($langKey);
    }
    
    private function _canFastCheckoutCc()
    {
        $fastCheckout = 'false';
        if ($this->_fastCheckout->canCustomerFastCheckoutCc($_SESSION["customer"]->customers_id)) {
            $data = $this->_fastCheckout->loadFastCheckoutData($_SESSION['customer']->customers_id);
            $payment = $this->_payments->getOne($data->paymentID_CC);
            if (array_key_exists('last4', $payment)) {
                $fastCheckout = 'true';
            }
        }
     
        $this->data['xt_paymill']['fast_checkout_cc'] = $fastCheckout;
    }
    
    private function _canFastCheckoutElv()
    {
        $fastCheckout = 'false';
        if ($this->_fastCheckout->canCustomerFastCheckoutElv($_SESSION["customer"]->customers_id)) {
            $data = $this->_fastCheckout->loadFastCheckoutData($_SESSION['customer']->customers_id);
            $payment = $this->_payments->getOne($data->paymentID_ELV);
            if (array_key_exists('code', $payment)) {
                $fastCheckout = 'true';
            }
        }
     
        $this->data['xt_paymill']['fast_checkout_elv'] = $fastCheckout;
    }
    
    private function _setCheckoutData()
    {
        global $currency;

        $this->_canFastCheckoutCc();
        $this->_canFastCheckoutElv();

        $data = $this->_fastCheckout->loadFastCheckoutData($_SESSION['customer']->customers_id);

        if ($this->data['xt_paymill']['fast_checkout_cc'] === 'true') {
            $payment = $this->_payments->getOne($data->paymentID_CC);
            $this->data['xt_paymill']['cc_number'] = '************' . $payment['last4'];
            $this->data['xt_paymill']['expire_date'] = $payment['expire_year'] . '-' . $payment['expire_month'] . '-01';
            $this->data['xt_paymill']['cvc'] = '***';
            $this->data['xt_paymill']['card_holder'] = $payment['card_holder'];
            $this->data['xt_paymill']['card_brand'] = $payment['card_type'];
        }

        if ($this->data['xt_paymill']['fast_checkout_elv'] === 'true') {
            $payment = $this->_payments->getOne($data->paymentID_ELV);
            $this->data['xt_paymill']['account_holder'] = $payment['holder'];
            if (array_key_exists('code', $payment)) {
                $this->data['xt_paymill']['bank_code'] = $payment['code'];
                $this->data['xt_paymill']['account_number'] = $payment['account'];
            }

            if (array_key_exists('iban', $payment)) {
                $this->data['xt_paymill']['iban'] = $payment['iban'];
                $this->data['xt_paymill']['bic'] = $payment['bic'];
            }
        }

        $this->data['xt_paymill']['currency'] = $currency->code;
        $this->data['xt_paymill']['amount'] = (int) round(
            $_SESSION['cart']->total_physical['plain'] * 100
        );
    }

    public function checkoutProcessData()
    {
        global $xtLink;
        $code = 'xt_paymill_' . $_SESSION['selected_payment_sub'];
        $token = $_POST['paymillToken'];
        if (!$this->_isTokenAvailable($token)) {
            $_SESSION[$code . '_error'] = TEXT_PAYMILL_ERR_TOKEN;
            $xtLink->_redirect($xtLink->_link(array('page' => 'checkout', 'paction' => 'payment', 'conn' => 'SSL')));
        } else {

            $this->_setTransaction();

            $data = $this->_fastCheckout->loadFastCheckoutData($_SESSION['customer']->customers_id);
            if (!empty($data->clientID)) {
                $this->_existingClient($data);
            }

            if ($token === 'dummyToken') {
                $this->_fastCheckout($code);
            }

            $this->_paymentProcessor->setToken($token);
            unset($_SESSION['token']);

            $result = $this->_paymentProcessor->processPayment();
            
            if (!$result) {
                $_SESSION[$code . '_error'] = $this->_getErrorMessage($this->_paymentProcessor->getErrorCode());
                $xtLink->_redirect($xtLink->_link(array('page' => 'checkout', 'paction' => 'payment', 'conn' => 'SSL')));
            }

            if ($this->_getPaymentConfig('FAST_CHECKOUT') === 'true') {
                $this->_savePayment($code);
            }

            $_SESSION['paymillTransactionId'] = $this->_paymentProcessor->getTransactionId();
        }
    }

    private function _savePayment($code)
    {
        if ($code === 'xt_paymill_cc') {
            $this->_fastCheckout->saveCcIds(
                $_SESSION['customer']->customers_id, $this->_paymentProcessor->getClientId(), $this->_paymentProcessor->getPaymentId()
            );
        }

        if ($code === 'xt_paymill_dd') {
            $this->_fastCheckout->saveElvIds(
                $_SESSION['customer']->customers_id, $this->_paymentProcessor->getClientId(), $this->_paymentProcessor->getPaymentId()
            );
        }
    }

    private function _setTransaction()
    {
        global $currency;

        $name = $_SESSION['customer']->customer_payment_address['customers_firstname']
            . ' '
            . $_SESSION['customer']->customer_payment_address['customers_lastname'];

        $this->_paymentProcessor->setAmount((int) round($_SESSION['cart']->total_physical['plain'] * 100));

        $this->_paymentProcessor->setEmail($_SESSION['customer']->customer_info['customers_email_address']);
        $this->_paymentProcessor->setName($name);
        $this->_paymentProcessor->setCurrency($currency->code);
        $this->_paymentProcessor->setDescription(_STORE_NAME);
    }

    private function _existingClient($data)
    {
        $client = $this->_clients->getOne($data->clientID);
        if ($client['email'] !== $_SESSION['customer']->customer_info['customers_email_address']) {
            $this->_clients->update(
                array(
                    'id' => $data->clientID,
                    'email' => $_SESSION['customer']->customer_info['customers_email_address']
                )
            );
        }
        
        if (array_key_exists('email', $client)) {
            $this->_paymentProcessor->setClientId($client['id']);
        }        
    }

    private function _fastCheckout($code)
    {
        if ($this->_fastCheckout->canCustomerFastCheckoutCc($_SESSION['customer']->customers_id) && $code === 'xt_paymill_cc') {
            $data = $this->_fastCheckout->loadFastCheckoutData($_SESSION['customer']->customers_id);
            if (!empty($data->paymentID_CC)) {
                $this->_paymentProcessor->setPaymentId($data->paymentID_CC);
            }
        }

        if ($this->_fastCheckout->canCustomerFastCheckoutElv($_SESSION['customer']->customers_id) && $code === 'xt_paymill_dd') {
            $data = $this->_fastCheckout->loadFastCheckoutData($_SESSION['customer']->customers_id);
            if ($data->paymentID_ELV) {
                $this->_paymentProcessor->setPaymentId($data->paymentID_ELV);
            }
        }
    }

    public function log($message, $debugInfo)
    {
        global $db;
        
        if (is_null($message)) {
            $message = '';
        }
        
        if (is_null($debugInfo)) {
            $debugInfo = '';
        }
        
        if ($this->_getPaymentConfig('DEBUG_MODE') === 'true') {
            $db->Execute($db->Prepare("INSERT INTO `pi_paymill_logging` (debug, message) VALUES(?, ?)"), array($debugInfo, $message));
        }
    }

    private function _isTokenAvailable($data)
    {
        return !empty($data);
    }

    private function _getPaymentConfig($key)
    {
        $value = null;
        if (defined('XT_PAYMILL_' . $key)) {
            $value = constant('XT_PAYMILL_' . $key);
        }

        return $value;
    }

    private function _success()
    {
        $this->_transactions->update(
            array(
                'id' => $_SESSION['paymillTransactionId'],
                'description' => ' OrderID: ' . $_SESSION['success_order_id'] . ' ' . _STORE_NAME
            )
        );

        unset($_SESSION['paymillTransactionId']);
    }
}