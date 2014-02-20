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
	Portions created by the Initial Developer are Copyright (C) 2013
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('ring_group_delete')) {
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

//get the id
	if (count($_GET)>0) {
		$id = check_str($_GET["id"]);
		$ring_group_uuid = check_str($_GET["ring_group_uuid"]);
	}

//delete ring_group_destination
	if (strlen($id)>0) {
		$sql = "delete from v_ring_group_destinations ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and ring_group_destination_uuid = '$id' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		unset($sql);
	}

//save the message to a session variable
	$_SESSION['message'] = $text['message-delete'];

//redirect the browser
	header("Location: ring_group_edit.php?id=$ring_group_uuid");
	exit;

?>