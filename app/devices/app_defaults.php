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
	Portions created by the Initial Developer are Copyright (C) 2008-2022
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

if ($domains_processed == 1) {

	//set all lines to enabled (true) where null or empty string
		$sql = "select device_line_uuid from v_device_lines ";
		$sql .= "where enabled is null or enabled = '' ";
		$database = new database;
		$device_lines = $database->select($sql, null, 'all');
		if (is_array($device_lines) && @sizeof($device_lines) != 0) {
			$sql = "update v_device_lines set ";
			$sql .= "enabled = 'true' ";
			$sql .= "where enabled is null ";
			$sql .= "or enabled = '' ";
			$database = new database;
			$database->execute($sql);
			unset($sql);
		}
		unset($sql, $device_lines);

	//set label to user_id if the label is null
		$sql = "select device_line_uuid from v_device_lines ";
		$sql .= "where label is null ";
		$database = new database;
		$device_lines = $database->select($sql, null, 'all');
		if (is_array($device_lines) && @sizeof($device_lines) != 0) {
			foreach($device_lines as $row) {
				$sql = "update v_device_lines ";
				$sql .= "set label = user_id ";
				$sql .= "where label is null ";
				$database->execute($sql);
			}
		}

	//set the device key vendor
		$sql = "select * from v_device_keys as k, v_devices as d ";
		$sql .= "where d.device_uuid = k.device_uuid  ";
		$sql .= "and k.device_uuid is not null ";
		$sql .= "and k.device_key_vendor is null ";
		$database = new database;
		$device_keys = $database->select($sql, null, 'all');
		if (is_array($device_keys) && @sizeof($device_keys)) {
			foreach ($device_keys as $index => &$row) {
				$array['device_keys'][$index]['device_key_uuid'] = $row["device_key_uuid"];
				$array['device_keys'][$index]['device_key_vendor'] = $row["device_vendor"];
			}
			if (is_array($array) && @sizeof($array)) {
				$p = new permissions;
				$p->add('device_key_edit', 'temp');

				$database = new database;
				$database->app_name = 'devices';
				$database->app_uuid = '4efa1a1a-32e7-bf83-534b-6c8299958a8e';
				$database->save($array);
				$response = $database->message;
				unset($array);

				$p->delete('device_key_edit', 'temp');
			}
		}
		unset($sql, $device_keys);

	//set the device profile keys
		$sql = "select count(*) from v_device_profile_keys ";
		$database = new database;
		$num_rows = $database->select($sql, null, 'column');
		if ($num_rows == 0) {
			//get the device profile keys from device_keys table
			$sql = "select * from v_device_keys ";
			$sql .= "where device_profile_uuid is not null ";
			$database = new database;
			$device_profile_keys = $database->select($sql, null, 'all');

			//loop through the device_keys to build the data array
			if (is_array($device_profile_keys) && @sizeof($device_profile_keys)) {
				foreach ($device_profile_keys as $index => &$row) {
					$array['device_profile_keys'][$index]['device_profile_key_uuid'] = $row["device_key_uuid"];
					$array['device_profile_keys'][$index]['domain_uuid'] = $row["domain_uuid"];
					$array['device_profile_keys'][$index]['device_profile_uuid'] = $row["device_profile_uuid"];
					$array['device_profile_keys'][$index]['profile_key_id'] = $row["device_key_id"];
					$array['device_profile_keys'][$index]['profile_key_category'] = $row["device_key_category"];
					$array['device_profile_keys'][$index]['profile_key_vendor'] = $row["device_key_vendor"];
					$array['device_profile_keys'][$index]['profile_key_type'] = $row["device_key_type"];
					$array['device_profile_keys'][$index]['profile_key_line'] = $row["device_key_line"];
					$array['device_profile_keys'][$index]['profile_key_value'] = $row["device_key_value"];
					$array['device_profile_keys'][$index]['profile_key_extension'] = $row["device_key_extension"];
					$array['device_profile_keys'][$index]['profile_key_protected'] = $row["device_key_protected"];
					$array['device_profile_keys'][$index]['profile_key_label'] = $row["device_key_label"];
					$array['device_profile_keys'][$index]['profile_key_icon'] = $row["device_key_icon"];
				}
			}

			//save the array
			if (is_array($array) && @sizeof($array)) {
				$p = new permissions;
				$p->add('device_profile_key_add', 'temp');

				$database = new database;
				$database->app_name = 'devices';
				$database->app_uuid = '4efa1a1a-32e7-bf83-534b-6c8299958a8e';
				$database->save($array);
				$response = $database->message;
				unset($array);

				$p->delete('device_profile_key_add', 'temp');
			}
		}
		unset($sql, $device_profile_keys);

	//set the device profile settings
		$sql = "select count(*) from v_device_profile_settings ";
		$database = new database;
		$num_rows = $database->select($sql, null, 'column');
		if ($num_rows == 0) {
			//get the device profile keys from device_keys table
			$sql = "select * from v_device_settings ";
			$sql .= "where device_profile_uuid is not null ";
			$database = new database;
			$device_profile_keys = $database->select($sql, null, 'all');

			//loop through the device_keys to build the data array
			if (is_array($device_profile_keys) && @sizeof($device_profile_keys)) {
				foreach ($device_profile_keys as $index => &$row) {
					$array['device_profile_settings'][$index]['device_profile_setting_uuid'] = $row["device_setting_uuid"];
					$array['device_profile_settings'][$index]['domain_uuid'] = $row["domain_uuid"];
					$array['device_profile_settings'][$index]['device_profile_uuid'] = $row["device_profile_uuid"];
					$array['device_profile_settings'][$index]['profile_setting_name'] = $row["device_setting_subcategory"];
					$array['device_profile_settings'][$index]['profile_setting_value'] = $row["device_setting_value"];
					$array['device_profile_settings'][$index]['profile_setting_enabled'] = $row["device_setting_enabled"];
					$array['device_profile_settings'][$index]['profile_setting_description'] = $row["device_setting_description"];
				}
			}

			//save the array
			if (is_array($array) && @sizeof($array)) {
				$p = new permissions;
				$p->add('device_profile_setting_add', 'temp');

				$database = new database;
				$database->app_name = 'devices';
				$database->app_uuid = '4efa1a1a-32e7-bf83-534b-6c8299958a8e';
				$database->save($array);
				$response = $database->message;
				unset($array);

				$p->delete('device_profile_setting_add', 'temp');
			}
		}
		unset($sql, $device_profile_keys);

	//add device vendor functions to the database
		$sql = "select count(*) from v_device_vendors; ";
		$database = new database;
		$num_rows = $database->select($sql, null, 'column');
		if ($num_rows == 0) {

			//get the vendor array
				require_once $_SERVER["DOCUMENT_ROOT"].'/'.PROJECT_PATH.'/app/devices/app_config.php';

			//get the groups and create an array to use the name to get the uuid
				$sql = "select * from v_groups ";
				$database = new database;
				$groups = $database->select($sql, null, 'all');
				foreach ($groups as $row) {
					if ($row['domain_uuid'] == '') {
						$group_uuids[$row['group_name']] = $row['group_uuid'];
					}
				}
				unset($sql, $groups, $row);

			//build the array
				if (is_array($vendors) && @sizeof($vendors) != 0) {
					$x = 0; $y = 0; $z = 0;
					foreach ($vendors as $vendor) {
						//insert the data into the database
							$device_vendor_uuid = uuid();
							$array['device_vendors'][$x]['device_vendor_uuid'] = $device_vendor_uuid;
							$array['device_vendors'][$x]['name'] = $vendor['name'];
							$array['device_vendors'][$x]['enabled'] = 'true';

						//add the vendor functions
							if (is_array($vendor['functions']) && @sizeof($vendor['functions'])) {

								foreach ($vendor['functions'] as $function) {
									//add the device vendor function
										$device_vendor_function_uuid = uuid();
										$array['device_vendor_functions'][$y]['device_vendor_uuid'] = $device_vendor_uuid;
										$array['device_vendor_functions'][$y]['device_vendor_function_uuid'] = $device_vendor_function_uuid;
										$array['device_vendor_functions'][$y]['name'] = $function['name'];
										$array['device_vendor_functions'][$y]['value'] = $function['value'];
										$array['device_vendor_functions'][$y]['enabled'] = 'true';
										$array['device_vendor_functions'][$y]['description'] = $function['description'];

									//add the device vendor function groups
										if (is_array($function['groups']) && @sizeof($function['groups']) != 0) {
											foreach ($function['groups'] as $group_name) {
												$array['device_vendor_function_groups'][$z]['device_vendor_function_group_uuid'] = uuid();
												$array['device_vendor_function_groups'][$z]['device_vendor_function_uuid'] = $device_vendor_function_uuid;
												$array['device_vendor_function_groups'][$z]['device_vendor_uuid'] = $device_vendor_uuid;
												$array['device_vendor_function_groups'][$z]['group_name'] = $group_name;
												$array['device_vendor_function_groups'][$z]['group_uuid'] = $group_uuids[$group_name];
												$z++;
											}
										}

									//increment the device vendor function index
										$y++;
								}
							}
							
						//increment the devic vendor index
							$x++;
					}
				}

			//execute
				if (is_array($array) && @sizeof($array) != 0) {
					$p = new permissions;
					$p->add('device_vendor_add', 'temp');
					$p->add('device_vendor_function_add', 'temp');
					$p->add('device_vendor_function_group_add', 'temp');

					$database = new database;
					$database->app_name = 'devices';
					$database->app_uuid = '4efa1a1a-32e7-bf83-534b-6c8299958a8e';
					$database->save($array);
					unset($array);

					$p->delete('device_vendor_add', 'temp');
					$p->delete('device_vendor_function_add', 'temp');
					$p->delete('device_vendor_function_group_add', 'temp');
				}

		}
		unset($num_rows);
	
	//where the device lines label is null set the value to the display name to maintain the original behavior
		$sql = "update v_device_lines set label = display_name where label is null;\n";
		$database->execute($sql);
		unset($sql);
}

?>
