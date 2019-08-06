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
	Portions created by the Initial Developer are Copyright (C) 2008-2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('dialplan_delete')
		|| permission_exists('inbound_route_delete')
		|| permission_exists('outbound_route_delete')
		|| permission_exists('fifo_delete')
		|| permission_exists('time_condition_delete')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the dialplan uuid
	$dialplan_uuids = $_REQUEST["id"];
	$app_uuid = $_REQUEST['app_uuid'];

//delete the dialplans
	if (is_array($dialplan_uuids) && @sizeof($dialplan_uuids) != 0) {

		//get dialplan contexts
			foreach ($dialplan_uuids as $dialplan_uuid) {
				//check each
					$dialplan_uuid = check_str($dialplan_uuid);

				//get the dialplan data
					$sql = "select * from v_dialplans ";
					$sql .= "where dialplan_uuid = :dialplan_uuid ";
					$parameters['dialplan_uuid'] = $dialplan_uuid;
					$database = new database;
					$result = $database->select($sql, $parameters, 'all');
					if (is_array($result) && @sizeof($result) != 0) {
						foreach ($result as &$row) {
							$database_dialplan_uuid = $row["dialplan_uuid"];
							$dialplan_contexts[] = $row["dialplan_context"];
						}
					}
					unset($sql, $parameters, $result, $row);
			}

		//delete dialplan and details
			$dialplans_deleted = 0;
			foreach ($dialplan_uuids as $index => $dialplan_uuid) {
				//child data
					$array['dialplan_details'][$index]['dialplan_uuid'] = $dialplan_uuid;
				//parent data
					$array['dialplans'][$index]['dialplan_uuid'] = $dialplan_uuid;
				//increment counter
					$dialplans_deleted++;
			}
			if (is_array($array) && @sizeof($array) != 0) {
				$p = new permissions;
				$p->add('dialplan_delete', 'temp');
				$p->add('dialplan_detail_delete', 'temp');

				$database = new database;
				$database->app_name = 'dialplans';
				$database->app_uuid = '742714e5-8cdf-32fd-462c-cbe7e3d655db';
				$database->delete($array);
				unset($array);

				$p->delete('dialplan_delete', 'temp');
				$p->delete('dialplan_detail_delete', 'temp');
			}

		//synchronize the xml config
			save_dialplan_xml();

		//strip duplicate contexts
			$dialplan_contexts = array_unique($dialplan_contexts, SORT_STRING);

		//clear the cache
			$cache = new cache;
			if (sizeof($dialplan_contexts) > 0) {
				foreach($dialplan_contexts as $dialplan_context) {
					$cache->delete("dialplan:".$dialplan_context);
				}
			}
	}

//redirect the browser
	$_SESSION["message"] = $text['message-delete'].(($dialplans_deleted > 1) ? ": ".$dialplans_deleted : null);
	header("Location: ".PROJECT_PATH."/app/dialplans/dialplans.php".(($app_uuid != '') ? "?app_uuid=".$app_uuid : null));

?>
