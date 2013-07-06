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
	Portions created by the Initial Developer are Copyright (C) 2008-2012
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	sreis
*/

	class domains {

		public function set() {

			//set the variable
				$db = $this->db;

			//clear the sessions
				unset($_SESSION['contact']);
				unset($_SESSION['domain']);
				unset($_SESSION['email']);
				unset($_SESSION['ldap']);
				unset($_SESSION['login']);
				unset($_SESSION['provision']);
				unset($_SESSION['security']);
				unset($_SESSION['server']);
				unset($_SESSION['switch']);

			//get the default settings
				$sql = "select * from v_default_settings ";
				$sql .= "where default_setting_enabled = 'true' ";
				$prep_statement = $db->prepare($sql);
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				foreach ($result as $row) {
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
					} else {
						if ($name == "array") {
							$_SESSION[$category][$subcategory][] = $row['default_setting_value'];
						}
						else {
							$_SESSION[$category][$subcategory][$name] = $row['default_setting_value'];
						}
					}
				}

			//get the domains settings
				$sql = "select * from v_domain_settings ";
				$sql .= "where domain_uuid = '" . $_SESSION["domain_uuid"] . "' ";
				$sql .= "and domain_setting_enabled = 'true' ";
				$prep_statement = $db->prepare($sql);
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
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
					} else {
						//$$category[$subcategory][$name] = $row['domain_setting_value'];
						if ($name == "array") {
							$_SESSION[$category][$subcategory][] = $row['domain_setting_value'];
						}
						else {
							$_SESSION[$category][$subcategory][$name] = $row['domain_setting_value'];
						}
					}
				}

			//get the user settings
				$sql = "select * from v_user_settings ";
				$sql .= "where domain_uuid = '" . $_SESSION["domain_uuid"] . "' ";
				$sql .= "and user_uuid = '" . $_SESSION["user_uuid"] . "' ";
				$sql .= "and user_setting_enabled = 'true' ";
				$prep_statement = $db->prepare($sql);
				if ($prep_statement) {
					$prep_statement->execute();
					$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
					foreach ($result as $row) {
						$name = $row['user_setting_name'];
						$category = $row['user_setting_category'];
						$subcategory = $row['user_setting_subcategory'];
						if (strlen($subcategory) == 0) {
							//$$category[$name] = $row['domain_setting_value'];
							if ($name == "array") {
								$_SESSION[$category][] = $row['user_setting_value'];
							}
							else {
								$_SESSION[$category][$name] = $row['user_setting_value'];
							}
						} else {
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
				if (count($_SESSION["domains"]) > 1) {
					$_SESSION["context"] = $_SESSION["domain_name"];
				} else {
					$_SESSION["context"] = 'default';
				}

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
	}

?>
