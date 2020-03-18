<?php

if ($domains_processed == 1) {

	//add the permissions
		$sql = "delete from v_permissions";
		$database = new database;
		$database->execute($sql, null);

	//get the $apps array from the installed apps from the core and mod directories
		$config_list = glob($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/*/*/app_config.php");
		$x = 0;
		foreach ($config_list as &$config_path) {
			include($config_path);
			$x++;
		}

	//restore default permissions
		$x = 0;
		foreach ($apps as $row) {
			if (is_array($row['permissions']) && @sizeof($row['permissions']) != 0) {
				foreach ($row['permissions'] as $permission) {
					$array['permissions'][$x]['permission_uuid'] = uuid();
					$array['permissions'][$x]['permission_name'] = $permission['name'];
					$array['permissions'][$x]['application_name'] = $row['name'];
					$array['permissions'][$x]['application_uuid'] = $row['uuid'];
					$x++;
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
				$database->save($array);
				unset($array);

			//revoke temporary permissions
				$p->delete('permission_add', 'temp');
		}

}

?>
