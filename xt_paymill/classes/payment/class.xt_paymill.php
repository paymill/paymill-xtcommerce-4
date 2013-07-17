<?php

require_once(dirname(dirname(__FILE__)) . '/lib/Services/Paymill/PaymentProcessor.php');
require_once(dirname(dirname(__FILE__)) . '/lib/Services/Paymill/LoggingInterface.php');

class xt_paymill implements Services_Paymill_LoggingInterface
{
    public $version = '2.1.0';
    
    public $subpayments = true;
    
    public $allowed_subpayments;
    
    /**
     * @var \Services_Paymill_PaymentProcessor
     */
    private $_paymentProcessor;
    
    private $_apiUrl    = 'https://api.paymill.com/v2/';
    
    public function __construct()
    {
        $this->_paymentProcessor = new Services_Paymill_PaymentProcessor();
        $this->_paymentProcessor->setApiUrl($this->_apiUrl);
        $this->_paymentProcessor->setLogger($this);
        $this->_paymentProcessor->setPrivateKey(trim($this->_getPaymentConfig('PRIVATE_API_KEY')));
        $this->_paymentProcessor->setSource($this->version . '_xt:Commerce_' . _SYSTEM_VERSION);
        $this->allowed_subpayments = array('cc', 'elv');
    }
    
    public function checkoutProcessData($subpayment_code, $payment_code)
    {
        global $xtLink, $currency;
        if (!$this->_isTokenAvailable($subpayment_code)) {
            $xtLink->_redirect($xtLink->_link(array('page' => 'checkout', 'paction' => 'payment', 'conn' => 'SSL')));
        } else {
            
            $name = $_SESSION['customer']->customer_payment_address['customers_firstname'] 
                  . ' ' 
                  . $_SESSION['customer']->customer_payment_address['customers_lastname'];
            
            $this->_paymentProcessor->setAmount((int) (round($_SESSION['cart']->total_physical['plain']) * 100));
            $this->_paymentProcessor->setToken($subpayment_code);
            $this->_paymentProcessor->setEmail($_SESSION['customer']->customer_info['customers_email_address']);
            $this->_paymentProcessor->setName($name);
            $this->_paymentProcessor->setCurrency($currency->code);
            $this->_paymentProcessor->setDescription('test'); //@todo set desc
            
            if ($payment_code === 'xt_paymill_cc') {
                $this->_paymentProcessor->setPreAuthAmount($_SESSION['paymillAuthorizedAmount']); //@todo set PreAuthAmount
            }

            if (!$this->_paymentProcessor->processPayment()) {
                //@todo set error message
                $xtLink->_redirect($xtLink->_link(array('page' => 'checkout', 'paction' => 'payment', 'conn' => 'SSL')));
            }
        }
    }
    
    public function log($message, $debugInfo)
    {
        if ($this->_getPaymentConfig('DEBUG_MODE')) {
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
            $value =  constant('XT_PAYMILL_' . $key);
        }
        
        return $value;
    }
}