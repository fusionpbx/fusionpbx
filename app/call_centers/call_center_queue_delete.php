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
	Portions created by the Initial Developer are Copyright (C) 2008-2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('call_center_queue_delete')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//delete the data
	if (is_uuid($_GET["id"])) {
		$call_center_queue_uuid = $_GET["id"];

		//get the dialplan uuid
			$sql = "select * from v_call_center_queues ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$sql .= "and call_center_queue_uuid = :call_center_queue_uuid ";
			$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
			$parameters['call_center_queue_uuid'] = $call_center_queue_uuid;
			$database = new database;
			$row = $database->select($sql, $parameters, 'row');
			if (is_array($row) && sizeof($row) != 0) {
				$queue_name = $row['queue_name'];
				$dialplan_uuid = $row['dialplan_uuid'];
			}
			unset($sql, $parameters, $row);

		//delete the tier from the database
			$array['call_center_tiers'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
			$array['call_center_tiers'][0]['call_center_queue_uuid'] = $call_center_queue_uuid;
			$array['call_center_tiers'][1]['domain_uuid'] = $_SESSION['domain_uuid'];
			$array['call_center_tiers'][1]['queue_name'] = $queue_name."@".$_SESSION['domain_name'];

		//delete the call center queue
			$array['call_center_queues'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
			$array['call_center_queues'][0]['call_center_queue_uuid'] = $call_center_queue_uuid;

		//delete the dialplan entry
			$array['dialplans'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
			$array['dialplans'][0]['dialplan_uuid'] = $dialplan_uuid;

		//delete the dialplan details
			$array['dialplan_details'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
			$array['dialplan_details'][0]['dialplan_uuid'] = $dialplan_uuid;

		//execute
			$p = new permissions;
			$p->add('call_center_tier_delete', 'temp');
			$p->add('dialplan_delete', 'temp');
			$p->add('dialplan_detail_delete', 'temp');

			$database = new database;
			$database->app_name = 'call_centers';
			$database->app_uuid = '95788e50-9500-079e-2807-fd530b0ea370';
			$database->delete($array);
			$response = $database->message;
			unset($array);

			$p->delete('call_center_tier_delete', 'temp');
			$p->delete('dialplan_delete', 'temp');
			$p->delete('dialplan_detail_delete', 'temp');

		//clear the cache
			$cache = new cache;
			$cache->delete("dialplan:".$_SESSION["context"]);
			remove_config_from_cache('configuration:callcenter.conf');

		//synchronize configuration
			save_dialplan_xml();
			save_call_center_xml();

		//apply settings reminder
			$_SESSION["reload_xml"] = true;

		//set message
			message::add($text['message-delete']);
	}

//redirect the browser
	header("Location: call_center_queues.php");
	return;

?>
