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
if (permission_exists('do_not_disturb')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//get the extension_uuid
	$extension_uuid = check_str($_REQUEST["id"]);

//get the extension number
	$sql = "select * from v_extensions ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and extension_uuid = '$extension_uuid' ";
	if (!(if_group("admin") || if_group("superadmin"))) {
		if (count($_SESSION['user']['extension']) > 0) {
			$sql .= "and (";
			$x = 0;
			foreach($_SESSION['user']['extension'] as $row) {
				if ($x > 0) { $sql .= "or "; }
				$sql .= "extension = '".$row['user']."' ";
				$x++;
			}
			$sql .= ")";
		}
		else {
			//hide any results when a user has not been assigned an extension
			$sql .= "and extension = 'disabled' ";
		}
	}
	$sql .= "and enabled = 'true' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	if (count($result)== 0) {
		echo "access denied";
		exit;
	}
	else {
		foreach ($result as &$row) {
			$extension = $row["extension"];
			$effective_caller_id_name = $row["effective_caller_id_name"];
			$effective_caller_id_number = $row["effective_caller_id_number"];
			$outbound_caller_id_name = $row["outbound_caller_id_name"];
			$outbound_caller_id_number = $row["outbound_caller_id_number"];
			$do_not_disturb = $row["do_not_disturb"];
			$call_forward_all = $row["call_forward_all"];
			$dial_string = $row["dial_string"];
			$call_forward_busy = $row["call_forward_busy"];
			$description = $row["description"];
		}
		if (strlen($do_not_disturb) == 0) {
			$do_not_disturb = "false";
		}
	}
	unset ($prep_statement);

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$dnd_enabled = check_str($_POST["dnd_enabled"]);
	}

//include the classes
	include "includes/classes/switch_do_not_disturb.php";

//do not disturb (dnd) config
	$dnd = new do_not_disturb;
	$dnd->domain_uuid = $_SESSION['domain_uuid'];
	$dnd->domain_name = $_SESSION['domain_name'];
	$dnd->extension = $extension;
	$dnd->enabled = $dnd_enabled;
	$dnd->set();
	$dnd->user_status();
	unset($dnd);

//redirect the user
	require_once "includes/header.php";
	echo "<meta http-equiv=\"refresh\" content=\"2;url=".PROJECT_PATH."/app/calls/v_calls.php\">\n";
	echo "<div align='center'>\n";
	echo "Update Complete<br />\n";
	echo "</div>\n";
	require_once "includes/footer.php";
	return;

?>