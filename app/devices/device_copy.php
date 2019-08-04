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
	if (is_array($_REQUEST["id"]) && isset($_REQUEST["mac"])) {
		$device_uuid = $_REQUEST["id"];
		$mac_address_new = $_REQUEST["mac"];
		$mac_address_new = preg_replace('#[^a-fA-F0-9./]#', '', $mac_address_new);
	}

//set the default
	$save = true;

//check to see if the mac address exists
	if ($mac_address_new == "" || $mac_address_new == "000000000000") {
		//allow duplicates to be used as templaes
	}
	else {
		$sql = "select count(*) from v_devices ";
		$sql .= "where device_mac_address = :device_mac_address ";
		$parameters['device_mac_address'] = $mac_address_new;
		$database = new database;
		$num_rows = $database->select($sql, $parameters, 'column');
		if ($num_rows == 0) {
			$save = true;
		}
		else {
			$save = false;
			message::add($text['message-duplicate']);
		}
		unset($sql, $parameters, $num_rows);
	}

//get the device
	$sql = "select * from v_devices ";
	$sql .= "where device_uuid = :device_uuid ";
	$parameters['device_uuid'] = $device_uuid;
	$database = new database;
	$devices = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//get device lines
	$sql = "select * from v_device_lines ";
	$sql .= "where device_uuid = :device_uuid ";
	$sql .= "order by line_number asc ";
	$parameters['device_uuid'] = $device_uuid;
	$database = new database;
	$device_lines = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//get device keys
	$sql = "select * from v_device_keys ";
	$sql .= "where device_uuid = :device_uuid ";
	$sql .= "order by ";
	$sql .= "case device_key_category ";
	$sql .= "when 'line' then 1 ";
	$sql .= "when 'memort' then 2 ";
	$sql .= "when 'programmable' then 3 ";
	$sql .= "when 'expansion' then 4 ";
	$sql .= "else 100 END, ";
	$sql .= "cast(device_key_id as numeric) asc ";
	$parameters['device_uuid'] = $device_uuid;
	$database = new database;
	$device_keys = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//get device settings
	$sql = "select * from v_device_settings ";
	$sql .= "where device_uuid = :device_uuid ";
	$sql .= "order by device_setting_subcategory asc ";
	$parameters['device_uuid'] = $device_uuid;
	$database = new database;
	$device_settings = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//prepare the devices array
	unset($devices[0]["device_uuid"]);

//add copy to the device description
	$devices[0]["device_description"] = $text['button-copy']." ".$devices[0]["device_description"];

//prepare the device_lines array
	$x = 0;
	foreach ($device_lines as $row) {
		unset($device_lines[$x]["device_uuid"]);
		unset($device_lines[$x]["device_line_uuid"]);
		$x++;
	}

//prepare the device_keys array
	$x = 0;
	foreach ($device_keys as $row) {
		unset($device_keys[$x]["device_uuid"]);
		unset($device_keys[$x]["device_key_uuid"]);
		$x++;
	}

//prepare the device_settings array
	$x = 0;
	foreach ($device_settings as $row) {
		unset($device_settings[$x]["device_uuid"]);
		unset($device_settings[$x]["device_setting_uuid"]);
		$x++;
	}

//create the device array
	$device = $devices[0];
	$device["device_mac_address"] = $mac_address_new;
	$device["device_lines"] = $device_lines;
	$device["device_keys"] = $device_keys;
	$device["device_settings"] = $device_settings;

//prepare the array
	$array['devices'][] = $device;

//copy the device
	if ($save) {
		$database = new database;
		$database->app_name = 'devices';
		$database->app_uuid = '4efa1a1a-32e7-bf83-534b-6c8299958a8e';
		$database->save($array);
		$response = $database->message;
		message::add($text['message-copy']);
	}

//redirect
	header("Location: devices.php");
	return;

?>
