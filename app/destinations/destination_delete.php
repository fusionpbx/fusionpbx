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
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('destination_delete')) {
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

//get the ID
	if (count($_GET) > 0) {
		$id = check_str($_GET["id"]);
	}

//if no id then exit
	if (isset($id)) {
		echo "ID is required.";
		exit;
	}

//get the dialplan_uuid
	$orm = new orm;
	$orm->name('destinations');
	$orm->uuid($id);
	$result = $orm->find()->get();
	foreach ($result as &$row) {
		$dialplan_uuid = $row["dialplan_uuid"];
	}
	unset ($prep_statement);

//delete the dialplan
	if (isset($dialplan_uuid)) {
		$sql = "delete from v_dialplan_details ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and dialplan_uuid = '$id' ";
		$db->exec(check_sql($sql));
		unset($sql);

		$sql = "delete from v_dialplan ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and dialplan_uuid = '$id' ";
		$db->exec(check_sql($sql));
		unset($sql);
	}

//delete the destination
	$sql = "delete from v_destinations ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and destination_uuid = '$id' ";
	$db->exec(check_sql($sql));
	unset($sql);

//redirect the user
	$_SESSION["message"] = $text['message-delete'];
	header("Location: destinations.php");
	return;

?>