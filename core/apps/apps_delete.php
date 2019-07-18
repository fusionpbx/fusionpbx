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
if (if_group("superadmin")) {
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
	if (is_uuid($_GET["id"]) {

		$app_uuid = $_GET["id"];

		//get the list of installed apps from the core and mod directories
			$config_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/*/*/app_config.php");
			$x=0;
			foreach ($config_list as $config_path) {
				include($config_path);
				$x++;
			}
		//find the app using the $app_uuid
			foreach ($apps as &$row) {
				if ($row["uuid"] == $app_uuid) {
					$name = $row['name'];
					if ($row["uuid"] == $app_uuid && $row['category'] != "Core") {
						//delete the app from the menu
							foreach ($row['menu'] as $index => &$menu) {
								//delete menu groups and permissions from the database
									$array['menu_item_groups'][$index]['menu_item_uuid'] = $menu['uuid'];
									$array['menu_items'][$index['menu_item_uuid'] = $menu['uuid'];
								//delete the app from the file system
									if (strlen($menu['path']) > 0) {
										system('rm -rf '.dirname($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.$menu['path']));
									}
							}
							if (is_array($array) && sizeof($array) != 0) {
								$database = new database;
								$database->app_name = 'apps';
								$database->app_uuid = 'd8704214-75a0-e52f-1336-f0780e29fef8';
								$database->delete($array);
								unset($array);
							}

						//delete the group permissions for the app
							foreach ($row['permissions'] as $index => &$permission) {
								$array['group_permissions'][$index]['permission_name'] = $permission['name'];
							}
							if (is_array($array) && sizeof($array) != 0) {
								$database = new database;
								$database->app_name = 'apps';
								$database->app_uuid = 'd8704214-75a0-e52f-1336-f0780e29fef8';
								$database->delete($array);
								unset($array);
							}
					}
				}
			}

		//set message
			message::add($text['message-delete']);

	}


//redirect the browser
	header("Location: apps.php");
	return;

?>