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
	Matthew Vale <github@mafoo.org>
	
*/
require_once "root.php";
require_once "resources/classes/EventSocket.php";

//define the install class
	class detect_switch {

		// version information
		protected $_major;
		protected $_minor;

		// folders
		protected $_base_dir = '';
		protected $_cache_dir = '';
		protected $_certs_dir = '';
		protected $_conf_dir = '';
		protected $_db_dir = '';
		protected $_external_ssl_dir = '';
		protected $_grammar_dir = '';
		protected $_htdocs_dir = '';
		protected $_internal_ssl_dir = '';
		protected $_log_dir = '';
		protected $_mod_dir = '';
		protected $_recordings_dir = '';
		protected $_run_dir = '';
		protected $_script_dir = '';
		protected $_sounds_dir = '';
		protected $_storage_dir = '';
		protected $_temp_dir = '';
		
		function major() { return $this->_major; }
		function minor() { return $this->_minor; }
		function base_dir() { return $this->_base_dir; }
		function cache_dir() { return $this->_cache_dir; }
		function certs_dir() { return $this->_certs_dir; }
		function conf_dir() { return $this->_conf_dir; }
		function db_dir() { return $this->_db_dir; }
		function external_ssl_dir() { return $this->_external_ssl_dir; }
		function grammar_dir() { return $this->_grammar_dir; }
		function htdocs_dir() { return $this->_htdocs_dir; }
		function internal_ssl_dir() { return $this->_internal_ssl_dir; }
		function log_dir() { return $this->_log_dir; }
		function mod_dir() { return $this->_mod_dir; }
		function recordings_dir() { return $this->_recordings_dir; }
		function run_dir() { return $this->_run_dir; }
		function script_dir() { return $this->_script_dir; }
		function sounds_dir() { return $this->_sounds_dir; }
		function storage_dir() { return $this->_storage_dir; }
		function temp_dir() { return $this->_temp_dir; }

		// event socket
		public $event_host = 'localhost';
		public $event_port = '8021';
		public $event_password = 'ClueCon';
		protected $event_socket;

		public function __construct($event_host, $event_port, $event_password) {
			if($event_host){		$this->event_host = $event_host; }
			if($event_port){		$this->event_port = $event_port; }
			if($event_password){	$this->event_password = $event_password; }
			$this->connect_event_socket();
			if(!$this->event_socket){
				$this->detect_event_socket();
			}
		}
		
		protected function detect_event_socket() {
			//perform searches for user's config here
		}
		
		public function detect() {
			$this->connect_event_socket();
			if(!$this->event_socket){
				throw new Exception('Failed to use event socket');
			}
			$FS_Version = $this->event_socket_request('api version');
			preg_match("/FreeSWITCH Version (\d+)\.(\d(?:\.\d+)?)/", $FS_Version, $matches);
			$this->_major = $matches[1];
			$this->_minor = $matches[2];
			$FS_Vars = $this->event_socket_request('api global_getvar');
			foreach (explode("\n",$FS_Vars) as $FS_Var){
				preg_match("/(\w+_dir)=(.*)/", $FS_Var, $matches);
				if(count($matches) > 0 and property_exists($this, "_" . $matches[1])){
					$field = "_" . $matches[1];
					$this->$field = $matches[2];
				}
			}
		}
		
		public function show_config() {
			$buffer = '';		
			$buffer .= "FreeSWITCH Verson => " . $this->_major . "." . $this->_minor . "\n";
			$buffer .= "base_dir => " . $this->_base_dir ."\n";
			$buffer .= "cache_dir => " . $this->_cache_dir ."\n";
			$buffer .= "certs_dir => " . $this->_certs_dir ."\n";
			$buffer .= "conf_dir => " . $this->_conf_dir ."\n";
			$buffer .= "db_dir => " . $this->_db_dir ."\n";
			$buffer .= "external_ssl_dir => " . $this->_external_ssl_dir ."\n";
			$buffer .= "grammar_dir => " . $this->_grammar_dir ."\n";
			$buffer .= "htdocs_dir => " . $this->_htdocs_dir ."\n";
			$buffer .= "internal_ssl_dir => " . $this->_internal_ssl_dir ."\n";
			$buffer .= "log_dir => " . $this->_log_dir ."\n";
			$buffer .= "mod_dir => " . $this->_mod_dir ."\n";
			$buffer .= "recordings_dir => " . $this->_recordings_dir ."\n";
			$buffer .= "run_dir => " . $this->_run_dir ."\n";
			$buffer .= "script_dir => " . $this->_script_dir ."\n";
			$buffer .= "sounds_dir => " . $this->_sounds_dir ."\n";
			$buffer .= "storage_dir => " . $this->_storage_dir ."\n";
			$buffer .= "temp_dir => " . $this->_temp_dir ."\n";
			return $buffer;
		}
		
		protected function connect_event_socket(){
			$esl = new EventSocket;
			if ($esl->connect($this->event_host, $this->event_port, $this->event_password)) {
				$this->event_socket = $esl->reset_fp();
				return true;
			}
			return false;
		}
	
		protected function event_socket_request($cmd) {
			$esl = new EventSocket($this->event_socket);
			$result = $esl->request($cmd);
			$esl->reset_fp();
			return $result;
		}
		
		public function restart_switch() {
			$this->connect_event_socket();
			if(!$this->event_socket){
				throw new Exception('Failed to use event socket');
			}
			$this->event_socket_request('api fsctl shutdown restart elegant');
		}
	}
?>