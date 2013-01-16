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
require_once "includes/require.php";
require_once "includes/checkauth.php";
require_once "includes/paging.php";
if (permission_exists('dialplan_add') 
	|| permission_exists('inbound_route_add') 
	|| permission_exists('outbound_route_add') 
	|| permission_exists('time_conditions_add')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//set the http get/post variable(s) to a php variable
	if (isset($_REQUEST["id"])) {
		$dialplan_uuid = check_str($_REQUEST["id"]);
	}

//get the dialplan data 
	$dialplan_uuid = $_GET["id"];
	$sql = "select * from v_dialplans ";
	$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
	$sql .= "and dialplan_uuid = '$dialplan_uuid' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		$database_dialplan_uuid = $row["dialplan_uuid"];
		$app_uuid = $row["app_uuid"];
		$dialplan_name = $row["dialplan_name"];
		$dialplan_order = $row["dialplan_order"];
		$dialplan_continue = $row["dialplan_continue"];
		$dialplan_context = $row["dialplan_context"];
		$dialplan_enabled = $row["dialplan_enabled"];
		$dialplan_description = "copy: ".$row["dialplan_description"];
		break; //limit to 1 row
	}
	unset ($prep_statement);

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
		$sql .= "'".$_SESSION['domain_uuid']."', ";
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
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and dialplan_uuid = '$database_dialplan_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$dialplan_detail_tag = $row["dialplan_detail_tag"];
			$dialplan_detail_order = $row["dialplan_detail_order"];
			$dialplan_detail_type = $row["dialplan_detail_type"];
			$dialplan_detail_data = $row["dialplan_detail_data"];

			//copy the dialplan details
				$dialplan_detail_uuid = uuid();
				$sql = "insert into v_dialplan_details ";
				$sql .= "(";
				$sql .= "domain_uuid, ";
				$sql .= "dialplan_uuid, ";
				$sql .= "dialplan_detail_uuid, ";
				$sql .= "dialplan_detail_tag, ";
				$sql .= "dialplan_detail_order, ";
				$sql .= "dialplan_detail_type, ";
				$sql .= "dialplan_detail_data ";
				$sql .= ")";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'".$_SESSION['domain_uuid']."', ";
				$sql .= "'".check_str($dialplan_uuid)."', ";
				$sql .= "'".check_str($dialplan_detail_uuid)."', ";
				$sql .= "'".check_str($dialplan_detail_tag)."', ";
				$sql .= "'".check_str($dialplan_detail_order)."', ";
				$sql .= "'".check_str($dialplan_detail_type)."', ";
				$sql .= "'".check_str($dialplan_detail_data)."' ";
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
			$switch_cmd = "memcache delete dialplan:".$_SESSION["context"]."@".$_SESSION['domain_name'];
			$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
		}

	//redirect the user
		require_once "includes/header.php";
		switch ($app_uuid) {
			case "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4":
				//inbound routes
				echo "<meta http-equiv=\"refresh\" content=\"2;url=".PROJECT_PATH."/app/dialplan/dialplans.php?app_uuid=$app_uuid\">\n";
				break;
			case "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3":
				//outbound routes
				echo "<meta http-equiv=\"refresh\" content=\"2;url=".PROJECT_PATH."/app/dialplan/dialplans.php?app_uuid=$app_uuid\">\n";
				break;
			case "4b821450-926b-175a-af93-a03c441818b1":
				//time conditions
				echo "<meta http-equiv=\"refresh\" content=\"2;url=".PROJECT_PATH."/app/dialplan/dialplans.php?app_uuid=$app_uuid\">\n";
				break;
			default:
				echo "<meta http-equiv=\"refresh\" content=\"2;url=".PROJECT_PATH."/app/dialplan/dialplans.php\">\n";
				break;
		}
		echo "<div align='center'>\n";
		echo "Copy Complete\n";
		echo "</div>\n";
		require_once "includes/footer.php";
		return;

?>