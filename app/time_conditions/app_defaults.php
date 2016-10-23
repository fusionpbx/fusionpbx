<?php

if ($domains_processed == 1) {

	//define region presets
		$array[$x]['default_setting_uuid'] = 'c8cbb0eb-850b-4afd-a918-cceaf8af3957';
		$array[$x]['default_setting_category'] = 'time_conditions';
		$array[$x]['default_setting_subcategory'] = 'region';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = 'usa';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'What region to use by default when choosing Time Conditions';

	//define English holiday presets
		$x++;
		$array[$x]['default_setting_uuid'] = '528ec73e-03bb-4ea1-9ce1-19b81fb3f584';
		$array[$x]['default_setting_category'] = 'time_conditions';
		$array[$x]['default_setting_subcategory'] = 'preset_england';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = '{"new_years_day":{"mday":"1","mon":"1"}}';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'England Holiday';
		$x++;
		$array[$x]['default_setting_uuid'] = '420b7282-2e49-4d63-9eb3-48b3b96bc184';
		$array[$x]['default_setting_category'] = 'time_conditions';
		$array[$x]['default_setting_subcategory'] = 'preset_england';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = '{"may_day":{"mon":"5","mday":"1-7","wday":"2"}}';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'England Holiday';
		$x++;
		$array[$x]['default_setting_uuid'] = 'c9ab6e93-63e0-4098-9290-7a721e813450';
		$array[$x]['default_setting_category'] = 'time_conditions';
		$array[$x]['default_setting_subcategory'] = 'preset_england';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = '{"spring_bank_holiday":{"mon":"5","mday":"25-31","wday":"2"}}';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'England Holiday';
		$x++;
		$array[$x]['default_setting_uuid'] = 'b7eac3ac-a99d-4fc8-8e3e-682a17a5b463';
		$array[$x]['default_setting_category'] = 'time_conditions';
		$array[$x]['default_setting_subcategory'] = 'preset_england';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = '{"august_bank_holiday":{"mon":"8","mday":"25-31","wday":"2"}}';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'England Holiday';
		$x++;
		$array[$x]['default_setting_uuid'] = 'cde53fd6-713e-43f9-beed-3cace375de56';
		$array[$x]['default_setting_category'] = 'time_conditions';
		$array[$x]['default_setting_subcategory'] = 'preset_england';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = '{"christmas_day":{"mday":"25","mon":"12"}}';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'England Holiday';
		$x++;
		$array[$x]['default_setting_uuid'] = '3ddfd3b3-5c2e-45ef-b3ca-7f361ecc0a93';
		$array[$x]['default_setting_category'] = 'time_conditions';
		$array[$x]['default_setting_subcategory'] = 'preset_england';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = '{"boxing_day":{"mday":"26","mon":"12"}}';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'England Holiday';

	//define USA holiday presets
		$x++;
		$array[$x]['default_setting_uuid'] = '3df036bb-ae96-4735-96da-a32e90b51940';
		$array[$x]['default_setting_category'] = 'time_conditions';
		$array[$x]['default_setting_subcategory'] = 'preset_usa';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = '{"new_years_day":{"mday":"1","mon":"1"}}';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'USA Holiday';
		$x++;
		$array[$x]['default_setting_uuid'] = '7a12b17c-67d9-439e-98fb-70039d27cf21';
		$array[$x]['default_setting_category'] = 'time_conditions';
		$array[$x]['default_setting_subcategory'] = 'preset_usa';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = '{"martin_luther_king_jr_day":{"wday":"2","mon":"1","mday":"15-21"}}';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'USA Holiday';
		$x++;
		$array[$x]['default_setting_uuid'] = '1ce5c94b-7181-4b33-92b0-2cf4a97f2fa3';
		$array[$x]['default_setting_category'] = 'time_conditions';
		$array[$x]['default_setting_subcategory'] = 'preset_usa';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = '{"presidents_day":{"wday":"2","mon":"2","mday":"15-21"}}';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'USA Holiday';
		$x++;
		$array[$x]['default_setting_uuid'] = '0957bbc4-60e8-44d1-b51d-943de4ee5b2f';
		$array[$x]['default_setting_category'] = 'time_conditions';
		$array[$x]['default_setting_subcategory'] = 'preset_usa';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = '{"memorial_day":{"mday":"25-31","wday":"2","mon":"5"}}';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'USA Holiday';
		$x++;
		$array[$x]['default_setting_uuid'] = '0aa94174-a339-47d6-b6ab-c264b3786074';
		$array[$x]['default_setting_category'] = 'time_conditions';
		$array[$x]['default_setting_subcategory'] = 'preset_usa';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = '{"independence_day":{"mday":"4","mon":"7"}}';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'USA Holiday';
		$x++;
		$array[$x]['default_setting_uuid'] = 'be512c08-029e-49a0-937d-1a62fc029609';
		$array[$x]['default_setting_category'] = 'time_conditions';
		$array[$x]['default_setting_subcategory'] = 'preset_usa';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = '{"labor_day":{"wday":"2","mon":"9","mday":"1-7"}}';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'USA Holiday';
		$x++;
		$array[$x]['default_setting_uuid'] = '261a0ea4-26a3-4261-95e5-888afd221ca0';
		$array[$x]['default_setting_category'] = 'time_conditions';
		$array[$x]['default_setting_subcategory'] = 'preset_usa';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = '{"columbus_day":{"wday":"2","mon":"10","mday":"8-14"}}';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'USA Holiday';
		$x++;
		$array[$x]['default_setting_uuid'] = '829d346b-b0ed-4690-8641-8ed01052e303';
		$array[$x]['default_setting_category'] = 'time_conditions';
		$array[$x]['default_setting_subcategory'] = 'preset_usa';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = '{"veterans_day":{"mday":"11","mon":"11"}}';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'USA Holiday';
		$x++;
		$array[$x]['default_setting_uuid'] = 'c1fdfebe-3544-4b01-8a83-d0fee8e9a47a';
		$array[$x]['default_setting_category'] = 'time_conditions';
		$array[$x]['default_setting_subcategory'] = 'preset_usa';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = '{"thanksgiving_day":{"wday":"5-6","mon":"11","mday":"22-28"}}';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'USA Holiday';
		$x++;
		$array[$x]['default_setting_uuid'] = '82e3eb39-27a4-4d70-8436-11059d3e51e7';
		$array[$x]['default_setting_category'] = 'time_conditions';
		$array[$x]['default_setting_subcategory'] = 'preset_usa';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = '{"christmas_day":{"mday":"25","mon":"12"}}';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'USA Holiday';

	//get an array of the default settings
		$sql = "select * from v_default_settings ";
		$sql .= "where default_setting_category = 'time_conditions' ";
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
