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

//normalize the mac address
	$sql = "select device_uuid, device_mac_address ";
	$sql .= "from v_devices ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and (device_mac_address like '%-%' or device_mac_address like '%:%') ";
	$prep_statement = $db->prepare(check_sql($sql));
	if ($prep_statement) {
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach($result as $row) {
			$device_uuid = $row["device_uuid"];
			$device_mac_address = $row["device_mac_address"];
			$device_mac_address = strtolower($device_mac_address);
			$device_mac_address = preg_replace('#[^a-fA-F0-9./]#', '', $device_mac_address);

			$sql = "update v_devices set ";
			$sql .= "device_mac_address = '".$device_mac_address."' ";
			$sql .= "where device_uuid = '".$device_uuid."' ";
			$db->exec(check_sql($sql));
			unset($sql);
		}
		unset($prep_statement, $result);
	}

//process this code online once
	if ($domains_processed == 1) {

		//define array of settings
			$x = 0;
			$array[$x]['default_setting_uuid'] = '931f9369-9aac-4620-8d4b-7d2bf642b1d2';
			$array[$x]['default_setting_category'] = 'provision';
			$array[$x]['default_setting_subcategory'] = 'enabled';
			$array[$x]['default_setting_name'] = 'text';
			$array[$x]['default_setting_value'] = 'true';
			$array[$x]['default_setting_enabled'] = 'false';
			$array[$x]['default_setting_description'] = '';
			$x++;
			$array[$x]['default_setting_uuid'] = '3790e46b-ef9e-4cdc-bfd2-6b3708751843';
			$array[$x]['default_setting_category'] = 'provision';
			$array[$x]['default_setting_subcategory'] = 'auto_insert_enabled';
			$array[$x]['default_setting_name'] = 'boolean';
			$array[$x]['default_setting_value'] = 'true';
			$array[$x]['default_setting_enabled'] = 'false';
			$array[$x]['default_setting_description'] = '';
			$x++;
			$array[$x]['default_setting_uuid'] = '27b7ccfd-58d7-409c-80ff-cca014349d70';
			$array[$x]['default_setting_category'] = 'provision';
			$array[$x]['default_setting_subcategory'] = 'http_auth_type';
			$array[$x]['default_setting_name'] = 'text';
			$array[$x]['default_setting_value'] = 'digest';
			$array[$x]['default_setting_enabled'] = 'false';
			$array[$x]['default_setting_description'] = '';
			$x++;
			$array[$x]['default_setting_uuid'] = 'c6a5b05b-210d-484f-bbb6-c1dd2223992e';
			$array[$x]['default_setting_category'] = 'provision';
			$array[$x]['default_setting_subcategory'] = 'http_auth_username';
			$array[$x]['default_setting_name'] = 'text';
			$array[$x]['default_setting_value'] = '';
			$array[$x]['default_setting_enabled'] = 'false';
			$array[$x]['default_setting_description'] = '';
			$x++;
			$array[$x]['default_setting_uuid'] = 'ed380d7d-b3b8-40b4-8528-f10d521ddef0';
			$array[$x]['default_setting_category'] = 'provision';
			$array[$x]['default_setting_subcategory'] = 'http_auth_password';
			$array[$x]['default_setting_name'] = 'text';
			$array[$x]['default_setting_value'] = '';
			$array[$x]['default_setting_enabled'] = 'false';
			$array[$x]['default_setting_description'] = '';
			$x++;
			$array[$x]['default_setting_uuid'] = 'd376fe0f-fb89-4418-8fb4-590e4cac483f';
			$array[$x]['default_setting_category'] = 'provision';
			$array[$x]['default_setting_subcategory'] = 'cidr';
			$array[$x]['default_setting_name'] = 'array';
			$array[$x]['default_setting_value'] = '';
			$array[$x]['default_setting_enabled'] = 'false';
			$array[$x]['default_setting_description'] = '';
			$x++;
			$array[$x]['default_setting_uuid'] = 'a5323190-b733-49c1-99c4-396ab8950bb8';
			$array[$x]['default_setting_category'] = 'provision';
			$array[$x]['default_setting_subcategory'] = 'admin_name';
			$array[$x]['default_setting_name'] = 'text';
			$array[$x]['default_setting_value'] = '';
			$array[$x]['default_setting_enabled'] = 'false';
			$array[$x]['default_setting_description'] = '';
			$x++;
			$array[$x]['default_setting_uuid'] = 'ae3f809e-81af-4ed4-82f7-275251210d3a';
			$array[$x]['default_setting_category'] = 'provision';
			$array[$x]['default_setting_subcategory'] = 'admin_password';
			$array[$x]['default_setting_name'] = 'text';
			$array[$x]['default_setting_value'] = '';
			$array[$x]['default_setting_enabled'] = 'false';
			$array[$x]['default_setting_description'] = '';
			$x++;
			$array[$x]['default_setting_uuid'] = 'cd2173be-aa43-4fd2-9c75-02f49c199485';
			$array[$x]['default_setting_category'] = 'provision';
			$array[$x]['default_setting_subcategory'] = 'path';
			$array[$x]['default_setting_name'] = 'text';
			$array[$x]['default_setting_value'] = '';
			$array[$x]['default_setting_enabled'] = 'false';
			$array[$x]['default_setting_description'] = '';
			$x++;
			$array[$x]['default_setting_uuid'] = '559cd2d6-8ca0-4e6e-ae9d-565c8eed898d';
			$array[$x]['default_setting_category'] = 'provision';
			$array[$x]['default_setting_subcategory'] = 'voicemail_number';
			$array[$x]['default_setting_name'] = 'text';
			$array[$x]['default_setting_value'] = '*97';
			$array[$x]['default_setting_enabled'] = 'true';
			$array[$x]['default_setting_description'] = '';
			$x++;
			$array[$x]['default_setting_uuid'] = 'a9dc7f4a-0a19-40cb-829a-093bf81d00db';
			$array[$x]['default_setting_category'] = 'provision';
			$array[$x]['default_setting_subcategory'] = 'line_sip_port';
			$array[$x]['default_setting_name'] = 'numeric';
			$array[$x]['default_setting_value'] = '5060';
			$array[$x]['default_setting_enabled'] = 'true';
			$array[$x]['default_setting_description'] = '';
			$x++;
			$array[$x]['default_setting_uuid'] = '472300e4-267a-4f0d-83ab-04d2017c7d0f';
			$array[$x]['default_setting_category'] = 'provision';
			$array[$x]['default_setting_subcategory'] = 'line_sip_transport';
			$array[$x]['default_setting_name'] = 'text';
			$array[$x]['default_setting_value'] = 'tcp';
			$array[$x]['default_setting_enabled'] = 'true';
			$array[$x]['default_setting_description'] = '';
			$x++;
			$array[$x]['default_setting_uuid'] = '5bc38b86-089f-44cb-9fff-38be38c497e8';
			$array[$x]['default_setting_category'] = 'provision';
			$array[$x]['default_setting_subcategory'] = 'line_register_expires';
			$array[$x]['default_setting_name'] = 'numeric';
			$array[$x]['default_setting_value'] = '80';
			$array[$x]['default_setting_enabled'] = 'true';
			$array[$x]['default_setting_description'] = '';
			$x++;
			$array[$x]['default_setting_uuid'] = '1752b247-873b-4d41-9846-b9df93efe8df';
			$array[$x]['default_setting_category'] = 'provision';
			$array[$x]['default_setting_subcategory'] = 'polycom_gmt_offset';
			$array[$x]['default_setting_name'] = 'text';
			$array[$x]['default_setting_value'] = '';
			$array[$x]['default_setting_enabled'] = 'false';
			$array[$x]['default_setting_description'] = '3600 * GMT offset';
			$x++;
			$array[$x]['default_setting_uuid'] = '098b2abd-3af3-4104-8fba-fabf9573f925';
			$array[$x]['default_setting_category'] = 'provision';
			$array[$x]['default_setting_subcategory'] = 'polycom_digitmap';
			$array[$x]['default_setting_name'] = 'text';
			$array[$x]['default_setting_value'] = '[*]xxxx|[2-9]11|0T|011xxx.T|[0-1][2-9]xxxxxxxxx|[2-9]xxxxxxxxx|[1-9]xxT|**x.T';
			$array[$x]['default_setting_enabled'] = 'false';
			$array[$x]['default_setting_description'] = '';
			$x++;
			$array[$x]['default_setting_uuid'] = '5aa7f396-d742-48f1-b53f-c609b9a6759a';
			$array[$x]['default_setting_category'] = 'provision';
			$array[$x]['default_setting_subcategory'] = 'daylight_savings_start_month';
			$array[$x]['default_setting_name'] = 'text';
			$array[$x]['default_setting_value'] = '3';
			$array[$x]['default_setting_enabled'] = 'true';
			$array[$x]['default_setting_description'] = '';
			$x++;
			$array[$x]['default_setting_uuid'] = '7d742914-9c55-4cee-a295-c19501389f41';
			$array[$x]['default_setting_category'] = 'provision';
			$array[$x]['default_setting_subcategory'] = 'daylight_savings_start_day';
			$array[$x]['default_setting_name'] = 'text';
			$array[$x]['default_setting_value'] = '13';
			$array[$x]['default_setting_enabled'] = 'true';
			$array[$x]['default_setting_description'] = '';
			$x++;
			$array[$x]['default_setting_uuid'] = '7b444c2f-bed7-4da5-8cf3-4cc79df8625f';
			$array[$x]['default_setting_category'] = 'provision';
			$array[$x]['default_setting_subcategory'] = 'daylight_savings_start_time';
			$array[$x]['default_setting_name'] = 'text';
			$array[$x]['default_setting_value'] = '2';
			$array[$x]['default_setting_enabled'] = 'true';
			$array[$x]['default_setting_description'] = '';
			$x++;
			$array[$x]['default_setting_uuid'] = 'f8e7b78b-1b84-42da-9e14-ec03dbb67c52';
			$array[$x]['default_setting_category'] = 'provision';
			$array[$x]['default_setting_subcategory'] = 'daylight_savings_stop_month';
			$array[$x]['default_setting_name'] = 'text';
			$array[$x]['default_setting_value'] = '11';
			$array[$x]['default_setting_enabled'] = 'true';
			$array[$x]['default_setting_description'] = '';
			$x++;
			$array[$x]['default_setting_uuid'] = '6f4a9657-e130-4003-bc96-22a1312d76f4';
			$array[$x]['default_setting_category'] = 'provision';
			$array[$x]['default_setting_subcategory'] = 'daylight_savings_stop_day';
			$array[$x]['default_setting_name'] = 'text';
			$array[$x]['default_setting_value'] = '6';
			$array[$x]['default_setting_enabled'] = 'true';
			$array[$x]['default_setting_description'] = '';
			$x++;
			$array[$x]['default_setting_uuid'] = 'd3e72ae2-b887-443d-8523-96726343bb55';
			$array[$x]['default_setting_category'] = 'provision';
			$array[$x]['default_setting_subcategory'] = 'daylight_savings_stop_time';
			$array[$x]['default_setting_name'] = 'text';
			$array[$x]['default_setting_value'] = '2';
			$array[$x]['default_setting_enabled'] = 'true';
			$array[$x]['default_setting_description'] = '';
			$x++;
			$array[$x]['default_setting_uuid'] = '931d6cc7-ca82-4813-ae92-7015e0c2ea1b';
			$array[$x]['default_setting_category'] = 'provision';
			$array[$x]['default_setting_subcategory'] = 'http_domain_filter';
			$array[$x]['default_setting_name'] = 'text';
			$array[$x]['default_setting_value'] = 'false';
			$array[$x]['default_setting_enabled'] = 'false';
			$array[$x]['default_setting_description'] = '';
			$x++;
			$array[$x]['default_setting_uuid'] = 'fc2fa8cd-b14e-48e3-99bd-7c01c9d6208d';
			$array[$x]['default_setting_category'] = 'provision';
			$array[$x]['default_setting_subcategory'] = 'yealink_time_zone';
			$array[$x]['default_setting_name'] = 'text';
			$array[$x]['default_setting_value'] = '-6';
			$array[$x]['default_setting_enabled'] = 'false';
			$array[$x]['default_setting_description'] = 'Time zone ranges from -11 to +12';
			$x++;
			$array[$x]['default_setting_uuid'] = '7f4a1607-4cbe-49f5-8cd2-6d599b89bd9b';
			$array[$x]['default_setting_category'] = 'provision';
			$array[$x]['default_setting_subcategory'] = 'yealink_time_format';
			$array[$x]['default_setting_name'] = 'text';
			$array[$x]['default_setting_value'] = '1';
			$array[$x]['default_setting_enabled'] = 'false';
			$array[$x]['default_setting_description'] = '0-12 Hour, 1-24 Hour';
			$x++;
			$array[$x]['default_setting_uuid'] = '166b27d1-1860-4154-88d3-5e15781e7bbb';
			$array[$x]['default_setting_category'] = 'provision';
			$array[$x]['default_setting_subcategory'] = 'yealink_date_format';
			$array[$x]['default_setting_name'] = 'text';
			$array[$x]['default_setting_value'] = '3';
			$array[$x]['default_setting_enabled'] = 'false';
			$array[$x]['default_setting_description'] = '0-WWW MMM DD (default), 1-DD-MMM-YY, 2-YYYY-MM-DD, 3-DD/MM/YYYY, 4-MM/DD/YY, 5-DD MMM YYYY, 6-WWW DD MMM';
			$x++;
			$array[$x]['default_setting_uuid'] = '6c4430f6-3713-4c8b-9da3-eaf1705d7dc3';
			$array[$x]['default_setting_category'] = 'provision';
			$array[$x]['default_setting_subcategory'] = 'contact_users';
			$array[$x]['default_setting_name'] = 'boolean';
			$array[$x]['default_setting_value'] = 'true';
			$array[$x]['default_setting_enabled'] = 'false';
			$array[$x]['default_setting_description'] = '';
			$x++;
			$array[$x]['default_setting_uuid'] = 'c5196771-f408-40b3-81c7-b4ce525620c3';
			$array[$x]['default_setting_category'] = 'provision';
			$array[$x]['default_setting_subcategory'] = 'contact_groups';
			$array[$x]['default_setting_name'] = 'boolean';
			$array[$x]['default_setting_value'] = 'true';
			$array[$x]['default_setting_enabled'] = 'false';
			$array[$x]['default_setting_description'] = '';
			$x++;
			$array[$x]['default_setting_uuid'] = '8854358d-c6a4-4eeb-b21b-37ced80a4fbb';
			$array[$x]['default_setting_category'] = 'provision';
			$array[$x]['default_setting_subcategory'] = 'contact_extensions';
			$array[$x]['default_setting_name'] = 'boolean';
			$array[$x]['default_setting_value'] = 'true';
			$array[$x]['default_setting_enabled'] = 'false';
			$array[$x]['default_setting_description'] = 'allow extensions to be provisioned as contacts as $extensions in provision templates';
			$x++;
			$array[$x]['default_setting_uuid'] = 'd157078e-b363-4f34-a6d4-8a86990a40b7';
			$array[$x]['default_setting_category'] = 'provision';
			$array[$x]['default_setting_subcategory'] = 'number_as_presence_id';
			$array[$x]['default_setting_name'] = 'text';
			$array[$x]['default_setting_value'] = 'true';
			$array[$x]['default_setting_enabled'] = 'true';
			$array[$x]['default_setting_description'] = '';
			$x++;
			$array[$x]['default_setting_uuid'] = '48dd60fe-d7de-417c-85c4-2d2d897a709c';
			$array[$x]['default_setting_category'] = 'provision';
			$array[$x]['default_setting_subcategory'] = 'ntp_server_primary';
			$array[$x]['default_setting_name'] = 'text';
			$array[$x]['default_setting_value'] = 'pool.ntp.org';
			$array[$x]['default_setting_enabled'] = 'true';
			$array[$x]['default_setting_description'] = '';
			$x++;
			$array[$x]['default_setting_uuid'] = '7bcc3c26-ac55-4934-be9f-e0edfbc7193b';
			$array[$x]['default_setting_category'] = 'provision';
			$array[$x]['default_setting_subcategory'] = 'ntp_server_secondary';
			$array[$x]['default_setting_name'] = 'text';
			$array[$x]['default_setting_value'] = '2.us.pool.ntp.org';
			$array[$x]['default_setting_enabled'] = 'true';
			$array[$x]['default_setting_description'] = '';


		//get an array of the default settings
			$sql = "select * from v_default_settings ";
			$sql .= "where default_setting_category = 'provision' ";
			$prep_statement = $db->prepare($sql);
			$prep_statement->execute();
			$default_settings = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			unset ($prep_statement, $sql);

		//find the missing default settings
			$x = 0;
			foreach ($array as $setting) {
				$found = false;
				$missing[$x] = $setting;
				foreach ($default_settings as $row) {
					if (trim($row['default_setting_subcategory']) == trim($setting['default_setting_subcategory'])) {
						$found = true;
						//remove items from the array that were found
						unset($missing[$x]);
					}
				}
				$x++;
			}
			unset($array);

		//update the array structure
			if (is_array($missing)) {
				$array['default_settings'] = $missing;
				unset($missing);
			}

		//add the default settings
			if (is_array($array)) {
				$database = new database;
				$database->app_name = 'default_settings';
				$database->app_uuid = '2c2453c0-1bea-4475-9f44-4d969650de09';
				$database->save($array);
				$message = $database->message;
				unset($database);
			}

		//move the dynamic provision variables that from v_vars table to v_default_settings
			if (count($_SESSION['provision']) == 0) {
				$sql = "select * from v_vars ";
				$sql .= "where var_cat = 'Provision' ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				foreach ($result as &$row) {
					//set the variable
						$var_name = check_str($row['var_name']);
					//remove the 'v_' prefix from the variable name
						if (substr($var_name, 0, 2) == "v_") {
							$var_name = substr($var_name, 2);
						}
					//add the provision variable to the default settings table
						$sql = "insert into v_default_settings ";
						$sql .= "(";
						$sql .= "default_setting_uuid, ";
						$sql .= "default_setting_category, ";
						$sql .= "default_setting_subcategory, ";
						$sql .= "default_setting_name, ";
						$sql .= "default_setting_value, ";
						$sql .= "default_setting_enabled, ";
						$sql .= "default_setting_description ";
						$sql .= ") ";
						$sql .= "values ";
						$sql .= "(";
						$sql .= "'".uuid()."', ";
						$sql .= "'provision', ";
						$sql .= "'".$var_name."', ";
						$sql .= "'var', ";
						$sql .= "'".check_str($row['var_value'])."', ";
						$sql .= "'".check_str($row['var_enabled'])."', ";
						$sql .= "'".check_str($row['var_description'])."' ";
						$sql .= ")";
						$db->exec(check_sql($sql));
						unset($sql);
				}
				unset($prep_statement);
				//delete the provision variables from system -> variables
				//$sql = "delete from v_vars ";
				//$sql .= "where var_cat = 'Provision' ";
				//echo $sql ."\n";
				//$db->exec(check_sql($sql));
				//echo "$var_name $var_value \n";
			}

		//unset the array variable
			unset($array);
	}

?>
