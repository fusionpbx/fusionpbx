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
	Copyright (C) 2008-2015 All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('device_profile_delete')) {
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
	if (isset($_GET["id"])) {
		$id = $_GET["id"];
	}

//delete the data and subdata
	if (is_uuid($id)) {

		//delete device profile keys
			$sql = "delete from v_device_keys ";
			$sql .= "where device_profile_uuid = '".$id."' ";
			$db->exec($sql);
			unset($sql);

		//delete device profile
			$sql = "delete from v_device_profiles ";
			$sql .= "where device_profile_uuid = '".$id."' ";
			$db->exec($sql);
			unset($sql);

		//remove device profile uuid from any assigned devices
			$sql = "update v_devices set ";
			$sql .= "device_profile_uuid = null ";
			$sql .= "where device_profile_uuid = '".$id."' ";
			$db->exec($sql);
			unset($sql);
	}

//write the provision files
	require_once "app/provision/provision_write.php";

//set the message and redirect the user
	$_SESSION["message"] = $text['message-delete'];
	header("Location: device_profiles.php");
	return;

?>