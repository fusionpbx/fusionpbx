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
	Portions created by the Initial Developer are Copyright (C) 2008-2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('sip_profile_delete')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the id
	$sip_profile_uuid = $_GET["id"];

//delete the records
	if (is_uuid($sip_profile_uuid)) {

		//get the details of the sip profile
			$sql = "select * from v_sip_profiles ";
			$sql .= "where sip_profile_uuid = :sip_profile_uuid ";
			$parameters['sip_profile_uuid'] = $sip_profile_uuid;
			$database = new database;
			$row = $database->select($sql, $parameters, 'row');
			if (is_array($array) && @sizeof($array) != 0) {
				$sip_profile_name = $row["sip_profile_name"];
				$sip_profile_hostname = $row["sip_profile_hostname"];
				$sip_profile_description = $row["sip_profile_description"];
			}
			unset($sql, $parameters, $row);

		//delete the sip profile domains
			$array['sip_profile_domains'][0]['sip_profile_uuid'] = $sip_profile_uuid;

		//delete the sip profile settings
			$array['sip_profile_settings'][0]['sip_profile_uuid'] = $sip_profile_uuid;

		//delete the sip profile
			$array['sip_profiles'][0]['sip_profile_uuid'] = $sip_profile_uuid;

		//execute delete
			$database = new database;
			$database->app_name = 'sip_profiles';
			$database->app_uuid = '159a8da8-0e8c-a26b-6d5b-19c532b6d470';
			$database->delete($array);
			unset($array);

		//delete the xml sip profile and directory
			unlink($_SESSION['switch']['conf']['dir']."/sip_profiles/".$sip_profile_name.".xml");
			unlink($_SESSION['switch']['conf']['dir']."/sip_profiles/".$sip_profile_name);

		//save the sip profile xml
			save_sip_profile_xml();

		//apply settings reminder
			$_SESSION["reload_xml"] = true;

		//get the hostname
			if ($sip_profile_name == nul) {
				$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
				if ($fp) {
					$switch_cmd = "hostname";
					$sip_profile_name = event_socket_request($fp, 'api '.$switch_cmd);
				}
			}

		//clear the cache
			$cache = new cache;
			$cache->delete("configuration:sofia.conf:".$sip_profile_name);

		//set message
			message::add($text['message-delete']);

	}

//redirect the browser
	header("Location: sip_profiles.php");
	exit;

?>