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
*/

//check the permission
	if(defined('STDIN')) {
		$document_root = str_replace("\\", "/", $_SERVER["PHP_SELF"]);
		preg_match("/^(.*)\/core\/.*$/", $document_root, $matches);
		$document_root = $matches[1];
		set_include_path($document_root);
		require_once "resources/require.php";
		$_SERVER["DOCUMENT_ROOT"] = $document_root;
		$display_type = 'text'; //html, text
	}
	else {
		require_once "root.php";
		require_once "resources/require.php";
		require_once "resources/check_auth.php";
		if (permission_exists('upgrade_schema') || permission_exists('upgrade_source') || if_group("superadmin")) {
			//echo "access granted";
		}
		else {
			echo "access denied";
			exit;
		}
		$display_type = 'html'; //html, text
	}

//set the default upgrade type
	$upgrade_type = 'defaults';

//get the command line arguments
	if(defined('STDIN')) {
		//$application_name = $argv[0];
		if (isset($argv[1])) {
			$upgrade_type = $argv[1];
		}
	}

//show the upgrade type
	//echo $upgrade_type."\n";

//run all app_defaults.php files
	if ($upgrade_type == 'domains') {
		require_once "resources/classes/config.php";
		require_once "resources/classes/domains.php";
		$domain = new domains;
		$domain->display_type = $display_type;
		$domain->upgrade();
	}

//upgrade schema and/or data_types
	if ($upgrade_type == 'schema') {
		//get the database schema put it into an array then compare and update the database as needed.
		require_once "resources/classes/schema.php";
		$obj = new schema;
		if (isset($argv[2]) && $argv[2] == 'data_types') {
			$obj->data_types = true;
		}
		echo $obj->schema($format);
	}

//restore the default menu
	if ($upgrade_type == 'menu') {

		//get the menu uuid and language
		$sql = "select menu_uuid, menu_language from v_menus ";
		$sql .= "where menu_name = :menu_name ";
		$parameters['menu_name'] = 'default';
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && sizeof($row) != 0) {
			$menu_uuid = $row["menu_uuid"];
			$menu_language = $row["menu_language"];
		}
		unset($sql, $parameters, $row);

		//show the menu
		if (isset($argv[2]) && $argv[2] == 'view') {
			print_r($_SESSION["menu"]);
		}

		//set the menu back to default
		if (isset($argv[2]) && (is_null($argv[2]) || $argv[2] == 'default')) {
			//restore the menu
			$included = true;
			require_once("core/menu/menu_restore_default.php");
			unset($sel_menu);
		
			//send message to the console
			echo $text['message-upgrade_menu']."\n";
		}
	}

//restore the default permissions
	if ($upgrade_type == 'permissions') {
		//default the permissions
		$included = true;
		require_once("core/groups/permissions_default.php");

		//send message to the console
		echo $text['message-upgrade_permissions']."\n";
	}

//default upgrade schema and app defaults
	if ($upgrade_type == 'defaults') {
		//add multi-lingual support
			require_once "resources/classes/text.php";
			$language = new text;
			$text = $language->get();
		
		//show the title
			if ($display_type == 'text') {
				echo "\n";
				echo $text['label-upgrade']."\n";
				echo "-----------------------------------------\n";
				echo "\n";
				echo $text['label-database']."\n";
			}
		
		//make sure the database schema and installation have performed all necessary tasks
			require_once "resources/classes/schema.php";
			$obj = new schema;
			echo $obj->schema("text");
		
		//run all app_defaults.php files
			require_once "resources/classes/domains.php";
			$domain = new domains;
			$domain->display_type = $display_type;
			$domain->upgrade();
		
		//show the content
			if ($display_type == 'html') {
				echo "<div align='center'>\n";
				echo "<table width='40%'>\n";
				echo "<tr>\n";
				echo "<th align='left'>".$text['header-message']."</th>\n";
				echo "</tr>\n";
				echo "<tr>\n";
				echo "<td class='row_style1'><strong>".$text['message-upgrade']."</strong></td>\n";
				echo "</tr>\n";
				echo "</table>\n";
				echo "</div>\n";
		
				echo "<br />\n";
				echo "<br />\n";
				echo "<br />\n";
				echo "<br />\n";
				echo "<br />\n";
				echo "<br />\n";
				echo "<br />\n";
			}
			elseif ($display_type == 'text') {
				echo "\n";
			}
		
		//include the footer
			if ($display_type == "html") {
				require_once "resources/footer.php";
			}
	}

?>
