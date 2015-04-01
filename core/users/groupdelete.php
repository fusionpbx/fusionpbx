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
	Portions created by the Initial Developer are Copyright (C) 2008-2015
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('group_delete') || if_group("superadmin")) {
		//access allowed
	}
	else {
		echo "access denied";
		return;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the http value and set as a variable
	$group_uuid = check_str($_GET["id"]);

//validate the uuid
	if (is_uuid($group_uuid)) {
		//get the group from v_groups
			$sql = "select domain_uuid, group_name from v_groups ";
			$sql .= "where group_uuid = '".$group_uuid."' ";
			if (!permission_exists('group_domain')) {
				$sql .= "and (domain_uuid = '".$_SESSION['domain_uuid']."' or domain_uuid is null); ";
			}
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			foreach ($result as &$row) {
				$domain_uuid = $row["domain_uuid"];
				$group_name = $row["group_name"];
			}
			unset ($prep_statement);

		//delete the group users
			$sql = "delete from v_group_users ";
			$sql .= "where group_uuid = '".$group_uuid."' ";
			if (!$db->exec($sql)) {
				$error = $db->errorInfo();
				print_r($error);
			}

		//delete the group permissions
			if (strlen($group_name) > 0) {
				$sql = "delete from v_group_permissions ";
				$sql .= "where group_name = '".$group_name."' ";
				$sql .= "and domain_uuid ".(($domain_uuid != '') ? " = '".$domain_uuid."' " : " is null ");
				if (!$db->exec($sql)) {
					$error = $db->errorInfo();
					print_r($error);
				}
			}

		//delete the group
			$sql = "delete from v_groups ";
			$sql .= "where group_uuid = '".$group_uuid."' ";
			$sql .= "and domain_uuid ".(($domain_uuid != '') ? " = '".$domain_uuid."' " : " is null ");
			if (!$db->exec($sql)) {
				$error = $db->errorInfo();
				print_r($error);
			}
	}

//redirect the user
	$_SESSION["message"] = $text['message-delete'];
	header("Location: groups.php");

?>