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
 Portions created by the Initial Developer are Copyright (C) 2008-2019
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('domain_all') && permission_exists('domain_delete')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();


//delete domain data and files
	if (is_uuid($_GET["id"])) {
		$id = $_GET["id"];

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
			$config_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/*/*/app_config.php");
			$x=0;
			if (isset($config_list)) foreach ($config_list as &$config_path) {
				include($config_path);
				$x++;
			}

		//delete the domain data from all tables in the database
			$db->beginTransaction();
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
							if ($field['name'] == "domain_uuid") {
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
			$db->commit();

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
				unlink($_SESSION['switch']['dialplan']['dir'].'/'.$domain_name.'.xml');
				if (strlen($_SESSION['switch']['dialplan']['dir']) > 0) {
					system('rm -rf '.$_SESSION['switch']['dialplan']['dir'].'/'.$domain_name);
				}

				//delete the dialplan public
				unlink($_SESSION['switch']['dialplan']['dir'].'/public/'.$domain_name.'.xml');
				if (strlen($_SESSION['switch']['dialplan']['dir']) > 0) {
					system('rm -rf '.$_SESSION['switch']['dialplan']['dir'].'/public/'.$domain_name);
				}

				//delete the extension
				unlink($_SESSION['switch']['extensions']['dir'].'/'.$domain_name.'.xml');
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
									unlink($_SESSION['switch']['sip_profiles']['dir']."/".$file);
								}
							}
						}
					}
					closedir($dh);
				}

				//delete the ivr menu
				if($dh = opendir($_SESSION['switch']['conf']['dir']."/ivr_menus/")) {
					$files = Array();
					while($file = readdir($dh)) {
						if($file != "." && $file != ".." && $file[0] != '.') {
							if(is_dir($dir . "/" . $file)) {
								//this is a directory
							} else {
								if (strpos($file, $v_needle) !== false && substr($file,-4) == '.xml') {
									unlink($_SESSION['switch']['conf']['dir']."/ivr_menus/".$file);
								}
							}
						}
					}
					closedir($dh);
				}

				//delete the recordings
				if (strlen($_SESSION['switch'][recordings]['dir']) > 0) {
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
			unset($_SESSION["domain_uuid"]);
			unset($_SESSION["domain_name"]);
			unset($_SESSION['domain']);
			unset($_SESSION['switch']);
	}

//redirect the browser
	message::add($text['message-delete']);
	header("Location: domains.php");
	return;

?>
