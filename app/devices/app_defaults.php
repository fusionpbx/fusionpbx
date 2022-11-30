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

	//add device vendor functions to the database
		$sql = "select count(*) as num_rows from v_device_vendors; ";
		$prep_statement = $db->prepare($sql);
		if ($prep_statement) {
			$prep_statement->execute();
			$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
			if ($row['num_rows'] == 0) {

				//get the vendor array
					require_once $_SERVER["DOCUMENT_ROOT"].'/'.PROJECT_PATH.'/app/devices/app_config.php';

				//get the groups and create an array to use the name to get the uuid
					$sql = "select * from v_groups; ";
					$prep_statement = $db->prepare($sql);
					$prep_statement->execute();
					$groups = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
					unset($prep_statement);
					foreach ($groups as $row) {
						if ($row['domain_uuid'] == '') {
							$group_uuids[$row['group_name']] = $row['group_uuid'];
						}
					}

				//process the array
					foreach ($vendors as $vendor) {
						//insert the data into the database
							$device_vendor_uuid = uuid();
							$sql = "insert into v_device_vendors ";
							$sql .= "(";
							$sql .= "device_vendor_uuid, ";
							$sql .= "name, ";
							$sql .= "enabled ";
							$sql .= ") ";
							$sql .= "values ";
							$sql .= "( ";
							$sql .= "'".$device_vendor_uuid."', ";
							$sql .= "'".$vendor['name']."', ";
							$sql .= "'true' ";
							$sql .= ");";
							//echo $sql."\n";
							$db->exec(check_sql($sql));
							unset($sql);

						//add the vendor functions
							foreach ($vendor['functions'] as $function) {
								//get the id
									$device_vendor_function_uuid = uuid();
								//add the device vendor function
									$sql = "insert into v_device_vendor_functions ";
									$sql .= "(";
									$sql .= "device_vendor_uuid, ";
									$sql .= "device_vendor_function_uuid, ";
									//$sql .= "label, ";
									$sql .= "name, ";
									$sql .= "value, ";
									$sql .= "enabled, ";
									$sql .= "description ";
									$sql .= ") ";
									$sql .= "values ";
									$sql .= "( ";
									$sql .= "'".$device_vendor_uuid."', ";
									$sql .= "'".$device_vendor_function_uuid."', ";
									//$sql .= "'".$function['label']."', ";
									$sql .= "'".$function['name']."', ";
									$sql .= "'".$function['value']."', ";
									$sql .= "'true', ";
									$sql .= "'".$function['description']."' ";
									$sql .= ");";
									//echo $sql."\n";
									$db->exec(check_sql($sql));
									unset($sql);

								//add the device vendor function groups
									if (is_array($function['groups'])) {
										$sql = "insert into v_device_vendor_function_groups ";
										$sql .= "(";
										$sql .= "device_vendor_function_group_uuid, ";
										$sql .= "device_vendor_function_uuid, ";
										$sql .= "device_vendor_uuid, ";
										$sql .= "group_name, ";
										$sql .= "group_uuid ";
										$sql .= ") ";
										$sql .= "values ";
										$i = 0;
										foreach ($function['groups'] as $group_name) {
											if ($i == 0) { $sql .= "("; } else { $sql .= ",("; }
											$sql .= "'".uuid()."', ";
											$sql .= "'".$device_vendor_function_uuid."', ";
											$sql .= "'".$device_vendor_uuid."', ";
											$sql .= "'$group_name', ";
											$sql .= "'".$group_uuids[$group_name]."' ";
											$sql .= ")";
											$i++;
										}
										$db->exec($sql);
									}
							}
					}

			} //if num_rows
		} // if prep_statement
}
?>
