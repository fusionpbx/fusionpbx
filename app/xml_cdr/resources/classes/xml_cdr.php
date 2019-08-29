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
	Portions created by the Initial Developer are Copyright (C) 2016-2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/


/**
 * xml_cdr class provides methods for adding cdr records to the database
 *
 * @method boolean add
 */
if (!class_exists('xml_cdr')) {
	class xml_cdr {

		/**
		 * define variables
		 */
		public $db;
		public $array;
		public $debug;
		public $fields;

		/**
		 * user summary
		 */
		public $domain_uuid;
		public $quick_select;
		public $start_stamp_begin;
		public $start_stamp_end;
		public $include_internal;
		public $extensions;

		/**
		 * Called when the object is created
		 */
		public function __construct() {
			//connect to the database if not connected
			if (!$this->db) {
				require_once "resources/classes/database.php";
				$database = new database;
				$database->connect();
				$this->db = $database->db;
			}
		}

		/**
		 * Called when there are no references to a particular object
		 * unset the variables used in the class
		 */
		public function __destruct() {
			if (isset($this)) foreach ($this as $key => $value) {
				unset($this->$key);
			}
		}

		/**
		 * cdr process logging
		 */
		public function log($message) {
			//save to file system (alternative to a syslog server)
				$fp = fopen($_SESSION['server']['temp']['dir'].'/xml_cdr.log', 'a+');
				if (!$fp) {
					return;
				}
				fwrite($fp, $message);
				fclose($fp);
		}

		/**
		 * cdr fields in the database schema
		 */
		public function fields() {

			$this->fields[] = "xml_cdr_uuid";
			$this->fields[] = "domain_uuid";
			$this->fields[] = "extension_uuid";
			$this->fields[] = "domain_name";
			$this->fields[] = "accountcode";
			$this->fields[] = "direction";
			$this->fields[] = "default_language";
			$this->fields[] = "context";
			$this->fields[] = "xml";
			$this->fields[] = "json";
			$this->fields[] = "missed_call";
			$this->fields[] = "caller_id_name";
			$this->fields[] = "caller_id_number";
			$this->fields[] = "caller_destination";
			$this->fields[] = "destination_number";
			$this->fields[] = "source_number";
			$this->fields[] = "start_epoch";
			$this->fields[] = "start_stamp";
			$this->fields[] = "answer_stamp";
			$this->fields[] = "answer_epoch";
			$this->fields[] = "end_epoch";
			$this->fields[] = "end_stamp";
			$this->fields[] = "duration";
			$this->fields[] = "mduration";
			$this->fields[] = "billsec";
			$this->fields[] = "billmsec";
			$this->fields[] = "bridge_uuid";
			$this->fields[] = "read_codec";
			$this->fields[] = "read_rate";
			$this->fields[] = "write_codec";
			$this->fields[] = "write_rate";
			$this->fields[] = "remote_media_ip";
			$this->fields[] = "network_addr";
			$this->fields[] = "record_path";
			$this->fields[] = "record_name";
			$this->fields[] = "leg";
			$this->fields[] = "pdd_ms";
			$this->fields[] = "rtp_audio_in_mos";
			$this->fields[] = "last_app";
			$this->fields[] = "last_arg";
			$this->fields[] = "cc_side";
			$this->fields[] = "cc_member_uuid";
			$this->fields[] = "cc_queue_joined_epoch";
			$this->fields[] = "cc_queue";
			$this->fields[] = "cc_member_session_uuid";
			$this->fields[] = "cc_agent_uuid";
			$this->fields[] = "cc_agent";
			$this->fields[] = "cc_agent_type";
			$this->fields[] = "cc_agent_bridged";
			$this->fields[] = "cc_queue_answered_epoch";
			$this->fields[] = "cc_queue_terminated_epoch";
			$this->fields[] = "cc_cause";
			$this->fields[] = "waitsec";
			$this->fields[] = "conference_name";
			$this->fields[] = "conference_uuid";
			$this->fields[] = "conference_member_id";
			$this->fields[] = "digits_dialed";
			$this->fields[] = "pin_number";
			$this->fields[] = "hangup_cause";
			$this->fields[] = "hangup_cause_q850";
			$this->fields[] = "sip_hangup_disposition";
			if (is_array($_SESSION['cdr']['field'])) {
				foreach ($_SESSION['cdr']['field'] as $field) {
					$this->fields[] = $field;
				}
			}
		}

		/**
		 * save to the database
		 */
		public function save() {

			$this->fields();
			$field_count = sizeof($this->fields);

			$sql = "insert into v_xml_cdr (";
			$f = 1;
			if (isset($this->fields)) foreach ($this->fields as $field) {
				if ($field_count == $f) {
					$sql .= "$field ";
				}
				else {
					$sql .= "$field, ";
				}
				$f++;
			}
			$sql .= ")\n";
			$sql .= "values \n";
			$row_count = sizeof($this->array);
			//$field_count = sizeof($this->fields);
			$i = 0;
			if (isset($this->array)) foreach ($this->array as $row) {
				$sql .= "(";
				$f = 1;
				if (isset($this->fields)) foreach ($this->fields as $field) {
					if (isset($row[$field]) && strlen($row[$field]) > 0) {
						$sql .= "'".$row[$field]."'";
					}
					else {
						$sql .= "null";
					}
					if ($field_count != $f) {
						$sql .= ",";
					}
					$f++;
				}
				$sql .= ")";
				if ($row_count != $i) {
					$sql .= ",\n";
				}
				$i++;
			}
			if (substr($sql,-2) == ",\n") {
				$sql = substr($sql,0,-2);
			}
			$this->db->exec(check_sql($sql));
			unset($sql);
		}

		/**
		 * process method converts the xml cdr and adds it to the database
		 */
		public function xml_array($key, $leg, $xml_string) {

			//fix the xml by escaping the contents of <sip_full_XXX>
				if(defined('STDIN')) {
					$xml_string = preg_replace_callback("/<([^><]+)>(.*?[><].*?)<\/\g1>/",
						function ($matches) {
							return '<' . $matches[1] . '>' .
								str_replace(">", "&gt;",
									str_replace("<", "&lt;", $matches[2])
								) .
							'</' . $matches[1] . '>';
						},
						$xml_string
					);
				}

			//parse the xml to get the call detail record info
				try {
					//$this->log($xml_string);
					$xml = simplexml_load_string($xml_string);
					//$this->log("\nxml load done\n");
				}
				catch(Exception $e) {
					echo $e->getMessage();
					//$this->log("\nfail loadxml: " . $e->getMessage() . "\n");
				}

			//check for duplicate call uuid's
				$duplicate_uuid = false;
				$uuid = check_str(urldecode($xml->variables->uuid));
				if($uuid != null) {
					//Check in the database
						$database = new database;
						$database->table = "v_xml_cdr";
						$where[1]["name"] = "xml_cdr_uuid";
						$where[1]["operator"] = "=";
						$where[1]["value"] = $uuid;
						$database->where = $where;
						$result = $database->count();
						if ($result > 0) {
							$duplicate_uuid = true;
						}
						unset($where,$result,$database);
					//Check in the array
						if (isset($this->array)) foreach ($this->array as $row) {
							if (in_array($uuid,$row,true))
								$duplicate_uuid = true;
						}
				}

			//process data if the call detail record is not a duplicate
				if ($duplicate_uuid == false && $uuid != null) {
					//get the destination number
						if ($xml->variables->current_application == "bridge") {
							$current_application_data = urldecode($xml->variables->current_application_data);
							$bridge_array = explode("/", $current_application_data);
							$destination_number = end($bridge_array);
							if (strpos($destination_number,'@') !== FALSE) {
								$destination_array = explode("@", $destination_number);
								$destination_number = $destination_array[0];
							}
						}
						else {
							$destination_number = urldecode($xml->variables->sip_to_user);
						}

					//if last_sent_callee_id_number is set use it for the destination_number
						if (strlen($xml->variables->last_sent_callee_id_number) > 0) {
							$destination_number = urldecode($xml->variables->last_sent_callee_id_number);
						}

					//set missed calls
						$missed_call = 'false';
						if ($xml->variables->call_direction == 'local' || $xml->variables->call_direction == 'inbound') {
							if ($xml->variables->billsec == 0) {
								$missed_call = 'true';
							}
						}
						if ($xml->variables->missed_call == 'true') {
							$missed_call = 'true';
						}

					//get the caller details
						$caller_id_name = urldecode($xml->variables->effective_caller_id_name);
						$caller_id_number = urldecode($xml->variables->effective_caller_id_number);
						$caller_id_destination = urldecode($xml->variables->caller_destination);
						foreach ($xml->callflow as $row) {
							$caller_id_number = urldecode($row->caller_profile->caller_id_number);
						}
						if (strlen($caller_id_name) == 0) {
							foreach ($xml->callflow as $row) {
								$caller_id_name = urldecode($row->caller_profile->caller_id_name);
							}
						}

					//misc
						$uuid = check_str(urldecode($xml->variables->uuid));
						$this->array[$key]['xml_cdr_uuid'] = $uuid;
						$this->array[$key]['destination_number'] = check_str($destination_number);
						$this->array[$key]['source_number'] = check_str(urldecode($xml->variables->effective_caller_id_number));
						$this->array[$key]['user_context'] = check_str(urldecode($xml->variables->user_context));
						$this->array[$key]['network_addr'] = check_str(urldecode($xml->variables->sip_network_ip));
						$this->array[$key]['missed_call'] = check_str($missed_call);
						$this->array[$key]['caller_id_name'] = check_str($caller_id_name);
						$this->array[$key]['caller_id_number'] = check_str($caller_id_number);
						$this->array[$key]['caller_destination'] = check_str(urldecode($xml->variables->caller_destination));
						$this->array[$key]['accountcode'] = check_str(urldecode($xml->variables->accountcode));
						$this->array[$key]['default_language'] = check_str(urldecode($xml->variables->default_language));
						$this->array[$key]['bridge_uuid'] = check_str(urldecode($xml->variables->bridge_uuid));
						//$this->array[$key]['digits_dialed'] = check_str(urldecode($xml->variables->digits_dialed));
						$this->array[$key]['sip_hangup_disposition'] = check_str(urldecode($xml->variables->sip_hangup_disposition));
						$this->array[$key]['pin_number'] = check_str(urldecode($xml->variables->pin_number));
					//time
						$this->array[$key]['start_epoch'] = check_str(urldecode($xml->variables->start_epoch));
						$start_stamp = check_str(urldecode($xml->variables->start_stamp));
						$this->array[$key]['start_stamp'] = $start_stamp;
						$this->array[$key]['answer_stamp'] = check_str(urldecode($xml->variables->answer_stamp));
						$this->array[$key]['answer_epoch'] = check_str(urldecode($xml->variables->answer_epoch));
						$this->array[$key]['end_epoch'] = check_str(urldecode($xml->variables->end_epoch));
						$this->array[$key]['end_stamp'] = check_str(urldecode($xml->variables->end_stamp));
						$this->array[$key]['duration'] = check_str(urldecode($xml->variables->duration));
						$this->array[$key]['mduration'] = check_str(urldecode($xml->variables->mduration));
						$this->array[$key]['billsec'] = check_str(urldecode($xml->variables->billsec));
						$this->array[$key]['billmsec'] = check_str(urldecode($xml->variables->billmsec));
					//codecs
						$this->array[$key]['read_codec'] = check_str(urldecode($xml->variables->read_codec));
						$this->array[$key]['read_rate'] = check_str(urldecode($xml->variables->read_rate));
						$this->array[$key]['write_codec'] = check_str(urldecode($xml->variables->write_codec));
						$this->array[$key]['write_rate'] = check_str(urldecode($xml->variables->write_rate));
						$this->array[$key]['remote_media_ip'] = check_str(urldecode($xml->variables->remote_media_ip));
						$this->array[$key]['hangup_cause'] = check_str(urldecode($xml->variables->hangup_cause));
						$this->array[$key]['hangup_cause_q850'] = check_str(urldecode($xml->variables->hangup_cause_q850));
					//call center
						$this->array[$key]['cc_side'] = check_str(urldecode($xml->variables->cc_side));
						$this->array[$key]['cc_member_uuid'] = check_str(urldecode($xml->variables->cc_member_uuid));
						$this->array[$key]['cc_queue_joined_epoch'] = check_str(urldecode($xml->variables->cc_queue_joined_epoch));
						$this->array[$key]['cc_queue'] = check_str(urldecode($xml->variables->cc_queue));
						$this->array[$key]['cc_member_session_uuid'] = check_str(urldecode($xml->variables->cc_member_session_uuid));
						$this->array[$key]['cc_agent'] = check_str(urldecode($xml->variables->cc_agent));
						$this->array[$key]['cc_agent_type'] = check_str(urldecode($xml->variables->cc_agent_type));
						$this->array[$key]['waitsec'] = check_str(urldecode($xml->variables->waitsec));
					//app info
						$this->array[$key]['last_app'] = check_str(urldecode($xml->variables->last_app));
						$this->array[$key]['last_arg'] = check_str(urldecode($xml->variables->last_arg));
					//conference
						$this->array[$key]['conference_name'] = check_str(urldecode($xml->variables->conference_name));
						$this->array[$key]['conference_uuid'] = check_str(urldecode($xml->variables->conference_uuid));
						$this->array[$key]['conference_member_id'] = check_str(urldecode($xml->variables->conference_member_id));
					//call quality
						$rtp_audio_in_mos = check_str(urldecode($xml->variables->rtp_audio_in_mos));
						if (strlen($rtp_audio_in_mos) > 0) {
							$this->array[$key]['rtp_audio_in_mos'] = $rtp_audio_in_mos;
						}

					//store the call leg
						$this->array[$key]['leg'] = $leg;

					//store the call direction
						$this->array[$key]['direction'] = check_str(urldecode($xml->variables->call_direction));

					//store post dial delay, in milliseconds
						$this->array[$key]['pdd_ms'] = check_str(urldecode($xml->variables->progress_mediamsec) + urldecode($xml->variables->progressmsec));

					//get break down the date to year, month and day
						$tmp_time = strtotime($start_stamp);
						$tmp_year = date("Y", $tmp_time);
						$tmp_month = date("M", $tmp_time);
						$tmp_day = date("d", $tmp_time);

					//get the domain values from the xml
						$domain_name = check_str(urldecode($xml->variables->domain_name));
						$domain_uuid = check_str(urldecode($xml->variables->domain_uuid));

					//get the domain name
						if (strlen($domain_name) == 0) {
							$domain_name = check_str(urldecode($xml->variables->sip_req_host));
						}
						if (strlen($domain_name) == 0) {
							$presence_id = check_str(urldecode($xml->variables->presence_id));
							if (strlen($presence_id) > 0) {
								$presence_array = explode($presence_id);
								$domain_name = $presence_array[1];
							}
						}

					//dynamic cdr fields
						if (is_array($_SESSION['cdr']['field'])) {
							foreach ($_SESSION['cdr']['field'] as $field) {
								$fields = explode(",", $field);
								$field_name = end($fields);
								$this->fields[] = $field_name;
								if (count($fields) == 1) {
									$this->array[$key][$field_name] = urldecode($xml->variables->$fields[0]);
								}
								if (count($fields) == 2) {
									$this->array[$key][$field_name] = urldecode($xml->$fields[0]->$fields[1]);
								}
								if (count($fields) == 3) {
									$this->array[$key][$field_name] = urldecode($xml->$fields[0]->$fields[1]->$fields[2]);
								}
							}
						}

					//send the domain name to the cdr log
						//$this->log("\ndomain_name is `$domain_name`; domain_uuid is '$domain_uuid'\n");

					//get the domain_uuid with the domain_name
						if (strlen($domain_uuid) == 0) {
							$sql = "select domain_uuid from v_domains ";
							if (strlen($domain_name) == 0 && $context != 'public' && $context != 'default') {
								$sql .= "where domain_name = '".$context."' ";
							}
							else {
								$sql .= "where domain_name = '".$domain_name."' ";
							}
							$row = $this->db->query($sql)->fetch();
							$domain_uuid = $row['domain_uuid'];
						}

					//set values in the database
						if (strlen($domain_uuid) > 0) {
							$this->array[$key]['domain_uuid'] = $domain_uuid;
						}
						if (strlen($domain_name) > 0) {
							$this->array[$key]['domain_name'] = $domain_name;
						}

					//get the recording details
						if (strlen($xml->variables->record_session) > 0) {
							$record_path = urldecode($xml->variables->record_path);
							$record_name = urldecode($xml->variables->record_name);
							$record_length = urldecode($xml->variables->record_seconds);
						}
						elseif (!isset($record_path) && urldecode($xml->variables->last_app) == "record_session") {
							$record_path = dirname(urldecode($xml->variables->last_arg));
							$record_name = basename(urldecode($xml->variables->last_arg));
							$record_length = urldecode($xml->variables->record_seconds);
						}
						elseif (strlen($xml->variables->record_name) > 0) {
							$record_path = urldecode($xml->variables->record_path);
							$record_name = urldecode($xml->variables->record_name);
							$record_length = urldecode($xml->variables->duration);
						}
						elseif (strlen($xml->variables->sofia_record_file) > 0) {
							$record_path = dirname(urldecode($xml->variables->sofia_record_file));
							$record_name = basename(urldecode($xml->variables->sofia_record_file));
							$record_length = urldecode($xml->variables->record_seconds);
						}
						elseif (strlen($xml->variables->cc_record_filename) > 0) {
							$record_path = dirname(urldecode($xml->variables->cc_record_filename));
							$record_name = basename(urldecode($xml->variables->cc_record_filename));
							$record_length = urldecode($xml->variables->record_seconds);
						}
						elseif (strlen($xml->variables->api_on_answer) > 0) {
							$command = str_replace("\n", " ", urldecode($xml->variables->api_on_answer));
							$parts = explode(" ", $command);
							if ($parts[0] == "uuid_record") {
								$recording = $parts[3];
								$record_path = dirname($recording);
								$record_name = basename($recording);
								$record_length = urldecode($xml->variables->duration);
							}
						}
						elseif (strlen($xml->variables->current_application_data) > 0) {
							$commands = explode(",", urldecode($xml->variables->current_application_data));
							foreach ($commands as $command) {
								$cmd = explode("=", $command);
								if ($cmd[0] == "api_on_answer") {
									$a = explode("]", $cmd[1]);
									$command = str_replace("'", "", $a[0]);
									$parts = explode(" ", $command);
									if ($parts[0] == "uuid_record") {
										$recording = $parts[3];
										$record_path = dirname($recording);
										$record_name = basename($recording);
										$record_length = urldecode($xml->variables->duration);
									}
								}
							}
						}
						if (!isset($record_name)) {
							$bridge_uuid = urldecode($xml->variables->bridge_uuid);
							$path = $_SESSION['switch']['recordings']['dir'].'/'.$domain_name.'/archive/'.$start_year.'/'.$start_month.'/'.$start_day;
							if (file_exists($path.'/'.$bridge_uuid.'.wav')) {
								$record_path = $path;
								$record_name = $bridge_uuid.'.wav';
								$record_length = urldecode($xml->variables->duration);
							} elseif (file_exists($path.'/'.$bridge_uuid.'.mp3')) {
								$record_path = $path;
								$record_name = $bridge_uuid.'.mp3';
								$record_length = urldecode($xml->variables->duration);
							}
						}
						if (!isset($record_name)) {
							$path = $_SESSION['switch']['recordings']['dir'].'/'.$domain_name.'/archive/'.$start_year.'/'.$start_month.'/'.$start_day;
							if (file_exists($path.'/'.$uuid.'.wav')) {
								$record_path = $path;
								$record_name = $uuid.'.wav';
								$record_length = urldecode($xml->variables->duration);
							} elseif (file_exists($path.'/'.$uuid.'.mp3')) {
								$record_path = $path;
								$record_name = $uuid.'.mp3';
								$record_length = urldecode($xml->variables->duration);
							}
						}
							
					// Last check
						 if (!isset($record_name) || is_null ($record_name) || (strlen($record_name) == 0)) {
							$bridge_uuid = check_str(urldecode($xml->variables->bridge_uuid));
							$path = $_SESSION['switch']['recordings']['dir'].'/'.$domain_name.'/archive/'.$start_year.'/'.$start_month.'/'.$start_day;
							if (file_exists($path.'/'.$bridge_uuid.'.wav')) {
								$record_path = $path;
								$record_name = $bridge_uuid.'.wav';
								$record_length = urldecode($xml->variables->duration);
							} elseif (file_exists($path.'/'.$bridge_uuid.'.mp3')) {
								$record_path = $path;
								$record_name = $bridge_uuid.'.mp3';
								$record_length = urldecode($xml->variables->duration);
							} elseif (file_exists($path.'/'.$bridge_uuid.'.wav')) {
								$record_path = $path;
								$record_name = $bridge_uuid.'.wav';
								$record_length = urldecode($xml->variables->duration);
							} elseif (file_exists($path.'/'.$bridge_uuid.'.mp3')) {
								$record_path = $path;
								$record_name = $bridge_uuid.'.mp3';
								$record_length = urldecode($xml->variables->duration);
							}
						}
					
					//add the call recording
						if (isset($record_path) && isset($record_name) && file_exists($record_path.'/'.$record_name) && $record_length > 0) {
							//add to the xml cdr table
								$this->array[$key]['record_path'] = $record_path;
								$this->array[$key]['record_name'] = $record_name;
							//add to the call recordings table
								if (file_exists($_SERVER["PROJECT_ROOT"]."/app/call_recordings/app_config.php")) {
									//build the array
									$x = 0;
									$array['call_recordings'][$x]['call_recording_uuid'] = $uuid;
									$array['call_recordings'][$x]['domain_uuid'] = $domain_uuid;
									$array['call_recordings'][$x]['call_recording_name'] = $record_name;
									$array['call_recordings'][$x]['call_recording_path'] = $record_path;
									$array['call_recordings'][$x]['call_recording_length'] = $record_length;
									$array['call_recordings'][$x]['call_recording_date'] = urldecode($xml->variables->start_stamp);
									$array['call_recordings'][$x]['call_direction'] = urldecode($xml->variables->call_direction);
									//$array['call_recordings'][$x]['call_recording_description']= $row['zzz'];
									//$array['call_recordings'][$x]['call_recording_base64']= $row['zzz'];

									//add the temporary permission
									$p = new permissions;
									$p->add("call_recording_add", "temp");
									$p->add("call_recording_edit", "temp");

									$database = new database;
									$database->app_name = 'call_recordings';
									$database->app_uuid = '56165644-598d-4ed8-be01-d960bcb8ffed';
									$database->domain_uuid = $domain_uuid;
									$database->save($array);
									$message = $database->message;

									//remove the temporary permission
									$p->delete("call_recording_add", "temp");
									$p->delete("call_recording_edit", "temp");
									unset($array);
								}
						}

					//save to the database in xml format
						if ($_SESSION['cdr']['format']['text'] == "xml" && $_SESSION['cdr']['storage']['text'] == "db") {
							$this->array[$key]['xml'] = check_str($xml_string);
						}

					//save to the database in json format
						if ($_SESSION['cdr']['format']['text'] == "json" && $_SESSION['cdr']['storage']['text'] == "db") {
							$this->array[$key]['json'] = check_str(json_encode($xml));
						}

					//insert the check_str($extension_uuid)
						if (strlen($xml->variables->extension_uuid) > 0) {
							$this->array[$key]['extension_uuid'] = check_str(urldecode($xml->variables->extension_uuid));
						}

					//insert the values
						if (strlen($uuid) > 0) {
							if ($this->debug) {
								//$time5_insert = microtime(true);
								//echo $sql."<br />\n";
							}
							try {
								$error = "false";
								//$this->db->exec(check_sql($sql));
							}
							catch(PDOException $e) {
								$tmp_dir = $_SESSION['switch']['log']['dir'].'/xml_cdr/failed/';
								if(!file_exists($tmp_dir)) {
									event_socket_mkdir($tmp_dir);
								}
								if ($_SESSION['cdr']['format']['text'] == "xml") {
									$tmp_file = $uuid.'.xml';
									$fh = fopen($tmp_dir.'/'.$tmp_file, 'w');
									fwrite($fh, $xml_string);
								}
								else {
									$tmp_file = $uuid.'.json';
									$fh = fopen($tmp_dir.'/'.$tmp_file, 'w');
									fwrite($fh, json_encode($xml));
								}
								fclose($fh);
								if ($this->debug) {
									echo $e->getMessage();
								}
								$error = "true";
							}

							if ($_SESSION['cdr']['storage']['text'] == "dir" && $error != "true") {
								if (strlen($uuid) > 0) {
									$tmp_time = strtotime($start_stamp);
									$tmp_year = date("Y", $tmp_time);
									$tmp_month = date("M", $tmp_time);
									$tmp_day = date("d", $tmp_time);
									$tmp_dir = $_SESSION['switch']['log']['dir'].'/xml_cdr/archive/'.$tmp_year.'/'.$tmp_month.'/'.$tmp_day;
									if(!file_exists($tmp_dir)) {
										event_socket_mkdir($tmp_dir);
									}
									if ($_SESSION['cdr']['format']['text'] == "xml") {
										$tmp_file = $uuid.'.xml';
										$fh = fopen($tmp_dir.'/'.$tmp_file, 'w');
										fwrite($fh, $xml_string);
									}
									else {
										$tmp_file = $uuid.'.json';
										$fh = fopen($tmp_dir.'/'.$tmp_file, 'w');
										fwrite($fh, json_encode($xml));
									}
									fclose($fh);
								}
							}
							unset($error);

							//if ($this->debug) {
								//GLOBAL $insert_time,$insert_count;
								//$insert_time+=microtime(true)-$time5_insert; //add this current query.
								//$insert_count++;
							//}
						}
						unset($sql);
				} //if ($duplicate_uuid == false)
		} //function xml_array

		/**
		 * get xml from the filesystem and save it to the database
		 */
		public function read_files() {
			$xml_cdr_dir = $_SESSION['switch']['log']['dir'].'/xml_cdr';
			$dir_handle = opendir($xml_cdr_dir);
			$x = 0;
			while($file = readdir($dir_handle)) {
				if ($file != '.' && $file != '..') {
					if ( !is_dir($xml_cdr_dir . '/' . $file) ) {
						//get the leg of the call and the file prefix
							if (substr($file, 0, 2) == "a_") {
								$leg = "a";
								$file_prefix = substr($file, 2, 1);
							}
							else {
								$leg = "b";
								$file_prefix = substr($file, 0, 1);
							}

						//set the limit
							if (isset($_SERVER["argv"][1]) && is_numeric($_SERVER["argv"][1])) {
								$limit = $_SERVER["argv"][1];
							}
							else {
								$limit = 1;
							}

						//filter for specific files based on the file prefix
							if (isset($_SERVER["argv"][2])) {
								if (strpos($_SERVER["argv"][2], $file_prefix) !== FALSE) {
									$import = true;
								}
								else {
									$import = false;
								}
							}
							else {
								$import = true;
							}

						//import the call detail record
							if ($import) {
								//get the xml cdr string
									$xml_string = file_get_contents($xml_cdr_dir.'/'.$file);

								//parse the xml and insert the data into the db
									$this->xml_array($x, $leg, $xml_string);

								//delete the file after it has been imported
									unlink($xml_cdr_dir.'/'.$file);
							}

						//increment the value
							if ($import) {
								$x++;
							}

						//if limit exceeded exit the loop
							if ($limit == $x) {
								//echo "limit: $limit count: $x if\n";
								break;
							}
					}
				}
			}
			$this->save();
			closedir($dir_handle);
		}
		//$this->read_files();

		/**
		 * read the call detail records from the http post
		 */
		public function post() {
			if (isset($_POST["cdr"])) {
				//debug method
					if ($this->debug){
						print_r($_POST["cdr"]);
					}

				//authentication for xml cdr http post
					if (!defined('STDIN')) {
						if ($_SESSION["cdr"]["http_enabled"]["boolean"] == "true" && strlen($_SESSION["xml_cdr"]["username"]) == 0) {
							//get the contents of xml_cdr.conf.xml
								$conf_xml_string = file_get_contents($_SESSION['switch']['conf']['dir'].'/autoload_configs/xml_cdr.conf.xml');

							//parse the xml to get the call detail record info
								try {
									$conf_xml = simplexml_load_string($conf_xml_string);
								}
								catch(Exception $e) {
									echo $e->getMessage();
								}
								if (isset($conf_xml->settings->param)) foreach ($conf_xml->settings->param as $row) {
									if ($row->attributes()->name == "cred") {
										$auth_array = explode(":", $row->attributes()->value);
										//echo "username: ".$auth_array[0]."<br />\n";
										//echo "password: ".$auth_array[1]."<br />\n";
									}
									if ($row->attributes()->name == "url") {
										//check name is equal to url
									}
								}
						}
					}

				//if http enabled is set to false then deny access
					if (!defined('STDIN')) {
						if ($_SESSION["cdr"]["http_enabled"]["boolean"] == "false") {
							echo "access denied<br />\n";
							return;
						}
					}

				//check for the correct username and password
					if (!defined('STDIN')) {
						if ($_SESSION["cdr"]["http_enabled"]["boolean"] == "true") {
							if ($auth_array[0] == $_SERVER["PHP_AUTH_USER"] && $auth_array[1] == $_SERVER["PHP_AUTH_PW"]) {
								//echo "access granted<br />\n";
								$_SESSION["xml_cdr"]["username"] = $auth_array[0];
								$_SESSION["xml_cdr"]["password"] = $auth_array[1];
							}
							else {
								echo "access denied<br />\n";
								return;
							}
						}
					}

				//loop through all attribues
					//foreach($xml->settings->param[1]->attributes() as $a => $b) {
					//		echo $a,'="',$b,"\"<br />\n";
					//}

				//get the http post variable
					$xml_string = trim($_POST["cdr"]);

				//get the leg of the call
					if (substr($_REQUEST['uuid'], 0, 2) == "a_") {
						$leg = "a";
					}
					else {
						$leg = "b";
					}

				//log the xml cdr
					//xml_cdr_log("process cdr via post\n");

				//parse the xml and insert the data into the database
					$this->xml_array(0, $leg, $xml_string);
					$this->save();
			}
		}
		//$this->post();

		/**
		 * user summary returns an array
		 */
		public function user_summary() {

			//build the date range
				if (strlen($this->start_stamp_begin) > 0 || strlen($this->start_stamp_end) > 0) {
					unset($this->quick_select);
					if (strlen($this->start_stamp_begin) > 0 && strlen($this->start_stamp_end) > 0) {
						$sql_date_range .= " and start_stamp between '".$this->start_stamp_begin.":00.000' and '".$this->start_stamp_end.":59.999' \n";
					}
					else {
						if (strlen($this->start_stamp_begin) > 0) { $sql_date_range .= "AND start_stamp >= '".$this->start_stamp_begin.":00.000' \n"; }
						if (strlen($this->start_stamp_end) > 0) { $sql_date_range .= "AND start_stamp <= '".$this->start_stamp_end.":59.999' \n"; }
					}
				}
				else {
					switch ($this->quick_select) {
						case 1: $sql_date_range .= "AND start_stamp >= '".date('Y-m-d H:i:s.000', strtotime("-1 week"))."' \n"; break; //last 7 days
						case 2: $sql_date_range .= "AND start_stamp >= '".date('Y-m-d H:i:s.000', strtotime("-1 hour"))."' \n"; break; //last hour
						case 3: $sql_date_range .= "AND start_stamp >= '".date('Y-m-d')." "."00:00:00.000' \n"; break; //today
						case 4: $sql_date_range .= "AND start_stamp between '".date('Y-m-d',strtotime("-1 day"))." "."00:00:00.000' and '".date('Y-m-d',strtotime("-1 day"))." "."23:59:59.999' \n"; break; //yesterday
						case 5: $sql_date_range .= "AND start_stamp >= '".date('Y-m-d',strtotime("this week"))." "."00:00:00.000' \n"; break; //this week
						case 6: $sql_date_range .= "AND start_stamp >= '".date('Y-m-')."01 "."00:00:00.000' \n"; break; //this month
						case 7: $sql_date_range .= "AND start_stamp >= '".date('Y-')."01-01 "."00:00:00.000' \n"; break; //this year
					}
				}

			//calculate the summary data
				$sql = "SELECT \n";
				$sql .= "e.domain_uuid, \n";
				$sql .= "d.domain_name, \n";
				$sql .= "e.extension, \n";
				$sql .= "e.number_alias, \n";

				$sql .= "COUNT(*) \n";
				$sql .= "FILTER( \n";
				$sql .= " WHERE c.domain_uuid = e.domain_uuid \n";
				$sql .= " AND ((\n";
				$sql .= "   c.caller_id_number = e.extension \n";
				$sql .= "   OR \n";
				$sql .= "   c.destination_number = e.extension) \n";
				$sql .= "  OR ( \n";
				$sql .= "   e.number_alias IS NOT NULL and ( \n";
				$sql .= "    c.caller_id_number = e.number_alias \n";
				$sql .= "    OR \n";
				$sql .= "    c.destination_number = e.number_alias))) \n";
				$sql .= " AND (\n";
				$sql .= "  c.answer_stamp IS NOT NULL \n";
				$sql .= "  and \n";
				$sql .= "  c.bridge_uuid IS NOT NULL) \n";

				if ($this->include_internal) {
					$sql .= " AND (direction = 'inbound' OR direction = 'local')) \n";
				}
				else {
					$sql .= "AND direction = 'inbound') \n";
				}
				$sql .= "AS answered, \n";

				$sql .= "COUNT(*) \n";
				$sql .= "FILTER( \n";
				$sql .= " WHERE (( \n";
				$sql .= "   c.caller_id_number = e.extension \n";
				$sql .= "   OR \n";
				$sql .= "   c.destination_number = e.extension) \n";
				$sql .= "  OR (\n";
				$sql .= "   e.number_alias IS NOT NULL \n";
				$sql .= "   AND ( \n";
				$sql .= "    c.caller_id_number = e.number_alias \n";
				$sql .= "    OR \n";
				$sql .= "    c.destination_number = e.number_alias))) \n";
				$sql .= " AND ( \n";
				$sql .= "  c.answer_stamp IS NULL \n";
				$sql .= "  AND \n";
				$sql .= "  c.bridge_uuid IS NULL) \n";
				if ($this->include_internal) {
							$sql .= " AND (direction = 'inbound' OR direction = 'outbound'))";
				} else {
							$sql .= " AND direction = 'inbound')";
				}
				$sql .= "AS missed, \n";

				$sql .= "COUNT(*) \n";
				$sql .= "FILTER( \n";
				$sql .= " WHERE (( \n";
				$sql .= "   c.caller_id_number = e.extension \n";
				$sql .= "   OR \n";
				$sql .= "   c.destination_number = e.extension) \n";
				$sql .= "  OR ( \n";
				$sql .= "   e.number_alias IS NOT NULL \n";
				$sql .= "   AND ( \n";
				$sql .= "    c.caller_id_number = e.number_alias \n";
				$sql .= "    OR \n";
				$sql .= "    c.destination_number = e.number_alias))) \n";
				$sql .= " AND c.hangup_cause = 'NO_ANSWER' \n";
 				if ($this->include_internal) {
					$sql .= " AND (direction = 'inbound' OR direction = 'local') \n";
				}
				else { 
					$sql .= "AND direction = 'inbound' \n";
				}
				$sql .= ") AS no_answer, \n";

				$sql .= "COUNT(*) \n";
				$sql .= "FILTER( \n";
				$sql .= " WHERE (( \n";
				$sql .= "   c.caller_id_number = e.extension \n";
				$sql .= "   OR \n";
				$sql .= "   c.destination_number = e.extension) \n";
				$sql .= "  OR ( \n";
				$sql .= "   e.number_alias IS NOT NULL \n";
				$sql .= "   AND ( \n";
				$sql .= "    c.caller_id_number = e.number_alias \n";
				$sql .= "    OR \n";
				$sql .= "    c.destination_number = e.number_alias))) \n";
				$sql .= " AND \n";
				$sql .= " c.hangup_cause = 'USER_BUSY' \n";
				if ($this->include_internal) {
						$sql .= " AND (direction = 'inbound' OR direction = 'local')) \n";
				}
				else {
						$sql .= " AND direction = 'inbound') \n";
				}
				$sql .= "AS busy, \n";

				$sql .= "SUM(c.billsec) \n";
				$sql .= "FILTER ( \n";
				$sql .= " WHERE (( \n";
				$sql .= "   c.caller_id_number = e.extension \n";
				$sql .= "   OR \n";
				$sql .= "   c.destination_number = e.extension) \n";
				$sql .= "  OR ( \n";
				$sql .= "   e.number_alias IS NOT NULL \n";
				$sql .= "   AND ( \n";
				$sql .= "    c.caller_id_number = e.number_alias \n";
				$sql .= "    OR \n";
				$sql .= "    c.destination_number = e.number_alias))) \n";
				if ($this->include_internal) {
						$sql .= " AND (direction = 'inbound' OR direction = 'outbound') \n";
				}
				$sql .= " ) / \n";
				$sql .= "COUNT(*) \n";
				$sql .= "FILTER ( \n";
				$sql .= " WHERE (( \n";
				$sql .= "   c.caller_id_number = e.extension \n";
				$sql .= "   OR \n";
				$sql .= "   c.destination_number = e.extension) \n";
				$sql .= "  OR ( \n";
				$sql .= "   e.number_alias IS NOT NULL \n";
				$sql .= "   AND ( \n";
				$sql .= "    c.caller_id_number = e.number_alias \n";
				$sql .= "    OR \n";
				$sql .= "    c.destination_number = e.number_alias))) \n";
				if ($this->include_internal) {
						$sql .= " AND (direction = 'inbound' OR direction = 'outbound') \n";
				}
				$sql .= " ) AS aloc, \n";

				$sql .= "COUNT(*) \n";
				$sql .= "FILTER ( \n";
				$sql .= " WHERE (( \n";
				$sql .= "   c.caller_id_number = e.extension \n";
				$sql .= "   OR \n";
				$sql .= "   c.destination_number = e.extension) \n";
				$sql .= "  OR ( \n";
				$sql .= "   e.number_alias IS NOT NULL \n";
				$sql .= "   AND ( \n";
				$sql .= "    c.caller_id_number = e.number_alias \n";
				$sql .= "    OR \n";
				$sql .= "    c.destination_number = e.number_alias))) \n";
				if ($this->include_internal) {
						$sql .= " AND (direction = 'inbound' OR direction = 'local')) \n";
				}
				else {
						$sql .= " AND direction = 'inbound') \n";
				}
				$sql .= "AS inbound_calls, \n";

				$sql .= "SUM(c.billsec) \n";
				$sql .= "FILTER ( \n";
				$sql .= " WHERE (( \n";
				$sql .= "   c.caller_id_number = e.extension \n";
				$sql .= "   OR \n";
				$sql .= "   c.destination_number = e.extension) \n";
				$sql .= "  OR ( \n";
				$sql .= "   e.number_alias IS NOT NULL \n";
				$sql .= "   AND ( \n";
				$sql .= "    c.caller_id_number = e.number_alias \n";
				$sql .= "    OR \n";
				$sql .= "    c.destination_number = e.number_alias))) \n";
				if ($this->include_internal) {
						$sql .= " AND (direction = 'inbound' OR direction = 'local')) \n";
				}
				else {
						$sql .= " AND direction = 'inbound') \n";
				}
				$sql .= "AS inbound_duration, \n";

				$sql .= "COUNT(*) \n";
				$sql .= "FILTER ( \n";
				$sql .= " WHERE c.extension_uuid = e.extension_uuid \n";
				$sql .= " AND c.direction = 'outbound' \n";
				$sql .= ") \n";
				$sql .= "AS outbound_calls, \n";

				$sql .= "SUM(c.billsec) \n";
				$sql .= "FILTER ( \n";
				$sql .= " WHERE c.extension_uuid = e.extension_uuid \n";
				$sql .= " AND c.direction = 'outbound' \n";
				$sql .= ") \n";
				$sql .= "AS outbound_duration, \n";

				$sql .= "e.description \n";

				$sql .= "FROM v_extensions AS e, v_domains AS d, \n";
				$sql .= "( SELECT \n";
				$sql .= " domain_uuid, \n";
				$sql .= " extension_uuid, \n";
				$sql .= " caller_id_number, \n";
				$sql .= " destination_number, \n";
				$sql .= " answer_stamp, \n";
				$sql .= " bridge_uuid, \n";
				$sql .= " direction, \n";
				$sql .= " start_stamp, \n";
				$sql .= " hangup_cause, \n";
				$sql .= " billsec \n";
				$sql .= " FROM v_xml_cdr \n";
				$sql .= " WHERE domain_uuid = '".$this->domain_uuid."' \n";
				$sql .= $sql_date_range;
				$sql .= ") AS c \n";

				$sql .= "WHERE \n";
				$sql .= "d.domain_uuid = e.domain_uuid \n";
				if (!($_GET['showall'] && permission_exists('xml_cdr_all'))) {
						$sql .= "AND e.domain_uuid = '".$this->domain_uuid."' \n";
				}
				$sql .= "GROUP BY e.extension, e.domain_uuid, d.domain_uuid, e.number_alias, e.description \n";
				$sql .= "ORDER BY extension ASC \n";
				$prep_statement = $this->db->prepare(check_sql($sql));
				$prep_statement->execute();
				$summary = $prep_statement->fetchAll(PDO::FETCH_NAMED);

			//return the array
				return $summary;
		}

		/**
		 * download the recordings
		 */
		public function download() {
			if (permission_exists('xml_cdr_view')) {

				//cache limiter
					session_cache_limiter('public');

				//get call recording from database
					$uuid = check_str($_GET['id']);
					if ($uuid != '') {
						$sql = "select record_name, record_path from v_xml_cdr ";
						$sql .= "where xml_cdr_uuid = '".$uuid."' ";
						//$sql .= "and domain_uuid = '".$domain_uuid."' \n";
						$prep_statement = $this->db->prepare($sql);
						$prep_statement->execute();
						$xml_cdr = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
						if (is_array($xml_cdr)) {
							foreach($xml_cdr as &$row) {
								$record_name = $row['record_name'];
								$record_path = $row['record_path'];
								break;
							}
						}
						unset ($sql, $prep_statement, $xml_cdr);
					}

				//build full path
					$record_file = $record_path . '/' . $record_name;

				//download the file
					if (file_exists($record_file)) {
						//content-range
						//if (isset($_SERVER['HTTP_RANGE']))  {
						//	range_download($record_file);
						//}
						ob_clean();
						$fd = fopen($record_file, "rb");
						if ($_GET['t'] == "bin") {
							header("Content-Type: application/force-download");
							header("Content-Type: application/octet-stream");
							header("Content-Type: application/download");
							header("Content-Description: File Transfer");
						}
						else {
							$file_ext = substr($record_name, -3);
							if ($file_ext == "wav") {
								header("Content-Type: audio/x-wav");
							}
							if ($file_ext == "mp3") {
								header("Content-Type: audio/mpeg");
							}
							if ($file_ext == "ogg") {
								header("Content-Type: audio/ogg");
							}
						}
						header('Content-Disposition: attachment; filename="'.$record_name.'"');
						header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
						header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
						// header("Content-Length: " . filesize($record_file));
						ob_clean();
						fpassthru($fd);
					}
			}
		} //end download method

	} //end the class
}
/*
//example use
	$cdr = new xml_cdr;
	$cdr->read_files();
*/
?>
