<?php

defined('_VALID_CALL') or die('Direct Access is not allowed.');

class callback_xt_paymill extends callback 
{
    public function process() 
    {
        global $db;
        
        $data = json_decode(file_get_contents('php://input'));
        if (!is_null($data) && isset($data->event) && isset($data->event->event_resource)) {
            if (isset($data->event->event_resource->transaction)) {
                $description = array();
                if (preg_match("/OrderID: (\S*)/", $data->event->event_resource->transaction->description, $description)) {
                    $this->orders_id = $description[1];
                    $paymillRefunded = $db->GetOne("SELECT status_id FROM " . TABLE_SYSTEM_STATUS_DESCRIPTION . " WHERE status_name = 'Refund / Chargeback (PAYMILL)'");
                    $this->_updateOrderStatus($paymillRefunded, true);
                }
            }
        }

        exit(header("HTTP/1.1 200 OK"));
    }
}