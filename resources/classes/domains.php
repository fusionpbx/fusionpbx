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
Portions created by the Initial Developer are Copyright (C) 2008-2025
the Initial Developer. All Rights Reserved.

Contributor(s):
Mark J Crane <markjcrane@fusionpbx.com>
sreis
*/

/**
 * domains class
 *
 */
class domains {

	/**
	 * declare constant variables
	 */
	const app_name = 'domains';
	const app_uuid = '8b91605b-f6d2-42e6-a56d-5d1ded01bb44';

	/**
	 * declare the variables
	 */
	private $name;
	private $table;
	private $toggle_field;
	private $toggle_values;
	private $location;

	/**
	 * Set in the constructor. Must be a database object and cannot be null.
	 *
	 * @var database Database Object
	 */
	private $database;

	/**
	 * Settings object set in the constructor. Must be a settings object and cannot be null.
	 *
	 * @var settings Settings Object
	 */
	private $settings;

	/**
	 * User UUID set in the constructor. This can be passed in through the $settings_array associative array or set in
	 * the session global array
	 *
	 * @var string
	 */
	private $user_uuid;

	/**
	 * Domain UUID set in the constructor. This can be passed in through the $settings_array associative array or set
	 * in the session global array
	 *
	 * @var string
	 */
	private $domain_uuid;

	/**
	 * Constructor for the class.
	 *
	 * This method initializes the object with setting_array and session data.
	 *
	 * @param array $setting_array An optional array of settings to override default values. Defaults to [].
	 */
	public function __construct($setting_array = []) {
		//assign the variables
		$this->name = 'domain';
		$this->table = 'domains';
		$this->toggle_field = 'domain_enabled';
		$this->toggle_values = ['true', 'false'];
		$this->location = 'domains.php';

		//set the domain and user uuids
		$this->domain_uuid = $setting_array['domain_uuid'] ?? $_SESSION['domain_uuid'] ?? '';
		$this->user_uuid = $setting_array['user_uuid'] ?? $_SESSION['user_uuid'] ?? '';

		//open a database connection
		if (empty($setting_array['database'])) {
			$this->database = database::new();
		} else {
			$this->database = $setting_array['database'];
		}

		//load the settings
		if (empty($setting_array['settings'])) {
			$this->settings = new settings(['database' => $this->database, 'domain_uuid' => $this->domain_uuid, 'user_uuid' => $this->user_uuid]);
		} else {
			$this->settings = $setting_array['settings'];
		}
	}

	/**
	 * get all enabled domains
	 *
	 * @returns array all enabled domains with uuid as array key
	 */
	public static function enabled() {

		//define database as global
		global $database;

		//define default return value
		$domains = [];

		//get the domains from the database
		$sql = "select * from v_domains ";
		$sql .= "where domain_enabled = true ";
		$sql .= "order by domain_name asc; ";
		$result = $database->select($sql, null, 'all');
		if (!empty($result)) {
			foreach ($result as $row) {
				$domains[$row['domain_uuid']] = $row;
			}
		}

		//return the domains array
		return $domains;
	}

	/**
	 * get all disabled domains
	 *
	 * @returns array all disabled domains with uuid as array key
	 */
	public static function disabled() {

		//define database as global
		global $database;

		//define default return value
		$domains = [];

		//get the domains from the database
		$sql = "select * from v_domains ";
		$sql .= "where domain_enabled = false ";
		$sql .= "order by domain_name asc; ";
		$result = $database->select($sql, null, 'all');
		if (!empty($result)) {
			foreach ($result as $row) {
				$domains[$row['domain_uuid']] = $row;
			}
		}

		//return the domains array
		return $domains;
	}

	/**
	 * Toggles the state of one or more records.
	 *
	 * @param array $records  An array of record IDs to delete, where each ID is an associative array
	 *                        containing 'uuid' and 'checked' keys. The 'checked' value indicates
	 *                        whether the corresponding checkbox was checked for deletion.
	 *
	 * @return void No return value; this method modifies the database state and sets a message.
	 */
	public function toggle($records) {
		if (permission_exists($this->name . '_edit')) {

			//add multi-lingual support
			$language = new text;
			$text = $language->get();

			//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'], 'negative');
				header('Location: ' . $this->location);
				exit;
			}

			//toggle the checked records
			if (is_array($records) && @sizeof($records) != 0) {
				//get current toggle state
				foreach ($records as $record) {
					if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['uuid'])) {
						$uuids[] = "'" . $record['uuid'] . "'";
					}
				}
				if (is_array($uuids) && @sizeof($uuids) != 0) {
					$sql = "select " . $this->name . "_uuid as uuid, " . $this->toggle_field . " as toggle from v_" . $this->table . " ";
					$sql .= "where " . $this->name . "_uuid in (" . implode(', ', $uuids) . ") ";
					$rows = $this->database->select($sql, $parameters ?? null, 'all');
					if (is_array($rows) && @sizeof($rows) != 0) {
						foreach ($rows as $row) {
							$states[$row['uuid']] = $row['toggle'];
						}
					}
					unset($sql, $parameters, $rows, $row);
				}

				//build update array
				$x = 0;
				foreach ($states as $uuid => $state) {
					//create the array
					$array[$this->table][$x][$this->name . '_uuid'] = $uuid;
					$array[$this->table][$x][$this->toggle_field] = $state == $this->toggle_values[0] ? $this->toggle_values[1] : $this->toggle_values[0];

					//increment the id
					$x++;
				}

				//save the changes
				if (is_array($array) && @sizeof($array) != 0) {
					//save the array

					$this->database->save($array);
					unset($array);

					//set message
					message::add($text['message-toggle']);
				}
				unset($records, $states);
			}
		}
	}

	/**
	 * Copies one or more records
	 *
	 * @param array $records  An array of record IDs to delete, where each ID is an associative array
	 *                        containing 'uuid' and 'checked' keys. The 'checked' value indicates
	 *                        whether the corresponding checkbox was checked for deletion.
	 *
	 * @return void No return value; this method modifies the database state and sets a message.
	 */
	public function copy($records) {
		if (permission_exists($this->name . '_add')) {

			//add multi-lingual support
			$language = new text;
			$text = $language->get();

			//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'], 'negative');
				header('Location: ' . $this->location);
				exit;
			}

			//copy the checked records
			if (is_array($records) && @sizeof($records) != 0) {

				//get checked records
				foreach ($records as $record) {
					if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['uuid'])) {
						$uuids[] = "'" . $record['uuid'] . "'";
					}
				}

				//create the array from existing data
				if (is_array($uuids) && @sizeof($uuids) != 0) {
					$sql = "select * from v_" . $this->table . " ";
					$sql .= "where " . $this->name . "_uuid in (" . implode(', ', $uuids) . ") ";
					$rows = $this->database->select($sql, null, 'all');
					if (is_array($rows) && @sizeof($rows) != 0) {
						$x = 0;
						foreach ($rows as $row) {
							//convert boolean values to a string
							foreach ($row as $key => $value) {
								if (gettype($value) == 'boolean') {
									$value = $value ? 'true' : 'false';
									$row[$key] = $value;
								}
							}

							//copy data
							$array[$this->table][$x] = $row;

							//add copy to the description
							$array[$this->table][$x][$this->name . '_uuid'] = uuid();
							$array[$this->table][$x][$this->name . '_description'] = trim($row[$this->name . '_description']) . ' (' . $text['label-copy'] . ')';

							//increment the id
							$x++;
						}
					}
					unset($sql, $parameters, $rows, $row);
				}

				//save the changes and set the message
				if (is_array($array) && @sizeof($array) != 0) {
					//save the array

					$this->database->save($array);
					unset($array);

					//set message
					message::add($text['message-copy']);
				}
				unset($records);
			}
		}
	}

	/**
	 * Upgrade function to apply necessary changes and settings.
	 *
	 * @return void
	 */
	public function upgrade() {

		//add multi-lingual support
		$language = new text;
		$text = $language->get(null, 'core/upgrade');

		//includes files
		require dirname(__DIR__, 2) . "/resources/require.php";

		//add missing default settings
		$this->settings();

		//save the database object to be used by app_defaults.php
		$database = $this->database;

		//get the variables
		$config = new config;
		$config_path = $config->config_file;

		//get the list of installed apps from the core and app directories (note: GLOB_BRACE doesn't work on some systems)
		$config_list_1 = glob(dirname(__DIR__, 2) . "/*/*/app_config.php");
		$config_list_2 = glob(dirname(__DIR__, 2) . "/*/*/app_menu.php");
		$config_list = array_merge((array)$config_list_1, (array)$config_list_2);
		unset($config_list_1, $config_list_2);
		$x = 0;
		foreach ($config_list as $config_path) {
			$app_path = dirname($config_path);
			$app_path = preg_replace('/\A.*(\/.*\/.*)\z/', '$1', $app_path);
			include($config_path);
			$x++;
		}

		//get the domains
		$sql = "select * from v_domains ";
		$domains = $this->database->select($sql, null, 'all');
		unset($sql);

		//get the list of installed apps from the core and mod directories
		$default_list = glob(dirname(__DIR__, 2) . "/*/*/app_defaults.php");

		//loop through all domains
		$domains_processed = 1;
		foreach ($domains as $domain) {
			//get the values from database and set them as php variables
			$domain_uuid = $domain["domain_uuid"];
			$domain_name = $domain["domain_name"];

			//get the context
			$context = $domain_name;

			//get the email queue settings
			$settings = new settings(["database" => $this->database, "domain_uuid" => $domain_uuid]);

			//run the php code in app_defaults.php
			foreach ($default_list as $default_path) {
				include($default_path);
			}

			//track the number of domains processed
			$domains_processed++;
		}

	} //end upgrade method

	/**
	 * Get the default settings for the application.
	 *
	 * This function first retrieves an array of default setting UUIDs from the database,
	 * then checks each app_config.php file in the project directory to see if they contain
	 * any default settings that are not already included in the UUID list. If so, it adds
	 * them to the array and attempts to insert them into the database.
	 *
	 * @return void
	 */
	public function settings() {

		//includes files
		require dirname(__DIR__, 2) . "/resources/require.php";

		//get an array of the default settings UUIDs
		$sql = "select * from v_default_settings ";
		$result = $this->database->select($sql, null, 'all');
		foreach ($result as $row) {
			$setting[$row['default_setting_uuid']] = 1;
		}
		unset($sql);

		//get the list of default settings
		$config_list = glob(dirname(__DIR__, 2) . "/*/*/app_config.php");
		$x = 0;
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
			$p = permissions::new();
			$p->add('default_setting_add', 'temp');

			//execute insert
			$this->database->app_name = 'default_settings';
			$this->database->app_uuid = '2c2453c0-1bea-4475-9f44-4d969650de09';
			$this->database->save($array, false);
			unset($array);

			//revoke temporary permissions
			$p->delete('default_setting_add', 'temp');
		}

	} //end settings method

	/**
	 * Deletes one or multiple records.
	 *
	 * @param array $records An array of record IDs to delete, where each ID is an associative array
	 *                       containing 'uuid' and 'checked' keys. The 'checked' value indicates
	 *                       whether the corresponding checkbox was checked for deletion.
	 *
	 * @return void No return value; this method modifies the database state and sets a message.
	 */
	public function delete($records) {
		if (permission_exists($this->name . '_delete')) {

			//add multi-lingual support
			$language = new text;
			$text = $language->get();

			//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'], 'negative');
				header('Location: ' . $this->location);
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
						$domain_name = $this->database->select($sql, $parameters, 'column');
						unset($sql, $parameters);

						//get the domain settings
						$sql = "select * from v_domain_settings ";
						$sql .= "where domain_uuid = :domain_uuid ";
						$sql .= "and domain_setting_enabled = 'true' ";
						$parameters['domain_uuid'] = $id;
						$result = $this->database->select($sql, $parameters, 'all');
						unset($sql, $parameters);

						if (is_array($result) && sizeof($result) != 0) {
							foreach ($result as $row) {
								$name = $row['domain_setting_name'];
								$category = $row['domain_setting_category'];
								$subcategory = $row['domain_setting_subcategory'];
								if ($subcategory != '') {
									if ($name == "array") {
										$_SESSION[$category][] = $row['default_setting_value'];
									} else {
										$_SESSION[$category][$name] = $row['default_setting_value'];
									}
								} else {
									if ($name == "array") {
										$_SESSION[$category][$subcategory][] = $row['default_setting_value'];
									} else {
										$_SESSION[$category][$subcategory]['uuid'] = $row['default_setting_uuid'];
										$_SESSION[$category][$subcategory][$name] = $row['default_setting_value'];
									}
								}
							}
						}
						unset($result, $row);

						//get the $apps array from the installed apps from the core and mod directories
						$config_list = glob(dirname(__DIR__, 2) . "/*/*/app_config.php");
						$x = 0;
						if (isset($config_list)) foreach ($config_list as $config_path) {
							include($config_path);
							$x++;
						}

						//delete the domain data from all tables in the database
						if (isset($apps)) foreach ($apps as $app) {
							if (isset($app['db'])) foreach ($app['db'] as $row) {
								if (is_array($row['table']['name'])) {
									$table_name = $row['table']['name']['text'];
									if (defined('STDIN')) {
										echo "<pre>" . print_r($table_name, 1) . "<pre>\n";
									}
								} else {
									$table_name = $row['table']['name'];
								}
								if ($table_name !== "v" && isset($row['fields'])) {
									foreach ($row['fields'] as $field) {
										if ($field['name'] == 'domain_uuid' && $table_name != 'v_domains') {
											$sql = "delete from " . $table_name . " where domain_uuid = :domain_uuid ";
											$parameters['domain_uuid'] = $id;
											$this->database->app_name = 'domain_settings';
											$this->database->app_uuid = 'b31e723a-bf70-670c-a49b-470d2a232f71';
											$this->database->execute($sql, $parameters);
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
								$v_needle = 'v_' . $domain_name . '_';
							} else {
								$v_needle = 'v_';
							}

							//delete the dialplan
							@unlink($_SESSION['switch']['dialplan']['dir'] . '/' . $domain_name . '.xml');
							if (!empty($_SESSION['switch']['dialplan']['dir'])) {
								recursive_delete($_SESSION['switch']['dialplan']['dir'] . '/' . $domain_name);
							}

							//delete the dialplan public
							@unlink($_SESSION['switch']['dialplan']['dir'] . '/public/' . $domain_name . '.xml');
							if (!empty($_SESSION['switch']['dialplan']['dir'])) {
								recursive_delete($_SESSION['switch']['dialplan']['dir'] . '/public/' . $domain_name);
							}

							//delete the extension
							@unlink($_SESSION['switch']['extensions']['dir'] . '/' . $domain_name . '.xml');
							if (!empty($_SESSION['switch']['extensions']['dir'])) {
								recursive_delete($_SESSION['switch']['extensions']['dir'] . '/' . $domain_name);
							}

							//delete fax
							if (!empty($_SESSION['switch']['storage']['dir'])) {
								recursive_delete($_SESSION['switch']['storage']['dir'] . '/fax/' . $domain_name);
							}

							//delete the gateways
							if (!empty($_SESSION['switch']['sip_profiles']['dir'])) {
								if ($dh = opendir($_SESSION['switch']['sip_profiles']['dir'])) {
									$files = [];
									while ($file = readdir($dh)) {
										if ($file != "." && $file != ".." && $file[0] != '.') {
											if (is_dir($dir . "/" . $file)) {
												//this is a directory do nothing
											} else {
												//check if file extension is xml
												if (strpos($file, $v_needle) !== false && substr($file, -4) == '.xml') {
													@unlink($_SESSION['switch']['sip_profiles']['dir'] . "/" . $file);
												}
											}
										}
									}
									closedir($dh);
								}
							}

							//delete the ivr menu
							if (!empty($_SESSION['switch']['conf']['dir'])) {
								if ($dh = opendir($_SESSION['switch']['conf']['dir'] . "/ivr_menus")) {
									$files = [];
									while ($file = readdir($dh)) {
										if ($file != "." && $file != ".." && $file[0] != '.') {
											if (!empty($dir) && !empty($file) && is_dir($dir . "/" . $file)) {
												//this is a directory
											} else {
												if (strpos($file, $v_needle) !== false && substr($file, -4) == '.xml') {
													@unlink($_SESSION['switch']['conf']['dir'] . "/ivr_menus/" . $file);
												}
											}
										}
									}
									closedir($dh);
								}
							}

							//delete the recordings
							if (!empty($_SESSION['switch']['recordings']['dir'])) {
								recursive_delete($_SESSION['switch']['recordings']['dir'] . '/' . $_SESSION['domain_name'] . '/' . $domain_name);
							}

							//delete voicemail
							if (!empty($_SESSION['switch']['voicemail']['dir'])) {
								recursive_delete($_SESSION['switch']['voicemail']['dir'] . '/' . $domain_name);
							}
						}

						//apply settings reminder
						$_SESSION["reload_xml"] = true;

						//remove the domain from domains session array
						unset($_SESSION["domains"][$id]);

						//add domain uuid to array for deletion below
						$domain_array['domains'][] = ['domain_uuid' => $id];
					}
				}

				//delete the checked rows
				if (is_array($domain_array) && @sizeof($domain_array) != 0) {
					//execute delete
					$this->database->delete($domain_array);

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
	 * set domain settings
	 *
	 * This method retrieves domain and user settings from the database,
	 * unsets previous domain settings, sets default settings as session variables,
	 * and updates the time zone based on the domain's time zone setting.
	 *
	 * @return void
	 */
	public function set() {

		//get previous domain settings
		if (isset($_SESSION["previous_domain_uuid"])) {
			$sql = "select * from v_domain_settings ";
			$sql .= "where domain_uuid = :previous_domain_uuid ";
			$sql .= "and domain_setting_enabled = 'true' ";
			$sql .= " order by domain_setting_order asc ";
			$parameters['previous_domain_uuid'] = $_SESSION["previous_domain_uuid"];
			$result = $this->database->select($sql, $parameters, 'all');
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
		$result = $this->database->select($sql, null, 'all');
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
					} else {
						$_SESSION[$category][$name] = $row['default_setting_value'];
					}
				} else {
					if ($name == "array") {
						$_SESSION[$category][$subcategory][] = $row['default_setting_value'];
					} else {
						$_SESSION[$category][$subcategory]['uuid'] = $row['default_setting_uuid'];
						$_SESSION[$category][$subcategory][$name] = $row['default_setting_value'];
					}
				}
			}
		}

		//get the domains settings
		if (!empty($_SESSION["domain_uuid"]) && is_uuid($_SESSION["domain_uuid"])) {

			//get settings from the database
			$sql = "select * from v_domain_settings ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$sql .= "and domain_setting_enabled = 'true' ";
			$sql .= " order by domain_setting_order asc ";
			$parameters['domain_uuid'] = $_SESSION["domain_uuid"];
			$result = $this->database->select($sql, $parameters, 'all');
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
						} else {
							$_SESSION[$category][$name] = $row['domain_setting_value'];
						}
					} else {
						//$$category[$subcategory][$name] = $row['domain_setting_value'];
						if ($name == "array") {
							$_SESSION[$category][$subcategory][] = $row['domain_setting_value'];
						} else {
							$_SESSION[$category][$subcategory][$name] = $row['domain_setting_value'];
						}
					}
				}
			}
		}

		//get the user settings
		if (array_key_exists("domain_uuid", $_SESSION) && array_key_exists("user_uuid", $_SESSION) && is_uuid($_SESSION["domain_uuid"])) {
			$sql = "select * from v_user_settings ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$sql .= "and user_uuid = :user_uuid ";
			$sql .= " order by user_setting_order asc ";
			$parameters['domain_uuid'] = $_SESSION["domain_uuid"];
			$parameters['user_uuid'] = $_SESSION["user_uuid"];
			$result = $this->database->select($sql, $parameters, 'all');
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
								} else {
									$_SESSION[$category][$name] = $row['user_setting_value'];
								}
							} else {
								//$$category[$subcategory][$name] = $row['domain_setting_value'];
								if ($name == "array") {
									$_SESSION[$category][$subcategory][] = $row['user_setting_value'];
								} else {
									$_SESSION[$category][$subcategory][$name] = $row['user_setting_value'];
								}
							}
						}
					}
				}
			}
		}

		//set the domain time zone as the default time zone
		date_default_timezone_set($this->settings->get('domain', 'time_zone', date_default_timezone_get()));

		//set the context
		if (!empty($_SESSION["domain_name"])) {
			$_SESSION["context"] = $_SESSION["domain_name"];
		}
	}

	/**
	 * Initializes a session with domain-specific data.
	 *
	 * This method retrieves a list of all domains from the database and then determines the current domain by checking
	 * the HTTP_HOST server variable. If the username is not set in the session, it will use the first domain in the
	 * list or match the domain name with the given domain name. The relevant domain UUID and name are then stored in
	 * the session.
	 *
	 * @return void
	 */
	public function session() {
		//get the list of domains
		$domains = self::all();

		//get the domain
		$domain_array = explode(":", $_SERVER["HTTP_HOST"] ?? '');

		//set domain_name and domain_uuid and update domains array with domain_uuid as the key
		foreach ($domains as $row) {
			if (!isset($_SESSION['username'])) {
				if (!empty($domains) && count($domains) == 1) {
					$_SESSION["domain_uuid"] = $row["domain_uuid"];
					$_SESSION["domain_name"] = $row['domain_name'];
				} else {
					if ($row['domain_name'] == $domain_array[0] || $row['domain_name'] == 'www.' . $domain_array[0]) {
						$_SESSION["domain_uuid"] = $row["domain_uuid"];
						$_SESSION["domain_name"] = $row["domain_name"];
					}
				}
			}
		}

		//set the domains session array
		$_SESSION['domains'] = $domains;
	}

	/**
	 * Retrieves a list of all domains from the database.
	 *
	 * @return array An array of domain data, where each key is a unique domain UUID and each value is an associative
	 *               array containing the domain's details.
	 */
	public static function all() {

		//define database as global
		global $database;

		//define default return value
		$domains = [];

		//get the domains from the database
		$sql = "select * from v_domains ";
		$sql .= "order by domain_name asc; ";
		$result = $database->select($sql, null, 'all');
		if (!empty($result)) {
			foreach ($result as $row) {
				$domains[$row['domain_uuid']] = $row;
			}
		}

		//return the domains array
		return $domains;
	}

}
