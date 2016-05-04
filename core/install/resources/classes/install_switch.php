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
			$this->write_progress("Install phase 1 completed for switch");
		}

		function install_phase_2() {
			$this->write_progress("Install phase 2 started for switch");
			$this->restart_switch();
			$this->write_progress("Install phase 2 completed for switch");
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

			//write the xml_cdr.conf.xml file
				if (file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/xml_cdr")) {
					xml_cdr_conf_xml();
				}

			//write the switch.conf.xml file
				if (file_exists($this->global_settings->switch_conf_dir())) {
					switch_conf_xml();
				}
		}

		protected function restart_switch() {
			$esl = new event_socket;
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