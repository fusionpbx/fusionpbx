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
	Copyright (C) 2019 All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//delete the message
	message::add($text['message-delete']);

//delete the data
	if (isset($_GET["id"]) && is_uuid($_GET["id"]) && permission_exists('device_profile_delete')) {

		//get the id
			$id = $_GET["id"];

		//delete the data
			$array['device_profile_keys'][]['device_profile_uuid'] = $id;
			$array['device_profile_settings'][]['device_profile_uuid'] = $id;
			$array['device_profiles'][]['device_profile_uuid'] = $id;
			$database = new database;
			$database->app_name = 'devices';
			$database->app_uuid = '4efa1a1a-32e7-bf83-534b-6c8299958a8e';
			$database->delete($array);
			unset($array);

		//redirect the user
			header('Location: device_profiles.php');
	}

//delete the child data
	if (isset($_REQUEST["device_profile_key_uuid"]) && is_uuid($_REQUEST["device_profile_key_uuid"]) && permission_exists('device_profile_key_delete')) {
		//select from v_device_profile_keys
			$sql = "select device_profile_uuid from v_device_profile_keys ";
			$sql .= "where device_profile_key_uuid = :device_profile_key_uuid ";
			$parameters['device_profile_key_uuid'] = $_REQUEST["device_profile_key_uuid"];
			$database = new database;
			$device_profile_uuid = $database->select($sql, $parameters, 'column');
			unset($sql, $parameters);

		//delete the row
			$array['device_profile_keys'][]['device_profile_key_uuid'] = $_REQUEST["device_profile_key_uuid"];
			$database = new database;
			$database->app_name = 'devices';
			$database->app_uuid = '4efa1a1a-32e7-bf83-534b-6c8299958a8e';
			$database->delete($array);

		//redirect the user
			header('Location: device_profile_edit.php?id='.urlencode($device_profile_uuid));
	}

//delete the child data
	if (isset($_REQUEST["device_profile_setting_uuid"]) && is_uuid($_REQUEST["device_profile_setting_uuid"]) && permission_exists('device_profile_setting_delete')) {
		//select from v_device_profile_settings
			$sql = "select device_profile_uuid from v_device_profile_settings ";
			$sql .= "where device_profile_setting_uuid = :device_profile_setting_uuid ";
			$parameters['device_profile_setting_uuid'] = $_REQUEST["device_profile_setting_uuid"];
			$database = new database;
			$device_profile_uuid = $database->select($sql, $parameters, 'column');
			unset($sql, $parameters);

		//delete the row
			$array['device_profile_settings'][]['device_profile_setting_uuid'] = $_REQUEST["device_profile_setting_uuid"];
			$database = new database;
			$database->app_name = 'devices';
			$database->app_uuid = '4efa1a1a-32e7-bf83-534b-6c8299958a8e';
			$database->delete($array);

		//redirect the user
			header('Location: device_profile_edit.php?id='.urlencode($device_profile_uuid));
	}

?>
