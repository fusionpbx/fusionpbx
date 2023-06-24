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
*/

/**
 * switch class provides methods for copying scripts
 *
 * @method string correct_path
 * @method string copy_files
 * @method string write_config
 */
if (!class_exists('scripts')) {
	class scripts {

		/**
		 * Called when the object is created
		 */
		public function __construct() {

		}

		/**
		 * Corrects the path for specifically for windows
		 */
		private function correct_path($path) {
			global $IS_WINDOWS;
			if ($IS_WINDOWS == null) {
				if (stristr(PHP_OS, 'WIN')) { $IS_WINDOWS = true; } else { $IS_WINDOWS = false; }
			}
			if ($IS_WINDOWS) {
				return str_replace('\\', '/', $path);
			}
			return $path;
		}

		/**
		 * Copy the switch scripts to the switch directory
		 */
		public function copy_files() {

			//include files
			require dirname(__DIR__, 4) . "/resources/require.php";

			//get the source directory
			if (file_exists('/usr/share/examples/fusionpbx/scripts')) {
				$source_directory = '/usr/share/examples/fusionpbx/scripts';
			}
			elseif (file_exists('/usr/local/www/fusionpbx/app/switch/resources/scripts')) {
				$source_directory = '/usr/local/www/fusionpbx/app/switch/resources/scripts';
			}
			elseif (file_exists('/var/www/fusionpbx/app/switch/resources/scripts')) {
				$source_directory = '/var/www/fusionpbx/app/switch/resources/scripts';
			}
			else {
				$source_directory = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/switch/resources/scripts';
			}

			//get the destination directory
			if (file_exists($conf['switch.scripts.dir'])) {
				$destination_directory = $conf['switch.scripts.dir'];
			}
			elseif (file_exists('/usr/share/examples/fusionpbx/scripts')) {
				$destination_directory = '/usr/share/examples/fusionpbx/scripts';
			}
			elseif (file_exists('/usr/local/share/freeswitch/scripts')) {
				$destination_directory = '/usr/local/share/freeswitch/scripts';
			}
			elseif (file_exists('/usr/local/freeswitch/scripts')) {
				$destination_directory = '/usr/local/freeswitch/scripts';
			}

			//copy the scripts directory
			if (!empty($source_directory) && is_readable($source_directory)) {
				//copy the main scripts
				recursive_copy($source_directory, $destination_directory);
				unset($source_directory);

				//copy the app/*/resource/install/scripts
				$app_scripts = glob($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'app/*/resource/scripts');
				foreach ($app_scripts as $app_script) {
					recursive_copy($app_script, $destination_directory);
				}
				unset($app_scripts);
			}
			else {
				throw new Exception("Cannot read from '$source_directory' to get the scripts");
			}
			chmod($destination_directory, 0775);
			unset($destination_directory);

		}

	}
}

/*
//example use

//update config.lua
	$obj = new switch;
	$obj->copy_files();
*/

?>
