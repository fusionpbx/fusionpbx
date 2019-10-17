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

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('user_setting_delete')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//delete user settings
	$user_setting_uuids = $_REQUEST["id"];
	$user_uuid = $_REQUEST["user_uuid"];

	if (is_uuid($user_uuid) && is_array($user_setting_uuids) && sizeof($user_setting_uuids) != 0) {
		foreach ($user_setting_uuids as $index => $user_setting_uuid) {
			if (is_uuid($user_setting_uuid)) {
				$array['user_settings'][$index]['user_setting_uuid'] = $user_setting_uuid;
				$array['user_settings'][$index]['user_uuid'] = $user_uuid;
			}
		}
		if (is_array($array) && sizeof($array) != 0) {
			$database = new database;
			$database->app_name = 'user_settings';
			$database->app_uuid = '3a3337f7-78d1-23e3-0cfd-f14499b8ed97';
			$database->delete($array);
			$user_settings_deleted = sizeof($array['user_settings']);
			unset($array);
		}
		// set message
		message::add($text['message-delete'].": ".$user_settings_deleted);
	}
	else {
		// set message
		message::add($text['message-delete_failed'], 'negative');
	}

	header("Location: /core/users/user_edit.php?id=".$user_uuid);
	exit;

?>
