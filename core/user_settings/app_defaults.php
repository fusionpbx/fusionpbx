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