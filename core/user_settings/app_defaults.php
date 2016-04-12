<?php

if ($domains_processed == 1) {

	//define array of settings
		$x = 0;
		$array[$x]['default_setting_category'] = 'login';
		$array[$x]['default_setting_subcategory'] = 'password_reset_key';
		$array[$x]['default_setting_name'] = 'text';
		$array[$x]['default_setting_value'] = generate_password('20', '4');
		$array[$x]['default_setting_enabled'] = 'false';
		$array[$x]['default_setting_description'] = 'Display a Reset Password link on the login box (requires smtp_host be defined).';
		$x++;
		$array[$x]['default_setting_category'] = 'login';
		$array[$x]['default_setting_subcategory'] = 'domain_name_visible';
		$array[$x]['default_setting_name'] = 'boolean';
		$array[$x]['default_setting_value'] = 'true';
		$array[$x]['default_setting_enabled'] = 'false';
		$array[$x]['default_setting_description'] = 'Displays a domain input or select box (if domain_name array defined) on the login box.';
		$x++;
		$array[$x]['default_setting_category'] = 'login';
		$array[$x]['default_setting_subcategory'] = 'domain_name';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = 'pbx1.yourdomain.com';
		$array[$x]['default_setting_enabled'] = 'false';
		$array[$x]['default_setting_description'] = 'Domain select option displayed on the login box.';
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

	//define array of dashboard settings
		$x = 0;
		$array[$x]['default_setting_category'] = 'dashboard';
		$array[$x]['default_setting_subcategory'] = 'admin';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = 'voicemail';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Enable Dashboard Voicemail block for users in the admin group.';
		$x++;
		$array[$x]['default_setting_category'] = 'dashboard';
		$array[$x]['default_setting_subcategory'] = 'admin';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = 'missed';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Enable Dashboard Missed Calls block for users in the admin group.';
		$x++;
		$array[$x]['default_setting_category'] = 'dashboard';
		$array[$x]['default_setting_subcategory'] = 'admin';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = 'recent';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Enable Dashboard Recent Calls block for users in the admin group.';
		$x++;
		$array[$x]['default_setting_category'] = 'dashboard';
		$array[$x]['default_setting_subcategory'] = 'admin';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = 'limits';
		$array[$x]['default_setting_enabled'] = 'false';
		$array[$x]['default_setting_description'] = 'Enable Dashboard Domain Limits block for users in the admin group.';
		$x++;
		$array[$x]['default_setting_category'] = 'dashboard';
		$array[$x]['default_setting_subcategory'] = 'admin';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = 'counts';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Enable Dashboard Domain Counts block for users in the admin group.';
		$x++;
		$array[$x]['default_setting_category'] = 'dashboard';
		$array[$x]['default_setting_subcategory'] = 'admin';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = 'call_routing';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Enable Dashboard Call Routing controls for users in the admin group.';
		$x++;
		$array[$x]['default_setting_category'] = 'dashboard';
		$array[$x]['default_setting_subcategory'] = 'admin';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = 'ring_groups';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Enable Dashboard Ring Group Forwarding controls for users in the admin group.';
		$x++;
		$array[$x]['default_setting_category'] = 'dashboard';
		$array[$x]['default_setting_subcategory'] = 'superadmin';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = 'voicemail';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Enable Dashboard Voicemail block for users in the superadmin group.';
		$x++;
		$array[$x]['default_setting_category'] = 'dashboard';
		$array[$x]['default_setting_subcategory'] = 'superadmin';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = 'missed';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Enable Dashboard Missed Calls block for users in the superadmin group.';
		$x++;
		$array[$x]['default_setting_category'] = 'dashboard';
		$array[$x]['default_setting_subcategory'] = 'superadmin';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = 'recent';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Enable Dashboard Recent Calls block for users in the superadmin group.';
		$x++;
		$array[$x]['default_setting_category'] = 'dashboard';
		$array[$x]['default_setting_subcategory'] = 'superadmin';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = 'limits';
		$array[$x]['default_setting_enabled'] = 'false';
		$array[$x]['default_setting_description'] = 'Enable Dashboard Domain Limits block for users in the superadmin group.';
		$x++;
		$array[$x]['default_setting_category'] = 'dashboard';
		$array[$x]['default_setting_subcategory'] = 'superadmin';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = 'counts';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Enable Dashboard System Counts block for users in the superadmin group.';
		$x++;
		$array[$x]['default_setting_category'] = 'dashboard';
		$array[$x]['default_setting_subcategory'] = 'superadmin';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = 'system';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Enable Dashboard System Status block for users in the superadmin group.';
		$x++;
		$array[$x]['default_setting_category'] = 'dashboard';
		$array[$x]['default_setting_subcategory'] = 'superadmin';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = 'call_routing';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Enable Dashboard Call Routing controls for users in the superadmin group.';
		$x++;
		$array[$x]['default_setting_category'] = 'dashboard';
		$array[$x]['default_setting_subcategory'] = 'superadmin';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = 'ring_groups';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Enable Dashboard Ring Group Forwarding controls for users in the superadmin group.';
		$x++;
		$array[$x]['default_setting_category'] = 'dashboard';
		$array[$x]['default_setting_subcategory'] = 'user';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = 'voicemail';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Enable Dashboard Voicemail block for users in the users group.';
		$x++;
		$array[$x]['default_setting_category'] = 'dashboard';
		$array[$x]['default_setting_subcategory'] = 'user';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = 'missed';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Enable Dashboard Missed Calls block for users in the users group.';
		$x++;
		$array[$x]['default_setting_category'] = 'dashboard';
		$array[$x]['default_setting_subcategory'] = 'user';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = 'recent';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Enable Dashboard Recent Calls block for users in the users group.';
		$x++;
		$array[$x]['default_setting_category'] = 'dashboard';
		$array[$x]['default_setting_subcategory'] = 'user';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = 'call_routing';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Enable Dashboard Call Routing controls for users in the users group.';
		$x++;
		$array[$x]['default_setting_category'] = 'dashboard';
		$array[$x]['default_setting_subcategory'] = 'user';
		$array[$x]['default_setting_name'] = 'array';
		$array[$x]['default_setting_value'] = 'ring_groups';
		$array[$x]['default_setting_enabled'] = 'true';
		$array[$x]['default_setting_description'] = 'Enable Dashboard Ring Group Forwarding controls for users in the users group.';

	//get an array of the default settings
		$sql = "select * from v_default_settings ";
		$sql .= "where default_setting_category = 'dashboard' ";
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