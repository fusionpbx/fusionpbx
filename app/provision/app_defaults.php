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
	$sql = "select device_uuid, device_address ";
	$sql .= "from v_devices ";
	$sql .= "where (device_address like '%-%' or device_address like '%:%') ";
	$result = $database->select($sql, null, 'all');
	if (!empty($result)) {
		foreach ($result as $row) {
			//define update values
				$device_uuid = $row["device_uuid"];
				$device_address = $row["device_address"];
				$device_address = strtolower($device_address);
				$device_address = preg_replace('#[^a-fA-F0-9./]#', '', $device_address);
			//build update array
				$array['devices'][0]['device_uuid'] = $device_uuid;
				$array['devices'][0]['device_address'] = $device_address;
			//grant temporary permissions
				$p = permissions::new();
				$p->add('device_add', 'temp');
			//execute update
				$database->app_name = 'provision';
				$database->app_uuid = 'abf28ead-92ef-3de6-ebbb-023fbc2b6dd3';
				$database->save($array, false);
				unset($array);
			//revoke temporary permissions
				$p->delete('device_add', 'temp');
		}
	}
	unset($sql, $result, $row, $p);

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
			$p = permissions::new();
			$p->add('default_setting_edit', 'temp');

		//execute update
			$database->app_name = 'provision';
			$database->app_uuid = 'abf28ead-92ef-3de6-ebbb-023fbc2b6dd3';
			$database->save($array, false);
			unset($array);

		//revoke temporary permissions
			$p->delete('default_setting_edit', 'temp');
			unset($p);
	}
	unset($sql);

	//update default settings in category provision set enabled to use type boolean
	$sql = "update v_default_settings ";
	$sql .= "set default_setting_name = 'boolean' ";
	$sql .= "where default_setting_category = 'provision' ";
	$sql .= "and default_setting_subcategory = 'enabled' ";
	$sql .= "and default_setting_name <> 'boolean' ";
	$database->execute($sql);

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
	$database->execute($sql);

	//update default settings
	$sql = "update v_default_settings set ";
	$sql .= "default_setting_name = 'array' ";
	$sql .= "where default_setting_category = 'provision' ";
	$sql .= "and default_setting_subcategory = 'http_auth_password' ";
	$sql .= "and default_setting_name = 'text' ";
	$database->execute($sql);

	//update domain settings
	$sql = "update v_domain_settings set ";
	$sql .= "domain_setting_name = 'array' ";
	$sql .= "where domain_setting_category = 'provision' ";
	$sql .= "and domain_setting_subcategory = 'http_auth_password' ";
	$sql .= "and domain_setting_name = 'text' ";
	$database->execute($sql);

	//update if the type is boolean with value of 0 or 1 use type text, or if type numeric use type text.
	//explanation: the template default setting use string for the template values, boolean type only used with conditions
	$sql = "update v_default_settings ";
	$sql .= "set default_setting_name = 'text' ";
	$sql .= "where ";
	$sql .= "( ";
	$sql .= " default_setting_category = 'provision' ";
	$sql .= " and default_setting_value in ('0', '1') ";
	$sql .= " and default_setting_name = 'boolean' ";
	$sql .= ") ";
	$sql .= "or ";
	$sql .= "( ";
	$sql .= "default_setting_category = 'provision' ";
	$sql .= "and default_setting_name = 'numeric' ";
	$sql .= ") ";
	$database->execute($sql);

}

?>
