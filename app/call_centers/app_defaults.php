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
	Portions created by the Initial Developer are Copyright (C) 2018 - 2022
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

if ($domains_processed == 1) {

	//list the missing call center queue and agent uuids
		$sql = "select t.call_center_tier_uuid, t.call_center_queue_uuid, t.call_center_agent_uuid, t.queue_name, t.agent_name, d.domain_name, ";
		$sql .= "(select call_center_queue_uuid from v_call_center_queues where replace(queue_name, ' ', '-') = t.queue_name and domain_uuid = t.domain_uuid) as queue_uuid, ";
		$sql .= "(select call_center_agent_uuid from v_call_center_agents where agent_name = t.agent_name and domain_uuid = t.domain_uuid) as agent_uuid ";
		$sql .= "from v_call_center_tiers as t, v_domains as d ";
		$sql .= "where t.domain_uuid = d.domain_uuid ";
		$sql .= "and (t.call_center_queue_uuid is null or t.call_center_agent_uuid is null) ";
		$database = new database;
		$tiers = $database->select($sql, null, 'all');
		if (is_array($tiers) && @sizeof($tiers) != 0) {
			foreach ($tiers as $index => &$row) {
				if ($row['call_center_queue_uuid'] == null && $row['queue_uuid'] != null) {
					$array['call_center_tiers'][$index]['call_center_queue_uuid'] = $row['queue_uuid'];
				}
				if ($row['call_center_agent_uuid'] == null && $row['agent_uuid'] != null) {
					$array['call_center_tiers'][$index]['call_center_agent_uuid'] = $row['agent_uuid'];
				}
				if (is_array($array['call_center_tiers'][$index]) && @sizeof($array['call_center_tiers'][$index]) != 0) {
					$array['call_center_tiers'][$index]['call_center_tier_uuid'] = $row['call_center_tier_uuid'];
				}
			}

			if (is_array($array) && @sizeof($array) != 0) {
				$p = new permissions;
				$p->add('call_center_tier_edit', 'temp');

				$database = new database;
				$database->app_name = 'call_centers';
				$database->app_uuid = '95788e50-9500-079e-2807-fd530b0ea370';
				$database->save($array, false);
				$response = $database->message;
				unset($array);

				$p->delete('call_center_tier_edit', 'temp');
			}
		}
		unset($sql);

	//update all callcenter dialplans to have the @domain in the queue name
		$sql = "select q.domain_uuid, d.domain_name, q.call_center_queue_uuid, q.dialplan_uuid, dp.dialplan_xml, ";
		$sql .= "q.queue_name, q.queue_extension, q.queue_timeout_action, q.queue_cid_prefix, q.queue_cc_exit_keys, ";
		$sql .= "q.queue_description, q.queue_time_base_score_sec, q.queue_greeting ";
		$sql .= "from v_call_center_queues as q, v_dialplans as dp, v_domains as d ";
		$sql .= "where q.domain_uuid = d.domain_uuid ";
		$sql .= "and (q.dialplan_uuid = dp.dialplan_uuid or q.dialplan_uuid is null) ";
		$database = new database;
		$call_center_queues = $database->select($sql, null, 'all');
		$id = 0;
		if (is_array($call_center_queues)) {
			foreach ($call_center_queues as $row) {

				//get the application and data
					$action_array = explode(":",$row['queue_timeout_action']);
					$queue_timeout_app = $action_array[0];
					unset($action_array[0]);
					$queue_timeout_data = implode($action_array);

				//add the recording path if needed
					if ($row['queue_greeting'] != '') {
						if (file_exists($_SESSION['switch']['recordings']['dir'].'/'.$row['domain_name'].'/'.$row['queue_greeting'])) {
							$queue_greeting_path = $_SESSION['switch']['recordings']['dir'].'/'.$row['domain_name'].'/'.$row['queue_greeting'];
						}
						else {
							$queue_greeting_path = trim($row['queue_greeting']);
						}
					}

				//build the xml dialplan
					$dialplan_xml = "<extension name=\"".$row["queue_name"]."\" continue=\"\" uuid=\"".$row["dialplan_uuid"]."\">\n";
					$dialplan_xml .= "	<condition field=\"destination_number\" expression=\"^([^#]+#)(.*)\$\" break=\"never\">\n";
					$dialplan_xml .= "		<action application=\"set\" data=\"caller_id_name=\$2\"/>\n";
					$dialplan_xml .= "	</condition>\n";
					$dialplan_xml .= "	<condition field=\"destination_number\" expression=\"^(callcenter\+)?".$row["queue_extension"]."$\">\n";
					$dialplan_xml .= "		<action application=\"answer\" data=\"\"/>\n";
					if (is_uuid($row['call_center_queue_uuid'])) {
						$dialplan_xml .= "		<action application=\"set\" data=\"call_center_queue_uuid=".$row['call_center_queue_uuid']."\"/>\n";
					}
					if (is_numeric($row['queue_extension'])) {
						$dialplan_xml .= "		<action application=\"set\" data=\"queue_extension=".$row['queue_extension']."\"/>\n";
					}
					$dialplan_xml .= "		<action application=\"set\" data=\"cc_export_vars=\${cc_export_vars},call_center_queue_uuid,sip_h_Alert-Info\"/>\n";
					$dialplan_xml .= "		<action application=\"set\" data=\"hangup_after_bridge=true\"/>\n";
					if ($row['queue_time_base_score_sec'] != '') {
						$dialplan_xml .= "		<action application=\"set\" data=\"cc_base_score=".$row['queue_time_base_score_sec']."\"/>\n";
					}
					if ($row['queue_greeting'] != '') {
						$greeting_array = explode(':', $row['queue_greeting']);
						if (count($greeting_array) == 1) {
							$dialplan_xml .= "		<action application=\"playback\" data=\"".$queue_greeting_path."\"/>\n";
						}
						else {
							if ($greeting_array[0] == 'say' || $greeting_array[0] == 'tone_stream' || $greeting_array[0] == 'phrase') {
								$dialplan_xml .= "		<action application=\"".$greeting_array[0]."\" data=\"".$greeting_array[1]."\"/>\n";
							}
						}
					}
					if (strlen($row['queue_cid_prefix']) > 0) {
						$dialplan_xml .= "		<action application=\"set\" data=\"effective_caller_id_name=".$row['queue_cid_prefix']."#\${caller_id_name}\"/>\n";
					}
					if (strlen($row['queue_cc_exit_keys']) > 0) {
						$dialplan_xml .= "		<action application=\"set\" data=\"cc_exit_keys=".$row['queue_cc_exit_keys']."\"/>\n";
					}
					$dialplan_xml .= "		<action application=\"callcenter\" data=\"".$row['queue_extension']."@".$row['domain_name']."\"/>\n";
					//if ($destination->valid($queue_timeout_app.':'.$queue_timeout_data)) {
						$dialplan_xml .= "		<action application=\"".$queue_timeout_app."\" data=\"".$queue_timeout_data."\"/>\n";
					//}
					$dialplan_xml .= "	</condition>\n";
					$dialplan_xml .= "</extension>";

				//build the dialplan array
					if (md5($row["dialplan_xml"]) != md5($dialplan_xml)) {
						$array['dialplans'][$id]["domain_uuid"] = $row["domain_uuid"];
						$array['dialplans'][$id]["dialplan_uuid"] = $row["dialplan_uuid"];
						$array['dialplans'][$id]["dialplan_name"] = $row["queue_name"];
						$array['dialplans'][$id]["dialplan_number"] = $row["queue_extension"];
						$array['dialplans'][$id]["dialplan_context"] = $row['domain_name'];
						$array['dialplans'][$id]["dialplan_continue"] = "false";
						$array['dialplans'][$id]["dialplan_xml"] = $dialplan_xml;
						$array['dialplans'][$id]["dialplan_order"] = "230";
						$array['dialplans'][$id]["dialplan_enabled"] = "true";
						$array['dialplans'][$id]["dialplan_description"] = $row["queue_description"];
						$array['dialplans'][$id]["app_uuid"] = "95788e50-9500-079e-2807-fd530b0ea370";
					}

				//increment the array id
					$id++;

			}
		}
		unset ($prep_statement);

	//save the array to the database
		if (is_array($array)) {
			//add the dialplan permission
				$p = new permissions;
				$p->add("dialplan_add", "temp");
				$p->add("dialplan_edit", "temp");

			//save to the data
				$database = new database;
				$database->app_name = 'call_centers';
				$database->app_uuid = '95788e50-9500-079e-2807-fd530b0ea370';
				$database->save($array, false);
				$message = $database->message;

			//remove the temporary permission
				$p->delete("dialplan_add", "temp");
				$p->delete("dialplan_edit", "temp");
		}

}

?>
