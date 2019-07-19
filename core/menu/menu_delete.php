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
if (if_group("admin") || if_group("superadmin")) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//delete the data
	if (is_uuid($_GET["id"])) {
		$menu_uuid = $_GET["id"];

		//start the database transaction
			$db->beginTransaction();

		//delete the menu
			$array['menus'][0]['menu_uuid'] = $menu_uuid;
			$database = new database;
			$database->app_name = 'menu';
			$database->app_uuid = 'f4b3b3d2-6287-489c-2a00-64529e46f2d7';
			$database->delete($array);
			unset($array);

		//delete the items in the menu
			$sql = "delete from v_menu_items ";
			$sql .= "where menu_uuid = :menu_uuid ";
			$parameters['menu_uuid'] = $menu_uuid;
			$database = new database;
			$database->execute($sql, $parameters);
			unset($sql, $parameters);

		//delete the menu permissions
			$sql = "delete from v_menu_item_groups ";
			$sql .= "where menu_uuid = :menu_uuid ";
			$parameters['menu_uuid'] = $menu_uuid;
			$database = new database;
			$database->execute($sql, $parameters);
			unset($sql, $parameters);

		//delete the menu languages
			$sql = "delete from v_menu_languages ";
			$sql .= "where menu_uuid = :menu_uuid ";
			$parameters['menu_uuid'] = $menu_uuid;
			$database = new database;
			$database->execute($sql, $parameters);
			unset($sql, $parameters);

		//save the changes to the database
			$db->commit();

		//set message
			message::add($text['message-delete']);
	}

//redirect the user
	header("Location: menu.php");
	exit;

?>