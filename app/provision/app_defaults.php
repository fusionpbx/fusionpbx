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
	Portions created by the Initial Developer are Copyright (C) 2008-2012
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
	}
	unset($prep_statement, $result);

//move the dynamic provision variables that from v_vars table to v_default_settings
	if (count($_SESSION['provision']) == 0 && $domains_processed == 1) {
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
		//delete the provision variables from system -> variables
		//$sql = "delete from v_vars ";
		//$sql .= "where var_cat = 'Provision' ";
		//echo $sql ."\n";
		//$db->exec(check_sql($sql));
		//echo "$var_name $var_value \n";
	}

?>