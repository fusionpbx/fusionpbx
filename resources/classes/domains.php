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
	Portions created by the Initial Developer are Copyright (C) 2008-2022
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	sreis
*/


/**
 * domains class
 *
 * @method null delete
 * @method null toggle
 * @method null copy
 */
if (!class_exists('domains')) {
	class domains {

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

		/**
		 * called when the object is created
		 */
		public function __construct() {
			//assign the variables
				$this->app_name = 'domains';
				$this->app_uuid = '8b91605b-f6d2-42e6-a56d-5d1ded01bb44';
				$this->name = 'domain';
				$this->table = 'domains';
				$this->toggle_field = 'domain_enabled';
				$this->toggle_values = ['true','false'];
				$this->location = 'domains.php';
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
							$d = 0;
							foreach ($records as $record) {
								//add to the array
									if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
										//set the uuid
											$id = $record['uuid'];

										//get the domain using the id
											$sql = "select domain_name from v_domains ";
											$sql .= "where domain_uuid = :domain_uuid ";
											$parameters['domain_uuid'] = $id;
											$database = new database;
											$domain_name = $database->select($sql, $parameters, 'column');
											unset($sql, $parameters);

										//get the domain settings
											$sql = "select * from v_domain_settings ";
											$sql .= "where domain_uuid = :domain_uuid ";
											$sql .= "and domain_setting_enabled = 'true' ";
											$parameters['domain_uuid'] = $id;
											$database = new database;
											$result = $database->select($sql, $parameters, 'all');
											unset($sql, $parameters);

											if (is_array($result) && sizeof($result) != 0) {
												foreach ($result as $row) {
													$name = $row['domain_setting_name'];
													$category = $row['domain_setting_category'];
													$subcategory = $row['domain_setting_subcategory'];
													if ($subcategory != '') {
														if ($name == "array") {
															$_SESSION[$category][] = $row['default_setting_value'];
														}
														else {
															$_SESSION[$category][$name] = $row['default_setting_value'];
														}
													}
													else {
														if ($name == "array") {
															$_SESSION[$category][$subcategory][] = $row['default_setting_value'];
														}
														else {
															$_SESSION[$category][$subcategory]['uuid'] = $row['default_setting_uuid'];
															$_SESSION[$category][$subcategory][$name] = $row['default_setting_value'];
														}
													}
												}
											}
											unset($result, $row);

										//get the $apps array from the installed apps from the core and mod directories
											$config_list = glob($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/*/*/app_config.php");
											$x=0;
											if (isset($config_list)) foreach ($config_list as &$config_path) {
												include($config_path);
												$x++;
											}

										//delete the domain data from all tables in the database
											if (isset($apps)) foreach ($apps as &$app) {
												if (isset($app['db'])) foreach ($app['db'] as $row) {
													if (is_array($row['table']['name'])) {
														$table_name = $row['table']['name']['text'];
														echo "<pre>";
														print_r($table_name);
														echo "<pre>\n";
													}
													else {
														$table_name = $row['table']['name'];
													}
													if ($table_name !== "v" && isset($row['fields'])) {
														foreach ($row['fields'] as $field) {
															if ($field['name'] == 'domain_uuid' && $table_name != 'v_domains') {
																$sql = "delete from ".$table_name." where domain_uuid = :domain_uuid ";
																$parameters['domain_uuid'] = $id;
																$database = new database;
																$database->app_name = 'domain_settings';
																$database->app_uuid = 'b31e723a-bf70-670c-a49b-470d2a232f71';
																$database->execute($sql, $parameters);
																unset($sql, $parameters);
															}
														}
													}
												}
											}

										//delete the directories
											if (strlen($domain_name) > 0) {
												//set the needle
												if (count($_SESSION["domains"]) > 1) {
													$v_needle = 'v_'.$domain_name.'_';
												}
												else {
													$v_needle = 'v_';
												}

												//delete the dialplan
												@unlink($_SESSION['switch']['dialplan']['dir'].'/'.$domain_name.'.xml');
												if (strlen($_SESSION['switch']['dialplan']['dir']) > 0) {
													system('rm -rf '.$_SESSION['switch']['dialplan']['dir'].'/'.$domain_name);
												}

												//delete the dialplan public
												@unlink($_SESSION['switch']['dialplan']['dir'].'/public/'.$domain_name.'.xml');
												if (strlen($_SESSION['switch']['dialplan']['dir']) > 0) {
													system('rm -rf '.$_SESSION['switch']['dialplan']['dir'].'/public/'.$domain_name);
												}

												//delete the extension
												@unlink($_SESSION['switch']['extensions']['dir'].'/'.$domain_name.'.xml');
												if (strlen($_SESSION['switch']['extensions']['dir']) > 0) {
													system('rm -rf '.$_SESSION['switch']['extensions']['dir'].'/'.$domain_name);
												}

												//delete fax
												if (strlen($_SESSION['switch']['storage']['dir']) > 0) {
													system('rm -rf '.$_SESSION['switch']['storage']['dir'].'/fax/'.$domain_name);
												}

												//delete the gateways
												if($dh = opendir($_SESSION['switch']['sip_profiles']['dir'])) {
													$files = Array();
													while($file = readdir($dh)) {
														if($file != "." && $file != ".." && $file[0] != '.') {
															if(is_dir($dir . "/" . $file)) {
																//this is a directory do nothing
															} else {
																//check if file extension is xml
																if (strpos($file, $v_needle) !== false && substr($file,-4) == '.xml') {
																	@unlink($_SESSION['switch']['sip_profiles']['dir']."/".$file);
																}
															}
														}
													}
													closedir($dh);
												}

												//delete the ivr menu
												if($dh = opendir($_SESSION['switch']['conf']['dir']."/ivr_menus")) {
													$files = Array();
													while($file = readdir($dh)) {
														if($file != "." && $file != ".." && $file[0] != '.') {
															if(is_dir($dir . "/" . $file)) {
																//this is a directory
															} else {
																if (strpos($file, $v_needle) !== false && substr($file,-4) == '.xml') {
																	@unlink($_SESSION['switch']['conf']['dir']."/ivr_menus/".$file);
																}
															}
														}
													}
													closedir($dh);
												}

												//delete the recordings
												if (strlen($_SESSION['switch']['recordings']['dir']) > 0) {
													system('rm -rf '.$_SESSION['switch']['recordings']['dir'].'/'.$_SESSION['domain_name'].'/'.$domain_name);
												}

												//delete voicemail
												if (strlen($_SESSION['switch']['voicemail']['dir']) > 0) {
													system('rm -rf '.$_SESSION['switch']['voicemail']['dir'].'/'.$domain_name);
												}
											}

										//apply settings reminder
											$_SESSION["reload_xml"] = true;

										//clear the domains session array to update it
											unset($_SESSION["domains"]);
											unset($_SESSION['domain']);
											unset($_SESSION['switch']);

										//remove the domain and save to transactions
											$domain_array['domains'][$d]['domain_uuid'] = $id;

										//increment the id
											$d++;
									}
							}

						//delete the checked rows
							if (is_array($domain_array) && @sizeof($domain_array) != 0) {
								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($domain_array);
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
									$uuids[] = "'".$record['uuid']."'";
								}
							}

						//create the array from existing data
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select * from v_".$this->table." ";
								$sql .= "where ".$this->name."_uuid in (".implode(', ', $uuids).") ";
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									$x = 0;
									foreach ($rows as $row) {
										//copy data
											$array[$this->table][$x] = $row;

										//add copy to the description
											$array[$this->table][$x][$this->name.'_uuid'] = uuid();
											$array[$this->table][$x][$this->name.'_description'] = trim($row[$this->name.'_description']).' ('.$text['label-copy'].')';

										//increment the id
											$x++;
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
		 * add default, domain and user settings to the session array
		 */
		public function set() {

			//get previous domain settings
				if (is_uuid($_SESSION["previous_domain_uuid"])) {
					$sql = "select * from v_domain_settings ";
					$sql .= "where domain_uuid = :previous_domain_uuid ";
					$sql .= "and domain_setting_enabled = 'true' ";
					$sql .= " order by domain_setting_order asc ";
					$parameters['previous_domain_uuid'] = $_SESSION["previous_domain_uuid"];
					$database = new database;
					$result = $database->select($sql, $parameters, 'all');
					unset($sql, $parameters);

					//unset previous domain settings
					foreach ($result as $row) {
						if ($row['domain_setting_category'] != 'user') { //skip off-limit categories
							unset($_SESSION[$row['domain_setting_category']][$row['domain_setting_subcategory']]);
						}
					}
					unset($_SESSION["previous_domain_uuid"]);
				}

			//get the default settings
				$sql = "select * from v_default_settings ";
				$sql .= "order by default_setting_order asc ";
				$database = new database;
				$result = $database->select($sql, null, 'all');
				unset($sql, $parameters);

				//unset all settings
				foreach ($result as $row) {
					if ($row['default_setting_category'] != 'user') { //skip off-limit categories
						unset($_SESSION[$row['default_setting_category']][$row['default_setting_subcategory']]);
					}
				}

				//set the enabled settings as a session
				foreach ($result as $row) {
					if ($row['default_setting_enabled'] == 'true') {
						$name = $row['default_setting_name'];
						$category = $row['default_setting_category'];
						$subcategory = $row['default_setting_subcategory'];
						if (strlen($subcategory) == 0) {
							if ($name == "array") {
								$_SESSION[$category][] = $row['default_setting_value'];
							}
							else {
								$_SESSION[$category][$name] = $row['default_setting_value'];
							}
						}
						else {
							if ($name == "array") {
								$_SESSION[$category][$subcategory][] = $row['default_setting_value'];
							}
							else {
								$_SESSION[$category][$subcategory]['uuid'] = $row['default_setting_uuid'];
								$_SESSION[$category][$subcategory][$name] = $row['default_setting_value'];
							}
						}
					}
				}

			//get the domains settings
				if (file_exists($_SERVER["PROJECT_ROOT"]."/app/domains/app_config.php")) {
					include "app/domains/resources/settings.php";
				}

			//get the domains settings
				if (is_uuid($_SESSION["domain_uuid"])) {

					//get settings from the database
					$sql = "select * from v_domain_settings ";
					$sql .= "where domain_uuid = :domain_uuid ";
					$sql .= "and domain_setting_enabled = 'true' ";
					$sql .= " order by domain_setting_order asc ";
					$parameters['domain_uuid'] = $_SESSION["domain_uuid"];
					$database = new database;
					$result = $database->select($sql, $parameters, 'all');
					unset($sql, $parameters);

					//unset the arrays that domains are overriding
					foreach ($result as $row) {
						$name = $row['domain_setting_name'];
						$category = $row['domain_setting_category'];
						$subcategory = $row['domain_setting_subcategory'];
						if ($name == "array") {
							unset($_SESSION[$category][$subcategory]);
						}
					}
					//set the enabled settings as a session
					foreach ($result as $row) {
						$name = $row['domain_setting_name'];
						$category = $row['domain_setting_category'];
						$subcategory = $row['domain_setting_subcategory'];
						if (strlen($subcategory) == 0) {
							//$$category[$name] = $row['domain_setting_value'];
							if ($name == "array") {
								$_SESSION[$category][] = $row['domain_setting_value'];
							}
							else {
								$_SESSION[$category][$name] = $row['domain_setting_value'];
							}
						}
						else {
							//$$category[$subcategory][$name] = $row['domain_setting_value'];
							if ($name == "array") {
								$_SESSION[$category][$subcategory][] = $row['domain_setting_value'];
							}
							else {
								$_SESSION[$category][$subcategory][$name] = $row['domain_setting_value'];
							}
						}
					}
				}

			//get the user settings
				if (array_key_exists("domain_uuid",$_SESSION) && array_key_exists("user_uuid",$_SESSION) && is_uuid($_SESSION["domain_uuid"])) {
					$sql = "select * from v_user_settings ";
					$sql .= "where domain_uuid = :domain_uuid ";
					$sql .= "and user_uuid = :user_uuid ";
					$sql .= " order by user_setting_order asc ";
					$parameters['domain_uuid'] = $_SESSION["domain_uuid"];
					$parameters['user_uuid'] = $_SESSION["user_uuid"];
					$database = new database;
					$result = $database->select($sql, $parameters, 'all');
					if (is_array($result)) {
						foreach ($result as $row) {
							if ($row['user_setting_enabled'] == 'true') {
								$name = $row['user_setting_name'];
								$category = $row['user_setting_category'];
								$subcategory = $row['user_setting_subcategory'];
								if (strlen($row['user_setting_value']) > 0) {
									if (strlen($subcategory) == 0) {
										//$$category[$name] = $row['domain_setting_value'];
										if ($name == "array") {
											$_SESSION[$category][] = $row['user_setting_value'];
										}
										else {
											$_SESSION[$category][$name] = $row['user_setting_value'];
										}
									}
									else {
										//$$category[$subcategory][$name] = $row['domain_setting_value'];
										if ($name == "array") {
											$_SESSION[$category][$subcategory][] = $row['user_setting_value'];
										}
										else {
											$_SESSION[$category][$subcategory][$name] = $row['user_setting_value'];
										}
									}
								}
							}
						}
					}
				}

			//set the values from the session variables
				if (strlen($_SESSION['domain']['time_zone']['name']) > 0) {
					//server time zone
					$_SESSION['time_zone']['system'] = date_default_timezone_get();
					//domain time zone set in system settings
					$_SESSION['time_zone']['domain'] = $_SESSION['domain']['time_zone']['name'];
					//set the domain time zone as the default time zone
					date_default_timezone_set($_SESSION['domain']['time_zone']['name']);
				}

			//set the context
				$_SESSION["context"] = $_SESSION["domain_name"];
		}

		/**
		 * upgrade application defaults
		 */
		public function upgrade() {

			//connect to the database if not connected
				if (!$this->db) {
					$database = new database;
					$database->connect();
					$this->db = $database->db;
				}

			//get the variables
				$config = new config;
				$config_exists = $config->exists();
				$config_path = $config->find();
				$config->get();
				$db_type = $config->db_type;
				$db_name = $config->db_name;
				$db_username = $config->db_username;
				$db_password = $config->db_password;
				$db_secure = $config->db_secure;
				$db_cert_authority = $config->db_cert_authority;
				$db_host = $config->db_host;
				$db_path = $config->db_path;
				$db_port = $config->db_port;

			//set the include path
				$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
				set_include_path(parse_ini_file($conf[0])['document.root']);

			//includes files
				include "resources/require.php";

			//check for default settings
				$this->settings();

			//get the list of installed apps from the core and app directories (note: GLOB_BRACE doesn't work on some systems)
				$config_list_1 = glob($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/*/*/app_config.php");
				$config_list_2 = glob($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/*/*/app_menu.php");
				$config_list = array_merge((array)$config_list_1, (array)$config_list_2);
				unset($config_list_1,$config_list_2);
				$db = $this->db;
				$x=0;
				foreach ($config_list as &$config_path) {
					$app_path = dirname($config_path);
					$app_path = preg_replace('/\A.*(\/.*\/.*)\z/', '$1', $app_path);
					include($config_path);
					$x++;
				}

			//get the domains
				$sql = "select * from v_domains ";
				$database = new database;
				$domains = $database->select($sql, null, 'all');
				unset($sql, $parameters);

			//get the domain_settings
				$sql = "select * from v_domain_settings ";
				$sql .= "where domain_setting_enabled = 'true' ";
				$database = new database;
				$domain_settings = $database->select($sql, null, 'all');
				unset($sql, $parameters);

			//get the default settings
				$sql = "select * from v_default_settings ";
				$sql .= "where default_setting_enabled = 'true' ";
				$database = new database;
				$database_default_settings = $database->select($sql, null, 'all');
				unset($sql, $parameters);

			//get the domain_uuid
				if (is_array($domains)) {
					foreach($domains as $row) {
						if (count($domains) == 1) {
							$_SESSION["domain_uuid"] = $row["domain_uuid"];
							$_SESSION["domain_name"] = $row['domain_name'];
						}
						else {
							if (lower_case($row['domain_name']) == lower_case($domain_array[0]) || lower_case($row['domain_name']) == lower_case('www.'.$domain_array[0])) {
								$_SESSION["domain_uuid"] = $row["domain_uuid"];
								$_SESSION["domain_name"] = $row['domain_name'];
							}
							$_SESSION['domains'][$row['domain_uuid']]['domain_uuid'] = $row['domain_uuid'];
							$_SESSION['domains'][$row['domain_uuid']]['domain_name'] = $row['domain_name'];
						}
					}
				}

			//loop through all domains
				$domain_count = count($domains);
				$domains_processed = 1;
				foreach ($domains as &$row) {
					//get the values from database and set them as php variables
						$domain_uuid = $row["domain_uuid"];
						$domain_name = $row["domain_name"];

					//get the context
						$context = $domain_name;

					//get the default settings - this needs to be done to reset the session values back to the defaults for each domain in the loop
						foreach($database_default_settings as $row) {
							$name = $row['default_setting_name'];
							$category = $row['default_setting_category'];
							$subcategory = $row['default_setting_subcategory'];
							if (strlen($subcategory) == 0) {
								if ($name == "array") {
									$_SESSION[$category][] = $row['default_setting_value'];
								}
								else {
									$_SESSION[$category][$name] = $row['default_setting_value'];
								}
							}
							else {
								if ($name == "array") {
									$_SESSION[$category][$subcategory][] = $row['default_setting_value'];
								}
								else {
									$_SESSION[$category][$subcategory]['uuid'] = $row['default_setting_uuid'];
									$_SESSION[$category][$subcategory][$name] = $row['default_setting_value'];
								}
							}
						}

					//get the domains settings for the current domain
						foreach($domain_settings as $row) {
							if ($row['domain_uuid'] == $domain_uuid) {
								$name = $row['domain_setting_name'];
								$category = $row['domain_setting_category'];
								$subcategory = $row['domain_setting_subcategory'];
								if (strlen($subcategory) == 0) {
									//$$category[$name] = $row['domain_setting_value'];
									$_SESSION[$category][$name] = $row['domain_setting_value'];
								}
								else {
									//$$category[$subcategory][$name] = $row['domain_setting_value'];
									$_SESSION[$category][$subcategory][$name] = $row['domain_setting_value'];
								}
							}
						}

					//get the list of installed apps from the core and mod directories and execute the php code in app_defaults.php
						$default_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/*/*/app_defaults.php");
						foreach ($default_list as &$default_path) {
							include($default_path);
						}

					//track of the number of domains processed
						$domains_processed++;
				}

			//clear the session variables
				unset($_SESSION['domain']);
				unset($_SESSION['switch']);

		} //end upgrade method

		/**
		 * add missing default settings
		 * update the uuid for older default settings that were added before the uuids was predefined.
		 */
		public function settings() {

			//includes files
				include "resources/require.php";

			//get an array of the default settings UUIDs
				$sql = "select * from v_default_settings ";
				$database = new database;
				$result = $database->select($sql, null, 'all');
				foreach($result as $row) {
					$setting[$row['default_setting_uuid']] = 1;
				}
				unset($sql);

			//get the list of default settings
				$config_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/*/*/app_config.php");
				$x=0;
				foreach ($config_list as $config_path) {
					include($config_path);
					$x++;
				}
				$x = 0;
				foreach ($apps as $app) {
					if (is_array($app['default_settings'])) {
						foreach ($app['default_settings'] as $row) {
							if (!isset($setting[$row['default_setting_uuid']])) {
								$array['default_settings'][$x] = $row;
								$array['default_settings'][$x]['app_uuid'] = $app['uuid'];
								$x++;
							}
						}
					}
				}

			//add the missing default settings
				if (is_array($array) && count($array) > 0) {
					//grant temporary permissions
						$p = new permissions;
						$p->add('default_setting_add', 'temp');

					//execute insert
						$database = new database;
						$database->app_name = 'default_settings';
						$database->app_uuid = '2c2453c0-1bea-4475-9f44-4d969650de09';
						$database->save($array, false);
						unset($array);

					//revoke temporary permissions
						$p->delete('default_setting_add', 'temp');
				}

		} //end settings method
	}
}

?>
