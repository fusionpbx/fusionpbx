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
	Portions created by the Initial Developer are Copyright (C) 2017
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//update the v_destinations set destination_app and destination_data
	if ($domains_processed == 1) {
		$sql = "select dialplan_uuid, dialplan_detail_type as destination_app, dialplan_detail_data as destination_data\n";
		$sql .= "from v_dialplan_details\n";
		$sql .= "where dialplan_uuid in (select dialplan_uuid from v_destinations where destination_type = 'inbound' and destination_app is null and destination_data is null)\n";
		$sql .= "and dialplan_detail_tag = 'action'\n";
		$sql .= "and (dialplan_detail_type = 'transfer' or dialplan_detail_type = 'bridge')\n";
		$sql .= "order by dialplan_detail_order;\n";
		$database = new database;
		$extensions = $database->select($sql, null, 'all');
		unset($sql);

		if (is_array($extensions) && @sizeof($extensions) != 0) {
			foreach($extensions as $row) {
				$sql = "update v_destinations ";
				$sql .= "set destination_app = :destination_app, destination_data = :destination_data ";
				$sql .= "where dialplan_uuid = :dialplan_uuid ";
				$parameters['destination_app'] = $row['destination_app'];
				$parameters['destination_data'] = $row['destination_data'];
				$parameters['dialplan_uuid'] = $row['dialplan_uuid'];
				$database = new database;
				$database->execute($sql, $parameters);
				unset($sql, $parameters);
			}
		}
		unset($extensions, $row);
	}

?>
