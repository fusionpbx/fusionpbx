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
	Portions created by the Initial Developer are Copyright (C) 2016
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
				$sql = "SELECT * FROM v_groups ";
				$sql .= "WHERE domain_uuid is null ";
				$result = $this->db->query($sql)->fetch();
				$prep_statement = $this->db->prepare(check_sql($sql));
				if ($prep_statement) {
					$prep_statement->execute();
					$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
					if (count($result) == 0) {
						$x = 0;
						$tmp[$x]['group_name'] = 'superadmin';
						$tmp[$x]['group_description'] = 'Super Administrator Group';
						$tmp[$x]['group_protected'] = 'false';
						$x++;
						$tmp[$x]['group_name'] = 'admin';
						$tmp[$x]['group_description'] = 'Administrator Group';
						$tmp[$x]['group_protected'] = 'false';
						$x++;
						$tmp[$x]['group_name'] = 'user';
						$tmp[$x]['group_description'] = 'User Group';
						$tmp[$x]['group_protected'] = 'false';
						$x++;
						$tmp[$x]['group_name'] = 'public';
						$tmp[$x]['group_description'] = 'Public Group';
						$tmp[$x]['group_protected'] = 'false';
						$x++;
						$tmp[$x]['group_name'] = 'agent';
						$tmp[$x]['group_description'] = 'Call Center Agent Group';
						$tmp[$x]['group_protected'] = 'false';
						$this->db->beginTransaction();
						foreach($tmp as $row) {
							if (strlen($row['group_name']) > 0) {
								$sql = "insert into v_groups ";
								$sql .= "(";
								$sql .= "domain_uuid, ";
								$sql .= "group_uuid, ";
								$sql .= "group_name, ";
								$sql .= "group_description, ";
								$sql .= "group_protected ";
								$sql .= ")";
								$sql .= "values ";
								$sql .= "(";
								$sql .= "null, ";
								$sql .= "'".uuid()."', ";
								$sql .= "'".$row['group_name']."', ";
								$sql .= "'".$row['group_description']."', ";
								$sql .= "'".$row['group_protected']."' ";
								$sql .= ")";
								$this->db->exec($sql);
								unset($sql);
							}
						}
						$this->db->commit();
					}
					unset($prep_statement, $result);
				}

			//if there are no permissions listed in v_group_permissions then set the default permissions
				$sql = "select count(*) as count from v_group_permissions ";
				$sql .= "where domain_uuid is null ";
				$prep_statement = $this->db->prepare($sql);
				$prep_statement->execute();
				$result = $prep_statement->fetch(PDO::FETCH_ASSOC);
				unset ($prep_statement);
				if ($result['count'] == 0) {
					//build the apps array
						$config_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/*/*/app_config.php");
						$x = 0;
						foreach ($config_list as &$config_path) {
							include($config_path);
							$x++;
						}
					//no permissions found add the defaults
						$this->db->beginTransaction();
						foreach($apps as $app) {
							foreach ($app['permissions'] as $row) {
								foreach ($row['groups'] as $group) {
									//add the record
									$sql = "insert into v_group_permissions ";
									$sql .= "(";
									$sql .= "group_permission_uuid, ";
									$sql .= "domain_uuid, ";
									$sql .= "permission_name, ";
									$sql .= "group_name ";
									$sql .= ")";
									$sql .= "values ";
									$sql .= "(";
									$sql .= "'".uuid()."', ";
									$sql .= "null, ";
									$sql .= "'".$row['name']."', ";
									$sql .= "'".$group."' ";
									$sql .= ")";
									$this->db->exec($sql);
									unset($sql);
								}
							}
						}
						$this->db->commit();
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