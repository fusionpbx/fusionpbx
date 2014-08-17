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

if ($domains_processed == 1) {

	//define array of settings
		$x = 0;
		$array[$x]['default_setting_category'] = 'theme';
		$array[$x]['default_setting_subcategory'] = 'background_image';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = '';
		$array[$x]['default_setting_enabled'] = 'false';
		$array[$x]['default_setting_description'] = 'Specify a folder path or file path/url to enable background image(s) within a selected compatible template.';
		$x++;
		$array[$x]['default_setting_category'] = 'theme';
		$array[$x]['default_setting_subcategory'] = 'background_color';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = '#ffffff';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_order'] = '0';
		$array[$x]['default_setting_description'] = 'Set a background (HTML compatible) color.';
		$x++;
		$array[$x]['default_setting_category'] = 'theme';
		$array[$x]['default_setting_subcategory'] = 'login_opacity';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = '0.35';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Set the opacity of the login box (decimal).';
		$x++;
		$array[$x]['default_setting_category'] = 'theme';
		$array[$x]['default_setting_subcategory'] = 'login_background_color';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = '#ffffff';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Set a background color (HTML compatible) for the login box.';
		$x++;
		$array[$x]['default_setting_category'] = 'theme';
		$array[$x]['default_setting_subcategory'] = 'footer_background_color';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = '#000000';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Set a background color (HTML compatible) for the footer bar.';
		$x++;
		$array[$x]['default_setting_category'] = 'theme';
		$array[$x]['default_setting_subcategory'] = 'footer_color';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = '#ffffff';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Set a foreground color (HTML compatible) for the footer bar.';
		$x++;
		$array[$x]['default_setting_category'] = 'theme';
		$array[$x]['default_setting_subcategory'] = 'footer_opacity';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = '0.2';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Set the opacity of the footer bar (decimal).';

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
					//print_r($message);
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
		$array[$x]['default_setting_value'] = '#f0f2f6';
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
			if ($row['num_rows'] == 1) {
				$orm = new orm;
				$orm->name('default_settings');
				$orm->save($array[0]);
				$message = $orm->message;
				//print_r($message);
			}
			unset($row);
		}

}

?>
