<?php

if ($domains_processed == 1) {

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

	//grant temporary permissions
		$p = new permissions;
		$p->add('sofia_global_setting_add', 'temp');

	//execute insert
		$database = new database;
		$database->app_name = 'sofia_global_settings';
		$database->app_uuid = '240c25a3-a2cf-44ea-a300-0626eca5b945';
		$database->save($array);
		unset($array);

	//revoke temporary permissions
		$p->delete('sofia_global_setting_add', 'temp');
}

?>
