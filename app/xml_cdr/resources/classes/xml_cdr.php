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
	Portions created by the Initial Developer are Copyright (C) 2016-2023
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
		public $fields;
		public $setting;
		public $domain_uuid;
		public $call_details;
		public $call_direction;
		public $billsec;
		private $username;
		private $password;
		private $json;

		/**
		 * user summary
		 */

		public $quick_select;
		public $start_stamp_begin;
		public $start_stamp_end;
		public $include_internal;
		public $extensions;

		/**
		 * delete method
		 */
		private $app_name;
		private $app_uuid;
		private $permission_prefix;
		private $list_page;
		private $table;
		private $uuid_prefix;

		/**
		 * Used by read_files, xml_array, and save methods
		 */
		public $file;

		/**
		 * Called when the object is created
		 */
		public function __construct() {
			//connect to the database if not connected
			if (!$this->db) {
				$database = new database;
				$database->connect();
				$this->db = $database->db;
			}

			//get the email queue settings
			$this->setting = new settings();

			//assign private variables (for delete method)
			$this->app_name = 'xml_cdr';
			$this->app_uuid = '4a085c51-7635-ff03-f67b-86e834422848';
			$this->permission_prefix = 'xml_cdr_';
			$this->list_page = 'xml_cdr.php';
			$this->table = 'xml_cdr';
			$this->uuid_prefix = 'xml_cdr_';
		}

		/**
		 * cdr process logging
		 */
		public function log($message) {

			//save the log if enabled is true
			if ($this->setting->get('log', 'enabled') == 'true') {

				//save the log to the php error log
				if ($this->setting->get('log', 'type') == 'error_log') {
					error_log($message);
				}

				//save the log to the syslog server
				if ($this->setting->get('log', 'type') == 'syslog') {
					openlog("XML CDR", LOG_PID | LOG_PERROR, LOG_LOCAL0);
					syslog(LOG_WARNING, $message);
					closelog();
				}

				//save the log to the file system
				if ($this->setting->get('log', 'text') == 'file') {
					$fp = fopen($this->setting->get('server', 'temp').'/xml_cdr.log', 'a+');
					if (!$fp) {
						return;
					}
					fwrite($fp, $message);
					fclose($fp);
				}

			}
		}

		/**
		 * cdr fields in the database schema
		 */
		public function fields() {

			$this->fields[] = "xml_cdr_uuid";
			$this->fields[] = "domain_uuid";
			$this->fields[] = "provider_uuid";
			$this->fields[] = "extension_uuid";
			$this->fields[] = "sip_call_id";
			$this->fields[] = "domain_name";
			$this->fields[] = "accountcode";
			$this->fields[] = "direction";
			$this->fields[] = "default_language";
			$this->fields[] = "context";
			$this->fields[] = "call_flow";
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
			$this->fields[] = "record_length";
			$this->fields[] = "leg";
			$this->fields[] = "originating_leg_uuid";
			$this->fields[] = "pdd_ms";
			$this->fields[] = "rtp_audio_in_mos";
			$this->fields[] = "last_app";
			$this->fields[] = "last_arg";
			$this->fields[] = "voicemail_message";
			$this->fields[] = "call_center_queue_uuid";
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
			$this->fields[] = "cc_queue_canceled_epoch";
			$this->fields[] = "cc_cancel_reason";
			$this->fields[] = "cc_cause";
			$this->fields[] = "waitsec";
			$this->fields[] = "conference_name";
			$this->fields[] = "conference_uuid";
			$this->fields[] = "conference_member_id";
			$this->fields[] = "digits_dialed";
			$this->fields[] = "pin_number";
			$this->fields[] = "status";
			$this->fields[] = "hangup_cause";
			$this->fields[] = "hangup_cause_q850";
			$this->fields[] = "sip_hangup_disposition";

			if (!empty($this->setting->get('cdr', 'field'))) {
				foreach ($this->setting->get('cdr', 'field') as $field) {
					$field_name = end(explode(',', $field));
					$this->fields[] = $field_name;
				}
			}
			$this->fields = array_unique($this->fields);
		}

		/**
		 * save to the database
		 */
		public function save() {

			$this->fields();
			$field_count = sizeof($this->fields);
			//$field_count = sizeof($this->fields);

			if (!empty($this->array)) {
				foreach ($this->array as $row) {
					//build the array
					if (isset($this->fields)) {
						foreach ($this->fields as $field) {
							$field = preg_replace('#[^a-zA-Z0-9_\-]#', '', $field);
							if (isset($row[$field]) && !empty($row[$field])) {
								$array['xml_cdr'][0][$field] = $row[$field];
							}
						}
					}

					//set the directory
					if (!empty($this->setting->get('switch', 'log'))) {
						$xml_cdr_dir = $this->setting->get('switch', 'log').'/xml_cdr';
					}

					//add the temporary permission
					$p = new permissions;
					$p->add("xml_cdr_add", "temp");
					$p->add("xml_cdr_edit", "temp");

					//save the call details record to the database
					$database = new database;
					$database->app_name = 'xml_cdr';
					$database->app_uuid = '4a085c51-7635-ff03-f67b-86e834422848';
					//$database->domain_uuid = $domain_uuid;
					$response = $database->save($array, false);
					if ($response['code'] == '200') {
						//saved to the database successfully delete the database file
						if (!empty($xml_cdr_dir)) {
							if (file_exists($xml_cdr_dir.'/'.$this->file)) {
								unlink($xml_cdr_dir.'/'.$this->file);
							}
						}
					}
					else {
						//move the file to a failed directory
						if (!empty($xml_cdr_dir)) {
							if (!file_exists($xml_cdr_dir.'/failed')) {
								if (!mkdir($xml_cdr_dir.'/failed', 0660, true)) {
									die('Failed to create '.$xml_cdr_dir.'/failed');
								}
							}
							rename($xml_cdr_dir.'/'.$this->file, $xml_cdr_dir.'/failed/'.$this->file);
						}

						//send an error message
						echo 'failed file moved to '.$xml_cdr_dir.'/failed/'.$this->file;
					}

					//clear the array
					unset($this->array);

					//debug results
					$this->log(print_r($database->message, true));

					//remove the temporary permission
					$p->delete("xml_cdr_add", "temp");
					$p->delete("xml_cdr_edit", "temp");
					unset($array);
				}
			}

		}

		/**
		 * process method converts the xml cdr and adds it to the database
		 */
		public function xml_array($key, $leg, $xml_string) {

			//xml string is empty
				if (empty($xml_string)) {
					return false;
				}

			//fix the xml by escaping the contents of <sip_full_XXX>
				if (defined('STDIN')) {
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

			//remove invalid numeric xml tags
				$xml_string = preg_replace('/<\/?\d+>/', '', $xml_string);

			//disable xml entities
				if (PHP_VERSION_ID < 80000) { libxml_disable_entity_loader(true); }

			//load the string into an xml object
				$xml = simplexml_load_string($xml_string, 'SimpleXMLElement', LIBXML_NOCDATA);
				if ($xml === false) {
					//set the directory
					if (!empty($this->setting->get('switch', 'log'))) {
						$xml_cdr_dir = $this->setting->get('switch', 'log').'/xml_cdr';
					}

					//failed to load the XML, move the XML file to the failed directory
					if (!empty($xml_cdr_dir)) {
						if (!file_exists($xml_cdr_dir.'/failed')) {
							if (!mkdir($xml_cdr_dir.'/failed', 0660, true)) {
								die('Failed to create '.$xml_cdr_dir.'/failed');
							}
						}
						rename($xml_cdr_dir.'/'.$this->file, $xml_cdr_dir.'/failed/'.$this->file);
					}

					//return without saving the invalid xml
					return false;
				}

			//check for duplicate call uuid's
				$duplicate_uuid = false;
				$uuid = urldecode($xml->variables->uuid);
				if ($uuid != null && is_uuid($uuid)) {
					$sql = "select count(xml_cdr_uuid) ";
					$sql .= "from v_xml_cdr ";
					$sql .= "where xml_cdr_uuid = :xml_cdr_uuid ";
					$parameters['xml_cdr_uuid'] = $uuid;
					$database = new database;
					$count = $database->select($sql, $parameters, 'column');
					if ($count > 0) {
						//duplicate uuid detected
						$duplicate_uuid = true;

						//remove the file as the record already exists in the database
						if (!empty($this->setting->get('switch', 'log'))) {
							$xml_cdr_dir = $this->setting->get('switch', 'log').'/xml_cdr';
							if (file_exists($xml_cdr_dir.'/'.$this->file)) {
								unlink($xml_cdr_dir.'/'.$this->file);
							}
						}
					}
					unset($sql, $parameters);
				}

			//set the call_direction
				if (isset($xml->variables->call_direction)) {
					$call_direction = urldecode($xml->variables->call_direction);
				}

			//set the accountcode
				if (isset($xml->variables->accountcode)) {
					$accountcode = urldecode($xml->variables->accountcode);
				}

			//process data if the call detail record is not a duplicate
				if ($duplicate_uuid == false && is_uuid($uuid)) {

					//get the caller ID from call flow caller profile
						$i = 0;
						foreach ($xml->callflow as $row) {
							if ($i == 0) {
								$caller_id_name = urldecode($row->caller_profile->caller_id_name);
								$caller_id_number = urldecode($row->caller_profile->caller_id_number);
							}
							$i++;
						}
						unset($i);

					//get the caller ID from variables
						if (!isset($caller_id_number) && isset($xml->variables->caller_id_name)) {
							$caller_id_name = urldecode($xml->variables->caller_id_name);
						}
						if (!isset($caller_id_number) && isset($xml->variables->caller_id_number)) {
							$caller_id_number = urldecode($xml->variables->caller_id_number);
						}
						if (!isset($caller_id_number) && isset($xml->variables->sip_from_user)) {
							$caller_id_number = urldecode($xml->variables->sip_from_user);
						}

					//if the origination caller id name and number are set then use them
						if (isset($xml->variables->origination_caller_id_name)) {
							$caller_id_name = urldecode($xml->variables->origination_caller_id_name);
						}
						if (isset($xml->variables->origination_caller_id_number)) {
							$caller_id_number = urldecode($xml->variables->origination_caller_id_number);
						}

					//if the call is outbound use the external caller ID
						if (isset($xml->variables->effective_caller_id_name)) {
							$caller_id_name = urldecode($xml->variables->effective_caller_id_name);
						}

						if (isset($xml->variables->origination_caller_id_name)) {
							$caller_id_name = urldecode($xml->variables->origination_caller_id_name);
						}

						if (isset($xml->variables->origination_caller_id_number)) {
							$caller_id_number = urldecode($xml->variables->origination_caller_id_number);
						}

						if (urldecode($call_direction) == 'outbound' && isset($xml->variables->effective_caller_id_number)) {
							$caller_id_number = urldecode($xml->variables->effective_caller_id_number);
						}

					//if the sip_from_domain and domain_name are not the same then original call direction was inbound
						//when an inbound call is forward the call_direction is set to inbound and then updated to outbound
						//use sip_from_display and sip_from_user to get the original caller ID instead of the updated caller ID info from the forward
						if (isset($xml->variables->sip_from_domain) && urldecode($xml->variables->sip_from_domain) != urldecode($xml->variables->domain_name)) {
							if (isset($xml->variables->sip_from_display)) {
								$caller_id_name = urldecode($xml->variables->sip_from_display);
							}
							if (isset($xml->variables->sip_from_user)) {
								$caller_id_number = urldecode($xml->variables->sip_from_user);
							}
						}

					//get the values from the callflow.
						$i = 0;
						foreach ($xml->callflow as $row) {
							if ($i == 0) {
								$context = urldecode($row->caller_profile->context);
								$destination_number = urldecode($row->caller_profile->destination_number);
								$network_addr = urldecode($row->caller_profile->network_addr);
							}
							$i++;
						}
						unset($i);

					//if last_sent_callee_id_number is set use it for the destination_number
						if (!empty($xml->variables->last_sent_callee_id_number)) {
							$destination_number = urldecode($xml->variables->last_sent_callee_id_number);
						}

					//remove the provider prefix
						if (isset($xml->variables->provider_prefix) && isset($destination_number)) {
							$provider_prefix = $xml->variables->provider_prefix;
							if ($provider_prefix == substr($destination_number, 0, strlen($provider_prefix))) {
								$destination_number = substr($destination_number, strlen($provider_prefix), strlen($destination_number));
							}
						}

					//get the caller_destination
						if (isset($xml->variables->caller_destination) ) {
							$caller_destination = urldecode($xml->variables->caller_destination);
						}
						if (isset($xml->variables->sip_h_caller_destination) ) {
							$caller_destination = urldecode($xml->variables->sip_h_caller_destination);
						}
						if (!isset($caller_destination) && isset($xml->variables->dialed_user)) {
							$caller_destination = urldecode($xml->variables->dialed_user);
						}

					//set missed calls
						if (isset($xml->variables->missed_call)) {
							//marked as missed
							$missed_call = $xml->variables->missed_call;
						}
						if (isset($xml->variables->billsec) && $xml->variables->billsec > 0) {
							//answered call
							$missed_call = 'false';
						}
						if (isset($xml->variables->cc_side) && $xml->variables->cc_side == 'agent') {
							//call center
							$missed_call = 'false';
						}
						if (isset($xml->variables->fax_success)) {
							//fax server
							$missed_call = 'false';
						}
						if (isset($xml->variables->hangup_cause) && $xml->variables->hangup_cause == 'LOSE_RACE') {
							//ring group or multi destination bridge statement
							$missed_call = 'false';
						}
						if (isset($xml->variables->hangup_cause) && $xml->variables->hangup_cause == 'NO_ANSWER' && isset($xml->variables->originating_leg_uuid)) {
							//ring group or multi destination bridge statement
							$missed_call = 'false';
						}
						if (isset($xml->variables->destination_number) && substr($xml->variables->destination_number, 0, 3) == '*99') {
							//voicemail
							$missed_call = 'true';
						}
						if (isset($xml->variables->voicemail_answer_stamp) && !empty($xml->variables->voicemail_answer_stamp)) {
							//voicemail
							$missed_call = 'true';
						}
						if (isset($xml->variables->cc_side) && $xml->variables->cc_side == 'member'
							&& isset($xml->variables->cc_cause) && $xml->variables->cc_cause == 'cancel') {
							//call center
							$missed_call = 'true';
						}

					//read the bridge statement variables
						if (isset($xml->variables->last_app)) {
							if (urldecode($xml->variables->last_app) == 'bridge') {
								//get the variables from inside the { and } brackets
								preg_match('/^\{([^}]+)\}/', urldecode($xml->variables->last_arg), $matches);

								//create a variables array from the comma delimitted string
								$bridge_variables = explode(",", $matches[1]);

								//set bridge variables as variables
								$x = 0;
								if (!empty($bridge_variables)) {
									foreach($bridge_variables as $variable) {
										$pairs = explode("=", $variable);
										$name = $pairs[0];
										$$name = $pairs[1];
										$x++;
									}
								}
							}
						}

					//get the last bridge_uuid from the call to preserve previous behavior
						foreach ($xml->variables->bridge_uuids as $bridge) {
							$last_bridge = urldecode($bridge);
						}

					//determine the call status
						$failed_array = array(
						"CALL_REJECTED",
						"CHAN_NOT_IMPLEMENTED",
						"DESTINATION_OUT_OF_ORDER",
						"EXCHANGE_ROUTING_ERROR",
						"INCOMPATIBLE_DESTINATION",
						"INVALID_NUMBER_FORMAT",
						"MANDATORY_IE_MISSING",
						"NETWORK_OUT_OF_ORDER",
						"NORMAL_TEMPORARY_FAILURE",
						"NORMAL_UNSPECIFIED",
						"NO_ROUTE_DESTINATION",
						"RECOVERY_ON_TIMER_EXPIRE",
						"REQUESTED_CHAN_UNAVAIL",
						"SUBSCRIBER_ABSENT",
						"SYSTEM_SHUTDOWN",
						"UNALLOCATED_NUMBER"
						);
						if ($xml->variables->billsec > 0) {
							$status = 'answered';
						}
						if ($xml->variables->hangup_cause == 'NO_ANSWER') {
							$status = 'no_answer';
						}
						if ($missed_call == 'true') {
							$status = 'missed';
						}
						if (substr($destination_number, 0, 3) == '*99') {
							$status = 'voicemail';
						}
						if (isset($xml->variables->voicemail_answer_stamp)) {
							$status = 'voicemail';
						}
						if (isset($xml->variables->voicemail_id)) {
							$status = 'voicemail';
						}
						if ($xml->variables->hangup_cause == 'ORIGINATOR_CANCEL') {
							$status = 'cancelled';
						}
						if ($xml->variables->hangup_cause == 'USER_BUSY') {
							$status = 'busy';
						}
						if (in_array($xml->variables->hangup_cause, $failed_array)) {
							$status = 'failed';
						}
						if (!isset($status) && in_array($xml->variables->last_bridge_hangup_cause, $failed_array)) {
							$status = 'failed';
						}
						if ($xml->variables->cc_side == 'agent' && $xml->variables->billsec == 0) {
							$status = 'no_answer';
						}
						if (!isset($status)  && $xml->variables->billsec == 0) {
							$status = 'no_answer';
						}

					//set the provider id
						if (isset($xml->variables->provider_uuid)) {
							$this->array[$key]['provider_uuid'] = urldecode($xml->variables->provider_uuid);
						}

					//misc
						$key = 0;
						$uuid = urldecode($xml->variables->uuid);
						$this->array[$key]['xml_cdr_uuid'] = $uuid;
						$this->array[$key]['destination_number'] = $destination_number;
						$this->array[$key]['sip_call_id'] = urldecode($xml->variables->sip_call_id);
						$this->array[$key]['source_number'] = urldecode($xml->variables->effective_caller_id_number);
						$this->array[$key]['user_context'] = urldecode($xml->variables->user_context);
						$this->array[$key]['network_addr'] = urldecode($xml->variables->sip_network_ip);
						$this->array[$key]['missed_call'] = $missed_call;
						$this->array[$key]['caller_id_name'] = $caller_id_name;
						$this->array[$key]['caller_id_number'] = $caller_id_number;
						$this->array[$key]['caller_destination'] = $caller_destination;
						$this->array[$key]['accountcode'] = urldecode($accountcode);
						$this->array[$key]['default_language'] = urldecode($xml->variables->default_language);
						$this->array[$key]['bridge_uuid'] = urldecode($xml->variables->bridge_uuid) ?: $last_bridge;
						//$this->array[$key]['digits_dialed'] = urldecode($xml->variables->digits_dialed);
						$this->array[$key]['sip_hangup_disposition'] = urldecode($xml->variables->sip_hangup_disposition);
						$this->array[$key]['pin_number'] = urldecode($xml->variables->pin_number);
						$this->array[$key]['status'] = $status;

					//time
						$start_epoch = urldecode($xml->variables->start_epoch);
						$this->array[$key]['start_epoch'] = $start_epoch;
						$this->array[$key]['start_stamp'] = is_numeric($start_epoch) ? date('c', $start_epoch) : null;
						$answer_epoch = urldecode($xml->variables->answer_epoch);
						$this->array[$key]['answer_epoch'] = $answer_epoch;
						$this->array[$key]['answer_stamp'] = is_numeric($answer_epoch) ? date('c', $answer_epoch) : null;
						$end_epoch = urldecode($xml->variables->end_epoch);
						$this->array[$key]['end_epoch'] = $end_epoch;
						$this->array[$key]['end_stamp'] = is_numeric($end_epoch) ? date('c', $end_epoch) : null;
						$this->array[$key]['duration'] = urldecode($xml->variables->billsec);
						$this->array[$key]['mduration'] = urldecode($xml->variables->billmsec);
						$this->array[$key]['billsec'] = urldecode($xml->variables->billsec);
						$this->array[$key]['billmsec'] = urldecode($xml->variables->billmsec);

					//codecs
						$this->array[$key]['read_codec'] = urldecode($xml->variables->read_codec);
						$this->array[$key]['read_rate'] = urldecode($xml->variables->read_rate);
						$this->array[$key]['write_codec'] = urldecode($xml->variables->write_codec);
						$this->array[$key]['write_rate'] = urldecode($xml->variables->write_rate);
						$this->array[$key]['remote_media_ip'] = urldecode($xml->variables->remote_media_ip);
						$this->array[$key]['hangup_cause'] = urldecode($xml->variables->hangup_cause);
						$this->array[$key]['hangup_cause_q850'] = urldecode($xml->variables->hangup_cause_q850);

					//store the call direction
						$this->array[$key]['direction'] = urldecode($call_direction);

					//call center
						if ($xml->variables->cc_member_uuid == '_undef_') { $xml->variables->cc_member_uuid = ''; }
						if ($xml->variables->cc_member_session_uuid == '_undef_') { $xml->variables->cc_member_session_uuid = ''; }
						if ($xml->variables->cc_agent_uuid == '_undef_') { $xml->variables->cc_agent_uuid = ''; }
						if ($xml->variables->call_center_queue_uuid == '_undef_') { $xml->variables->call_center_queue_uuid = ''; }
						if ($xml->variables->cc_queue_joined_epoch == '_undef_') { $xml->variables->cc_queue_joined_epoch = ''; }
						$this->array[$key]['cc_side'] = urldecode($xml->variables->cc_side);
						$this->array[$key]['cc_member_uuid'] = urldecode($xml->variables->cc_member_uuid);
						$this->array[$key]['cc_queue'] = urldecode($xml->variables->cc_queue);
						$this->array[$key]['cc_member_session_uuid'] = urldecode($xml->variables->cc_member_session_uuid);
						$this->array[$key]['cc_agent_uuid'] = urldecode($xml->variables->cc_agent_uuid);
						$this->array[$key]['cc_agent'] = urldecode($xml->variables->cc_agent);
						$this->array[$key]['cc_agent_type'] = urldecode($xml->variables->cc_agent_type);
						$this->array[$key]['cc_agent_bridged'] = urldecode($xml->variables->cc_agent_bridged);
						$this->array[$key]['cc_queue_joined_epoch'] = urldecode($xml->variables->cc_queue_joined_epoch);
						$this->array[$key]['cc_queue_answered_epoch'] = urldecode($xml->variables->cc_queue_answered_epoch);
						$this->array[$key]['cc_queue_terminated_epoch'] = urldecode($xml->variables->cc_queue_terminated_epoch);
						$this->array[$key]['cc_queue_canceled_epoch'] = urldecode($xml->variables->cc_queue_canceled_epoch);
						$this->array[$key]['cc_cancel_reason'] = urldecode($xml->variables->cc_cancel_reason);
						$this->array[$key]['cc_cause'] = urldecode($xml->variables->cc_cause);
						$this->array[$key]['waitsec'] = urldecode($xml->variables->waitsec);
						if (urldecode($xml->variables->cc_side) == 'agent') {
							$this->array[$key]['direction'] = 'inbound';
						}
						$this->array[$key]['cc_queue'] = urldecode($xml->variables->cc_queue);
						$this->array[$key]['call_center_queue_uuid'] = urldecode($xml->variables->call_center_queue_uuid);

					//app info
						$this->array[$key]['last_app'] = urldecode($xml->variables->last_app);
						$this->array[$key]['last_arg'] = urldecode($xml->variables->last_arg);

					//voicemail message success
						if (!empty($xml->variables->voicemail_answer_stamp) && $xml->variables->voicemail_message_seconds > 0){
							$this->array[$key]['voicemail_message'] = "true";
						}
						else { //if ($xml->variables->voicemail_action == "save") {
							$this->array[$key]['voicemail_message'] = "false";
						}

					//conference
						$this->array[$key]['conference_name'] = urldecode($xml->variables->conference_name);
						$this->array[$key]['conference_uuid'] = urldecode($xml->variables->conference_uuid);
						$this->array[$key]['conference_member_id'] = urldecode($xml->variables->conference_member_id);

					//call quality
						$rtp_audio_in_mos = urldecode($xml->variables->rtp_audio_in_mos);
						if (!empty($rtp_audio_in_mos)) {
							$this->array[$key]['rtp_audio_in_mos'] = $rtp_audio_in_mos;
						}

					//store the call leg
						$this->array[$key]['leg'] = $leg;

					//store the originating leg uuid
						$this->array[$key]['originating_leg_uuid'] = urldecode($xml->variables->originating_leg_uuid);

					//store post dial delay, in milliseconds
						$this->array[$key]['pdd_ms'] = urldecode((int)$xml->variables->progress_mediamsec) + (int)urldecode($xml->variables->progressmsec);

					//get break down the date to year, month and day
						$start_stamp = urldecode($xml->variables->start_stamp);
						$start_time = strtotime($start_stamp);
						$start_year = date("Y", $start_time);
						$start_month = date("M", $start_time);
						$start_day = date("d", $start_time);

					//get the domain values from the xml
						$domain_name = urldecode($xml->variables->domain_name);
						$domain_uuid = urldecode($xml->variables->domain_uuid);

					//get the domain name
						if (empty($domain_name)) {
							$domain_name = urldecode($xml->variables->dialed_domain);
						}
						if (empty($domain_name)) {
							$domain_name = urldecode($xml->variables->sip_invite_domain);
						}
						if (empty($domain_name)) {
							$domain_name = urldecode($xml->variables->sip_req_host);
						}
						if (empty($domain_name)) {
							$presence_id = urldecode($xml->variables->presence_id);
							if (!empty($presence_id)) {
								$presence_array = explode($presence_id, '%40');
								$domain_name = $presence_array[1];
							}
						}

					//dynamic cdr fields
						if (!empty($this->setting->get('cdr', 'field'))) {
							foreach ($this->setting->get('cdr', 'field') as $field) {
								$fields = explode(",", $field);
								$field_name = end($fields);
								$this->fields[] = $field_name;
								if (!isset($this->array[$key][$field_name])) {
									if (count($fields) == 1) {
										$this->array[$key][$field_name] = urldecode($xml->variables->{$fields[0]});
									}
									if (count($fields) == 2) {
										$this->array[$key][$field_name] = urldecode($xml->{$fields[0]}->{$fields[1]});
									}
									if (count($fields) == 3) {
										$this->array[$key][$field_name] = urldecode($xml->{$fields[0]}->{$fields[1]}->{$fields[2]});
									}
									if (count($fields) == 4) {
										$this->array[$key][$field_name] = urldecode($xml->{$fields[0]}->{$fields[1]}->{$fields[2]}->{$fields[3]});
									}
									if (count($fields) == 5) {
										$this->array[$key][$field_name] = urldecode($xml->{$fields[0]}->{$fields[1]}->{$fields[2]}->{$fields[3]}->{$fields[4]});
									}
								}
							}
						}

					//send the domain name to the cdr log
						//$this->log("\ndomain_name is `$domain_name`;\ndomain_uuid is '$domain_uuid'\n");

					//get the domain_uuid with the domain_name
						if (empty($domain_uuid)) {
							$sql = "select domain_uuid from v_domains ";
							if (empty($domain_name) && $context != 'public' && $context != 'default') {
								$sql .= "where domain_name = :context ";
								$parameters['context'] = $context;
							}
							else {
								$sql .= "where domain_name = :domain_name ";
								$parameters['domain_name'] = $domain_name;
							}
							$database = new database;
							$domain_uuid = $database->select($sql, $parameters, 'column');
							unset($parameters);
						}

					//set values in the database
						if (!empty($domain_uuid)) {
							$this->array[$key]['domain_uuid'] = $domain_uuid;
						}
						if (!empty($domain_name)) {
							$this->array[$key]['domain_name'] = $domain_name;
						}

					//get the recording details
						if (isset($xml->variables->record_path) && isset($xml->variables->record_name)) {
							$record_path = urldecode($xml->variables->record_path);
							$record_name = urldecode($xml->variables->record_name);
							if (isset($xml->variables->record_seconds)) {
								$record_length = urldecode($xml->variables->record_seconds);
							}
							else {
								$record_length = urldecode($xml->variables->duration);
							}
						}
						elseif (isset($xml->variables->cc_record_filename)) {
							$record_path = dirname(urldecode($xml->variables->cc_record_filename));
							$record_name = basename(urldecode($xml->variables->cc_record_filename));
							$record_length = urldecode($xml->variables->record_seconds);
						}
						elseif (!isset($record_path) && urldecode($xml->variables->last_app) == "record_session") {
							$record_path = dirname(urldecode($xml->variables->last_arg));
							$record_name = basename(urldecode($xml->variables->last_arg));
							$record_length = urldecode($xml->variables->record_seconds);
						}
						elseif (!empty($xml->variables->sofia_record_file)) {
							$record_path = dirname(urldecode($xml->variables->sofia_record_file));
							$record_name = basename(urldecode($xml->variables->sofia_record_file));
							$record_length = urldecode($xml->variables->record_seconds);
						}
						elseif (!empty($xml->variables->api_on_answer)) {
							$command = str_replace("\n", " ", urldecode($xml->variables->api_on_answer));
							$parts = explode(" ", $command);
							if ($parts[0] == "uuid_record") {
								$recording = $parts[3];
								$record_path = dirname($recording);
								$record_name = basename($recording);
								$record_length = urldecode($xml->variables->duration);
							}
						}
						elseif (!empty($xml->variables->conference_recording)) {
							$conference_recording = urldecode($xml->variables->conference_recording);
							$record_path = dirname($conference_recording);
							$record_name = basename($conference_recording);
							$record_length = urldecode($xml->variables->duration);
						}
						elseif (!empty($xml->variables->current_application_data)) {
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

					//check to see if file exists with the default file name and path
						if (empty($record_name)) {
							$path = $this->setting->get('switch', 'recordings').'/'.$domain_name.'/archive/'.$start_year.'/'.$start_month.'/'.$start_day;
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

					//last check - check to see if file exists with the bridge_uuid for the file name and path
						 if (empty($record_name)) {
							$bridge_uuid = urldecode($xml->variables->bridge_uuid) ?: $last_bridge;
							$path = $this->setting->get('switch', 'recordings').'/'.$domain_name.'/archive/'.$start_year.'/'.$start_month.'/'.$start_day;
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

					//debug information
						//echo "line: ".__line__;
						//echo "record_path: ".$record_path."\n";
						//echo "record_name: ".$record_name."\n";
						//echo "record_length: ".$record_length."\n";
						//exit;

					//add the call record path, name and length to the database
						if (isset($record_path) && isset($record_name) && file_exists($record_path.'/'.$record_name)) {
							$this->array[$key]['record_path'] = $record_path;
							$this->array[$key]['record_name'] = $record_name;
							if (isset($record_length)) {
								$this->array[$key]['record_length'] = $record_length;
							}
							else {
								$this->array[$key]['record_length'] = urldecode($xml->variables->duration);
							}
						}

					//save the xml object to json
						$this->json = json_encode($xml);

					//save to the database in xml format
						if ($this->setting->get('cdr', 'format') == "xml" && $this->setting->get('cdr', 'storage') == "db") {
							$this->array[$key]['xml'] = $xml_string;
						}

					//save to the database in json format
						if ($this->setting->get('cdr', 'format') == "json" && $this->setting->get('cdr', 'storage') == "db") {
							$this->array[$key]['json'] = $this->json;
						}

					//build the call detail array with json decode
						$this->call_details = json_decode($this->json, true);

					//get the call flow json
						$this->array[$key]['call_flow'] = json_encode($this->call_flow());

					//get the extension_uuid and then add it to the database fields array
						if (isset($xml->variables->extension_uuid)) {
							$this->array[$key]['extension_uuid'] = urldecode($xml->variables->extension_uuid);
						}
						else {
							if (isset($domain_uuid) && isset($xml->variables->dialed_user)) {
								$sql = "select extension_uuid from v_extensions ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$sql .= "and (extension = :dialed_user or number_alias = :dialed_user) ";
								$parameters['domain_uuid'] = $domain_uuid;
								$parameters['dialed_user'] = $xml->variables->dialed_user;
								$database = new database;
								$extension_uuid = $database->select($sql, $parameters, 'column');
								$this->array[$key]['extension_uuid'] = $extension_uuid;
								unset($parameters);
							}
							if (isset($domain_uuid) && isset($xml->variables->referred_by_user)) {
								$sql = "select extension_uuid from v_extensions ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$sql .= "and (extension = :referred_by_user or number_alias = :referred_by_user) ";
								$parameters['domain_uuid'] = $domain_uuid;
								$parameters['referred_by_user'] = $xml->variables->referred_by_user;
								$database = new database;
								$extension_uuid = $database->select($sql, $parameters, 'column');
								$this->array[$key]['extension_uuid'] = $extension_uuid;
								unset($parameters);
							}
							if (isset($domain_uuid) && isset($xml->variables->last_sent_callee_id_number)) {
								$sql = "select extension_uuid from v_extensions ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$sql .= "and (extension = :last_sent_callee_id_number or number_alias = :last_sent_callee_id_number) ";
								$parameters['domain_uuid'] = $domain_uuid;
								$parameters['last_sent_callee_id_number'] = $xml->variables->last_sent_callee_id_number;
								$database = new database;
								$extension_uuid = $database->select($sql, $parameters, 'column');
								$this->array[$key]['extension_uuid'] = $extension_uuid;
								unset($parameters);
							}
						}

					//store xml cdr on the file system as a file
						if ($this->setting->get('cdr', 'storage') == "dir" && $error != "true") {
							if (!empty($uuid)) {
								$tmp_dir = $this->setting->get('switch', 'log').'/xml_cdr/archive/'.$start_year.'/'.$start_month.'/'.$start_day;
								if(!file_exists($tmp_dir)) {
									mkdir($tmp_dir, 0770, true);
								}
								if ($this->setting->get('cdr', 'format') == "xml") {
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

					//save data to the database
						$this->save();

					//debug
						//GLOBAL $insert_time,$insert_count;
						//$insert_time+=microtime(true)-$time5_insert; //add this current query.
						//$insert_count++;

				} //if ($duplicate_uuid == false)
		} //function xml_array

		/**
		 * Build a call flow array based on call details.
		 *
		 * This method constructs an array that represents the call flow, utilizing the provided call_details array. Reverses the array to put the events in chronological order and adds profile end times.
		 *
		 * @return array The call flow array.
		 */
		public function call_flow() {

			//save the call flow to the database
			if (isset($this->call_details['callflow'])) {
				//set the call flow array
				$call_flow_array = $this->call_details['callflow'];

				//normalize the array
				if (!isset($call_flow_array[0])) {
					$tmp = $call_flow_array;
					unset($call_flow_array);
					$call_flow_array[0] = $tmp;
				}

				//reverse the array to put events in chronological order
				$call_flow_array = array_reverse($call_flow_array);

				//add the profile end time to the call flow array
				$i = 0;
				foreach ($call_flow_array as $row) {
					//set the profile end time
					if (isset($call_flow_array[$i+1]["times"]["profile_created_time"])) {
						$call_flow_array[$i]["times"]["profile_end_time"] = $call_flow_array[$i+1]["times"]["profile_created_time"];
					}
					else {
						$call_flow_array[$i]["times"]["profile_end_time"] = urldecode($this->call_details['variables']['end_uepoch']);
					}
					$i++;
				}

				//format the times in the call flow array and add the profile duration
				$i = 0;
				foreach ($call_flow_array as $row) {
					foreach ($row["times"] as $name => $value) {
						if ($value > 0) {
							$call_flow_array[$i]["times"]["profile_duration_seconds"] = round(((int) $call_flow_array[$i]["times"]["profile_end_time"])/1000000 - ((int) $call_flow_array[$i]["times"]["profile_created_time"])/1000000);
							$call_flow_array[$i]["times"]["profile_duration_formatted"] = gmdate("G:i:s", (int) $call_flow_array[$i]["times"]["profile_duration_seconds"]);
						}
					}
					$i++;
				}

				//add the call_flow to the array
				return $call_flow_array;
			}
		}

		/**
		 * Build a call flow summary array based on call summary
		 *
		 * This method constructs an array that represents the call flow summary using the call flow array array. The call flow summary array contains a simplified view of the call flow.
		 *
		 * @return array The call flow summary array.
		 */
		public function call_flow_summary($call_flow_array) {

			//set the time zone
			if (!empty($this->setting->get('domain', 'time_zone'))) {
				$time_zone = $this->setting->get('domain', 'time_zone');
			}
			else {
				$time_zone = date_default_timezone_get();
			}

			//get the destination select list
			$destination = new destinations;
			$destination_array = $destination->get('dialplan');

			//add new rows when callee_id_number exists
			$new_rows = 0;
			foreach ($call_flow_array as $key => $row) {
				if (!empty($row["caller_profile"]["destination_number"])
					and !empty($row["caller_profile"]["callee_id_number"])
					and $row["caller_profile"]["destination_number"] !== $row["caller_profile"]["callee_id_number"]) {
						//build the base of the new_row array
						$new_row["caller_profile"]["destination_number"] = $row["caller_profile"]["callee_id_number"];
						$new_row["caller_profile"]["caller_id_name"] = $row["caller_profile"]["callee_id_name"];
						$new_row["caller_profile"]["caller_id_number"] = $row["caller_profile"]["caller_id_number"];
						$new_row['times']["profile_created_time"] = $row["times"]["profile_created_time"];
						$new_row['times']["profile_end_time"] = $row["times"]["profile_end_time"];

						//update the times if the transfer_time exists. The order of this is important add new row needs to be set before this code
						if (isset($row["times"]["transfer_time"]) and $row["times"]["transfer_time"] > 0) {
							//change the end time for the current row
							$call_flow_array[$key+$new_rows]["times"]["profile_end_time"] = $row["times"]["transfer_time"];

							//change the created time for the new row
							$new_row['times']["profile_created_time"] = $row["times"]["transfer_time"];
						}

						//update the times if the bridged_time exists. The order of this is important add new row needs to be set before this code, and transfer_time needs to be before bridge_time
						if (isset($row["times"]["bridged_time"]) and $row["times"]["bridged_time"] > 0) {
							//change the end time for the current row
							$call_flow_array[$key+$new_rows]["times"]["profile_end_time"] = $row["times"]["bridged_time"];

							//change the created time for the new row
							$new_row['times']["profile_created_time"] = $row["times"]["bridged_time"];
						}

						//increment the new row id
						$new_rows++;

						//insert the new row into the array without overwriting an existing row
						array_splice($call_flow_array, $key+$new_rows, 0, [$new_row]);

						//clean up
						unset($new_row);
				}
				$i++;
			}

			//format the times in the call flow array
			$i = 0;
			foreach ($call_flow_array as $key => $row) {
				foreach ($row["times"] as $name => $value) {
					if ($value > 0) {
						$call_flow_array[$i]["times"][$name.'stamp'] = date("Y-m-d H:i:s", (int) $value/1000000);
					}
				}
				$i++;
			}

			//build the call flow summary
			$x = 0; $skip_row = false;
			if (!empty($call_flow_array)) {
				foreach ($call_flow_array as $row) {
					//skip this row
					if ($skip_row) {
						$skip_row = false;
						continue;
					}

					//get the application array
					if (!empty($destination_array) && !empty($row["caller_profile"]["destination_number"])) {
						$app = $this->find_app($destination_array, urldecode($row["caller_profile"]["destination_number"]));
					}

					//call centers
					if ($app['application'] == 'call_centers') {
						if (isset($row["caller_profile"]["transfer_source"])) {
							$app['status'] = 'answered'; //Out
						}
						else {
							$app['status'] = 'waited'; //In
						}
					}

					//call flows
					if ($app['application'] == 'call_flows') {
						$app['status'] = 'routed';
					}

					//conferences
					if ($app['application'] == 'conferences') {
						$app['status'] = 'answered';
					}

					//destinations
					if ($app['application'] == 'destinations') {
						$app['status'] = 'routed';
					}

					//extensions
					if ($app['application'] == 'extensions') {
						if ($this->billsec == 0) {
							$app['status'] = 'missed';
						}
						else {
							$app['status'] = 'answered';
						}
					}

					//ivr menus
					if ($app['application'] == 'ivr_menus') {
						$app['status'] = 'routed';
					}

					//outbound routes
					if ($this->call_direction == 'outbound') {
						if (empty($app['application'])) {
							$app['application'] = 'dialplans';
							$app['uuid'] = '';
							$app['status'] = '';
							$app['name'] = 'Outbound';
							$app['label'] = 'Outbound';
						}
					}

					//ring groups
					if ($app['application'] == 'ring_groups') {
						$app['status'] = 'waited';
					}

					//time conditions
					if ($app['application'] == 'time_conditions') {
						$app['status'] = 'routed';
					}

					//valet park
					if (!empty($row["caller_profile"]["destination_number"])
						and (substr($row["caller_profile"]["destination_number"], 0, 4) == 'park'
						or (substr($row["caller_profile"]["destination_number"], 0, 3) == '*59'
						and strlen($row["caller_profile"]["destination_number"]) == 5))) {
						//add items to the app array
						$app['application'] = 'dialplans';
						$app['uuid'] = '46ae6d82-bb83-46a3-901d-33d0724347dd';
						$app['name'] = 'Park';
						$app['label'] = 'Park';

						//set the call park status
						if (strpos($row["caller_profile"]["transfer_source"], 'park+') !== false) {
							//$app['status'] = 'In';
							$app['status'] = 'parked';

							//skip the next row
							$skip_row = true;
						}
						else {
							//$app['status'] = 'Out';
							$app['status'] = 'unparked';
						}
					}

					//conference
					if ($app['application'] == 'conferences') {
						$skip_row = true;
					}

					//voicemails
					if ($app['application'] == 'voicemails') {
						$app['status'] = 'voicemail';
					}

					//debug - add the callee_id_number to the end of the status
					if (isset($_REQUEST['debug']) && $_REQUEST['debug'] == 'true' && !empty($row["caller_profile"]["destination_number"])
						and !empty($row["caller_profile"]["callee_id_number"])
						and $row["caller_profile"]["destination_number"] !== $row["caller_profile"]["callee_id_number"]) {
							$app['status'] .= ' ('.$row["caller_profile"]["callee_id_number"].')';
					}

					//build the application urls
					$destination_url = "/app/".$app['application']."/".$destination->singular($app['application'])."_edit.php?id=".$app["uuid"];
					$application_url = "/app/".$app['application']."/".$app['application'].".php";
					if ($app['application'] == 'call_centers') {
						$destination_url = "/app/".$app['application']."/".$destination->singular($app['application'])."_queue_edit.php?id=".$app['uuid'];
						$application_url = "/app/".$app['application']."/".$destination->singular($app['application'])."_queues.php";
					}

					//add the application and destination details
					$language2 = new text;
					$text2 = $language2->get($this->setting->get('domain', 'language'), 'app/'.$app['application']);
					$call_flow_summary[$x]["application_name"] = $app['application'];
					$call_flow_summary[$x]["application_label"] = trim($text2['title-'.$app['application']]);
					$call_flow_summary[$x]["application_url"] = $application_url;
					$call_flow_summary[$x]["destination_uuid"] = $app['uuid'];
					$call_flow_summary[$x]["destination_name"] = $app['name'];
					$call_flow_summary[$x]["destination_url"] = $destination_url;
					$call_flow_summary[$x]["destination_number"] = $row["caller_profile"]["destination_number"];
					$call_flow_summary[$x]["destination_label"] = $app['label'];
					$call_flow_summary[$x]["destination_status"] = $app['status'];
					$call_flow_summary[$x]["destination_description"] = $app['description'];
					//$call_flow_summary[$x]["application"] = $app;

					//set the start and epoch
					$profile_created_epoch = round($row['times']['profile_created_time'] / 1000000);
					$profile_end_epoch = round($row['times']['profile_end_time'] / 1000000);

					//add the call flow times
					$call_flow_summary[$x]["start_epoch"] = $profile_created_epoch;
					$call_flow_summary[$x]["end_epoch"] = $profile_end_epoch;
					$call_flow_summary[$x]["start_stamp"] =  date("Y-m-d H:i:s", $profile_created_epoch);
					$call_flow_summary[$x]["end_stamp"] =  date("Y-m-d H:i:s", $profile_end_epoch);
					$call_flow_summary[$x]["duration_seconds"] =  $profile_end_epoch - $profile_created_epoch;
					$call_flow_summary[$x]["duration_formatted"] =  gmdate("G:i:s",(int) $call_flow_summary[$x]["duration_seconds"]);
					unset($app);
					$x++;
				}
			}
			unset($x);

			//return the call flow summary array
			return $call_flow_summary;
		}

	//add a function to return the find_app
		public function find_app($destination_array, $detail_action) {

			//add the destinations to the destination array
			$sql = "select * from v_destinations ";
			$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
			$parameters['domain_uuid'] = $this->domain_uuid;
			$database = new database;
			$destinations = $database->select($sql, $parameters, 'all');
			if (!empty($destinations)) {
				foreach($destinations as $row) {
					$destination_array['destinations'][$id]['application'] = 'destinations';
					$destination_array['destinations'][$id]['destination_uuid'] = $row["destination_uuid"];
					$destination_array['destinations'][$id]['uuid'] = $row["destination_uuid"];
					$destination_array['destinations'][$id]['dialplan_uuid'] = $row["dialplan_uuid"];
					$destination_array['destinations'][$id]['destination_type'] = $row["destination_type"];
					$destination_array['destinations'][$id]['destination_prefix'] = $row["destination_prefix"];
					$destination_array['destinations'][$id]['destination_number'] = $row["destination_number"];
					$destination_array['destinations'][$id]['extension'] = $row["destination_prefix"] . $row["destination_number"];
					$destination_array['destinations'][$id]['destination_trunk_prefix'] = $row["destination_trunk_prefix"];
					$destination_array['destinations'][$id]['destination_area_code'] = $row["destination_area_code"];
					$destination_array['destinations'][$id]['context'] = $row["destination_context"];
					$destination_array['destinations'][$id]['label'] = $row["destination_description"];
					$destination_array['destinations'][$id]['destination_enabled'] = $row["destination_enabled"];
					$destination_array['destinations'][$id]['name'] = $row["destination_description"];
					$destination_array['destinations'][$id]['description'] = $row["destination_description"];
					//$destination_array[$id]['destination_caller_id_name'] = $row["destination_caller_id_name"];
					//$destination_array[$id]['destination_caller_id_number'] = $row["destination_caller_id_number"];
					$id++;
				}
			}
			unset($sql, $parameters, $row);

			$result = '';
			if (!empty($destination_array)) {
				foreach($destination_array as $application => $row) {
					if (!empty($row)) {
						foreach ($row as $key => $value) {
							//find matching destinations
							if ($application == 'destinations') {
								if ('+'.$value['destination_prefix'].$value['destination_number'] == $detail_action
									or $value['destination_prefix'].$value['destination_number'] == $detail_action
									or $value['destination_number'] == $detail_action
									or $value['destination_trunk_prefix'].$value['destination_number'] == $detail_action
									or '+'.$value['destination_prefix'].$value['destination_area_code'].$value['destination_number'] == $detail_action
									or $value['destination_prefix'].$value['destination_area_code'].$value['destination_number'] == $detail_action
									or $value['destination_area_code'].$value['destination_number'] == $detail_action) {
										if (file_exists($_SERVER["PROJECT_ROOT"]."/app/".$application."/app_languages.php")) {
											$value['application'] = $application;
											return $value;
										}
								}
							}

							//find all other matching actions
							if (!empty($value['extension']) && $value['extension'] == $detail_action or preg_match('/^'.preg_quote($value['extension']).'$/', $detail_action)) {
								if (file_exists($_SERVER["PROJECT_ROOT"]."/app/".$application."/app_languages.php")) {
									$value['application'] = $application;
									return $value;
								}
							}
						}
					}
				}
			}
		}


		/**
		 * get xml from the filesystem and save it to the database
		 */
		public function read_files() {
			$xml_cdr_dir = $this->setting->get('switch', 'log').'/xml_cdr';
			$dir_handle = opendir($xml_cdr_dir);
			$x = 0;
			while($file = readdir($dir_handle)) {
				if ($file != '.' && $file != '..') {
					//used to test a single file
					//$file = 'a_aa76e0af-461e-4d46-be23-433260307ede.cdr.xml';

					//process the XML files
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

						//import the call detail files are less than 3 mb - 3 million bytes
							if ($import && filesize($xml_cdr_dir.'/'.$file) <= 3000000) {
								//get the xml cdr string
									$call_details = file_get_contents($xml_cdr_dir.'/'.$file);

								//set the file
									$this->file = $file;

								//decode the xml string
									if (substr($call_details, 0, 1) == '%') {
										$call_details = urldecode($call_details);
									}

								//parse the xml and insert the data into the db
									$this->xml_array($x, $leg, $call_details);

								//increment the value
									$x++;
							}

						//move the files that are too large to the failed directory
							if ($import && filesize($xml_cdr_dir.'/'.$file) >= 3000000) {
								if (!empty($xml_cdr_dir)) {
									if (!file_exists($xml_cdr_dir.'/failed')) {
										if (!mkdir($xml_cdr_dir.'/failed', 0660, true)) {
											die('Failed to create '.$xml_cdr_dir.'/failed');
										}
									}
									rename($xml_cdr_dir.'/'.$file, $xml_cdr_dir.'/failed/'.$file);
								}
							}

						//if limit exceeded exit the loop
							if ($limit == $x) {
								//echo "limit: $limit count: $x if\n";
								break;
							}
					}
				}
			}
			//close the directory handle
			closedir($dir_handle);
		}
		//$this->read_files();

		/**
		 * read the call detail records from the http post
		 */
		public function post() {
			if (isset($_POST["cdr"])) {

				//debug method
					//$this->log($_POST["cdr"]);

				//authentication for xml cdr http post
					if (!defined('STDIN')) {
						if ($this->setting->get('cdr', 'http_enabled') == "true") {
							//get the contents of xml_cdr.conf.xml
								$conf_xml_string = file_get_contents($this->setting->get('switch', 'conf').'/autoload_configs/xml_cdr.conf.xml');

							//parse the xml to get the call detail record info
								try {
									//disable xml entities
									libxml_disable_entity_loader(true);

									//load the string into an xml object
									$conf_xml = simplexml_load_string($conf_xml_string, 'SimpleXMLElement', LIBXML_NOCDATA);
								}
								catch(Exception $e) {
									echo $e->getMessage();
								}
								if (isset($conf_xml->settings->param)) {
									foreach ($conf_xml->settings->param as $row) {
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
					}

				//if http enabled is set to false then deny access
					if (!defined('STDIN')) {
						if ($this->setting->get('cdr', 'http_enabled') == "false") {
							openlog('FusionPBX', LOG_NDELAY, LOG_AUTH);
							syslog(LOG_WARNING, '['.$_SERVER['REMOTE_ADDR'].'] XML CDR import default setting http_enabled is not enabled. Line: '.__line__);
							closelog();

							echo "access denied\n";
							return;
						}
					}

				//check for the correct username and password
					if (!defined('STDIN')) {
						if ($this->setting->get('cdr', 'http_enabled') == "true") {
							if ($auth_array[0] == $_SERVER["PHP_AUTH_USER"] && $auth_array[1] == $_SERVER["PHP_AUTH_PW"]) {
								//echo "access granted\n";
								$this->username = $auth_array[0];
								$this->password = $auth_array[1];
							}
							else {
								openlog('FusionPBX', LOG_NDELAY, LOG_AUTH);
								syslog(LOG_WARNING, '['.$_SERVER['REMOTE_ADDR'].'] XML CDR import username or password failed. Line: '.__line__);
								closelog();

								echo "access denied\n";
								return;
							}
						}
					}

				//loop through all attribues
					//foreach($xml->settings->param[1]->attributes() as $a => $b) {
					//		echo $a,'="',$b,"\"\n";
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
					$this->log("HTTP POST\n");

				//parse the xml and insert the data into the database
					$this->xml_array(0, $leg, $xml_string);
			}
		}
		//$this->post();

		/**
		 * user summary returns an array
		 */
		public function user_summary() {

			//set the time zone
				if (!empty($this->setting->get('domain', 'time_zone'))) {
					$time_zone = $this->setting->get('domain', 'time_zone');
				}
				else {
					$time_zone = date_default_timezone_get();
				}

			//build the date range
				if ((!empty($this->start_stamp_begin) && strlen($this->start_stamp_begin) > 0) || !empty($this->start_stamp_end)) {
					unset($this->quick_select);
					if (strlen($this->start_stamp_begin) > 0 && !empty($this->start_stamp_end)) {
						$sql_date_range = " and start_stamp between :start_stamp_begin::timestamptz and :start_stamp_end::timestamptz \n";
						$parameters['start_stamp_begin'] = $this->start_stamp_begin.':00.000 '.$time_zone;
						$parameters['start_stamp_end'] = $this->start_stamp_end.':59.999 '.$time_zone;
					}
					else {
						if (!empty($this->start_stamp_begin)) {
							$sql_date_range = "and start_stamp >= :start_stamp_begin::timestamptz \n";
							$parameters['start_stamp_begin'] = $this->start_stamp_begin.':00.000 '.$time_zone;
						}
						if (!empty($this->start_stamp_end)) {
							$sql_date_range .= "and start_stamp <= :start_stamp_end::timestamptz \n";
							$parameters['start_stamp_end'] = $this->start_stamp_end.':59.999 '.$time_zone;
						}
					}
				}
				else {
					switch ($this->quick_select) {
						case 1: $sql_date_range = "and start_stamp >= '".date('Y-m-d H:i:s.000', strtotime("-1 week"))." ".$time_zone."'::timestamptz \n"; break; //last 7 days
						case 2: $sql_date_range = "and start_stamp >= '".date('Y-m-d H:i:s.000', strtotime("-1 hour"))." ".$time_zone."'::timestamptz \n"; break; //last hour
						case 3: $sql_date_range = "and start_stamp >= '".date('Y-m-d')." "."00:00:00.000 ".$time_zone."'::timestamptz \n"; break; //today
						case 4: $sql_date_range = "and start_stamp between '".date('Y-m-d',strtotime("-1 day"))." "."00:00:00.000 ".$time_zone."'::timestamptz and '".date('Y-m-d',strtotime("-1 day"))." "."23:59:59.999 ".$time_zone."'::timestamptz \n"; break; //yesterday
						case 5: $sql_date_range = "and start_stamp >= '".date('Y-m-d',strtotime("this week"))." "."00:00:00.000 ".$time_zone."' \n"; break; //this week
						case 6: $sql_date_range = "and start_stamp >= '".date('Y-m-')."01 "."00:00:00.000 ".$time_zone."'::timestamptz \n"; break; //this month
						case 7: $sql_date_range = "and start_stamp >= '".date('Y-')."01-01 "."00:00:00.000 ".$time_zone."'::timestamptz \n"; break; //this year
					}
				}

			//calculate the summary data
				$sql = "select \n";
				$sql .= "e.domain_uuid, \n";
				$sql .= "d.domain_name, \n";
				$sql .= "e.extension, \n";
				$sql .= "e.number_alias, \n";

				//answered
				$sql .= "count(*) \n";
				$sql .= "filter ( \n";
				$sql .= " where c.extension_uuid = e.extension_uuid \n";
				$sql .= " and missed_call = false\n";
				if (!permission_exists('xml_cdr_enterprise_leg')) {
					$sql .= " and originating_leg_uuid is null \n";
				}
				elseif (!permission_exists('xml_cdr_lose_race')) {
					$sql .= " and hangup_cause <> 'LOSE_RACE' \n";
				}
				$sql .= " and (cc_side IS NULL or cc_side !='agent')";
				if ($this->include_internal) {
					$sql .= " and (direction = 'inbound' or direction = 'local') \n";
				}
				else {
					$sql .= "and direction = 'inbound' \n";
				}
				$sql .= ") \n";
				$sql .= "as answered, \n";

				//missed
				$sql .= "count(*) \n";
				$sql .= "filter ( \n";
				$sql .= " where c.extension_uuid = e.extension_uuid \n";
				$sql .= " and missed_call = true\n";
				$sql .= " and (cc_side is null or cc_side != 'agent') \n";
				$sql .= ") \n";
				$sql .= "as missed, \n";

				//cc missed
				$sql .= "count(*) \n";
				$sql .= "filter ( \n";
				$sql .= " where c.extension_uuid = e.extension_uuid \n";
				$sql .= " and c.hangup_cause = 'NO_ANSWER' \n";
				$sql .= " and (cc_side IS NOT NULL or cc_side ='agent')";
 				if ($this->include_internal) {
					$sql .= " and (direction = 'inbound' or direction = 'local') \n";
				}
				else {
					$sql .= "and direction = 'inbound' \n";
				}
				$sql .= ") \n";
				$sql .= "as no_answer, \n";

				//busy
				$sql .= "count(*) \n";
				$sql .= "filter ( \n";
				$sql .= " where c.extension_uuid = e.extension_uuid \n";
				$sql .= " and c.hangup_cause = 'USER_BUSY' \n";
				if ($this->include_internal) {
						$sql .= " and (direction = 'inbound' or direction = 'local') \n";
				}
				else {
						$sql .= " and direction = 'inbound' \n";
				}
				$sql .= ") \n";
				$sql .= "as busy, \n";

				//aloc
				$sql .= "sum(c.billsec) \n";
				$sql .= "filter ( \n";
				$sql .= " where c.extension_uuid = e.extension_uuid \n";
				if (!$this->include_internal) {
						$sql .= " and (direction = 'inbound' or direction = 'outbound') \n";
				}
				$sql .= " ) / \n";
				$sql .= "count(*) \n";
				$sql .= "filter ( \n";
				$sql .= " where c.extension_uuid = e.extension_uuid \n";
				if (!$this->include_internal) {
						$sql .= " and (direction = 'inbound' or direction = 'outbound') \n";
				}
				$sql .= ") \n";
				$sql .= "as aloc, \n";

				//inbound calls
				$sql .= "count(*) \n";
				$sql .= "filter ( \n";
				$sql .= " where c.extension_uuid = e.extension_uuid \n";
				if (!permission_exists('xml_cdr_enterprise_leg')) {
					$sql .= " and originating_leg_uuid is null \n";
				}
				elseif (!permission_exists('xml_cdr_lose_race')) {
					$sql .= " and hangup_cause <> 'LOSE_RACE' \n";
				}
				$sql .= " and (cc_side is null or cc_side != 'agent') \n";
				if ($this->include_internal) {
						$sql .= " and (direction = 'inbound' or direction = 'local') \n";
				}
				else {
						$sql .= " and direction = 'inbound' \n";
				}
				$sql .= ") \n";
				$sql .= "as inbound_calls, \n";

				//inbound duration
				$sql .= "sum(c.billsec) \n";
				$sql .= "filter ( \n";
				$sql .= " where c.extension_uuid = e.extension_uuid \n";
				if ($this->include_internal) {
						$sql .= " and (direction = 'inbound' or direction = 'local')) \n";
				}
				else {
						$sql .= " and direction = 'inbound') \n";
				}
				$sql .= "as inbound_duration, \n";

				//outbound duration
				$sql .= "count(*) \n";
				$sql .= "filter ( \n";
				$sql .= " where c.extension_uuid = e.extension_uuid \n";
				$sql .= " and c.direction = 'outbound' \n";
				$sql .= ") \n";
				$sql .= "as outbound_calls, \n";

				$sql .= "sum(c.billsec) \n";
				$sql .= "filter ( \n";
				$sql .= " where c.extension_uuid = e.extension_uuid \n";
				$sql .= " and c.direction = 'outbound' \n";
				$sql .= ") \n";
				$sql .= "as outbound_duration, \n";

				$sql .= "e.description \n";

				$sql .= "from v_extensions as e, v_domains as d, \n";
				$sql .= "( select \n";
				$sql .= " domain_uuid, \n";
				$sql .= " extension_uuid, \n";
				$sql .= " caller_id_number, \n";
				$sql .= " destination_number, \n";
				$sql .= " missed_call, \n";
				$sql .= " answer_stamp, \n";
				$sql .= " bridge_uuid, \n";
				$sql .= " direction, \n";
				$sql .= " start_stamp, \n";
				$sql .= " hangup_cause, \n";
				$sql .= " originating_leg_uuid, \n";
				$sql .= " billsec, \n";
				$sql .= " cc_side, \n";
				$sql .= " sip_hangup_disposition, \n";
				$sql .= " voicemail_message \n";
				$sql .= " from v_xml_cdr \n";
				if (!(!empty($_GET['show']) && $_GET['show'] === 'all' && permission_exists('xml_cdr_extension_summary_all'))) {
					$sql .= " where domain_uuid = :domain_uuid \n";
				}
				else {
					$sql .= " where true \n";
				}
				$sql .= $sql_date_range;
				$sql .= ") as c \n";

				$sql .= "where \n";
				$sql .= "d.domain_uuid = e.domain_uuid \n";
				if (!(!empty($_GET['show']) && $_GET['show'] === 'all' && permission_exists('xml_cdr_extension_summary_all'))) {
					$sql .= "and e.domain_uuid = :domain_uuid \n";
				}
				$sql .= "group by e.extension, e.domain_uuid, d.domain_uuid, e.number_alias, e.description \n";
				$sql .= "order by extension asc \n";
				if (!(!empty($_GET['show']) && $_GET['show'] === 'all' && permission_exists('xml_cdr_extension_summary_all'))) {
					$parameters['domain_uuid'] = $this->domain_uuid;
				}
				$database = new database;
				$summary = $database->select($sql, $parameters, 'all');
				unset($parameters);

			//return the array
				return $summary;
		}

		/**
		 * download the recordings
		 */
		public function download($uuid) {
			if (!permission_exists('xml_cdr_view')) {
				echo "permission denied";
				return;
			}

			//get call recording from database
			if (!is_uuid($uuid)) {
				echo "invalid uuid";
				return;
			}

			$sql = "select record_name, record_path from v_xml_cdr ";
			$sql .= "where xml_cdr_uuid = :xml_cdr_uuid ";
			//$sql .= "and domain_uuid = '".$domain_uuid."' \n";
			$parameters['xml_cdr_uuid'] = $uuid;
			//$parameters['domain_uuid'] = $domain_uuid;
			$database = new database;
			$row = $database->select($sql, $parameters, 'row');
			if (!empty($row) && is_array($row)) {
				$record_name = $row['record_name'];
				$record_path = $row['record_path'];
			} else {
				echo "recording not found";
				return;
			}
			unset ($sql, $parameters, $row);

			//build full path
			$record_file = $record_path.'/'.$record_name;

			//download the file
			if (!file_exists($record_file) || $record_file == '/') {
				echo "recording not found";
				return;
			}

			ob_clean();
			$fd = fopen($record_file, "rb");
			if ($_GET['t'] == "bin") {
				header("Content-Type: application/force-download");
				header("Content-Type: application/octet-stream");
				header("Content-Type: application/download");
				header("Content-Description: File Transfer");
			}
			else {
				$file_ext = pathinfo($record_name, PATHINFO_EXTENSION);
				switch ($file_ext) {
					case "wav" : header("Content-Type: audio/x-wav"); break;
					case "mp3" : header("Content-Type: audio/mpeg"); break;
					case "ogg" : header("Content-Type: audio/ogg"); break;
				}
			}
			$record_name = preg_replace('#[^a-zA-Z0-9_\-\.]#', '', $record_name);
			header('Content-Disposition: attachment; filename="'.$record_name.'"');
			header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
			header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
			if ($_GET['t'] == "bin") {
				header("Content-Length: ".filesize($record_file));
			}
 			ob_clean();

 			//content-range
 			if (isset($_SERVER['HTTP_RANGE']) && $_GET['t'] != "bin")  {
				$this->range_download($record_file);
			}

 			fpassthru($fd);

		} //end download method

		/*
		 * range download method (helps safari play audio sources)
		 */
		private function range_download($file) {
			$fp = @fopen($file, 'rb');

			$size   = filesize($file); // File size
			$length = $size;           // Content length
			$start  = 0;               // Start byte
			$end    = $size - 1;       // End byte
			// Now that we've gotten so far without errors we send the accept range header
			/* At the moment we only support single ranges.
			* Multiple ranges requires some more work to ensure it works correctly
			* and comply with the spesifications: http://www.w3.org/Protocols/rfc2616/rfc2616-sec19.html#sec19.2
			*
			* Multirange support annouces itself with:
			* header('Accept-Ranges: bytes');
			*
			* Multirange content must be sent with multipart/byteranges mediatype,
			* (mediatype = mimetype)
			* as well as a boundry header to indicate the various chunks of data.
			*/
			header("Accept-Ranges: 0-$length");
			// header('Accept-Ranges: bytes');
			// multipart/byteranges
			// http://www.w3.org/Protocols/rfc2616/rfc2616-sec19.html#sec19.2
			if (isset($_SERVER['HTTP_RANGE'])) {

				$c_start = $start;
				$c_end   = $end;
				// Extract the range string
				list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
				// Make sure the client hasn't sent us a multibyte range
				if (strpos($range, ',') !== false) {
					// (?) Shoud this be issued here, or should the first
					// range be used? Or should the header be ignored and
					// we output the whole content?
					header('HTTP/1.1 416 Requested Range Not Satisfiable');
					header("Content-Range: bytes $start-$end/$size");
					// (?) Echo some info to the client?
					exit;
				}
				// If the range starts with an '-' we start from the beginning
				// If not, we forward the file pointer
				// And make sure to get the end byte if spesified
				if ($range == '-') {
					// The n-number of the last bytes is requested
					$c_start = $size - substr($range, 1);
				}
				else {
					$range  = explode('-', $range);
					$c_start = $range[0];
					$c_end   = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
				}
				/* Check the range and make sure it's treated according to the specs.
				* http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
				*/
				// End bytes can not be larger than $end.
				$c_end = ($c_end > $end) ? $end : $c_end;
				// Validate the requested range and return an error if it's not correct.
				if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {

					header('HTTP/1.1 416 Requested Range Not Satisfiable');
					header("Content-Range: bytes $start-$end/$size");
					// (?) Echo some info to the client?
					exit;
				}
				$start  = $c_start;
				$end    = $c_end;
				$length = $end - $start + 1; // Calculate new content length
				fseek($fp, $start);
				header('HTTP/1.1 206 Partial Content');
			}
			// Notify the client the byte range we'll be outputting
			header("Content-Range: bytes $start-$end/$size");
			header("Content-Length: $length");

			// Start buffered download
			$buffer = 1024 * 8;
			while(!feof($fp) && ($p = ftell($fp)) <= $end) {
				if ($p + $buffer > $end) {
					// In case we're only outputtin a chunk, make sure we don't
					// read past the length
					$buffer = $end - $p + 1;
				}
				set_time_limit(0); // Reset time limit for big files
				echo fread($fp, $buffer);
				flush(); // Free up memory. Otherwise large files will trigger PHP's memory limit.
			}

			fclose($fp);
		}

		/**
		 * delete records
		 */
		public function delete($records) {
			if (!permission_exists($this->permission_prefix.'delete')) {
				return false;
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

			//delete multiple records
			if (!is_array($records) || @sizeof($records) == 0) {
				return;
			}
			$records_deleted = 0;

			//loop through records
			foreach($records as $x => $record) {
				if (empty($record['checked']) || $record['checked'] != 'true' || !is_uuid($record['uuid'])) {
					continue;
				}

				//get the call recordings
				$sql = "select xml_cdr_uuid, record_name, record_path from v_xml_cdr ";
				$sql .= "where xml_cdr_uuid = :xml_cdr_uuid ";
				$sql .= "and record_name is not null";
				$parameters['xml_cdr_uuid'] = $record['uuid'];
				$database = new database;
				$row = $database->select($sql, $parameters, 'row');
				unset($sql, $parameters);

				//delete the call recording (file)
				$call_recording_path = realpath($row['record_path']);
				$call_recording_name = $row['record_name'];
				if (file_exists($call_recording_path.'/'.$call_recording_name)) {
					@unlink($call_recording_path.'/'.$call_recording_name);
				}

				//build the delete array
				$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];

				//increment counter
				$records_deleted++;
			}

			if (!is_array($array) || @sizeof($array) == 0) {
				return;
			}

			//grant temporary permissions
			$p = new permissions;
			$p->add('call_recording_delete', 'temp');

			//execute delete
			$database = new database;
			$database->app_name = $this->app_name;
			$database->app_uuid = $this->app_uuid;
			$database->delete($array);
			unset($array);

			//revoke temporary permissions
			$p->delete('call_recording_delete', 'temp');

			//set message
			message::add($text['message-delete'].": ".$records_deleted);

			unset($records);
		} //method

	} //class
}

?>
