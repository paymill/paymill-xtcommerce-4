<?php

if ($request['get'] == 'plg_xt_paymill_pci') {
    if (!isset($result)) {
        $result = array();
    }

    $result[] = array(
        'id' => '0',
        'name' => TEXT_PAYMILL_CC_PCI_SAQ_A
    );

    $result[] = array(
        'id' => '1',
        'name' => TEXT_PAYMILL_CC_PCI_SAQ_A_EP
    );
}
