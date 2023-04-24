<?php

if ($domains_processed == 1) {

	//get all of the sofia global default settings
		$sql = "select * from v_sofia_global_settings \n";
		$database = new database;
		$sofia_global_settings = $database->select($sql, null, 'all');

	//build array
		$x = 0;
		$settings['sofia_global_settings'][$x]['sofia_global_setting_uuid'] = '9a0e83b3-e71c-4a9a-9f1c-680d32f756f8';
		$settings['sofia_global_settings'][$x]['global_setting_name'] = 'log-level';
		$settings['sofia_global_settings'][$x]['global_setting_value'] = '0';
		$settings['sofia_global_settings'][$x]['global_setting_enabled'] = 'true';
		$settings['sofia_global_settings'][$x]['global_setting_description'] = '';
		$x++;
		$settings['sofia_global_settings'][$x]['sofia_global_setting_uuid'] = 'c2aa551a-b6d2-49a6-b633-21b5b1ddd5df';
		$settings['sofia_global_settings'][$x]['global_setting_name'] = 'auto-restart';
		$settings['sofia_global_settings'][$x]['global_setting_value'] = 'true';
		$settings['sofia_global_settings'][$x]['global_setting_enabled'] = 'true';
		$settings['sofia_global_settings'][$x]['global_setting_description'] = '';
		$x++;
		$settings['sofia_global_settings'][$x]['sofia_global_setting_uuid'] = 'a9901c0c-efd8-4e66-9648-239566af576e';
		$settings['sofia_global_settings'][$x]['global_setting_name'] = 'debug-presence';
		$settings['sofia_global_settings'][$x]['global_setting_value'] = '0';
		$settings['sofia_global_settings'][$x]['global_setting_enabled'] = 'true';
		$settings['sofia_global_settings'][$x]['global_setting_description'] = '';
		$x++;
		$settings['sofia_global_settings'][$x]['sofia_global_setting_uuid'] = '31054912-3b07-422d-a109-b995fd8d67f7';
		$settings['sofia_global_settings'][$x]['global_setting_name'] = 'capture-server';
		$settings['sofia_global_settings'][$x]['global_setting_value'] = 'udp:127.0.0.1:9060';
		$settings['sofia_global_settings'][$x]['global_setting_enabled'] = 'false';
		$settings['sofia_global_settings'][$x]['global_setting_description'] = '';
		$x++;
		$settings['sofia_global_settings'][$x]['sofia_global_setting_uuid'] = 'b27af7db-4ba5-452b-a5ed-a922c8f201aa';
		$settings['sofia_global_settings'][$x]['global_setting_name'] = 'inbound-reg-in-new-thread';
		$settings['sofia_global_settings'][$x]['global_setting_value'] = 'true';
		$settings['sofia_global_settings'][$x]['global_setting_enabled'] = 'true';
		$settings['sofia_global_settings'][$x]['global_setting_description'] = '';
		$x++;
		$settings['sofia_global_settings'][$x]['sofia_global_setting_uuid'] = 'cd33b89f-55ef-4b47-833a-538dba70e27e';
		$settings['sofia_global_settings'][$x]['global_setting_name'] = 'max-reg-threads';
		$settings['sofia_global_settings'][$x]['global_setting_value'] = '8';
		$settings['sofia_global_settings'][$x]['global_setting_enabled'] = 'true';
		$settings['sofia_global_settings'][$x]['global_setting_description'] = '';

	//build an array of missing global settings
		$x = 0;
		foreach($settings['sofia_global_settings'] as $row) {
			$y = 0;
			$setting_found = false;
			if (is_array($sofia_global_settings) && @sizeof($sofia_global_settings) != 0) {
				foreach($sofia_global_settings as $field) {
					if ($row['sofia_global_setting_uuid'] == $field['sofia_global_setting_uuid']) {
						$setting_found = true;
						break;
					}
				}
			}

			//add the setting to the array
			if (!$setting_found) {
				$array['sofia_global_settings'][$x] = $row;
				$array['sofia_global_settings'][$x]['insert_date'] = 'now()';
				$x++;
			}
		}

	//add settings that are not in the database
		if (is_array($array) && count($array) > 0) {
			//grant temporary permissions
				$p = new permissions;
				$p->add('sofia_global_setting_add', 'temp');

			//execute insert
				$database = new database;
				$database->app_name = 'sofia_global_settings';
				$database->app_uuid = '240c25a3-a2cf-44ea-a300-0626eca5b945';
				$database->save($array, false);
				unset($array);

			//revoke temporary permissions
				$p->delete('sofia_global_setting_add', 'temp');
		}

}

?>
