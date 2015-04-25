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
if (permission_exists('voicemail_greeting_delete')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

if (count($_GET) > 0) {
	$voicemail_greeting_uuid = check_str($_GET["id"]);
	$voicemail_id = check_str($_GET["voicemail_id"]);
}

if (strlen($voicemail_greeting_uuid) > 0) {
	//get the greeting filename
		$sql = "select greeting_filename from v_voicemail_greetings ";
		$sql .= "where voicemail_greeting_uuid = '".$voicemail_greeting_uuid."' ";
		$sql .= "and domain_uuid = '".$domain_uuid."' ";
		$sql .= "and voicemail_id = '".$voicemail_id."' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$greeting_filename = $row["greeting_filename"];
			break; //limit to 1 row
		}
		unset ($prep_statement);

	//delete recording from the database
		$sql = "delete from v_voicemail_greetings ";
		$sql .= "where voicemail_greeting_uuid = '".$voicemail_greeting_uuid."' ";
		$sql .= "and domain_uuid = '".$domain_uuid."' ";
		$sql .= "and voicemail_id = '".$voicemail_id."' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		unset($sql);

	//set the greeting directory
		$v_greeting_dir = $_SESSION['switch']['storage']['dir'].'/voicemail/default/'.$_SESSION['domains'][$domain_uuid]['domain_name'].'/'.$voicemail_id;

	//delete the recording file
		if (file_exists($v_greeting_dir."/".$greeting_filename)) {
			@unlink($v_greeting_dir."/".$greeting_filename);
		}
}

//redirect the user
	$_SESSION["message"] = $text['message-delete'];
	header("Location: voicemail_greetings.php?id=".$voicemail_id);
	return;
?>