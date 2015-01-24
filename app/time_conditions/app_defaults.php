<?php

if ($domains_processed == 1) {

	//define holiday presets
		$preset[] = json_encode(array("new_years_day" => array("variables" => array("mday" => "1", "mon" => "1"))));
		$preset[] = json_encode(array("martin_luther_king_jr_day" => array("variables" => array("wday" => "2", "mon" => "1", "mweek" => "3"))));
		$preset[] = json_encode(array("presidents_day" => array("variables" => array("wday" => "2", "mon" => "2", "mweek" => "3"))));
		$preset[] = json_encode(array("memorial_day" => array("variables" => array("mday" => "25-31", "wday" => "2", "mon" => "5"))));
		$preset[] = json_encode(array("independence_day" => array("variables" => array("mday" => "4", "mon" => "7"))));
		$preset[] = json_encode(array("labor_day" => array("variables" => array("wday" => "2", "mon" => "9", "mweek" => "1"))));
		$preset[] = json_encode(array("columbus_day" => array("variables" => array("wday" => "2", "mon" => "10", "mweek" => "2"))));
		$preset[] = json_encode(array("veterans_day" => array("variables" => array("mday" => "11", "mon" => "11"))));
		$preset[] = json_encode(array("thanksgiving_day" => array("variables" => array("wday" => "5-6", "mon" => "11", "mweek" => "4"))));
		$preset[] = json_encode(array("christmas_day" => array("variables" => array("mday" => "25", "mon" => "12"))));

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

	//iterate and add each, if necessary
		foreach ($array as $index => $default_settings) {

		//add default settings
			$sql = "select count(*) as num_rows from v_default_settings ";
			$sql .= "where default_setting_category = '".$default_settings['default_setting_category']."' ";
			$sql .= "and default_setting_subcategory = '".$default_settings['default_setting_subcategory']."' ";
			$sql .= "and default_setting_name = '".$default_settings['default_setting_name']."' ";
			$sql .= "and default_setting_value = '".$default_settings['default_setting_value']."' ";
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

}

?>