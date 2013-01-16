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
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('gateway_delete')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

if (strlen($_GET["id"])>0) {
	//set the variable
		$id = check_str($_GET["id"]);

	//get the gateway name
		$sql = "select * from v_gateways ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and gateway_uuid = '$id' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$gateway = $row["gateway"];
			$profile = $row["profile"];
			break; //limit to 1 row
		}
		unset ($prep_statement);

	//delete the xml file
		if (count($_SESSION["domains"]) > 1) {
			$gateway_xml_file = $_SESSION['switch']['gateways']['dir']."/".$profile."/v_".$_SESSION['domain_name'].'-'.$gateway.".xml";
		}
		else {
			$gateway_xml_file = $_SESSION['switch']['gateways']['dir']."/".$profile."/v_".$gateway.".xml";
		}
		unlink($gateway_xml_file);

	//create the event socket connection and stop the gateway
		if (!$fp) {
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
		}
		if ($fp) {
			//send the api gateway stop command over event socket
				if (count($_SESSION["domains"]) > 1) {
					$tmp_cmd = 'api sofia profile '.$profile.' killgw '.$_SESSION['domain_name'].'-'.$gateway;
				}
				else {
					$tmp_cmd = 'api sofia profile '.$profile.' killgw '.$gateway;
				}
				$response = event_socket_request($fp, $tmp_cmd);
				unset($tmp_cmd);
		}

	//delete gateway
		$sql = "delete from v_gateways ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and gateway_uuid = '$id' ";
		$db->query($sql);
		unset($sql);

	//syncrhonize configuration
		save_gateway_xml();

	//synchronize the xml config
		save_dialplan_xml();

	//delete the gateways from memcache
		$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
		if ($fp) {
			$switch_cmd = "memcache delete configuration:sofia.conf";
			$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
		}

	//rescan the sip profile to look for new or stopped gateways
		//create the event socket connection and send a command
			if (!$fp) {
				$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
			}
			if ($fp) {
				//send the api commandover event socket
					$tmp_cmd = 'api sofia profile '.$profile.' rescan';
					$response = event_socket_request($fp, $tmp_cmd);
					unset($tmp_cmd);
				//close the connection
					fclose($fp);
			}
			usleep(1000);

		//clear the apply settings reminder
			$_SESSION["reload_xml"] = false;
}

//redirect the users
	require_once "includes/header.php";
	echo "<meta http-equiv=\"refresh\" content=\"2;url=gateways.php\">\n";
	echo "<div align='center'>\n";
	echo $text['message-delete']."\n";
	echo "</div>\n";
	require_once "includes/footer.php";
	return;

?>