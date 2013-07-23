<?php

defined('_VALID_CALL') or die('Direct Access is not allowed.');

require_once(dirname(__FILE__) . '/lib/Services/Paymill/PaymentProcessor.php');
require_once(dirname(__FILE__) . '/lib/Services/Paymill/LoggingInterface.php');
require_once(dirname(__FILE__) . '/helpers/FastCheckout.php');

class xt_paymill implements Services_Paymill_LoggingInterface
{

    public $version = '2.1.0';
    public $subpayments = true;
    public $allowed_subpayments;
    public $data = array();

    /**
     * @var \Services_Paymill_PaymentProcessor
     */
    private $_paymentProcessor;
    private $_apiUrl = 'https://api.paymill.com/v2/';

    public function __construct()
    {
        global $page;
        
        $this->_fastCheckout = new FastCheckout();
        
        if ($page->page_name == 'checkout' && $page->page_action == 'payment') {
            $this->_setCheckoutData();
        }
        
        $this->_paymentProcessor = new Services_Paymill_PaymentProcessor();
        $this->_paymentProcessor->setApiUrl($this->_apiUrl);
        $this->_paymentProcessor->setLogger($this);
        $this->_paymentProcessor->setPrivateKey(trim($this->_getPaymentConfig('PRIVATE_API_KEY')));
        $this->_paymentProcessor->setSource($this->version . '_xt:Commerce_' . _SYSTEM_VERSION);
        $this->allowed_subpayments = array('cc', 'dd');    
    }
    
    private function _setCheckoutData()
    {
        global $currency;
        
        $_SESSION['paymillAuthorizedAmount'] = (int) round(
            ($_SESSION['cart']->total_physical['plain'] + XT_PAYMILL_DIFFERENT_AMOUNT) * 100
        );
        
        $this->data['xt_paymill']['fast_checkout_cc'] = $this->_fastCheckout->canCustomerFastCheckoutCcTemplate(
            $_SESSION["customer"]->customers_id
        );
        
        $this->data['xt_paymill']['fast_checkout_elv'] = $this->_fastCheckout->canCustomerFastCheckoutElvTemplate(
            $_SESSION["customer"]->customers_id
        );
        
        $this->data['xt_paymill']['currency'] = $currency->code;
        $this->data['xt_paymill']['amount'] = $_SESSION['paymillAuthorizedAmount'];
        
        if (array_key_exists('xt_paymill_cc_error', $_SESSION)) {
            $this->data['xt_paymill']['error_cc'] = $_SESSION['xt_paymill_cc_error'];
            unset($_SESSION['xt_paymill_cc_error']);
        }
        
        if (array_key_exists('xt_paymill_dd_error', $_SESSION)) {
            $this->data['xt_paymill']['error_elv'] = $_SESSION['xt_paymill_dd_error'];
            unset($_SESSION['xt_paymill_dd_error']);
        }
    }

    public function checkoutProcessData($subpayment_code)
    {
        global $xtLink, $currency;
        $code = 'xt_paymill_' . $subpayment_code;
        $token = $_SESSION['token'];
        if (!$this->_isTokenAvailable($token)) {
            $_SESSION[$code . '_error'] = TEXT_PAYMILL_ERR_TOKEN;
            $xtLink->_redirect($xtLink->_link(array('page' => 'checkout', 'paction' => 'payment', 'conn' => 'SSL')));
        } else {

            $name = $_SESSION['customer']->customer_payment_address['customers_firstname']
                  . ' '
                  . $_SESSION['customer']->customer_payment_address['customers_lastname'];

            $this->_paymentProcessor->setAmount((int) round($_SESSION['cart']->total_physical['plain'] * 100));
            $this->_paymentProcessor->setToken($token);
            $this->_paymentProcessor->setEmail($_SESSION['customer']->customer_info['customers_email_address']);
            $this->_paymentProcessor->setName($name);
            $this->_paymentProcessor->setCurrency($currency->code);
            $this->_paymentProcessor->setDescription(_STORE_NAME . ' Order ID: ' . $this->_getNextOrderId());

            if ($code === 'xt_paymill_cc') {
                $this->_paymentProcessor->setPreAuthAmount($_SESSION['paymillAuthorizedAmount']);
            }

            if ($this->_fastCheckout->canCustomerFastCheckoutCc($_SESSION['customer']->customers_id) && $code === 'xt_paymill_cc') {
                $data = $this->_fastCheckout->loadFastCheckoutData($_SESSION['customer']->customers_id);
                $this->_paymentProcessor->setClientId($data->clientID);
                if (!empty($data->paymentID_CC)) {
                    $this->_paymentProcessor->setPaymentId($data->paymentID_CC);
                }
            }
            
            if ($this->_fastCheckout->canCustomerFastCheckoutElv($_SESSION['customer']->customers_id) && $code === 'xt_paymill_dd') {
                $data = $this->_fastCheckout->loadFastCheckoutData($_SESSION['customer']->customers_id);
                $this->_paymentProcessor->setClientId($data->clientID);
                if ($data->paymentID_ELV) {
                    $this->_paymentProcessor->setPaymentId($data->paymentID_ELV);
                }
            }
            
            if (!$this->_paymentProcessor->processPayment()) {
                $_SESSION[$code . '_error'] = TEXT_PAYMILL_ERR_ORDER;
                $xtLink->_redirect($xtLink->_link(array('page' => 'checkout', 'paction' => 'payment', 'conn' => 'SSL')));
            }
            
            if ($this->_getPaymentConfig('FAST_CHECKOUT') === 'true') {

                if ($code === 'xt_paymill_cc') {
                    $this->_fastCheckout->saveCcIds(
                        $_SESSION['customer']->customers_id, 
                        $this->_paymentProcessor->getClientId(), 
                        $this->_paymentProcessor->getPaymentId()
                    );
                }

                if ($code === 'xt_paymill_dd') {
                    $this->_fastCheckout->saveElvIds(
                        $_SESSION['customer']->customers_id, 
                        $this->_paymentProcessor->getClientId(), 
                        $this->_paymentProcessor->getPaymentId()
                    );
                }
            }

            unset($_SESSION['token']);
        }
    }

    private function _getNextOrderId()
    {
        return $_SESSION['last_order_id'] + 1;
    }

    public function log($message, $debugInfo)
    {
        if ($this->_getPaymentConfig('DEBUG_MODE') === 'true') {
            $logfile = _SRV_WEBROOT . _SRV_WEB_PLUGINS . '/xt_paymill/log/log.txt';
            if (file_exists($logfile) && is_writable($logfile)) {
                $handle = fopen($logfile, 'a+');
                fwrite($handle, "[" . date(DATE_RFC822) . "] " . $message . "\n");
                fwrite($handle, "[" . date(DATE_RFC822) . "] " . $debugInfo . "\n");
                fclose($handle);
            }
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

}