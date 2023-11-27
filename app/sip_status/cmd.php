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
	Portions created by the Initial Developer are Copyright (C) 2008-2020
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (if_group("superadmin")) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//set the variables
	$profile = $_GET['profile'] ?? null;
	$action = $_GET['action'];
	$gateway = $_GET['gateway'] ?? null;

//validate the sip profile name
	$sql = "select sip_profile_name from v_sip_profiles ";
	$sql .= "where sip_profile_name = :profile_name ";
	$parameters['profile_name'] = $profile;
	$database = new database;
	$profile_name = $database->select($sql, $parameters, 'column');
	unset($sql, $parameters);

//validate the gateway
	if (!empty($_GET['gateway']) && is_uuid($_GET['gateway'])) {
		$gateway_name = $_GET['gateway'];
	}

//build the commands
	switch ($action) {
		case "killgw":
			$command = "sofia profile '".$profile_name."' killgw ".$gateway_name;
			break;
		case "start":
			$command = "sofia profile '".$profile_name."' start";
			break;
		case "stop":
			$command = "sofia profile '".$profile_name."' stop";
			break;
		case "restart":
			$command = "sofia profile '".$profile_name."' restart";
			break;
		case "flush_inbound_reg":
			$command = "sofia profile '".$profile_name."' flush_inbound_reg";
			break;
		case "rescan":
			$command = "sofia profile '".$profile_name."' rescan";
			break;
		case "cache-flush":
			$cache = new cache;
			$response = $cache->flush();
			message::add($response, 'alert');
			break;
		case "reloadxml":
			$command = "reloadxml";
			break;
		case "reloadacl":
			$command = "reloadacl";
			break;
		default:
			unset($action);
	}

//create the event socket connection
	$fp = event_socket_create();
	if ($fp) {
		//if reloadxml then run reloadacl, reloadxml and rescan the external profile for new gateways
			if (isset($command)) {
				//clear the apply settings reminder
					$_SESSION["reload_xml"] = false;

				//run the command
					$result = rtrim(event_socket_request($fp, 'api '.$command));
			}

		//sofia profile
			if (isset($profile) && strlen($profile)) {
				message::add('<strong>'.$profile.'</strong> '.$result, 'alert', 3000);
			}
			else if (!empty($result)) {
				message::add($result, 'alert');
			}

		//close the connection
			fclose($fp);
	}

//redirect the user
	header("Location: sip_status.php");

?>
