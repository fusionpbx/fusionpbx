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
require_once "resources/check_auth.php";
if (permission_exists('user_delete')) {
	//access allowed
}
else {
	echo "access denied";
	return;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the id
	$user_uuid = check_str($_GET["id"]);

//validate the uuid
	if (is_uuid($user_uuid)) {
		//get the user's domain from v_users
			if (permission_exists('user_domain')) {
				$sql = "select domain_uuid from v_users ";
				$sql .= "where user_uuid = '".$user_uuid."' ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				foreach ($result as &$row) {
					$domain_uuid = $row["domain_uuid"];
				}
				unset ($prep_statement);
			}
			else {
				$domain_uuid = $_SESSION['domain_uuid'];
			}

		//required to be a superadmin to delete a member of the superadmin group
			$superadmin_list = superadmin_list($db);
			if (if_superadmin($superadmin_list, $user_uuid)) {
				if (!if_group("superadmin")) {
					//access denied - do not delete the user
					header("Location: index.php");
					return;
				}
			}

		//delete the user settings
			$sql = "delete from v_user_settings ";
			$sql .= "where user_uuid = '".$user_uuid."' ";
			$sql .= "and domain_uuid = '".$domain_uuid."' ";
			if (!$db->exec($sql)) {
				$info = $db->errorInfo();
				print_r($info);
			}

		//delete the groups the user is assigned to
			$sql = "delete from v_group_users ";
			$sql .= "where user_uuid = '".$user_uuid."' ";
			$sql .= "and domain_uuid = '".$domain_uuid."' ";
			if (!$db->exec($sql)) {
				$info = $db->errorInfo();
				print_r($info);
			}

		//delete the user
			$sql = "delete from v_users ";
			$sql .= "where user_uuid = '".$user_uuid."' ";
			$sql .= "and domain_uuid = '".$domain_uuid."' ";
			if (!$db->exec($sql)) {
				$info = $db->errorInfo();
				print_r($info);
			}
	}

//redirect the user
	$_SESSION["message"] = $text['message-delete'];
	header("Location: index.php");

?>