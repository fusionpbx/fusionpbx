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
  Tim Fry <tim.fry@hotmail.com>
 */

//check the permission
defined('STDIN') or die('Unauthorized');

//include files
require_once dirname(__DIR__, 2) . "/resources/require.php";

show_upgrade_menu();

function show_upgrade_menu() {
//		error_reporting(E_ALL);
	while (true) {
		echo "\n";
		echo "+------------------+\n";
		echo "| Set Default Menu |\n";
		echo "+------------------+\n";
		echo "\n";
		echo "1) Update FusionPBX\n";
		echo "2) Schema\n";
		echo "3) App Defaults\n";
		echo "4) Menus\n";
		echo "5) Permissions\n";
		echo "6) All of the above\n";
		echo "7) Validate schema data types\n";
		echo "8) Show current version\n";
		echo "9) Exit\n";
		echo "\n";
		echo "Choice: ";
		$choice = readline();
		switch ($choice) {
			case 1:
				do_upgrade_code();
				break;
			case 2:
				do_upgrade_schema();
				break;
			case 3:
				do_upgrade_domains();
				break;
			case 4:
				do_upgrade_menu();
				break;
			case 5:
				do_upgrade_permissions();
				break;
			case 6:
				do_upgrade_code();
				do_upgrade_schema();
				do_upgrade_domains();
				do_upgrade_permissions();
				do_upgrade_menu();
				break;
			case 7:
				do_upgrade_schema(true);
				break;
			case 8:
				show_software_version();
				break;
			case 9:
				exit();
		}
	}
}

//show the upgrade type
function show_software_version() {
	echo software::version() . "\n";
}

function do_upgrade_code() {
	//assume failed
	$result = ['result' => false, 'message' => 'Failed'];
	//global $conf;
	if (defined('PROJECT_ROOT')) {
		return git_pull(PROJECT_ROOT);
	}
	return $result;
}

//run all app_defaults.php files
function do_upgrade_domains() {
	require_once dirname(__DIR__, 2) . "/resources/classes/config.php";
	require_once dirname(__DIR__, 2) . "/resources/classes/domains.php";
	$domain = new domains;
	$domain->display_type = 'text';
	$domain->upgrade();
}

//upgrade schema and/or data_types
function do_upgrade_schema(bool $data_types = false) {
	//get the database schema put it into an array then compare and update the database as needed.
	require_once dirname(__DIR__, 2) . "/resources/classes/schema.php";
	$obj = new schema;
	$obj->data_types = $data_types;
	echo $obj->schema('text');
}

//restore the default menu
function do_upgrade_menu() {
	global $included, $sel_menu, $menu_uuid, $menu_language;
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
		require_once dirname(__DIR__, 2) . "/core/menu/menu_restore_default.php";
		unset($sel_menu);
		$text = (new text)->get(null, 'core/upgrade');
		//send message to the console
		echo $text['message-upgrade_menu'] . "\n";
	}
}

//restore the default permissions
function do_upgrade_permissions() {
	global $included;
	//default the permissions
	$included = true;
	require_once dirname(__DIR__, 2) . "/core/groups/permissions_default.php";

	//send message to the console
	$text = (new text)->get(null, 'core/upgrade');
	echo $text['message-upgrade_permissions'] . "\n";
}

//default upgrade schema and app defaults
function do_upgrade_defaults() {
	//add multi-lingual support
	$language = new text;
	$text = $language->get(null, 'core/upgrade');

	echo "\n";
	echo $text['label-upgrade'] . "\n";
	echo "-----------------------------------------\n";
	echo "\n";
	echo $text['label-database'] . "\n";

	//make sure the database schema and installation have performed all necessary tasks
	$obj = new schema;
	echo $obj->schema("text");

	//run all app_defaults.php files
	$domain = new domains;
	$domain->display_type = 'text';
	$domain->upgrade();

	echo "\n";
}

function load_config_php() {
	//if the config file doesn't exist and the config.php does exist use it to write a new config file
	//include the config.php
	include "/etc/fusionpbx/config.php";

	//set the default config file location
	if (stristr(PHP_OS, 'BSD')) {
		$config_path = '/usr/local/etc/fusionpbx';
		$config_file = $config_path . '/config.conf';
		$document_root = '/usr/local/www/fusionpbx';

		$conf_dir = '/usr/local/etc/freeswitch';
		$sounds_dir = '/usr/share/freeswitch/sounds';
		$database_dir = '/var/lib/freeswitch/db';
		$recordings_dir = '/var/lib/freeswitch/recordings';
		$storage_dir = '/var/lib/freeswitch/storage';
		$voicemail_dir = '/var/lib/freeswitch/storage/voicemail';
		$scripts_dir = '/usr/share/freeswitch/scripts';
	}
	if (stristr(PHP_OS, 'Linux')) {
		$config_path = '/etc/fusionpbx/';
		$config_file = $config_path . '/config.conf';
		$document_root = '/var/www/fusionpbx';

		$conf_dir = '/etc/freeswitch';
		$sounds_dir = '/usr/share/freeswitch/sounds';
		$database_dir = '/var/lib/freeswitch/db';
		$recordings_dir = '/var/lib/freeswitch/recordings';
		$storage_dir = '/var/lib/freeswitch/storage';
		$voicemail_dir = '/var/lib/freeswitch/storage/voicemail';
		$scripts_dir = '/usr/share/freeswitch/scripts';
	}

	//make the config directory
	if (isset($config_path)) {
		system('mkdir -p ' . $config_path);
	} else {
		echo "config directory not found\n";
		exit;
	}

	//build the config file
	$conf = "\n";
	$conf .= "#database system settings\n";
	$conf .= "database.0.type = " . $db_type . "\n";
	$conf .= "database.0.host = " . $db_host . "\n";
	$conf .= "database.0.port = " . $db_port . "\n";
	$conf .= "database.0.sslmode = prefer\n";
	$conf .= "database.0.name = " . $db_name . "\n";
	$conf .= "database.0.username = " . $db_username . "\n";
	$conf .= "database.0.password = " . $db_password . "\n";
	$conf .= "\n";
	$conf .= "#database switch settings\n";
	$conf .= "database.1.type = sqlite\n";
	$conf .= "database.1.path = " . $database_dir . "\n";
	$conf .= "database.1.name = core.db\n";
	$conf .= "\n";
	$conf .= "#general settings\n";
	$conf .= "document.root = " . $document_root . "\n";
	$conf .= "project.path =\n";
	$conf .= "temp.dir = /tmp\n";
	$conf .= "php.dir = " . PHP_BINDIR . "\n";
	$conf .= "php.bin = php\n";
	$conf .= "\n";
	$conf .= "#cache settings\n";
	$conf .= "cache.method = file\n";
	$conf .= "cache.location = /var/cache/fusionpbx\n";
	$conf .= "cache.settings = true\n";
	$conf .= "\n";
	$conf .= "#switch settings\n";
	$conf .= "switch.conf.dir = " . $conf_dir . "\n";
	$conf .= "switch.sounds.dir = " . $sounds_dir . "\n";
	$conf .= "switch.database.dir = " . $database_dir . "\n";
	$conf .= "switch.recordings.dir = " . $recordings_dir . "\n";
	$conf .= "switch.storage.dir = " . $storage_dir . "\n";
	$conf .= "switch.voicemail.dir = " . $voicemail_dir . "\n";
	$conf .= "switch.scripts.dir = " . $scripts_dir . "\n";
	$conf .= "\n";
	$conf .= "#switch xml handler\n";
	$conf .= "xml_handler.fs_path = false\n";
	$conf .= "xml_handler.reg_as_number_alias = false\n";
	$conf .= "xml_handler.number_as_presence_id = true\n";
	$conf .= "\n";
	$conf .= "#error reporting hide show all errors except notices and warnings\n";
	$conf .= "error.reporting = 'E_ALL ^ E_NOTICE ^ E_WARNING'\n";

	//write the config file
	$file_handle = fopen($config_file, "w");
	if (!$file_handle) {
		return;
	}
	fwrite($file_handle, $conf);
	fclose($file_handle);
}
