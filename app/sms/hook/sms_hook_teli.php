<?php

include "../root.php";

require_once "resources/require.php";
require_once "../sms_hook_common.php";
error_log('[SMS] REQUEST: ' .  print_r($_REQUEST, true));
if(check_acl()) {
		if ($debug) {
			error_log('[SMS] REQUEST: ' .  print_r($_REQUEST, true));
		}
		route_and_send_sms($_POST['source'], $_POST['destination'], $_POST['message']);
} else {
	error_log('ACCESS DENIED [SMS]: ' .  print_r($_SERVER['REMOTE_ADDR'], true));
	die("access denied");
}
