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
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

if (count($_GET) > 0) {
	$id = check_str($_GET["id"]);
}

if (strlen($id)>0) {

	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
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
							foreach ($row['menu'] as &$menu) {
								//delete menu groups and permissions from the database
									$sql = "delete from v_menu_item_groups ";
									$sql .= "where menu_item_uuid = '".$menu['uuid']."' ";
									$db->query($sql);

									$sql = "delete from v_menu_items ";
									$sql .= "where menu_item_uuid = '".$menu['uuid']."' ";
									$db->query($sql);

								//delete the app from the file system
									if (strlen($menu['path']) > 0) {
										system('rm -rf '.dirname($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.$menu['path']));
									}
							}

						//delete the group permissions for the app
							foreach ($row['permissions'] as &$permission) {
								$sql = "delete from v_group_permissions ";
								$sql .= "where permission_name = '".$permission['name']."' ";
								$db->query($sql);
							}
					}
				}
			}
	}
}

//redirect the browser
	$_SESSION["message"] = $text['message-delete'];
	header("Location: apps.php");
	return;

?>