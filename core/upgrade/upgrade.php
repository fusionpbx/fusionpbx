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
*/

//include file
	require dirname(__DIR__, 2) . "/resources/require.php";

//if the config file doesn't exist and the config.php does exist use it to write a new config file
	if (isset($config_exists) && !$config_exists && file_exists("/etc/fusionpbx/config.php")) {
		//include the config.php
		include("/etc/fusionpbx/config.php");

		//set the default config file location
		if (stristr(PHP_OS, 'BSD')) {
			$config_path = '/usr/local/etc/fusionpbx';
			$config_file = $config_path.'/config.conf';
			$document_root = '/usr/local/www/fusionpbx';

			$conf_dir = '/usr/local/etc/freeswitch';
			$sounds_dir = '/usr/share/freeswitch/sounds';
			$database_dir = '/var/lib/freeswitch/db';
			$recordings_dir = '/var/lib/freeswitch/recordings';
			$storage_dir = '/var/lib/freeswitch/storage';
			$voicemail_dir = '/var/lib/freeswitch/storage/voicemail';
			$scripts_dir = '/usr/share/freeswitch/scripts';
			$php_dir = PHP_BINDIR;
			$cache_location = '/var/cache/fusionpbx';
		}
		if (stristr(PHP_OS, 'Linux')) {
			$config_path = '/etc/fusionpbx/';
			$config_file = $config_path.'/config.conf';
			$document_root = '/var/www/fusionpbx';

			$conf_dir = '/etc/freeswitch';
			$sounds_dir = '/usr/share/freeswitch/sounds';
			$database_dir = '/var/lib/freeswitch/db';
			$recordings_dir = '/var/lib/freeswitch/recordings';
			$storage_dir = '/var/lib/freeswitch/storage';
			$voicemail_dir = '/var/lib/freeswitch/storage/voicemail';
			$scripts_dir = '/usr/share/freeswitch/scripts';
			$php_dir = PHP_BINDIR;
			$cache_location = '/var/cache/fusionpbx';
		}
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$system_drive = getenv('SystemDrive');
			$config_path = $system_drive . DIRECTORY_SEPARATOR . 'ProgramData' . DIRECTORY_SEPARATOR . 'fusionpbx' ;
			$config_file = $config_path.DIRECTORY_SEPARATOR.'config.conf';
			$document_root = $_SERVER["DOCUMENT_ROOT"];

			$conf_dir = $_SERVER['ProgramFiles'].DIRECTORY_SEPARATOR.'freeswitch'.DIRECTORY_SEPARATOR.'conf';
			$sounds_dir = $_SERVER['ProgramFiles'].DIRECTORY_SEPARATOR.'freeswitch'.DIRECTORY_SEPARATOR.'sounds';
			$database_dir = $_SERVER['ProgramFiles'].DIRECTORY_SEPARATOR.'freeswitch'.DIRECTORY_SEPARATOR.'db';
			$recordings_dir = $_SERVER['ProgramFiles'].DIRECTORY_SEPARATOR.'freeswitch'.DIRECTORY_SEPARATOR.'recordings';
			$storage_dir = $_SERVER['ProgramFiles'].DIRECTORY_SEPARATOR.'freeswitch'.DIRECTORY_SEPARATOR.'storage';
			$voicemail_dir = $_SERVER['ProgramFiles'].DIRECTORY_SEPARATOR.'freeswitch'.DIRECTORY_SEPARATOR.'voicemail';
			$scripts_dir = $_SERVER['ProgramFiles'].DIRECTORY_SEPARATOR.'freeswitch'.DIRECTORY_SEPARATOR.'scripts';
			$php_dir = dirname(PHP_BINARY);
			$cache_location = dirname($_SERVER['DOCUMENT_ROOT']).DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'fusionpbx';
		}

		//make the config directory
		if (isset($config_path)) {
			system('mkdir -p '.$config_path);
		}
		else {
			echo "config directory not found\n";
			exit;
		}

		//build the config file
		$conf = "\n";
		$conf .= "#database system settings\n";
		$conf .= "database.0.type = ".$db_type."\n";
		$conf .= "database.0.host = ".$db_host."\n";
		$conf .= "database.0.port = ".$db_port."\n";
		$conf .= "database.0.sslmode = prefer\n";
		$conf .= "database.0.name = ".$db_name."\n";
		$conf .= "database.0.username = ".$db_username."\n";
		$conf .= "database.0.password = ".$db_password."\n";
		$conf .= "\n";
		$conf .= "#database switch settings\n";
		$conf .= "database.1.type = sqlite\n";
		$conf .= "database.1.path = ".$database_dir."\n";
		$conf .= "database.1.name = core.db\n";
		$conf .= "\n";
		$conf .= "#general settings\n";
		$conf .= "document.root = ".$document_root."\n";
		$conf .= "project.path =\n";
		$conf .= "temp.dir = /tmp\n";
		$conf .= "php.dir = ".$php_dir."\n";
		$conf .= "php.bin = php\n";
		$conf .= "\n";
		$conf .= "#cache settings\n";
		$conf .= "cache.method = file\n";
		$conf .= "cache.location = ".$cache_location."\n";
		$conf .= "cache.settings = true\n";
		$conf .= "\n";
		$conf .= "#switch settings\n";
		$conf .= "switch.conf.dir = ".$conf_dir."\n";
		$conf .= "switch.sounds.dir = ".$sounds_dir."\n";
		$conf .= "switch.database.dir = ".$database_dir."\n";
		$conf .= "switch.recordings.dir = ".$recordings_dir."\n";
		$conf .= "switch.storage.dir = ".$storage_dir."\n";
		$conf .= "switch.voicemail.dir = ".$voicemail_dir."\n";
		$conf .= "switch.scripts.dir = ".$scripts_dir."\n";
		$conf .= "\n";
		$conf .= "#switch xml handler\n";
		$conf .= "xml_handler.fs_path = false\n";
		$conf .= "xml_handler.reg_as_number_alias = false\n";
		$conf .= "xml_handler.number_as_presence_id = true\n";
		$conf .= "\n";
		$conf .= "#error reporting hide show all errors except notices and warnings\n";
		$conf .= "error.reporting = 'E_ALL ^ E_NOTICE ^ E_WARNING'\n";

		//write the config file
		$file_handle = fopen($config_file,"w");
		if (!$file_handle){ return; }
		fwrite($file_handle, $conf);
		fclose($file_handle);
	}

//check the permission
	if (defined('STDIN')) {
		$display_type = 'text'; //html, text
	}
	else {
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

//set the default as an empty string
	$upgrade_type = '';

//get the command line arguments
	if (defined('STDIN')) {
		if (!empty($argv[1])) {
			$upgrade_type = $argv[1];
		}
	}

//use upgrade language file
	$language = new text;
	$text = $language->get(null, 'core/upgrade');

//always update the now global autoload cache just-in-case the source files have updated
	$autoload->update();

//trigger clear cache for any classes that require it
	foreach ($autoload->get_interface_list('clear_cache') as $class) {
		$class::clear_cache();
	}

//show the title
	if ($display_type == 'text') {
		//echo "\n";
		//echo $text['label-upgrade']."\n";
		echo "\n";
	}

//show the help menu
	if ($upgrade_type == 'help' or $upgrade_type == '-h' or $upgrade_type == '--help') {

		//send the help message to the console
		echo "Usage:\n";
		echo "  upgrade.php [-hvnostdmpgfui] [--menu [default|list]] [--services [update|restart]]\n";
		echo "  A terminal-based upgrade tool used to simplify or automate the upgrade.\n";
		echo "\n";
		echo "Options:\n";
		echo "  -h --help                              Show this help message.\n";
		echo "  -v --version                           Show the version.\n";
		echo "  -n --main                              Update the main application.\n";
		echo "  -o --optional                          Update the optional applications.\n";
		echo "  -s --schema                            Check the table and field structure.\n";
		echo "  -t --types                             Updates field data types as needed.\n";
		echo "  -d --defaults                          Restore application defaults.\n";
		echo "  -m --menu [default|list]               Restore menu default or show the menu list\n";
		echo "  -p --permissions                       Restore default file and group permissions.\n";
		echo "  -g --group                             Restore default group permissions.\n";
		echo "  -f --file                              Update the file permissions.\n";
		echo "  -u --service [update|stop|restart]     Update and restart services.\n";
		echo "  -i --interactive                       Show the interactive menu.\n";
		echo "\n";

	}

//get the version of the software
	if ($upgrade_type == 'version' or $upgrade_type == '-v' or $upgrade_type == '--version') {
		echo software::version()."\n";
	}

//upgrade schema and/or data_types
	if ($upgrade_type == 'schema' or $upgrade_type == '-s' or $upgrade_type == '--schema') {
		//send a message to the console
		if ($display_type === 'text') {
			echo "[ Update ] Table and field structure.\n";
		}

		//get the database schema put it into an array then compare and update the database as needed.
		$obj = new schema;
		if (isset($argv[2]) && $argv[2] == 'data_types') {
			$obj->data_types = true;
		}
		$response = $obj->schema($format ?? '');
		if ($display_type === 'text') {
			foreach(explode("\n", $response) as $row) {
				echo "        ".trim($row)."\n";
			}
		}
	}

//upgrade schema and/or data_types
	if ($upgrade_type == 'data_types' or $upgrade_type == '-t' or $upgrade_type == '--types') {
		//send a message to the console
		if ($display_type === 'text') {
			echo "[ Update ] Table, field structure and data types.\n";
		}

		//get the database schema put it into an array then compare and update the database as needed.
		$obj = new schema;
		$obj->data_types = true;
		$response = $obj->schema($format ?? '');
		if ($display_type === 'text') {
			foreach(explode("\n", $response) as $row) {
				echo "        ".trim($row)."\n";
			}
		}
	}

//run all application defaults - add missing defaults
	if ($upgrade_type == 'defaults' or $upgrade_type == '-d' or $upgrade_type == '--defaults') {
		//send a message to the console
		if ($display_type === 'text') {
			echo "[ Update ] Restore application defaults.\n";
		}

		//run for command line only
		if (defined('STDIN')) {
			$nginx_path = '/etc/nginx/sites-enabled/fusionpbx';
			if (file_exists($nginx_path)) {
				//get the nginx configuration
				$nginx_config = file_get_contents($nginx_path);

				// define the location block to add if it doesn't exist
				$websocket_settings = "        #redirect websockets to port 8080\n";
				$websocket_settings .= "                location /websockets/ {\n";
				$websocket_settings .= "                proxy_pass http://127.0.0.1:8080;\n";
				$websocket_settings .= "                proxy_http_version 1.1;\n";
				$websocket_settings .= "                proxy_set_header Upgrade \$http_upgrade;\n";
				$websocket_settings .= "                proxy_set_header Connection \"upgrade\";\n";
				$websocket_settings .= "                proxy_set_header Host \$host;\n";
				$websocket_settings .= "        }\n";
				$websocket_settings .= "\n";

				// search array
				$search_array['0'] = 'listen 443 ssl;';
				$search_array['1'] = '#redirect letsencrypt to dehydrated';

				// add the websocket settings if it is not in the config
				if (strpos($nginx_config, '/websockets/') === false) {

					// find the position where websockets string should be added.
					$ssl_found = false;
					$character_count = 0;
					$i = 1;
					foreach(explode("\n", $nginx_config) as $line) {
						// count each line and add an additional character for the line feed
						$character_count += strlen($line) + 1;

						// find the section for ssl on port 443
						if (trim($line) == $search_array[0]) {
							$ssl_found = true;
						}

						// find the second search string inside the ssl section
						if ($ssl_found && trim($line) == $search_array[1]) {
							// use substr_replace to add the string at the correct position
							$new_config = substr_replace($nginx_config, $websocket_settings, $character_count - strlen($line."\n"), 0);

							// write the updated configuration back to the file
							if (file_put_contents($nginx_path, $new_config) !== false) {
								echo "Websockets configuration updated.\n";
							}
						}
						$i++;
					}
				}
			}
		}

		// upgrade application defaults
		$domain = new domains;
		$domain->display_type = $display_type;
		$domain->upgrade();
	}

//restore the default menu
	if ($upgrade_type == 'menu' or $upgrade_type == '-m' or $upgrade_type == '--menu') {
		//get the menu uuid and language
		$sql = "select menu_uuid, menu_name, menu_language ";
		$sql .= "from v_menus ";
		$menus = $database->select($sql, null, 'all');
		foreach ($menus as $row) {
			if ($row == 'default') {
				$menu_uuid = $row["menu_uuid"];
				$menu_language = $row["menu_language"];
			}
		}
		unset($sql, $row);

		//show the menu
		if (isset($argv[2]) && $argv[2] == 'list') {
			echo "Menu List\n";
			foreach ($menus as $row) {
				if (!empty($row) && sizeof($row) != 0) {
					echo "  ".$row["menu_name"]."\n";
				}
			}
			echo "\n";
		}

		//set the menu back to default
		if (empty($argv[2]) || $argv[2] == 'default') {
			//send a message to the console
			echo "[ Update ] Restore the default menu\n";

			//restore the menu
			$included = true;
			require_once("core/menu/menu_restore_default.php");
			echo "\n";
		}
	}

//restore the default permissions
	if ($upgrade_type == 'permissions' or $upgrade_type == '-p' or $upgrade_type == '--permissions') {

		if (empty($argv[2]) || $argv[2] == 'default') {
			//send a message to the console
			echo "[ Update ] Restore the file permissions\n";

			//restore default file permissions.
			update_file_permissions($text, $settings);
		}

		if (empty($argv[2]) || $argv[2] == 'default') {
			//send a message to the console
			echo "[ Update ] Restore the group permissions\n";

			//default the groups in case they are missing
			$groups = new groups;
			$groups->defaults();
		}

		//default the permissions
		$included = true;
		require_once("core/groups/permissions_default.php");

		//add a line feed
		echo "\n";

	}

//restore the file permissions
	if ($upgrade_type == 'file' or $upgrade_type == '-f' or $upgrade_type == '--file') {
		//send a message to the console
		echo "[ Update ] Restore the file permissions\n";

		//restore default file permissions.
		update_file_permissions($text, $settings);

		//add a line feed
		echo "\n";
	}

//restore the group permissions
	if ($upgrade_type == 'group' or $upgrade_type == '-g' or $upgrade_type == '--group') {
		//send a message to the console
		echo "[ Update ] Restore the group permissions\n";

		//default the groups in case they are missing
		$groups = new groups;
		$groups->defaults();

		//default the permissions
		$included = true;
		require_once("core/groups/permissions_default.php");

		//add a line feed
		echo "\n";
	}

//default upgrade schema and app defaults
	if (empty($upgrade_type)) {

		//set the display type
			if (defined('STDIN')) {
				$display_type = 'text';
			}
			else {
				$display_type = 'html';
			}

		//send a message to the console
			if ($display_type === 'text') {
				echo "[ Update ] Table and field structure.\n";
			}

		//Update the table and field structure.
			$obj = new schema;
			$response = $obj->schema("text");
			if ($display_type === 'text') {
				foreach(explode("\n", $response) as $row) {
					echo "        ".trim($row)."\n";
				}
			}

		//send a message to the console
			if ($display_type === 'text') {
				echo "[ Update ] Application defaults.\n";
			}

		//run all app_defaults.php files
			$domain = new domains;
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

//update main software source
	if ($upgrade_type == 'services' or $upgrade_type == '-u' or $upgrade_type == '--service' or $upgrade_type == '--services') {

		if (empty($argv[2]) || $argv[2] == 'update') {
			//send a message to the console
			echo "[ Update ] Update default services\n";

			//add or update all the services
			upgrade_services($text, $settings);
		}

		//send a message to the console
		if (empty($argv[2]) || $argv[2] == 'stop') {
			echo "[ Update ] Stop services\n";

			//stop all the services
			stop_services($text, $settings);
		}

		//send a message to the console
		if (empty($argv[2]) || $argv[2] == 'restart') {
			echo "[ Update ] Restart services\n";

			//restart all the services
			restart_services($text, $settings);
		}

	}

//check for the upgrade menu option first
	if ($upgrade_type == 'interactive' or $upgrade_type == '-i' or $upgrade_type == '--interactive') {
		require __DIR__ . '/upgrade_menu.php';
		exit();
	}

//update main software source
	if ($upgrade_type == 'main' or $upgrade_type == '-n'  or $upgrade_type == '--main') {

		//send a message to the console
		echo "[ Update ] The main application\n";

		$git_result = git_pull(dirname(__DIR__, 2));
		foreach ($git_result['message'] as $response_line) {
			echo $repo . ": " . $response_line . "\n";
		}

	}

//update optional applications
	if ($upgrade_type == 'optional' or $upgrade_type == '-o'  or $upgrade_type == '--optional') {

		//set the $text array as global
		global $text;

		//get the list of updateable repos
		$updateable_repos = git_find_repos(dirname(__DIR__, 2)."/app");

		//send a message to the console
		echo "[Update ] The optional applications\n";

		//update the optional repos
		$messages = [];
		foreach ($updateable_repos as $repo => $row) {

			//set the application name
			$application = $row[0];

			// Set the project root
			$project_root = dirname(__DIR__, 1);

			//show the response
			echo " $application\n";

			//pull the changes using git
			$git_result = git_pull($repo);

			//set the response message array
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
 * Update file system permissions
 */
function update_file_permissions($text, settings $settings) {

	if (is_root()) {
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

			//execute
			exec("chown -R www-data:www-data $dir");
		}
	} else {
		echo ($text['label-not_running_as_root'] ?? "Not root user - operation skipped")."\n";
	}
}

/**
 * Upgrade services
 */
function upgrade_services($text, settings $settings) {
	//echo ($text['description-upgrade_services'] ?? "")."\n";
	$core_files = glob(dirname(__DIR__, 2) . "/core/*/resources/service/*.service");
	$app_files = glob(dirname(__DIR__, 2) . "/app/*/resources/service/*.service");
	$service_files = array_merge($core_files, $app_files);
	foreach($service_files as $file) {
		$service_name = find_service_name($file);
		echo "	Name: ".$service_name."\n";
		system("cp " . escapeshellarg($file) . " /etc/systemd/system/" . escapeshellarg($service_name) . ".service");
		system("systemctl daemon-reload");
		system("systemctl enable --now " . escapeshellarg($service_name));
	}
}

/**
 * Stop services
 */
function stop_services($text, settings $settings) {
	//echo ($text['description-stop_services'] ?? "")."\n";
	$core_files = glob(dirname(__DIR__, 2) . "/core/*/resources/service/*.service");
	$app_files = glob(dirname(__DIR__, 2) . "/app/*/resources/service/*.service");
	$service_files = array_merge($core_files, $app_files);
	foreach($service_files as $file) {
		$service_name = find_service_name($file);
		echo "	Name: ".$service_name."\n";
		system("systemctl stop ".$service_name);
	}
}

/**
 * Restart services
 */
function restart_services($text, settings $settings) {
	//echo ($text['description-restart_services'] ?? "")."\n";
	$core_files = glob(dirname(__DIR__, 2) . "/core/*/resources/service/*.service");
	$app_files = glob(dirname(__DIR__, 2) . "/app/*/resources/service/*.service");
	$service_files = array_merge($core_files, $app_files);
	foreach($service_files as $file) {
		$service_name = find_service_name($file);
		echo "	Name: ".$service_name."\n";
		system("systemctl restart ".$service_name);
	}
}

/**
 * Get the service name
 * @param string $file
 */
function find_service_name(string $file) {
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
function is_root(): bool {
	return posix_getuid() === 0;
}

