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
	Portions created by the Initial Developer are Copyright (C) 2008-2025
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//check permisions
	if (empty($included) || !$included) {
		//includes files
		require_once dirname(__DIR__, 2) . "/resources/require.php";
		require_once "resources/check_auth.php";
		if (!permission_exists('group_edit')) {
			echo "access denied";
			return;
		}
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//permission restore default
	$permission = new permission;
	$permission->restore();

//reload the permissions for current user
	if (!empty($_SESSION["groups"]) && is_array($_SESSION["groups"])) {
		//clear current permissions
		unset($_SESSION['permissions'], $_SESSION['user']['permissions']);

		//get the permissions assigned to the groups that the current user is a member of, set the permissions in session variables
		$x = 0;
		$sql = "select distinct(permission_name) from v_group_permissions ";
		$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
		$sql .= "and permission_assigned = 'true' ";
		foreach ($_SESSION["groups"] as $field) {
			if (!empty($field['group_name'])) {
				$sql_where_or[] = "group_name = :group_name_".$x;
				$parameters['group_name_'.$x] = $field['group_name'];
				$x++;
			}
		}
		if (is_array($sql_where_or) && @sizeof($sql_where_or) != 0) {
			$sql .= "and (".implode(' or ', $sql_where_or).") ";
		}
		$parameters['domain_uuid'] = $_SESSION["domain_uuid"];
		$result = $database->select($sql, $parameters, 'all');
		if (is_array($result) && @sizeof($result) != 0) {
			foreach ($result as $row) {
				$_SESSION['permissions'][$row["permission_name"]] = true;
				$_SESSION["user"]["permissions"][$row["permission_name"]] = true;
			}
		}
		unset($sql, $parameters, $result, $row);
	}

//redirect the users
	if (empty($included) || !$included) {
		//show a message to the user
		message::add($text['message-restore']);
		header("Location: groups.php");
		return;
	}

?>
