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
	Portions created by the Initial Developer are Copyright (C) 2008-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//process this code online once
if ($domains_processed == 1) {

	//normalize the mac address
	$sql = "select device_uuid, device_mac_address ";
	$sql .= "from v_devices ";
	$sql .= "where (device_mac_address like '%-%' or device_mac_address like '%:%') ";
	$database = new database;
	$result = $database->select($sql, null, 'all');
	if (is_array($result) && @sizeof($result) != 0) {
		foreach ($result as $row) {
			//define update values
				$device_uuid = $row["device_uuid"];
				$device_mac_address = $row["device_mac_address"];
				$device_mac_address = strtolower($device_mac_address);
				$device_mac_address = preg_replace('#[^a-fA-F0-9./]#', '', $device_mac_address);
			//build update array
				$array['devices'][0]['device_uuid'] = $device_uuid;
				$array['devices'][0]['device_mac_address'] = $device_mac_address;
			//grant temporary permissions
				$p = new permissions;
				$p->add('device_add', 'temp');
			//execute update
				$database = new database;
				$database->app_name = 'provision';
				$database->app_uuid = 'abf28ead-92ef-3de6-ebbb-023fbc2b6dd3';
				$database->save($array);
				unset($array);
			//revoke temporary permissions
				$p->delete('device_add', 'temp');
		}
	}
	unset($sql, $result, $row);

	//update http_auth_enabled set to true
	$sql = "select count(*) from v_default_settings ";
	$sql .= "where default_setting_subcategory = 'http_auth_disable' ";
	if ($database->select($sql, null, 'column') > 0) {
		//build update array
			$array['default_settings'][$x]['default_setting_uuid'] = 'c998c762-6a43-4911-a465-a9653eeb793d';
			$array['default_settings'][$x]['default_setting_subcategory'] = 'http_auth_enabled';
			$array['default_settings'][$x]['default_setting_value'] = 'true';
			$array['default_settings'][$x]['default_setting_enabled'] = 'true';

		//grant temporary permissions
			$p = new permissions;
			$p->add('default_setting_edit', 'temp');

		//execute update
			$database = new database;
			$database->app_name = 'provision';
			$database->app_uuid = 'abf28ead-92ef-3de6-ebbb-023fbc2b6dd3';
			$database->save($array);
			unset($array);

		//grant temporary permissions
			$p = new permissions;
			$p->delete('default_setting_edit', 'temp');
	}
	unset($sql);

	//update default settings
	$sql = "update v_default_settings set ";
	$sql .= "default_setting_value = 'true', ";
	$sql .= "default_setting_name = 'boolean', ";
	$sql .= "default_setting_enabled = 'true' ";
	$sql .= "where default_setting_category = 'provision' ";
	$sql .= "and default_setting_subcategory = 'http_domain_filter' ";
	$sql .= "and default_setting_name = 'text' ";
	$sql .= "and default_setting_value = 'false' ";
	$sql .= "and default_setting_enabled = 'false' ";
	$database = new database;
	$database->execute($sql);

	//update default settings
	$sql = "update v_default_settings set ";
	$sql .= "default_setting_name = 'array' ";
	$sql .= "where default_setting_category = 'provision' ";
	$sql .= "and default_setting_subcategory = 'http_auth_password' ";
	$sql .= "and default_setting_name = 'text' ";
	$database = new database;
	$database->execute($sql);

	//update domain settings
	$sql = "update v_domain_settings set ";
	$sql .= "domain_setting_name = 'array' ";
	$sql .= "where domain_setting_category = 'provision' ";
	$sql .= "and domain_setting_subcategory = 'http_auth_password' ";
	$sql .= "and domain_setting_name = 'text' ";
	$database = new database;
	$database->execute($sql);

}

?>
