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
	Portions created by the Initial Developer are Copyright (C) 2008-2023
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
							foreach ($records as $record) {
								//add to the array
									if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['uuid'])) {
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
														if (defined('STDIN')) {
															echo "<pre>".print_r($table_name, 1)."<pre>\n";
														}
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
											if (!empty($domain_name)) {
												//set the needle
												if (count($_SESSION["domains"]) > 1) {
													$v_needle = 'v_'.$domain_name.'_';
												}
												else {
													$v_needle = 'v_';
												}

												//delete the dialplan
												@unlink($_SESSION['switch']['dialplan']['dir'].'/'.$domain_name.'.xml');
												if (!empty($_SESSION['switch']['dialplan']['dir'])) {
													system('rm -rf '.$_SESSION['switch']['dialplan']['dir'].'/'.$domain_name);
												}

												//delete the dialplan public
												@unlink($_SESSION['switch']['dialplan']['dir'].'/public/'.$domain_name.'.xml');
												if (!empty($_SESSION['switch']['dialplan']['dir'])) {
													system('rm -rf '.$_SESSION['switch']['dialplan']['dir'].'/public/'.$domain_name);
												}

												//delete the extension
												@unlink($_SESSION['switch']['extensions']['dir'].'/'.$domain_name.'.xml');
												if (!empty($_SESSION['switch']['extensions']['dir'])) {
													system('rm -rf '.$_SESSION['switch']['extensions']['dir'].'/'.$domain_name);
												}

												//delete fax
												if (!empty($_SESSION['switch']['storage']['dir'])) {
													system('rm -rf '.$_SESSION['switch']['storage']['dir'].'/fax/'.$domain_name);
												}

												//delete the gateways
												if (!empty($_SESSION['switch']['sip_profiles']['dir'])) {
													if ($dh = opendir($_SESSION['switch']['sip_profiles']['dir'])) {
														$files = Array();
														while ($file = readdir($dh)) {
															if ($file != "." && $file != ".." && $file[0] != '.') {
																if (is_dir($dir . "/" . $file)) {
																	//this is a directory do nothing
																}
																else {
																	//check if file extension is xml
																	if (strpos($file, $v_needle) !== false && substr($file,-4) == '.xml') {
																		@unlink($_SESSION['switch']['sip_profiles']['dir']."/".$file);
																	}
																}
															}
														}
														closedir($dh);
													}
												}

												//delete the ivr menu
												if (!empty($_SESSION['switch']['conf']['dir'])) {
													if ($dh = opendir($_SESSION['switch']['conf']['dir']."/ivr_menus")) {
														$files = Array();
														while ($file = readdir($dh)) {
															if ($file != "." && $file != ".." && $file[0] != '.') {
																if (!empty($dir) && !empty($file) && is_dir($dir."/".$file)) {
																	//this is a directory
																}
																else {
																	if (strpos($file, $v_needle) !== false && substr($file,-4) == '.xml') {
																		@unlink($_SESSION['switch']['conf']['dir']."/ivr_menus/".$file);
																	}
																}
															}
														}
														closedir($dh);
													}
												}

												//delete the recordings
												if (!empty($_SESSION['switch']['recordings']['dir'])) {
													system('rm -rf '.$_SESSION['switch']['recordings']['dir'].'/'.$_SESSION['domain_name'].'/'.$domain_name);
												}

												//delete voicemail
												if (!empty($_SESSION['switch']['voicemail']['dir'])) {
													system('rm -rf '.$_SESSION['switch']['voicemail']['dir'].'/'.$domain_name);
												}
											}

										//apply settings reminder
											$_SESSION["reload_xml"] = true;

										//remove the domain from domains session array
											unset($_SESSION["domains"][$id]);

										//add domain uuid to array for deletion below
											$domain_array['domains'][] = ['domain_uuid'=>$id];
									}
							}

						//delete the checked rows
							if (is_array($domain_array) && @sizeof($domain_array) != 0) {
								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($domain_array);

								//set message
									message::add($text['message-delete']);

								//reload default/domain settings
									$this->set();
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
								if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->name."_uuid as uuid, ".$this->toggle_field." as toggle from v_".$this->table." ";
								$sql .= "where ".$this->name."_uuid in (".implode(', ', $uuids).") ";
								$database = new database;
								$rows = $database->select($sql, $parameters ?? null, 'all');
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
								if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['uuid'])) {
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
				if (isset($_SESSION["previous_domain_uuid"])) {
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
						if (empty($subcategory)) {
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
				if (!empty($_SESSION["domain_uuid"]) && is_uuid($_SESSION["domain_uuid"])) {

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
						if ($row['domain_setting_enabled'] == 'true') {
							$name = $row['domain_setting_name'];
							$category = $row['domain_setting_category'];
							$subcategory = $row['domain_setting_subcategory'];
							if (empty($subcategory)) {
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
								if (!empty($row['user_setting_value'])) {
									if (empty($subcategory)) {
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
				if (!empty($_SESSION['domain']['time_zone']['name'])) {
					//server time zone
					$_SESSION['time_zone']['system'] = date_default_timezone_get();
					//domain time zone set in system settings
					$_SESSION['time_zone']['domain'] = $_SESSION['domain']['time_zone']['name'];
					//set the domain time zone as the default time zone
					date_default_timezone_set($_SESSION['domain']['time_zone']['name']);
				}

			//set the context
				if (!empty($_SESSION["domain_name"])) {
					$_SESSION["context"] = $_SESSION["domain_name"];
				}
		}

		/**
		 * upgrade application defaults
		 */
		public function upgrade() {

			//add multi-lingual support
				$language = new text;
				$text = $language->get(null, 'core/upgrade');

			//includes files
				require dirname(__DIR__, 2) . "/resources/require.php";

			//add missing default settings
				$this->settings();

			//get the variables
				$config = new config;
				$config_path = $config->config_file;

			//get the list of installed apps from the core and app directories (note: GLOB_BRACE doesn't work on some systems)
				$config_list_1 = glob($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/*/*/app_config.php");
				$config_list_2 = glob($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/*/*/app_menu.php");
				$config_list = array_merge((array)$config_list_1, (array)$config_list_2);
				unset($config_list_1,$config_list_2);
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
				unset($sql);

			//loop through all domains
				$domains_processed = 1;
				foreach ($domains as $domain) {
					//get the values from database and set them as php variables
						$domain_uuid = $domain["domain_uuid"];
						$domain_name = $domain["domain_name"];

					//get the context
						$context = $domain_name;

					//get the email queue settings
						$setting = new settings(["domain_uuid" => $domain_uuid]);

					//get the list of installed apps from the core and mod directories and execute the php code in app_defaults.php
						$default_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/*/*/app_defaults.php");
						foreach ($default_list as &$default_path) {
							//echo $default_path."<br />\n";
							include($default_path);
						}

					//track of the number of domains processed
						$domains_processed++;
				}

			//output result
				if (defined('STDIN')) {
					if ($domains_processed > 1) {
						echo $text['message-upgrade_apps']."\n";
					}
				}

		} //end upgrade method

		/**
		 * add missing default settings
		 * update the uuid for older default settings that were added before the uuids was predefined.
		 */
		public function settings() {

			//includes files
				require dirname(__DIR__, 2) . "/resources/require.php";

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
					if (isset($app['default_settings']) && is_array($app['default_settings'])) {
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
				if (isset($array) && is_array($array) && count($array) > 0) {
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

		/**
		 * get a domain list
		 */
		public function all() {
			//get the domains from the database
				$database = new database;
				if ($database->table_exists('v_domains')) {
					$sql = "select * from v_domains order by domain_name asc;";
					$database = new database;
					$result = $database->select($sql, null, 'all');
					foreach($result as $row) {
						$domain_names[] = $row['domain_name'];
					}
					unset($prep_statement);
				}

			//build the domains array in the correct order
				if (is_array($domain_names)) {
					foreach ($domain_names as $dn) {
						foreach ($result as $row) {
							if ($row['domain_name'] == $dn) {
								$domains[] = $row;
							}
						}
					}
					unset($result);
				}

			//return the domains array
				return $domains;
		}

		/**
		 * get a domain list
		 */
		public function session() {
			//get the list of domains
				$domains = $this->all();

			//get the domain
				$domain_array = explode(":", $_SERVER["HTTP_HOST"] ?? '');

			//set domain_name and domain_uuid and update domains array with domain_uuid as the key
				if (!empty($domains) && is_array($domains)) {
					foreach($domains as $row) {
						if (!isset($_SESSION['username'])) {
							if (!empty($domains) && count($domains) == 1) {
								$domain_uuid = $row["domain_uuid"];
								$domain_name = $row['domain_name'];
								$_SESSION["domain_uuid"] = $row["domain_uuid"];
								$_SESSION["domain_name"] = $row['domain_name'];
							}
							else {
								if ($row['domain_name'] == $domain_array[0] || $row['domain_name'] == 'www.'.$domain_array[0]) {
									$_SESSION["domain_uuid"] = $row["domain_uuid"];
									$_SESSION["domain_name"] = $row["domain_name"];
								}
							}
						}
						$_SESSION['domains'][$row['domain_uuid']] = $row;
					}
					unset($domains, $prep_statement);
				}
		}

		/**
		 * Retrieves a list of domains from the database with optional filtering based on domain status.
		 *
		 * This function executes a SQL query to retrieve domain UUIDs and names from the v_domains table.
		 * If $ignore_domain_enabled is set to false, the function will filter the results based on the $domain_status parameter,
		 * including only domains with the specified enabled or disabled status. The results are returned as an associative array
		 * where the keys are domain UUIDs and the values are domain names.
		 *
		 * @param database $database The database connection object to be used for executing the query.
		 * @param bool $ignore_domain_enabled Optional. A flag to indicate whether to ignore the domain enabled status filter. Default is false.
		 * @param bool $domain_status Optional. The desired status of the domains to be retrieved. If true, retrieves domains that are enabled. If false, retrieves domains that are disabled. This parameter is ignored if $ignore_domain_enabled is true. Default is true.
		 * @return array An associative array where the keys are domain UUIDs and the values are domain names of the domains retrieved from the database.
		 */
		public static function get_list(database $database, bool $ignore_domain_enabled = false, bool $domain_status = true): array {
			$domains = [];
			$status_string = $domain_status ? 'true' : 'false';
			$sql = "select domain_uuid, domain_name from v_domains";
			if (!$ignore_domain_enabled) {
				$sql .= " where domain_enabled='$status_string'";
			}
			$result = $database->select($sql);
			if (!empty($result)) {
				foreach ($result as $row) {
					$domains[$row['domain_uuid']] = $row['domain_name'];
				}
			}
			return $domains;
		}

		/**
		 * Returns an array of domains including disabled with their domain UUID as the array key
		 * @param database $database Database object
		 * @return array List of domains with domain UUID as key
		 * @depends database
		 */
		public static function get_list_all(database $database): array {
			return self::get_list($database, true);
		}

		/**
		 * Returns an array of disabled domains with their domain UUID as the array key
		 * @param database $database
		 * @return array List of domains with domain UUID as key
		 */
		public static function get_list_disabled(database $database): array {
			return self::get_list($database, false, false);
		}

		/**
		 * Used to return a single matching domain name or an empty string
		 * @param database $database Database object
		 * @param string $uuid Valid UUID of the domain
		 * @param bool $ignore_domain_enabled When set to true, ignores the column domain_enabled
		 * @param bool $domain_status When $ignore_domain_enabled is false, allows returning only enabled domains or disabled domains
		 * @return string Returns domain name or empty string
		 */
		public static function get_name_by_uuid(database $database, string $uuid, bool $ignore_domain_enabled = false, bool $domain_status = true): string {
			$domain_name = "";
			$status_string = $domain_status ? 'true' : 'false';
			$sql = "select domain_name from v_domains";
			$sql .= " where domain_uuid = :uuid";
			if (!$ignore_domain_enabled) {
				$sql .= " and domain_enabled='$status_string'";
			}
			$parameters['uuid'] = $uuid;
			$result = $database->select($sql, $parameters, 'column');
			if (!empty($result)) {
				$domain_name = $result;
			}
			return $domain_name;
		}

		/**
		 * Used to get domain UUIDs from a matching a domain name. It is possible to have multiple domains with the same name
		 * so the value returned will either be an empty array or an indexed array containing one or more domains
		 * @param database $database Database object
		 * @param string $name Domain name to match in the database
		 * @param bool $ignore_domain_enabled When set to true, ignores the column domain_enabled
		 * @param bool $domain_status When $ignore_domain_enabled is false, allows returning only enabled domains or disabled domains
		 * @return array
		 */
		public static function get_domain_uuid_by_name(database $database, string $name, bool $ignore_domain_enabled = false, bool $domain_status = true): array {
			$domains = [];
			$status_string = $domain_status ? 'true' : 'false';
			$sql = "select domain_uuid from v_domains";
			$sql .= " where domain_name = :name";
			if (!$ignore_domain_enabled) {
				$sql .= " and domain_enabled='$status_string'";
			}
			$parameters['name'] = $name;
			$result = $database->select($sql, $parameters, 'all');
			if (!empty($result)) {
				$domains = array_map(function ($row) { return $row['domain_uuid']; }, $result);
			}
			return $domains;
		}

	}
}

?>
