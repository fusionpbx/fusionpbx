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
	Copyright (C) 2010-2016
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";

//define the ivr_menu class
	class ivr_menu {
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
			require_once "resources/classes/database.php";
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

		public function save($array) {
			//action add or update
				if (strlen($this->ivr_menu_uuid) == 0) {
					$action = "add";
				}
				else {
					$action = "update";
				}

			//add or update the dialplan
				$this->dialplan($array);
				$array['dialplan_uuid'] = $this->dialplan_uuid;
				$data['ivr_menus'][] = $array;

			//save the ivr_menus
				$database = new database;
				$database->app_name = 'ivr_menus';
				$database->app_uuid = $this->app_uuid;
				if (strlen($this->ivr_menu_uuid) > 0) {
					$database->uuid($this->ivr_menu_uuid);
				}
				$result = $database->save($data);
				$this->ivr_menu_uuid = $database->message['uuid'];

			//update dialplan with the ivr_menu_uuid
				if ($action == "add") {
					$array['ivr_menu_uuid'] = $this->ivr_menu_uuid;
					$this->dialplan($array);
				}

			//set the add message
				if ($action == "add" && permission_exists('ivr_menu_add')) {
					messages::add($text['message-add']);
				}

			//set the update message
				if ($action == "update" && permission_exists('ivr_menu_edit')) {
					messages::add($text['message-update']);
				}
				
			//return the result
				return $database->message;
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
							//set the uuid
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
							$switch_cmd = "memcache delete dialplan:".$_SESSION["context"];
							$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
						}

					//commit the transaction
						//$count = $database->db->exec("COMMIT;");
				}
		}

		public function dialplan($field) {

			//set global variables
				global $db;

			//action add or update
				if (strlen($field['dialplan_uuid']) == 0) {
					$field['dialplan_uuid'] = uuid();
					$action = "add";
				}
				else {
					$action = "update";
				}

			//delete child data
				if ($action == "update") {
					$sql = "delete from v_dialplan_details ";
					$sql .= "where dialplan_uuid = '".$field["dialplan_uuid"]."'; ";
					$db->query($sql);
					unset($sql);
				}

			//get dialplan details from the database
				if ($action == "update") {
					$sql = "select * from v_dialplan_details ";
					$sql .= "where dialplan_uuid = '".$field["dialplan_uuid"]."' ";
					$prep_statement = $db->prepare($sql);
					if ($prep_statement) {
						$prep_statement->execute();
						$dialplan_details = $prep_statement->fetchAll(PDO::FETCH_NAMED);
					}
					unset($sql, $prep_statement, $row);
				}

			//get the array from the database
				/*
				$database = new database;
				if ($this->db) { $database->db = $this->db; }
				$database->table = "v_ivr_menu_details";
				$database->where[0]['name'] = 'ivr_menu_uuid';
				$database->where[0]['value'] = $field["ivr_menu_uuid"];
				$database->where[0]['operator'] = '=';
				$ivr_menu_details = $database->find();
				*/

			//add the details array
				$x=0;
				$details[$x]['dialplan_detail_tag'] = 'condition'; //condition, action, antiaction
				$details[$x]['dialplan_detail_type'] = 'destination_number';
				$details[$x]['dialplan_detail_data'] = '^'.$field['ivr_menu_extension'].'$';
				$details[$x]['dialplan_detail_order'] = '005';

				$x++;
				$details[$x]['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
				$details[$x]['dialplan_detail_type'] = 'answer';
				$details[$x]['dialplan_detail_data'] = '';
				$details[$x]['dialplan_detail_order'] = '010';

				$x++;
				$details[$x]['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
				$details[$x]['dialplan_detail_type'] = 'sleep';
				$details[$x]['dialplan_detail_data'] = '1000';
				$details[$x]['dialplan_detail_order'] = '015';

				$x++;
				$details[$x]['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
				$details[$x]['dialplan_detail_type'] = 'set';
				$details[$x]['dialplan_detail_data'] = 'hangup_after_bridge=true';
				$details[$x]['dialplan_detail_order'] = '020';

				$x++;
				$details[$x]['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
				$details[$x]['dialplan_detail_type'] = 'set';
				if ($field['ivr_menu_ringback'] == "music" || $field['ivr_menu_ringback'] == "") {
					$details[$x]['dialplan_detail_data'] = 'ringback=${hold_music}';
				}
				else {
					$details[$x]['dialplan_detail_data'] = 'ringback='.$field['ivr_menu_ringback'];
				}
				$details[$x]['dialplan_detail_order'] = '025';

				$x++;
				$details[$x]['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
				$details[$x]['dialplan_detail_type'] = 'set';
				if ($field['ivr_menu_ringback'] == "music" || $field['ivr_menu_ringback'] == "") {
					$details[$x]['dialplan_detail_data'] = 'transfer_ringback=${hold_music}';
				}
				else {
					$details[$x]['dialplan_detail_data'] = 'transfer_ringback='.$field['ivr_menu_ringback'];
				}
				$details[$x]['dialplan_detail_order'] = '030';

				$x++;
				$details[$x]['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
				$details[$x]['dialplan_detail_type'] = 'set';
				$details[$x]['dialplan_detail_data'] = 'ivr_menu_uuid='.$this->ivr_menu_uuid;
				$details[$x]['dialplan_detail_order'] = '035';

				$x++;
				$details[$x]['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
				if ($_SESSION['ivr_menu']['application']['text'] == "lua") {
					$details[$x]['dialplan_detail_type'] = 'lua';
					$details[$x]['dialplan_detail_data'] = 'ivr_menu.lua';
				}
				else {
					$details[$x]['dialplan_detail_type'] = 'ivr';
					$details[$x]['dialplan_detail_data'] = $this->ivr_menu_uuid;
				}
				$details[$x]['dialplan_detail_order'] = '040';

				if (strlen($field['ivr_menu_exit_app']) > 0) {
					$x++;
					$details[$x]['dialplan_detail_tag'] = 'action'; //condition, action, antiaction
					$details[$x]['dialplan_detail_type'] = $field['ivr_menu_exit_app'];
					$details[$x]['dialplan_detail_data'] = $field['ivr_menu_exit_data'];
					$details[$x]['dialplan_detail_order'] = '045';
					$x++;
				}

			//build the array
				$array['domain_uuid'] = $field['domain_uuid'];
				$array['dialplan_uuid'] = $field['dialplan_uuid'];
				$array['dialplan_name'] = $field['ivr_menu_name'];
				$array['dialplan_order'] = '333';
				$array['dialplan_context'] = $_SESSION['context'];
				$array['dialplan_enabled'] = $field['ivr_menu_enabled'];
				$array['dialplan_description'] = $field['ivr_menu_description'];
				$array['app_uuid'] = $this->app_uuid;
				$x=0;
				foreach ($details as $row) {
					//find the matching uuid
						/*
						foreach ($dialplan_details as $database) {
							if ($database['dialplan_detail_tag'] == $row['dialplan_detail_tag']
								&& $database['dialplan_detail_type'] == $row['dialplan_detail_type']
								&& $database['dialplan_detail_data'] == $row['dialplan_detail_data']) {
									$array['dialplan_details'][$x]['dialplan_detail_uuid'] = $database['dialplan_detail_uuid'];
							}
						}
						*/

					//add to the array
						$array['dialplan_details'][$x]['domain_uuid'] = $field['domain_uuid'];
						$array['dialplan_details'][$x]['dialplan_uuid'] = $field['dialplan_uuid'];
						$array['dialplan_details'][$x]['dialplan_detail_tag'] = $row['dialplan_detail_tag'];
						$array['dialplan_details'][$x]['dialplan_detail_type'] = $row['dialplan_detail_type'];
						$array['dialplan_details'][$x]['dialplan_detail_data'] = $row['dialplan_detail_data'];
						$array['dialplan_details'][$x]['dialplan_detail_order'] = $row['dialplan_detail_order'];

					//increment the row
						$x++;
				}

			//debug
				//echo "<pre>\n";
				//print_r($dialplan_details);
				//print_r($array);
				//echo "</pre>\n";
				//exit;

			//add the dialplan permission
				$p = new permissions;
				$p->add("dialplan_add", 'temp');
				$p->add("dialplan_detail_add", 'temp');
				$p->add("dialplan_edit", 'temp');
				$p->add("dialplan_detail_edit", 'temp');

			//save the dialplan
				$database = new database;
				$database->name('dialplans');
				$database->app_name = 'dialplans';
				$database->app_uuid = $this->app_uuid;
				$database->save($array);
				$response = $database->message;
				if (strlen($response['uuid']) > 0) {
					$this->dialplan_uuid = $response['uuid'];
				}

			//debug
				//echo "<pre>\n";
				//print_r($response);
				//echo "</pre>\n";
				//exit;

			//remove the temporary permission
				$p->delete("dialplan_add", 'temp');
				$p->delete("dialplan_detail_add", 'temp');
				$p->delete("dialplan_edit", 'temp');
				$p->delete("dialplan_detail_edit", 'temp');

			//synchronize the xml config
				save_dialplan_xml();

			//delete the dialplan context from memcache
				//$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
				//if ($fp) {
				//	$switch_cmd .= "memcache delete dialplan:".$_SESSION["context"]."@".$_SESSION['domain_name'];
				//	$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
				//}
		}

		function get_xml(){
			/*
			//set the global variables
				global $db;

			//prepare for dialplan .xml files to be written. delete all dialplan files that are prefixed with dialplan_ and have a file extension of .xml
				if (count($_SESSION["domains"]) > 1) {
					$v_needle = 'v_'.$_SESSION['domain_name'].'_';
				}
				else {
					$v_needle = 'v_';
				}
				if($dh = opendir($_SESSION['switch']['conf']['dir']."/ivr_menus/")) {
					$files = Array();
					while($file = readdir($dh)) {
						if($file != "." && $file != ".." && $file[0] != '.') {
							if(is_dir($dir . "/" . $file)) {
								//this is a directory
							} else {
								if (strpos($file, $v_needle) !== false && substr($file,-4) == '.xml') {
									//echo "file: $file<br />\n";
									unlink($_SESSION['switch']['conf']['dir']."/ivr_menus/".$file);
								}
							}
						}
					}
					closedir($dh);
				}

				$sql = "select * from v_ivr_menus ";
				$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
				$result_count = count($result);
				unset ($prep_statement, $sql);
				if ($result_count > 0) {
					foreach($result as $row) {
						$dialplan_uuid = $row["dialplan_uuid"];
						$ivr_menu_uuid = $row["ivr_menu_uuid"];
						$ivr_menu_name = check_str($row["ivr_menu_name"]);
						$ivr_menu_extension = $row["ivr_menu_extension"];
						$ivr_menu_greet_long = $row["ivr_menu_greet_long"];
						$ivr_menu_greet_short = $row["ivr_menu_greet_short"];
						$ivr_menu_invalid_sound = $row["ivr_menu_invalid_sound"];
						$ivr_menu_exit_sound = $row["ivr_menu_exit_sound"];
						$ivr_menu_confirm_macro = $row["ivr_menu_confirm_macro"];
						$ivr_menu_confirm_key = $row["ivr_menu_confirm_key"];
						$ivr_menu_tts_engine = $row["ivr_menu_tts_engine"];
						$ivr_menu_tts_voice = $row["ivr_menu_tts_voice"];
						$ivr_menu_confirm_attempts = $row["ivr_menu_confirm_attempts"];
						$ivr_menu_timeout = $row["ivr_menu_timeout"];
						$ivr_menu_exit_app = $row["ivr_menu_exit_app"];
						$ivr_menu_exit_data = $row["ivr_menu_exit_data"];
						$ivr_menu_inter_digit_timeout = $row["ivr_menu_inter_digit_timeout"];
						$ivr_menu_max_failures = $row["ivr_menu_max_failures"];
						$ivr_menu_max_timeouts = $row["ivr_menu_max_timeouts"];
						$ivr_menu_digit_len = $row["ivr_menu_digit_len"];
						$ivr_menu_direct_dial = $row["ivr_menu_direct_dial"];
						$ivr_menu_enabled = $row["ivr_menu_enabled"];
						$ivr_menu_description = check_str($row["ivr_menu_description"]);

						//replace space with an underscore
							$ivr_menu_name = str_replace(" ", "_", $ivr_menu_name);

						//add each IVR menu to the XML config
							$tmp = "<include>\n";
							if (strlen($ivr_menu_description) > 0) {
								$tmp .= "	<!-- $ivr_menu_description -->\n";
							}
							if (count($_SESSION["domains"]) > 1) {
								$tmp .= "	<menu name=\"".$_SESSION['domains'][$_SESSION['domain_uuid']['domain_name']."-".$ivr_menu_name."\n";
							}
							else {
								$tmp .= "	<menu name=\"$ivr_menu_name\"\n";
							}
							if (stripos($ivr_menu_greet_long, 'mp3') !== false || stripos($ivr_menu_greet_long, 'wav') !== false) {
								//found wav or mp3
								$tmp .= "		greet-long=\"".$ivr_menu_greet_long."\"\n";
							}
							else {
								//not found
								$tmp .= "		greet-long=\"".$ivr_menu_greet_long."\"\n";
							}
							if (stripos($ivr_menu_greet_short, 'mp3') !== false || stripos($ivr_menu_greet_short, 'wav') !== false) {
								if (strlen($ivr_menu_greet_short) > 0) {
									$tmp .= "		greet-short=\"".$ivr_menu_greet_short."\"\n";
								}
							}
							else {
								//not found
								if (strlen($ivr_menu_greet_short) > 0) {
									$tmp .= "		greet-short=\"".$ivr_menu_greet_short."\"\n";
								}
							}
							$tmp .= "		invalid-sound=\"$ivr_menu_invalid_sound\"\n";
							$tmp .= "		exit-sound=\"$ivr_menu_exit_sound\"\n";
							$tmp .= "		confirm-macro=\"$ivr_menu_confirm_macro\"\n";
							$tmp .= "		confirm-key=\"$ivr_menu_confirm_key\"\n";
							$tmp .= "		tts-engine=\"$ivr_menu_tts_engine\"\n";
							$tmp .= "		tts-voice=\"$ivr_menu_tts_voice\"\n";
							$tmp .= "		confirm-attempts=\"$ivr_menu_confirm_attempts\"\n";
							$tmp .= "		timeout=\"$ivr_menu_timeout\"\n";
							$tmp .= "		inter-digit-timeout=\"$ivr_menu_inter_digit_timeout\"\n";
							$tmp .= "		max-failures=\"$ivr_menu_max_failures\"\n";
							$tmp .= "		max-timeouts=\"$ivr_menu_max_timeouts\"\n";
							$tmp .= "		digit-len=\"$ivr_menu_digit_len\">\n";

							$sub_sql = "select * from v_ivr_menu_options ";
							$sub_sql .= "where ivr_menu_uuid = '$ivr_menu_uuid' ";
							$sub_sql .= "and domain_uuid = '".$_SESSION['domain_uuid']."' ";
							$sub_sql .= "order by ivr_menu_option_order asc ";
							$sub_prep_statement = $db->prepare(check_sql($sub_sql));
							$sub_prep_statement->execute();
							$sub_result = $sub_prep_statement->fetchAll(PDO::FETCH_ASSOC);
							foreach ($sub_result as &$sub_row) {
								//$ivr_menu_uuid = $sub_row["ivr_menu_uuid"];
								$ivr_menu_option_digits = $sub_row["ivr_menu_option_digits"];
								$ivr_menu_option_action = $sub_row["ivr_menu_option_action"];
								$ivr_menu_option_param = $sub_row["ivr_menu_option_param"];
								$ivr_menu_option_description = $sub_row["ivr_menu_option_description"];

								$tmp .= "		<entry action=\"$ivr_menu_option_action\" digits=\"$ivr_menu_option_digits\" param=\"$ivr_menu_option_param\"/>";
								if (strlen($ivr_menu_option_description) == 0) {
									$tmp .= "\n";
								}
								else {
									$tmp .= "	<!-- $ivr_menu_option_description -->\n";
								}
							}
							unset ($sub_prep_statement, $sub_row);

							if ($ivr_menu_direct_dial == "true") {
								$tmp .= "		<entry action=\"menu-exec-app\" digits=\"/(^\d{3,6}$)/\" param=\"transfer $1 XML ".$_SESSION["context"]."\"/>\n";
							}
							$tmp .= "	</menu>\n";
							$tmp .= "</include>\n";

						//remove invalid characters from the file names
							$ivr_menu_name = str_replace(" ", "_", $ivr_menu_name);
							$ivr_menu_name = preg_replace("/[\*\:\\/\<\>\|\'\"\?]/", "", $ivr_menu_name);

						//write the file
							if (count($_SESSION["domains"]) > 1) {
								$fout = fopen($_SESSION['switch']['conf']['dir']."/ivr_menus/v_".$_SESSION['domains'][$row['domain_uuid']]['domain_name']."_".$ivr_menu_name.".xml","w");
							}
							else {
								$fout = fopen($_SESSION['switch']['conf']['dir']."/ivr_menus/v_".$ivr_menu_name.".xml","w");
							}
							fwrite($fout, $tmp);
							fclose($fout);
					}
				}
				*/
			return $xml;
		}

		function save_xml($xml){
			return $xml;
		}

	}

?>
