<?php

defined('_VALID_CALL') or die('Direct Access is not allowed.');

class xt_paymill_hook_registration
{

    protected $_table = 'pi_paymill_hooks';
    protected $_tableLang = null;
    protected $_tableSeo = null;
    protected $_masterKey = 'id';

    function setPosition($position)
    {
        $this->position = $position;
    }

    function _getParams()
    {
        $params = array(
            'header' => array(
                'paymill_id' => array('type' => 'hidden'),
                'language_code' => array('type' => 'hidden'),
                'customers_id' => array('type' => 'hidden')
            ),
            'master_key' => $this->_masterKey,
            'default_sort' => $this->_masterKey,
            'SortField' => $this->_masterKey,
            'SortDir' => 'DESC',
            'exclude' => array('type')
        );

        return $params;
    }

    function _get($id = 0)
    {
        global $db;
        
        $record = $db->Execute("SELECT * FROM " . $this->_table);

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

        $obj = new stdClass();
        $obj->totalCount = $record->RecordCount();

        $obj->data = $tableData->getHeader();

        if (!empty($data)) {
            $obj->data = $data;
        }
        
        if ($id === 'new') {
            $obj->data[0]['endpoint_url'] = _SYSTEM_BASE_HTTPS . _SRV_WEB_UPLOAD . 'plugins/xt_paymill/callback/notification_endpoint.php';
        }

        return $obj;
    }
            
    function _set($data, $set_type = 'edit')
    {
        
        if ($this->position != 'admin') {
            return false;
        }
        
        
        
        
        return true;
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