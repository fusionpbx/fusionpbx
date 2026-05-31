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

// Include file
	require dirname(__DIR__, 2) . "/resources/require.php";

// If the config file doesn't exist and the config.php does exist use it to write a new config file
	if (isset($config_exists) && !$config_exists && file_exists("/etc/fusionpbx/config.php")) {
		// Include the config.php
		include("/etc/fusionpbx/config.php");

		// Set the default config file location
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
			$document_root = dirname(__DIR__, 2);

			$conf_dir = $_SERVER['ProgramFiles'].DIRECTORY_SEPARATOR.'freeswitch'.DIRECTORY_SEPARATOR.'conf';
			$sounds_dir = $_SERVER['ProgramFiles'].DIRECTORY_SEPARATOR.'freeswitch'.DIRECTORY_SEPARATOR.'sounds';
			$database_dir = $_SERVER['ProgramFiles'].DIRECTORY_SEPARATOR.'freeswitch'.DIRECTORY_SEPARATOR.'db';
			$recordings_dir = $_SERVER['ProgramFiles'].DIRECTORY_SEPARATOR.'freeswitch'.DIRECTORY_SEPARATOR.'recordings';
			$storage_dir = $_SERVER['ProgramFiles'].DIRECTORY_SEPARATOR.'freeswitch'.DIRECTORY_SEPARATOR.'storage';
			$voicemail_dir = $_SERVER['ProgramFiles'].DIRECTORY_SEPARATOR.'freeswitch'.DIRECTORY_SEPARATOR.'voicemail';
			$scripts_dir = $_SERVER['ProgramFiles'].DIRECTORY_SEPARATOR.'freeswitch'.DIRECTORY_SEPARATOR.'scripts';
			$php_dir = dirname(PHP_BINARY);
			$cache_location = dirname(dirname(__DIR__, 2)).DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'fusionpbx';
		}

		// Make the config directory
		if (isset($config_path)) {
			system('mkdir -p '.$config_path);
		}
		else {
			echo "Config directory not found\n";
			exit;
		}

		// Build the config file
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
		$conf .= "#session settings\n";
		$conf .= "session.cookie_httponly = true\n";
		$conf .= "session.cookie_secure = true\n";
		$conf .= "session.cookie_samesite = Lax\n";
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

		// Write the config file
		$file_handle = fopen($config_file,"w");
		if (!$file_handle){ return; }
		fwrite($file_handle, $conf);
		fclose($file_handle);
	}

// Check the permission
	if (defined('STDIN')) {
		$display_type = 'text'; //html, text
	}
	else {
		require_once "resources/check_auth.php";
		if (!(permission_exists('upgrade_schema') || permission_exists('upgrade_source'))) {
			echo "access denied";
			exit;
		}
		$display_type = 'html'; //html, text
	}

// Set the default as an empty string
	$upgrade_type = '';

// Get the command line arguments
	if (defined('STDIN')) {
		if (!empty($argv[1])) {
			$upgrade_type = $argv[1];
		}
	}

// Initiliaze the schema object
	$schema = new schema(['database' => $database]);

// Use upgrade language file
	$language = new text;
	$text = $language->get(null, 'core/upgrade');

// Always update the now global autoload cache just-in-case the source files have updated
	$autoload->update();

// Trigger clear cache for any classes that require it
	foreach ($autoload->get_interface_list('clear_cache') as $class) {
		$class::clear_cache();
	}

// Show the title
	if ($display_type == 'text') {
		//echo "\n";
		//echo $text['label-upgrade']."\n";
		echo "\n";
	}

// Show the help menu
	if ($upgrade_type == 'help' or $upgrade_type == '-h' or $upgrade_type == '--help') {

		// Send the help message to the console
		echo "Usage:\n";
		echo "  upgrade.php [-hvnostdmpgfui] [--menu [default|list]] [--services [update|restart]]\n";
		echo "  A terminal-based upgrade tool used to simplify or automate the upgrade.\n";
		echo "\n";
		echo "Options:\n";
		echo "  -h --help                              Show this help message.\n";
		echo "  -v --version                           Show the version.\n";
		echo "  -n --main                              Update the main application.\n";
		echo "  -o --optional                          Update the optional applications.\n";
		echo "  -s --schema                            Update the database tables, columns and data types.\n";
		echo "  -d --defaults                          Restore application defaults.\n";
		echo "  -m --menu [default|list]               Restore menu default or show the menu list\n";
		echo "  -p --permissions                       Restore default file and group permissions.\n";
		echo "  -g --group                             Restore default group permissions.\n";
		echo "  -f --file                              Update the file permissions.\n";
		echo "  -u --service [update|stop|restart]     Update and restart services.\n";
		echo "  -i --interactive                       Show the interactive menu.\n";
		echo "\n";

	}

// Get the version of the software
	if ($upgrade_type == 'version' or $upgrade_type == '-v' or $upgrade_type == '--version') {
		echo software::version()."\n";
	}

// Upgrade the schema and data_types
	if ($upgrade_type == 'schema' or $upgrade_type == '-s' or $upgrade_type == '--schema') {
		// Send a message to the console
		if ($display_type === 'text') {
			echo "[ Update ] Table and field structure.\n";
		}

		// Get the database schema put it into an array then compare and update the database as needed.
		$response = $schema->upgrade($format ?? '');
		if ($display_type === 'text') {
			foreach(explode("\n", $response) as $row) {
				echo trim($row)."\n";
			}
		}
	}

// Run all application defaults - add missing defaults
	if ($upgrade_type == 'defaults' or $upgrade_type == '-d' or $upgrade_type == '--defaults') {
		// Send a message to the console
		if ($display_type === 'text') {
			echo "[ Update ] Restore application defaults.\n";
		}

		// Run for command line only
		if (defined('STDIN')) {
			$nginx_path = '/etc/nginx/sites-enabled/fusionpbx';
			if (file_exists($nginx_path)) {
				// Get the nginx configuration
				$nginx_config = file_get_contents($nginx_path);

				// Define the location block to add if it doesn't exist
				$websocket_settings = "        #redirect websockets to port 8080\n";
				$websocket_settings .= "                location /websockets/ {\n";
				$websocket_settings .= "                proxy_pass http://127.0.0.1:8080;\n";
				$websocket_settings .= "                proxy_http_version 1.1;\n";
				$websocket_settings .= "                proxy_set_header Upgrade \$http_upgrade;\n";
				$websocket_settings .= "                proxy_set_header Connection \"upgrade\";\n";
				$websocket_settings .= "                proxy_set_header Host \$host;\n";
				$websocket_settings .= "        }\n";
				$websocket_settings .= "\n";

				// Search array
				$search_array['0'] = 'listen 443 ssl;';
				$search_array['1'] = '#redirect letsencrypt to dehydrated';

				// Add the websocket settings if it is not in the config
				if (strpos($nginx_config, '/websockets/') === false) {

					// Find the position where websockets string should be added.
					$ssl_found = false;
					$character_count = 0;
					$i = 1;
					foreach(explode("\n", $nginx_config) as $line) {
						// Count each line and add an additional character for the line feed
						$character_count += strlen($line) + 1;

						// Find the section for ssl on port 443
						if (trim($line) == $search_array[0]) {
							$ssl_found = true;
						}

						// Find the second search string inside the ssl section
						if ($ssl_found && trim($line) == $search_array[1]) {
							// Use substr_replace to add the string at the correct position
							$new_config = substr_replace($nginx_config, $websocket_settings, $character_count - strlen($line."\n"), 0);

							// Write the updated configuration back to the file
							if (file_put_contents($nginx_path, $new_config) !== false) {
								echo "Websockets configuration updated.\n";
							}
						}
						$i++;
					}
				}
			}
		}

		// Update php fpm service
		update_php_fpm($settings);

		// Upgrade application defaults
		$domain = new domains;
		$domain->upgrade();
	}

// Restore the default menu
	if ($upgrade_type == 'menu' or $upgrade_type == '-m' or $upgrade_type == '--menu') {
		// Get the menu_uuid and language
		$sql = "select menu_uuid, menu_name, menu_language ";
		$sql .= "from v_menus ";
		$menus = $database->select($sql, null, 'all');
		foreach ($menus as $row) {
			if ($row['menu_name'] == 'default') {
				$menu_uuid = $row["menu_uuid"];
				$menu_language = $row["menu_language"];
				break;
			}
		}
		unset($sql, $row);

		// Show the menu
		if (isset($argv[2]) && $argv[2] == 'list') {
			echo "Menu List\n";
			foreach ($menus as $row) {
				if (!empty($row) && sizeof($row) != 0) {
					echo $row["menu_name"]."\n";
				}
			}
			echo "\n";
		}

		// Set the menu back to default
		if (empty($argv[2]) || $argv[2] == 'default') {
			// Send a message to the console
			echo "[ Update ] Restore the default menu\n";

			// Restore the menu
			$included = true;
			require_once("core/menu/menu_restore_default.php");
			echo "\n";
		}
	}

// Restore the default permissions
	if ($upgrade_type == 'permissions' or $upgrade_type == '-p' or $upgrade_type == '--permissions') {

		if (empty($argv[2]) || $argv[2] == 'default') {
			// Send a message to the console
			echo "[ Update ] Restore the file permissions\n";

			// Restore default file permissions.
			update_file_permissions($text, $settings);
		}

		if (empty($argv[2]) || $argv[2] == 'default') {
			// Send a message to the console
			echo "[ Update ] Restore the group permissions\n";

			// Default the groups in case they are missing
			$groups = new groups;
			$groups->defaults();
		}

		// Default the permissions
		$included = true;
		require_once("core/groups/permissions_default.php");

		// Add a line feed
		echo "\n";

	}

// Restore the file permissions
	if ($upgrade_type == 'file' or $upgrade_type == '-f' or $upgrade_type == '--file') {
		// Send a message to the console
		echo "[ Update ] Restore the file permissions\n";

		// Restore default file permissions.
		update_file_permissions($text, $settings);

		// Add a line feed
		echo "\n";
	}

// Restore the group permissions
	if ($upgrade_type == 'group' or $upgrade_type == '-g' or $upgrade_type == '--group') {
		// Send a message to the console
		echo "[ Update ] Restore the group permissions\n";

		// Default the groups in case they are missing
		$groups = new groups;
		$groups->defaults();

		// Default the permissions
		$included = true;
		require_once("core/groups/permissions_default.php");

		// Add a line feed
		echo "\n";
	}

// Default upgrade schema and app defaults
	if (empty($upgrade_type)) {

		// Set the display type
			if (defined('STDIN')) {
				$display_type = 'text';
			}
			else {
				$display_type = 'html';
			}

		// Send a message to the console
			if ($display_type === 'text') {
				echo "[ Update ] Table and field structure.\n";
			}

		// Update the table and field structure.
			$response = $schema->upgrade("text");
			if ($display_type === 'text') {
				foreach(explode("\n", $response) as $row) {
					echo trim($row)."\n";
				}
			}

		// Send a message to the console
			if ($display_type === 'text') {
				echo "[ Update ] Application defaults.\n";
			}

		// Update php fpm service
			update_php_fpm($settings);

		// Run all app_defaults.php files
			$domain = new domains;
			$domain->upgrade();

		// Show the content
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

		// Include the footer
			if ($display_type == "html") {
				require_once "resources/footer.php";
			}
	}

// Update main software source
	if ($upgrade_type == 'services' or $upgrade_type == '-u' or $upgrade_type == '--service' or $upgrade_type == '--services') {

		// Add missing services
		$service = new services();
		$service->add_missing();

		if (empty($argv[2]) || $argv[2] == 'update') {
			// Send a message to the console
			echo "[ Update ] Update default services\n";
			//echo ($text['description-upgrade_services'] ?? "")."\n";

			// Add or update all the services
			$object = new services();
			$object->upgrade('all');
		}

		// Send a message to the console
		if (empty($argv[2]) || $argv[2] == 'stop') {
			echo "[ Update ] Stop services\n";
			//echo ($text['description-stop_services'] ?? "")."\n";

			// Stop all the services
			$object = new services();
			$object->stop('all');
		}

		// Send a message to the console
		if (empty($argv[2]) || $argv[2] == 'restart') {
			echo "[ Update ] Restart services\n";
			//echo ($text['description-restart_services'] ?? "")."\n";

			// Restart all the services
			$object = new services();
			$object->restart('all');
		}

	}

// Check for the upgrade menu option first
	if ($upgrade_type == 'interactive' or $upgrade_type == '-i' or $upgrade_type == '--interactive') {
		require __DIR__ . '/upgrade_menu.php';
		exit();
	}

// Update main software source
	if ($upgrade_type == 'main' or $upgrade_type == '-n'  or $upgrade_type == '--main') {

		// Send a message to the console
		echo "[ Update ] The main application\n";

		$git_result = git_pull(dirname(__DIR__, 2));
		foreach ($git_result['message'] as $response_line) {
			echo $repo . ": " . $response_line . "\n";
		}

	}

// Update optional applications
	if ($upgrade_type == 'optional' or $upgrade_type == '-o'  or $upgrade_type == '--optional') {

		// Set the $text array as global
		global $text;

		// Get the list of updateable repos
		$updateable_repos = git_find_repos(dirname(__DIR__, 2)."/app");

		// Send a message to the console
		echo "[Update ] The optional applications\n";

		// Update the optional repos
		$messages = [];
		foreach ($updateable_repos as $repo => $row) {

			// Set the application name
			$application = $row[0];

			// Set the project root
			$project_root = dirname(__DIR__, 1);

			// Show the response
			echo "$application\n";

			// Pull the changes using git
			$git_result = git_pull($repo);

			// Set the response message array
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
 * Update PHP FPM service a ReadWritePaths
 *
 * This function updates PHP FPM servie add ReadWritePaths to allow writing to specific directories
 *
 * @param settings $settings The current application settings instance.
 */
function update_php_fpm(settings $settings) {

	// Update the php-fpm.service file
	if (PHP_OS === 'Linux') {
		// Get the PHP version
		$php_version =  PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;

		// Make sure the /usr/share/fusionpbx directory exists
		if (!file_exists('/usr/share/fusionpbx')) {
			mkdir('/usr/share/fusionpbx', 0755, true);
		}

		// Build the read write paths array
		$read_write_paths[] = '/etc/freeswitch';
		$read_write_paths[] = '/usr/share/freeswitch';
		$read_write_paths[] = '/var/lib/freeswitch';
		$read_write_paths[] = $settings->get('cache', 'location', '/var/cache/fusionpbx');
		$read_write_paths[] = '/usr/share/fusionpbx';

		// Build the line to add to the service file
		$line_to_insert = "ReadWritePaths=/tmp";
		foreach($read_write_paths as $read_write_path) {
			if (file_exists($read_write_path)) {
				$line_to_insert .= ' '.$read_write_path;
			}
		}
		$service_file = "/usr/lib/systemd/system/php".$php_version."-fpm.service";
		if (file_exists($service_file)) {
			// Get the file contents
			$file_contents = file_get_contents($service_file);

			// Check if "ReadWritePaths" exists in the file contents
			$file_updated = strpos($file_contents, 'ReadWritePaths') !== false;

			// Check if the service file is writable
			$file_writable = is_writable($service_file);

			// Find the empty line befor the Install
			if (!$file_updated && $file_writable) {
				$lines = explode("\n", $file_contents);
				$insert_index = -1;
				for ($i = 0; $i < count($lines); $i++) {
					if (trim($lines[$i]) === '[Install]') {
						// Find empty line before [Install]
						for ($j = $i - 1; $j >= 0; $j--) {
							if (trim($lines[$j]) === '') {
								$insert_index = $j;
								break;
							}
						}
						break;
					}
				}

				// Add the new content
				array_splice($lines, $insert_index, 0, $line_to_insert);
				$new_contents = implode("\n", $lines) . "\n";
				file_put_contents($service_file, $new_contents);

				// Prepared systemd to use the update
				system('systemctl daemon-reload');
				system('systemctl restart php'.$php_version.'-fpm');
			}
		}
	}
}

/**
 * Update file permissions for a FusionPBX installation.
 *
 * This function updates the permissions of various directories in a FusionPBX installation,
 * specifically adjusting ownership to match the expected behavior. The changes are only applied
 * when running as root, and an error message is displayed otherwise.
 *
 * @param array    $text     A translation dictionary containing the label for when not running as root.
 * @param settings $settings The current application settings instance.
 */
function update_file_permissions($text, settings $settings) {

	if (is_root()) {
		// Initialize the array
		$directories = [];

		// Get the fusionpbx folder
		$project_root = dirname(__DIR__, 2);

		// Adjust the project root
		$directories[] = $project_root;

		// Adjust the /etc/freeswitch
		$directories[] = $settings->get('switch', 'conf', null);
		$directories[] = $settings->get('switch', 'call_center', null); //normally in conf but can be different
		$directories[] = $settings->get('switch', 'dialplan', null); //normally in conf but can be different
		$directories[] = $settings->get('switch', 'directory', null); //normally in conf but can be different
		$directories[] = $settings->get('switch', 'languages', null); //normally in conf but can be different
		$directories[] = $settings->get('switch', 'sip_profiles', null); //normally in conf but can be different

		// Adjust the /usr/share/freeswitch/{scripts,sounds}
		$directories[] = $settings->get('switch', 'scripts', null);
		$directories[] = $settings->get('switch', 'sounds', null);

		// Adjust the /var/lib/freeswitch/{db,recordings,storage,voicemail}
		$directories[] = $settings->get('switch', 'db', null);
		$directories[] = $settings->get('switch', 'recordings', null);
		$directories[] = $settings->get('switch', 'storage', null);
		$directories[] = $settings->get('switch', 'voicemail', null); //normally included in storage but can be different

		// Only set the xml_cdr directory permissions
		$log_directory = $settings->get('switch', 'log', null);
		if ($log_directory !== null) {
			$directories[] = $log_directory . '/xml_cdr';
		}

		// Run chown command for each directory
		foreach ($directories as $dir) {
			// Skip empty directories
			if (empty($dir)) { continue; }

			// Skip /dev/shm directory
			if (strpos($dir, '/dev/shm') !== false) {
				continue;
			}

			// Update the file ownership to use the web server user
			exec("chown -R www-data:www-data $dir");
		}
	} else {
		echo ($text['label-not_running_as_root'] ?? "Not root user - operation skipped")."\n";
	}
}

/**
 * Upgrade services by copying and enabling them in systemd.
 *
 * This function iterates through all service files found in the application's
 * core and app directories, copies each one to /etc/systemd/system, reloads
 * the daemon, and enables the service.
 *
 * @param string   $text     Text containing the upgrade description (not used)
 * @param settings $settings Application settings
 */
function upgrade_services($text, settings $settings) {
	// echo ($text['description-upgrade_services'] ?? "")."\n";

	// Determine the search file name
	if (stristr(PHP_OS, 'Linux')) {
		$search_file_name = 'debian';
	}
	if (stristr(PHP_OS, 'FreeBSD')) {
		$search_file_name = 'freebsd';
	}

	// Get the list of services
	$core_files = glob(dirname(__DIR__, 4) . "/core/*/resources/service/".$search_file_name.".service");
	$app_files = glob(dirname(__DIR__, 4) . "/app/*/resources/service/".$search_file_name.".service");
	$service_files = array_merge($core_files, $app_files);

	if (!empty($service_files)) {
		foreach($service_files as $file) {
			// Set a variable for the service name
			$service_name = find_service_name($file);
			// Sanitize the service name
			$service_name = preg_replace('/[^a-zA-Z0-9_]/', '', $service_name);
			// Send the service name to the console
			if (stristr(PHP_OS, 'Linux')) {
				echo "Name: ".$service_name."\n";
			}
			// Install and start the service
			if (stristr(PHP_OS, 'Linux')) {
				system("cp " . escapeshellarg($file) . " /etc/systemd/system/" . escapeshellarg($service_name) . ".service");
				system("systemctl daemon-reload");
				system("systemctl enable " . escapeshellarg($service_name));
				system("systemctl start " . escapeshellarg($service_name));
			}
			if (stristr(PHP_OS, 'FreeBSD')) {
				system("cp " . $file . " /usr/local/etc/rc.d/".$service_name);
				system("sysrc " . $service_name . "_enable=\"YES\"");
				system("chmod 755 /usr/local/etc/rc.d/" . $service_name);
				system("service " . $service_name . " start");
			}
		}
	}
}

/**
 * Stops running services by name.
 *
 * This function iterates over all service files, extracts the service names,
 * and stops each service using systemctl.
 *
 * @param array    $text
 * @param settings $settings
 */
function stop_services($text, settings $settings) {
	// echo ($text['description-stop_services'] ?? "")."\n";

	// Determine the search file name
	if (stristr(PHP_OS, 'Linux')) {
		$search_file_name = 'debian';
	}
	if (stristr(PHP_OS, 'FreeBSD')) {
		$search_file_name = 'freebsd';
	}

	// Get the list of services
	$core_files = glob(dirname(__DIR__, 4) . "/core/*/resources/service/".$search_file_name.".service");
	$app_files = glob(dirname(__DIR__, 4) . "/app/*/resources/service/".$search_file_name.".service");
	$service_files = array_merge($core_files, $app_files);

	// Stop each of the services
	if (!empty($service_files)) {
		foreach($service_files as $file) {
			// Set a variable for the service name
		 	$service_name = find_service_name($file);
		 	// Sanitize the service name
		 	$service_name = preg_replace('/[^a-zA-Z0-9_]/', '', $service_name);
		 	// Send the service name to the console
			if (stristr(PHP_OS, 'Linux')) {
		 		echo "Name: " . $service_name . "\n";
			}
		 	// Stop the service
			if (stristr(PHP_OS, 'Linux')) {
		 		system("systemctl stop " . $service_name);
			}
			if (stristr(PHP_OS, 'FreeBSD')) {
				system("service " . $service_name . " stop");
			}
		}
	}
}

/**
 * Restarts all services
 *
 * This function restarts all core and app services.
 *
 * @param array    $text     Array containing localized text
 * @param settings $settings Settings object
 */
function restart_services($text, settings $settings) {
	// echo ($text['description-restart_services'] ?? "")."\n";

	// Determine the search file name
	if (stristr(PHP_OS, 'Linux')) {
		$search_file_name = 'debian';
	}
	if (stristr(PHP_OS, 'FreeBSD')) {
		$search_file_name = 'freebsd';
	}

	// Get the list of services
	$core_files = glob(dirname(__DIR__, 2) . "/core/*/resources/service/".$search_file_name.".service");
	$app_files = glob(dirname(__DIR__, 2) . "/app/*/resources/service/".$search_file_name.".service");
	$service_files = array_merge($core_files, $app_files);

	// Restart each of the services
	if (!empty($service_files)) {
		foreach($service_files as $file) {
			// Set a variable for the service name
			$service_name = find_service_name($file);
		 	// Sanitize the service name
		 	$service_name = preg_replace('/[^a-zA-Z0-9_]/', '', $service_name);
		 	// Send the service name to the console
			if (stristr(PHP_OS, 'Linux')) {
				echo "Name: ".$service_name."\n";
			}
			// Restart the service
			if (stristr(PHP_OS, 'Linux')) {
				system("systemctl restart ".$service_name);
			}
			if (stristr(PHP_OS, 'FreeBSD')) {
				system("service " . $service_name . " restart");
			}
		}
	}
}

/**
 * Finds the service name in an INI file from a given file.
 *
 * @param string $file The fully qualified path and file containing the ExecStart command.
 *
 * @return string|null The service name if found, otherwise an empty string.
 */
function find_service_name(string $file) {
	if (stristr(PHP_OS, 'Linux')) {
		$parsed = parse_ini_file($file);
		$exec_cmd = $parsed['ExecStart'];
		$parts = explode(' ', $exec_cmd);
		$php_file = $parts[1] ?? '';
		if (!empty($php_file)) {
			$path_info = pathinfo($php_file);
			return $path_info['filename'];
		}
	}
	if (stristr(PHP_OS, 'FreeBSD')) {
		$service_content = file_get_contents($file);
		if (preg_match('/^\s*name\s*=\s*["\']([^"\']+)["\']\s*$/m', $service_content, $name_matches)) {
			if (!empty($name_matches[1])) {
				return $name_matches[1];
			}
		}
	}
	return '';
}

/**
 * Checks whether the current user is the root user or not.
 *
 * @return bool True if the current user has root privileges, false otherwise.
 */
function is_root(): bool {
	return posix_getuid() === 0;
}
