<?php

if ($domains_processed == 1) {

	//define array of settings
		$x = 0;
		$array[$x]['default_setting_category'] = 'login';
		$array[$x]['default_setting_subcategory'] = 'password_reset_key';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = generate_password('20', '4');
		$array[$x]['default_setting_enabled'] = 'false';
		$array[$x]['default_setting_description'] = 'Reset Password link visible on login page when populated and enabled.';
		$x++;

	//iterate and add each, if necessary
		foreach ($array as $index => $default_settings) {

		//add theme default settings
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

}

?>