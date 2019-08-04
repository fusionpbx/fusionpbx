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
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('device_setting_delete')) {
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
	$device_setting_uuid = $_GET["id"];
	$device_uuid = $_GET["device_uuid"];
	$device_profile_uuid = $_GET["device_profile_uuid"];

//default location
	$location = 'devices.php';

if (is_uuid($device_setting_uuid)) {

	//delete device settings
		if (is_uuid($device_uuid)) {
			$array['device_settings'][0]['device_setting_uuid'] = $device_setting_uuid;
			$array['device_settings'][0]['device_uuid'] = $device_uuid;

			$location = "device_edit.php?id=".$device_uuid;
		}

	//delete profile device settings
		if (is_uuid($device_profile_uuid)) {
			$array['device_settings'][1]['device_setting_uuid'] = $device_setting_uuid;
			$array['device_settings'][1]['device_profile_uuid'] = $device_profile_uuid;

			$location = "device_profile_edit.php?id=".$device_profile_uuid;
		}

	//execute
		$database = new database;
		$database->app_name = 'devices';
		$database->app_uuid = '4efa1a1a-32e7-bf83-534b-6c8299958a8e';
		$database->delete($array);
		unset($array);

	//set message
		message::add($text['message-delete']);

}

//redirect
	header("Location: ".$location);
	exit;

?>
