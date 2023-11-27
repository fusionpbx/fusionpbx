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
	Portions created by the Initial Developer are Copyright (C) 2008-2023
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
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

//pre-populate the form
	if (!empty($_GET) && is_array($_GET) && is_uuid($_GET["id"]) && (empty($_POST["persistformvar"]) || $_POST["persistformvar"] != "true")) {
		$call_center_queue_uuid = $_GET["id"];
		$sql = "select queue_extension from v_call_center_queues ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and call_center_queue_uuid = :call_center_queue_uuid ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['call_center_queue_uuid'] = $call_center_queue_uuid;
		$database = new database;
		$queue_extension = $database->select($sql, $parameters, 'column');
		unset($sql, $parameters);
	}

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

//connect to event socket
	if (isset($queue_extension) && isset($cmd)) {
		$fp = event_socket_create();
		if ($fp) {
			$response = event_socket_request($fp, 'api reloadxml');
			$response = event_socket_request($fp, 'api callcenter_config queue '.$cmd. ' '.$queue_extension."@".$_SESSION["domain_name"]);
			fclose($fp);
		}
		else {
			$response = '';
		}
	}

//send the redirect
	$_SESSION["message"] = $response;
	header("Location: call_center_queues.php?savemsg=".urlencode($response));

?>