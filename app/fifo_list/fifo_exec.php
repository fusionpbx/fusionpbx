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
	Copyright (C) 2010 - 2019
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes 
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('active_queue_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//get variables
	if (is_array($_GET)>0) {
		$switch_cmd = trim($_GET["cmd"]);
		$action = trim($_GET["action"]);
		$direction = trim($_GET["direction"]);
	}

//GET to PHP variables
	if (is_array($_GET)) {
		//fs cmd
		if (strlen($switch_cmd) > 0) {
			/*
			if ($action == "energy") {
				//conference 3001-example.dyndns.org energy 103
				$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
				$result_array = explode("=",$switch_result);
				$tmp_value = $result_array[1];
				if ($direction == "up") { $tmp_value = $tmp_value + 100; }
				if ($direction == "down") { $tmp_value = $tmp_value - 100; }
				//echo "energy $tmp_value<br />\n";
				$switch_result = event_socket_request($fp, 'api '.$switch_cmd.' '.$tmp_value);
			}
			if ($action == "volume_in") {
				$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
				$result_array = explode("=",$switch_result);
				$tmp_value = $result_array[1];
				if ($direction == "up") { $tmp_value = $tmp_value + 1; }
				if ($direction == "down") { $tmp_value = $tmp_value - 1; }
				//echo "volume $tmp_value<br />\n";
				$switch_result = event_socket_request($fp, 'api '.$switch_cmd.' '.$tmp_value);
			}
			if ($action == "volume_out") {
				$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
				$result_array = explode("=",$switch_result);
				$tmp_value = $result_array[1];
				if ($direction == "up") { $tmp_value = $tmp_value + 1; }
				if ($direction == "down") { $tmp_value = $tmp_value - 1; }
				//echo "volume $tmp_value<br />\n";
				$switch_result = event_socket_request($fp, 'api '.$switch_cmd.' '.$tmp_value);
			}
			*/
		//connect to the event socket
			//$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
		//send the command over event socket
			//if ($fp) {
			//	$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
			//}
		}

	}

?>
