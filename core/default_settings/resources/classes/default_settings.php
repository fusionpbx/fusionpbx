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
	Portions created by the Initial Developer are Copyright (C) 2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

/**
 * default_settings class
 *
 * @method null delete
 * @method null toggle
 * @method null copy
 */
if (!class_exists('default_settings')) {
	class default_settings {

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
		public $domain_uuid;

		/**
		 * called when the object is created
		 */
		public function __construct() {
			//assign the variables
				$this->app_name = 'default_settings';
				$this->app_uuid = '2c2453c0-1bea-4475-9f44-4d969650de09';
				$this->name = 'default_setting';
				$this->table = 'default_settings';
				$this->toggle_field = 'default_setting_enabled';
				$this->toggle_values = ['true','false'];
				$this->location = 'default_settings.php';
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
						//build the delete array
							$x = 0;
							foreach ($records as $record) {
								//add to the array
									if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
										$array[$this->table][$x][$this->name.'_uuid'] = $record['uuid'];
									}

								//increment the id
									$x++;
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {
								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
									unset($array);

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
								$sql .= "where ".$this->name."_uuid in (".implode(', ', $uuids).") ";
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
									$uuids[] = $record['uuid'];
								}
							}

						//copy settings
							if (is_uuid($this->domain_uuid) && is_array($uuids) && sizeof($uuids) > 0) {
								$settings_copied = 0;
								foreach ($uuids as $x => $uuid) {

									// get default setting from db
									$sql = "select * from v_default_settings ";
									$sql .= "where default_setting_uuid = :default_setting_uuid ";
									$parameters['default_setting_uuid'] = $uuid;
									$database = new database;
									$row = $database->select($sql, $parameters, 'row');
									if (is_array($row) && sizeof($row) != 0) {
										$default_setting_category = $row["default_setting_category"];
										$default_setting_subcategory = $row["default_setting_subcategory"];
										$default_setting_name = $row["default_setting_name"];
										$default_setting_value = $row["default_setting_value"];
										$default_setting_order = $row["default_setting_order"];
										$default_setting_enabled = $row["default_setting_enabled"];
										$default_setting_description = $row["default_setting_description"];
									}
									unset($sql, $parameters, $row);

									//set a random password for http_auth_password
									if ($default_setting_subcategory == "http_auth_password") {
										$default_setting_value = generate_password();
									}

									// check if exists
									$sql = "select domain_setting_uuid from v_domain_settings ";
									$sql .= "where domain_uuid = :domain_uuid ";
									$sql .= "and domain_setting_category = :domain_setting_category ";
									$sql .= "and domain_setting_subcategory = :domain_setting_subcategory ";
									$sql .= "and domain_setting_name = :domain_setting_name ";
									$sql .= "and domain_setting_name <> 'array' ";
									$parameters['domain_uuid'] = $this->domain_uuid;
									$parameters['domain_setting_category'] = $default_setting_category;
									$parameters['domain_setting_subcategory'] = $default_setting_subcategory;
									$parameters['domain_setting_name'] = $default_setting_name;
									$database = new database;
									$target_domain_setting_uuid = $database->select($sql, $parameters, 'column');
									$message = $database->message;

									$action = is_uuid($target_domain_setting_uuid) ? 'update' : 'add';
									unset($sql, $parameters);

									// fix null
									$default_setting_order = $default_setting_order != '' ? $default_setting_order : null;

									//begin array
									$array['domain_settings'][$x]['domain_uuid'] = $this->domain_uuid;
									$array['domain_settings'][$x]['domain_setting_category'] = $default_setting_category;
									$array['domain_settings'][$x]['domain_setting_subcategory'] = $default_setting_subcategory;
									$array['domain_settings'][$x]['domain_setting_name'] = $default_setting_name;
									$array['domain_settings'][$x]['domain_setting_value'] = $default_setting_value;
									$array['domain_settings'][$x]['domain_setting_order'] = $default_setting_order;
									$array['domain_settings'][$x]['domain_setting_enabled'] = $default_setting_enabled ?: 0;
									$array['domain_settings'][$x]['domain_setting_description'] = $default_setting_description;

									//insert
									if ($action == "add" && permission_exists("domain_select") && permission_exists("domain_setting_add") && count($_SESSION['domains']) > 1) {
										$array['domain_settings'][$x]['domain_setting_uuid'] = uuid();
									}
									//update
									if ($action == "update" && permission_exists('domain_setting_edit')) {
										$array['domain_settings'][$x]['domain_setting_uuid'] = $target_domain_setting_uuid;
									}

									//execute
									if (is_uuid($array['domain_settings'][$x]['domain_setting_uuid'])) {
										$database = new database;
										$database->app_name = $this->table;
										$database->app_uuid = $this->app_uuid;
										$database->save($array);
										$message = $database->message;
										unset($array);

										$settings_copied++;
									}

								} // foreach
							}

						//set message
							if ($settings_copied != 0) {
								message::add($text['message-copy']);
							}
							unset($records);
					}
			}
		} //method

	} //class
}

?>