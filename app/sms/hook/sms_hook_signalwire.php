<?php

include "../root.php";

require_once "resources/require.php";
require_once "../sms_hook_common.php";

if ($_REQUEST['AccountSid'] == "34de8b7c-ac5b-48ac-80c3-df5476e9b3d1") {
	if  ($_SERVER['REQUEST_METHOD'] == 'POST') {
		if ($debug) {
			error_log('[SMS] REQUEST: ' .  print_r($_REQUEST, true));
		}
		route_and_send_sms($_REQUEST['From'], str_replace("+","",$_REQUEST['To']), $_REQUEST['Body']);
	} else {
	  die("no");
	}
} else {
	error_log('ACCESS DENIED [SMS]: ' .  print_r($_SERVER['REMOTE_ADDR'], true));
	die("access denied");
}
?>
