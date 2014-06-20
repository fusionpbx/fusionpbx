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
	Portions created by the Initial Developer are Copyright (C) 2008-2013
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Sérgio Reis <uc@wavecom.pt>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
require_once "resources/paging.php";
if (permission_exists('extension_add')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

//set the http get/post variable(s) to a php variable
if (isset($_REQUEST["group_name"]) && isset($_REQUEST["new_group_name"])) {

	$group_name = check_str($_REQUEST["group_name"]);
	$new_group_name = check_str($_REQUEST["new_group_name"]);
	$new_group_desc = check_str($_REQUEST["new_group_desc"]);

	//get the groups data
		$sql = "select * from v_groups ";
		$sql .= "where domain_uuid = '".$domain_uuid."' ";
		$sql .= "and group_name = '".$group_name."' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$domain_uuid = $row["domain_uuid"];
			$group_name = $row["group_name"];
		}
		unset ($prep_statement);

	//create new group
		$group_uuid = uuid();
		$sql = "insert into v_groups ";
		$sql .= "( ";
		$sql .= "group_uuid, ";
		$sql .= "domain_uuid, ";
		$sql .= "group_name, ";
		$sql .= "group_description ";
		$sql .= ") ";
		$sql .= "values ";
		$sql .= "( ";
		$sql .= "'".$group_uuid."', ";
		$sql .= "'".$domain_uuid."', ";
		$sql .= "'".$new_group_name."', ";
		$sql .= "'".$new_group_desc."' ";
		$sql .= ") ";
		$db->exec(check_sql($sql));
		unset($sql);

	//get the group permissions data
		$sql = "select * from v_group_permissions ";
		$sql .= "where domain_uuid = '".$domain_uuid."' ";
		$sql .= "and group_name = '".$group_name."' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$domain_uuid = $row["domain_uuid"];
			$permission_name = $row["permission_name"];
			$group_name = $row["group_name"];

		//copy the group permissions
			$group_permission_uuid = uuid();
			$sql = "insert into v_group_permissions ";
			$sql .= "( ";
			$sql .= "group_permission_uuid, ";
			$sql .= "domain_uuid, ";
			$sql .= "permission_name, ";
			$sql .= "group_name ";
			$sql .= ") ";
			$sql .= "values ";
			$sql .= "( ";
			$sql .= "'".$group_permission_uuid."', ";
			$sql .= "'".$domain_uuid."', ";
			$sql .= "'".$permission_name."', ";
			$sql .= "'".$new_group_name."' ";
			$sql .= ") ";
			$db->exec(check_sql($sql));
			unset($sql);
		}
		unset ($prep_statement);

	//redirect the user
		$_SESSION["message"] = $text['message-copy'];

}

header("Location: groups.php");
return;

?>