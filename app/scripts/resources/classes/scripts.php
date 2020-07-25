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

		/**
		 * Writes the config.lua
		 */
		public function write_config() {
			if (is_array($_SESSION['switch']['scripts'])) {

				//replace the backslash with a forward slash
					$this->db_path = str_replace("\\", "/", $this->db_path);

				//get the odbc information
					$sql = "select * from v_databases ";
					$sql .= "where database_driver = 'odbc' ";
					$database = new database;
					$row = $database->select($sql, null, 'row');
					if (is_array($row) && @sizeof($row) != 0) {
						$this->dsn_name = $row["database_name"];
						$this->dsn_username = $row["database_username"];
						$this->dsn_password = $row["database_password"];
					}
					unset($sql, $row);

				//get the recordings directory
					if (is_array($_SESSION['switch']['recordings'])) {
						$recordings_dir = $_SESSION['switch']['recordings']['dir'];
					}

				//get the http_protocol
					if (!isset($_SERVER['HTTP_PROTOCOL'])) {
						$_SERVER['HTTP_PROTOCOL'] = 'http';
						if (isset($_SERVER['REQUEST_SCHEME'])) { $_SERVER['HTTP_PROTOCOL'] = $_SERVER['REQUEST_SCHEME']; }
						if ($_SERVER['HTTPS'] == 'on') { $_SERVER['HTTP_PROTOCOL'] = 'https'; }
						if ($_SERVER['SERVER_PORT'] == '443') { $_SERVER['HTTP_PROTOCOL'] = 'https'; }
					}

				//find the location to write the config.lua
					if (is_dir("/etc/fusionpbx")){
						$config = "/etc/fusionpbx/config.lua";
					}
					else if (is_dir("/usr/local/etc/fusionpbx")){
						$config = "/usr/local/etc/fusionpbx/config.lua";
					}
					else {
						$config = $_SESSION['switch']['scripts']['dir']."/resources/config.lua";
					}
					$fout = fopen($config,"w");
					if(!$fout){
						return;
					}

				//make the config.lua
					$tmp = "\n";
					$tmp .= "--set the variables\n";
					if (strlen($_SESSION['switch']['conf']['dir']) > 0) {
						$tmp .= $this->correct_path("	conf_dir = [[".$_SESSION['switch']['conf']['dir']."]];\n");
					}
					if (strlen($_SESSION['switch']['sounds']['dir']) > 0) {
						$tmp .= $this->correct_path("	sounds_dir = [[".$_SESSION['switch']['sounds']['dir']."]];\n");
					}
					if (strlen($_SESSION['switch']['db']['dir']) > 0) {
						$tmp .= $this->correct_path("	database_dir = [[".$_SESSION['switch']['db']['dir']."]];\n");
					}
					if (strlen($_SESSION['switch']['recordings']['dir']) > 0) {
						$tmp .= $this->correct_path("	recordings_dir = [[".$recordings_dir."]];\n");
					}
					if (strlen($_SESSION['switch']['storage']['dir']) > 0) {
						$tmp .= $this->correct_path("	storage_dir = [[".$_SESSION['switch']['storage']['dir']."]];\n");
					}
					if (strlen($_SESSION['switch']['voicemail']['dir']) > 0) {
						$tmp .= $this->correct_path("	voicemail_dir = [[".$_SESSION['switch']['voicemail']['dir']."]];\n");
					}
					if (strlen($_SESSION['switch']['scripts']['dir']) > 0) {
						$tmp .= $this->correct_path("	scripts_dir = [[".$_SESSION['switch']['scripts']['dir']."]];\n");
					}
					$tmp .= $this->correct_path("	php_dir = [[".PHP_BINDIR."]];\n");
					if (substr(strtoupper(PHP_OS), 0, 3) == "WIN") {
						$tmp .= "	php_bin = \"php.exe\";\n";
					}
					elseif (file_exists(PHP_BINDIR."/php5")) { 
	 					$tmp .= "	php_bin = \"php5\";\n";
 					}
					else {
						$tmp .= "	php_bin = \"php\";\n";
					}
					$tmp .= $this->correct_path("	document_root = [[".$_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."]];\n");
					$tmp .= $this->correct_path("	project_path = [[".PROJECT_PATH."]];\n");
					$tmp .= $this->correct_path("	http_protocol = [[".$_SERVER['HTTP_PROTOCOL']."]];\n");
					$tmp .= "\n";

					$tmp .= "--cache settings\n";
					$tmp .= "	cache = {}\n";
					if (strlen($_SESSION['cache']['method']['text']) > 0) {
						$tmp .= "	cache.method = [[".$_SESSION['cache']['method']['text']."]];\n";  //file, memcache
					}
					if (strlen($_SESSION['cache']['location']['text']) > 0) {
						$tmp .= "	cache.location = [[".$_SESSION['cache']['location']['text']."]];\n";
					}
					$tmp .= "	cache.settings = false;\n";
					$tmp .= "\n";

					if ((strlen($this->db_type) > 0) || (strlen($this->dsn_name) > 0)) {
						$tmp .= "--database information\n";
						$tmp .= "	database = {}\n";
						$tmp .= "	database.type = \"".$this->db_type."\";\n";
						$tmp .= "	database.name = \"".$this->db_name."\";\n";
						$tmp .= $this->correct_path("	database.path = [[".$this->db_path."]];\n");

						if (strlen($this->dsn_name) > 0) {
							$tmp .= "	database.system = \"odbc://".$this->dsn_name.":".$this->dsn_username.":".$this->dsn_password."\";\n";
							$tmp .= "	database.switch = \"odbc://freeswitch:".$this->dsn_username.":".$this->dsn_password."\";\n";
						}
						elseif ($this->db_type == "pgsql") {
							if ($this->db_host == "localhost") { $this->db_host = "127.0.0.1"; }
							$host = filter_var($this->db_host, FILTER_VALIDATE_IP) ? "hostaddr" : "host";
							if ($this->db_secure == true) {
								$tmp .= "	database.system = \"pgsql://".$host."=".$this->db_host." port=".$this->db_port." dbname=".$this->db_name." user=".$this->db_username." password=".$this->db_password." sslmode=verify-ca sslrootcert=".$this->db_cert_authority." options=''\";\n";
								$tmp .= "	database.switch = \"pgsql://".$host."=".$this->db_host." port=".$this->db_port." dbname=freeswitch user=".$this->db_username." password=".$this->db_password." sslmode=verify-ca sslrootcert=".$this->db_cert_authority." options=''\";\n";
							}
							else {
								$tmp .= "	database.system = \"pgsql://".$host."=".$this->db_host." port=".$this->db_port." dbname=".$this->db_name." user=".$this->db_username." password=".$this->db_password." options=''\";\n";
								$tmp .= "	database.switch = \"pgsql://".$host."=".$this->db_host." port=".$this->db_port." dbname=freeswitch user=".$this->db_username." password=".$this->db_password." options=''\";\n";
							}
						}
						elseif ($this->db_type == "sqlite") {
							$tmp .= "	database.system = \"sqlite://".$this->db_path."/".$this->db_name."\";\n";
							$tmp .= "	database.switch = \"sqlite://".$_SESSION['switch']['db']['dir']."\";\n";
						}
						elseif ($this->db_type == "mysql") {
							$tmp .= "	database.system = \"\";\n";
							$tmp .= "	database.switch = \"\";\n";
						}
						$tmp .= "\n";
						$tmp .= "	database.backend = {}\n";
						$tmp .= "	database.backend.base64 = 'luasql'\n";
						$tmp .= "\n";
					}
					$tmp .= "--set defaults\n";
					$tmp .= "	expire = {}\n";
					$tmp .= "	expire.default = \"3600\";\n";
					$tmp .= "	expire.directory = \"3600\";\n";
					$tmp .= "	expire.dialplan = \"3600\";\n";
					$tmp .= "	expire.languages = \"3600\";\n";
					$tmp .= "	expire.sofia = \"3600\";\n";
					$tmp .= "	expire.acl = \"3600\";\n";
					$tmp .= "	expire.ivr = \"3600\";\n";
					$tmp .= "\n";
					$tmp .= "--set xml_handler\n";
					$tmp .= "	xml_handler = {}\n";
					$tmp .= "	xml_handler.fs_path = false;\n";
					$tmp .= "	xml_handler.reg_as_number_alias = false;\n";
					$tmp .= "	xml_handler.number_as_presence_id = true;\n";
					$tmp .= "\n";
					$tmp .= "--set settings\n";
					$tmp .= "	settings = {}\n";
					$tmp .= "	settings.recordings = {}\n";
					$tmp .= "	settings.voicemail = {}\n";
					$tmp .= "	settings.fax = {}\n";
					if (isset($_SESSION['recordings']['storage_type']['text'])) {
						$tmp .= "	settings.recordings.storage_type = \"".$_SESSION['recordings']['storage_type']['text']."\";\n";
					}
					else {
						$tmp .= "	settings.recordings.storage_type = \"\";\n";
					}
					if (isset($_SESSION['voicemail']['storage_type']['text'])) {
						$tmp .= "	settings.voicemail.storage_type = \"".$_SESSION['voicemail']['storage_type']['text']."\";\n";
					}
					else {
						$tmp .= "	settings.voicemail.storage_type = \"\";\n";
					}
					if (isset($_SESSION['fax']['storage_type']['text'])) {
						$tmp .= "	settings.fax.storage_type = \"".$_SESSION['fax']['storage_type']['text']."\";\n";
					}
					else {
						$tmp .= "	settings.fax.storage_type = \"\";\n";
					}
					$tmp .= "\n";	
					$tmp .= "--set the debug options\n";
					$tmp .= "	debug.params = false;\n";
					$tmp .= "	debug.sql = false;\n";
					$tmp .= "	debug.xml_request = false;\n";
					$tmp .= "	debug.xml_string = false;\n";
					$tmp .= "	debug.cache = false;\n";
					$tmp .= "\n";
					$tmp .= "--additional info\n";
					$tmp .= "	domain_count = ".count($_SESSION["domains"]).";\n";
					$tmp .= $this->correct_path("	temp_dir = [[".$_SESSION['server']['temp']['dir']."]];\n");
					if (isset($_SESSION['domain']['dial_string']['text'])) {
						$tmp .= "	dial_string = \"".$_SESSION['domain']['dial_string']['text']."\";\n";
					}
					$tmp .= "\n";
					$tmp .= "--include local.lua\n";
					$tmp .= "	require(\"resources.functions.file_exists\");\n";
					$tmp .= "	if (file_exists(\"/etc/fusionpbx/local.lua\")) then\n";
					$tmp .= "		dofile(\"/etc/fusionpbx/local.lua\");\n";
					$tmp .= "	elseif (file_exists(\"/usr/local/etc/fusionpbx/local.lua\")) then\n";
					$tmp .= "		dofile(\"/usr/local/etc/fusionpbx/local.lua\");\n";
					$tmp .= "	elseif (file_exists(scripts_dir..\"/resources/local.lua\")) then\n";
					$tmp .= "		require(\"resources.local\");\n";
					$tmp .= "	end\n";
					fwrite($fout, $tmp);
					unset($tmp);
					fclose($fout);
			}
		}

	}
}

/*
//example use

//update config.lua
	$obj = new scripts;
	$obj->write_config();
*/

?>
