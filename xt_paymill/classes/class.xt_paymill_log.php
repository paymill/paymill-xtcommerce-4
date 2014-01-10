<?php

defined('_VALID_CALL') or die('Direct Access is not allowed.');

class xt_paymill_log
{

    protected $_table = 'pi_paymill_logging';
    protected $_tableLang = null;
    protected $_tableSeo = null;
    protected $_masterKey = 'id';

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
        $params['display_searchPanel'] = true;
        $params['display_GetSelectedBtn'] = true;     

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