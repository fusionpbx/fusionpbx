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

//if there are no items in the menu then add the default menu
	if ($domains_processed == 1) {
		$sql = "SELECT count(*) as count FROM v_menus ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$sub_result = $prep_statement->fetch(PDO::FETCH_ASSOC);
		unset ($prep_statement);
		if ($sub_result['count'] > 0) {
			if ($display_type == "text") {
				echo "	Menu:			no change\n";
			}
		}
		else {
			//create the uuid
				$menu_uuid = 'b4750c3f-2a86-b00d-b7d0-345c14eca286';
			//set the defaults
				$menu_name = 'default';
				$menu_language = 'en';
				$menu_description = '';
			//add the menu
				$sql = "insert into v_menus ";
				$sql .= "(";
				$sql .= "menu_uuid, ";
				$sql .= "menu_name, ";
				$sql .= "menu_language, ";
				$sql .= "menu_description ";
				$sql .= ")";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'".$menu_uuid."', ";
				$sql .= "'$menu_name', ";
				$sql .= "'$menu_language', ";
				$sql .= "'$menu_description' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);
			//add the menu items
				require_once "resources/classes/menu.php";
				$menu = new menu;
				$menu->db = $db;
				$menu->menu_uuid = $menu_uuid;
				$menu->restore();
				unset($menu);
				if ($display_type == "text") {
					echo "	Menu:			added\n";
				}
		}
		unset($prep_statement, $sub_result);
	}

//if there are no groups listed in v_menu_item_groups then add the default groups
	if ($domains_processed == 1) {
		$sql = "SELECT * FROM v_menus ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		$result_count = count($result);
		foreach($result as $field) {
			//get the menu_uuid
				$menu_uuid = $field['menu_uuid'];
			//check each menu to see if there are items in the menu assigned to it
				$sql = "select count(*) as count from v_menu_item_groups ";
				$sql .= "where menu_uuid = '$menu_uuid' ";
				$prep_statement = $db->prepare($sql);
				$prep_statement->execute();
				$sub_result = $prep_statement->fetch(PDO::FETCH_ASSOC);
				unset ($prep_statement);
				if ($sub_result['count'] == 0) {
					//no menu item groups found add the defaults
						foreach($apps as $app) {
							foreach ($app['menu'] as $sub_row) {
								foreach ($sub_row['groups'] as $group) {
									//add the record
									$sql = "insert into v_menu_item_groups ";
									$sql .= "(";
									$sql .= "menu_uuid, ";
									$sql .= "menu_item_uuid, ";
									$sql .= "group_name ";
									$sql .= ")";
									$sql .= "values ";
									$sql .= "(";
									$sql .= "'$menu_uuid', ";
									$sql .= "'".$sub_row['uuid']."', ";
									$sql .= "'".$group."' ";
									$sql .= ")";
									$db->exec($sql);
									unset($sql);
								}
							}
						}
				}
		}
		unset($prep_statement);
	}

?>