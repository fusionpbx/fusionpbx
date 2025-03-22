<?php
/* $Id$ */
/*
	v_exec.php
	Copyright (C) 2008 - 2019 Mark J Crane
	All rights reserved.

	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:

	1. Redistributions of source code must retain the above copyright notice,
	   this list of conditions and the following disclaimer.

	2. Redistributions in binary form must reproduce the above copyright
	   notice, this list of conditions and the following disclaimer in the
	   documentation and/or other materials provided with the distribution.

	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
	POSSIBILITY OF SUCH DAMAGE.
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('call_center_active_options')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//http get variables set to php variables
	if (count($_GET) > 0) {
		$command = trim($_GET["command"]);
		$uuid = trim($_GET["uuid"]);
		$extension = trim($_GET["extension"]);
		$caller_id_name = trim($_GET["extension"]);
		$caller_id_number = trim($_GET["extension"]);
	}

//validate the extension
	if (!is_numeric($extension)) {
		$extension = null;
	}
	
//validate the uuid
	if (!is_uuid($uuid)) {
		$uuid = null;
	}

//validate the caller_id_name
	if (isset($caller_id_name) && strlen($caller_id_name)) {
		$caller_id_name = substr($caller_id_name, 0, 10);
	}

//validate the caller_id_number
	if (!is_numeric($caller_id_number)) {
		$caller_id_number = null;
	}

//validate the command
	switch ($command) {
		case "eavesdrop":
			$switch_command = "originate {origination_caller_id_name=eavesdrop,origination_caller_id_number=".$extension."}user/".$_SESSION['user']['extension'][0]['user']."@".$_SESSION['domain_name']." &eavesdrop(".$uuid.")";
			break;
		case "uuid_transfer":
			$switch_command = "uuid_transfer ".$uuid." -bleg ".$_SESSION['user']['extension'][0]['user']." XML ".$_SESSION['domain_name'];
			break;
		case "uuid_pickup":
				$switch_command = "uuid_transfer ".$uuid." ".$_SESSION['user']['extension'][0]['user']." XML ".$_SESSION['domain_name'];
				break;
		case "bridge":
			$switch_command = "originate {origination_caller_id_name=".$caller_id_name.",origination_caller_id_number=".$caller_id_number."}user/".$_SESSION['user']['extension'][0]['user']."@".$_SESSION['domain_name']." bridge(user/".$extension."@".$_SESSION['domain_name'].")";
			break;
		default:
			echo "access denied";
			exit;
	}

//run the command
	if (isset($switch_command)) {
		$response = event_socket::api($switch_command);
	}

/*
//set the username
	if (if_group("admin") || if_group("superadmin")) {
		//use the username that was provided
	}
	else {
		$username = $_SESSION['username'];
	}

//get to php variables
	if (count($_GET) > 0) {
		if ($_GET['action'] == "user_status") {

		//validate the user status
			$user_status = $_GET['data'];
			switch ($user_status) {
				case "Available":
				case "Available (On Demand)":
				case "On Break":
				case "Do Not Disturb":
				case "Logged Out":
					break;
				default:
					$user_status = null;
			}

			$user_status = $data;
			$sql = "update v_users set ";
			$sql .= "user_status = :user_status ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and username = '".$username."' ";
			$parameters['user_status'] = trim($user_status, "'");
			$database = new database;
			$database->execute($sql, $parameters);
			unset($sql, $parameters);
		}

		//fs cmd
		if (!empty($switch_cmd)) {
			//setup the event socket connection
				$esl = event_socket::create();
			//ensure the connection exists
				if ($esl) {
					//send the command
						$switch_result = event_socket::api($switch_cmd);
					//set the user state
						$cmd = "api callcenter_config agent set state ".$username."@".$_SESSION['domain_name']." Waiting";
						$response = event_socket::command($cmd);
				}
		}
	}
*/

?>