<?php

require_once(dirname(dirname(__FILE__)) . '/lib/Services/Paymill/PaymentProcessor.php');
require_once(dirname(dirname(__FILE__)) . '/lib/Services/Paymill/LoggingInterface.php');

class Paymill implements Services_Paymill_LoggingInterface
{
    /**
     * @var \Services_Paymill_PaymentProcessor
     */
    private $_paymentProcessor;
    
    private $_bridgeUrl = 'https://bridge.paymill.com/';
    
    private $_apiUrl    = 'https://api.paymill.com/v2/';
    
    public $version = '2.1.0';
    
    public $subpayments = true;
    
    public function __construct()
    {
        $this->_paymentProcessor = new Services_Paymill_PaymentProcessor();
        $this->_paymentProcessor->setApiUrl($this->_apiUrl);
    }
    
    public function log($message, $debugInfo)
    {
        $logfile = _SRV_WEBROOT . '/plugins/xt_paymill/log/log.txt';
        if (file_exists($logfile) && is_writable($logfile)) {
            $handle = fopen($logfile, 'a+');
            fwrite($handle, "[" . date(DATE_RFC822) . "] " . $message . "\n");
            fwrite($handle, "[" . date(DATE_RFC822) . "] " . $debugInfo . "\n");
            fclose($handle);
        }
    }
}