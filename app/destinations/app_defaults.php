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
	Portions created by the Initial Developer are Copyright (C) 2017 - 2022
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

if ($domains_processed == 1) {

	//update the v_destinations set destination_app and destination_data
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
		unset($extensions, $row, $array);

	//use destinations actions to
		$sql = "select * from v_destinations ";
		$sql .= "where destination_actions is null ";
		$database = new database;
		$destinations = $database->select($sql, null, 'all');
		if (is_array($destinations)) {
			//pre-set the numbers
			$row_id = 0;
			$z=0;

			//loop through the array
			foreach ($destinations as $row) {
				//prepare the actions array
				if (isset($row['destination_app']) && $row['destination_data'] != '') {
					$actions[0]['destination_app'] = $row['destination_app'];
					$actions[0]['destination_data'] = $row['destination_data'];
				}
				if (isset($row['destination_alternate_data']) && $row['destination_alternate_data'] != '') {
					$actions[1]['destination_app'] = $row['destination_alternate_app'];
					$actions[1]['destination_data'] = $row['destination_alternate_data'];
				}

				//build the array of destinations
				if (is_array($actions)) {
					$array['destinations'][$z]['destination_uuid'] = $row['destination_uuid'];
					$array['destinations'][$z]['destination_actions'] = json_encode($actions);
					$z++;
				}

				//process a chunk of the array
				if ($row_id === 1000) {
					//save to the data
					if (is_array($array)) {
						//add temporary permissions
						$p = new permissions;
						$p->add('destination_edit', 'temp');
		
						//create the database object and save the data
						$database = new database;
						$database->app_name = 'destinations';
						$database->app_uuid = '5ec89622-b19c-3559-64f0-afde802ab139';
						$database->save($array, false);
						unset($array);
		
						//remove the temporary permissions
						$p->delete('destination_edit', 'temp');
					}

					//set the row id back to 0
					$row_id = 0;		
				}

				//increment the number
				$row_id++;

				//unset actions
				unset($actions);
			}

			if (is_array($array)) {
				//add temporary permissions
				$p = new permissions;
				$p->add('destination_edit', 'temp');

				//create the database object and save the data
				$database = new database;
				$database->app_name = 'destinations';
				$database->app_uuid = '5ec89622-b19c-3559-64f0-afde802ab139';
				$database->save($array, false);
				unset($array);

				//remove the temporary permissions
				$p->delete('destination_edit', 'temp');
			}
		}
		unset($sql, $num_rows);

}

?>
