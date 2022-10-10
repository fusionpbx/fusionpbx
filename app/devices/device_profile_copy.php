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
	Portions created by the Initial Developer are Copyright (C) 2008-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('device_profile_add')) {
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
	$sql = "select * from v_device_profile_keys ";
	$sql .= "where device_profile_uuid = :device_profile_uuid ";
	$sql .= "order by ";
	$sql .= "case profile_key_category ";
	$sql .= "when 'line' then 1 ";
	$sql .= "when 'memort' then 2 ";
	$sql .= "when 'programmable' then 3 ";
	$sql .= "when 'expansion' then 4 ";
	$sql .= "else 100 end, ";
	$sql .= "profile_key_id asc ";
	$parameters['device_profile_uuid'] = $device_profile_uuid;
	$database = new database;
	$device_profile_keys = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//get device settings
	$sql = "select * from v_device_profile_settings ";
	$sql .= "where device_profile_uuid = :device_profile_uuid ";
	$sql .= "order by profile_setting_name asc ";
	$parameters['device_profile_uuid'] = $device_profile_uuid;
	$database = new database;
	$device_profile_settings = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//prepare the devices array
	$device_profile_uuid = uuid();
	$device_profiles[0]["device_profile_uuid"] = $device_profile_uuid;

//add copy to the device description
	//$device_profiles[0]["device_profile_name"] = $device_profiles[0]["device_profile_name"]."-".strtolower($text['button-copy']);
	$device_profiles[0]["device_profile_description"] = $device_profiles[0]["device_profile_description"].' ('.$text['button-copy'].')';

//prepare the device_keys array
	$x = 0;
	if (is_array($device_profile_keys) && count($device_profile_keys) > 0) {
		foreach ($device_profile_keys as $row) {
			$device_profile_keys[$x]["device_profile_uuid"] = $device_profile_uuid;
			$device_profile_keys[$x]["device_profile_key_uuid"] = uuid();
			$x++;
		}
	}

//prepare the device_settings array
	$x = 0;
	if (is_array($device_profile_settings) && count($device_profile_settings) > 0) {
		foreach ($device_profile_settings as $row) {
			$device_profile_settings[$x]["device_profile_uuid"] = $device_profile_uuid;
			$device_profile_settings[$x]["device_profile_setting_uuid"] = uuid();
			$x++;
		}
	}

//create the device array
	$array["device_profiles"] = $device_profiles;
	if (is_array($device_profile_keys) && count($device_profile_keys) > 0) {
		$array["device_profiles"][0]["device_profile_keys"] = $device_profile_keys;
	}
	if (is_array($device_profile_settings) && count($device_profile_settings) > 0) {
		$array["device_profiles"][0]["device_profile_settings"] = $device_profile_settings;
	}

//copy the device
	$database = new database;
	$database->app_name = 'devices';
	$database->app_uuid = '4efa1a1a-32e7-bf83-534b-6c8299958a8e';
	$database->save($array);
	unset($array);

//set the message
	message::add($text['message-copy']);

//redirect
	header("Location: device_profiles.php");
	return;

?>
