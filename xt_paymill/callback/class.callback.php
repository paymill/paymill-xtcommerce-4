<?php

defined('_VALID_CALL') or die('Direct Access is not allowed.');

require_once(dirname(dirname(__FILE__)) . '/classes/lib/Services/Paymill/Transactions.php');
require_once(dirname(dirname(__FILE__)) . '/classes/helpers/Util.php');

class callback_xt_paymill extends callback 
{
    public function process() 
    {
        global $db;
        
        $data = json_decode(file_get_contents('php://input'));
        if (!is_null($data) && isset($data->event) && isset($data->event->event_resource)) {
            if (isset($data->event->event_resource->transaction)) {
                $description = array();
                if ($this->_validateRequest($data) && preg_match("/OrderID: (\S*)/", $data->event->event_resource->transaction->description, $description)) {
                    $this->orders_id = $description[1];
                    $paymillRefunded = $db->GetOne("SELECT status_id FROM " . TABLE_SYSTEM_STATUS_DESCRIPTION . " WHERE status_name = 'Refund / Chargeback (PAYMILL)'");
                    $this->_updateOrderStatus($paymillRefunded, true);
                }
            }
        }

        exit(header("HTTP/1.1 200 OK"));
    }
    
    private function _getPaymentConfig($key)
    {
        $value = null;
        if (defined('XT_PAYMILL_' . $key)) {
            $value = constant('XT_PAYMILL_' . $key);
        }

        return $value;
    }
    
    private function _validateRequest($data)
    {
         $valid = false;
        if (!is_null($data) && isset($data->event) && isset($data->event->event_resource) && isset($data->event->event_resource->transaction)) {
            $transactionObject = new Services_Paymill_Transactions(
                trim($this->_getPaymentConfig('PRIVATE_API_KEY')), Util::$apiUrl
            );
            $transaction = $transactionObject->getOne($data->event->event_resource->transaction->id);

            // Validate data
            if (isset($transaction['id']) && ($transaction['id'] === $data->event->event_resource->transaction->id)) {
                $valid = true;
            }
        }
        
        return $valid;
    }
}