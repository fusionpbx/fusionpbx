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
if (permission_exists('user_setting_delete')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the variables
	if (count($_GET)>0) {
		$id = check_str($_GET["id"]);
		$user_uuid = check_str($_GET["user_uuid"]);
	}

//delete the record
	if (strlen($id) > 0) {
		//delete user_setting
			$sql = "delete from v_user_settings ";
			$sql .= "where user_uuid = '$user_uuid' ";
			$sql .= "and user_setting_uuid = '$id' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			unset($sql);
	}

//redirect the user
	$_SESSION["message"] = $text['message-delete'];
	header("Location: usersupdate.php?id=".$user_uuid);
	return;

?>