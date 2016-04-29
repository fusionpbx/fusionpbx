<?php

if ($domains_processed == 1) {

	//define array of editor settings
		$x = 0;
		$array[$x]['default_setting_category'] = 'editor';
		$array[$x]['default_setting_subcategory'] = 'font_size';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = '14px';
		$array[$x]['default_setting_enabled'] = 'false';
		$array[$x]['default_setting_description'] = 'Set the default text size for Editor.';
		$x++;
		$array[$x]['default_setting_category'] = 'editor';
		$array[$x]['default_setting_subcategory'] = 'indent_guides';
		$array[$x]['default_setting_name'] = 'boolean';
		$array[$x]['default_setting_value'] = 'false';
		$array[$x]['default_setting_enabled'] = 'false';
		$array[$x]['default_setting_description'] = 'Set the default visibility of indent guides for Editor.';
		$x++;
		$array[$x]['default_setting_category'] = 'editor';
		$array[$x]['default_setting_subcategory'] = 'invisibles';
		$array[$x]['default_setting_name'] = 'boolean';
		$array[$x]['default_setting_value'] = 'false';
		$array[$x]['default_setting_enabled'] = 'false';
		$array[$x]['default_setting_description'] = 'Set the default state of invisible characters for Editor.';
		$x++;
		$array[$x]['default_setting_category'] = 'editor';
		$array[$x]['default_setting_subcategory'] = 'line_numbers';
		$array[$x]['default_setting_name'] = 'boolean';
		$array[$x]['default_setting_value'] = 'false';
		$array[$x]['default_setting_enabled'] = 'false';
		$array[$x]['default_setting_description'] = 'Set the default visibility of line numbers for Editor.';
		$x++;
		$array[$x]['default_setting_category'] = 'editor';
		$array[$x]['default_setting_subcategory'] = 'live_previews';
		$array[$x]['default_setting_name'] = 'boolean';
		$array[$x]['default_setting_value'] = 'false';
		$array[$x]['default_setting_enabled'] = 'false';
		$array[$x]['default_setting_description'] = 'Enable or disable live previewing of syntax, text size and theme changes.';
		$x++;
		$array[$x]['default_setting_category'] = 'editor';
		$array[$x]['default_setting_subcategory'] = 'theme';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = 'Cobalt';
		$array[$x]['default_setting_enabled'] = 'false';
		$array[$x]['default_setting_description'] = 'Set the default theme.';

	//get an array of the default settings
		$sql = "select * from v_default_settings ";
		$sql .= "where default_setting_category = 'editor' ";
		$prep_statement = $db->prepare($sql);
		$prep_statement->execute();
		$default_settings = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		unset ($prep_statement, $sql);

	//find the missing default settings
		$x = 0;
		foreach ($array as $setting) {
			$found = false;
			$missing[$x] = $setting;
			foreach ($default_settings as $row) {
				if (trim($row['default_setting_subcategory']) == trim($setting['default_setting_subcategory'])) {
					$found = true;
					//remove items from the array that were found
					unset($missing[$x]);
				}
			}
			$x++;
		}

	//add the missing default settings
		foreach ($missing as $row) {
			//add the default settings
			$orm = new orm;
			$orm->name('default_settings');
			$orm->save($row);
			$message = $orm->message;
			unset($orm);
			//print_r($message);
		}
		unset($missing);

	//unset the array variable
		unset($array);

}

?>