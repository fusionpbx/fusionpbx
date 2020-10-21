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

//define the domain settings class
if (!class_exists('domain_settings')) {
	class domain_settings {

		/**
		 * declare private variables
		 */
		private $app_name;
		private $app_uuid;
		private $permission_prefix;
		private $list_page;
		private $table;
		private $uuid_prefix;
		private $toggle_field;
		private $toggle_values;

		/**
		 * declare public variables
		 */
		public $domain_uuid;
		public $domain_uuid_target;

		/**
		 * called when the object is created
		 */
		public function __construct() {

			//assign private variables
				$this->app_name = 'domain_settings';
				$this->app_uuid = 'b31e723a-bf70-670c-a49b-470d2a232f71';
				$this->permission_prefix = 'domain_setting_';
				$this->list_page = PROJECT_PATH."/core/domains/domain_edit.php?id=".urlencode($this->domain_uuid);
				$this->table = 'domain_settings';
				$this->uuid_prefix = 'domain_setting_';
				$this->toggle_field = 'domain_setting_enabled';
				$this->toggle_values = ['true','false'];

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
		 * delete records
		 */
		public function delete($records) {
			if (permission_exists($this->permission_prefix.'delete')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate('/core/domain_settings/domain_settings.php')) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->list_page);
						exit;
					}

				//delete multiple records
					if (is_array($records) && @sizeof($records) != 0) {

						//build the delete array
							foreach ($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];
									$array[$this->table][$x]['domain_uuid'] = $this->domain_uuid;
								}
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
		 * toggle records
		 */
		public function toggle($records) {
			if (permission_exists($this->permission_prefix.'edit')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate('/core/domain_settings/domain_settings.php')) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->list_page);
						exit;
					}

				//toggle the checked records
					if (is_array($records) && @sizeof($records) != 0) {

						//get current toggle state
							foreach ($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->uuid_prefix."uuid as uuid, ".$this->toggle_field." as toggle from v_".$this->table." ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$sql .= "and ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
								$parameters['domain_uuid'] = $this->domain_uuid;
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
							if (is_array($states) && @sizeof($states) != 0) {
								$x = 0;
								foreach ($states as $uuid => $state) {
									$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $uuid;
									$array[$this->table][$x][$this->toggle_field] = $state == $this->toggle_values[0] ? $this->toggle_values[1] : $this->toggle_values[0];
									$x++;
								}
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
		 * copy records
		 */
		public function copy($records) {
			if (permission_exists($this->permission_prefix.'add') && permission_exists('domain_select') && count($_SESSION['domains']) > 1) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate('/core/domain_settings/domain_settings.php')) {
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
							if (is_array($uuids) && @sizeof($uuids) != 0) {

								$settings_copied = 0;

								//to different domain
									if (is_uuid($this->domain_uuid_target)) {

										foreach ($uuids as $uuid) {

											//get domain setting from db
											$sql = "select * from v_domain_settings ";
											$sql .= "where domain_setting_uuid = :domain_setting_uuid ";
											$parameters['domain_setting_uuid'] = $uuid;
											$database = new database;
											$row = $database->select($sql, $parameters, 'row');
											if (is_array($row) && sizeof($row) != 0) {
												$domain_setting_category = $row["domain_setting_category"];
												$domain_setting_subcategory = $row["domain_setting_subcategory"];
												$domain_setting_name = $row["domain_setting_name"];
												$domain_setting_value = $row["domain_setting_value"];
												$domain_setting_order = $row["domain_setting_order"];
												$domain_setting_enabled = $row["domain_setting_enabled"];
												$domain_setting_description = $row["domain_setting_description"];
											}
											unset($sql, $parameters, $row);

											//set a random password for http_auth_password
											if ($domain_setting_subcategory == "http_auth_password") {
												$domain_setting_value = generate_password();
											}

											// check if exists
											$sql = "select domain_setting_uuid from v_domain_settings ";
											$sql .= "where domain_uuid = :domain_uuid ";
											$sql .= "and domain_setting_category = :domain_setting_category ";
											$sql .= "and domain_setting_subcategory = :domain_setting_subcategory ";
											$sql .= "and domain_setting_name = :domain_setting_name ";
											$sql .= "and domain_setting_name <> 'array' ";
											$parameters['domain_uuid'] = $this->domain_uuid_target;
											$parameters['domain_setting_category'] = $domain_setting_category;
											$parameters['domain_setting_subcategory'] = $domain_setting_subcategory;
											$parameters['domain_setting_name'] = $domain_setting_name;
											$database = new database;
											$target_domain_setting_uuid = $database->select($sql, $parameters, 'column');

											$action = is_uuid($target_domain_setting_uuid) ? 'update' : 'add';
											unset($sql, $parameters);

											// fix null
											$domain_setting_order = $domain_setting_order != '' ? $domain_setting_order : null;

											//begin array
											$array['domain_settings'][0]['domain_uuid'] = $this->domain_uuid_target;
											$array['domain_settings'][0]['domain_setting_category'] = $domain_setting_category;
											$array['domain_settings'][0]['domain_setting_subcategory'] = $domain_setting_subcategory;
											$array['domain_settings'][0]['domain_setting_name'] = $domain_setting_name;
											$array['domain_settings'][0]['domain_setting_value'] = $domain_setting_value;
											$array['domain_settings'][0]['domain_setting_order'] = $domain_setting_order;
											$array['domain_settings'][0]['domain_setting_enabled'] = $domain_setting_enabled ?: 0;
											$array['domain_settings'][0]['domain_setting_description'] = $domain_setting_description;

											//insert
											if ($action == "add" && permission_exists("domain_setting_add")) {
												$array['domain_settings'][0]['domain_setting_uuid'] = uuid();
											}
											//update
											if ($action == "update" && permission_exists('domain_setting_edit')) {
												$array['domain_settings'][0]['domain_setting_uuid'] = $target_domain_setting_uuid;
											}

											//execute
											if (is_uuid($array['domain_settings'][0]['domain_setting_uuid'])) {
												$database = new database;
												$database->app_name = 'domain_settings';
												$database->app_uuid = 'b31e723a-bf70-670c-a49b-470d2a232f71';
												$database->save($array);
												unset($array);

												$settings_copied++;
											}

										} //foreach
									} //if

								//to default settings
									else if ($this->domain_uuid_target == 'default') {
										foreach ($uuids as $uuid) {

											//get domain setting from db
											$sql = "select * from v_domain_settings ";
											$sql .= "where domain_setting_uuid = :domain_setting_uuid ";
											$parameters['domain_setting_uuid'] = $uuid;
											$database = new database;
											$row = $database->select($sql, $parameters, 'row');
											if (is_array($row) && sizeof($row) != 0) {
												$domain_setting_category = $row["domain_setting_category"];
												$domain_setting_subcategory = $row["domain_setting_subcategory"];
												$domain_setting_name = $row["domain_setting_name"];
												$domain_setting_value = $row["domain_setting_value"];
												$domain_setting_order = $row["domain_setting_order"];
												$domain_setting_enabled = $row["domain_setting_enabled"];
												$domain_setting_description = $row["domain_setting_description"];
											}
											unset($sql, $parameters, $row);

											//set a random password for http_auth_password
											if ($domain_setting_subcategory == "http_auth_password") {
												$domain_setting_value = generate_password();
											}

											// check if exists
											$sql = "select default_setting_uuid from v_default_settings ";
											$sql .= "where default_setting_category = :default_setting_category ";
											$sql .= "and default_setting_subcategory = :default_setting_subcategory ";
											$sql .= "and default_setting_name = :default_setting_name ";
											$sql .= "and default_setting_name <> 'array' ";
											$parameters['default_setting_category'] = $domain_setting_category;
											$parameters['default_setting_subcategory'] = $domain_setting_subcategory;
											$parameters['default_setting_name'] = $domain_setting_name;
											$database = new database;
											$target_default_setting_uuid = $database->select($sql, $parameters, 'column');

											$action = is_uuid($target_default_setting_uuid) ? 'update' : 'add';
											unset($sql, $parameters);

											// fix null
											$domain_setting_order = $domain_setting_order != '' ? $domain_setting_order : null;

											//begin array
											$array['default_settings'][0]['default_setting_category'] = $domain_setting_category;
											$array['default_settings'][0]['default_setting_subcategory'] = $domain_setting_subcategory;
											$array['default_settings'][0]['default_setting_name'] = $domain_setting_name;
											$array['default_settings'][0]['default_setting_value'] = $domain_setting_value;
											$array['default_settings'][0]['default_setting_order'] = $domain_setting_order;
											$array['default_settings'][0]['default_setting_enabled'] = $domain_setting_enabled;
											$array['default_settings'][0]['default_setting_description'] = $domain_setting_description;

											//insert
											if ($action == "add" && permission_exists("default_setting_add")) {
												$array['default_settings'][0]['default_setting_uuid'] = uuid();
											}
											//update
											if ($action == "update" && permission_exists('default_setting_edit')) {
												$array['default_settings'][0]['default_setting_uuid'] = $target_default_setting_uuid;
											}

											//execute
											if (is_uuid($array['default_settings'][0]['default_setting_uuid'])) {
												$database = new database;
												$database->app_name = 'domain_settings';
												$database->app_uuid = 'b31e723a-bf70-670c-a49b-470d2a232f71';
												$database->save($array);
												unset($array);

												$settings_copied++;
											}

										} //foreach
									} //if

								// set message
									message::add($text['message-copy'].": ".escape($settings_copied));

							}

					}

					unset($records);
			}
		} //method

	} //class
}

?>