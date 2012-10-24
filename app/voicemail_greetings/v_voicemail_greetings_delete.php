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
require_once "includes/require.php";
require_once "includes/checkauth.php";
require_once "app_languages.php";
if (permission_exists('voicemail_greetings_delete')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

        foreach($content_voicemail_greetings as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

if (count($_GET)>0) {
    $id = $_GET["id"];
	$user_id = $_GET["user_id"];
}

if (strlen($id)>0) {
	//get the greeting filename
		$sql = "";
		$sql .= "select * from v_voicemail_greetings ";
		$sql .= "where greeting_uuid = '$id' ";
		$sql .= "and domain_uuid = '$domain_uuid' ";
		$sql .= "and user_id = '$user_id' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$greeting_name = $row["greeting_name"];
			break; //limit to 1 row
		}
		unset ($prep_statement);

	//delete recording from the database
		$sql = "";
		$sql .= "delete from v_voicemail_greetings ";
		$sql .= "where greeting_uuid = '$id' ";
		$sql .= "and domain_uuid = '$domain_uuid' ";
		$sql .= "and user_id = '$user_id' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		unset($sql);

	//set the greeting directory
		$v_greeting_dir = $_SESSION['switch']['storage']['dir'].'/voicemail/default/'.$_SESSION['domains'][$domain_uuid]['domain_name'].'/'.$user_id;

	//delete the recording file
		unlink($v_greeting_dir."/".$greeting_name);
}

//redirect the user
	require_once "includes/header.php";
	echo "<meta http-equiv=\"refresh\" content=\"2;url=v_voicemail_greetings.php?id=$user_id\">\n";
	echo "<div align='center'>\n";
	echo "".$text['confirm-delete-2']."\n";
	echo "</div>\n";
	require_once "includes/footer.php";
	return;
?>