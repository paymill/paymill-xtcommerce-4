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
    protected $_table = 'pi_paymill_logging';
    protected $_tableLang = null;
    protected $_tableSeo = null;
    protected $_masterKey = 'id';

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

        $this->_setCheckoutData();

        $this->_paymentProcessor = new Services_Paymill_PaymentProcessor();
        $this->_paymentProcessor->setApiUrl($this->_apiUrl);
        $this->_paymentProcessor->setLogger($this);
        $this->_paymentProcessor->setPrivateKey(trim($this->_getPaymentConfig('PRIVATE_API_KEY')));
        $this->_paymentProcessor->setSource($this->version . '_xt:Commerce_' . _SYSTEM_VERSION);
        $this->allowed_subpayments = array('cc', 'dd');

        if ($page->page_name == 'checkout' && $page->page_action == 'success') {
            $this->_success();
        }
    }

    private function _setCheckoutData()
    {
        global $currency, $page;

        if ($page->page_name == 'checkout' && $page->page_action == 'payment') {
            unset($_SESSION['paymillAuthorizedAmount']);
            $_SESSION['paymillAuthorizedAmount'] = (int) round(
                ($_SESSION['cart']->total_physical['plain'] + $this->_getPaymentConfig('DIFFERENT_AMOUNT')) * 100
            );
        }

        $this->data['xt_paymill']['fast_checkout_cc'] = $this->_fastCheckout->canCustomerFastCheckoutCcTemplate(
            $_SESSION["customer"]->customers_id
        );

        $this->data['xt_paymill']['fast_checkout_elv'] = $this->_fastCheckout->canCustomerFastCheckoutElvTemplate(
            $_SESSION["customer"]->customers_id
        );

        $data = $this->_fastCheckout->loadFastCheckoutData($_SESSION['customer']->customers_id);

        if (!empty($data->paymentID_CC)) {
            $payment = $this->_payments->getOne($data->paymentID_CC);
            $this->data['xt_paymill']['cc_number'] = '************' . $payment['last4'];
            $this->data['xt_paymill']['expire_date'] = $payment['expire_year'] . '-' . $payment['expire_month'] . '-01';
            $this->data['xt_paymill']['cvc'] = '***';
            $this->data['xt_paymill']['card_holder'] = $payment['card_holder'];
            $this->data['xt_paymill']['card_brand'] = $payment['card_type'];
        }

        if (!empty($data->paymentID_ELV)) {
            $payment = $this->_payments->getOne($data->paymentID_ELV);
            $this->data['xt_paymill']['bank_code'] = $payment['code'];
            $this->data['xt_paymill']['account_holder'] = $payment['holder'];
            $this->data['xt_paymill']['account_number'] = $payment['account'];
        }

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
        global $xtLink;
        $code = 'xt_paymill_' . $subpayment_code;
        $token = $_SESSION['token'];
        if (!$this->_isTokenAvailable($token)) {
            $_SESSION[$code . '_error'] = TEXT_PAYMILL_ERR_TOKEN;
            $xtLink->_redirect($xtLink->_link(array('page' => 'checkout', 'paction' => 'payment', 'conn' => 'SSL')));
        } else {

            $this->_setTransaction($code);

            $data = $this->_fastCheckout->loadFastCheckoutData($_SESSION['customer']->customers_id);
            if (!empty($data->clientID)) {
                $this->_existingClient($data);
            }

            if ($token === 'dummyToken') {
                $this->_fastCheckout($code);
            }

            $this->_paymentProcessor->setToken($token);
            unset($_SESSION['token']);

            if (!$this->_paymentProcessor->processPayment()) {
                $_SESSION[$code . '_error'] = TEXT_PAYMILL_ERR_ORDER;
                $xtLink->_redirect($xtLink->_link(array('page' => 'checkout', 'paction' => 'payment', 'conn' => 'SSL')));
            }

            if ($this->_getPaymentConfig('FAST_CHECKOUT') === 'true') {
                $this->_savePayment($code);
            }

            unset($_SESSION['paymillAuthorizedAmount']);

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

    private function _setTransaction($code)
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

        if ($code === 'xt_paymill_cc') {
            $this->_paymentProcessor->setPreAuthAmount($_SESSION['paymillAuthorizedAmount']);
            unset($_SESSION['paymillAuthorizedAmount']);
        }
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

        $this->_paymentProcessor->setClientId($client['id']);
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
        if ($this->_getPaymentConfig('DEBUG_MODE') === 'true') {
            $db->Execute("INSERT INTO `pi_paymill_logging` (debug, message) VALUES('" . $debugInfo . "', '" . $message . "')");
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
                'description' => _STORE_NAME . ' Order ID: ' . $_SESSION['success_order_id']
            )
        );


        unset($_SESSION['paymillTransactionId']);
    }

    function setPosition($position)
    {
        $this->position = $position;
    }

    function _getParams()
    {
        $header = array();
        $header['paymill_id'] = array('type' => 'hidden');
        $header['language_code'] = array('type' => 'hidden');
        $header['customers_id'] = array('type' => 'hidden');

        $params['header'] = $header;
        $params['master_key'] = $this->_masterKey;
        $params['default_sort'] = $this->_masterKey;
        $params['SortField'] = $this->_masterKey;
        $params['SortDir'] = "DESC";

        $params['display_checkCol'] = true;
        $params['display_statusTrueBtn'] = false;
        $params['display_statusFalseBtn'] = false;
        $params['display_newBtn'] = false;
        $params['display_editBtn'] = true;
        $params['display_searchPanel']  = true;
        
        $params['exclude'] = array('debug');  

        return $params;
    }
    
    function _get($id = 0)
    {
        global $db;
        
        $where = '';
        
        if ($this->url_data['query']) {
            $where = ' WHERE debug like "%' . $this->url_data['query'] . '%"';
        }

        $record = $db->Execute("SELECT * FROM pi_paymill_logging" . $where);
        
        $data = array();
        while (!$record->EOF) {
            $data[] = $record->fields;
            $record->MoveNext();
        }
        
        $tableData = new adminDB_DataRead(
            $this->_table, $this->_tableLang, $this->_tableSeo, $this->_masterKey
        );

        if ($this->position != 'admin') {
            return false;
        }
        
        if (!empty($id)) {
            $html = '<h2>No Debug available</h2>';
            foreach ($data as $value) {
                if ($value['id'] == $id && !empty($value['debug'])) {
                    $html = '<h2>Debug</h2>';
                    $debug = str_replace('\n', "\n", $value['debug']);
                    $html.= '<pre>' . print_r($debug, true) . '</pre>';
                }
            }
            
            exit($html);
        }
        

        $obj = new stdClass();
        $obj->totalCount = $record->RecordCount();

        $obj->data = $tableData->getHeader();
        
        if (!empty($data)) {
            $obj->data = $data;
        }
        
        return $obj;
    }
    
    function _unset($id = 0)
    {
        global $db;

        $id = (int) $id;

        if ($id == 0 || !is_int($id) || $this->position != 'admin') {
            return false;
        }

        $db->Execute("DELETE FROM " . $this->_table . " WHERE " . $this->_masterKey . " = '" . $id . "'");

        return true;
    }

}