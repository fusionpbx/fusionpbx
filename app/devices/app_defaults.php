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
	Portions created by the Initial Developer are Copyright (C) 2008-2015
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
}

?>