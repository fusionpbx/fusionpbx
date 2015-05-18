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
	Portions created by the Initial Developer are Copyright (C) 2008-2012
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('gateway_delete')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

if (strlen($_GET["id"])>0) {
	//set the variable
		$id = check_str($_GET["id"]);

	//get the gateway name
		$sql = "select * from v_gateways ";
		$sql .= "where gateway_uuid = '$id' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$gateway_uuid = $row["gateway_uuid"];
			$gateway = $row["gateway"];
			$profile = $row["profile"];
		}
		unset ($prep_statement);

	//remove gateway from session variable
		unset($_SESSION['gateways'][$gateway_uuid]);

	//delete the xml file
		if ($_SESSION['switch']['sip_profiles']['dir'] != '') {
			$gateway_xml_file = $_SESSION['switch']['sip_profiles']['dir']."/".$profile."/v_".$gateway_uuid.".xml";
			if (file_exists($gateway_xml_file)) {
				unlink($gateway_xml_file);
			}
		}

	//create the event socket connection and stop the gateway
		if (!$fp) {
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
		}

	//send the api gateway stop command over event socket
		$cmd = 'api sofia profile '.$profile.' killgw '.$gateway_uuid;
		$response = event_socket_request($fp, $cmd);
		unset($cmd);

	//delete the gateway
		$sql = "delete from v_gateways ";
		$sql .= "where gateway_uuid = '$id' ";
		$db->query($sql);
		unset($sql);

	//syncrhonize configuration
		save_gateway_xml();

	//delete the gateways from memcache
		$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
		if ($fp) {
			$hostname = trim(event_socket_request($fp, 'api switchname'));
			$switch_cmd = "memcache delete configuration:sofia.conf:".$hostname;
			$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
		}

	//rescan the sip profile to look for new or stopped gateways
		//create the event socket connection and send a command
			if (!$fp) {
				$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
			}
			if ($fp) {
				//send the api commandover event socket
					$cmd = 'api sofia profile '.$profile.' rescan';
					$response = event_socket_request($fp, $cmd);
					unset($cmd);
				//close the connection
					fclose($fp);
			}
			usleep(1000);

		//clear the apply settings reminder
			$_SESSION["reload_xml"] = false;
}

//redirect the users
	$_SESSION["message"] = $text['message-delete'];
	header("Location: gateways.php");
	return;

?>