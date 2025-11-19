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
		$extensions = $database->select($sql, null, 'all');
		unset($sql);

		if (!empty($extensions)) {
			foreach($extensions as $row) {
				$sql = "update v_destinations ";
				$sql .= "set destination_app = :destination_app, destination_data = :destination_data ";
				$sql .= "where dialplan_uuid = :dialplan_uuid ";
				$parameters['destination_app'] = $row['destination_app'];
				$parameters['destination_data'] = $row['destination_data'];
				$parameters['dialplan_uuid'] = $row['dialplan_uuid'];
				$database->execute($sql, $parameters);
				unset($sql, $parameters);
			}
		}
		unset($extensions, $row, $array);

	//update destination_context if the type is inbound and context is empty, then use public
		$sql = "select count(destination_uuid) as count from v_destinations ";
		$sql .= "where destination_context is null ";
		$sql .= "and destination_type = 'inbound' ";
		$destination_count = $database->select($sql, null, 'column');
		if ($destination_count > 0) {
			$sql = "update v_destinations ";
			$sql .= "set destination_context = 'public' ";
			$sql .= "where destination_context is null ";
			$sql .= "and destination_type = 'inbound' ";
			$database->execute($sql, null);
			unset($sql, $parameters);
		}

	//update destinations actions
		$sql = "select * from v_destinations ";
		$sql .= "where destination_actions is null ";
		$destinations = $database->select($sql, null, 'all');
		if (is_array($destinations)) {
			//pre-set the numbers
			$row_id = 0;
			$z=0;

			//loop through the array
			foreach ($destinations as $row) {
				//prepare the actions array
				if (isset($row['destination_app']) && !empty($row['destination_data'])) {
					$actions[0]['destination_app'] = $row['destination_app'];
					$actions[0]['destination_data'] = $row['destination_data'];
				}
				if (isset($row['destination_alternate_data']) && !empty($row['destination_alternate_data'])) {
					$actions[1]['destination_app'] = $row['destination_alternate_app'];
					$actions[1]['destination_data'] = $row['destination_alternate_data'];
				}

				//build the array of destinations
				if (!empty($actions)) {
					$array['destinations'][$z]['destination_uuid'] = $row['destination_uuid'];
					$array['destinations'][$z]['destination_actions'] = json_encode($actions);
					$z++;
				}

				//process a chunk of the array
				if ($row_id === 1000) {
					//save to the data
					if (!empty($array)) {
						//add temporary permissions
						$p = permissions::new();
						$p->add('destination_edit', 'temp');

						//create the database object and save the data
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

			if (!empty($array)) {
				//add temporary permissions
				$p = permissions::new();
				$p->add('destination_edit', 'temp');

				//create the database object and save the data
				$database->save($array, false);
				unset($array);

				//remove the temporary permissions
				$p->delete('destination_edit', 'temp');
			}
		}
		unset($sql, $num_rows);

	//synchronize the destination actions with destination_app and destination_data
		$sql = "select * from v_destinations ";
		$sql .= "where destination_actions is not null ";
		$destinations = $database->select($sql, null, 'all');
		if (is_array($destinations)) {
			//pre-set the numbers
			$row_id = 0;
			$z=0;

			//loop through the array
			foreach ($destinations as $row) {
				$i = 0;
				if (!empty(json_decode($row['destination_actions'], true))) {
					foreach (json_decode($row['destination_actions'], true) as $action) {
						//build the array of destinations
						if ($i == 0 ) {
							$destination_action = $action['destination_app'] . ' ' . $action['destination_data'];
							if ($destination_action !== $row['destination_app'] . ' ' . $row['destination_data']) {
								$array['destinations'][$z]['destination_uuid'] = $row['destination_uuid'];
								$array['destinations'][$z]['destination_number'] = $row['destination_number'];
								$array['destinations'][$z]['destination_app'] = $action['destination_app'];
								$array['destinations'][$z]['destination_data'] = $action['destination_data'];
								$z++;
							}
						}
						if ($i == 1) {
							$destination_action = $action['destination_app'] . ' ' . $action['destination_data'];
							if ($destination_action !== $row['destination_alternate_app'] . ' ' . $row['destination_alternate_data']) {
								$array['destinations'][$z]['destination_uuid'] = $row['destination_uuid'];
								$array['destinations'][$z]['destination_number'] = $row['destination_number'];
								$array['destinations'][$z]['destination_alternate_app'] = $action['destination_app'];
								$array['destinations'][$z]['destination_alternate_data'] = $action['destination_data'];
								$z++;
							}
						}

						//increment the id
						$i++;
					}
				}

				//process a chunk of the array
				if ($row_id === 1000) {
					//save to the data
					if (!empty($array)) {

						//add temporary permissions
						$p = permissions::new();
						$p->add('destination_edit', 'temp');

						//create the database object and save the data
						//$database->save($array, false);
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

			if (!empty($array)) {
				//add temporary permissions
				$p = permissions::new();
				$p->add('destination_edit', 'temp');

				//create the database object and save the data
				$database->save($array, false);
				unset($array);

				//remove the temporary permissions
				$p->delete('destination_edit', 'temp');
			}
		}
		unset($sql, $num_rows);

}

?>
