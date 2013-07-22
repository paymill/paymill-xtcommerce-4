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
        
        $data = $this->loadFastCheckoutData($userId);
        if (!empty($data)) {
            $sql = "UPDATE `xplugin_pi_paymill_tfastcheckout`SET `paymentID_CC` = '$newPaymentId' WHERE `userID` = '$userId'";
        } else {
            $sql = "INSERT INTO `xplugin_pi_paymill_tfastcheckout` (`userID`, `clientID`, `paymentID_CC`) VALUES ('$userId', '$newClientId', '$newPaymentId')";
        }
        
        $db->Execute($sql);
    }
    
    public function saveElvIds($userId, $newClientId, $newPaymentId)
    {   
        global $db;
        
        $data = $this->loadFastCheckoutData($userId);
        if (!empty($data)) {
            $sql = "UPDATE `xplugin_pi_paymill_tfastcheckout`SET `paymentID_ELV` = '$newPaymentId' WHERE `userID` = '$userId'";
        } else {
            $sql = "INSERT INTO `xplugin_pi_paymill_tfastcheckout` (`userID`, `clientID`, `paymentID_ELV`) VALUES ('$userId', '$newClientId', '$newPaymentId')";
        }
        
        $db->Execute($sql);
    }
    
    public function loadFastCheckoutData($userId)
    {
        global $db;
        
        $sql = "SELECT * FROM `xplugin_pi_paymill_tfastcheckout` WHERE `userID` = '$userId'";
        
        return $db->Execute($sql);
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