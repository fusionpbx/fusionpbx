<?php

if ($domains_processed == 1) {

	//add the permissions
		$sql = "select * from v_permissions \n";
		$database = new database;
		$database_permissions = $database->select($sql, null, 'all');

	//get the $apps array from the installed apps from the core and mod directories
		$config_list = glob($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/*/*/app_config.php");
		$x = 0;
		foreach ($config_list as &$config_path) {
			include($config_path);
			$x++;
		}

	//restore default permissions
		$x = 0;
		foreach ($apps as $app) {
			if (is_array($app['permissions']) && @sizeof($app['permissions']) != 0) {
				foreach ($app['permissions'] as $app_permission) {
					//check if the permission is in the database
					$permission_found = false;
					if (is_array($database_permissions) && @sizeof($database_permissions) != 0) {
						foreach($database_permissions as $row) {
							if ($row['permission_name'] == $app_permission['name']) {
								$permission_found = true;
							}
						}
					}

					//add the permission to the array
					if (!$permission_found) {
						$array['permissions'][$x]['permission_uuid'] = uuid();
						$array['permissions'][$x]['permission_name'] = $app_permission['name'];
						$array['permissions'][$x]['application_name'] = $app['name'];
						$array['permissions'][$x]['application_uuid'] = $app['uuid'];
						$array['permissions'][$x]['insert_date'] = 'now()';
						$x++;
					}
				}
			}
		}

	//save the data to the database
		if (is_array($array) && @sizeof($array)) {
			//grant temporary permissions
				$p = new permissions;
				$p->add('permission_add', 'temp');

			//execute insert
				$database = new database;
				$database->app_name = 'permissions';
				$database->app_uuid = 'ce1498a0-46e2-487d-85de-4eec7122a984';
				$database->save($array, false);
				unset($array);

			//revoke temporary permissions
				$p->delete('permission_add', 'temp');
		}

}

?>
