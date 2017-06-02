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
	KonradSC <konrd@yahoo.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('bulk_account_settings_devices')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//check for the ids
	if (is_array($_REQUEST) && sizeof($_REQUEST) > 0) {

		$device_uuids = $_REQUEST["id"];
		$option_selected = $_REQUEST["option_selected"];
		$new_setting = $_REQUEST["new_setting"];
		foreach($device_uuids as $device_uuid) {
			$device_uuid = check_str($device_uuid);
			if ($device_uuid != '') {
				//get the devices array
					$sql = "select * from v_devices ";
					$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
					$sql .= "and device_uuid = '".$device_uuid."' ";
					$database = new database;
					$database->select($sql);
					$devices = $database->result;
					if (is_array($devices)) { 
						foreach ($devices as &$row) {
							$device = $row["device"];
							//$user_context = $row["user_context"];
						}
						unset ($prep_statement);
					}

						$array["devices"][$i]["domain_uuid"] = $domain_uuid;
						$array["devices"][$i]["device_uuid"] = $device_uuid;
						$array["devices"][$i][$option_selected] = $new_setting;

				
					$database = new database;
					$database->app_name = 'bulk_account_settings';
					$database->app_uuid = null;
					$database->save($array);
					$message = $database->message;
				
					//echo "<pre>".print_r($message, true)."<pre>\n";
					//exit;
					
					unset($database,$array,$i);
			}
		}
	}

//redirect the browser
	$_SESSION["message"] = $text['message-update'];
	header("Location: bulk_account_settings_devices.php?option_selected=".$option_selected."");
	return;

?>