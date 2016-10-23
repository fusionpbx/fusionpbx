<?php

if ($domains_processed == 1) {

	//define array of editor settings
		$x = 0;
		$array[$x]['default_setting_uuid'] = '2e217303-53ff-4dda-b74e-7f07738d83c2';
		$array[$x]['default_setting_category'] = 'editor';
		$array[$x]['default_setting_subcategory'] = 'font_size';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = '14px';
		$array[$x]['default_setting_enabled'] = 'false';
		$array[$x]['default_setting_description'] = 'Set the default text size for Editor.';
		$x++;
		$array[$x]['default_setting_uuid'] = '8701cdca-64a1-4ff4-85a1-a09b1389ce92';
		$array[$x]['default_setting_category'] = 'editor';
		$array[$x]['default_setting_subcategory'] = 'indent_guides';
		$array[$x]['default_setting_name'] = 'boolean';
		$array[$x]['default_setting_value'] = 'false';
		$array[$x]['default_setting_enabled'] = 'false';
		$array[$x]['default_setting_description'] = 'Set the default visibility of indent guides for Editor.';
		$x++;
		$array[$x]['default_setting_uuid'] = '5669f8cf-f0a0-4d9c-ad75-caf239e9f5cd';
		$array[$x]['default_setting_category'] = 'editor';
		$array[$x]['default_setting_subcategory'] = 'invisibles';
		$array[$x]['default_setting_name'] = 'boolean';
		$array[$x]['default_setting_value'] = 'false';
		$array[$x]['default_setting_enabled'] = 'false';
		$array[$x]['default_setting_description'] = 'Set the default state of invisible characters for Editor.';
		$x++;
		$array[$x]['default_setting_uuid'] = '7122cb30-d557-4001-af94-d8d21f964a63';
		$array[$x]['default_setting_category'] = 'editor';
		$array[$x]['default_setting_subcategory'] = 'line_numbers';
		$array[$x]['default_setting_name'] = 'boolean';
		$array[$x]['default_setting_value'] = 'false';
		$array[$x]['default_setting_enabled'] = 'false';
		$array[$x]['default_setting_description'] = 'Set the default visibility of line numbers for Editor.';
		$x++;
		$array[$x]['default_setting_uuid'] = '62cfc1ac-6566-45ba-8c7d-f4234ab1b31e';
		$array[$x]['default_setting_category'] = 'editor';
		$array[$x]['default_setting_subcategory'] = 'live_previews';
		$array[$x]['default_setting_name'] = 'boolean';
		$array[$x]['default_setting_value'] = 'false';
		$array[$x]['default_setting_enabled'] = 'false';
		$array[$x]['default_setting_description'] = 'Enable or disable live previewing of syntax, text size and theme changes.';
		$x++;
		$array[$x]['default_setting_uuid'] = '7b403afd-e4d6-4e96-8c8f-2cf5d6187191';
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
		unset($array);

	//update the array structure
		if (is_array($missing)) {
			$array['default_settings'] = $missing;
			unset($missing);
		}

	//add the default settings
		if (is_array($array)) {
			$database = new database;
			$database->app_name = 'default_settings';
			$database->app_uuid = '2c2453c0-1bea-4475-9f44-4d969650de09';
			$database->save($array);
			$message = $database->message;
			unset($database);
		}

}

?>
