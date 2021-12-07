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
	Portions created by the Initial Developer are Copyright (C) 2016-2021
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

/**
 * groups class provides methods for add, delete groups, and add default groups
 *
 * @method null delete
 * @method null toggle
 * @method null copy
 */
if (!class_exists('groups')) {
	class groups {

		/**
		* declare the variables
		*/
		private $app_name;
		private $app_uuid;
		private $name;
		private $table;
		private $toggle_field;
		private $toggle_values;
		private $location;
		public  $group_uuid;

		/**
		 * called when the object is created
		 */
		public function __construct() {
			//assign the variables
				$this->app_name = 'groups';
				$this->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
		}

		/**
		 * called when there are no references to a particular object
		 * unset the variables used in the class
		 */
		public function __destruct() {
			foreach ($this as $key => $value) {
				unset($this->$key);
			}
		}

		/**
		 * delete rows from the database
		 */
		public function delete($records) {
			//assign the variables
				$this->name = 'group';
				$this->table = 'groups';
				$this->location = 'groups.php';

			if (permission_exists($this->name.'_delete')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->location);
						exit;
					}

				//delete multiple records
					if (is_array($records) && @sizeof($records) != 0) {
						//build array of checked records
							foreach ($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$array[$this->table][$x][$this->name.'_uuid'] = $record['uuid'];
									$array['group_permissions'][$x][$this->name.'_uuid'] = $record['uuid'];
								}
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {

								//grant temporary permissions
									$p = new permissions;
									$p->add('group_permission_delete', 'temp');

								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
									unset($array);

								//revoke temporary permissions
									$p->delete('group_permission_delete', 'temp');

								//set message
									message::add($text['message-delete']);
							}
							unset($records);
					}
			}
		}

		public function delete_members($records) {
			//assign the variables
				$this->name = 'group_member';
				$this->table = 'user_groups';
				$this->location = 'group_members.php?group_uuid='.$this->group_uuid;

			if (permission_exists($this->name.'_delete')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->location);
						exit;
					}

				//delete multiple records
					if (is_array($records) && @sizeof($records) != 0) {
						//build array of checked records
							foreach ($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$array[$this->table][$x]['user_uuid'] = $record['uuid'];
									$array[$this->table][$x]['group_uuid'] = $this->group_uuid;
								}
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {

								//grant temporary permissions
									$p = new permissions;
									$p->add('user_group_delete', 'temp');

								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
									unset($array);

								//revoke temporary permissions
									$p->delete('user_group_delete', 'temp');

								//set message
									message::add($text['message-delete']);
							}
							unset($records);
					}
			}
		}

		/**
		 * toggle a field between two values
		 */
		public function toggle($records) {
			//assign the variables
				$this->name = 'group';
				$this->table = 'groups';
				$this->toggle_field = 'group_protected';
				$this->toggle_values = ['true','false'];
				$this->location = 'groups.php';

			if (permission_exists($this->name.'_edit')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->location);
						exit;
					}

				//toggle the checked records
					if (is_array($records) && @sizeof($records) != 0) {
						//get current toggle state
							foreach($records as $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->name."_uuid as uuid, ".$this->toggle_field." as toggle from v_".$this->table." ";
								$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
								$sql .= "and ".$this->name."_uuid in (".implode(', ', $uuids).") ";
								$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										$states[$row['uuid']] = $row['toggle'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//build update array
							$x = 0;
							foreach($states as $uuid => $state) {
								//create the array
									$array[$this->table][$x][$this->name.'_uuid'] = $uuid;
									$array[$this->table][$x][$this->toggle_field] = $state == $this->toggle_values[0] ? $this->toggle_values[1] : $this->toggle_values[0];

								//increment the id
									$x++;
							}

						//save the changes
							if (is_array($array) && @sizeof($array) != 0) {
								//save the array
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->save($array);
									unset($array);

								//set message
									message::add($text['message-toggle']);
							}
							unset($records, $states);
					}
			}
		}

		/**
		 * copy rows from the database
		 */
		public function copy($records) {
			//assign the variables
				$this->name = 'group';
				$this->table = 'groups';
				$this->location = 'groups.php';

			if (permission_exists($this->name.'_add')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->location);
						exit;
					}

				//copy the checked records
					if (is_array($records) && @sizeof($records) != 0) {

						//get checked records
							foreach($records as $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}

						//create the array from existing data
							if (is_array($uuids) && @sizeof($uuids) != 0) {

								//primary table
									$sql = "select * from v_".$this->table." ";
									$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
									$sql .= "and ".$this->name."_uuid in (".implode(', ', $uuids).") ";
									$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
									$database = new database;
									$rows = $database->select($sql, $parameters, 'all');
									if (is_array($rows) && @sizeof($rows) != 0) {
										$y = 0;
										foreach ($rows as $x => $row) {
											$primary_uuid = uuid();

											//copy data
												$array[$this->table][$x] = $row;

											//overwrite
												$array[$this->table][$x][$this->name.'_uuid'] = $primary_uuid;
												$array[$this->table][$x][$this->name.'_description'] = trim($row[$this->name.'_description']).' ('.$text['label-copy'].')';

											//permissions sub table
												$sql_2 = "select * from v_group_permissions where group_uuid = :group_uuid";
												$parameters_2['group_uuid'] = $row['group_uuid'];
												$database = new database;
												$rows_2 = $database->select($sql_2, $parameters_2, 'all');
												if (is_array($rows_2) && @sizeof($rows_2) != 0) {
													foreach ($rows_2 as $row_2) {

														//copy data
															$array['group_permissions'][$y] = $row_2;

														//overwrite
															$array['group_permissions'][$y]['group_permission_uuid'] = uuid();
															$array['group_permissions'][$y]['group_uuid'] = $primary_uuid;

														//increment
															$y++;

													}
												}
												unset($sql_2, $parameters_2, $rows_2, $row_2);
										}
									}
									unset($sql, $parameters, $rows, $row);
							}

						//save the changes and set the message
							if (is_array($array) && @sizeof($array) != 0) {
								//save the array
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->save($array);
									unset($array);

								//set message
									message::add($text['message-copy']);
							}
							unset($records);
					}
			}
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
					$group_uuids[$array['groups'][$x]['group_name']] = $array['groups'][$x]['group_uuid'];
					$x++;
					$array['groups'][$x]['group_uuid'] = uuid();
					$array['groups'][$x]['domain_uuid'] = null;
					$array['groups'][$x]['group_name'] = 'admin';
					$array['groups'][$x]['group_level'] = '50';
					$array['groups'][$x]['group_description'] = 'Administrator Group';
					$array['groups'][$x]['group_protected'] = 'false';
					$group_uuids[$array['groups'][$x]['group_name']] = $array['groups'][$x]['group_uuid'];
					$x++;
					$array['groups'][$x]['group_uuid'] = uuid();
					$array['groups'][$x]['domain_uuid'] = null;
					$array['groups'][$x]['group_name'] = 'user';
					$array['groups'][$x]['group_level'] = '30';
					$array['groups'][$x]['group_description'] = 'User Group';
					$array['groups'][$x]['group_protected'] = 'false';
					$group_uuids[$array['groups'][$x]['group_name']] = $array['groups'][$x]['group_uuid'];
					$x++;
					$array['groups'][$x]['group_uuid'] = uuid();
					$array['groups'][$x]['domain_uuid'] = null;
					$array['groups'][$x]['group_name'] = 'agent';
					$array['groups'][$x]['group_level'] = '20';
					$array['groups'][$x]['group_description'] = 'Call Center Agent Group';
					$array['groups'][$x]['group_protected'] = 'false';
					$group_uuids[$array['groups'][$x]['group_name']] = $array['groups'][$x]['group_uuid'];
					$x++;
					$array['groups'][$x]['group_uuid'] = uuid();
					$array['groups'][$x]['domain_uuid'] = null;
					$array['groups'][$x]['group_name'] = 'public';
					$array['groups'][$x]['group_level'] = '10';
					$array['groups'][$x]['group_description'] = 'Public Group';
					$array['groups'][$x]['group_protected'] = 'false';
					$group_uuids[$array['groups'][$x]['group_name']] = $array['groups'][$x]['group_uuid'];

					//add the temporary permissions
					$p = new permissions;
					$p->add("group_add", "temp");
					$p->add("group_edit", "temp");

					//save the data to the database
					$database = new database;
					$database->app_name = $this->app_name;
					$database->app_uuid = $this->app_uuid;
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
					$config_list = glob($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/*/*/app_config.php");
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
								$array['group_permissions'][$x]['permission_protected'] = 'false';
								$array['group_permissions'][$x]['permission_assigned'] = 'true';
								$array['group_permissions'][$x]['group_name'] = $group;
								$array['group_permissions'][$x]['group_uuid'] = $group_uuids[$group];
							}
						}
					}
					unset($group_uuids);

					//add the temporary permissions
					$p = new permissions;
					$p->add("group_permission_add", "temp");
					$p->add("group_permission_edit", "temp");

					//save the data to the database
					$database = new database;
					$database->app_name = $this->app_name;
					$database->app_uuid = $this->app_uuid;
					$database->save($array);
					unset($array);

					//remove the temporary permission
					$p->delete("group_permission_add", "temp");
					$p->delete("group_permission_edit", "temp");
				}
		}

	}
}

?>
