<?php
/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2008-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('call_center_queue_add') || permission_exists('call_center_queue_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//get the variables
	$cmd = $_GET['cmd'];
	$queue = $_GET['queue'];

//validate the variables
	switch ($cmd) {
		case "load":
			//allow the command
			break;
		case "unload":
			//allow the command
			break;
		case "reload":
			//allow the command
			break;
		default:
			unset($cmd);
	}

//only allow a uuid for the queue name
	if (!is_uuid($queue)) {
		unset($queue);
	}

//connect to event socket
	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
	if ($fp) {
		$response = event_socket_request($fp, 'api reloadxml');
		if (isset($cmd) && isset($queue)) {
			$response = event_socket_request($fp, 'api callcenter_config queue '.$cmd. ' '.$queue);
		}
		fclose($fp);
	}
	else {
		$response = '';
	}

//send the redirect
	$_SESSION["message"] = $response;
	header("Location: call_center_queues.php?savemsg=".urlencode($response));

?>
