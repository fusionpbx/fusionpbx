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
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$tiers = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($tiers as &$row) {
			if ($row['call_center_queue_uuid'] == null && $row['queue_uuid'] != null) {
				$sql = "update v_call_center_tiers set call_center_queue_uuid = '".$row['queue_uuid']."' ";
				$sql .= "where call_center_tier_uuid = '".$row['call_center_tier_uuid']."' ";
				$db->exec(check_sql($sql));
				unset($sql);
			}

			if ($row['call_center_agent_uuid'] == null && $row['agent_uuid'] != null) {
				$sql = "update v_call_center_tiers set call_center_agent_uuid = '".$row['agent_uuid']."' ";
				$sql .= "where call_center_tier_uuid = '".$row['call_center_tier_uuid']."' ";
				$db->exec(check_sql($sql));
				unset($sql);
			}
		}

}

?>
