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
if (permission_exists('menu_edit')) {
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

//move down more than one level at a time
//update v_menu_items set menu_item_order = (menu_item_order+1) where menu_item_order > 2 or menu_item_order = 2

if (count($_GET)>0) {
	$menu_item_id = check_str($_GET["menu_item_id"]);
	$menu_item_order = check_str($_GET["menu_item_order"]);
	$menu_parent_guid = check_str($_GET["menu_parent_guid"]);

	$sql = "SELECT menu_item_order FROM v_menu_items ";
	$sql .= "where domain_uuid = '".$domain_uuid."' ";
	$sql .= "order by menu_item_order desc ";
	$sql .= "limit 1 ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		$highestmenu_item_order = $row[menu_item_order];
	}
	unset($prep_statement);

	if ($menu_item_order != $highestmenu_item_order) {
		//clear the menu session so it will rebuild with the update
			$_SESSION["menu"] = "";

		//move the current item's order number up
			$sql  = "update v_menu_items set ";
			$sql .= "menu_item_order = (menu_item_order-1) "; //move down
			$sql .= "where domain_uuid = '".$domain_uuid."' ";
			$sql .= "and menu_item_order = ".($menu_item_order+1)." ";
			$db->exec(check_sql($sql));
			unset($sql);

		//move the selected item's order number down
			$sql  = "update v_menu_items set ";
			$sql .= "menu_item_order = (menu_item_order+1) "; //move up
			$sql .= "where domain_uuid = '".$domain_uuid."' ";
			$sql .= "and menu_item_id = '$menu_item_id' ";
			$db->exec(check_sql($sql));
			unset($sql);
	}

	//redirect the user
		$_SESSION["message"] = $text['message-moved_down'];
		header("Location: menu_list.php?menu_item_id=".$menu_item_id);
		return;
}

?>