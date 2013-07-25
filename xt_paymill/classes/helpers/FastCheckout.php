<?php

class FastCheckout
{
    public function canCustomerFastCheckoutCcTemplate($userId)
    {
        $flag = 'false';
        if ($this->canCustomerFastCheckoutCc($userId)) {
            $flag = 'true';
        }
        
        return $flag;
    }    
    
    public function canCustomerFastCheckoutElvTemplate($userId)
    {
        $flag = 'false';
        if ($this->canCustomerFastCheckoutElv($userId)) {
            $flag = 'true';
        }
        
        return $flag;
    }    
    
    public function canCustomerFastCheckoutCc($userId)
    {   
        return $this->hasCcPaymentId($userId) && $this->_getPaymentConfig('FAST_CHECKOUT') === 'true';
    }
    
    public function canCustomerFastCheckoutElv($userId)
    {
        return $this->hasElvPaymentId($userId) && $this->_getPaymentConfig('FAST_CHECKOUT') === 'true';
    }
    
    public function saveCcIds($userId, $newClientId, $newPaymentId)
    {
        global $db;
        
        if ($this->_canUpdate($userId)) {
            $sql = "UPDATE `pi_paymill_fastcheckout`SET `paymentID_CC` = '$newPaymentId' WHERE `userID` = '$userId'";
        } else {
            $sql = "INSERT INTO `pi_paymill_fastcheckout` (`userID`, `clientID`, `paymentID_CC`) VALUES ('$userId', '$newClientId', '$newPaymentId')";
        }

        $db->Execute($sql);
    }
    
    public function saveElvIds($userId, $newClientId, $newPaymentId)
    {   
        global $db;
        
        if ($this->_canUpdate($userId)) {
            $sql = "UPDATE `pi_paymill_fastcheckout`SET `paymentID_ELV` = '$newPaymentId' WHERE `userID` = '$userId'";
        } else {
            $sql = "INSERT INTO `pi_paymill_fastcheckout` (`userID`, `clientID`, `paymentID_ELV`) VALUES ('$userId', '$newClientId', '$newPaymentId')";
        }
        
        $db->Execute($sql);
    }
    
    private function _canUpdate($userId)
    {
        $data = $this->loadFastCheckoutData($userId);
        return !empty($data->paymentID_CC) || !empty($data->paymentID_ELV);
    }
    
    public function loadFastCheckoutData($userId)
    {
        global $db;
        
        $sql = "SELECT * FROM `pi_paymill_fastcheckout` WHERE `userID` = '$userId'";
        
        return $db->Execute($sql)->FetchObj();
    }
    
    public function hasElvPaymentId($userId)
    {
        $data = $this->loadFastCheckoutData($userId);
        return !empty($data) && !empty($data->paymentID_ELV);
    }
    
    public function hasCcPaymentId($userId)
    {
        $data = $this->loadFastCheckoutData($userId);
        
        return !empty($data) && !empty($data->paymentID_CC);
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