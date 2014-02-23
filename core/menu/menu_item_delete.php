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
if (permission_exists('menu_delete')) {
	//access granted
}
else {
	echo "access denied";
	return;
}

//add multi-lingual support
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

if (count($_GET)>0) {
	//clear the menu session so it will rebuild with the update
		$_SESSION["menu"] = "";

	//get the menu uuid
		$menu_uuid = check_str($_GET["id"]);
		$menu_item_uuid = check_str($_GET["menu_item_uuid"]);

	//delete the item in the menu
		$sql  = "delete from v_menu_items ";
		$sql .= "where menu_item_uuid = '$menu_item_uuid' ";
		$sql .= "and menu_uuid = '$menu_uuid' ";
		$db->exec(check_sql($sql));
		unset($sql);

	//delete the menu item groups
		$sql  = "delete from v_menu_item_groups ";
		$sql .= "where menu_item_uuid = '$menu_item_uuid' ";
		$sql .= "and menu_uuid = '$menu_uuid' ";
		$db->exec(check_sql($sql));
		unset($sql);

	//delete the menu item language
		$sql  = "delete from v_menu_languages ";
		$sql .= "where menu_uuid = '$menu_uuid' ";
		$sql .= "and menu_item_uuid = '$menu_item_uuid' ";
		$db->exec(check_sql($sql));
		unset($sql);

	//redirect the user
		$_SESSION["message"] = $text['message-delete'];
		header("Location: menu_edit.php?id=".$menu_uuid);
		return;
}

?>