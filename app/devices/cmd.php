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
	if (permission_exists("device_key_add") || permission_exists("device_key_edit") || if_group("superadmin")) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the variables
	$cmd = $_GET['cmd'];
	$user = $_GET['user'];
	$vendor = device::get_vendor_by_agent($_GET['agent']);

//get the count
	$sql = "select d.domain_name ";
	$sql .= "from v_extensions as e, v_domains as d ";
	$sql .= "where e.domain_uuid = :domain_uuid ";
	$sql .= "and e.domain_uuid = d.domain_uuid ";
	$sql .= "and extension = :extension ";
	$parameters['extension'] = $user;
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$row = $database->select($sql, $parameters, 'row');
	if (is_array($row)) {
		$domain_name = $row['domain_name'];
	}
	else {
		echo "invalid user\n";
		exit;
	}

//create the event socket connection
	$esl = event_socket::create();
	if ($esl->is_connected()) {
		// Get the SIP profiles for the user
		$command = "sofia_contact */{$user}@{$domain_name}";
		$contact_string = event_socket::api($command);

		// The first value in the array will be full matching text, the second one will be the array of profile matches
		preg_match_all('/sofia\/([^,]+)\/(?:[^,]+)/', $contact_string, $matches);
		if (sizeof($matches) != 2 || sizeof($matches[1]) < 1) {
			$profiles = array("internal");
		} else {
			// We have at least one profile, get all of the unique profiles
			$profiles = array_unique($matches[1]);
		}

		foreach ($profiles as $profile) {
			//prepare the command
			if ($cmd == "unregister") {
				$command = "sofia profile {$profile} flush_inbound_reg {$user}@{$domain_name} reboot";
			}
			elseif ($cmd == "check_sync") {
				$command = "lua app.lua event_notify {$profile} {$cmd} {$user}@{$domain_name} {$vendor}";
				//if ($cmd == "check_sync") {
				//	$command = "sofia profile ".$profile." check_sync ".$user."@".$domain_name;
				//}
			}

			//send the command
			$response = event_socket::api("{$command}");
			event_socket::api("log notice {$command}");

			//prepare the response
			$message = $text['message-command_sent'];
			if (trim($response) != '-ERR no reply') {
				$message .= ' '.htmlentities($response);
			}

			//show the response
			message::add($text['label-event']." ".$message, 'positive', 3500);
		}
	}

//redirect the user
	if ($_GET['rdr'] == "false") {
		//redirect false
		echo $response;
	}
	else {
		//send the redirect
		if (isset($_SERVER['HTTP_REFERER'])) {
			header("Location: ".$_SERVER['HTTP_REFERER']);
		}
	}

?>
