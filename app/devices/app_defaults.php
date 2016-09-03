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


if ($domains_processed == 1) {
	//set all lines to enabled (true) where null or empty string
		$sql = "update v_device_lines set ";
		$sql .= "enabled = 'true' ";
		$sql .= "where enabled is null ";
		$sql .= "or enabled = '' ";
		$db->exec(check_sql($sql));
		unset($sql);

	//set the device key vendor
		$sql = "select * from v_device_keys as k, v_devices as d ";
		$sql .= "where d.device_uuid = k.device_uuid  ";
		$sql .= "and k.device_uuid is not null ";
		$sql .= "and k.device_key_vendor is null ";
		$s = $db->prepare($sql);
		$s->execute();
		$device_keys = $s->fetchAll(PDO::FETCH_ASSOC);
		foreach ($device_keys as &$row) {
			$sql = "update v_device_keys ";
			$sql .= "set device_key_vendor = '".$row["device_vendor"]."' ";
			$sql .= "where device_key_uuid = '".$row["device_key_uuid"]."';\n ";
			$db->exec(check_sql($sql));
		}
		unset($device_keys, $sql);

	//add device vendor and functions to the database
	//get a list of vendors
		$prep_statement = $db->prepare("SELECT device_vendor_uuid, name FROM v_device_vendors;");
		if ($prep_statement) {
			$prep_statement->execute();
			$db_vendors = $prep_statement->fetchAll(PDO::FETCH_KEY_PAIR);
			unset($prep_statement);

		//get the vendor array
			require_once $_SERVER["DOCUMENT_ROOT"].'/'.PROJECT_PATH.'/app/devices/app_config.php';

		//get the groups and create an array to use the name to get the uuid
			$sql = "SELECT group_name, group_uuid FROM v_groups; ";
			$prep_statement = $db->prepare($sql);
			$prep_statement->execute();
			$group_uuids = $prep_statement->fetchAll(PDO::FETCH_KEY_PAIR);
			unset($prep_statement);

		//process the array
			foreach ($vendors as $vendor) {
				//check if vendor is already in the database
				$device_vendor_uuid=array_search($vendor['name'], $db_vendors);
				if(!$device_vendor_uuid) {
				//add vendor to the database
					$device_vendor_uuid = uuid();
					$sql = "insert into v_device_vendors (device_vendor_uuid, name, enabled) ";
					$sql .= "values (?, ?, 'true');";
					$prep_statement = $db->prepare($sql);
					$prep_statement->bindValue(1, $device_vendor_uuid);
					$prep_statement->bindValue(2, $vendor['name']);
					$prep_statement->execute();
					unset($prep_statement);
				}

				//get list of functions for vendor
				$prep_statement = $db->prepare("select device_vendor_function_uuid, name from v_device_vendor_functions where device_vendor_uuid = ?;");
				$prep_statement->bindValue(1, $device_vendor_uuid);
				$prep_statement->execute();
				$db_vendor_functions = $prep_statement->fetchAll(PDO::FETCH_KEY_PAIR);
				unset($prep_statement);

				//add the vendor functions
				foreach ($vendor['functions'] as $function) {
				//check if function already exists
					$device_vendor_function_uuid=array_search($function['name'], $db_vendor_functions);
					if (!$device_vendor_function_uuid) {
					//add the device vendor funtction
						$device_vendor_function_uuid = uuid();
						$sql = "insert into v_device_vendor_functions (device_vendor_uuid, device_vendor_function_uuid, name, value, enabled, description) ";
						$sql .= "values ( ?, ?, ?, ?, 'true', ?);";
						$prep_statement = $db->prepare($sql);
						$prep_statement->bindValue(1, $device_vendor_uuid);
						$prep_statement->bindValue(2, $device_vendor_function_uuid);
						$prep_statement->bindValue(3, $function['name']);
						$prep_statement->bindValue(4, $function['value']);
						$prep_statement->bindValue(5, $function['description']);
						$prep_statement->execute();
						unset($prep_statement);
					}

					//add the device vendor function groups
					if (is_array($function['groups'])) {
						//get list of function groups for vendor
						$prep_statement = $db->prepare("select device_vendor_function_group_uuid, group_name from v_device_vendor_function_groups where device_vendor_uuid = ? and device_vendor_function_uuid = ?;");
						$prep_statement->bindValue(1, $device_vendor_uuid);
						$prep_statement->execute();
						$db_vendor_function_groups = $prep_statement->fetchAll(PDO::FETCH_KEY_PAIR);
						unset($prep_statement);

						foreach ($function['groups'] as $group_name) {
							if (!array_search($group_name, $db_vendor_function_groups)) {
								$sql = "insert into v_device_vendor_function_groups ";
								$sql .= "(device_vendor_function_group_uuid, device_vendor_function_uuid, device_vendor_uuid,  group_uuid) ";
								$sql .= "values (?,?,?,?,?)";
								$prep_statement = $db->prepare($sql);
								$prep_statement->bindValue(1, uuid());
								$prep_statement->bindValue(2, $device_vendor_function_uuid);
								$prep_statement->bindValue(3, $device_vendor_uuid);
								$prep_statement->bindValue(5, $group_uuids[$group_name]);
								$prep_statement->execute();
								unset($prep_statement);
							}
						}
					}
				}
			}

		} // if prep_statement
}

?>
