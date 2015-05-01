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

if ($domains_processed == 1) {

	//define array of settings
		$x = 0;
		$array[$x]['default_setting_category'] = 'theme';
		$array[$x]['default_setting_subcategory'] = 'login_opacity';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = '0.35';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Set the opacity of the login box (decimal).';
		$x++;
		$array[$x]['default_setting_category'] = 'theme';
		$array[$x]['default_setting_subcategory'] = 'login_shadow_color';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = '#888888';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Set the shadow color (HTML compatible) of the login box.';
		$x++;
		$array[$x]['default_setting_category'] = 'theme';
		$array[$x]['default_setting_subcategory'] = 'login_background_color';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = '#ffffff';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Set the background color (hexadecimal) for the login box.';
		$x++;
		$array[$x]['default_setting_category'] = 'theme';
		$array[$x]['default_setting_subcategory'] = 'domain_visible';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = 'true';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Set the visibility of the name of the domain currently being managed.';
		$x++;
		$array[$x]['default_setting_category'] = 'theme';
		$array[$x]['default_setting_subcategory'] = 'domain_color';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = '#000000';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Set the text color for domain name.';
		$x++;
		$array[$x]['default_setting_category'] = 'theme';
		$array[$x]['default_setting_subcategory'] = 'domain_shadow_color';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = '#ffffff';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Set the text shadow color for domain name (Enhanced theme only).';
		$x++;
		$array[$x]['default_setting_category'] = 'theme';
		$array[$x]['default_setting_subcategory'] = 'domain_background_color';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = '#000000';
		$array[$x]['default_setting_enabled'] = 'false';
		$array[$x]['default_setting_description'] = 'Set the background color (hexadecimal) for the domain name.';
		$x++;
		$array[$x]['default_setting_category'] = 'theme';
		$array[$x]['default_setting_subcategory'] = 'domain_background_opacity';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = '0.1';
		$array[$x]['default_setting_enabled'] = 'false';
		$array[$x]['default_setting_description'] = 'Set the background opacity of the domain name.';
		$x++;
		$array[$x]['default_setting_category'] = 'theme';
		$array[$x]['default_setting_subcategory'] = 'footer_background_color';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = '#000000';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Set the background color (HTML compatible) for the footer bar.';
		$x++;
		$array[$x]['default_setting_category'] = 'theme';
		$array[$x]['default_setting_subcategory'] = 'footer_color';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = '#ffffff';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Set the foreground color (HTML compatible) for the footer bar.';
		$x++;
		$array[$x]['default_setting_category'] = 'theme';
		$array[$x]['default_setting_subcategory'] = 'footer_opacity';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = '0.2';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Set the opacity of the footer bar (decimal).';
		$x++;
		$array[$x]['default_setting_category'] = 'theme';
		$array[$x]['default_setting_subcategory'] = 'message_default_background_color';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = '#ccffcc';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Set the background color (HTML compatible) for the positive (default) message bar.';
		$x++;
		$array[$x]['default_setting_category'] = 'theme';
		$array[$x]['default_setting_subcategory'] = 'message_default_color';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = '#004200';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Set the foreground color (HTML compatible) for the positive (default) message bar text.';
		$x++;
		$array[$x]['default_setting_category'] = 'theme';
		$array[$x]['default_setting_subcategory'] = 'message_negative_background_color';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = '#ffcdcd';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Set the background color (HTML compatible) for the negative message bar.';
		$x++;
		$array[$x]['default_setting_category'] = 'theme';
		$array[$x]['default_setting_subcategory'] = 'message_negative_color';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = '#670000';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Set the foreground color (HTML compatible) for the negative message bar text.';
		$x++;
		$array[$x]['default_setting_category'] = 'theme';
		$array[$x]['default_setting_subcategory'] = 'message_alert_background_color';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = '#ffe585';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Set the background color (HTML compatible) for the alert message bar.';
		$x++;
		$array[$x]['default_setting_category'] = 'theme';
		$array[$x]['default_setting_subcategory'] = 'message_alert_color';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = '#d66721';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Set the foreground color (HTML compatible) for the alert message bar text.';
		$x++;
		$array[$x]['default_setting_category'] = 'theme';
		$array[$x]['default_setting_subcategory'] = 'message_opacity';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = '0.9';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Set the opacity of the message bar (decimal).';
		$x++;
		$array[$x]['default_setting_category'] = 'theme';
		$array[$x]['default_setting_subcategory'] = 'message_delay';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = '1.75';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Set the hide delay of the message bar (seconds).';
		$x++;
		$array[$x]['default_setting_category'] = 'theme';
		$array[$x]['default_setting_subcategory'] = 'body_opacity';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = '0.93';
		$array[$x]['default_setting_enabled'] = 'false';
		$array[$x]['default_setting_description'] = 'Set the opacity of the body and content (decimal).';
		$x++;
		$array[$x]['default_setting_category'] = 'theme';
		$array[$x]['default_setting_subcategory'] = 'menu_opacity';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = '0.96';
		$array[$x]['default_setting_enabled'] = 'false';
		$array[$x]['default_setting_description'] = 'Set the opacity of the main menu (decimal, Minimized theme only).';

	//iterate and add each, if necessary
		foreach ($array as $index => $default_settings) {
			//add theme default settings
			$sql = "select count(*) as num_rows from v_default_settings ";
			$sql .= "where default_setting_category = 'theme' ";
			$sql .= "and default_setting_subcategory = '".$default_settings['default_setting_subcategory']."' ";
			$prep_statement = $db->prepare($sql);
			if ($prep_statement) {
				$prep_statement->execute();
				$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
				unset($prep_statement);
				if ($row['num_rows'] == 0) {
					$orm = new orm;
					$orm->name('default_settings');
					$orm->save($array[$index]);
					$message = $orm->message;
				}
				unset($row);
			}
		}

	//define secondary background color array
		unset($array);
		$x = 0;
		$array[$x]['default_setting_category'] = 'theme';
		$array[$x]['default_setting_subcategory'] = 'background_color';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = '#ffffff';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_order'] = '0';
		$array[$x]['default_setting_description'] = 'Set a background (HTML compatible) color.';
		$x++;
		$array[$x]['default_setting_category'] = 'theme';
		$array[$x]['default_setting_subcategory'] = 'background_color';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = '#e7ebf1';
		$array[$x]['default_setting_order'] = '1';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Set a secondary background (HTML compatible) color, for a gradient effect.';

	//add secondary background color separately, if missing
		$sql = "select count(*) as num_rows from v_default_settings ";
		$sql .= "where default_setting_category = 'theme' ";
		$sql .= "and default_setting_subcategory = 'background_color' ";
		$prep_statement = $db->prepare($sql);
		if ($prep_statement) {
			$prep_statement->execute();
			$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
			unset($prep_statement);
			if ($row['num_rows'] == 0) {
				$orm = new orm;
				$orm->name('default_settings');
				foreach ($array as $index => $null) {
					$orm->save($array[$index]);
				}
				$message = $orm->message;
				//print_r($message);
			}
			unset($row);
		}

	//get the background images
		$relative_path = PROJECT_PATH.'/themes/enhanced/images/backgrounds';
		$backgrounds = opendir($_SERVER["DOCUMENT_ROOT"].'/'.$relative_path);
		unset($array);
		$x = 0;
		while (false !== ($file = readdir($backgrounds))) {
			if ($file != "." AND $file != ".."){
				$new_path = $dir.'/'.$file;
				$level = explode('/',$new_path);
				$ext = pathinfo($file, PATHINFO_EXTENSION);
				if ($ext == "png" || $ext == "jpg" || $ext == "jpeg" || $ext == "gif") {
					$x++;
					$array[$x]['default_setting_category'] = 'theme';
					$array[$x]['default_setting_subcategory'] = 'background_image';
					$array[$x]['default_setting_name'] = 'array';
					$array[$x]['default_setting_value'] = $relative_path.'/'.$file;
					$array[$x]['default_setting_enabled'] = 'false';
					$array[$x]['default_setting_description'] = 'Set a relative path or URL within a selected compatible template.';
				}
				if ($x > 300) { break; };
			}
		}

	//get default settings
		$sql = "select * from v_default_settings ";
		$sql .= "where default_setting_category = 'theme' ";
		$sql .= "and default_setting_subcategory = 'background_image' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$default_settings = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		unset($prep_statement);

	//add theme default settings
		foreach ($array as $row) {
			$found = false;
			foreach ($default_settings as $field) {
				if ($field["default_setting_value"] == $row["default_setting_value"]) {
					$found = true;
				}
			}
			if (!$found) {
				$orm = new orm;
				$orm->name('default_settings');
				$orm->save($row);
				$message = $orm->message;
				//print_r($message);
			}
		}

	//unset the array variable
		unset($array);
}

?>