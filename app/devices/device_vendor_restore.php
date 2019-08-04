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
	Portions created by the Initial Developer are Copyright (C) 2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('device_vendor_restore')) {
			//access granted
	}
	else {
			echo "access denied";
			exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//flush everything
	$sql = "delete from v_device_vendors";
	$database = new database;
	$database->execute($sql);
	unset($sql);

	$sql = "delete from v_device_vendor_functions";
	$database = new database;
	$database->execute($sql);
	unset($sql);

	$sql = "delete from v_device_vendor_function_groups";
	$database = new database;
	$database->execute($sql);
	unset($sql);

//add device vendor functions to the database
	$sql = "select count(*) from v_device_vendors; ";
	$database = new database;
	$num_rows = $database->select($sql, null, 'column');
	unset($sql);

	if ($num_rows == 0) {

		//get the vendor array
			require_once $_SERVER["DOCUMENT_ROOT"].'/'.PROJECT_PATH.'/app/devices/app_config.php';

		//get the groups and create an array to use the name to get the uuid
			$sql = "select * from v_groups ";
			$database = new database;
			$groups = $database->select($sql, null, 'all');
			if (is_array($groups) && @sizeof($groups) != 0) {
				foreach ($groups as $row) {
					if (!is_uuid($row['domain_uuid'])) {
						$group_uuids[$row['group_name']] = $row['group_uuid'];
					}
				}
			}
			unset($sql);

		//create insert array
			foreach ($vendors as $index_1 => $vendor) {
				//insert the data into the database
					$device_vendor_uuid = uuid();
					$array['device_vendors'][$index_1]['device_vendor_uuid'] = $device_vendor_uuid;
					$array['device_vendors'][$index_1]['name'] = $vendor['name'];
					$array['device_vendors'][$index_1]['enabled'] = 'true';

				//add the vendor functions
					foreach ($vendor['functions'] as $index_2 => $function) {
						$device_vendor_function_uuid = uuid();
						$array['device_vendor_functions'][$index_2]['device_vendor_uuid'] = $device_vendor_uuid;
						$array['device_vendor_functions'][$index_2]['device_vendor_function_uuid'] = $device_vendor_function_uuid;
						//$array['device_vendor_functions'][$index_2]['label'] = $function['label'];
						$array['device_vendor_functions'][$index_2]['name'] = $function['name'];
						$array['device_vendor_functions'][$index_2]['value'] = $function['value'];
						$array['device_vendor_functions'][$index_2]['enabled'] = 'true';
						$array['device_vendor_functions'][$index_2]['description'] = $function['description'];

						//add the device vendor function groups
							if (is_array($function['groups']) && @sizeof($function['groups']) != 0) {
								foreach ($function['groups'] as $index_3 => $group_name) {
									$device_vendor_function_group_uuid = uuid();
									$array['device_vendor_function_groups'][$index_3]['device_vendor_function_group_uuid'] = $device_vendor_function_group_uuid;
									$array['device_vendor_function_groups'][$index_3]['device_vendor_function_uuid'] = $device_vendor_function_uuid;
									$array['device_vendor_function_groups'][$index_3]['device_vendor_uuid'] = $device_vendor_uuid;
									$array['device_vendor_function_groups'][$index_3]['group_name'] = $group_name;
									$array['device_vendor_function_groups'][$index_3]['group_uuid'] = $group_uuids[$group_name];
								}
							}
					}
			}

		//assign temp permissions
			$p = new permissions;
			$p->add('device_vendor_add', 'temp');
			$p->add('device_vendor_function_add', 'temp');
			$p->add('device_vendor_function_group_add', 'temp');

		//process array
			$database = new database;
			$database->app_name = 'devices';
			$database->app_uuid = '4efa1a1a-32e7-bf83-534b-6c8299958a8e';
			$database->save($array);
			unset($array);

		//remove temp permissions
			$p->delete('device_vendor_add', 'temp');
			$p->delete('device_vendor_function_add', 'temp');
			$p->delete('device_vendor_function_group_add', 'temp');

		//set message
			message::add($text['message-restore']);

	}
	unset($num_rows);

//redirect
	header('Location: device_vendors.php');
	exit;

?>
