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
	Portions created by the Initial Developer are Copyright (C) 2008-2020
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

/**
 * scripts class provides methods for creating the config.lua and copying switch scripts
 *
 * @method string correct_path
 * @method string copy_files
 * @method string write_config
 */
if (!class_exists('scripts')) {
	class scripts {

		public $db;
		public $db_type;
		public $db_name;
		public $db_secure;
		public $db_cert_authority;
		public $db_host;
		public $db_port;
		public $db_path;
		public $db_username;
		public $db_password;
		public $dsn_name;
		public $dsn_username;
		public $dsn_password;

		/**
		 * Called when the object is created
		 */
		public function __construct() {
			//get database properties
			$database = new database;
			$database->connect();
			$this->db = $database->db;
			$this->db_type = $database->type;
			$this->db_name = $database->db_name;
			$this->db_host = $database->host;
			$this->db_port = $database->port;
			$this->db_path = $database->path;
			$this->db_secure = $database->db_secure;
			$this->db_cert_authority = $database->db_cert_authority;
			$this->db_username = $database->username;
			$this->db_password = $database->password;
		}

		/**
		 * Called when there are no references to a particular object
		 * unset the variables used in the class
		 */
		public function __destruct() {
			foreach ($this as $key => $value) {
				unset($this->$key);
			}
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
		 * Copy the switch scripts from the web directory to the switch directory
		 */
		public function copy_files() {
			if (is_array($_SESSION['switch']['scripts'])) {
				$destination_directory = $_SESSION['switch']['scripts']['dir'];
				if (file_exists($destination_directory)) {
					//get the source directory
					if (file_exists('/usr/share/examples/fusionpbx/scripts')) {
						$source_directory = '/usr/share/examples/fusionpbx/scripts';
					}
					else {
						$source_directory = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/scripts/resources/scripts';
					}
					if (is_readable($source_directory)) {
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

	}
}

/*
//example use

//update config.lua
	$obj = new scripts;
	$obj->copy_files();
*/

?>
