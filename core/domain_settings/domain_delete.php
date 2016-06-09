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
 Portions created by the Initial Developer are Copyright (C) 2008-2016
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('domain_delete')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the id
	if (count($_GET)>0) {
		$id = check_str($_GET["id"]);
	}

if (strlen($id) > 0) {
	//get the domain using the id
		$sql = "select * from v_domains ";
		$sql .= "where domain_uuid = '$id' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		if (isset($result)) foreach ($result as &$row) {
			$domain_name = $row["domain_name"];
		}
		unset ($prep_statement);

	//get the domain settings
		$sql = "select * from v_domain_settings ";
		$sql .= "where domain_uuid = '".$id."' ";
		$sql .= "and domain_setting_enabled = 'true' ";
		$prep_statement = $db->prepare($sql);
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		if (isset($result)) foreach($result as $row) {
			$name = $row['domain_setting_name'];
			$category = $row['domain_setting_category'];
			$subcategory = $row['domain_setting_subcategory'];
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
					$_SESSION[$category][$subcategory]['uuid'] = $row['default_setting_uuid'];
					$_SESSION[$category][$subcategory][$name] = $row['default_setting_value'];
				}
			}
		}

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
				$table_name = $row['table'];
				if (isset($row['fields'])) foreach ($row['fields'] as $field) {
					if ($field['name'] == "domain_uuid") {
						$sql = "delete from $table_name where domain_uuid = '$id' ";
						$db->query($sql);
					}
				}
			}
		}
		$db->commit();

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
	$_SESSION["message"] = $text['message-delete'];
	header("Location: domains.php");
	return;

?>