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
if (permission_exists('group_member_add') || if_group("superadmin")) {
	//access allowed
}
else {
	echo "access denied";
	return;
}

//requires a superadmin to add a user to the superadmin group
	if (!if_group("superadmin") && $_GET["group_name"] == "superadmin") {
		echo "access denied";
		return;
	}

//get the http values and set them as variables
	$domain_uuid = check_str($_POST["domain_uuid"]);
	$group_uuid = check_str($_POST["group_uuid"]);
	$group_name = check_str($_POST["group_name"]);
	$user_uuid = check_str($_POST["user_uuid"]);

//add the user to the group
	if (is_uuid($user_uuid) && is_uuid($group_uuid) && strlen($group_name) > 0) {
		$sql = "insert into v_group_users ";
		$sql .= "(";
		$sql .= "group_user_uuid, ";
		$sql .= "domain_uuid, ";
		$sql .= "group_uuid, ";
		$sql .= "group_name, ";
		$sql .= "user_uuid ";
		$sql .= ")";
		$sql .= "values ";
		$sql .= "(";
		$sql .= "'".uuid()."', ";
		$sql .= "'".$domain_uuid."', ";
		$sql .= "'".$group_uuid."', ";
		$sql .= "'".$group_name."', ";
		$sql .= "'".$user_uuid."' ";
		$sql .= ")";
		if (!$db->exec($sql)) {
			$info = $db->errorInfo();
			echo "<pre>".print_r($info, true)."</pre>";
			exit;
		}
		else {
			//log the success
			//$log_type = 'group'; $log_status='add'; $log_add_user=$_SESSION["username"]; $log_desc= "username: ".$username." added to group: ".$group_name;
			//log_add($db, $log_type, $log_status, $log_desc, $log_add_user, $_SERVER["REMOTE_ADDR"]);
		}
	}

//redirect the user
	$_SESSION["message"] = $text['message-update'];
	header("Location: groupmembers.php?group_uuid=".$group_uuid."&group_name=".$group_name);

?>