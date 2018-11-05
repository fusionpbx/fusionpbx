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
	Portions created by the Initial Developer are Copyright (C) 2008-2018
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
	$prep_statement = $db->prepare(check_sql($sql));
	if ($prep_statement) {
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		if (is_array($result)) {
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
	}

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
	$db->exec($sql);
	unset($sql);

	//update default settings
	$sql = "update v_default_settings set ";
	$sql .= "default_setting_name = 'array' ";
	$sql .= "where default_setting_category = 'provision' ";
	$sql .= "and default_setting_subcategory = 'http_auth_password' ";
	$sql .= "and default_setting_name = 'text' ";
	$db->exec($sql);
	unset($sql);

	//update domain settings
	$sql = "update v_domain_settings set ";
	$sql .= "domain_setting_name = 'array' ";
	$sql .= "where domain_setting_category = 'provision' ";
	$sql .= "and domain_setting_subcategory = 'http_auth_password' ";
	$sql .= "and domain_setting_name = 'text' ";
	$db->exec($sql);
	unset($sql);

}

?>
