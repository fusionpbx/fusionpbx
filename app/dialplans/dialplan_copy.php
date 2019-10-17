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
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";
	require_once "resources/classes/logging.php";

//check permissions
	if (permission_exists('dialplan_add')
		|| permission_exists('inbound_route_add')
		|| permission_exists('outbound_route_add')
		|| permission_exists('fifo_add')
		|| permission_exists('time_condition_add')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//logger
	$log = new Logging();

//set the http get/post variable(s) to a php variable
	if (is_uuid($_REQUEST["id"])) {
		$id = $_REQUEST["id"];
		$log->log("debug", "isset id.");
		$log->log("debug", $id);
	}

//get the dialplan data
	if (is_uuid($id)) {
		$sql = "select * from v_dialplans ";
		$sql .= "where dialplan_uuid = :dialplan_uuid ";
		$parameters['dialplan_uuid'] = $id;
		$database = new database;
		$dialplans = $database->select($sql, $parameters, 'all');
		if (is_array($dialplans) && @sizeof($dialplans) != 0) {
			foreach ($dialplans as &$row) {
				//create a new primary key for the new row
					$dialplan_uuid = uuid();
					$row['dialplan_uuid'] = $dialplan_uuid;

				//get the app_uuid
					if (is_uuid($row["app_uuid"])) {
						//get the app uuid
							$app_uuid = $row["app_uuid"];
						//create a new app_uuid when copying a dialplan except for these exceptions
							switch ($app_uuid) {
								case "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4": break; //inbound routes
								case "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3": break; //outbound routes
								case "4b821450-926b-175a-af93-a03c441818b1": break; //time conditions
								default:
									$app_uuid = uuid();
							}
						//set the app uuid
							$row['app_uuid'] = $app_uuid;
					}

				//add copy to the name and description
					//$row['dialplan_name'] = $row['dialplan_name'].'-copy';
					if (strlen($row['dialplan_description']) == 0) {
						$dialplan_description = 'copy';
					}
					else {
						$dialplan_description = $row['dialplan_description'].'-copy';
					}
					$row['dialplan_description'] = $dialplan_description;
			}
		}
		unset($sql, $parameters, $row);
	}

//get the the dialplan details
	if (is_uuid($id)) {
		$sql = "select * from v_dialplan_details ";
		$sql .= "where dialplan_uuid = :dialplan_uuid ";
		$parameters['dialplan_uuid'] = $id;
		$database = new database;
		$dialplan_details = $database->select($sql, $parameters, 'all');
		if (is_array($dialplan_details) && @sizeof($dialplan_details) != 0) {
			foreach ($dialplan_details as &$row) {
				//create a new primary key for the new row
					$row['dialplan_detail_uuid'] = uuid();
				//update the foreign relation uuid
					$row['dialplan_uuid'] = $dialplan_uuid;
			}
		}
		unset($sql, $parameters);
	}

//build the array
	$array['dialplans'] = $dialplans;
	if (count($dialplan_details) > 0) {
		$array['dialplans'][0]['dialplan_details'] = $dialplan_details;
	}

//add or update the database
	$database = new database;
	$database->app_name = 'dialplans';
	$database->app_uuid = $app_uuid;
	$database->uuid($dialplan_uuid);
	$database->save($array);
	unset($array);

//update the dialplan xml
	$dialplans = new dialplan;
	$dialplans->source = "details";
	$dialplans->destination = "database";
	$dialplans->uuid = $dialplan_uuid;
	$dialplans->xml();

//clear the cache
	$cache = new cache;
	$cache->delete("dialplan:".$dialplan_context);

//synchronize the xml config
	save_dialplan_xml();

//send a redirect
	message::add($text['message-copy']);
	switch ($app_uuid) {
		case "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4": //inbound routes
		case "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3": //outbound routes
		case "4b821450-926b-175a-af93-a03c441818b1": //time conditions
			$redirect_url = PROJECT_PATH."/app/dialplans/dialplans.php?app_uuid=".$app_uuid;
			break;
		default:
			$redirect_url = PROJECT_PATH."/app/dialplans/dialplans.php";
	}
	header("Location: ".$redirect_url);
	return;

?>
