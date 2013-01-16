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
if (permission_exists('dialplan_delete') 
	|| permission_exists('inbound_route_delete')
	|| permission_exists('outbound_route_delete')
	|| permission_exists('time_conditions_delete')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

if (count($_GET)>0) {
    $dialplan_uuid = check_str($_GET["id"]);
}

if (strlen($dialplan_uuid)>0) {
	//get the dialplan data
		$sql = "select * from v_dialplans ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and dialplan_uuid = '$dialplan_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$database_dialplan_uuid = $row["dialplan_uuid"];
			$dialplan_context = $row["dialplan_context"];
			$app_uuid = $row["app_uuid"];
		}
		unset ($prep_statement);

	//start the atomic transaction
		$count = $db->exec("BEGIN;");

	//delete child data
		$sql = "delete from v_dialplan_details ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and dialplan_uuid = '$dialplan_uuid' ";
		$db->query($sql);
		unset($sql);

	//delete parent data
		$sql = "delete from v_dialplans ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and dialplan_uuid = '$dialplan_uuid' ";
		$db->query($sql);
		unset($sql);

	//commit the atomic transaction
		$count = $db->exec("COMMIT;");

	//synchronize the xml config
		save_dialplan_xml();

	//delete the dialplan context from memcache
		$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
		if ($fp) {
			$switch_cmd = "memcache delete dialplan:".$_SESSION["context"]."@".$_SESSION['domain_name'];
			$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
		}
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
	echo "Delete Complete\n";
	echo "</div>\n";
	require_once "includes/footer.php";
	return;
?>