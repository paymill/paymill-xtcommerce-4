<?php

defined('_VALID_CALL') or die('Direct Access is not allowed.'); 

if ($_SESSION['selected_payment'] == 'xt_paymill') {
    $paymill = new xt_paymill();
    $tpl = $_SESSION['selected_payment_sub'] . '.html';
    $pluginTemplate = new Template();
    $pluginTemplate->getTemplatePath($tpl, 'xt_paymill', '', 'plugin');
    echo ($pluginTemplate->getTemplate('', $tpl, $paymill->data));
}

?>