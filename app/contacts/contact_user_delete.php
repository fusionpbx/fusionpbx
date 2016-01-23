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
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('contact_group_delete')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

// check if included in another file
	if (!$included) {
		//add multi-lingual support
		$language = new text;
		$text = $language->get();

		if (count($_REQUEST) > 0) {
			$contact_user_uuid = check_str($_REQUEST["id"]);
			$contact_uuid = check_str($_REQUEST["contact_uuid"]);
		}
	}

//delete the user
	if (is_uuid($contact_uuid) && is_uuid($contact_user_uuid)) {
		$sql = "delete from v_contact_users ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and contact_user_uuid = '$contact_user_uuid' ";
		$db->exec(check_sql($sql));
		unset($sql);
	}

//redirect the browser
	if (!$included) {
		$_SESSION["message"] = $text['message-delete'];
		header("Location: contact_edit.php?id=".$contact_uuid);
		return;
	}

?>