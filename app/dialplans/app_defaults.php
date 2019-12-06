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
	Portions created by the Initial Developer are Copyright (C) 2008-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//get the $apps array from the installed apps from the core and mod directories
	if ($domains_processed == 1) {

		//get the list of domains
			$sql = "select * from v_domains ";
			$database = new database;
			$domains = $database->select($sql, null, 'all');
			unset($sql);

		//dialplan class
			$dialplan = new dialplan;
			$dialplan->import($domains);

		//update the dialplan order
			$database = new database;
			$sql = "update v_dialplans set dialplan_order = '870' where dialplan_order = '980' and dialplan_name = 'cidlookup';\n";
			$database->execute($sql);
			$sql = "update v_dialplans set dialplan_order = '880' where dialplan_order = '990' and dialplan_name = 'call_screen';\n";
			$database->execute($sql);
			$sql = "update v_dialplans set dialplan_order = '890' where dialplan_order = '999' and dialplan_name = 'local_extension';\n";
			$database->execute($sql);
			unset($sql);

		//set empty strings to null
			$database = new database;
			$sql = "update v_device_lines set outbound_proxy_primary = null where outbound_proxy_primary = '';\n";
			$database->execute($sql);
			$sql = "update v_device_lines set outbound_proxy_secondary = null where outbound_proxy_secondary = '';\n";
			$database->execute($sql);
			unset($sql);

		//change recording_slots to recording_id
			$database = new database;
			$sql = "update v_dialplan_details set dialplan_detail_data = 'recording_id=true' ";
			$sql .= "where dialplan_uuid in (select dialplan_uuid from v_dialplans where app_uuid = '430737df-5385-42d1-b933-22600d3fb79e') ";
			$sql .= "and dialplan_detail_data = 'recording_slots=true'; \n";
			$database->execute($sql);
			$sql = "update v_dialplan_details set dialplan_detail_data = 'recording_id=false' ";
			$sql .= "where dialplan_uuid in (select dialplan_uuid from v_dialplans where app_uuid = '430737df-5385-42d1-b933-22600d3fb79e') ";
			$sql .= "and dialplan_detail_data = 'recording_slots=false'; \n";
			$database->execute($sql);
			unset($sql);
	}

//add xml for each dialplan where the dialplan xml is empty
	if ($domains_processed == 1) {
		$sql = "select domain_name ";
		$sql .= "from v_domains \n";
		$database = new database;
		$results = $database->select($sql, null, 'all');
		if (is_array($results) && @sizeof($results) != 0) {
			foreach ($results as $row) {
				$dialplans = new dialplan;
				$dialplans->source = "details";
				$dialplans->destination = "database";
				$dialplans->context = $row["domain_name"];
				$dialplans->is_empty = "dialplan_xml";
				$array = $dialplans->xml();
			}
		}
		unset($sql, $results);
		$dialplans = new dialplan;
		$dialplans->source = "details";
		$dialplans->destination = "database";
		$dialplans->is_empty = "dialplan_xml";
		$array = $dialplans->xml();
	}

//delete the follow me bridge dialplan
	if ($domains_processed == 1) {
		$database = new database;
		$sql = "delete from v_dialplan_details where dialplan_uuid = '8ed73d1f-698f-466c-8a7a-1cf4cd229f7f' ";
		$database->execute($sql);
		$sql = "delete from v_dialplans where dialplan_uuid = '8ed73d1f-698f-466c-8a7a-1cf4cd229f7f' ";
		$database->execute($sql);
		unset($sql);
	}

//add not found dialplan to inbound routes
	/*
	if ($domains_processed == 1) {
		if (is_readable($_SESSION['switch']['dialplan']['dir'])) {
			$sql = "select count(*) from v_dialplans ";
			$sql .= "where dialplan_uuid = 'ea5339de-1982-46ca-9695-c35176165314' ";
			$database = new database;
			$num_rows = $database->select($sql, null, 'column');
			if ($num_rows == 0) {
				$array['dialplans'][0]['dialplan_uuid'] = 'ea5339de-1982-46ca-9695-c35176165314';
				$array['dialplans'][0]['app_uuid'] = 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4';
				$array['dialplans'][0]['dialplan_context'] = 'public';
				$array['dialplans'][0]['dialplan_name'] = 'not-found';
				$array['dialplans'][0]['dialplan_continue'] = 'false';
				$array['dialplans'][0]['dialplan_order'] = '999';
				$array['dialplans'][0]['dialplan_enabled'] = 'false';

				$array['dialplan_details'][0]['dialplan_uuid'] = 'ea5339de-1982-46ca-9695-c35176165314';
				$array['dialplan_details'][0]['dialplan_detail_uuid'] = '8a21744d-b381-4cb0-9930-55b776e4e461';
				$array['dialplan_details'][0]['dialplan_detail_tag'] = 'condition';
				$array['dialplan_details'][0]['dialplan_detail_type'] = 'context';
				$array['dialplan_details'][0]['dialplan_detail_data'] = 'public';
				$array['dialplan_details'][0]['dialplan_detail_order'] = '10';

				$array['dialplan_details'][1]['dialplan_uuid'] = 'ea5339de-1982-46ca-9695-c35176165314';
				$array['dialplan_details'][1]['dialplan_detail_uuid'] = 'e391530c-4078-4b49-bc11-bda4a23ad566';
				$array['dialplan_details'][1]['dialplan_detail_tag'] = 'action';
				$array['dialplan_details'][1]['dialplan_detail_type'] = 'log';
				$array['dialplan_details'][1]['dialplan_detail_data'] = 'WARNING [inbound routes] 404 not found \${sip_network_ip}';
				$array['dialplan_details'][1]['dialplan_detail_order'] = '20';

				$p = new permissions;
				$p->add('dialplan_add', 'temp');
				$p->add('dialplan_detail_add', 'temp');

				$database = new database;
				$database->app_name = 'dialplans';
				$database->app_uuid = '742714e5-8cdf-32fd-462c-cbe7e3d655db';
				$database->save($array);
				unset($array);

				$p->delete('dialplan_add', 'temp');
				$p->delete('dialplan_detail_add', 'temp');
			}
			unset($sql, $num_rows);
		}
	}
	*/

?>
