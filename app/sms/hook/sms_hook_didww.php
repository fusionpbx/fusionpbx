<?php

include "../root.php";

require_once "resources/require.php";
require_once "../sms_hook_common.php";

error_log(print_r($_POST,true));

if(strcasecmp($_SERVER['REQUEST_METHOD'], 'POST') != 0){
    throw new Exception("Request method must be POST! $_SERVER[REQUEST_METHOD]");
}


$sms_from=$_POST['from'];
$sms_text=base64_decode($_POST['body']);
$sms_to_did_no=$_POST['to'];

route_and_send_sms($sms_from, $sms_to_did_no, $sms_text);

die("no");
?>