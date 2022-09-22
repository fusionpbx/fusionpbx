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
	Portions created by the Initial Developer are Copyright (C) 2008-2020
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

if ($domains_processed == 1) {

	//unset array if it exists
		if (isset($array)) { unset($array); }

	//update the software table
		$sql = "select software_version from v_software ";
		$database = new database;
		$software_version = $database->select($sql, null, 'column');
		if ($software_version == '') {
			$array['software'][0]['software_uuid'] = '7de057e7-333b-4ebf-9466-315ae7d44efd';
			$array['software'][0]['software_name'] = 'FusionPBX';
			$array['software'][0]['software_url'] = 'https://www.fusionpbx.com';
			$array['software'][0]['software_version'] = software::version();
		}
		elseif ($software_version != software::version()) {
			$array['software'][0]['software_uuid'] = '7de057e7-333b-4ebf-9466-315ae7d44efd';
			$array['software'][0]['software_version'] = software::version();
		}

	//save the data in the array
		if (is_array($array) && count($array) > 0) {
			//add the temporary permission
			$p = new permissions;
			$p->add("software_add", 'temp');
			$p->add("software_edit", 'temp');

			//save the data
			$database = new database;
			$database->app_name = 'software';
			$database->app_uuid = 'b88c795f-7dea-4fc8-9ab7-edd555242cff';
			$database->save($array, false);
			unset($array);

			//remove the temporary permission
			$p->delete("software_add", 'temp');
			$p->delete("software_edit", 'temp');
		}

}

?>
