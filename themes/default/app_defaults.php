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
			$database->execute($sql);
			unset($sql);

			$sql = "update v_default_settings ";
			$sql .= "set default_setting_value = '#666' ";
			$sql .= "where default_setting_subcategory = 'message_default_background_color' ";
			$sql .= "and default_setting_value = '#004200' ";
			$database->execute($sql);
			unset($sql);

			$sql = "update v_default_settings ";
			$sql .= "set default_setting_value = 'true',  default_setting_enabled = 'true' ";
			$sql .= "where default_setting_subcategory = 'menu_main_icons' ";
			$sql .= "and default_setting_value = 'false' ";
			$sql .= "and default_setting_enabled = 'false' ";
			$database->execute($sql);
			unset($sql);

		//replace glyphicon icon with v6 font awesome icons for default main menu items
			$queries[] = "update v_menu_items set menu_item_icon = 'fa-solid fa-house' where menu_item_icon = 'glyphicon-home' ";
			$queries[] = "update v_menu_items set menu_item_icon = 'fa-solid fa-user' where menu_item_icon = 'glyphicon-user' ";
			$queries[] = "update v_menu_items set menu_item_icon = 'fa-solid fa-right-left' where menu_item_icon = 'glyphicon-transfer' ";
			$queries[] = "update v_menu_items set menu_item_icon = 'fa-solid fa-paper-plane' where menu_item_icon = 'glyphicon-send' ";
			$queries[] = "update v_menu_items set menu_item_icon = 'fa-solid fa-chart-column' where menu_item_icon = 'glyphicon-equalizer' ";
			$queries[] = "update v_menu_items set menu_item_icon = 'fa-solid fa-gear' where menu_item_icon = 'glyphicon-cog' ";
			$queries[] = "update v_menu_items set menu_item_icon = 'fa-solid fa-right-from-bracket' where menu_item_icon = 'glyphicon-log-out' ";
			$queries[] = "update v_menu_items set menu_item_icon = 'fa-solid fa-right-to-bracket' where menu_item_icon = 'glyphicon-log-in' ";
			$queries[] = "update v_menu_items set menu_item_icon = 'fa-solid fa-up-right-from-square' where menu_item_icon = 'glyphicon-new-window' ";

		//replace v5.x font awesome icons with v6.x icons for default main menu items
			$queries[] = "update v_menu_items set menu_item_icon = 'fa-solid fa-house' where menu_item_icon = 'fa-home' ";
			$queries[] = "update v_menu_items set menu_item_icon = 'fa-solid fa-user' where menu_item_icon = 'fa-user' ";
			$queries[] = "update v_menu_items set menu_item_icon = 'fa-solid fa-right-left' where menu_item_icon = 'fa-exchange-alt' ";
			$queries[] = "update v_menu_items set menu_item_icon = 'fa-solid fa-paper-plane' where menu_item_icon = 'fa-paper-plane' ";
			$queries[] = "update v_menu_items set menu_item_icon = 'fa-solid fa-chart-column' where menu_item_icon = 'fa-chart-bar' ";
			$queries[] = "update v_menu_items set menu_item_icon = 'fa-solid fa-gear' where menu_item_icon = 'fa-cog' ";
			$queries[] = "update v_menu_items set menu_item_icon = 'fa-solid fa-right-from-bracket' where menu_item_icon = 'fa-sign-out-alt' ";
			$queries[] = "update v_menu_items set menu_item_icon = 'fa-solid fa-right-to-bracket' where menu_item_icon = 'fa-sign-in-alt' ";
			$queries[] = "update v_menu_items set menu_item_icon = 'fa-solid fa-up-right-from-square' where menu_item_icon = 'fa-external-link-alt' ";

		//convert button and other icons in default/domain/user settings to use v6.x font awesome style class prefixes (e.g. 'fa-solid ' instead of 'fas ')
			foreach (['default','domain','user'] as $type) {
				$queries[] = "update v_".$type."_settings set ".$type."_setting_value = replace(".$type."_setting_value, 'fas ', 'fa-solid ') where ".$type."_setting_category = 'theme' and ".$type."_setting_subcategory like 'button_icon_%' ";
				$queries[] = "update v_".$type."_settings set ".$type."_setting_value = concat('fa-solid fa-', ".$type."_setting_value) where ".$type."_setting_category = 'theme' and ".$type."_setting_subcategory like 'body_header_icon_%' and ".$type."_setting_value not like 'fa-solid fa-%' and ".$type."_setting_value not like 'fa-regular fa-%' and ".$type."_setting_value not like 'fa-brands fa-%' ";
				$queries[] = "update v_".$type."_settings set ".$type."_setting_value = concat('fa-solid fa-', ".$type."_setting_value) where ".$type."_setting_category = 'theme' and ".$type."_setting_subcategory like 'menu_side_item_main_sub_icon_%' and ".$type."_setting_name = 'text' and ".$type."_setting_value not like 'fa-solid fa-%' and ".$type."_setting_value not like 'fa-regular fa-%' and ".$type."_setting_value not like 'fa-brands fa-%' ";
			}
			unset($type);

		//execute array of queries
			foreach ($queries as $sql) {
				$database->execute($sql);
			}
			unset($queries, $sql);

	}

?>