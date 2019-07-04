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
//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('call_flow_delete')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//delete the user data
	if (is_uuid($_GET["id"])) {

		$call_flow_uuid = $_GET["id"];

		//get the dialplan uuid
			$sql = "select * from v_call_flows ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$sql .= "and call_flow_uuid = :call_flow_uuid ";
			$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
			$parameters['call_flow_uuid'] = $call_flow_uuid;
			$database = new database;
			$row = $database->select($sql, $parameters, 'row');
			if (is_array($row) && sizeof($row) != 0) {
				$dialplan_uuid = $row['dialplan_uuid'];
				$call_flow_context = $row['call_flow_context'];
			}
			unset($sql, $parameters, $row);

		//delete call_flow
			$array['call_flows'][0]['call_flow_uuid'] = $call_flow_uuid;
			$array['call_flows'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
			$database = new database;
			$database->app_name = 'call_flows';
			$database->app_uuid = 'b1b70f85-6b42-429b-8c5a-60c8b02b7d14';
			$database->delete($array);
			unset($array);

		//delete the dialplan entry
			$p = new permissions;
			$p->add('dialplan_delete', 'temp');

			$array['dialplans'][0]['dialplan_uuid'] = $dialplan_uuid;
			$array['dialplans'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
			$database = new database;
			$database->app_name = 'call_flows';
			$database->app_uuid = 'b1b70f85-6b42-429b-8c5a-60c8b02b7d14';
			$database->delete($array);
			unset($array);

			$p->delete('dialplan_delete', 'temp');

		//delete the dialplan details
			$p = new permissions;
			$p->add('dialplan_detail_delete', 'temp');

			$array['dialplans'][0]['dialplan_uuid'] = $dialplan_uuid;
			$array['dialplans'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
			$database = new database;
			$database->app_name = 'call_flows';
			$database->app_uuid = 'b1b70f85-6b42-429b-8c5a-60c8b02b7d14';
			$database->delete($array);
			unset($array);

			$p->delete('dialplan_detail_delete', 'temp');

		//syncrhonize configuration
			save_dialplan_xml();

		//apply settings reminder
			$_SESSION["reload_xml"] = true;

		//clear the cache
			$cache = new cache;
			$cache->delete("dialplan:".$call_flow_context);

		//set message
			message::add($text['message-delete']);
	}

//redirect the browser
	header("Location: call_flows.php");
	return;

?>
