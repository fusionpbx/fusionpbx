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
  Tim Fry <tim.fry@hotmail.com>
 */

//check the permission
defined('STDIN') or die('Unauthorized');

//include files
require_once dirname(__DIR__, 2) . "/resources/require.php";

//create a database connection using default config
$config = config::load();
$database = database::new(['config' => $config]);

//load global defaults
$settings = new settings(['database' => $database]);

//get the language code from global defaults
$language_code = $settings->get('domain', 'language');

//get the software name
$software_name = $settings->get('theme', 'title');

//set the scope for text to be used in any function
global $text, $display_type;

//add multi-lingual support
$language = new text;
$text = $language->get($language_code, 'core/upgrade');

//output to text type instead of html
$display_type = 'text';

//run the upgrade menu
show_upgrade_menu();

/**
 * Show upgrade menu
 * @global type $text
 * @global type $software_name
 */
function show_upgrade_menu() {
	//set the global variables
	global $text, $software_name, $settings;

	//debug information
	//error_reporting(E_ALL);

	//build the options array
	$options = [
		['1', $text['label-upgrade_source'], $text['description-update_all_source_files']],
		['1a', $text['label-main_software'], $text['description-main_software']],
		['1b', $text['label-optional_applications'], $text['description-optional_applications']],
		['2', $text['label-database'], $text['description-upgrade_schema']],
		['2a', $text['label-schema'], $text['description-upgrade_schema']],
		['2b', $text['label-upgrade_data_types'], $text['description-upgrade_data_types']],
		['3', $text['label-upgrade_apps'], $text['description-upgrade_apps']],
		['4', $text['label-upgrade_menu'], $text['description-upgrade_menu']],
		['5', $text['label-upgrade_permissions'], $text['description-upgrade_permissions']],
		['6', $text['label-update_file_permissions'], $text['description-update_file_permissions']],
		['7', $text['label-upgrade_services'], $text['description-upgrade_services']],
		['8', $text['label-restart_services'], $text['description-restart_services']]
	];

	$line = str_repeat('-', strlen($text['title-cli_upgrade']) + 8);

	//show the menu
	while (true) {
		//show the upgrade title
		echo $text['title-cli_upgrade'] . "\n";
		echo "  ".$software_name . " " . show_software_version();
		echo "\n";

		//show the command menu
		echo "Options:\n";
		foreach ($options as list($key, $label, $description)) {
			if (!is_numeric($key)) { echo "    "; }
			echo "  $key) $label" . ((!empty($option) && $option == 'h') ? " - " . $description : "") . "\n";
		}
		echo "\n";
		echo "  a) All\n";
		echo "  h) Help\n";
		echo "  q) Quit\n";

		//prompt for user input
		echo "\n";
		echo "Enter an Option: ";

		//read the input and then call the correction actions
		$option = readline();
		switch ($option) {
			case 1:
				do_upgrade_code();
				do_upgrade_code_submodules();
				do_upgrade_auto_loader();
				break;
			case '1a':
				do_upgrade_code();
				do_upgrade_auto_loader();
				break;
			case '1b':
				do_upgrade_code_submodules();
				do_upgrade_auto_loader();
				break;
			case 2:
				do_upgrade_schema(true);
				break;
			case '2a':
				do_upgrade_schema();
				break;
			case '2b':
				do_upgrade_schema(true);
				break;
			case 3:
				do_upgrade_auto_loader();
				do_upgrade_domains();
				break;
			case 4:
				do_upgrade_auto_loader();
				do_upgrade_menu();
				break;
			case 5:
				do_upgrade_auto_loader();
				do_upgrade_permissions();
				break;
			case 6:
				do_upgrade_auto_loader();
				do_file_permissions($text, $settings);
				break;
			case 7:
				do_upgrade_auto_loader();
				do_upgrade_services($text, $settings);
				break;
			case 8:
				do_restart_services($text, $settings);
				break;
			case 'a':
				do_upgrade_code();
				do_upgrade_auto_loader();
				do_upgrade_schema(true);
				do_upgrade_domains();
				do_upgrade_menu();
				do_upgrade_permissions();
				do_file_permissions($text, $settings);
				do_upgrade_services($text, $settings);
				do_restart_services($text, $settings);
				break;
			case 'q':
				exit();
		}

		//add a few line feeds
		echo "\n\n";
	}
}

/**
 * Rebuild the cache file
 * @global type $text
 */
function do_upgrade_auto_loader() {
	global $text, $autoload;

	//remove temp files
	unlink(sys_get_temp_dir() . '/' . auto_loader::CLASSES_FILE);
	unlink(sys_get_temp_dir() . '/' . auto_loader::INTERFACES_FILE);

	//create a new instance of the autoloader
	$autoload->update();
	echo "{$text['message-updated_autoloader']}\n";
}

/**
 * Update file system permissions
 */
function do_file_permissions($text, settings $settings) {

	echo ($text['label-header1'] ?? "Root account or sudo account must be used for this option") . "\n";
	echo ($text['label-header2'] ?? "This option is used for resetting the permissions on the filesystem after executing commands using the root user account") . "\n";
	if (is_root_user()) {
		//initialize the array
		$directories = [];

		//get the fusionpbx folder
		$project_root = dirname(__DIR__, 2);

		//adjust the project root
		$directories[] = $project_root;

		//adjust the /etc/freeswitch
		$directories[] = $settings->get('switch', 'conf', null);
		$directories[] = $settings->get('switch', 'call_center', null); //normally in conf but can be different
		$directories[] = $settings->get('switch', 'dialplan', null); //normally in conf but can be different
		$directories[] = $settings->get('switch', 'directory', null); //normally in conf but can be different
		$directories[] = $settings->get('switch', 'languages', null); //normally in conf but can be different
		$directories[] = $settings->get('switch', 'sip_profiles', null); //normally in conf but can be different

		//adjust the /usr/share/freeswitch/{scripts,sounds}
		$directories[] = $settings->get('switch', 'scripts', null);
		$directories[] = $settings->get('switch', 'sounds', null);

		//adjust the /var/lib/freeswitch/{db,recordings,storage,voicemail}
		$directories[] = $settings->get('switch', 'db', null);
		$directories[] = $settings->get('switch', 'recordings', null);
		$directories[] = $settings->get('switch', 'storage', null);
		$directories[] = $settings->get('switch', 'voicemail', null); //normally included in storage but can be different

		//only set the xml_cdr directory permissions
		$log_directory = $settings->get('switch', 'log', null);
		if ($log_directory !== null) {
			$directories[] = $log_directory . '/xml_cdr';
		}

		//update the auto_loader cache permissions file
		$directories[] = sys_get_temp_dir() . '/' . auto_loader::CLASSES_FILE;

		//execute chown command for each directory
		foreach ($directories as $dir) {
			//skip empty directories
			if (empty($dir)) { continue; }

			//skip /dev/shm directory
			if (strpos($dir, '/dev/shm') !== false) {
				continue;
			}

			//notify user
			echo "chown -R www-data:www-data $dir\n";

			//execute
			exec("chown -R www-data:www-data $dir");
		}
	} else {
		echo ($text['label-not_running_as_root'] ?? "Not root user - operation skipped")."\n";
	}
}

function current_user(): ?string {
	return posix_getpwuid(posix_getuid())['name'] ?? null;
}

//show the upgrade type
function show_software_version(): ?string {
	return software::version() . "\n";
}

/**
 * Upgrade the source folder
 * @return type
 */
function do_upgrade_code() {
	//assume failed
	$result = ['result' => false, 'message' => 'Failed'];
	//global $conf;
	if (defined('PROJECT_ROOT')) {
		$result = git_pull(PROJECT_ROOT);
		if (!empty($result['message']) && is_array($result['message'])) {
			echo implode("\n", $result['message']);
			echo "\n";
		}
	}
	return;
}

/**
 * Upgrade any of the git submodules
 * @global type $text
 */
function do_upgrade_code_submodules() {
	global $text;
	$updateable_repos = git_find_repos(dirname(__DIR__, 2)."/app");

	$messages = [];
	foreach ($updateable_repos as $repo => $apps) {
		$git_result = git_pull($repo);
		if ($git_result['result']) {
			$messages[$repo] = $text['message-optional_apps_upgrade_source_cli'] . (!empty($git_result['message']) && is_array($git_result['message']) ? ' - '.implode("\n", $git_result['message']) : '');
		}
		else {
			if (!empty($git_result['message']) && is_array($git_result['message'])) {
				$message = "ERROR:\n" . implode("\n", $git_result['message']);
			} else {
				$message = $git_result['message'];
			}
			$messages[$repo] = $text['message-optional_apps_upgrade_source_failed_cli'] . " - " . $message;
		}
	}
	foreach ($messages as $repo => $message) {
		echo $repo.": ".$message."\n";
	}
}

/**
 * Execute all app_defaults.php files
 */
function do_upgrade_domains() {
	$domain = new domains;
	$domain->display_type = 'text';
	$domain->upgrade();
}

/**
 * Upgrade schema and/or data_types
 */
function do_upgrade_schema(bool $data_types = false) {
	//get the database schema put it into an array then compare and update the database as needed.
	$obj = new schema;
	$obj->data_types = $data_types;
	echo $obj->schema('text');
}

/**
 * Restore the default menu
 */
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
	if (!isset($argv[2]) || $argv[2] == 'default') {
		//restore the menu
		$included = true;
		require_once dirname(__DIR__, 2) . "/core/menu/menu_restore_default.php";
		unset($sel_menu);
		$text = (new text)->get(null, 'core/upgrade');
		//send message to the console
		echo $text['message-upgrade_menu'] . "\n";
	}
}

/**
 * Restore the default permissions
 */
function do_upgrade_permissions() {
	global $included;
	//default the permissions
	$included = true;
	require_once dirname(__DIR__, 2) . "/core/groups/permissions_default.php";

	//send message to the console
	$text = (new text)->get(null, 'core/upgrade');
	echo $text['message-upgrade_permissions'] . "\n";
}

/**
 * Default upgrade schema and app defaults
 */
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

/**
 * Upgrade services
 */
function do_upgrade_services($text, settings $settings) {
	echo ($text['description-upgrade_services'] ?? "")."\n";
	$core_files = glob(dirname(__DIR__, 2) . "/core/*/resources/service/*.service");
	$app_files = glob(dirname(__DIR__, 2) . "/app/*/resources/service/*.service");
	$service_files = array_merge($core_files, $app_files);
	foreach($service_files as $file) {
		$service_name = get_service_name($file);
		echo " ".$service_name."\n";
		system("cp " . escapeshellarg($file) . " /etc/systemd/system/" . escapeshellarg($service_name) . ".service");
		system("systemctl daemon-reload");
		system("systemctl enable --now " . escapeshellarg($service_name));
	}
}

/**
 * Get the service name
 * @param string $file
 */
function get_service_name(string $file) {
	$parsed = parse_ini_file($file);
	$exec_cmd = $parsed['ExecStart'];
	$parts = explode(' ', $exec_cmd);
	$php_file = $parts[1] ?? '';
	if (!empty($php_file)) {
		$path_info = pathinfo($php_file);
		return $path_info['filename'];
	}
	return '';
}


/**
 * Checks if the current user has root privileges.
 *
 * @return bool Returns true if the current user is the root user, false otherwise.
 */
function is_root_user(): bool {
	return posix_getuid() === 0;
}


/**
 * Restart services
 */
function do_restart_services($text, settings $settings) {
	echo ($text['description-restart_services'] ?? "")."\n";
	$core_files = glob(dirname(__DIR__, 2) . "/core/*/resources/service/*.service");
	$app_files = glob(dirname(__DIR__, 2) . "/app/*/resources/service/*.service");
	$service_files = array_merge($core_files, $app_files);
	foreach($service_files as $file) {
		$service_name = get_service_name($file);
		echo " ".$service_name."\n";
		system("systemctl restart ".$service_name);
	}
}

/**
 * Load the old config.php file
 * @return type
 */
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
	$conf .= "#error reporting hide all errors\n";
	$conf .= "error.reporting = user\n";

	//write the config file
	$file_handle = fopen($config_file, "w");
	if (!$file_handle) {
		return;
	}
	fwrite($file_handle, $conf);
	fclose($file_handle);
}
