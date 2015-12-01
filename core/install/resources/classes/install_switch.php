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

		protected $domain_uuid;
		protected $domain_name;
		protected $detect_switch;
		protected $config_lua;

		public $debug = false;

		function __construct($domain_name, $domain_uuid, $detect_switch) {
			if($detect_switch == null){
				if(strlen($_SESSION['event_socket_ip_address']) == 0 or strlen($_SESSION['event_socket_port']) == 0 or strlen($_SESSION['event_socket_password']) == 0 ){
					throw new Exception('The parameter $detect_switch was empty and i could not find the event socket details from the session');
				}
				$detect_switch = new detect_switch($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
				$domain_name = $_SESSION['domain_name'];
				$domain_uuid = $_SESSION['domain_uuid'];
			}elseif(!is_a($detect_switch, 'detect_switch')){
				throw new Exception('The parameter $detect_switch must be a detect_switch object (or a subclass of)');
			}
			$this->domain_uuid = $domain_uuid;
			$this->domain = $domain_name;
			$this->detect_switch = $detect_switch;
			if (is_dir("/etc/fusionpbx")){
				$this->config_lua = "/etc/fusionpbx/config.lua";
			} elseif (is_dir("/usr/local/etc/fusionpbx")){
				$this->config_lua = "/usr/local/etc/fusionpbx/config.lua";
			}
			else {
				$this->config_lua = $this->detect_switch->scripts_dir."/resources/config.lua";
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
			echo "$message\n";
		}

		//$options '-n' --no-clobber
		protected function recursive_copy($src, $dst, $options = '') {
			if (file_exists('/bin/cp')) {
				if (strtoupper(substr(PHP_OS, 0, 3)) === 'SUN') {
					//copy -R recursive, preserve attributes for SUN
					$cmd = 'cp -Rp '.$src.'/* '.$dst;
				} else {
					//copy -R recursive, -L follow symbolic links, -p preserve attributes for other Posix systemss
					$cmd = 'cp -RLp '.$options.' '.$src.'/* '.$dst;
				}
				$this->write_debug($cmd);
				exec ($cmd);
			}
			elseif(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'){
				exec("copy /L '$src' '$dst'");
			}
			else {
				$dir = opendir($src);
				if (!$dir) {
					if (!mkdir($src, 0755, true)) {
						throw new Exception("recursive_copy() source directory '".$src."' does not exist.");
					}
				}
				if (!is_dir($dst)) {
					if (!mkdir($dst, 0755, true)) {
						throw new Exception("recursive_copy() failed to create destination directory '".$dst."'");
					}
				}
				//This looks wrong, essentially if we can't use /bin/cp it manually fils dirs, not correct
				$scripts_dir_target = $_SESSION['switch']['scripts']['dir'];
				$scripts_dir_source = realpath($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/install/scripts');
				foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src)) as $file_path_source) {
					if (
					substr_count($file_path_source, '/..') == 0 &&
					substr_count($file_path_source, '/.') == 0 &&
					substr_count($file_path_source, '/.svn') == 0 &&
					substr_count($file_path_source, '/.git') == 0
					) {
						if ($dst != $src.'/resources/config.lua') {
							$this->write_debug($file_path_source.' ---> '.$dst);
							copy($file_path_source, $dst);
							chmod($dst, 0755);
						}
					}
				}

				while(false !== ($file = readdir($dir))) {
					if (($file != '.') && ($file != '..')) {
						if (is_dir($src.'/'.$file)) {
							$this->recursive_copy($src.'/'.$file, $dst.'/'.$file);
						}
						else {
						//copy only missing files -n --no-clobber
							if (strpos($options,'-n') !== false) {
								if (!file_exists($dst.'/'.$file)) {
									$this->write_debug("copy(".$src."/".$file.", ".$dst."/".$file.")");
									copy($src.'/'.$file, $dst.'/'.$file);
								}
							}
							else {
								copy($src.'/'.$file, $dst.'/'.$file);
							}
						}
					}
				}
				closedir($dir);
			}
		}

		protected function recursive_delete($dir) {
			if (file_exists('/bin/rm')) {
				$this->write_debug('rm -Rf '.$dir.'/*');
				exec ('rm -Rf '.$dir.'/*');
			}
			elseif(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'){
				$this->write_debug("del /S /F /Q '$dir'");
				exec("del /S /F /Q '$dir'");
			}
			else {
				foreach (glob($dir) as $file) {
					if (is_dir($file)) {
						$this->write_debug("rm dir: ".$file);
						$this->recursive_delete("$file/*");
						rmdir($file);
					} else {
						$this->write_debug("delete file: ".$file);
						unlink($file);
					}
				}
			}
			clearstatcache();
		}
		
		protected function backup_dir($dir, $backup_name){
			if (!is_readable($dir)) {
				throw new Exception("backup_dir() source directory '".$dir."' does not exist.");
			}
			$dst_tar = join( DIRECTORY_SEPARATOR, array(sys_get_temp_dir(), "$backup_name.tar"));
			//pharData is the correct ay to do it, but it keeps creating incomplete archives
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

		function install() {
			$this->write_progress("Install started for switch");
			$this->copy_conf();
			$this->copy_scripts();
			$this->create_config_lua();
			$this->write_progress("Install completed for switch");
		}

		function upgrade() {
			$this->copy_scripts();
			$this->create_config_lua();
		}

		function copy_conf() {
			$this->write_progress("\tCopying Config");
			//make a backup of the config
				if (file_exists($this->detect_switch->conf_dir())) {
					$this->backup_dir($this->detect_switch->conf_dir(), 'fusionpbx_switch_config');
					$this->recursive_delete($this->detect_switch->conf_dir());
				}
			//make sure the conf directory exists
				if (!is_dir($this->detect_switch->conf_dir())) {
					if (!mkdir($this->detect_switch->conf_dir(), 0774, true)) {
						throw new Exception("Failed to create the switch conf directory '".$this->detect_switch->conf_dir()."'. ");
					}
				}
			//copy resources/templates/conf to the freeswitch conf dir
				if (file_exists('/usr/share/examples/fusionpbx/resources/templates/conf')){
					$src_dir = "/usr/share/examples/fusionpbx/resources/templates/conf";
				}
				else {
					$src_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/templates/conf";
				}
				$dst_dir = $this->detect_switch->conf_dir();
				if (is_readable($dst_dir)) {
					$this->recursive_copy($src_dir, $dst_dir);
					unset($src_dir, $dst_dir);
				}
				$fax_dir = join( DIRECTORY_SEPARATOR, array($this->detect_switch->storage_dir(), 'fax'));
				if (!is_readable($fax_dir)) { mkdir($fax_dir,0777,true); }
				$voicemail_dir = join( DIRECTORY_SEPARATOR, array($this->detect_switch->storage_dir(), 'voicemail'));
				if (!is_readable($voicemail_dir)) { mkdir($voicemail_dir,0777,true); }
			
			//create the dialplan/default.xml for single tenant or dialplan/domain.xml
				if (file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/dialplan")) {
					$dialplan = new dialplan;
					$dialplan->domain_uuid = $this->domain_uuid;
					$dialplan->domain = $this->domain_name;
					$dialplan->switch_dialplan_dir = join( DIRECTORY_SEPARATOR, array($this->detect_switch->conf_dir(), "/dialplan"));
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
				if (file_exists($this->detect_switch->conf_dir())) {
					switch_conf_xml();
				}

		}

		function copy_scripts() {
			$this->write_progress("\tCopying Scripts");
			if (strlen($_SESSION['switch']['scripts']['dir']) > 0) {
				$script_dir = $_SESSION['switch']['scripts']['dir'];
			}
			else {
				$script_dir = $this->detect_switch->script_dir();
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
					$this->recursive_copy($src_dir, $dst_dir, $_SESSION['scripts']['options']['text']);
					unset($src_dir, $dst_dir);
				}else{
					throw new Exception("Cannot read from '$script_dir' to get teh scripts");
				}
				chmod($dst_dir, 0774);
			}else{
				$this->write_progress("\tSkipping scripts, scripts_dir is unset");
			}
		}

		function create_config_lua() {
			$this->write_progress("\tCreating " . $this->config_lua);
			global $db;
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
				throw new Exception("Failed to open '{$this->config_lua}' for writing");
			}
			$tmp = "\n";
			$tmp .= "--set the variables\n";
			if (strlen($_SESSION['switch']['sounds']['dir']) > 0) {
				$tmp .= normalize_path_to_os("	sounds_dir = [[".$_SESSION['switch']['sounds']['dir']."]];\n");
			}
			if (strlen($_SESSION['switch']['phrases']['dir']) > 0) {
				$tmp .= normalize_path_to_os("	phrases_dir = [[".$_SESSION['switch']['phrases']['dir']."]];\n");
			}
			if (strlen($_SESSION['switch']['db']['dir']) > 0) {
				$tmp .= normalize_path_to_os("	database_dir = [[".$_SESSION['switch']['db']['dir']."]];\n");
			}
			if (strlen($_SESSION['switch']['recordings']['dir']) > 0) {
				$tmp .= normalize_path_to_os("	recordings_dir = [[".$_SESSION['switch']['recordings']['dir']."]];\n");
			}
			if (strlen($_SESSION['switch']['storage']['dir']) > 0) {
				$tmp .= normalize_path_to_os("	storage_dir = [[".$_SESSION['switch']['storage']['dir']."]];\n");
			}
			if (strlen($_SESSION['switch']['voicemail']['dir']) > 0) {
				$tmp .= normalize_path_to_os("	voicemail_dir = [[".$_SESSION['switch']['voicemail']['dir']."]];\n");
			}
			if (strlen($_SESSION['switch']['scripts']['dir']) > 0) {
				$tmp .= normalize_path_to_os("	scripts_dir = [[".$_SESSION['switch']['scripts']['dir']."]];\n");
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

			if ((strlen($db_type) > 0) || (strlen($dsn_name) > 0)) {
				$tmp .= "--database information\n";
				$tmp .= "	database = {}\n";
				$tmp .= "	database[\"type\"] = \"".$db_type."\";\n";
				$tmp .= "	database[\"name\"] = \"".$db_name."\";\n";
				$tmp .= normalize_path_to_os("	database[\"path\"] = [[".$db_path."]];\n");

				if (strlen($dsn_name) > 0) {
					$tmp .= "	database[\"system\"] = \"odbc://".$dsn_name.":".$dsn_username.":".$dsn_password."\";\n";
					$tmp .= "	database[\"switch\"] = \"odbc://freeswitch:".$dsn_username.":".$dsn_password."\";\n";
				}
				elseif ($db_type == "pgsql") {
					if ($db_host == "localhost") { $db_host = "127.0.0.1"; }
					$tmp .= "	database[\"system\"] = \"pgsql://hostaddr=".$db_host." port=".$db_port." dbname=".$db_name." user=".$db_username." password=".$db_password." options='' application_name='".$db_name."'\";\n";
					$tmp .= "	database[\"switch\"] = \"pgsql://hostaddr=".$db_host." port=".$db_port." dbname=freeswitch user=".$db_username." password=".$db_password." options='' application_name='freeswitch'\";\n";
				}
				elseif ($db_type == "sqlite") {
					$tmp .= "	database[\"system\"] = \"sqlite://".$db_path."/".$db_name."\";\n";
					$tmp .= "	database[\"switch\"] = \"sqlite://".$_SESSION['switch']['db']['dir']."\";\n";
				}
				elseif ($db_type == "mysql") {
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
			$tmp .= "	domain_count = ".count($_SESSION["domains"]).";\n";
			$tmp .= normalize_path_to_os("	temp_dir = [[".$_SESSION['server']['temp']['dir']."]];\n");
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
?>