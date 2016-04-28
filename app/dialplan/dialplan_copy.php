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
require_once "resources/paging.php";
require_once "resources/classes/logging.php";
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
	if (isset($_REQUEST["id"])) {
		$dialplan_uuid = check_str($_REQUEST["id"]);
		$log->log("debug", "isset id.");
		$log->log("debug", $dialplan_uuid);
	}

//get the dialplan data
	$dialplan_uuid = $_GET["id"];
	$sql = "select * from v_dialplans ";
	$sql .= "where dialplan_uuid = '$dialplan_uuid' ";
	$log->log("debug", check_sql($sql));
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$log->log("debug", $result);
	foreach ($result as &$row) {
		$domain_uuid = check_str($row["domain_uuid"]);
		$database_dialplan_uuid = check_str($row["dialplan_uuid"]);
		$app_uuid = check_str($row["app_uuid"]);
		$dialplan_name = check_str($row["dialplan_name"]);
		$dialplan_order = check_str($row["dialplan_order"]);
		$dialplan_continue = check_str($row["dialplan_continue"]);
		$dialplan_context = check_str($row["dialplan_context"]);
		$dialplan_enabled = check_str($row["dialplan_enabled"]);
		$dialplan_description = check_str("copy: ".$row["dialplan_description"]);
		break; //limit to 1 row
	}
	unset ($prep_statement);

//create a new app_uuid when copying a dialplan except for these exceptions
	switch ($app_uuid) {
		case "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4": break; //inbound routes
		case "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3": break; //outbound routes
		case "4b821450-926b-175a-af93-a03c441818b1": break; //time conditions
		default:
			$app_uuid = uuid();
	}

//copy the dialplan
	$dialplan_uuid = uuid();
	$sql = "insert into v_dialplans ";
	$sql .= "(";
	$sql .= "domain_uuid, ";
	$sql .= "dialplan_uuid, ";
	$sql .= "app_uuid, ";
	$sql .= "dialplan_name, ";
	$sql .= "dialplan_order, ";
	$sql .= "dialplan_continue, ";
	$sql .= "dialplan_context, ";
	$sql .= "dialplan_enabled, ";
	$sql .= "dialplan_description ";
	$sql .= ")";
	$sql .= "values ";
	$sql .= "(";
	$sql .= "'".$domain_uuid."', ";
	$sql .= "'$dialplan_uuid', ";
	$sql .= "'$app_uuid', ";
	$sql .= "'".$dialplan_name."-copy', ";
	$sql .= "'$dialplan_order', ";
	$sql .= "'$dialplan_continue', ";
	$sql .= "'$dialplan_context', ";
	$sql .= "'$dialplan_enabled', ";
	$sql .= "'$dialplan_description' ";
	$sql .= ")";
	$db->exec(check_sql($sql));
	unset($sql);

//get the the dialplan details
	$sql = "select * from v_dialplan_details ";
	$sql .= "where dialplan_uuid = '$database_dialplan_uuid' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		//set the variables
			$domain_uuid = $row["domain_uuid"];
			$dialplan_detail_tag = $row["dialplan_detail_tag"];
			$dialplan_detail_order = $row["dialplan_detail_order"];
			$dialplan_detail_type = $row["dialplan_detail_type"];
			$dialplan_detail_data = $row["dialplan_detail_data"];
			$dialplan_detail_break = $row["dialplan_detail_break"];
			$dialplan_detail_inline = $row["dialplan_detail_inline"];
			$dialplan_detail_group = ($row["dialplan_detail_group"] != '') ? $row["dialplan_detail_group"] : '0';

		//copy the dialplan details
			$dialplan_detail_uuid = uuid();
			$sql = "insert into v_dialplan_details ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "dialplan_uuid, ";
			$sql .= "dialplan_detail_uuid, ";
			$sql .= "dialplan_detail_tag, ";
			$sql .= "dialplan_detail_type, ";
			$sql .= "dialplan_detail_data, ";
			$sql .= "dialplan_detail_break, ";
			$sql .= "dialplan_detail_inline, ";
			$sql .= "dialplan_detail_group, ";
			$sql .= "dialplan_detail_order ";
			$sql .= ")";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'".$domain_uuid."', ";
			$sql .= "'".$dialplan_uuid."', ";
			$sql .= "'".$dialplan_detail_uuid."', ";
			$sql .= "'".check_str($dialplan_detail_tag)."', ";
			$sql .= "'".check_str($dialplan_detail_type)."', ";
			$sql .= "'".check_str($dialplan_detail_data)."', ";
			$sql .= "'".$dialplan_detail_break."', ";
			$sql .= "'".$dialplan_detail_inline."', ";
			$sql .= "'".check_str($dialplan_detail_group)."', ";
			$sql .= "'".check_str($dialplan_detail_order)."' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);
	}
	unset ($prep_statement);

//synchronize the xml config
	save_dialplan_xml();

//delete the dialplan context from memcache
	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
	if ($fp) {
		$switch_cmd = "memcache delete dialplan:".$dialplan_context;
		$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
	}

//send a redirect
	$_SESSION["message"] = $text['message-copy'];
	switch ($app_uuid) {
		case "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4": //inbound routes
		case "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3": //outbound routes
		case "4b821450-926b-175a-af93-a03c441818b1": //time conditions
			$redirect_url = PROJECT_PATH."/app/dialplan/dialplans.php?app_uuid=".$app_uuid;
			break;
		default:
			$redirect_url = PROJECT_PATH."/app/dialplan/dialplans.php";
	}
	header("Location: ".$redirect_url);
	return;

?>