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
	Portions created by the Initial Developer are Copyright (C) 2008-2015
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
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
	if (isset($_REQUEST["id"]) && isset($_REQUEST["mac"])) {
		$device_uuid = check_str($_REQUEST["id"]);
		$mac_address_new = check_str($_REQUEST["mac"]);
		$mac_address_new = preg_replace('#[^a-fA-F0-9./]#', '', $mac_address_new);
	}

//set the default
	$save = true;

//check to see if the mac address exists
	if ($mac_address_new == "" || $mac_address_new == "000000000000") {
		//allow duplicates to be used as templaes
	}
	else {
		$sql = "SELECT count(*) AS num_rows FROM v_devices ";
		$sql .= "WHERE device_mac_address = '".$mac_address_new."' ";
		$prep_statement = $db->prepare($sql);
		if ($prep_statement) {
			$prep_statement->execute();
			$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
			if ($row['num_rows'] == "0") {
				$save = true;
			}
			else {
				$save = false;
				$_SESSION['message'] =  $text['message-duplicate'];
			}
		}
		unset($prep_statement);
	}

//get the device
	$sql = "SELECT * FROM v_devices ";
	$sql .= "where device_uuid = '".$device_uuid."' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$devices = $prep_statement->fetchAll(PDO::FETCH_NAMED);

//get device lines
	$sql = "SELECT * FROM v_device_lines ";
	$sql .= "where device_uuid = '".$device_uuid."' ";
	$sql .= "order by line_number asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$device_lines = $prep_statement->fetchAll(PDO::FETCH_NAMED);

//get device keys
	$sql = "SELECT * FROM v_device_keys ";
	$sql .= "WHERE device_uuid = '".$device_uuid."' ";
	$sql .= "ORDER by ";
	$sql .= "CASE device_key_category ";
	$sql .= "WHEN 'line' THEN 1 ";
	$sql .= "WHEN 'memort' THEN 2 ";
	$sql .= "WHEN 'programmable' THEN 3 ";
	$sql .= "WHEN 'expansion' THEN 4 ";
	$sql .= "ELSE 100 END, ";
	$sql .= "cast(device_key_id as numeric) asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$device_keys = $prep_statement->fetchAll(PDO::FETCH_NAMED);

//get device settings
	$sql = "SELECT * FROM v_device_settings ";
	$sql .= "WHERE device_uuid = '".$device_uuid."' ";
	$sql .= "ORDER by device_setting_subcategory asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$device_settings = $prep_statement->fetchAll(PDO::FETCH_NAMED);

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

//copy the device
	if ($save) {
		$orm = new orm;
		$orm->name('devices');
		$orm->save($device);
		$response = $orm->message;
		$_SESSION["message"] = $text['message-copy'];
	}

//redirect
	header("Location: devices.php");
	return;

?>