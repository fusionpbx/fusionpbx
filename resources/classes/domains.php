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

	class domains {

		public function set() {

			//set the global variable
				global $db;

			//set the PDO error mode
				$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

			//get the default settings
				$sql = "select * from v_default_settings ";
				try {
					$prep_statement = $db->prepare($sql . " order by default_setting_order asc ");
					$prep_statement->execute();
				}
				catch(PDOException $e) {
					$prep_statement = $db->prepare($sql);
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
						$prep_statement = $db->prepare($sql . " order by domain_setting_order asc ");
						$prep_statement->execute();
					}
					catch(PDOException $e) {
						$prep_statement = $db->prepare($sql);
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
				if (strlen($_SESSION["domain_uuid"]) > 0 && strlen($_SESSION["user_uuid"]) > 0) {
					$sql = "select * from v_user_settings ";
					$sql .= "where domain_uuid = '" . $_SESSION["domain_uuid"] . "' ";
					$sql .= "and user_uuid = '" . $_SESSION["user_uuid"] . "' ";
					try {
						$prep_statement = $db->prepare($sql . " order by user_setting_order asc ");
						$prep_statement->execute();
					}
					catch(PDOException $e) {
						$prep_statement = $db->prepare($sql);
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
				$db->setAttribute(PDO::ATTR_ERRMODE, '');

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

			//recordings add the domain to the path if there is more than one domains
				if (count($_SESSION["domains"]) > 1) {
					if (strlen($_SESSION['switch']['recordings']['dir']) > 0) {
						if (substr($_SESSION['switch']['recordings']['dir'], -strlen($_SESSION["domain_name"])) != $_SESSION["domain_name"]) {
							//get the default recordings directory
							$sql = "select * from v_default_settings ";
							$sql .= "where default_setting_enabled = 'true' ";
							$sql .= "and default_setting_category = 'switch' ";
							$sql .= "and default_setting_subcategory = 'recordings' ";
							$sql .= "and default_setting_name = 'dir' ";
							$prep_statement = $db->prepare($sql);
							$prep_statement->execute();
							$result_default_settings = $prep_statement->fetchAll(PDO::FETCH_NAMED);
							foreach ($result_default_settings as $row) {
								$name = $row['default_setting_name'];
								$category = $row['default_setting_category'];
								$subcategory = $row['default_setting_subcategory'];
								$switch_recordings_dir = $row['default_setting_value'];
							}
							//add the domain
							$_SESSION['switch']['recordings']['dir'] = $switch_recordings_dir . '/' . $_SESSION["domain_name"];
						}
					}
				}
		}

		public function upgrade() {

			//set the global variable
				global $db, $db_type, $db_name, $db_username, $db_password, $db_host, $db_path, $db_port;

			//get the PROJECT PATH
				include "root.php";

			//get the list of installed apps from the core and app directories (note: GLOB_BRACE doesn't work on some systems)
				$config_list_1 = glob($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/*/*/app_config.php");
				$config_list_2 = glob($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/*/*/app_menu.php");
				$config_list = array_merge((array)$config_list_1, (array)$config_list_2);
				unset($config_list_1,$config_list_2);
				$x=0;
				foreach ($config_list as &$config_path) {
					include($config_path);
					$x++;
				}

			//get the domain_uuid
				$sql = "select * from v_domains ";
				$prep_statement = $db->prepare($sql);
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				foreach($result as $row) {
					if (count($result) == 1) {
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
				unset($result, $prep_statement);

			//get the default settings
				$sql = "select * from v_default_settings ";
				$sql .= "where default_setting_enabled = 'true' ";
				$prep_statement = $db->prepare($sql);
				$prep_statement->execute();
				$result_default_settings = $prep_statement->fetchAll(PDO::FETCH_NAMED);

			//get the default recordings directory
				foreach($result_default_settings as $row) {
					$name = $row['default_setting_name'];
					$category = $row['default_setting_category'];
					$subcategory = $row['default_setting_subcategory'];
					if ($category == 'switch' && $subcategory == 'recordings' && $name == 'dir') {
						$switch_recordings_dir = $row['default_setting_value'];
					}
				}

			//loop through all domains
				$sql = "select * from v_domains ";
				$v_prep_statement = $db->prepare(check_sql($sql));
				$v_prep_statement->execute();
				$main_result = $v_prep_statement->fetchAll(PDO::FETCH_ASSOC);
				$domain_count = count($main_result);
				$domains_processed = 1;
				foreach ($main_result as &$row) {
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
						foreach($result_defaults_settings as $row) {
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

					//get the domains settings
						$sql = "select * from v_domain_settings ";
						$sql .= "where domain_uuid = '".$domain_uuid."' ";
						$sql .= "and domain_setting_enabled = 'true' ";
						$prep_statement = $db->prepare($sql);
						$prep_statement->execute();
						$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
						foreach($result as $row) {
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

					//set the recordings directory
						if (strlen($switch_recordings_dir) > 1 && count($_SESSION["domains"]) > 1) {
							$_SESSION['switch']['recordings']['dir'] = $switch_recordings_dir."/".$domain_name;
						}

					//get the list of installed apps from the core and mod directories and execute the php code in app_defaults.php
						$default_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/*/*/app_defaults.php");
						foreach ($default_list as &$default_path) {
							include($default_path);
						}

					//track of the number of domains processed
						$domains_processed++;
				}
				unset ($v_prep_statement);

			//synchronize the dialplan
				if (function_exists('save_dialplan_xml')) {
					save_dialplan_xml();
				}

			//clear the session variables
				unset($_SESSION['domain']);
				unset($_SESSION['switch']);

		}
	}

?>