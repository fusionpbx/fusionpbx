<?php

if ($domains_processed == 1) {
	
	//get all of the sofia global default settings
		$sql = "select sofia_global_setting_uuid ";
		$sql .= "from v_sofia_global_settings \n";
		$database = new database;
		$sofia_global_settings = $database->select($sql, null, 'all');

	//build array
		$x = 0;
		$array['sofia_global_settings'][$x]['sofia_global_setting_uuid'] = '9a0e83b3-e71c-4a9a-9f1c-680d32f756f8';
		$array['sofia_global_settings'][$x]['global_setting_name'] = 'log-level';
		$array['sofia_global_settings'][$x]['global_setting_value'] = '0';
		$array['sofia_global_settings'][$x]['global_setting_enabled'] = 'true';
		$array['sofia_global_settings'][$x]['global_setting_description'] = '';
		$x++;
		$array['sofia_global_settings'][$x]['sofia_global_setting_uuid'] = 'c2aa551a-b6d2-49a6-b633-21b5b1ddd5df';
		$array['sofia_global_settings'][$x]['global_setting_name'] = 'auto-restart';
		$array['sofia_global_settings'][$x]['global_setting_value'] = 'true';
		$array['sofia_global_settings'][$x]['global_setting_enabled'] = 'true';
		$array['sofia_global_settings'][$x]['global_setting_description'] = '';
		$x++;
		$array['sofia_global_settings'][$x]['sofia_global_setting_uuid'] = 'a9901c0c-efd8-4e66-9648-239566af576e';
		$array['sofia_global_settings'][$x]['global_setting_name'] = 'debug-presence';
		$array['sofia_global_settings'][$x]['global_setting_value'] = '0';
		$array['sofia_global_settings'][$x]['global_setting_enabled'] = 'true';
		$array['sofia_global_settings'][$x]['global_setting_description'] = '';
		$x++;
		$array['sofia_global_settings'][$x]['sofia_global_setting_uuid'] = '31054912-3b07-422d-a109-b995fd8d67f7';
		$array['sofia_global_settings'][$x]['global_setting_name'] = 'capture-server';
		$array['sofia_global_settings'][$x]['global_setting_value'] = 'udp:127.0.0.1:9060';
		$array['sofia_global_settings'][$x]['global_setting_enabled'] = 'false';
		$array['sofia_global_settings'][$x]['global_setting_description'] = '';
		$x++;
		$array['sofia_global_settings'][$x]['sofia_global_setting_uuid'] = 'b27af7db-4ba5-452b-a5ed-a922c8f201aa';
		$array['sofia_global_settings'][$x]['global_setting_name'] = 'inbound-reg-in-new-thread';
		$array['sofia_global_settings'][$x]['global_setting_value'] = 'true';
		$array['sofia_global_settings'][$x]['global_setting_enabled'] = 'true';
		$array['sofia_global_settings'][$x]['global_setting_description'] = '';
		$x++;
		$array['sofia_global_settings'][$x]['sofia_global_setting_uuid'] = 'cd33b89f-55ef-4b47-833a-538dba70e27e';
		$array['sofia_global_settings'][$x]['global_setting_name'] = 'max-reg-threads';
		$array['sofia_global_settings'][$x]['global_setting_value'] = '8';
		$array['sofia_global_settings'][$x]['global_setting_enabled'] = 'true';
		$array['sofia_global_settings'][$x]['global_setting_description'] = '';

	//removes settings from the array that are already in the database
		$x = 0;
		foreach($sofia_global_settings as $row) {
			$x = 0;
			foreach ($array['sofia_global_settings'] as $sub_row) {
				if ($row['sofia_global_setting_uuid'] == $sub_row['sofia_global_setting_uuid']) {
					unset($array['sofia_global_settings'][$x]);
				}
				$x++;
			}
		}

	//add settings that are not in the database
		if (count($array['sofia_global_settings']) > 0) {
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
