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
if (permission_exists('conference_room_delete')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the id
	if (count($_GET)>0) {
		$id = check_str($_GET["id"]);
	}

if (strlen($id)>0) {
	//get the meeting_uuid
		if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
			$conference_room_uuid = check_str($_GET["id"]);
			$sql = "select * from v_conference_rooms ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and conference_room_uuid = '$conference_room_uuid' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll();
			foreach ($result as &$row) {
				$meeting_uuid = $row["meeting_uuid"];
			}
			unset ($prep_statement);
		}

	//delete the conference session
		$sql = "delete from v_conference_rooms ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and conference_room_uuid = '$id' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		unset($sql);

	//delete the meeting users
		$sql = "delete from v_meeting_users ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and meeting_uuid = '$meeting_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		unset($sql);

	//delete the meetings
		$sql = "delete from v_meetings ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and meeting_uuid = '$meeting_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		unset($sql);
}


$_SESSION["message"] = $text['message-delete'];
header("Location: conference_rooms.php");
return;

?>