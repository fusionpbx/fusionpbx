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
	Portions created by the Initial Developer are Copyright (C) 2008-2026
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

/**
 * switch class provides methods for copying switch_files
 */
class switch_files {

	private $config;

	/**
	 * Initializes the object with setting array.
	 *
	 * @param array $setting_array An array containing settings for domain, user, and database connections. Defaults to
	 *                             an empty array.
	 *
	 * @return void
	 */
	public function __construct(array $setting_array = []) {
		//set objects
		$this->config = $setting_array['config'] ?? config::load();
	}

	/**
	 * Converts the given path to a platform-agnostic format.
	 *
	 * @param string $path The path to be converted.
	 *
	 * @return string The converted path. If running on Windows, backslashes are replaced with forward slashes.
	 */
	private function correct_path($path) {
		global $IS_WINDOWS;
		if ($IS_WINDOWS == null) {
			if (stristr(PHP_OS, 'WIN')) {
				$IS_WINDOWS = true;
			} else {
				$IS_WINDOWS = false;
			}
		}
		if ($IS_WINDOWS) {
			return str_replace('\\', '/', $path);
		}
		return $path;
	}

	/**
	 * Copy the switch scripts to the switch directory
	 *
	 * The function attempts to find the source and destination directories by checking various system locations. If
	 * neither is found, it throws an exception.
	 *
	 * @return void
	 */
	public function copy_scripts() {

		// Initialize the message variable to hold any error messages
		$message = '';
	
		// Get the source directory
		if (file_exists('/usr/share/examples/fusionpbx/scripts')) {
			$source_directory = '/usr/share/examples/fusionpbx/scripts';
		} elseif (file_exists('/usr/local/www/fusionpbx/app/switch/resources/scripts')) {
			$source_directory = '/usr/local/www/fusionpbx/app/switch/resources/scripts';
		} elseif (file_exists('/var/www/fusionpbx/app/switch/resources/scripts')) {
			$source_directory = '/var/www/fusionpbx/app/switch/resources/scripts';
		} else {
			$source_directory = dirname(__DIR__, 4) . '/app/switch/resources/scripts';
		}

		// Get the destination directory
		if (file_exists($this->config->get('switch.scripts.dir'))) {
			$destination_directory = $this->config->get('switch.scripts.dir');
		} elseif (file_exists('/usr/share/freeswitch/scripts/')) {
			$destination_directory = '/usr/share/freeswitch/scripts';
		} elseif (file_exists('/usr/local/freeswitch/scripts')) {
			$destination_directory = '/usr/local/freeswitch/scripts';
		}

		// Check if the source and destination directories were found
		if (empty($source_directory)) {
			throw new Exception("Source directory for switch scripts not found.");	
		}
		if (empty($destination_directory)) {
			throw new Exception("Destination directory for switch scripts not found.");
		}

		// Copy the scripts directory
		if (!empty($source_directory) && !empty($destination_directory) 
			&& is_readable($source_directory) && is_writable($destination_directory)) {
			// Copy the main scripts
			recursive_copy($source_directory, $destination_directory);
			unset($source_directory);

			// Copy the app/*/resources/install/scripts
			$app_scripts = glob(dirname(__DIR__, 4) . 'app/*/resources/scripts');
			foreach ($app_scripts as $app_script) {
				recursive_copy($app_script, $destination_directory);
			}
			unset($app_scripts);
		} else {
			// Determine the specific reason for the failure and set the message
			if (!is_readable($source_directory)) {
				$message = "Cannot read from '$source_directory' to get the scripts";
			} elseif (!is_writable($destination_directory)) {
				$message = "Cannot write to '$destination_directory' to copy the scripts";
			} else {
				$message = "Unknown error occurred while copying scripts from '$source_directory' to '$destination_directory'";
			}

			// If the message variable is set then throw an exception or send a message to the browser
			if (is_cli()) {
				// Throw an exception with the message if run from the command line
				throw new Exception($message);
			}
			else {
				// Send a message to browser if not run from the command line
				message::add('Error: ' . $message, 'negative');
			}
		}

		// Set the permissions of the destination directory to 0775
		chmod($destination_directory, 0775);

	}

	/**
	 * Copy the switch languages to the switch directory
	 *
	 * @return void
	 */
	public function copy_languages() {

		// Get the source directory
		if (file_exists('/usr/share/examples/freeswitch/conf/languages')) {
			$source_directory = '/usr/share/examples/fusionpbx/conf/languages';
		} elseif (file_exists('/usr/local/www/fusionpbx/app/switch/resources/conf/languages')) {
			$source_directory = '/usr/local/www/fusionpbx/app/switch/resources/conf/languages';
		} elseif (file_exists('/var/www/fusionpbx/app/switch/resources/conf/languages')) {
			$source_directory = '/var/www/fusionpbx/app/switch/resources/conf/languages';
		} else {
			$source_directory = dirname(__DIR__, 4) . '/app/switch/resources/conf/languages';
		}

		// Get the destination directory
		if (file_exists($this->config->get('switch.conf.dir') . '/languages')) {
			$destination_directory = $this->config->get('switch.conf.dir') . '/languages';
		} elseif (file_exists('/etc/freeswitch/languages')) {
			$destination_directory = '/usr/local/share/freeswitch/languages';
		} elseif (file_exists('/usr/local/freeswitch/conf/languages')) {
			$destination_directory = '/usr/local/freeswitch/conf/languages';
		}

		// Copy the languages directory
		if (!empty($source_directory) && is_readable($source_directory)) {
			// Copy the main languages
			recursive_copy($source_directory, $destination_directory);
			unset($source_directory);
		} else {
			throw new Exception("Cannot read from '$source_directory' to get the scripts");
		}

		// Set the permissions of the destination directory to 0775
		chmod($destination_directory, 0775);

	}

}

/*
//example use

//update config.lua
	$obj = new app_switch;
	$obj->copy_scripts();
	$obj->copy_languages();
*/
