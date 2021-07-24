<?php

include "../root.php";

require_once "resources/require.php";
require_once "../sms_hook_common.php";

if ($debug) {
	error_log('[SMS] REQUEST: ' .  print_r($_SERVER, true));
}

if (check_acl()) {
	if  ($_SERVER['CONTENT_TYPE'] == 'application/json; charset=utf-8') {
		$data = json_decode(file_get_contents("php://input"));
		if ($debug) {
			error_log('[SMS] REQUEST: ' .  print_r($data, true));
		}
		route_and_send_sms($data->from, $data->to, $data->text);
	} else {
		error_log('[SMS] REQUEST: No SMS Data Received');
		die("no");
	}
} else {
	error_log('ACCESS DENIED [SMS]: ' .  print_r($_SERVER['REMOTE_ADDR'], true));
	die("access denied");
}
?>