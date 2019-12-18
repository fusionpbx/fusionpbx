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
	Portions created by the Initial Developer are Copyright (C) 2008-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//process this only one time
	if ($domains_processed == 1) {
	
		//get the background images
			$relative_path = PROJECT_PATH.'/themes/default/images/backgrounds';
			$backgrounds = opendir($_SERVER["DOCUMENT_ROOT"].'/'.$relative_path);
			unset($array);
			$x = 0;
			while (false !== ($file = readdir($backgrounds))) {
				if ($file != "." AND $file != "..") {
					$ext = pathinfo($file, PATHINFO_EXTENSION);
					if ($ext == "png" || $ext == "jpg" || $ext == "jpeg" || $ext == "gif") {
						$array[$x]['default_setting_category'] = 'theme';
						$array[$x]['default_setting_subcategory'] = 'background_image';
						$array[$x]['default_setting_name'] = 'array';
						$array[$x]['default_setting_value'] = $relative_path.'/'.$file;
						$array[$x]['default_setting_enabled'] = 'false';
						$array[$x]['default_setting_description'] = 'Set a relative path or URL within a selected compatible template.';
						$x++;
						$array[$x]['default_setting_category'] = 'theme';
						$array[$x]['default_setting_subcategory'] = 'login_background_image';
						$array[$x]['default_setting_name'] = 'array';
						$array[$x]['default_setting_value'] = $relative_path.'/'.$file;
						$array[$x]['default_setting_enabled'] = 'false';
						$array[$x]['default_setting_description'] = 'Set a relative path or URL within a selected compatible template.';
						$x++;
					}
					if ($x > 300) { break; };
				}
			}
		//migrate old default_settings
			$sql = "update v_default_settings ";
			$sql .= "set default_setting_value = '#fafafa' ";
			$sql .= "where default_setting_subcategory = 'message_default_color' ";
			$sql .= "and default_setting_value = '#ccffcc' ";
			$database = new database;
			$database->execute($sql);
			unset($sql);

			$sql = "update v_default_settings ";
			$sql .= "set default_setting_value = '#666' ";
			$sql .= "where default_setting_subcategory = 'message_default_background_color' ";
			$sql .= "and default_setting_value = '#004200' ";
			$database = new database;
			$database->execute($sql);
			unset($sql);

			$sql = "update v_default_settings ";
			$sql .= "set default_setting_value = 'true',  default_setting_enabled = 'true' ";
			$sql .= "where default_setting_subcategory = 'menu_main_icons' ";
			$sql .= "and default_setting_value = 'false' ";
			$sql .= "and default_setting_enabled = 'false' ";
			$database = new database;
			$database->execute($sql);
			unset($sql);

		//replace glyphicon icon with fontawesome icon for default main menu items
			$queries[] = "update v_menu_items set menu_item_icon = 'fa-home' where menu_item_icon = 'glyphicon-home' ";
			$queries[] = "update v_menu_items set menu_item_icon = 'fa-user' where menu_item_icon = 'glyphicon-user' ";
			$queries[] = "update v_menu_items set menu_item_icon = 'fa-exchange-alt' where menu_item_icon = 'glyphicon-transfer' ";
			$queries[] = "update v_menu_items set menu_item_icon = 'fa-paper-plane' where menu_item_icon = 'glyphicon-send' ";
			$queries[] = "update v_menu_items set menu_item_icon = 'fa-chart-bar' where menu_item_icon = 'glyphicon-equalizer' ";
			$queries[] = "update v_menu_items set menu_item_icon = 'fa-cog' where menu_item_icon = 'glyphicon-cog' ";
			$queries[] = "update v_menu_items set menu_item_icon = 'fa-sign-out-alt' where menu_item_icon = 'glyphicon-log-out' ";
			$queries[] = "update v_menu_items set menu_item_icon = 'fa-sign-in-alt' where menu_item_icon = 'glyphicon-log-in' ";
			$queries[] = "update v_menu_items set menu_item_icon = 'fa-external-link-alt' where menu_item_icon = 'glyphicon-new-window' ";
			foreach ($queries as $sql) {
				$database = new database;
				$database->execute($sql);
			}
			unset($queries, $sql);

	}

?>
