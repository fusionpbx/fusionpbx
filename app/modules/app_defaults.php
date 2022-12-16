<?php
/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2008-2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//process one time
	if ($domains_processed == 1) {

		//add missing switch directories in default settings
			$obj = new switch_settings;
			$obj->settings();
			unset($obj);

		//add the module object
			$module = new modules;
			$module->db = $db;

		//add the access control list to the database
			$sql = "select * from v_modules ";
			$sql .= "where module_order is null ";
			$database = new database;
			$modules = $database->select($sql, null, 'all');
			if (is_array($modules) && @sizeof($modules) != 0) {
				foreach ($modules as $index => &$row) {
					//get the module details
						$mod = $module->info($row['module_name']);
					//update the module order
						$array['modules'][$index]['module_uuid'] = $row['module_uuid'];
						$array['modules'][$index]['module_order'] = $mod['module_order'];
				}
				if (is_array($array) && @sizeof($array) != 0) {
					$database = new database;
					$database->app_name = 'modules';
					$database->app_uuid = '5eb9cba1-8cb6-5d21-e36a-775475f16b5e';
					$database->save($array, false);
					unset($array);
				}
			}
			unset($sql, $modules, $index, $row);

		//use the module class to get the list of modules from the db and add any missing modules
			if (isset($_SESSION['switch']['mod']['dir'])) {
				$module->dir = $_SESSION['switch']['mod']['dir'];
				$module->get_modules();
				$module->synch();
				$module->xml();
				$msg = $module->msg;
			}
	}

?>
