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
			$prep_statement = $db->prepare($sql);
			if ($prep_statement) {
				$prep_statement->execute();
				$modules = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
				foreach ($modules as &$row) {
					//get the module details
						$mod = $module->info($row['module_name']);
					//update the module order
						$sql = "update v_modules set ";
						$sql .= "module_order = '".$mod['module_order']."' ";
						$sql .= "where module_uuid = '".$row['module_uuid']."' ";
						$db->exec(check_sql($sql));
						unset($sql);
				}
			}

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