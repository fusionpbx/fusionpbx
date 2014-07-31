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
	Portions created by the Initial Developer are Copyright (C) 2008-2012
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
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
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

//set the dialplan uuid
	$dialplan_uuids = $_REQUEST["id"];
	$app_uuid = check_str($_REQUEST['app_uuid']);

if (sizeof($dialplan_uuids) > 0) {

	//get dialplan contexts
	foreach ($dialplan_uuids as $dialplan_uuid) {

		//check each
			$dialplan_uuid = check_str($dialplan_uuid);

		//get the dialplan data
			$sql = "select * from v_dialplans ";
			$sql .= "where dialplan_uuid = '".$dialplan_uuid."' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			foreach ($result as &$row) {
				$database_dialplan_uuid = $row["dialplan_uuid"];
				$dialplan_contexts[] = $row["dialplan_context"];
			}
			unset($prep_statement);
	}

	//start the atomic transaction
		$db->beginTransaction();

	//delete dialplan and details
	$dialplans_deleted = 0;
	foreach ($dialplan_uuids as $dialplan_uuid) {

		//delete child data
			$sql = "delete from v_dialplan_details ";
			$sql .= "where dialplan_uuid = '".$dialplan_uuid."'; ";
			$db->query($sql);
			unset($sql);

		//delete parent data
			$sql = "delete from v_dialplans ";
			$sql .= "where dialplan_uuid = '".$dialplan_uuid."'; ";
			$db->query($sql);
			unset($sql);

		$dialplans_deleted++;
	}

	//commit the atomic transaction
		$db->commit();

	//synchronize the xml config
		save_dialplan_xml();

	//strip duplicate contexts
	$dialplan_contexts = array_unique($dialplan_contexts, SORT_STRING);

	//delete the dialplan contexts from memcache
	if (sizeof($dialplan_contexts) > 0) {
		$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
		if ($fp) {
			foreach($dialplan_contexts as $dialplan_context) {
				$switch_cmd = "memcache delete dialplan:".$dialplan_context;
				$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
			}
		}
	}
}


$_SESSION["message"] = $text['message-delete'].(($dialplans_deleted > 1) ? ": ".$dialplans_deleted : null);
header("Location: ".PROJECT_PATH."/app/dialplan/dialplans.php".(($app_uuid != '') ? "?app_uuid=".$app_uuid : null));
?>