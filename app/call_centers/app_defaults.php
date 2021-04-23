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
	Portions created by the Initial Developer are Copyright (C) 2018
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
				$database->save($array);
				$response = $database->message;
				unset($array);

				$p->delete('call_center_tier_edit', 'temp');
			}
		}
		unset($sql);

		//update all callcenter dialplans to have the @domain in the queue name
			$sql = "UPDATE \n";
			$sql .= "   v_dialplans d SET dialplan_xml = REPLACE( \n";
			$sql .= "        dialplan_xml, \n";
			$sql .= "        (SELECT call_center_queue_uuid::text FROM v_call_center_queues c WHERE c.dialplan_uuid = d.dialplan_uuid ), \n";
			$sql .= "        (SELECT call_center_queue_uuid::text FROM v_call_center_queues c WHERE c.dialplan_uuid = d.dialplan_uuid ) || '@' || (SELECT domain_name  FROM v_domains vd WHERE vd.domain_uuid = d.domain_uuid) \n";
			$sql .= "    ) \n";
			$sql .= "WHERE dialplan_uuid IN (SELECT dialplan_uuid FROM v_call_center_queues) \n";
			$sql .= "AND dialplan_xml NOT LIKE '%<action application=\"callcenter\" data=%@%\"/>%' \n";
			$database = new database;
			$database->execute($sql);

}

?>
