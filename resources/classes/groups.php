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
	Portions created by the Initial Developer are Copyright (C) 2016-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/


/**
 * groups class provides methods for add, delete groups, and add default groups
 *
 * @method string add
 * @method boolean delete
 * @method boolean defaults
 */
if (!class_exists('groups')) {
	class groups {

		public $db;

		/**
		 * Called when the object is created
		 */
		public function __construct() {
			//connect to the database if not connected
			if (!$this->db) {
				require_once "resources/classes/database.php";
				$database = new database;
				$database->connect();
				$this->db = $database->db;
			}
		}

		/**
		 * Called when there are no references to a particular object
		 * unset the variables used in the class
		 */
		public function __destruct() {
			foreach ($this as $key => $value) {
				unset($this->$key);
			}
		}

		/**
		 * add a group
		 */
		public function add() {
			$id = uuid();
			//return $id;
			return false;
		}

		/**
		 * delete a group
		 */
		public function delete($id) {
			return false;
		}

		/**
		 * add defaults groups
		 */
		public function defaults() {

			//if the are no groups add the default groups
				$sql = "select * from v_groups ";
				$sql .= "where domain_uuid is null ";
				$database = new database;
				$result = $database->select($sql, null, 'all');
				if (count($result) == 0) {
					$x = 0;
					$array['groups'][$x]['group_uuid'] = uuid();
					$array['groups'][$x]['domain_uuid'] = null;
					$array['groups'][$x]['group_name'] = 'superadmin';
					$array['groups'][$x]['group_level'] = '80';
					$array['groups'][$x]['group_description'] = 'Super Administrator Group';
					$array['groups'][$x]['group_protected'] = 'false';
					$x++;
					$array['groups'][$x]['group_uuid'] = uuid();
					$array['groups'][$x]['domain_uuid'] = null;
					$array['groups'][$x]['group_name'] = 'admin';
					$array['groups'][$x]['group_level'] = '50';
					$array['groups'][$x]['group_description'] = 'Administrator Group';
					$array['groups'][$x]['group_protected'] = 'false';
					$x++;
					$array['groups'][$x]['group_uuid'] = uuid();
					$array['groups'][$x]['domain_uuid'] = null;
					$array['groups'][$x]['group_name'] = 'user';
					$array['groups'][$x]['group_level'] = '30';
					$array['groups'][$x]['group_description'] = 'User Group';
					$array['groups'][$x]['group_protected'] = 'false';
					$x++;
					$array['groups'][$x]['group_uuid'] = uuid();
					$array['groups'][$x]['domain_uuid'] = null;
					$array['groups'][$x]['group_name'] = 'agent';
					$array['groups'][$x]['group_level'] = '20';
					$array['groups'][$x]['group_description'] = 'Call Center Agent Group';
					$array['groups'][$x]['group_protected'] = 'false';
					$x++;
					$array['groups'][$x]['group_uuid'] = uuid();
					$array['groups'][$x]['domain_uuid'] = null;
					$array['groups'][$x]['group_name'] = 'public';
					$array['groups'][$x]['group_level'] = '10';
					$array['groups'][$x]['group_description'] = 'Public Group';
					$array['groups'][$x]['group_protected'] = 'false';

					//add the temporary permissions
					$p = new permissions;
					$p->add("group_add", "temp");
					$p->add("group_edit", "temp");

					//save the data to the database
					$database = new database;
					$database->app_name = 'groups';
					$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
					$database->save($array);
					unset($array);

					//remove the temporary permission
					$p->delete("group_add", "temp");
					$p->delete("group_edit", "temp");
				}
				unset($result);

			//if there are no permissions listed in v_group_permissions then set the default permissions
				$sql = "select count(*) from v_group_permissions ";
				$sql .= "where domain_uuid is null ";
				$database = new database;
				$num_rows = $database->select($sql, null, 'column');
				if ($num_rows == 0) {
					//build the apps array
					$config_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/*/*/app_config.php");
					$x = 0;
					foreach ($config_list as &$config_path) {
						include($config_path);
						$x++;
					}

					//no permissions found add the defaults
					foreach($apps as $app) {
						if (is_array($app['permissions'])) foreach ($app['permissions'] as $row) {
							if (is_array($row['groups'])) foreach ($row['groups'] as $group) {
								$x++;
								$array['group_permissions'][$x]['group_permission_uuid'] = uuid();
								$array['group_permissions'][$x]['domain_uuid'] = null;
								$array['group_permissions'][$x]['permission_name'] = $row['name'];
								$array['group_permissions'][$x]['group_name'] = $group;
							}
						}
					}

					//add the temporary permissions
					$p = new permissions;
					$p->add("group_permission_add", "temp");
					$p->add("group_permission_edit", "temp");

					//save the data to the database
					$database = new database;
					$database->app_name = 'groups';
					$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
					$database->save($array);
					unset($array);

					//remove the temporary permission
					$p->delete("group_permission_add", "temp");
					$p->delete("group_permission_edit", "temp");
				}
		}
	} //end scripts class
}
/*
//example use
	$group = new groups;
	$group->defaults();
*/
?>
