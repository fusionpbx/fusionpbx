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
 Portions created by the Initial Developer are Copyright (C) 2008-2019
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
*/

//define the modules class
if (!class_exists('modules')) {
	class modules {

		/**
		 * declare public variables
		 */
		public $dir;
		public $fp;
		public $modules;
		public $msg;

		/**
		 * declare private variables
		 */
		private $app_name;
		private $app_uuid;
		private $permission_prefix;
		private $list_page;
		private $table;
		private $uuid_prefix;
		private $toggle_field;
		private $toggle_values;

		/**
		 * called when the object is created
		 */
		public function __construct() {

			//assign private variables
				$this->app_name = 'modules';
				$this->app_uuid = '5eb9cba1-8cb6-5d21-e36a-775475f16b5e';
				$this->permission_prefix = 'module_';
				$this->list_page = 'modules.php';
				$this->table = 'modules';
				$this->uuid_prefix = 'module_';
				$this->toggle_field = 'module_enabled';
				$this->toggle_values = ['true','false'];

		}

		/**
		 * called when there are no references to a particular object
		 * unset the variables used in the class
		 */
		public function __destruct() {
			foreach ($this as $key => $value) {
				unset($this->$key);
			}
		}

		//get the additional information about a specific module
			public function info($name) {
				$module_label = substr($name, 4);
				$module_label = ucwords(str_replace("_", " ", $module_label));
				$mod['module_label'] = $module_label;
				$mod['module_name'] = $name;
				$mod['module_order'] = '800';
				$mod['module_enabled'] = 'false';
				$mod['module_default_enabled'] = 'false';
				$mod['module_description'] = '';
				switch ($name) {
					case "mod_amr":
						$mod['module_label'] = 'AMR';
						$mod['module_category'] = 'Codecs';
						$mod['module_description'] = 'AMR codec.';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_avmd":
						$mod['module_label'] = 'AVMD';
						$mod['module_category'] = 'Applications';
						$mod['module_description'] = 'Advanced voicemail beep detection.';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_blacklist":
						$mod['module_label'] = 'Blacklist';
						$mod['module_category'] = 'Applications';
						$mod['module_description'] = 'Blacklist.';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_bv":
						$mod['module_label'] = 'BV';
						$mod['module_category'] = 'Codecs';
						$mod['module_description'] = 'BroadVoice16 and BroadVoice32 audio codecs.';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_cdr_csv":
						$mod['module_label'] = 'CDR CSV';
						$mod['module_category'] = 'Event Handlers';
						$mod['module_description'] = 'CSV call detail record handler.';
						$mod['module_enabled'] = 'false';
						$mod['module_default_enabled'] = 'false';
						break;
					case "mod_cdr_sqlite":
						$mod['module_label'] = 'CDR SQLite';
						$mod['module_category'] = 'Event Handlers';
						$mod['module_description'] = 'SQLite call detail record handler.';
						$mod['module_enabled'] = 'false';
						$mod['module_default_enabled'] = 'false';
						break;
					case "mod_callcenter":
						$mod['module_label'] = 'Call Center';
						$mod['module_category'] = 'Applications';
						$mod['module_description'] = 'Call queuing with agents and tiers for call centers.';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_cepstral":
						$mod['module_label'] = 'Cepstral';
						$mod['module_category'] = 'Speech Recognition / Text to Speech';
						$mod['module_description'] = 'Text to Speech engine.';
						$mod['module_enabled'] = 'false';
						$mod['module_default_enabled'] = 'false';
						break;
					case "mod_cidlookup":
						$mod['module_label'] = 'CID Lookup';
						$mod['module_category'] = 'Applications';
						$mod['module_description'] = 'Lookup caller id info.';
						$mod['module_enabled'] = 'false';
						$mod['module_default_enabled'] = 'false';
						break;
					case "mod_cluechoo":
						$mod['module_label'] = 'Cluechoo';
						$mod['module_category'] = 'Applications';
						$mod['module_description'] = 'A framework demo module.';
						$mod['module_enabled'] = 'false';
						$mod['module_default_enabled'] = 'false';
						break;
					case "mod_commands":
						$mod['module_label'] = 'Commands';
						$mod['module_category'] = 'Applications';
						$mod['module_description'] = 'API interface commands.';
						$mod['module_order'] = 100;
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_conference":
						$mod['module_label'] = 'Conference';
						$mod['module_category'] = 'Applications';
						$mod['module_description'] = 'Conference room module.';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_console":
						$mod['module_label'] = 'Console';
						$mod['module_category'] = 'Loggers';
						$mod['module_description'] = 'Send logs to the console.';
						$mod['module_order'] = 400;
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_curl":
						$mod['module_label'] = 'CURL';
						$mod['module_category'] = 'Applications';
						$mod['module_description'] = 'Allows scripts to make HTTP requests and return responses in plain text or JSON.';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_db":
						$mod['module_label'] = 'DB';
						$mod['module_category'] = 'Applications';
						$mod['module_description'] = 'Database key / value storage functionality, dialing and limit backend.';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_dialplan_asterisk":
						$mod['module_label'] = 'Dialplan Asterisk';
						$mod['module_category'] = 'Dialplan Interfaces';
						$mod['module_description'] = 'Allows Asterisk dialplans.';
						$mod['module_enabled'] = 'false';
						$mod['module_default_enabled'] = 'false';
						break;
					case "mod_dialplan_xml":
						$mod['module_label'] = 'Dialplan XML';
						$mod['module_category'] = 'Dialplan Interfaces';
						$mod['module_description'] = 'Provides dialplan functionality in XML.';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_directory":
						$mod['module_label'] = 'Directory';
						$mod['module_category'] = 'Applications';
						$mod['module_description'] = 'Dial by name directory.';
						$mod['module_enabled'] = 'false';
						$mod['module_default_enabled'] = 'false';
						break;
					case "mod_distributor":
						$mod['module_label'] = 'Distributor';
						$mod['module_category'] = 'Applications';
						$mod['module_description'] = 'Round robin call distribution.';
						$mod['module_enabled'] = 'false';
						$mod['module_default_enabled'] = 'false';
						break;
					case "mod_dptools":
						$mod['module_label'] = 'Dialplan Plan Tools';
						$mod['module_category'] = 'Applications';
						$mod['module_description'] = 'Provides a number of apps and utilities for the dialplan.';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_enum":
						$mod['module_label'] = 'ENUM';
						$mod['module_category'] = 'Applications';
						$mod['module_description'] = 'Route PSTN numbers over internet according to ENUM servers, such as e164.org.';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_esf":
						$mod['module_label'] = 'ESF';
						$mod['module_category'] = 'Applications';
						$mod['module_description'] = 'Holds the multi cast paging application for SIP.';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_event_socket":
						$mod['module_label'] = 'Event Socket';
						$mod['module_category'] = 'Event Handlers';
						$mod['module_description'] = 'Sends events via a single socket.';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_expr":
						$mod['module_label'] = 'Expr';
						$mod['module_category'] = 'Applications';
						$mod['module_description'] = 'Expression evaluation library.';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_fifo":
						$mod['module_label'] = 'FIFO';
						$mod['module_category'] = 'Applications';
						$mod['module_description'] = 'FIFO provides custom call queues including call park.';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_flite":
						$mod['module_label'] = 'Flite';
						$mod['module_category'] = 'Speech Recognition / Text to Speech';
						$mod['module_description'] = 'Text to Speech engine.';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_fsv":
						$mod['module_label'] = 'FSV';
						$mod['module_category'] = 'Applications';
						$mod['module_description'] = 'Video application (Recording and playback).';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_g723_1":
						$mod['module_label'] = 'G.723.1';
						$mod['module_category'] = 'Codecs';
						$mod['module_description'] = 'G.723.1 codec.';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_g729":
						$mod['module_label'] = 'G.729';
						$mod['module_category'] = 'Codecs';
						$mod['module_description'] = 'G729 codec supports passthrough mode';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_h26x":
						$mod['module_label'] = 'H26x';
						$mod['module_category'] = 'Codecs';
						$mod['module_description'] = 'Video codecs';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_hash":
						$mod['module_label'] = 'Hash';
						$mod['module_category'] = 'Applications';
						$mod['module_description'] = 'Resource limitation.';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_httapi":
						$mod['module_label'] = 'HT-TAPI';
						$mod['module_category'] = 'Applications';
						$mod['module_description'] = 'HT-TAPI Hypertext Telephony API';
						$mod['module_enabled'] = 'false';
						$mod['module_default_enabled'] = 'false';
						break;
					case "mod_http_cache":
						$mod['module_label'] = 'HTTP Cache';
						$mod['module_category'] = 'Applications';
						$mod['module_description'] = 'HTTP GET with caching';
						$mod['module_enabled'] = 'false';
						$mod['module_default_enabled'] = 'false';
						break;
					case "mod_ilbc":
						$mod['module_label'] = 'iLBC';
						$mod['module_category'] = 'Codecs';
						$mod['module_description'] = 'iLBC codec.';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_ladspa":
						$mod['module_label'] = 'Ladspa';
						$mod['module_category'] = 'Applications';
						$mod['module_description'] = 'Auto-tune calls.';
						$mod['module_enabled'] = 'false';
						$mod['module_default_enabled'] = 'false';
						break;
					case "mod_lcr":
						$mod['module_label'] = 'LCR';
						$mod['module_category'] = 'Applications';
						$mod['module_description'] = 'Least cost routing.';
						$mod['module_enabled'] = 'false';
						$mod['module_default_enabled'] = 'false';
						break;
					case "mod_local_stream":
						$mod['module_label'] = 'Local Stream';
						$mod['module_category'] = 'Streams / Files';
						$mod['module_description'] = 'For local streams (play all the files in a directory).';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_logfile":
						$mod['module_label'] = 'Log File';
						$mod['module_category'] = 'Loggers';
						$mod['module_description'] = 'Send logs to the local file system.';
						$mod['module_order'] = 400;
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_loopback":
						$mod['module_label'] = 'Loopback';
						$mod['module_category'] = 'Endpoints';
						$mod['module_description'] = 'A loopback channel driver to make an outbound call as an inbound call.';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_lua":
						$mod['module_label'] = 'Lua';
						$mod['module_category'] = 'Languages';
						$mod['module_description'] = 'Lua script.';
						$mod['module_order'] = 200;
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_memcache":
						$mod['module_label'] = 'Memcached';
						$mod['module_category'] = 'Applications';
						$mod['module_description'] = 'API for memcached.';
						$mod['module_enabled'] = 'true';
						$mod['module_order'] = 150;
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_native_file":
						$mod['module_label'] = 'Native File';
						$mod['module_category'] = 'File Format Interfaces';
						$mod['module_description'] = 'File interface for codec specific file formats.';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_nibblebill":
						$mod['module_label'] = 'Nibblebill';
						$mod['module_category'] = 'Applications';
						$mod['module_description'] = 'Billing module.';
						$mod['module_enabled'] = 'false';
						$mod['module_default_enabled'] = 'false';
						break;
					case "mod_opus":
						$mod['module_label'] = 'Opus';
						$mod['module_category'] = 'Codecs';
						$mod['module_description'] = 'OPUS ultra-low delay audio codec';
						$mod['module_enabled'] = 'false';
						$mod['module_default_enabled'] = 'false';
						break;
					case "mod_park":
						$mod['module_label'] = 'Park';
						$mod['module_category'] = 'Applications';
						$mod['module_description'] = 'Park Calls.';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_pocketsphinx":
						$mod['module_label'] = 'PocketSphinx';
						$mod['module_category'] = 'Speech Recognition / Text to Speech';
						$mod['module_description'] = 'Speech Recognition.';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_rtmp":
						$mod['module_label'] = 'RTMP';
						$mod['module_category'] = 'Applications';
						$mod['module_description'] = 'Real Time Media Protocol';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_say_de":
						$mod['module_label'] = 'German';
						$mod['module_category'] = 'Say';
						$mod['module_description'] = '';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_say_en":
						$mod['module_label'] = 'English';
						$mod['module_category'] = 'Say';
						$mod['module_description'] = '';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_say_es":
						$mod['module_label'] = 'Spanish';
						$mod['module_category'] = 'Say';
						$mod['module_description'] = '';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_say_fr":
						$mod['module_label'] = 'French';
						$mod['module_category'] = 'Say';
						$mod['module_description'] = '';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_say_he":
						$mod['module_label'] = 'Hebrew';
						$mod['module_category'] = 'Say';
						$mod['module_description'] = '';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_say_hu":
						$mod['module_label'] = 'Hungarian';
						$mod['module_category'] = 'Say';
						$mod['module_description'] = '';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_say_it":
						$mod['module_label'] = 'Italian';
						$mod['module_category'] = 'Say';
						$mod['module_description'] = '';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_say_nl":
						$mod['module_label'] = 'Dutch';
						$mod['module_category'] = 'Say';
						$mod['module_description'] = '';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_say_pt":
						$mod['module_label'] = 'Portuguese';
						$mod['module_category'] = 'Say';
						$mod['module_description'] = '';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_say_ru":
						$mod['module_label'] = 'Russian';
						$mod['module_category'] = 'Say';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_say_th":
						$mod['module_label'] = 'Thai';
						$mod['module_category'] = 'Say';
						$mod['module_description'] = '';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_say_zh":
						$mod['module_label'] = 'Chinese';
						$mod['module_category'] = 'Say';
						$mod['module_description'] = '';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_shout":
						$mod['module_label'] = 'Shout';
						$mod['module_category'] = 'Streams / Files';
						$mod['module_description'] = 'MP3 files and shoutcast streams.';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_siren":
						$mod['module_label'] = 'Siren';
						$mod['module_category'] = 'Codecs';
						$mod['module_description'] = 'Siren codec';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_sms":
						$mod['module_label'] = 'SMS';
						$mod['module_category'] = 'Applications';
						$mod['module_description'] = 'Chat messages';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_sndfile":
						$mod['module_label'] = 'Sound File';
						$mod['module_category'] = 'File Format Interfaces';
						$mod['module_description'] = 'Multi-format file format transcoder (WAV, etc).';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_sofia":
						$mod['module_label'] = 'Sofia';
						$mod['module_category'] = 'Endpoints';
						$mod['module_description'] = 'SIP module.';
						$mod['module_order'] = 300;
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_spandsp":
						$mod['module_label'] = 'SpanDSP';
						$mod['module_category'] = 'Applications';
						$mod['module_description'] = 'FAX provides fax send and receive.';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_speex":
						$mod['module_label'] = 'Speex';
						$mod['module_category'] = 'Codecs';
						$mod['module_description'] = 'Speex codec.';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_spidermonkey":
						$mod['module_label'] = 'SpiderMonkey';
						$mod['module_category'] = 'Languages';
						$mod['module_description'] = 'JavaScript support.';
						$mod['module_enabled'] = 'false';
						$mod['module_default_enabled'] = 'false';
						break;
					case "mod_spidermonkey_core_db":
						$mod['module_label'] = 'SpiderMonkey Core DB';
						$mod['module_category'] = 'Languages';
						$mod['module_description'] = 'Javascript support for SQLite.';
						$mod['module_enabled'] = 'false';
						$mod['module_default_enabled'] = 'false';
						break;
					case "mod_spidermonkey_curl":
						$mod['module_label'] = 'SpiderMonkey Curl';
						$mod['module_category'] = 'Languages';
						$mod['module_description'] = 'Javascript curl support.';
						$mod['module_enabled'] = 'false';
						$mod['module_default_enabled'] = 'false';
						break;
					case "mod_spidermonkey_socket":
						$mod['module_label'] = 'SpiderMonkey Socket';
						$mod['module_category'] = 'Languages';
						$mod['module_description'] = 'Javascript socket support.';
						$mod['module_enabled'] = 'false';
						$mod['module_default_enabled'] = 'false';
						break;
					case "mod_spidermonkey_teletone":
						$mod['module_label'] = 'SpiderMonkey Teletone';
						$mod['module_category'] = 'Languages';
						$mod['module_description'] = 'Javascript teletone support.';
						$mod['module_enabled'] = 'false';
						$mod['module_default_enabled'] = 'false';
						break;
					case "mod_syslog":
						$mod['module_label'] = 'Syslog';
						$mod['module_category'] = 'Loggers';
						$mod['module_description'] = 'Send logs to a remote syslog server.';
						$mod['module_order'] = 400;
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_tone_stream":
						$mod['module_label'] = 'Tone Stream';
						$mod['module_category'] = 'Streams / Files';
						$mod['module_description'] = 'Generate tone streams.';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_tts_commandline":
						$mod['module_label'] = 'TTS Commandline';
						$mod['module_category'] = 'Speech Recognition / Text to Speech';
						$mod['module_description'] = 'Commandline text to speech engine.';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_unimrcp":
						$mod['module_label'] = 'MRCP';
						$mod['module_category'] = 'Speech Recognition / Text to Speech';
						$mod['module_description'] = 'Media Resource Control Protocol.';
						$mod['module_enabled'] = 'false';
						$mod['module_default_enabled'] = 'false';
						break;
					case "mod_valet_parking":
						$mod['module_label'] = 'Valet Parking';
						$mod['module_category'] = 'Applications';
						$mod['module_description'] = 'Call parking';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_voicemail":
						$mod['module_label'] = 'Voicemail';
						$mod['module_category'] = 'Applications';
						$mod['module_description'] = 'Full featured voicemail module.';
						$mod['module_enabled'] = 'false';
						$mod['module_default_enabled'] = 'false';
						break;
					case "mod_voicemail_ivr":
						$mod['module_label'] = 'Voicemail IVR';
						$mod['module_category'] = 'Applications';
						$mod['module_description'] = 'Voicemail IVR interface.';
						$mod['module_enabled'] = 'false';
						$mod['module_default_enabled'] = 'false';
						break;
					case "mod_translate":
						$mod['module_label'] = 'Translate';
						$mod['module_category'] = 'Applications';
						$mod['module_description'] = 'format numbers into a specified format.';
						$mod['module_enabled'] = 'false';
						$mod['module_default_enabled'] = 'false';
						break;
					case "mod_xml_cdr":
						$mod['module_label'] = 'XML CDR';
						$mod['module_category'] = 'XML Interfaces';
						$mod['module_description'] = 'XML based call detail record handler.';
						$mod['module_enabled'] = 'true';
						$mod['module_default_enabled'] = 'true';
						break;
					case "mod_xml_curl":
						$mod['module_label'] = 'XML Curl';
						$mod['module_category'] = 'XML Interfaces';
						$mod['module_description'] = 'Request XML config files dynamically.';
						$mod['module_enabled'] = 'false';
						$mod['module_default_enabled'] = 'false';
						break;
					case "mod_xml_rpc":
						$mod['module_label'] = 'XML RPC';
						$mod['module_category'] = 'XML Interfaces';
						$mod['module_description'] = 'XML Remote Procedure Calls. Issue commands from your web application.';
						$mod['module_enabled'] = 'false';
						$mod['module_default_enabled'] = 'false';
						break;
					default:
						$mod['module_category'] = 'Auto';
						$mod['module_enabled'] = 'false';
						$mod['module_default_enabled'] = 'false';
				}
				return $mod;
			}

		//check to see if the module exists in the array
			public function exists($name) {
				//set the default
					$result = false;
				//look for the module
					foreach ($this->modules as &$row) {
						if ($row['module_name'] == $name) {
							$result = true;
							break;
						}
					}
				//return the result
					return $result;
			}

		//check the status of the module
			public function active($name) {
				if (!$this->fp) {
					$this->fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
				}
				if ($this->fp) {
					$cmd = "api module_exists ".$name;
					$response = trim(event_socket_request($this->fp, $cmd));
					return $response == "true" ? true : false;
				}
				else {
					return false;
				}
			}

		//get the list of modules
			public function get_modules() {
				$sql = " select * from v_modules ";
				$sql .= "order by module_category,  module_label";
				$database = new database;
				$this->modules = $database->select($sql, null, 'all');
				unset($sql);
			}

		//add missing modules for more module info see http://wiki.freeswitch.com/wiki/Modules
			public function synch() {
				if ($handle = opendir($this->dir)) {
					$modules_new = '';
					$module_found = false;
					$x = 0;
					while (false !== ($file = readdir($handle))) {
						if ($file != "." && $file != "..") {
							if (substr($file, -3) == ".so" || substr($file, -4) == ".dll") {
								if (substr($file, -3) == ".so") {
									$name = substr($file, 0, -3);
								}
								if (substr($file, -4) == ".dll") {
									$name = substr($file, 0, -4);
								}
								if (!$this->exists($name)) {
									//set module found to true
										$module_found = true;
									//get the module array
										$mod = $this->info($name);
									//append the module label
										$modules_new .= "<li>".$mod['module_label']."</li>\n";
									//set the order
										$order = $mod['module_order'];
									//build insert array
										$array['modules'][$x]['module_uuid'] = uuid();
										$array['modules'][$x]['module_label'] = $mod['module_label'];
										$array['modules'][$x]['module_name'] = $mod['module_name'];
										$array['modules'][$x]['module_description'] = $mod['module_description'];
										$array['modules'][$x]['module_category'] = $mod['module_category'];
										$array['modules'][$x]['module_order'] = $order;
										$array['modules'][$x]['module_enabled'] = $mod['module_enabled'];
										$array['modules'][$x]['module_default_enabled'] = $mod['module_default_enabled'];
									$x++;
								}
							}
						}
					}
					if (is_array($array) && @sizeof($array) != 0) {
						//grant temporary permissions
							$p = new permissions;
							$p->add('module_add', 'temp');
						//execute insert
							$database = new database;
							$database->app_name = 'modules';
							$database->app_uuid = '5eb9cba1-8cb6-5d21-e36a-775475f16b5e';
							$database->save($array);
							unset($array);
						//revoke temporary permissions
							$p->delete('module_add', 'temp');
					}
					closedir($handle);
					if ($module_found) {
						$msg = "<strong>Added New Modules:</strong><br />\n";
						$msg .= "<ul>\n";
						$msg .= $modules_new;
						$msg .= "</ul>\n";
						$this->msg = $msg;
					}
				}
			}

		//save the modules.conf.xml file
			function xml() {
				//set the globals
					global $config, $domain_uuid;

				//compose xml
					$xml = "<configuration name=\"modules.conf\" description=\"Modules\">\n";
					$xml .= "	<modules>\n";

					$sql = "select * from v_modules ";
					$sql .= "order by module_order asc, ";
					$sql .= "module_category asc ";
					$database = new database;
					$result = $database->select($sql, null, 'all');
					unset($sql);

					$prev_module_cat = '';
					if (is_array($result) && @sizeof($result) != 0) {
						foreach ($result as $row) {
							if ($prev_module_cat != $row['module_category']) {
								$xml .= "\n		<!-- ".$row['module_category']." -->\n";
							}
							if ($row['module_enabled'] == "true"){
								$xml .= "		<load module=\"".$row['module_name']."\"/>\n";
							}
							$prev_module_cat = $row['module_category'];
						}
					}
					unset($result, $row);

					$xml .= "\n";
					$xml .= "	</modules>\n";
					$xml .= "</configuration>";

					$fout = fopen($_SESSION['switch']['conf']['dir']."/autoload_configs/modules.conf.xml","w");
					fwrite($fout, $xml);
					unset($xml);
					fclose($fout);

				//apply settings
					$_SESSION["reload_xml"] = true;
			}

		/**
		 * start modules
		 */
		public function start($records) {
			$this->control('start', $records);
		}

		/**
		 * stop modules
		 */
		public function stop($records) {
			$this->control('stop', $records);
		}

		/**
		 * control (load/unload) modules
		 */
		private function control($action, $records) {
			if (permission_exists($this->permission_prefix.'edit')) {

				//set local variables
					switch ($action) {
						case 'start':
							$action = 'load';
							$response_message = 'message-module_started';
							break;
						case 'stop':
							$action = 'unload';
							$response_message = 'message-module_stopped';
							break;
						default:
							exit;
					}

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->list_page);
						exit;
					}

				//control the checked modules
					if (is_array($records) && @sizeof($records) != 0) {

						//filter out unchecked modules, build where clause for below
							foreach($records as $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}

						//get module details
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->uuid_prefix."uuid as uuid, module_name as module, module_enabled as enabled from v_".$this->table." ";
								$sql .= "where ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										$modules[$row['uuid']]['name'] = $row['module'];
										$modules[$row['uuid']]['enabled'] = $row['enabled'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						if (is_array($modules) && @sizeof($modules) != 0) {
							//create the event socket connection
								$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);

							if ($fp) {
								//control modules
									foreach ($modules as $module_uuid => $module) {
										if ($module['enabled'] == 'true') {
											$cmd = 'api '.$action.' '.$module['name'];
											$responses[$module_uuid]['module'] = $module['name'];
											$responses[$module_uuid]['message'] = trim(event_socket_request($fp, $cmd));
										}
										else {
											$responses[$module_uuid]['module'] = $module['name'];
											$responses[$module_uuid]['message'] = $text['label-disabled'];
										}
									}

								//set message
									$message = $text[$response_message];
									if (is_array($responses) && @sizeof($responses) != 0) {
										foreach ($responses as $response) {
											$message .= "<br><strong>".$response['module']."</strong>: ".$response['message'];
										}
									}
									message::add($message, 'positive', 7000);
							}
						}
					}

			}
		}

		/**
		 * delete modules
		 */
		public function delete($records) {
			if (permission_exists($this->permission_prefix.'delete')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->list_page);
						exit;
					}

				//delete the checked modules
					if (is_array($records) && @sizeof($records) != 0) {

						//filter out unchecked modules, build where clause for below
							foreach($records as $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}

						//get module details
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->uuid_prefix."uuid as uuid, module_name as module from v_".$this->table." ";
								$sql .= "where ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										$modules[$row['uuid']]['name'] = $row['module'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//build the delete array
							if (is_array($modules) && @sizeof($modules) != 0) {
								$x = 0;
								foreach ($modules as $module_uuid => $module) {
									$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $module_uuid;
									$x++;
								}
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {

								//create the event socket connection
									$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);

								//stop modules
									if ($fp) {
										foreach ($modules as $module_uuid => $module) {
											if ($this->active($module['name'])) {
												$cmd = 'api unload '.$module['name'];
												$responses[$module_uuid]['module'] = $module['name'];
												$responses[$module_uuid]['message'] = trim(event_socket_request($fp, $cmd));
											}
										}
									}

								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
									unset($array);

								//rewrite mod
									$this->xml();

								//set message
									$message = $text['message-delete'];
									if (is_array($responses) && @sizeof($responses) != 0) {
										foreach ($responses as $response) {
											$message .= "<br><strong>".$response['module']."</strong>: ".$response['message'];
										}
									}
									message::add($message, 'positive', 7000);

							}
					}

			}
		}

		/**
		 * toggle records
		 */
		public function toggle($records) {
			if (permission_exists($this->permission_prefix.'edit')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->list_page);
						exit;
					}

				//toggle the checked records
					if (is_array($records) && @sizeof($records) != 0) {

						//get current toggle state
							foreach($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->uuid_prefix."uuid as uuid, ".$this->toggle_field." as state, module_name as name from v_".$this->table." ";
								$sql .= "where ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										$modules[$row['uuid']]['state'] = $row['state'];
										$modules[$row['uuid']]['name'] = $row['name'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//build update array
							$x = 0;
							if (is_array($modules) && @sizeof($modules) != 0) {
								foreach($modules as $uuid => $module) {
									$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $uuid;
									$array[$this->table][$x][$this->toggle_field] = $module['state'] == $this->toggle_values[0] ? $this->toggle_values[1] : $this->toggle_values[0];
									$x++;
								}
							}

						//save the changes
							if (is_array($array) && @sizeof($array) != 0) {

								//save the array
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->save($array);
									unset($array);

								//create the event socket connection
									$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);

								//stop modules if active
									if ($fp) {
										foreach ($modules as $module_uuid => $module) {
											if ($this->active($module['name'])) {
												$cmd = 'api unload '.$module['name'];
												$responses[$module_uuid]['module'] = $module['name'];
												$responses[$module_uuid]['message'] = trim(event_socket_request($fp, $cmd));
											}
										}
									}

								//rewrite mod
									$this->xml();

								//set message
									$message = $text['message-toggle'];
									if (is_array($responses) && @sizeof($responses) != 0) {
										foreach ($responses as $response) {
											$message .= "<br><strong>".$response['module']."</strong>: ".$response['message'];
										}
									}
									message::add($message, 'positive', 7000);

							}
							unset($records, $modules);
					}

			}
		} //method


	} //class
}

/*
require_once "resources/classes/modules.php";
$mod = new modules;
$mod->dir = $_SESSION['switch']['mod']['dir'];
echo $mod->dir."\n";
//get modules from the database
	$mod->get_modules();
//module exists
	if ($mod->exists("mod_lua")) {
		echo "exists true\n";
	}
	else {
		echo "exists false\n";
	}
//module active
	if ($mod->active("mod_lua")) {
		echo "active true\n";
	}
	else {
		echo "active false\n";
	}
//synch
	$mod->synch();
	echo $mod->msg;
//show module info
	$result = $mod->info("mod_lua");
	echo "<pre>\n";
	print_r($result);
	echo "</pre>\n";
//list modules
	//$result = $mod->modules
	//echo "<pre>\n";
	//print_r($result);
	//echo "</pre>\n";
*/

?>
