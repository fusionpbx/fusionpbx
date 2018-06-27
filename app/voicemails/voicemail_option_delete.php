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
	Portions created by the Initial Developer are Copyright (C) 2008-2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('voicemail_option_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the http values as variables
	if (count($_GET)>0) {
		$voicemail_option_uuid = check_str($_GET["id"]);
		$voicemail_uuid = check_str($_GET["voicemail_uuid"]);
	}

//delete the voicemail option
	if (strlen($voicemail_option_uuid) > 0) {
		$sql = "delete from v_voicemail_options ";
		$sql .= "where domain_uuid = '".$domain_uuid."' ";
		$sql .= "and voicemail_option_uuid = '".$voicemail_option_uuid."' ";
		$db->exec(check_sql($sql));
		unset($sql);
	}

//redirect the user
	messages::add($text['message-delete']);
	header('Location: voicemail_edit.php?id='.$voicemail_uuid);

?>
