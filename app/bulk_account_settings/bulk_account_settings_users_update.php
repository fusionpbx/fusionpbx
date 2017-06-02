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
	if (permission_exists('bulk_account_settings_users')) {
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

		$user_uuids = $_REQUEST["id"];
		$option_selected = $_REQUEST["option_selected"];
		$new_setting = $_REQUEST["new_setting"];
		foreach($user_uuids as $user_uuid) {
			$user_uuid = check_str($user_uuid);
			if ($user_uuid != '') {
			//get the users array
				$sql = "select * from v_users ";
				$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
				$sql .= "and user_uuid = '".$user_uuid."' ";
				$database = new database;
				$database->select($sql);
				$users = $database->result;
				if (is_array($users)) { 
					foreach ($users as &$row) {
						$user = $row["user"];
					}
					unset ($prep_statement);
				}
				
				//user_status or user_enabled
				if($option_selected == "user_status" || $option_selected == "user_enabled"){
					$array["users"][$i]["domain_uuid"] = $domain_uuid;
					$array["users"][$i]["user_uuid"] = $user_uuid;
					$array["users"][$i][$option_selected] = $new_setting;
				}

				//password
				if($option_selected == "password"){
					$salt = uuid();
					$array["users"][$i]["domain_uuid"] = $domain_uuid;
					$array["users"][$i]["user_uuid"] = $user_uuid;
					$array['users'][$i]['password'] = md5($salt.$new_setting);
					$array['users'][$i]['salt'] = $salt;				

				}

				//timezone
				if($option_selected == "time_zone"){
					$sql = "select * from v_user_settings ";
					$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
					$sql .= "and user_uuid = '".$user_uuid."' ";
					$sql .= "and user_setting_subcategory = 'time_zone' ";
					$database = new database;
					$database->select($sql);
					$users = $database->result;
					if (is_array($users)) { 
						foreach ($users as &$row) {
							$user_setting_uuid = $row["user_setting_uuid"];
						}
						unset ($prep_statement);
					}
				
					$array["user_settings"][$i]["domain_uuid"] = $domain_uuid;
					$array["user_settings"][$i]["user_uuid"] = $user_uuid;
					$array["user_settings"][$i]["user_setting_uuid"] = $user_setting_uuid;
					$array["user_settings"][$i]["user_setting_value"] = $new_setting;

				}

				$database = new database;
				$database->app_name = 'bulk_account_settings';
				$database->app_uuid = null;
				$database->save($array);
				$message = $database->message;
				unset($database,$array,$i);
			}
		}
	}

//redirect the browser
	$_SESSION["message"] = $text['message-update'];
	header("Location: bulk_account_settings_users.php?option_selected=".$option_selected."");
	return;
?>
