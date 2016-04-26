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

//define the install class
	class global_settings {

		// cached data
		protected $_switch_dirs;
		protected $_switch_vdirs;
		public function get_switch_dirs()	{ return $this->_switch_dirs; }
		public function get_switch_vdirs()	{ return $this->_switch_vdirs; }

		// dirs - detected from the switch
		protected $_switch_base_dir = '';
		protected $_switch_cache_dir = '';
		protected $_switch_conf_dir = '';
		protected $_switch_db_dir = '';
		protected $_switch_grammar_dir = '';
		protected $_switch_htdocs_dir = '';
		protected $_switch_log_dir = '';
		protected $_switch_mod_dir = '';
		protected $_switch_recordings_dir = '';
		protected $_switch_run_dir = '';
		protected $_switch_script_dir = '';
		protected $_switch_sounds_dir = '';
		protected $_switch_storage_dir = '';
		protected $_switch_temp_dir = '';
		public function switch_base_dir()			{ return $this->_switch_base_dir; }
		public function switch_cache_dir()			{ return $this->_switch_cache_dir; }
		public function switch_conf_dir()			{ return $this->_switch_conf_dir; }
		public function switch_db_dir()				{ return $this->_switch_db_dir; }
		public function switch_grammar_dir()		{ return $this->_switch_grammar_dir; }
		public function switch_htdocs_dir()			{ return $this->_switch_htdocs_dir; }
		public function switch_log_dir()			{ return $this->_switch_log_dir; }
		public function switch_mod_dir()			{ return $this->_switch_mod_dir; }
		public function switch_recordings_dir()		{ return $this->_switch_recordings_dir; }
		public function switch_run_dir()			{ return $this->_switch_run_dir; }
		public function switch_script_dir()			{ return $this->_switch_script_dir; }
		public function switch_sounds_dir()			{ return $this->_switch_sounds_dir; }
		public function switch_storage_dir()		{ return $this->_switch_storage_dir; }
		public function switch_temp_dir()			{ return $this->_switch_temp_dir; }

		// virtual dirs - assumed based on the detected dirs
		protected $_switch_voicemail_vdir = '';
		protected $_switch_phrases_vdir = '';
		protected $_switch_extensions_vdir = '';
		protected $_switch_sip_profiles_vdir = '';
		protected $_switch_dialplan_vdir = '';
		protected $_switch_backup_vdir = '';
		public function switch_voicemail_vdir()		{ return $this->_switch_voicemail_vdir; }
		public function switch_phrases_vdir()		{ return $this->_switch_phrases_vdir; }
		public function switch_extensions_vdir()	{ return $this->_switch_extensions_vdir; }
		public function switch_sip_profiles_vdir()	{ return $this->_switch_sip_profiles_vdir; }
		public function switch_dialplan_vdir()		{ return $this->_switch_dialplan_vdir; }
		public function switch_backup_vdir()		{ return $this->_switch_backup_vdir; }

		// event socket
		protected $_switch_event_host;
		protected $_switch_event_port;
		protected $_switch_event_password;
		public function switch_event_host()		{ return $this->_switch_event_host; }
		public function switch_event_port()		{ return $this->_switch_event_port; }
		public function switch_event_password()	{ return $this->_switch_event_password; }

		// database information
		protected $_db_type;
		protected $_db_path;
		protected $_db_host;
		protected $_db_port;
		protected $_db_name;
		protected $_db_username;
		protected $_db_password;
		protected $_db_create;
		protected $_db_create_username;
		protected $_db_create_password;
		public function db_type()	 			{ return $this->_db_type; }
		public function db_path()	 			{ return $this->_db_path; }
		public function db_host()	 			{ return $this->_db_host; }
		public function db_port()	 			{ return $this->_db_port; }
		public function db_name()	 			{ return $this->_db_name; }
		public function db_username()	 		{ return $this->_db_username; }
		public function db_password()	 		{ return $this->_db_password; }
		public function db_create()	 			{ return $this->_db_create; }
		public function db_create_username()	{ return $this->_db_create_username; }
		public function db_create_password()	{ return $this->_db_create_password; }

		//misc information
		protected $_domain_uuid;
		protected $_domain_name;
		protected $_domain_count;
		public function domain_uuid()	 		{ return $this->_domain_uuid; }
		public function domain_name()	 		{ return $this->_domain_name; }
		public function domain_count()	 		{ return $this->_domain_count; }
		public function set_domain_uuid($domain_uuid) {
			$e = new Exception();
			$trace = $e->getTrace();
			if($trace[1]['function'] != 'create_domain'){
				throw new Exception('Only create_domain is allowed to update the domain_uuid');
			}
			$this->_domain_uuid = $domain_uuid;
		}

		public function __construct($detect_switch = null, $domain_name = null, $domain_uuid = null) {
			$this->_switch_dirs = preg_grep ('/^switch_.*_dir$/', get_class_methods('global_settings') );
			sort( $this->_switch_dirs );
			$this->_switch_vdirs = preg_grep ('/^switch_.*_vdir$/', get_class_methods('global_settings') );
			sort( $this->_switch_vdirs );
			if(is_null($detect_switch)){
				//take settings from session
				foreach ($this->_switch_dirs as $dir){
					$category = 'switch';
					$session_var;
					preg_match( '/^switch_(.*)_dir$/', $dir, $session_var);
					$dir = "_$dir";
					if($session_var[1] == 'script'){ $session_var[1] = 'scripts'; }
					if($session_var[1] == 'temp'){ $category = 'server'; }
					$this->$dir = $_SESSION[$category][$session_var[1]]['dir'];
				}
				foreach ($this->_switch_vdirs as $vdir){
					$category = 'switch';
					$session_var;
					preg_match( '/^switch_(.*)_vdir$/', $vdir, $session_var);
					$vdir = "_$vdir";
					if($session_var[1] == 'backup'){ $category = 'server'; }
					$this->$vdir = $_SESSION[$category][$session_var[1]]['dir'];
				}
				$this->_switch_event_host		= $_SESSION['event_socket_ip_address'];
				$this->_switch_event_port		= $_SESSION['event_socket_port'];
				$this->_switch_event_password	= $_SESSION['event_socket_password'];

				// domain info
				$this->_domain_name = $_SESSION['domain_name'];
				$this->_domain_uuid = $_SESSION['domain_uuid'];

				// collect misc info
				$this->_domain_count = count($_SESSION["domains"]);

				// collect db_info
				global $db_type, $db_path, $db_host, $db_port, $db_name, $db_username, $db_password, $db_create;
				$this->_db_type = $db_type;
				$this->_db_path = $db_path;
				$this->_db_host = $db_host;
				$this->_db_port = $db_port;
				$this->_db_name = $db_name;
				$this->_db_username = $db_username;
				$this->_db_password = $db_password;

			}elseif(!is_a($detect_switch, 'detect_switch')){
				throw new Exception('The parameter $detect_switch must be a detect_switch object (or a subclass of)');

			}else{
				//copy from detect_switch
				foreach($detect_switch->get_dirs() as $dir){
					$t_dir = "_switch_$dir";
					$this->$t_dir = $detect_switch->$dir();
				}
				foreach($detect_switch->get_vdirs() as $vdir){
					$t_vdir = "_switch_$vdir";
					$this->$t_vdir = $detect_switch->$vdir();
				}
				$this->_switch_event_host		= $detect_switch->event_host;
				$this->_switch_event_port		= $detect_switch->event_port;
				$this->_switch_event_password	= $detect_switch->event_password;

				//copy from _POST
				foreach($_POST as $key=>$value){
					if(substr($key,0,3) == "db_"){
						$o_key = "_$key";
						$this->$o_key = $value;
					}
				}
				if($this->_db_create== 1) {
                                    if (strlen($this->_db_create_username)==0) $this->_db_create_username='root';
                                    if (strlen($this->_db_create_password)==0) $this->_db_password;
				}
				if (strlen($this->_db_port) == 0) { $this->_db_port = "5432"; }

				// domain info
				if(strlen($domain_uuid) == 0){ $domain_uuid = uuid(); }
				$this->_domain_name = $domain_name;
				$this->_domain_uuid = $domain_uuid;

				//collect misc info
				$this->_domain_count = 1;	//assumed to be one
			}
		}
	}
?>