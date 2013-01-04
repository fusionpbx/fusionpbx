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

//check the permission
	if(defined('STDIN')) {
		$document_root = str_replace("\\", "/", $_SERVER["PHP_SELF"]);
		preg_match("/^(.*)\/core\/.*$/", $document_root, $matches);
		$document_root = $matches[1];
		set_include_path($document_root);
		require_once "includes/require.php";
		$_SERVER["DOCUMENT_ROOT"] = $document_root;
		$display_type = 'text'; //html, text
	}
	else {
		include "root.php";
		require_once "includes/require.php";
		require_once "includes/checkauth.php";
		if (permission_exists('upgrade_schema') || permission_exists('upgrade_svn') || if_group("superadmin")) {
			//echo "access granted";
		}
		else {
			echo "access denied";
			exit;
		}
	}

//copy the files and directories from includes/install
	require_once "includes/classes/install.php";
	$install = new install;
	$install->domain_uuid = $domain_uuid;
	$install->domain_name = $domain;
	$install->switch_conf_dir = $_SESSION['switch']['conf']['dir'];
	$install->switch_scripts_dir = $_SESSION['switch']['scripts']['dir'];
	$install->switch_sounds_dir = $_SESSION['switch']['sounds']['dir'];
	$install->copy();
	//print_r($install->result);

//get the list of installed apps from the core and mod directories
	$config_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/*/*/app_config.php");
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
			if ($row['domain_name'] == $domain_array[0] || $row['domain_name'] == 'www.'.$domain_array[0]) {
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
			if ($domain_count == 1) {
				$context = "default";
			}
			else {
				$context = $domain_name;
			}

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
					$_SESSION[$category][$name] = $row['default_setting_value'];
				}
				else {
					$_SESSION[$category][$subcategory][$name] = $row['default_setting_value'];
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

?>