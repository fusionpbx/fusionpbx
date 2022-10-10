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
 Portions created by the Initial Developer are Copyright (C) 2008-2021
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('default_setting_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get submitted variables
	$search = $_REQUEST['search'];
	$default_setting_uuids = $_REQUEST["id"];

//toggle the setting
	$toggled = 0;
	if (is_array($default_setting_uuids) && sizeof($default_setting_uuids) > 0) {
		foreach ($default_setting_uuids as $default_setting_uuid) {
			if (is_uuid($default_setting_uuid)) {
				//get current status
					$sql = "select default_setting_enabled from v_default_settings where default_setting_uuid = :default_setting_uuid ";
					$parameters['default_setting_uuid'] = $default_setting_uuid;
					$database = new database;
					$default_setting_enabled = $database->select($sql, $parameters, 'column');
					$new_status = ($default_setting_enabled == 'true') ? 'false' : 'true';
					unset($sql, $parameters);

				//set new status
					$array['default_settings'][0]['default_setting_uuid'] = $default_setting_uuid;
					$array['default_settings'][0]['default_setting_enabled'] = $new_status;
					$database = new database;
					$database->app_name = 'default_settings';
					$database->app_uuid = '2c2453c0-1bea-4475-9f44-4d969650de09';
					$database->save($array);
					$message = $database->message;
					unset($array);

				//increment toggle total
					$toggled++;
			}
		}
		if ($toggled > 0) {
			$_SESSION["message"] = $text['message-toggled'].': '.$toggled;
		}
	}

//redirect the user
	$search = preg_replace('#[^a-zA-Z0-9_\-\.]# ', '', $search);
	header("Location: default_settings.php".($search != '' ? '?search='.$search : null));

?>
