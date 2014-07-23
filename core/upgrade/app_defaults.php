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
*/

if (strlen($_SESSION['switch']['scripts']['dir']) > 0) {
	if ($domains_processed == 1) {
		//if the resource scripts resource directory does not exist then create it
			if (!is_dir($_SESSION['switch']['scripts']['dir']."/resources")) {
					mkdir($_SESSION['switch']['scripts']['dir']."/resources",0755,true);
			}

		//copy the files and directories from resources/install
			//require_once "resources/classes/install.php";
			$install = new install;
			$install->domain_uuid = $domain_uuid;
			$install->domain_name = $domain;
			//$install->switch_conf_dir = $_SESSION['switch']['conf']['dir'];
			$install->switch_scripts_dir = $_SESSION['switch']['scripts']['dir'];
			//$install->switch_sounds_dir = $_SESSION['switch']['sounds']['dir'];
			$install->copy_scripts();
			//$install->copy_sounds();
			//print_r($install->result);

		//update the software table
			$sql = "select count(*) as num_rows from v_software ";
			$prep_statement = $db->prepare($sql);
			if ($prep_statement) {
				$prep_statement->execute();
				$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
				if ($row['num_rows'] == 0) {
					$sql = "insert into v_software ";
					$sql .= "(";
					$sql .= "software_uuid, ";
					$sql .= "software_name, ";
					$sql .= "software_url, ";
					$sql .= "software_version ";
					$sql .= ")";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'".uuid()."', ";
					$sql .= "'FusionPBX', ";
					$sql .= "'www.fusionpbx.com', ";
					$sql .= "'".software_version()."' ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					unset($sql);
				}
				else {
					$sql = "update v_software ";
					$sql .= "set software_version = '".software_version()."' ";
					$db->exec(check_sql($sql));
					unset($sql);
				}
				unset($prep_statement, $row);
			}

		//ensure the login message is set, if new message exists
			$sql = "select count(*) as num_rows from v_default_settings ";
			$sql .= "where default_setting_category = 'login' ";
			$sql .= "and default_setting_subcategory = 'message' ";
			$sql .= "and default_setting_name = 'text' ";
			$prep_statement = $db->prepare($sql);
			if ($prep_statement) {
				$prep_statement->execute();
				$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
				unset($prep_statement);
				if ($row['num_rows'] == 0) {

					// insert message
					$sql = "insert into v_default_settings ";
					$sql .= "(";
					$sql .= "default_setting_uuid, ";
					$sql .= "default_setting_category, ";
					$sql .= "default_setting_subcategory, ";
					$sql .= "default_setting_name, ";
					$sql .= "default_setting_value, ";
					$sql .= "default_setting_enabled, ";
					$sql .= "default_setting_description ";
					$sql .= ")";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'".uuid()."', ";
					$sql .= "'login', ";
					$sql .= "'message', ";
					$sql .= "'text', ";
					$sql .= "'".$text['login-message_text']."', ";
					$sql .= "'true', ";
					$sql .= "'' ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					unset($sql);

				}
				else {

					// get current message value
					$sql = "select default_setting_uuid, default_setting_value ";
					$sql .= "from v_default_settings ";
					$sql .= "where default_setting_category = 'login' ";
					$sql .= "and default_setting_subcategory = 'message' ";
					$sql .= "and default_setting_name = 'text' ";
					$prep_statement = $db->prepare($sql);
					if ($prep_statement) {
						$prep_statement->execute();
						$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
						if (count($result) > 0) {
							foreach($result as $row) {
								$current_default_setting_uuid = $row["default_setting_uuid"];
								$current_default_setting_value = $row["default_setting_value"];
								break;
							}

							// compare to message in language file, update and enable if different
							$new_default_setting_value = str_replace("''", "'", $text['login-message_text']);
							if ($current_default_setting_value != $new_default_setting_value) {
								$sql = "update v_default_settings set ";
								$sql .= "default_setting_value = '".$text['login-message_text']."', ";
								$sql .= "default_setting_enabled = 'true' ";
								$sql .= "where default_setting_uuid = '".$current_default_setting_uuid."' ";
								$db->exec(check_sql($sql));
								unset($sql);
							}
						}
						unset($prep_statement, $result);
					}
				}
			}

	}
}

?>