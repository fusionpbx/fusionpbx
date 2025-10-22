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
	Portions created by the Initial Developer are Copyright (C) 2008-2025
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
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
	if (is_uuid($_REQUEST["id"]) && isset($_REQUEST["mac"])) {
		$device_uuid = $_REQUEST["id"];
		$device_address = $_REQUEST["mac"];
		$device_address = preg_replace('#[^a-fA-F0-9./]#', '', $device_address);
	}

//set the default
	$save = true;

//check to see if the device address exists
	if ($device_address == "" || $device_address == "000000000000") {
		//allow duplicates to be used as templaes
	}
	else {
		$sql = "select count(*) from v_devices ";
		$sql .= "where device_address = :device_address ";
		$parameters['device_address'] = $device_address;
		$num_rows = $database->select($sql, $parameters, 'column');
		if ($num_rows == 0) {
			$save = true;
		}
		else {
			$save = false;
			message::add($text['message-duplicate'],'negative');
		}
		unset($sql, $parameters, $num_rows);
	}

//get the device
	$sql = "select ";
	$sql .= "device_uuid, ";
	$sql .= "domain_uuid, ";
	$sql .= "device_profile_uuid, ";
	$sql .= "device_address, ";
	$sql .= "device_label, ";
	$sql .= "device_vendor, ";
	$sql .= "device_location, ";
	$sql .= "device_model, ";
	$sql .= "device_firmware_version, ";
	$sql .= "cast(device_enabled as text), ";
	$sql .= "device_enabled_date, ";
	$sql .= "device_template, ";
	$sql .= "device_user_uuid, ";
	$sql .= "device_username, ";
	$sql .= "device_password, ";
	$sql .= "device_uuid_alternate, ";
	$sql .= "device_description, ";
	$sql .= "device_provisioned_date, ";
	$sql .= "device_provisioned_method, ";
	$sql .= "device_provisioned_ip, ";
	$sql .= "device_provisioned_agent, ";
	$sql .= "device_serial_number ";
	$sql .= "from v_devices ";
	$sql .= "where device_uuid = :device_uuid ";
	$parameters['device_uuid'] = $device_uuid;
	$devices = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//get device lines
	$sql = "select * from v_device_lines ";
	$sql .= "where device_uuid = :device_uuid ";
	$sql .= "order by line_number asc ";
	$parameters['device_uuid'] = $device_uuid;
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
	$sql .= "cast(device_key_id as int) asc ";
	$parameters['device_uuid'] = $device_uuid;
	$device_keys = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//get device settings
	$sql = "select * from v_device_settings ";
	$sql .= "where device_uuid = :device_uuid ";
	$sql .= "order by device_setting_subcategory asc ";
	$parameters['device_uuid'] = $device_uuid;
	$device_settings = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//set the device primary key
	$device_uuid = uuid();

//prepare the device array
	$devices[0]["device_uuid"] = $device_uuid;
	$devices[0]["device_description"] = $text['button-copy']." ".$devices[0]["device_description"];

//prepare the device_lines array

	$x = 0;
	if (is_array($device_lines)) {
		foreach ($device_lines as $row) {
			$device_lines[$x]["device_uuid"] = $device_uuid;
			$device_lines[$x]["device_line_uuid"] = uuid();
			$x++;
		}
	}

//prepare the device_keys array
	$x = 0;
	if (is_array($device_keys)) {
		foreach ($device_keys as $row) {
			$device_keys[$x]["device_uuid"] = $device_uuid;
			$device_keys[$x]["device_key_uuid"] = uuid();
			$x++;
		}
	}

//prepare the device_settings array
	$x = 0;
	if (is_array($device_settings)) {
		foreach ($device_settings as $row) {
			$device_settings[$x]["device_uuid"] = $device_uuid;
			$device_settings[$x]["device_setting_uuid"] = uuid();
			$x++;
		}
	}

//normalize the device address
	if (isset($device_address) && !empty($device_address)) {
		$device_address = strtolower($device_address);
		$device_address = preg_replace('#[^a-fA-F0-9./]#', '', $device_address);
	}

//create the device array
	$device = $devices[0];
	$device["device_address"] = $device_address;
	$device["device_lines"] = $device_lines;
	$device["device_keys"] = $device_keys;
	$device["device_settings"] = $device_settings;

//prepare the array
	$array['devices'][] = $device;

//copy the device
	if ($save) {
		$database->save($array);
		//$response = $database->message;
		message::add($text['message-copy']);
	}

//redirect
	if (is_uuid($device_uuid)) {
		header("Location: device_edit.php?id=".urlencode($device_uuid));
	}

?>
