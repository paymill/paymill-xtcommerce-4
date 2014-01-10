<?php

$data = json_decode(file_get_contents('php://input'));
if (!is_null($data) && isset($data->event) && isset($data->event->event_resource)) {
    if (isset($data->event->event_resource->transaction)) {
        $description = array();
        if (preg_match("/OrderID: (\S*)/", $data->event->event_resource->transaction->description, $description)) {
            
        }
    }
}

exit(header("HTTP/1.1 200 OK"));