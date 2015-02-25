<?php

if ($domains_processed == 1) {

	//define holiday presets
		$preset[] = json_encode(array("new_years_day" => array("mday" => "1", "mon" => "1")));
		$preset[] = json_encode(array("martin_luther_king_jr_day" => array("wday" => "2", "mon" => "1", "mweek" => "3")));
		$preset[] = json_encode(array("presidents_day" => array("wday" => "2", "mon" => "2", "mweek" => "3")));
		$preset[] = json_encode(array("memorial_day" => array("mday" => "25-31", "wday" => "2", "mon" => "5")));
		$preset[] = json_encode(array("independence_day" => array("mday" => "4", "mon" => "7")));
		$preset[] = json_encode(array("labor_day" => array("wday" => "2", "mon" => "9", "mweek" => "1")));
		$preset[] = json_encode(array("columbus_day" => array("wday" => "2", "mon" => "10", "mweek" => "2")));
		$preset[] = json_encode(array("veterans_day" => array("mday" => "11", "mon" => "11")));
		$preset[] = json_encode(array("thanksgiving_day" => array("wday" => "5-6", "mon" => "11", "mweek" => "4")));
		$preset[] = json_encode(array("christmas_day" => array("mday" => "25", "mon" => "12")));

	//define array of settings
		$x = 0;
		foreach ($preset as $json) {
			$array[$x]['default_setting_category'] = 'time_conditions';
			$array[$x]['default_setting_subcategory'] = 'preset';
			$array[$x]['default_setting_name'] = 'array';
			$array[$x]['default_setting_value'] = $json;
			$array[$x]['default_setting_enabled'] = 'true';
			$array[$x]['default_setting_description'] = 'Holiday';
			$x++;
		}

	//get an array of the default settings
		$sql = "select * from v_default_settings ";
		$sql .= "where default_setting_category = 'time_conditions' ";
		$sql .= "and default_setting_subcategory = 'preset' ";
		$sql .= "and default_setting_name = 'array' ";
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
				if (trim($row['default_setting_value']) == trim($setting['default_setting_value'])) {
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