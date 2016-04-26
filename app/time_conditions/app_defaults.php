<?php

if ($domains_processed == 1) {

	//define holiday presets
		$preset['usa'][] = json_encode(array("new_years_day" => array("mday" => "1", "mon" => "1")));
		$preset['usa'][] = json_encode(array("martin_luther_king_jr_day" => array("wday" => "2", "mon" => "1", "mweek" => "3")));
		$preset['usa'][] = json_encode(array("presidents_day" => array("wday" => "2", "mon" => "2", "mweek" => "3")));
		$preset['usa'][] = json_encode(array("memorial_day" => array("mday" => "25-31", "wday" => "2", "mon" => "5")));
		$preset['usa'][] = json_encode(array("independence_day" => array("mday" => "4", "mon" => "7")));
		$preset['usa'][] = json_encode(array("labor_day" => array("wday" => "2", "mon" => "9", "mweek" => "1")));
		$preset['usa'][] = json_encode(array("columbus_day" => array("wday" => "2", "mon" => "10", "mweek" => "2")));
		$preset['usa'][] = json_encode(array("veterans_day" => array("mday" => "11", "mon" => "11")));
		$preset['usa'][] = json_encode(array("thanksgiving_day" => array("wday" => "5-6", "mon" => "11", "mweek" => "4")));
		$preset['usa'][] = json_encode(array("christmas_day" => array("mday" => "25", "mon" => "12")));

		$preset['england'][] = json_encode(array("new_years_day" => array("mday" => "1", "mon" => "1")));
		$preset['england'][] = json_encode(array("christmas_day" => array("mday" => "25", "mon" => "12")));
		$preset['england'][] = json_encode(array("boxing_day" => array("mday" => "26", "mon" => "12")));
		$preset['england'][] = json_encode(array("may_day" => array("mon" => "5", "mweek" => "1", "wday" => "2")));
		$preset['england'][] = json_encode(array("spring_bank_holiday" => array("mon" => "5", "mday" => "25-31", "wday" => "2")));
		$preset['england'][] = json_encode(array("august_bank_holiday" => array("mon" => "8", "mday" => "25-31", "wday" => "2")));

	//iterate and migrate old presets first
		$sql = "update v_default_settings ";
		$sql .= "set default_setting_subcategory = 'preset_usa' ";
		$sql .= ", default_setting_description = 'usa Holiday' ";
		$sql .= "where default_setting_category = 'time_conditions' ";
		$sql .= "and default_setting_subcategory = 'preset' ";
		$prep_statement = $db->prepare($sql);
		if ($prep_statement) {
			$prep_statement->execute();
			unset ($prep_statement, $sql);
		}

	//iterate and add each, if necessary
		$x = 0;
		foreach ($preset as $region => $data) {
			$sql = "select * from v_default_settings ";
			$sql .= "where default_setting_category = 'time_conditions' ";
			$sql .= "and default_setting_subcategory = 'preset_$region' ";
			$sql .= "and default_setting_name = 'array' ";
			$prep_statement = $db->prepare($sql);
			if ($prep_statement) {
				$prep_statement->execute();
				$default_settings = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				unset ($prep_statement, $sql);
				foreach ($data as $json) {
					$found = false;
					$missing[$x]['default_setting_category'] = 'time_conditions';
					$missing[$x]['default_setting_subcategory'] = "preset_$region";
					$missing[$x]['default_setting_name'] = 'array';
					$missing[$x]['default_setting_value'] = $json;
					$missing[$x]['default_setting_enabled'] = 'true';
					$missing[$x]['default_setting_description'] = "$region Holiday";
					foreach ($default_settings as $row) {
						if (trim($row['default_setting_value']) == trim($json)) {
							$found = true;
							//remove items from the array that were found
							unset($missing[$x]);
						}
					}
					$x++;
				}
			}
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

		$array[$x]['default_setting_category'] = 'time_conditions';
		$array[$x]['default_setting_subcategory'] = 'region';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = 'usa';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'What region to use by default when choosing Time Conditions';
		$x++;

	//iterate and add each, if necessary
		foreach ($array as $index => $default_settings) {

	//add the default setting
			$sql = "select count(*) as num_rows from v_default_settings ";
			$sql .= "where default_setting_category = '".$default_settings['default_setting_category']."' ";
			$sql .= "and default_setting_subcategory = '".$default_settings['default_setting_subcategory']."' ";
			$sql .= "and default_setting_name = '".$default_settings['default_setting_name']."' ";
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

	//unset the array variable
		unset($array);
}

?>