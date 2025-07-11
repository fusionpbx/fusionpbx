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
	Portions created by the Initial Developer are Copyright (C) 2019 - 2023
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//process this only one time
if ($domains_processed == 1) {

	//select ring groups with an empty context
	$sql = "select * from v_ring_groups ";
	$sql .= "where ring_group_context is null ";
	$database = new database;
	$ring_groups = $database->select($sql, null, 'all');
	if (is_array($ring_groups) && @sizeof($ring_groups) != 0) {
		//get the domain list
		$sql = "select * from v_domains ";
		$domains = $database->select($sql, null, 'all');

		//update the ring group context
		$x = 0;
		foreach ($ring_groups as $row) {
			foreach ($domains as $domain) {
				if ($row['domain_uuid'] == $domain['domain_uuid']) {
					$array['ring_groups'][$x]['ring_group_uuid'] = $row['ring_group_uuid'];
					$array['ring_groups'][$x]['ring_group_context'] = $domain['domain_name'];
					$x++;
				}
			}
		}
		if (is_array($array) && @sizeof($array) != 0) {
			//grant temporary permissions
				$p = new permissions;
				$p->add('ring_group_edit', 'temp');
			//execute update
				$database = new database;
				$database->app_name = 'ring_groups';
				$database->app_uuid = '1d61fb65-1eec-bc73-a6ee-a6203b4fe6f2';
				$database->save($array, false);
				unset($array);
			//revoke temporary permissions
				$p->delete('ring_group_edit', 'temp');
		}
	}

	//enable ring group destinations by default 
	$sql = "update v_ring_group_destinations ";
	$sql .= "set destination_enabled = true ";
	$sql .= "where destination_enabled is null; ";
	$database = new database;
	$database->execute($sql, null);
	unset($sql);

}

?>
