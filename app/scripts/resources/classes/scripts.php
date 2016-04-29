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
			//connect to the database if not connected
			require_once "resources/classes/database.php";
			$database = new database;
			$database->connect();
			$this->db = $database->db;
			$this->db_type = $database->type;
			$this->db_name = $database->db_name;
			$this->db_host = $database->host;
			$this->db_port = $database->port;
			$this->db_path = $database->path;
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
			if (strlen($_SESSION['switch']['scripts']['dir']) > 0) {
				$dst_dir = $_SESSION['switch']['scripts']['dir'];
				if(strlen($dst_dir) == 0) {
					throw new Exception("Cannot copy scripts the 'script_dir' is empty");
				}
				if (file_exists($dst_dir)) {
					//get the source directory
					if (file_exists('/usr/share/examples/fusionpbx/resources/install/scripts')){
						$src_dir = '/usr/share/examples/fusionpbx/resources/install/scripts';
					}
					else {
						$src_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/install/scripts';
					}
					if (is_readable($dst_dir)) {
						recursive_copy($src_dir,$dst_dir);
						unset($src_dir);
					}else{
						throw new Exception("Cannot read from '$src_dir' to get the scripts");
					}
					chmod($dst_dir, 0775);
                                        unset($dst_dir);
				} else {
					throw new Exception("Scripts directory doesn't exist");
				}
			}
		}

		/**
		 * Writes the config.lua
		 */
		public function write_config() {
			if (is_dir($_SESSION['switch']['scripts']['dir'])) {

				//replace the backslash with a forward slash
					$this->db_path = str_replace("\\", "/", $this->db_path);

				//get the odbc information
					$sql = "select count(*) as num_rows from v_databases ";
					$sql .= "where database_driver = 'odbc' ";
					$prep_statement = $this->db->prepare($sql);
					if ($prep_statement) {
						$prep_statement->execute();
						$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
						unset($prep_statement);
						if ($row['num_rows'] > 0) {
							$odbc_num_rows = $row['num_rows'];

							$sql = "select * from v_databases ";
							$sql .= "where database_driver = 'odbc' ";
							$prep_statement = $this->db->prepare(check_sql($sql));
							$prep_statement->execute();
							$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
							foreach ($result as &$row) {
								$this->dsn_name = $row["database_name"];
								$this->dsn_username = $row["database_username"];
								$this->dsn_password = $row["database_password"];
								break; //limit to 1 row
							}
							unset ($prep_statement);
						}
						else {
							$odbc_num_rows = '0';
						}
					}

				//get the recordings directory
					$recordings_dir = $_SESSION['switch']['recordings']['dir'];

				//find the location to write the config.lua
					if (is_dir("/etc/fusionpbx")){
						$config = "/etc/fusionpbx/config.lua";
					} elseif (is_dir("/usr/local/etc/fusionpbx")){
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
					if (strlen($_SESSION['switch']['sounds']['dir']) > 0) {
						$tmp .= $this->correct_path("	sounds_dir = [[".$_SESSION['switch']['sounds']['dir']."]];\n");
					}
					if (strlen($_SESSION['switch']['phrases']['dir']) > 0) {
						$tmp .= $this->correct_path("	phrases_dir = [[".$_SESSION['switch']['phrases']['dir']."]];\n");
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
					else {
						$tmp .= "	php_bin = \"php5\";\n";
					}
					$tmp .= $this->correct_path("	document_root = [[".$_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."]];\n");
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
							$tmp .= "	database.system = \"pgsql://hostaddr=".$this->db_host." port=".$this->db_port." dbname=".$this->db_name." user=".$this->db_username." password=".$this->db_password." options='' application_name='".$this->db_name."'\";\n";
							$tmp .= "	database.switch = \"pgsql://hostaddr=".$this->db_host." port=".$this->db_port." dbname=freeswitch user=".$this->db_username." password=".$this->db_password." options='' application_name='freeswitch'\";\n";
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
					}
					$tmp .= "--set defaults\n";
					$tmp .= "	expire = {}\n";
					$tmp .= "	expire.directory = \"3600\";\n";
					$tmp .= "	expire.dialplan = \"3600\";\n";
					$tmp .= "	expire.languages = \"3600\";\n";
					$tmp .= "	expire.sofia = \"3600\";\n";
					$tmp .= "	expire.acl = \"3600\";\n";
					$tmp .= "\n";
					$tmp .= "--set xml_handler\n";
					$tmp .= "	xml_handler = {}\n";
					$tmp .= "	xml_handler.fs_path = false;\n";
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
		} //end config_lua
	} //end scripts class
}
/*
//example use

//update config.lua
	$obj = new scripts;
	$obj->write_config();
*/
?>