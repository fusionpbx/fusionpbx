<?php

include "../root.php";

require_once "resources/require.php";
require_once "../sms_hook_common.php";

if (check_acl()) {
	if  ($_SERVER['REQUEST_METHOD'] == 'POST') {

		//Make sure that the content type of the POST request has been set to application/json
		$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
		if(strcasecmp($contentType, 'application/json') != 0){
			error_log("CONTENT IS NOT JSON\n");
    		throw new Exception('Content type must be: application/json');
		}

		//Receive the RAW post data.
		$content = trim(file_get_contents("php://input"));
		//$mail_body.="RAW post data: ".$content."\n";

	//Attempt to decode the incoming RAW post data from JSON.
	$decoded = json_decode($content, true);
	$mail_body.="decoded data: \n";
	$sms_from=$decoded['from'];
	$sms_text=$decoded['text'];
	foreach($decoded['recipients'] as $key=>$value):
  		$sms_to_did_no=$value;
	endforeach;


		route_and_send_sms(str_replace("+","",$sms_from), str_replace("+","",$sms_to_did_no), $sms_text);

	} else {
		error_log("REQUEST IS NOT POST\n");
	  die("no");
	}
} else {
	error_log('ACCESS DENIED [SMS]: ' .  print_r($_SERVER['REMOTE_ADDR'], true));
	die("access denied");
}


?>
