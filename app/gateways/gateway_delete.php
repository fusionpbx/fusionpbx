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
//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
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

//delete the gateway
	if (is_uuid($_GET["id"])) {
		//set the variable
			$id = $_GET["id"];

		//get the gateway name
			$sql = "select * from v_gateways ";
			$sql .= "where gateway_uuid = :gateway_uuid ";
			$parameters['gateway_uuid'] = $id;
			$database = new database;
			$row = $database->select($sql, $parameters, 'row');
			if (is_array($row) && @sizeof($row) != 0) {
				$gateway_uuid = $row["gateway_uuid"];
				$gateway = $row["gateway"];
				$profile = $row["profile"];
			}
			unset($sql, $parameters, $row);

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
			$array['gateways'][0]['gateway_uuid'] = $id;

			$database = new database;
			$database->app_name = 'gateways';
			$database->app_uuid = '297ab33e-2c2f-8196-552c-f3567d2caaf8';
			$database->delete($array);
			unset($array);

		//syncrhonize configuration
			save_gateway_xml();

		//clear the cache
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
			$hostname = trim(event_socket_request($fp, 'api switchname'));
			$cache = new cache;
			$cache->delete("configuration:sofia.conf:".$hostname);


		//create the event socket connection and send a command
			if (!$fp) {
				$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
			}

		//rescan the sip profile to look for new or stopped gateways
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

		//set message
			message::add($text['message-delete']);
	}

//redirect the users
	header("Location: gateways.php");
	exit;

?>
