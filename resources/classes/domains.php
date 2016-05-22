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
	Portions created by the Initial Developer are Copyright (C) 2008-2014
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	sreis
*/

if (!class_exists('domains')) {
	class domains {

		//define variables
		public $db;

		//class constructor
		public function __construct() {
			//connect to the database if not connected
			if (!$this->db) {
				require_once "resources/classes/database.php";
				$database = new database;
				$database->connect();
				$this->db = $database->db;
			}
		}

		public function set() {

			//set the PDO error mode
				$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

			//get the default settings
				$sql = "select * from v_default_settings ";
				try {
					$prep_statement = $this->db->prepare($sql . " order by default_setting_order asc ");
					$prep_statement->execute();
				}
				catch(PDOException $e) {
					$prep_statement = $this->db->prepare($sql);
					$prep_statement->execute();
				}
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				//unset all settings
				foreach ($result as $row) {
					unset($_SESSION[$row['default_setting_category']]);
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
				if (strlen($_SESSION["domain_uuid"]) > 0) {
					$sql = "select * from v_domain_settings ";
					$sql .= "where domain_uuid = '" . $_SESSION["domain_uuid"] . "' ";
					$sql .= "and domain_setting_enabled = 'true' ";
					try {
						$prep_statement = $this->db->prepare($sql . " order by domain_setting_order asc ");
						$prep_statement->execute();
					}
					catch(PDOException $e) {
						$prep_statement = $this->db->prepare($sql);
						$prep_statement->execute();
					}
					$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
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
				if (array_key_exists("domain_uuid",$_SESSION) and array_key_exists("user_uuid",$_SESSION) and strlen($_SESSION["domain_uuid"]) > 0 && strlen($_SESSION["user_uuid"]) > 0) {
					$sql = "select * from v_user_settings ";
					$sql .= "where domain_uuid = '" . $_SESSION["domain_uuid"] . "' ";
					$sql .= "and user_uuid = '" . $_SESSION["user_uuid"] . "' ";
					try {
						$prep_statement = $this->db->prepare($sql . " order by user_setting_order asc ");
						$prep_statement->execute();
					}
					catch(PDOException $e) {
						$prep_statement = $this->db->prepare($sql);
						$prep_statement->execute();
					}
					if ($prep_statement) {
						$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
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

			//set the PDO error mode
				$this->db->setAttribute(PDO::ATTR_ERRMODE, '');

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

		public function upgrade() {

			//get the db variables
				$config = new config;
				$config_exists = $config->exists();
				$config_path = $config->find();
				$config->get();
				$db_type = $config->db_type;
				$db_name = $config->db_name;
				$db_username = $config->db_username;
				$db_password = $config->db_password;
				$db_host = $config->db_host;
				$db_path = $config->db_path;
				$db_port = $config->db_port;

			//get the PROJECT PATH
				include "root.php";

			//get the list of installed apps from the core and app directories (note: GLOB_BRACE doesn't work on some systems)
				$config_list_1 = glob($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/*/*/app_config.php");
				$config_list_2 = glob($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/*/*/app_menu.php");
				$config_list = array_merge((array)$config_list_1, (array)$config_list_2);
				unset($config_list_1,$config_list_2);
				$db = $this->db;
				$x=0;
				foreach ($config_list as &$config_path) {
					include($config_path);
					$x++;
				}

			//get the domains
				$sql = "select * from v_domains ";
				$prep_statement = $this->db->prepare($sql);
				$prep_statement->execute();
				$domains = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				unset($prep_statement);

			//get the domain_settings
				$sql = "select * from v_domain_settings ";
				$sql .= "where domain_setting_enabled = 'true' ";
				$prep_statement = $this->db->prepare($sql);
				$prep_statement->execute();
				$domain_settings = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				unset($prep_statement);

			//get the default settings
				$sql = "select * from v_default_settings ";
				$sql .= "where default_setting_enabled = 'true' ";
				$prep_statement = $this->db->prepare($sql);
				$prep_statement->execute();
				$database_default_settings = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				unset($prep_statement);

			//get the domain_uuid
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

			//loop through all domains
				$domain_count = count($domains);
				$domains_processed = 1;
				foreach ($domains as &$row) {
					//get the values from database and set them as php variables
						$domain_uuid = $row["domain_uuid"];
						$domain_name = $row["domain_name"];

					//get the context
						$context = $domain_name;

					//show the domain when display_type is set to text
						if ($display_type == "text") {
							echo "\n";
							echo $domain_name;
							echo "\n";
						}

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

			//synchronize the dialplan
				if (function_exists('save_dialplan_xml')) {
					save_dialplan_xml();
				}

			//update config.lua
				if (file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/scripts/resources/classes/scripts.php')) {
					$obj = new scripts;
					$obj->write_config();
				}

			//clear the session variables
				unset($_SESSION['domain']);
				unset($_SESSION['switch']);

		}
	}
}

?>