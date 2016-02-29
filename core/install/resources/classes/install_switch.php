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
	Copyright (C) 2010-2015
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";

//define the install class
	class install_switch {

		protected $global_settings;
		protected $config_lua;
		protected $dbh;

		public $debug = false;
		public $echo_progress = false;

		function __construct($global_settings) {
			if(is_null($global_settings)){
				require_once "core/install/resources/classes/global_settings.php";
				$global_settings = new global_settings();
			}elseif(!is_a($global_settings, 'global_settings')){
				throw new Exception('The parameter $global_settings must be a global_settings object (or a subclass of)');
			}
			$this->global_settings = $global_settings;
			if (is_dir("/etc/fusionpbx")){
				$this->config_lua = "/etc/fusionpbx/config.lua";
			}elseif (is_dir("/usr/local/etc/fusionpbx")){
				$this->config_lua = "/usr/local/etc/fusionpbx/config.lua";
			}elseif(strlen($this->global_settings->switch_script_dir()) > 0) {
				$this->config_lua = $this->global_settings->switch_script_dir()."/resources/config.lua";
			}else{
				throw new Exception("Could not work out where to put the config.lua");
			}
			$this->config_lua = normalize_path_to_os($this->config_lua);
		}

		//utility Functions
		function write_debug($message) {
			if($this->debug){
				echo "$message\n";
			}
		}
		
		function write_progress($message) {
			if($this->echo_progress){
				echo "$message\n";
			}
		}

		protected function backup_dir($dir, $backup_name){
			if (!is_readable($dir)) {
				throw new Exception("backup_dir() source directory '".$dir."' does not exist.");
			}
			$dst_tar = join( DIRECTORY_SEPARATOR, array(sys_get_temp_dir(), "$backup_name.tar"));
			//pharData is the correct way to do it, but it keeps creating incomplete archives
			//$tar = new PharData($dst_tar);
			//$tar->buildFromDirectory($dir);
			$this->write_debug("backing up to $dst_tar");
			if (file_exists('/bin/tar')) {
				exec('tar -cvf ' .$dst_tar. ' -C '.$dir .' .');
			}else{
				$this->write_debug('WARN: old config could not be compressed');
				$dst_dir = join( DIRECTORY_SEPARATOR, array(sys_get_temp_dir(), "$backup_name"));
				recursive_copy($dir, $dst_dir);
			}
		}

		function install_phase_1() {
			$this->write_progress("Install phase 1 started for switch");
			$this->copy_conf();
			$this->copy_scripts();
			$this->write_progress("Install phase 1 completed for switch");
		}

		function install_phase_2() {
			$this->write_progress("Install phase 2 started for switch");
			$this->create_config_lua();
			$this->restart_switch();
			$this->write_progress("Install phase 2 completed for switch");
		}

		function upgrade() {
			$this->copy_scripts();
			$this->create_config_lua();
		}

		protected function copy_conf() {
			//send a message
				$this->write_progress("\tCopying Config");

			//make a backup of the config
				if (file_exists($this->global_settings->switch_conf_dir())) {
					$this->backup_dir($this->global_settings->switch_conf_dir(), 'fusionpbx_switch_config');
					recursive_delete($this->global_settings->switch_conf_dir());
				}

			//make sure the conf directory exists
				if (!is_dir($this->global_settings->switch_conf_dir())) {
					if (!mkdir($this->global_settings->switch_conf_dir(), 0774, true)) {
						throw new Exception("Failed to create the switch conf directory '".$this->global_settings->switch_conf_dir()."'. ");
					}
				}

			//copy resources/templates/conf to the freeswitch conf dir
				if (file_exists('/usr/share/examples/fusionpbx/resources/templates/conf')){
					$src_dir = "/usr/share/examples/fusionpbx/resources/templates/conf";
				}
				else {
					$src_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/templates/conf";
				}
				$dst_dir = $this->global_settings->switch_conf_dir();
				if (is_readable($dst_dir)) {
					recursive_copy($src_dir, $dst_dir);
					unset($src_dir, $dst_dir);
				}
				$fax_dir = join( DIRECTORY_SEPARATOR, array($this->global_settings->switch_storage_dir(), 'fax'));
				if (!is_readable($fax_dir)) { mkdir($fax_dir,0777,true); }
				$voicemail_dir = join( DIRECTORY_SEPARATOR, array($this->global_settings->switch_storage_dir(), 'voicemail'));
				if (!is_readable($voicemail_dir)) { mkdir($voicemail_dir,0777,true); }

			//create the dialplan/default.xml for single tenant or dialplan/domain.xml
				if (file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/dialplan")) {
					$dialplan = new dialplan;
					$dialplan->domain_uuid = $this->domain_uuid;
					$dialplan->domain = $this->domain_name;
					$dialplan->switch_dialplan_dir = join( DIRECTORY_SEPARATOR, array($this->global_settings->switch_conf_dir(), "/dialplan"));
					$dialplan->restore_advanced_xml();
					if($this->_debug){
						print_r($dialplan->result, $message);
						$this->write_debug($message);
					}
				}

			//write the xml_cdr.conf.xml file
				if (file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/xml_cdr")) {
					xml_cdr_conf_xml();
				}

			//write the switch.conf.xml file
				if (file_exists($this->global_settings->switch_conf_dir())) {
					switch_conf_xml();
				}

		}

		protected function copy_scripts() {
			$this->write_progress("\tCopying Scripts");
			$script_dir = $this->global_settings->switch_script_dir();
			if(strlen($script_dir) == 0) {
				throw new Exception("Cannot copy scripts the 'script_dir' is empty");
			}
			if (file_exists($script_dir)) {
				if (file_exists('/usr/share/examples/fusionpbx/resources/install/scripts')){
					$src_dir = '/usr/share/examples/fusionpbx/resources/install/scripts';
				}
				else {
					$src_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/install/scripts';
				}
				$dst_dir = $script_dir;
				if (is_readable($script_dir)) {
					recursive_copy($src_dir, $dst_dir, $_SESSION['scripts']['options']['text']);
					unset($src_dir, $dst_dir);
				}else{
					throw new Exception("Cannot read from '$src_dir' to get the scripts");
				}
				chmod($dst_dir, 0774);
			}else{
				$this->write_progress("\tSkipping scripts, script_dir is unset");
			}
		}

		public function create_config_lua() {
			//define the database connection as global
				global $db;

			//send progress
				$this->write_progress("\tCreating " . $this->config_lua);

			//set the directories
				$path = dirname($this->config_lua);
				$parent_dir = basename($path);
				if ($parent_dir == 'resources' and !file_exists($path)){
					$this->write_progress("\t... creating missing '$path'");
					if (!mkdir($path, 0755, true)) {
						throw new Exception("Failed to create the missing resources directory '$path'");
					}
				}

			//get the odbc information
				$sql = "select count(*) as num_rows from v_databases ";
				$sql .= "where database_driver = 'odbc' ";
				if (strlen($order_by)> 0) { $sql .= "order by $order_by $order "; }
				$prep_statement = $db->prepare($sql);
				if ($prep_statement) {
					$prep_statement->execute();
					$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
					unset($prep_statement);
					if ($row['num_rows'] > 0) {
						$odbc_num_rows = $row['num_rows'];
						$sql = "select * from v_databases ";
						$sql .= "where database_driver = 'odbc' ";
						$prep_statement = $db->prepare(check_sql($sql));
						$prep_statement->execute();
						$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
						foreach ($result as &$row) {
							$dsn_name = $row["database_name"];
							$dsn_username = $row["database_username"];
							$dsn_password = $row["database_password"];
							break; //limit to 1 row
						}
						unset ($prep_statement);
					}
					else {
						$odbc_num_rows = '0';
					}
				}

			//config.lua
				$fout = fopen($this->config_lua,"w");
				if(!$fout){
					throw new Exception("Failed to open '".$this->config_lua."' for writing");
				}
				$tmp = "\n";
				$tmp .= "--set the variables\n";
				if (strlen($this->global_settings->switch_sounds_dir()) > 0) {
					$tmp .= normalize_path_to_os("	sounds_dir = [[".$this->global_settings->switch_sounds_dir()."]];\n");
				}
				if (strlen($this->global_settings->switch_phrases_vdir()) > 0) {
					$tmp .= normalize_path_to_os("	phrases_dir = [[".$this->global_settings->switch_phrases_vdir()."]];\n");
				}
				if (strlen($this->global_settings->switch_db_dir()) > 0) {
					$tmp .= normalize_path_to_os("	database_dir = [[".$this->global_settings->switch_db_dir()."]];\n");
				}
				if (strlen($this->global_settings->switch_recordings_dir()) > 0) {
					$tmp .= normalize_path_to_os("	recordings_dir = [[".$this->global_settings->switch_recordings_dir()."]];\n");
				}
				if (strlen($this->global_settings->switch_storage_dir()) > 0) {
					$tmp .= normalize_path_to_os("	storage_dir = [[".$this->global_settings->switch_storage_dir()."]];\n");
				}
				if (strlen($this->global_settings->switch_voicemail_vdir()) > 0) {
					$tmp .= normalize_path_to_os("	voicemail_dir = [[".$this->global_settings->switch_voicemail_vdir()."]];\n");
				}
				if (strlen($this->global_settings->switch_script_dir()) > 0) {
					$tmp .= normalize_path_to_os("	scripts_dir = [[".$this->global_settings->switch_script_dir()."]];\n");
				}
				$tmp .= normalize_path_to_os("	php_dir = [[".PHP_BINDIR."]];\n");
				if (substr(strtoupper(PHP_OS), 0, 3) == "WIN") {
					$tmp .= "	php_bin = \"php.exe\";\n";
				}
				else {
					$tmp .= "	php_bin = \"php\";\n";
				}
				$tmp .= normalize_path_to_os("	document_root = [[".$_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."]];\n");
				$tmp .= "\n";

				if ((strlen($this->global_settings->db_type()) > 0) || (strlen($dsn_name) > 0)) {
					$tmp .= "--database information\n";
					$tmp .= "	database = {}\n";
					$tmp .= "	database[\"type\"] = \"".$this->global_settings->db_type()."\";\n";
					$tmp .= "	database[\"name\"] = \"".$this->global_settings->db_name()."\";\n";
					$tmp .= normalize_path_to_os("	database[\"path\"] = [[".$this->global_settings->db_path()."]];\n");

					if (strlen($dsn_name) > 0) {
						$tmp .= "	database[\"system\"] = \"odbc://".$dsn_name.":".$dsn_username.":".$dsn_password."\";\n";
						$tmp .= "	database[\"switch\"] = \"odbc://freeswitch:".$dsn_username.":".$dsn_password."\";\n";
					}
					elseif ($this->global_settings->db_type() == "pgsql") {
						$db_host = $this->global_settings->db_host();
						if($db_host == 'localhost') { $db_host = '127.0.0.1'; } // lua cannot resolve localhost
						if (filter_var($db_host, FILTER_VALIDATE_IP)) {
							$host_type = "hostaddr";
						}
						else {
							$host_type = "host";
						}
						$tmp .= "	database[\"system\"] = \"pgsql://".$host_type."=".$db_host." port=".$this->global_settings->db_port()." dbname=".$this->global_settings->db_name()." user=".$this->global_settings->db_username()." password=".$this->global_settings->db_password()." options='' application_name='".$this->global_settings->db_name()."'\";\n";
						$tmp .= "	database[\"switch\"] = \"pgsql://".$host_type."=".$db_host." port=".$this->global_settings->db_port()." dbname=freeswitch user=".$this->global_settings->db_username()." password=".$this->global_settings->db_password()." options='' application_name='freeswitch'\";\n";
					}
					elseif ($this->global_settings->db_type() == "sqlite") {
						$tmp .= "	database[\"system\"] = \"sqlite://".$this->global_settings->db_path()."/".$this->global_settings->db_name()."\";\n";
						$tmp .= "	database[\"switch\"] = \"sqlite://".$_SESSION['switch']['db']['dir']."\";\n";
					}
					elseif ($this->global_settings->db_type() == "mysql") {
						$tmp .= "	database[\"system\"] = \"\";\n";
						$tmp .= "	database[\"switch\"] = \"\";\n";
					}
					$tmp .= "\n";
				}
				$tmp .= "--set defaults\n";
				$tmp .= "	expire = {}\n";
				$tmp .= "	expire[\"directory\"] = \"3600\";\n";
				$tmp .= "	expire[\"dialplan\"] = \"3600\";\n";
				$tmp .= "	expire[\"languages\"] = \"3600\";\n";
				$tmp .= "	expire[\"sofia.conf\"] = \"3600\";\n";
				$tmp .= "	expire[\"acl.conf\"] = \"3600\";\n";
				$tmp .= "\n";
				$tmp .= "--set xml_handler\n";
				$tmp .= "	xml_handler = {}\n";
				$tmp .= "	xml_handler[\"fs_path\"] = false;\n";
				$tmp .= "\n";
				$tmp .= "--set the debug options\n";
				$tmp .= "	debug[\"params\"] = false;\n";
				$tmp .= "	debug[\"sql\"] = false;\n";
				$tmp .= "	debug[\"xml_request\"] = false;\n";
				$tmp .= "	debug[\"xml_string\"] = false;\n";
				$tmp .= "	debug[\"cache\"] = false;\n";
				$tmp .= "\n";
				$tmp .= "--additional info\n";
				$tmp .= "	domain_count = ".$this->global_settings->domain_count().";\n";
				$tmp .= normalize_path_to_os("	temp_dir = [[".$this->global_settings->switch_temp_dir()."]];\n");
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

		protected function restart_switch() {
			$esl = new EventSocket;
			if(!$esl->connect($this->global_settings->switch_event_host(), $this->global_settings->switch_event_port(), $this->global_settings->switch_event_password())) {
				throw new Exception("Failed to connect to switch");
			}
			if (!$esl->request('api fsctl shutdown restart elegant')){
				throw new Exception("Failed to send switch restart");
			}
			$esl->reset_fp();
		}
	}
?>