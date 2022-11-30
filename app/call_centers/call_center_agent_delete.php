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
	Portions created by the Initial Developer are Copyright (C) 2008-2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check the permissions
	if (permission_exists('call_center_agent_delete')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the primary key
	if (isset($_GET["id"]) && is_uuid($_GET["id"])) {
		$id = check_str($_GET["id"]);
	}
	else {
		exit;
	}

//delete the agent from the freeswitch
	//setup the event socket connection
		$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
	//delete the agent over event socket
		if ($fp) {
			$cmd = "api callcenter_config agent del ".$id;
			$response = event_socket_request($fp, $cmd);
		}

//delete the agent from db
	if (strlen($id)>0) {
		//tiers table
			$sql = "delete from v_call_center_tiers ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and agent_name = '$agent_name' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			unset($sql);

		//agents table
			$sql = "delete from v_call_center_agents ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and call_center_agent_uuid = '$id' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			unset($sql);
	}

//synchronize configuration
	save_call_center_xml();
	remove_config_from_cache('configuration:callcenter.conf');

//redirect the browser
	messages::add($text['message-delete']);
	header("Location: call_center_agents.php");
	return;

?>
