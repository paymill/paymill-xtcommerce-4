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
    
    private $_bridgeUrl = 'https://bridge.paymill.com/';
    
    private $_apiUrl    = 'https://api.paymill.com/v2/';
    
    public $data = array();

    public function __construct()
    {
        $this->_paymentProcessor = new Services_Paymill_PaymentProcessor();
        $this->_paymentProcessor->setApiUrl($this->_apiUrl);
        $this->allowed_subpayments = array('cc', 'elv');
    }
    
    public function checkoutProcessData()
    {
        global $xtLink;
        if (!$this->_isTokenAvailable($_SESSION)) {
            $xtLink->_redirect($xtLink->_link(array('page' => 'checkout', 'paction' => 'payment', 'conn' => 'SSL')));
        } else {
            //@todo kram setzen
            $this->_paymentProcessor->setAmount();
            
            
            if ($this->_paymentProcessor->processPayment()) {
                
                // Finish stuff
                
            } else {
                // Error stuff
            }
        }
    }
    
    public function checkoutPreData()
    {
        global $xtLink;
        if (!$this->_isTokenAvailable($_POST)) {
            $xtLink->_redirect($xtLink->_link(array('page' => 'checkout', 'paction' => 'payment', 'conn' => 'SSL')));
        } else {
            $_SESSION['paymill_token'] = $_POST['paymill_token'];
        }
    }
    
    public function log($message, $debugInfo)
    {
        $logfile = _SRV_WEBROOT . _SRV_WEB_PLUGINS . '/xt_paymill/log/log.txt';
        if (file_exists($logfile) && is_writable($logfile)) {
            $handle = fopen($logfile, 'a+');
            fwrite($handle, "[" . date(DATE_RFC822) . "] " . $message . "\n");
            fwrite($handle, "[" . date(DATE_RFC822) . "] " . $debugInfo . "\n");
            fclose($handle);
        }
    }
    
    private function _isTokenAvailable($data)
    {
        return array_key_exists('paymill_token', $data) && !empty($data['paymill_token']);
    }
}