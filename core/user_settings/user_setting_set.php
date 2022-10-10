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
Portions created by the Initial Developer are Copyright (C) 2020
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

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get http post variables and set them to php variables
	if (is_array($_GET) && @sizeof($_GET) != 0) {
		$user_setting_category = strtolower($_GET['category']);
		$user_setting_subcategory = strtolower($_GET['subcategory']);
		$user_setting_name = strtolower($_GET['name']);
		$submitted_value = $_GET['value'];
		//$submitted_order = is_numeric($_GET['order']) ? $_GET['order'] : null;
		$submitted_enabled = strtolower($_GET['enabled']);
	}

//validate allowed user setting
	switch ($user_setting_category) {
		case 'theme':
			switch ($user_setting_subcategory) {
				case 'menu_side_state':
					if ($submitted_value == 'delete') {
						$user_setting_value = 'delete';
						$user_setting_enabled = 'delete';
					}
					else if ($submitted_value == 'expanded' || $submitted_value == 'contracted') {
						$user_setting_value = $submitted_value;
						$user_setting_enabled = 'true';
					}
					break 2;
				default:
					//setting not allowed
					echo 'false';
					exit;
			}
			break;
		default:
			//setting not allowed
			echo 'false';
			exit;
	}

//add/update user setting
	if (isset($user_setting_value) && isset($user_setting_enabled)) {

		//get existing user setting uuid, if exists
			$sql = "select user_setting_uuid from v_user_settings ";
			$sql .= "where user_uuid = :user_uuid ";
			$sql .= "and domain_uuid = :domain_uuid ";
			$sql .= "and user_setting_category = :user_setting_category ";
			$sql .= "and user_setting_subcategory = :user_setting_subcategory ";
			$sql .= "and user_setting_name = :user_setting_name ";
			$parameters['user_uuid'] = $_SESSION['user_uuid'];
			$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
			$parameters['user_setting_category'] = $user_setting_category;
			$parameters['user_setting_subcategory'] = $user_setting_subcategory;
			$parameters['user_setting_name'] = $user_setting_name;
			$database = new database;
			$user_setting_uuid = $database->select($sql, $parameters, 'column');
			unset($sql, $parameters);

		//delete user setting
			if ($user_setting_value == 'delete' && $user_setting_enabled == 'delete') {
				if (is_uuid($user_setting_uuid)) {
					//create data array
						$array['user_settings'][0]['user_setting_uuid'] = $user_setting_uuid;
						$array['user_settings'][0]['user_uuid'] = $_SESSION['user_uuid'];
						$array['user_settings'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
					//grant temporary permissions
						$p = new permissions;
						$p->add('user_setting_delete', 'temp');
					//execute
						$database = new database;
						$database->app_name = 'user_settings';
						$database->app_uuid = '3a3337f7-78d1-23e3-0cfd-f14499b8ed97';
						$database->delete($array);
						unset($array);
					//revoke temporary permissions
						$p->delete('user_setting_delete', 'temp');
					//reset session variables to default
						require "resources/classes/domains.php";
						$domain = new domains();
						$domain->db = $db;
						$domain->set();
				}

				//set response
					echo 'deleted';

			}

		//insert or update user setting
			else {

				//create data array
					$array['user_settings'][0]['user_setting_uuid'] = is_uuid($user_setting_uuid) ? $user_setting_uuid : uuid();
					$array['user_settings'][0]['user_uuid'] = $_SESSION['user_uuid'];
					$array['user_settings'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
					$array['user_settings'][0]['user_setting_category'] = $user_setting_category;
					$array['user_settings'][0]['user_setting_subcategory'] = $user_setting_subcategory;
					$array['user_settings'][0]['user_setting_name'] = $user_setting_name;
					$array['user_settings'][0]['user_setting_value'] = $user_setting_value;
					//$array['user_settings'][0]['user_setting_order'] = $user_setting_order;
					$array['user_settings'][0]['user_setting_enabled'] = $user_setting_enabled;

				//grant temporary permissions
					$p = new permissions;
					$p->add('user_setting_add', 'temp');
					$p->add('user_setting_edit', 'temp');

				//execute
					$database = new database;
					$database->app_name = 'user_settings';
					$database->app_uuid = '3a3337f7-78d1-23e3-0cfd-f14499b8ed97';
					$database->save($array);
					unset($array);

				//revoke temporary permissions
					$p->delete('user_setting_add', 'temp');
					$p->delete('user_setting_edit', 'temp');

				//update session variable
					$_SESSION[$user_setting_category][$user_setting_subcategory][$user_setting_name] = $user_setting_value;

				//set response
					echo 'true';

			}
	}

?>