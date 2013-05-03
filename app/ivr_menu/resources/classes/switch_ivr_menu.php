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
	Copyright (C) 2010
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "includes/classes/switch_dialplan.php";

//define the directory class
	class switch_ivr_menu {
		public $db;
		public $domain_uuid;
		public $domain_name;
		public $dialplan_uuid;
		public $ivr_menu_uuid;
		public $ivr_menu_name;
		public $ivr_menu_extension;
		public $ivr_menu_greet_long;
		public $ivr_menu_greet_short;
		public $ivr_menu_invalid_sound;
		public $ivr_menu_exit_sound;
		public $ivr_menu_confirm_macro;
		public $ivr_menu_confirm_key;
		public $ivr_menu_tts_engine;
		public $ivr_menu_tts_voice;
		public $ivr_menu_confirm_attempts;
		public $ivr_menu_timeout;
		public $ivr_menu_exit_app;
		public $ivr_menu_exit_data;
		public $ivr_menu_inter_digit_timeout;
		public $ivr_menu_max_failures;
		public $ivr_menu_max_timeouts;
		public $ivr_menu_digit_len;
		public $ivr_menu_direct_dial;
		public $ivr_menu_ringback;
		public $ivr_menu_cid_prefix;
		public $ivr_menu_enabled;
		public $ivr_menu_description;
		public $ivr_menu_option_uuid;
		public $ivr_menu_option_digits;
		public $ivr_menu_option_action;
		public $ivr_menu_option_param;
		public $ivr_menu_option_order;
		public $ivr_menu_option_description;
		public $order_by; //array

		public function __construct() {
			require_once "includes/classes/database.php";
			$this->app_uuid = 'a5788e9b-58bc-bd1b-df59-fff5d51253ab';
		}

		public function __destruct() {
			foreach ($this as $key => $value) {
				unset($this->$key);
			}
		}

		public function get_domain_uuid() {
			return $this->domain_uuid;
		}
		public function set_domain_uuid($domain_uuid){
			$this->domain_uuid = $domain_uuid;
		}

		public function get_fields($table) {
			//get the $apps array from the installed apps from the core and mod directories
				$config_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/*/*/app_config.php");
				$x=0;
				foreach ($config_list as &$config_path) {
					include($config_path);
					$x++;
				}

			//update the app db array add exists true or false
				$sql = '';
				foreach ($apps as $x => &$app) {
					foreach ($app['db'] as $y => &$row) {
						if ($row['table'] == $table) {
							//check if the column exists
								foreach ($row['fields'] as $z => $field) {
									if ($field['deprecated'] == "true") {
										//skip this field
									}
									else {
										if (is_array($field['name'])) {
											$field_name = $field['name']['text'];
										}
										else {
											$field_name = $field['name'];
										}
										if (strlen(field_name) > 0) {
											$fields[$z]['name'] = $field_name;
										}
										unset($field_name);
									}
								}
						}
					}
				}
			return $fields;
		}

		public function count() {
			$database = new database;
			if ($this->db) {
				$database->db = $this->db;
			}
			$database->domain_uuid = $this->domain_uuid;
			$database->table = "v_ivr_menus";
			$database->where[0]['name'] = 'domain_uuid';
			$database->where[0]['value'] = $this->domain_uuid;
			$database->where[0]['operator'] = '=';
			return $database->count();
		}

		public function find() {
			$database = new database;
			if ($this->db) {
				$database->db = $this->db;
			}
			$database->table = "v_ivr_menus";
			$database->where[0]['name'] = 'domain_uuid';
			$database->where[0]['value'] = $this->domain_uuid;
			$database->where[0]['operator'] = '=';
			if (isset($this->ivr_menu_uuid)) {
				$database->where[1]['name'] = 'ivr_menu_uuid';
				$database->where[1]['value'] = $this->ivr_menu_uuid;
				$database->where[1]['operator'] = '=';
			}
			if (isset($this->ivr_menu_option_uuid)) {
				$database->where[2]['name'] = 'ivr_menu_uuid';
				$database->where[2]['value'] = $this->ivr_menu_uuid;
				$database->where[2]['operator'] = '=';
			}
			if (isset($this->order_by)) {
				$database->order_by = $this->order_by;
			}
			return $database->find();
		}

		public function add() {

			//add the ivr menu
				if (strlen($this->ivr_menu_option_action) == 0) {
					if (strlen($this->ivr_menu_extension) > 0) {
						//set the ivr menu uuid
							$this->ivr_menu_uuid = uuid();

						//ensure the dialplan_uuid has a uuid
							if (strlen($this->dialplan_uuid) == 0) {
								$this->dialplan_uuid = uuid();
							}

						//add the dialplan
							$database = new database;
							if ($this->db) {
								$database->db = $this->db;
							}
							$database->table = "v_dialplans";
							$database->fields['domain_uuid'] = $this->domain_uuid;
							$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
							$database->fields['dialplan_name'] = $this->ivr_menu_name;
							$database->fields['dialplan_order'] = '333';
							$database->fields['dialplan_context'] = $_SESSION['context'];
							$database->fields['dialplan_enabled'] = $this->ivr_menu_enabled;
							$database->fields['dialplan_description'] = $this->ivr_menu_description;
							$database->fields['app_uuid'] = $this->app_uuid;
							$database->add();

						//add the dialplan details
							$detail_data = '^'.$this->ivr_menu_extension.'$';
							$database->table = "v_dialplan_details";
							$database->fields['domain_uuid'] = $this->domain_uuid;
							$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
							$database->fields['dialplan_detail_uuid'] = uuid();
							$database->fields['dialplan_detail_tag'] = 'condition'; //condition, action, antiaction
							$database->fields['dialplan_detail_type'] = 'destination_number';
							$database->fields['dialplan_detail_data'] = $detail_data;
							$database->fields['dialplan_detail_order'] = '005';
							$database->add();

							$database->table = "v_dialplan_details";
							$database->fields['domain_uuid'] = $this->domain_uuid;
							$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
							$database->fields['dialplan_detail_uuid'] = uuid();
							$database->fields['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
							$database->fields['dialplan_detail_type'] = 'answer';
							$database->fields['dialplan_detail_data'] = '';
							$database->fields['dialplan_detail_order'] = '010';
							$database->add();

							$database->table = "v_dialplan_details";
							$database->fields['domain_uuid'] = $this->domain_uuid;
							$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
							$database->fields['dialplan_detail_uuid'] = uuid();
							$database->fields['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
							$database->fields['dialplan_detail_type'] = 'sleep';
							$database->fields['dialplan_detail_data'] = '1000';
							$database->fields['dialplan_detail_order'] = '015';
							$database->add();

							$database->table = "v_dialplan_details";
							$database->fields['domain_uuid'] = $this->domain_uuid;
							$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
							$database->fields['dialplan_detail_uuid'] = uuid();
							$database->fields['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
							$database->fields['dialplan_detail_type'] = 'set';
							$database->fields['dialplan_detail_data'] = 'hangup_after_bridge=true';
							$database->fields['dialplan_detail_order'] = '020';
							$database->add();

							$database->table = "v_dialplan_details";
							$database->fields['domain_uuid'] = $this->domain_uuid;
							$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
							$database->fields['dialplan_detail_uuid'] = uuid();
							$database->fields['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
							$database->fields['dialplan_detail_type'] = 'set';
							if ($this->ivr_menu_ringback == "music" || $this->ivr_menu_ringback == "") {
								$database->fields['dialplan_detail_data'] = 'ringback=${hold_music}';
							}
							else {
								$database->fields['dialplan_detail_data'] = 'ringback='.$this->ivr_menu_ringback;
							}
							$database->fields['dialplan_detail_order'] = '025';
							$database->add();

							$database->table = "v_dialplan_details";
							$database->fields['domain_uuid'] = $this->domain_uuid;
							$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
							$database->fields['dialplan_detail_uuid'] = uuid();
							$database->fields['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
							$database->fields['dialplan_detail_type'] = 'set';
							if ($this->ivr_menu_ringback == "music" || $this->ivr_menu_ringback == "") {
								$database->fields['dialplan_detail_data'] = 'transfer_ringback=${hold_music}';
							}
							else {
								$database->fields['dialplan_detail_data'] = 'transfer_ringback='.$this->ivr_menu_ringback;
							}
							$database->fields['dialplan_detail_order'] = '030';
							$database->add();

							$database->table = "v_dialplan_details";
							$database->fields['domain_uuid'] = $this->domain_uuid;
							$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
							$database->fields['dialplan_detail_uuid'] = uuid();
							$database->fields['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
							$database->fields['dialplan_detail_type'] = 'set';
							$database->fields['dialplan_detail_data'] = 'ivr_menu_uuid='.$this->ivr_menu_uuid;
							$database->fields['dialplan_detail_order'] = '035';
							$database->add();

							$database->table = "v_dialplan_details";
							$database->fields['domain_uuid'] = $this->domain_uuid;
							$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
							$database->fields['dialplan_detail_uuid'] = uuid();
							$database->fields['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
							$database->fields['dialplan_detail_type'] = 'lua';
							$database->fields['dialplan_detail_data'] = 'ivr_menu.lua';
							$database->fields['dialplan_detail_order'] = '040';
							$database->add();

							if (strlen($this->ivr_menu_exit_app) > 0) {
								$database->table = "v_dialplan_details";
								$database->fields['domain_uuid'] = $this->domain_uuid;
								$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
								$database->fields['dialplan_detail_uuid'] = uuid();
								$database->fields['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
								$database->fields['dialplan_detail_type'] = $this->ivr_menu_exit_app;
								$database->fields['dialplan_detail_data'] = $this->ivr_menu_exit_data;
								$database->fields['dialplan_detail_order'] = '045';
								$database->add();
							}
						}

					//add the ivr menu
						$database = new database;
						if ($this->db) {
							$database->db = $this->db;
						}
						$database->table = "v_ivr_menus";
						$database->fields['domain_uuid'] = $this->domain_uuid;
						if (strlen($this->ivr_menu_extension) > 0) {
							$database->fields['ivr_menu_extension'] = $this->ivr_menu_extension;
							$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
						}
						$database->fields['ivr_menu_uuid'] = $this->ivr_menu_uuid;
						$database->fields['ivr_menu_name'] = $this->ivr_menu_name;
						$database->fields['ivr_menu_greet_long'] = $this->ivr_menu_greet_long;
						$database->fields['ivr_menu_greet_short'] = $this->ivr_menu_greet_short;
						$database->fields['ivr_menu_invalid_sound'] = $this->ivr_menu_invalid_sound;
						$database->fields['ivr_menu_exit_sound'] = $this->ivr_menu_exit_sound;
						$database->fields['ivr_menu_confirm_macro'] = $this->ivr_menu_confirm_macro;
						$database->fields['ivr_menu_confirm_key'] = $this->ivr_menu_confirm_key;
						$database->fields['ivr_menu_tts_engine'] = $this->ivr_menu_tts_engine;
						$database->fields['ivr_menu_tts_voice'] = $this->ivr_menu_tts_voice;
						$database->fields['ivr_menu_confirm_attempts'] = $this->ivr_menu_confirm_attempts;
						$database->fields['ivr_menu_timeout'] = $this->ivr_menu_timeout;
						$database->fields['ivr_menu_exit_app'] = $this->ivr_menu_exit_app;
						$database->fields['ivr_menu_exit_data'] = $this->ivr_menu_exit_data;
						$database->fields['ivr_menu_inter_digit_timeout'] = $this->ivr_menu_inter_digit_timeout;
						$database->fields['ivr_menu_max_failures'] = $this->ivr_menu_max_failures;
						$database->fields['ivr_menu_max_timeouts'] = $this->ivr_menu_max_timeouts;
						$database->fields['ivr_menu_max_timeouts'] = $this->ivr_menu_max_timeouts;
						$database->fields['ivr_menu_digit_len'] = $this->ivr_menu_digit_len;
						$database->fields['ivr_menu_digit_len'] = $this->ivr_menu_digit_len;
						$database->fields['ivr_menu_direct_dial'] = $this->ivr_menu_direct_dial;
						$database->fields['ivr_menu_ringback'] = $this->ivr_menu_ringback;
						$database->fields['ivr_menu_cid_prefix'] = $this->ivr_menu_cid_prefix;
						$database->fields['ivr_menu_enabled'] = $this->ivr_menu_enabled;
						$database->fields['ivr_menu_description'] = $this->ivr_menu_description;
						$database->add();
				}

			//add the ivr menu option
				if (strlen($this->ivr_menu_option_action) > 0) {
					$database = new database;
					if ($this->db) {
						$database->db = $this->db;
					}
					$database->table = "v_ivr_menu_options";
					$database->fields['domain_uuid'] = $this->domain_uuid;
					$database->fields['ivr_menu_uuid'] = $this->ivr_menu_uuid;
					$database->fields['ivr_menu_option_uuid'] = $this->ivr_menu_option_uuid;
					$database->fields['ivr_menu_option_digits'] = $this->ivr_menu_option_digits;
					$database->fields['ivr_menu_option_action'] = $this->ivr_menu_option_action;
					$database->fields['ivr_menu_option_param'] = $this->ivr_menu_option_param;
					$database->fields['ivr_menu_option_order'] = $this->ivr_menu_option_order;
					$database->fields['ivr_menu_option_description'] = $this->ivr_menu_option_description;
					$database->add();
				}

			//delete the dialplan context from memcache
				$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
				if ($fp) {
					$switch_cmd .= "memcache delete dialplan:".$_SESSION["context"]."@".$_SESSION['domain_name'];
					$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
				}
		}

		public function update() {

			//udate the ivr menu
				if (strlen($this->ivr_menu_option_action) == 0) {
					//get the dialplan uuid
						$database = new database;
						if ($this->db) {
							$database->db = $this->db;
						}
						$database->table = "v_ivr_menus";
						$database->where[0]['name'] = 'domain_uuid';
						$database->where[0]['value'] = $this->domain_uuid;
						$database->where[0]['operator'] = '=';
						$database->where[1]['name'] = 'ivr_menu_uuid';
						$database->where[1]['value'] = $this->ivr_menu_uuid;
						$database->where[1]['operator'] = '=';
						$result = $database->find();
						foreach($result as $row) {
							$this->dialplan_uuid = $row['dialplan_uuid'];
						}

					//if the extension number is empty and the dialplan exists then delete the dialplan
						if (strlen($this->ivr_menu_extension) == 0) {
							if (strlen($this->dialplan_uuid) > 0) {
								//delete dialplan entry
									$database = new database;
									$database->table = "v_dialplan_details";
									$database->where[0]['name'] = 'domain_uuid';
									$database->where[0]['value'] = $this->domain_uuid;
									$database->where[0]['operator'] = '=';
									$database->where[1]['name'] = 'dialplan_uuid';
									$database->where[1]['value'] = $this->dialplan_uuid;
									$database->where[1]['operator'] = '=';
									$database->delete();

								//delete the child dialplan information
									$database = new database;
									$database->table = "v_dialplans";
									$database->where[0]['name'] = 'domain_uuid';
									$database->where[0]['value'] = $this->domain_uuid;
									$database->where[0]['operator'] = '=';
									$database->where[1]['name'] = 'dialplan_uuid';
									$database->where[1]['value'] = $this->dialplan_uuid;
									$database->where[1]['operator'] = '=';
									$database->delete();
								//update the table to remove the dialplan_uuid
									$this->dialplan_uuid = '';
							}
						}

					//update the ivr menu
						if (strlen($this->dialplan_uuid) == 0) {
							$this->dialplan_uuid = uuid();
						}
						$database = new database;
						$database->table = "v_ivr_menus";
						$database->fields['ivr_menu_uuid'] = $this->ivr_menu_uuid;
						$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
						$database->fields['ivr_menu_name'] = $this->ivr_menu_name;
						$database->fields['ivr_menu_extension'] = $this->ivr_menu_extension;
						$database->fields['ivr_menu_greet_long'] = $this->ivr_menu_greet_long;
						$database->fields['ivr_menu_greet_short'] = $this->ivr_menu_greet_short;
						$database->fields['ivr_menu_invalid_sound'] = $this->ivr_menu_invalid_sound;
						$database->fields['ivr_menu_exit_sound'] = $this->ivr_menu_exit_sound;
						$database->fields['ivr_menu_confirm_macro'] = $this->ivr_menu_confirm_macro;
						$database->fields['ivr_menu_confirm_key'] = $this->ivr_menu_confirm_key;
						$database->fields['ivr_menu_tts_engine'] = $this->ivr_menu_tts_engine;
						$database->fields['ivr_menu_tts_voice'] = $this->ivr_menu_tts_voice;
						$database->fields['ivr_menu_confirm_attempts'] = $this->ivr_menu_confirm_attempts;
						$database->fields['ivr_menu_timeout'] = $this->ivr_menu_timeout;
						$database->fields['ivr_menu_exit_app'] = $this->ivr_menu_exit_app;
						$database->fields['ivr_menu_exit_data'] = $this->ivr_menu_exit_data;
						$database->fields['ivr_menu_inter_digit_timeout'] = $this->ivr_menu_inter_digit_timeout;
						$database->fields['ivr_menu_max_failures'] = $this->ivr_menu_max_failures;
						$database->fields['ivr_menu_max_timeouts'] = $this->ivr_menu_max_timeouts;
						$database->fields['ivr_menu_max_timeouts'] = $this->ivr_menu_max_timeouts;
						$database->fields['ivr_menu_digit_len'] = $this->ivr_menu_digit_len;
						$database->fields['ivr_menu_digit_len'] = $this->ivr_menu_digit_len;
						$database->fields['ivr_menu_direct_dial'] = $this->ivr_menu_direct_dial;
						$database->fields['ivr_menu_ringback'] = $this->ivr_menu_ringback;
						$database->fields['ivr_menu_cid_prefix'] = $this->ivr_menu_cid_prefix;
						$database->fields['ivr_menu_enabled'] = $this->ivr_menu_enabled;
						$database->fields['ivr_menu_description'] = $this->ivr_menu_description;
						$database->where[0]['name'] = 'domain_uuid';
						$database->where[0]['value'] = $this->domain_uuid;
						$database->where[0]['operator'] = '=';
						$database->where[1]['name'] = 'ivr_menu_uuid';
						$database->where[1]['value'] = $this->ivr_menu_uuid;
						$database->where[1]['operator'] = '=';
						$database->update();

					//check to see if the dialplan entry exists
						$dialplan = new dialplan;
						$dialplan->domain_uuid = $_SESSION["domain_uuid"];
						$dialplan->dialplan_uuid = $this->dialplan_uuid;
						$dialplan_exists = $dialplan->dialplan_exists();

					//if the dialplan entry does not exist then add it
						if (!$dialplan_exists) {
							$database = new database;
							$database->table = "v_dialplans";
							$database->fields['dialplan_name'] = $this->ivr_menu_name;
							$database->fields['dialplan_order'] = '333';
							$database->fields['dialplan_context'] = $_SESSION['context'];
							$database->fields['dialplan_enabled'] = $this->ivr_menu_enabled;
							$database->fields['dialplan_description'] = $this->ivr_menu_description;
							$database->fields['app_uuid'] = $this->app_uuid;
							$database->fields['domain_uuid'] = $this->domain_uuid;
							$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
							$database->add();
						}
					//if the dialplan entry exists then update it
						if ($dialplan_exists && strlen($this->ivr_menu_extension) > 0) {
							//update the dialplan
								$database = new database;
								$database->table = "v_dialplans";
								$database->fields['dialplan_name'] = $this->ivr_menu_name;
								$database->fields['dialplan_order'] = '333';
								$database->fields['dialplan_context'] = $_SESSION['context'];
								$database->fields['dialplan_enabled'] = $this->ivr_menu_enabled;
								$database->fields['dialplan_description'] = $this->ivr_menu_description;
								$database->fields['app_uuid'] = $this->app_uuid;
								$database->fields['domain_uuid'] = $this->domain_uuid;
								$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
								$database->where[0]['name'] = 'domain_uuid';
								$database->where[0]['value'] = $this->domain_uuid;
								$database->where[0]['operator'] = '=';
								$database->where[1]['name'] = 'dialplan_uuid';
								$database->where[1]['value'] = $this->dialplan_uuid;
								$database->where[1]['operator'] = '=';
								$database->update();

							//delete the old dialplan details to prepare for new details 
								$database = new database;
								$database->table = "v_dialplan_details";
								$database->where[0]['name'] = 'domain_uuid';
								$database->where[0]['value'] = $this->domain_uuid;
								$database->where[0]['operator'] = '=';
								$database->where[1]['name'] = 'dialplan_uuid';
								$database->where[1]['value'] = $this->dialplan_uuid;
								$database->where[1]['operator'] = '=';
								$database->delete();
						}

						//add the dialplan details
							$detail_data = '^'.$this->ivr_menu_extension.'$';
							$database->table = "v_dialplan_details";
							$database->fields['domain_uuid'] = $this->domain_uuid;
							$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
							$database->fields['dialplan_detail_uuid'] = uuid();
							$database->fields['dialplan_detail_tag'] = 'condition'; //condition, action, antiaction
							$database->fields['dialplan_detail_type'] = 'destination_number';
							$database->fields['dialplan_detail_data'] = $detail_data;
							$database->fields['dialplan_detail_order'] = '005';
							$database->add();

							$database->table = "v_dialplan_details";
							$database->fields['domain_uuid'] = $this->domain_uuid;
							$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
							$database->fields['dialplan_detail_uuid'] = uuid();
							$database->fields['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
							$database->fields['dialplan_detail_type'] = 'answer';
							$database->fields['dialplan_detail_data'] = '';
							$database->fields['dialplan_detail_order'] = '010';
							$database->add();

							$database->table = "v_dialplan_details";
							$database->fields['domain_uuid'] = $this->domain_uuid;
							$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
							$database->fields['dialplan_detail_uuid'] = uuid();
							$database->fields['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
							$database->fields['dialplan_detail_type'] = 'sleep';
							$database->fields['dialplan_detail_data'] = '1000';
							$database->fields['dialplan_detail_order'] = '015';
							$database->add();

							$database->table = "v_dialplan_details";
							$database->fields['domain_uuid'] = $this->domain_uuid;
							$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
							$database->fields['dialplan_detail_uuid'] = uuid();
							$database->fields['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
							$database->fields['dialplan_detail_type'] = 'set';
							$database->fields['dialplan_detail_data'] = 'hangup_after_bridge=true';
							$database->fields['dialplan_detail_order'] = '020';
							$database->add();

							$database->table = "v_dialplan_details";
							$database->fields['domain_uuid'] = $this->domain_uuid;
							$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
							$database->fields['dialplan_detail_uuid'] = uuid();
							$database->fields['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
							$database->fields['dialplan_detail_type'] = 'set';
							if ($this->ivr_menu_ringback == "music" || $this->ivr_menu_ringback == "") {
								$database->fields['dialplan_detail_data'] = 'ringback=${hold_music}';
							}
							else {
								$database->fields['dialplan_detail_data'] = 'ringback='.$this->ivr_menu_ringback;
							}
							$database->fields['dialplan_detail_order'] = '025';
							$database->add();

							$database->table = "v_dialplan_details";
							$database->fields['domain_uuid'] = $this->domain_uuid;
							$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
							$database->fields['dialplan_detail_uuid'] = uuid();
							$database->fields['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
							$database->fields['dialplan_detail_type'] = 'set';
							if ($this->ivr_menu_ringback == "music" || $this->ivr_menu_ringback == "") {
								$database->fields['dialplan_detail_data'] = 'transfer_ringback=${hold_music}';
							}
							else {
								$database->fields['dialplan_detail_data'] = 'transfer_ringback='.$this->ivr_menu_ringback;
							}
							$database->fields['dialplan_detail_order'] = '030';
							$database->add();

							/*
							$database->table = "v_dialplan_details";
							$database->fields['domain_uuid'] = $this->domain_uuid;
							$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
							$database->fields['dialplan_detail_uuid'] = uuid();
							$database->fields['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
							$database->fields['dialplan_detail_type'] = 'ivr';
							if (count($_SESSION["domains"]) > 1) {
								$database->fields['dialplan_detail_data'] = $_SESSION['domain_name'].'-'.$this->ivr_menu_name;
							}
							else {
								$database->fields['dialplan_detail_data'] = $this->ivr_menu_name;
							}
							$database->fields['dialplan_detail_order'] = '035';
							$database->add();
							*/

							$database->table = "v_dialplan_details";
							$database->fields['domain_uuid'] = $this->domain_uuid;
							$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
							$database->fields['dialplan_detail_uuid'] = uuid();
							$database->fields['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
							$database->fields['dialplan_detail_type'] = 'set';
							$database->fields['dialplan_detail_data'] = 'ivr_menu_uuid='.$this->ivr_menu_uuid;
							$database->fields['dialplan_detail_order'] = '035';
							$database->add();

							$database->table = "v_dialplan_details";
							$database->fields['domain_uuid'] = $this->domain_uuid;
							$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
							$database->fields['dialplan_detail_uuid'] = uuid();
							$database->fields['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
							$database->fields['dialplan_detail_type'] = 'lua';
							$database->fields['dialplan_detail_data'] = 'ivr_menu.lua';
							$database->fields['dialplan_detail_order'] = '040';
							$database->add();

							if (strlen($this->ivr_menu_exit_app) > 0) {
								$database->table = "v_dialplan_details";
								$database->fields['domain_uuid'] = $this->domain_uuid;
								$database->fields['dialplan_uuid'] = $this->dialplan_uuid;
								$database->fields['dialplan_detail_uuid'] = uuid();
								$database->fields['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
								$database->fields['dialplan_detail_type'] = $this->ivr_menu_exit_app;
								$database->fields['dialplan_detail_data'] = $this->ivr_menu_exit_data;
								$database->fields['dialplan_detail_order'] = '045';
								$database->add();
							}
				}

			//update the ivr menu option
				if (strlen($this->ivr_menu_option_action) > 0) {
					$database = new database;
					$database->table = "v_ivr_menu_options";
					$database->fields['ivr_menu_option_digits'] = $this->ivr_menu_option_digits;
					$database->fields['ivr_menu_option_action'] = $this->ivr_menu_option_action;
					$database->fields['ivr_menu_option_param'] = $this->ivr_menu_option_param;
					$database->fields['ivr_menu_option_order'] = $this->ivr_menu_option_order;
					$database->fields['ivr_menu_option_description'] = $this->ivr_menu_option_description;
					$database->where[0]['name'] = 'domain_uuid';
					$database->where[0]['value'] = $this->domain_uuid;
					$database->where[0]['operator'] = '=';
					$database->where[1]['name'] = 'ivr_menu_uuid';
					$database->where[1]['value'] = $this->ivr_menu_uuid;
					$database->where[1]['operator'] = '=';
					$database->where[2]['name'] = 'ivr_menu_option_uuid';
					$database->where[2]['value'] = $this->ivr_menu_option_uuid;
					$database->where[2]['operator'] = '=';
					$database->update();
				}
		}

		function delete() {
			//create the database object
				$database = new database;
				if ($this->db) {
					$database->db = $this->db;
				}
			//start the transaction
				//$count = $database->db->exec("BEGIN;");

			//delete the ivr menu option
				if (strlen($this->ivr_menu_option_uuid) > 0) {
					$database->table = "v_ivr_menu_options";
					$database->where[0]['name'] = 'domain_uuid';
					$database->where[0]['value'] = $this->domain_uuid;
					$database->where[0]['operator'] = '=';
					$database->where[1]['name'] = 'ivr_menu_option_uuid';
					$database->where[1]['value'] = $this->ivr_menu_option_uuid;
					$database->where[1]['operator'] = '=';
					$database->delete();
					unset($this->ivr_menu_option_uuid);
				}

			//delete the ivr menu
				if (strlen($this->ivr_menu_option_uuid) == 0) {
					//select the dialplan entries
						$database->table = "v_ivr_menus";
						$database->where[0]['name'] = 'domain_uuid';
						$database->where[0]['value'] = $this->domain_uuid;
						$database->where[0]['operator'] = '=';
						$database->where[1]['name'] = 'ivr_menu_uuid';
						$database->where[1]['value'] = $this->ivr_menu_uuid;
						$database->where[1]['operator'] = '=';
						$result = $database->find();
						foreach($result as $row) {
							$this->dialplan_uuid = $row['dialplan_uuid'];
							//delete the child dialplan information
								$database->table = "v_dialplan_details";
								$database->where[0]['name'] = 'domain_uuid';
								$database->where[0]['value'] = $this->domain_uuid;
								$database->where[0]['operator'] = '=';
								$database->where[1]['name'] = 'dialplan_uuid';
								$database->where[1]['value'] = $this->dialplan_uuid;
								$database->where[1]['operator'] = '=';
								$database->delete();
							//delete the dialplan information
								$database->table = "v_dialplans";
								$database->where[0]['name'] = 'domain_uuid';
								$database->where[0]['value'] = $this->domain_uuid;
								$database->where[0]['operator'] = '=';
								$database->where[1]['name'] = 'dialplan_uuid';
								$database->where[1]['value'] = $this->dialplan_uuid;
								$database->where[1]['operator'] = '=';
								$database->delete();
						}

					//delete child data
						$database->table = "v_ivr_menu_options";
						$database->where[0]['name'] = 'domain_uuid';
						$database->where[0]['value'] = $this->domain_uuid;
						$database->where[0]['operator'] = '=';
						$database->where[1]['name'] = 'ivr_menu_uuid';
						$database->where[1]['value'] = $this->ivr_menu_uuid;
						$database->where[1]['operator'] = '=';
						$database->delete();

					//delete parent data
						$database->table = "v_ivr_menus";
						$database->where[0]['name'] = 'domain_uuid';
						$database->where[0]['value'] = $this->domain_uuid;
						$database->where[0]['operator'] = '=';
						$database->where[1]['name'] = 'ivr_menu_uuid';
						$database->where[1]['value'] = $this->ivr_menu_uuid;
						$database->where[1]['operator'] = '=';
						$database->delete();

					//delete the dialplan context from memcache
						$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
						if ($fp) {
							$switch_cmd = "memcache delete dialplan:".$_SESSION["context"]."@".$_SESSION['domain_name'];
							$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
						}

					//commit the transaction
						//$count = $database->db->exec("COMMIT;");
				}
		}

		function get_xml(){
			return $xml;
		}

		function save_xml($xml){
			return $xml;
		}

		function xml_save_all() {
		}
	}

?>