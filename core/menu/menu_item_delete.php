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
	$language = new text;
	$text = $language->get();

//delete the data
	if (is_uuid($_GET["id"]) && is_uuid($_GET["menu_item_uuid"])) {
		//get the menu uuid
			$menu_uuid = $_GET["id"];
			$menu_item_uuid = $_GET["menu_item_uuid"];

		//clear the menu session so it will rebuild with the update
			$_SESSION["menu"] = "";

		//delete the item in the menu
			$array['menu_items'][0]['menu_item_uuid'] = $menu_item_uuid;
			$array['menu_items'][0]['menu_uuid'] = $menu_uuid;
			$database = new database;
			$database->app_name = 'menu';
			$database->app_uuid = 'f4b3b3d2-6287-489c-2a00-64529e46f2d7';
			$database->delete($array);

		//delete the menu item groups
			$sql  = "delete from v_menu_item_groups ";
			$sql .= "where menu_item_uuid = :menu_item_uuid ";
			$sql .= "and menu_uuid = :menu_uuid ";
			$parameters['menu_item_uuid'] = $menu_item_uuid;
			$parameters['menu_uuid'] = $menu_uuid;
			$database = new database;
			$database->execute($sql, $parameters);
			unset($sql, $parameters);

		//delete the menu item language
			$sql  = "delete from v_menu_languages ";
			$sql .= "where menu_uuid = :menu_uuid ";
			$sql .= "and menu_item_uuid = :menu_item_uuid ";
			$parameters['menu_uuid'] = $menu_uuid;
			$parameters['menu_item_uuid'] = $menu_item_uuid;
			$database = new database;
			$database->execute($sql, $parameters);
			unset($sql, $parameters);

		//set message
			message::add($text['message-delete']);
	}

//redirect the user
	header("Location: menu_edit.php?id=".$menu_uuid);
	exit;

?>