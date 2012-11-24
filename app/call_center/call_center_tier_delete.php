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
require_once "root.php";
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('call_center_tiers_delete')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//get the id
	if (count($_GET)>0) {
		$id = check_str($_GET["id"]);
	}

//get the agent details
	$sql = "select * from v_call_center_tiers ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and call_center_tier_uuid = '$id' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		$agent_name = $row["agent_name"];
		$queue_name = $row["queue_name"];
		break; //limit to 1 row
	}
	unset ($prep_statement);

//delete the agent from the freeswitch
	//get the domain using the $domain_uuid
		$tmp_domain = $_SESSION['domains'][$domain_uuid]['domain_name'];
	//setup the event socket connection
		$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
	//delete the agent over event socket
		if ($fp) {
			//callcenter_config tier del [queue_name] [agent_name]
			$cmd = "api callcenter_config tier del ".$queue_name."@".$tmp_domain." ".$agent_name."@".$_SESSION['domains'][$domain_uuid]['domain_name'];
			$response = event_socket_request($fp, $cmd);
		}

//delete the tier from the database
	if (strlen($id)>0) {
		$sql = "delete from v_call_center_tiers ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and call_center_tier_uuid = '$id' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		unset($sql);
	}

//redirect the user
	require_once "includes/header.php";
	echo "<meta http-equiv=\"refresh\" content=\"2;url=call_center_tiers.php\">\n";
	echo "<div align='center'>\n";
	echo "Delete Complete\n";
	echo "</div>\n";
	require_once "includes/footer.php";
	return;

?>