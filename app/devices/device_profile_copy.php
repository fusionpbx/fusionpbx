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
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('device_add')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the http get/post variable(s) to a php variable
	if (is_uuid($_REQUEST["id"])) {
		$device_profile_uuid = $_REQUEST["id"];
	}

//get the device
	$sql = "select * from v_device_profiles ";
	$sql .= "where device_profile_uuid = :device_profile_uuid ";
	$parameters['device_profile_uuid'] = $device_profile_uuid;
	$database = new database;
	$device_profiles = $database->select($sql, $parameters);
	unset($sql, $parameters);

//get device keys
	$sql = "select * from v_device_keys ";
	$sql .= "where device_profile_uuid = :device_profile_uuid ";
	$sql .= "order by ";
	$sql .= "case device_key_category ";
	$sql .= "when 'line' then 1 ";
	$sql .= "when 'memort' then 2 ";
	$sql .= "when 'programmable' then 3 ";
	$sql .= "when 'expansion' then 4 ";
	$sql .= "else 100 end, ";
	$sql .= "cast(device_key_id as numeric) asc ";
	$parameters['device_profile_uuid'] = $device_profile_uuid;
	$database = new database;
	$device_keys = $database->select($sql, $parameters);
	unset($sql, $parameters);

//get device settings
	$sql = "select * from v_device_settings ";
	$sql .= "where device_profile_uuid = :device_profile_uuid ";
	$sql .= "order by device_setting_subcategory asc ";
	$parameters['device_profile_uuid'] = $device_profile_uuid;
	$database = new database;
	$device_settings = $database->select($sql, $parameters);
	unset($sql, $parameters);

//prepare the devices array
	unset($device_profiles[0]["device_profile_uuid"]);

//add copy to the device description
	//$device_profiles[0]["device_profile_name"] = $device_profiles[0]["device_profile_name"]."-".strtolower($text['button-copy']);
	$device_profiles[0]["device_profile_description"] = $text['button-copy']." ".$device_profiles[0]["device_profile_description"];

//prepare the device_keys array
	$x = 0;
	foreach ($device_keys as $row) {
		unset($device_keys[$x]["device_profile_uuid"]);
		unset($device_keys[$x]["device_key_uuid"]);
		$x++;
	}

//prepare the device_settings array
	$x = 0;
	foreach ($device_settings as $row) {
		unset($device_settings[$x]["device_profile_uuid"]);
		unset($device_settings[$x]["device_setting_uuid"]);
		$x++;
	}

//create the device array
	$array["device_profiles"] = $device_profiles;
	$array["device_profiles"][0]["device_keys"] = $device_keys;
	$array["device_profiles"][0]["device_settings"] = $device_settings;

//copy the device
	$database = new database;
	$database->app_name = 'devices';
	$database->app_uuid = '4efa1a1a-32e7-bf83-534b-6c8299958a8e';
	$database->save($array);
	unset($array);

	message::add($text['message-copy']);

//redirect
	header("Location: device_profiles.php");
	return;

?>
