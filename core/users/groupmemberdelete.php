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
if (permission_exists('group_member_delete') || if_group("superadmin")) {
	//access allowed
}
else {
	echo "access denied";
	return;
}

//requires a superadmin to delete superadmin group
	if (!if_group("superadmin") && $_GET["group_name"] == "superadmin") {
		echo "access denied";
		return;
	}

//get the http values and set them as variables
	$group_name = check_str($_GET["group_name"]);
	$user_uuid = check_str($_GET["user_uuid"]);
	$group_uuid = check_str($_GET["group_uuid"]);

//delete the group membership
	$sql_delete = "delete from v_group_users ";
	$sql_delete .= "where user_uuid = '".$user_uuid."' ";
	$sql_delete .= "and group_uuid = '".$group_uuid."' ";
	if (!$db->exec($sql_delete)) {
		$info = $db->errorInfo();
		echo "<pre>".print_r($info, true)."</pre>";
		exit;
	}
	else {
		//$log_type = 'group'; $log_status='remove'; $log_add_user=$_SESSION["username"]; $log_desc= "username: ".$username." removed from group: ".$group_name;
		//log_add($db, $log_type, $log_status, $log_desc, $log_add_user, $_SERVER["REMOTE_ADDR"]);
	}

//redirect the user
	$_SESSION["message"] = $text['message-delete'];
	header("Location: groupmembers.php?group_uuid=".$group_uuid."&group_name=".$group_name);

?>