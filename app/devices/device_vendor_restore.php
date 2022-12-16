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

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
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
			$x = 0; $z = 0;
			foreach ($vendors as $vendor) {
				//insert the data into the database
					$device_vendor_uuid = uuid();
					$array['device_vendors'][$x]['device_vendor_uuid'] = $device_vendor_uuid;
					$array['device_vendors'][$x]['name'] = $vendor['name'];
					$array['device_vendors'][$x]['enabled'] = 'true';

				//add the device vendor functions
					$y = 0;
					foreach ($vendor['functions'] as $function) {
						//add the device vendor function
							$device_vendor_function_uuid = uuid();
							$array['device_vendors'][$x]['device_vendor_functions'][$y]['device_vendor_uuid'] = $device_vendor_uuid;
							$array['device_vendors'][$x]['device_vendor_functions'][$y]['device_vendor_function_uuid'] = $device_vendor_function_uuid;
							//$array['device_vendors'][$x]['device_vendor_functions'][$y]['label'] = $function['label'];
							$array['device_vendors'][$x]['device_vendor_functions'][$y]['name'] = $function['name'];
							$array['device_vendors'][$x]['device_vendor_functions'][$y]['value'] = $function['value'];
							$array['device_vendors'][$x]['device_vendor_functions'][$y]['enabled'] = 'true';
							$array['device_vendors'][$x]['device_vendor_functions'][$y]['description'] = $function['description'];

						//add the device vendor function groups
							if (is_array($function['groups']) && @sizeof($function['groups']) != 0) {
								foreach ($function['groups'] as $group_name) {
									$device_vendor_function_group_uuid = uuid();
									$array['device_vendor_function_groups'][$z]['device_vendor_function_group_uuid'] = $device_vendor_function_group_uuid;
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
				
				//increment the devic vendor index
					$x++;
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
