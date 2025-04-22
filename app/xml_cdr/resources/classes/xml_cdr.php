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
	Portions created by the Initial Developer are Copyright (C) 2016-2024
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

/**
 * xml_cdr class provides methods for adding cdr records to the database
 */
	class xml_cdr {

		/**
		 * Internal array structure that is populated from the database
		 * @var array Array of settings loaded from Default Settings
		 */
		private $settings;

		/**
		 * Set in the constructor. Must be a database object and cannot be null.
		 * @var database Database Object
		 */
		private $database;

		/**
		 * Set in the constructor. This can be null.
		 * @var destinations Object
		 */
		private $destinations;

		/**
		 * define variables
		 */
		public $array;
		public $fields;
		public $setting;
		public $domain_uuid;
		public $call_details;
		public $call_direction;
		public $status;
		public $billsec;
		private $username;
		private $password;
		private $json;
		public $recording_uuid;
		public $binary;

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
		public function __construct($setting_array = []) {

			//open a database connection
			if (empty($setting_array['database'])) {
				$this->database = database::new();
			} else {
				$this->database = $setting_array['database'];
			}

			//get the settings object
			if (empty($setting_array['settings'])) {
				$this->settings = new settings();
			} else {
				$this->settings = $setting_array['settings'];
			}

			//get the destinations object
			if (!empty($setting_array['destinations'])) {
				$this->destinations = $setting_array['destinations'];
			}

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
			if ($this->settings->get('log', 'enabled', false)) {

				//save the log to the php error log
				if ($this->settings->get('log', 'type') == 'error_log') {
					error_log($message);
				}

				//save the log to the syslog server
				if ($this->settings->get('log', 'type') == 'syslog') {
					openlog("XML CDR", LOG_PID | LOG_PERROR, LOG_LOCAL0);
					syslog(LOG_WARNING, $message);
					closelog();
				}

				//save the log to the file system
				if ($this->settings->get('log', 'text') == 'file') {
					$fp = fopen($this->settings->get('server', 'temp').'/xml_cdr.log', 'a+');
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
			$this->fields[] = "hold_accum_seconds";
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

			if (!empty($this->settings->get('cdr', 'field'))) {
				foreach ($this->settings->get('cdr', 'field') as $field) {
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

				//set the directory
				if (!empty($this->settings->get('switch', 'log'))) {
					$xml_cdr_dir = $this->settings->get('switch', 'log').'/xml_cdr';
				}

				//add the temporary permission
				$p = permissions::new();
				$p->add("xml_cdr_add", "temp");
				$p->add("xml_cdr_json_add", "temp");
				$p->add("xml_cdr_flow_add", "temp");
				$p->add("xml_cdr_log_add", "temp");

				//save the call details record to the database
				$this->database->app_name = 'xml_cdr';
				$this->database->app_uuid = '4a085c51-7635-ff03-f67b-86e834422848';
				//$this->database->domain_uuid = $domain_uuid;
				$response = $this->database->save($this->array, false);
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
				$this->log(print_r($this->database->message, true));

				//remove the temporary permission
				$p->delete("xml_cdr_add", "temp");
				$p->delete("xml_cdr_json_add", "temp");
				$p->delete("xml_cdr_flow_add", "temp");
				$p->delete("xml_cdr_log_add", "temp");
				unset($array);

			}

		}

		/**
		 * process method converts the xml cdr and adds it to the database
		 */
		public function xml_array($key, $leg, $xml_string) {

			//set the directory
				if (!empty($this->settings->get('switch', 'log'))) {
					$xml_cdr_dir = $this->settings->get('switch', 'log').'/xml_cdr';
				}

			//xml string is empty
				if (empty($xml_string) && !empty($xml_cdr_dir) && !empty($this->file)) {
					unlink($xml_cdr_dir.'/'.$this->file);
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

			//replace xml tag name <set api_hangup_hook> with <api_hangup_hook>
				$xml_string = preg_replace('/(<\/?)(set )([^>]*>)/', '$1$3', $xml_string);

			//replace xml tag name <^^,default_language> with <default_language>
				$xml_string = preg_replace('/(<\/?)(\^\^,)([^>]*>)/', '$1$3', $xml_string);

			//replace xml tag name <nolocal:operator> with <operator>
				$xml_string = preg_replace('/(<\/?)(nolocal:)([^>]*>)/', '$1$3', $xml_string);

			//disable xml entities
				if (PHP_VERSION_ID < 80000) { libxml_disable_entity_loader(true); }

			//load the string into an xml object
				$xml = simplexml_load_string($xml_string, 'SimpleXMLElement', LIBXML_NOCDATA);
				if ($xml === false) {

					//failed to load the XML, move the XML file to the failed directory
					if (!empty($xml_cdr_dir)) {
						if (!file_exists($xml_cdr_dir.'/failed/invalid_xml')) {
							if (!mkdir($xml_cdr_dir.'/failed/invalid_xml', 0660, true)) {
								die('Failed to create '.$xml_cdr_dir.'/failed');
							}
						}
						rename($xml_cdr_dir.'/'.$this->file, $xml_cdr_dir.'/failed/invalid_xml/'.$this->file);
					}

					//return without saving the invalid xml
					return false;
				}

			//skip call detail records for calls blocked by call block
				if (isset($xml->variables->call_block) && !empty($this->settings->get('call_block', 'save_call_detail_record'))) {
					if ($xml->variables->call_block == 'true' && $this->settings->get('call_block', 'save_call_detail_record', false) !== true) {
						//delete the xml cdr file
						if (!empty($this->settings->get('switch', 'log'))) {
							$xml_cdr_dir = $this->settings->get('switch', 'log').'/xml_cdr';
							if (file_exists($xml_cdr_dir.'/'.$this->file)) {
								unlink($xml_cdr_dir.'/'.$this->file);
							}
						}

						//return without saving
						return false;
					}
				}

			//check for duplicate call uuid's
				$duplicate_uuid = false;
				$uuid = urldecode($xml->variables->uuid);
				if (empty($uuid)) {
					$uuid = urldecode($xml->variables->call_uuid);
				}
				if ($uuid != null && is_uuid($uuid)) {
					//check for duplicates
					$sql = "select count(xml_cdr_uuid) ";
					$sql .= "from v_xml_cdr ";
					$sql .= "where xml_cdr_uuid = :xml_cdr_uuid ";
					$parameters['xml_cdr_uuid'] = $uuid;
					$count = $this->database->select($sql, $parameters, 'column');
					if ($count > 0) {
						//duplicate uuid detected
						$duplicate_uuid = true;

						//remove the file as the record already exists in the database
						if (!empty($this->settings->get('switch', 'log'))) {
							$xml_cdr_dir = $this->settings->get('switch', 'log').'/xml_cdr';
							if (file_exists($xml_cdr_dir.'/'.$this->file)) {
								unlink($xml_cdr_dir.'/'.$this->file);
							}
						}

						//return without saving
						return false;
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

			//process call detail record data
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

					//if the caller ID was updated then update the caller ID
						if (isset($xml->variables->effective_caller_id_name)) {
							$caller_id_name = urldecode($xml->variables->effective_caller_id_name);
						}
						if (isset($xml->variables->effective_caller_id_number)) {
							$caller_id_number = urldecode($xml->variables->effective_caller_id_number);
						}

					//if intercept is used then update use the last sent callee id name and number
						if (isset($xml->variables->last_app) && $xml->variables->last_app == 'intercept' && !empty($xml->variables->last_sent_callee_id_name)) {
							$caller_id_name = urldecode($xml->variables->last_sent_callee_id_name);
						}
						if (isset($xml->variables->last_app) && $xml->variables->last_app == 'intercept' && !empty($xml->variables->last_sent_callee_id_number)) {
							$caller_id_number = urldecode($xml->variables->last_sent_callee_id_number);
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
						if (isset($call_direction) && $call_direction == 'inbound'
							&& isset($xml->variables->hangup_cause)
							&& $xml->variables->hangup_cause == 'ORIGINATOR_CANCEL') {
							$missed_call = 'true';
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
						if (isset($xml->variables->cc_side) && $xml->variables->cc_side == 'member'
							&& isset($xml->variables->cc_cause) && $xml->variables->cc_cause == 'cancel') {
							//call center
							$missed_call = 'true';
						}
						if (isset($xml->variables->billsec) && $xml->variables->billsec > 0) {
							//answered call
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
						if (isset($xml->variables->missed_call) && $xml->variables->missed_call == 'true') {
							//marked as missed
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
						if (!isset($status) && $xml->variables->billsec == 0) {
							$status = 'no_answer';
						}
						if ($missed_call == 'true') {
							$status = 'missed';
						}
						if (substr($destination_number, 0, 3) == '*99') {
							$status = 'voicemail';
						}
						if (!empty($xml->variables->voicemail_message_seconds)) {
							$status = 'voicemail';
						}

					//set the key
						$key = 'xml_cdr';

					//get the domain values from the xml
						$domain_name = urldecode($xml->variables->domain_name);
						$domain_uuid = urldecode($xml->variables->domain_uuid);

					//sanitize the caller ID
						$caller_id_name = preg_replace('#[^a-zA-Z0-9\-.\#*@ ]#', '', $caller_id_name);
						$caller_id_number = preg_replace('#[^0-9\-\#\*]#', '', $caller_id_number);

					//misc
						$this->array[$key][0]['ring_group_uuid'] = urldecode($xml->variables->ring_group_uuid);
						$this->array[$key][0]['xml_cdr_uuid'] = $uuid;
						$this->array[$key][0]['destination_number'] = $destination_number;
						$this->array[$key][0]['sip_call_id'] = urldecode($xml->variables->sip_call_id);
						$this->array[$key][0]['source_number'] = urldecode($xml->variables->effective_caller_id_number);
						$this->array[$key][0]['network_addr'] = urldecode($xml->variables->sip_network_ip);
						$this->array[$key][0]['missed_call'] = $missed_call;
						$this->array[$key][0]['caller_id_name'] = $caller_id_name;
						$this->array[$key][0]['caller_id_number'] = $caller_id_number;
						$this->array[$key][0]['caller_destination'] = $caller_destination;
						$this->array[$key][0]['accountcode'] = urldecode($accountcode);
						$this->array[$key][0]['default_language'] = urldecode($xml->variables->default_language);
						$this->array[$key][0]['bridge_uuid'] = urldecode($xml->variables->bridge_uuid) ?: $last_bridge;
						//$this->array[$key][0]['digits_dialed'] = urldecode($xml->variables->digits_dialed);
						$this->array[$key][0]['sip_hangup_disposition'] = urldecode($xml->variables->sip_hangup_disposition);
						$this->array[$key][0]['pin_number'] = urldecode($xml->variables->pin_number);
						$this->array[$key][0]['status'] = $status;

					//time
						//catch invalid call detail records
						if (empty($xml->variables->start_epoch)) {
							//empty the array so it can't save
							$this->array = null;

							//move the file to the failed location
							$this->move_to_failed($this->file);

							//stop processing
							return;
						}
						$start_epoch = urldecode($xml->variables->start_epoch);
						$this->array[$key][0]['start_epoch'] = $start_epoch;
						$this->array[$key][0]['start_stamp'] = is_numeric((int)$start_epoch) ? date('c', $start_epoch) : null;
						$answer_epoch = urldecode($xml->variables->answer_epoch);
						$this->array[$key][0]['answer_epoch'] = $answer_epoch;
						$this->array[$key][0]['answer_stamp'] = is_numeric((int)$answer_epoch) ? date('c', $answer_epoch) : null;
						$end_epoch = urldecode($xml->variables->end_epoch);
						$this->array[$key][0]['end_epoch'] = $end_epoch;
						$this->array[$key][0]['end_stamp'] = is_numeric((int)$end_epoch) ? date('c', $end_epoch) : null;
						$this->array[$key][0]['duration'] = urldecode($xml->variables->billsec);
						$this->array[$key][0]['mduration'] = urldecode($xml->variables->billmsec);
						$this->array[$key][0]['billsec'] = urldecode($xml->variables->billsec);
						$this->array[$key][0]['billmsec'] = urldecode($xml->variables->billmsec);
						$this->array[$key][0]['hold_accum_seconds'] = urldecode($xml->variables->hold_accum_seconds);

					//codecs
						$this->array[$key][0]['read_codec'] = urldecode($xml->variables->read_codec);
						$this->array[$key][0]['read_rate'] = urldecode($xml->variables->read_rate);
						$this->array[$key][0]['write_codec'] = urldecode($xml->variables->write_codec);
						$this->array[$key][0]['write_rate'] = urldecode($xml->variables->write_rate);
						$this->array[$key][0]['remote_media_ip'] = urldecode($xml->variables->remote_media_ip);
						$this->array[$key][0]['hangup_cause'] = urldecode($xml->variables->hangup_cause);
						$this->array[$key][0]['hangup_cause_q850'] = urldecode($xml->variables->hangup_cause_q850);

					//store the call direction
						$this->array[$key][0]['direction'] = urldecode($call_direction);

					//call center
						if ($xml->variables->cc_member_uuid == '_undef_') { $xml->variables->cc_member_uuid = ''; }
						if ($xml->variables->cc_member_session_uuid == '_undef_') { $xml->variables->cc_member_session_uuid = ''; }
						if ($xml->variables->cc_agent_uuid == '_undef_') { $xml->variables->cc_agent_uuid = ''; }
						if ($xml->variables->call_center_queue_uuid == '_undef_') { $xml->variables->call_center_queue_uuid = ''; }
						if ($xml->variables->cc_queue_joined_epoch == '_undef_') { $xml->variables->cc_queue_joined_epoch = ''; }

						$this->array[$key][0]['cc_side'] = urldecode($xml->variables->cc_side);
						if (!empty($xml->variables->cc_member_uuid) && is_uuid(urldecode($xml->variables->cc_member_uuid))) {
							$this->array[$key][0]['cc_member_uuid'] = urldecode($xml->variables->cc_member_uuid);
						}
						$this->array[$key][0]['cc_queue'] = urldecode($xml->variables->cc_queue);
						if (!empty($xml->variables->call_center_queue_uuid) && is_uuid(urldecode($xml->variables->call_center_queue_uuid))) {
							$call_center_queue_uuid = urldecode($xml->variables->call_center_queue_uuid);
						}
						if (empty($call_center_queue_uuid) && !empty($xml->variables->cc_queue)) {
							$sql = "select call_center_queue_uuid from v_call_center_queues ";
							$sql .= "where domain_uuid = :domain_uuid ";
							$sql .= "and queue_extension = :queue_extension ";
							$parameters['domain_uuid'] = $domain_uuid;
							$parameters['queue_extension'] = explode("@", $xml->variables->cc_queue)[0];
							$call_center_queue_uuid = $this->database->select($sql, $parameters, 'column');
							unset($parameters);
						}
						if (!empty($call_center_queue_uuid) && is_uuid($call_center_queue_uuid)) {
							$this->array[$key][0]['call_center_queue_uuid'] = $call_center_queue_uuid;
						}
						if (!empty($xml->variables->cc_member_session_uuid) && is_uuid(urldecode($xml->variables->cc_member_session_uuid))) {
							$this->array[$key][0]['cc_member_session_uuid'] = urldecode($xml->variables->cc_member_session_uuid);
						}
						if (!empty($xml->variables->cc_agent_uuid) && is_uuid(urldecode($xml->variables->cc_agent_uuid))) {
							$this->array[$key][0]['cc_agent_uuid'] = urldecode($xml->variables->cc_agent_uuid);
						}
						$this->array[$key][0]['cc_agent'] = urldecode($xml->variables->cc_agent);
						$this->array[$key][0]['cc_agent_type'] = urldecode($xml->variables->cc_agent_type);
						$this->array[$key][0]['cc_agent_bridged'] = urldecode($xml->variables->cc_agent_bridged);
						if (!empty($xml->variables->cc_queue_joined_epoch) && is_numeric((int)$xml->variables->cc_queue_joined_epoch)) {
							$this->array[$key][0]['cc_queue_joined_epoch'] = urldecode($xml->variables->cc_queue_joined_epoch);
						}
						if (!empty($xml->variables->cc_queue_answered_epoch) && is_numeric((int)$xml->variables->cc_queue_answered_epoch)) {
							$this->array[$key][0]['cc_queue_answered_epoch'] = urldecode($xml->variables->cc_queue_answered_epoch);
						}
						if (!empty($xml->variables->cc_queue_terminated_epoch) && is_numeric((int)trim($xml->variables->cc_queue_terminated_epoch))) {
							$this->array[$key][0]['cc_queue_terminated_epoch'] = urldecode($xml->variables->cc_queue_terminated_epoch);
						}
						if (!empty($xml->variables->cc_queue_canceled_epoch) && is_numeric((int)$xml->variables->cc_queue_canceled_epoch)) {
							$this->array[$key][0]['cc_queue_canceled_epoch'] = urldecode($xml->variables->cc_queue_canceled_epoch);
						}
						$this->array[$key][0]['cc_cancel_reason'] = urldecode($xml->variables->cc_cancel_reason);
						$this->array[$key][0]['cc_cause'] = urldecode($xml->variables->cc_cause);
						$this->array[$key][0]['waitsec'] = urldecode($xml->variables->waitsec);
						if (urldecode($xml->variables->cc_side) == 'agent') {
							$this->array[$key][0]['direction'] = 'inbound';
						}

					//set the provider id
						if (isset($xml->variables->provider_uuid)) {
							$this->array[$key][0]['provider_uuid'] = urldecode($xml->variables->provider_uuid);
						}

					//app info
						$this->array[$key][0]['last_app'] = urldecode($xml->variables->last_app);
						$this->array[$key][0]['last_arg'] = urldecode($xml->variables->last_arg);

					//voicemail message success
						if (!empty($xml->variables->voicemail_answer_stamp) && $xml->variables->voicemail_message_seconds > 0){
							$this->array[$key][0]['voicemail_message'] = "true";
						}
						else { //if ($xml->variables->voicemail_action == "save") {
							$this->array[$key][0]['voicemail_message'] = "false";
						}

					//conference
						$this->array[$key][0]['conference_name'] = urldecode($xml->variables->conference_name);
						$this->array[$key][0]['conference_uuid'] = urldecode($xml->variables->conference_uuid);
						$this->array[$key][0]['conference_member_id'] = urldecode($xml->variables->conference_member_id);

					//call quality
						$rtp_audio_in_mos = urldecode($xml->variables->rtp_audio_in_mos);
						if (!empty($rtp_audio_in_mos)) {
							$this->array[$key][0]['rtp_audio_in_mos'] = $rtp_audio_in_mos;
						}

					//store the call leg
						$this->array[$key][0]['leg'] = $leg;

					//store the originating leg uuid
						$this->array[$key][0]['originating_leg_uuid'] = urldecode($xml->variables->originating_leg_uuid);

					//store post dial delay, in milliseconds
						$this->array[$key][0]['pdd_ms'] = urldecode((int)$xml->variables->progress_mediamsec) + (int)urldecode($xml->variables->progressmsec);

					//get break down the date to year, month and day
						$start_stamp = urldecode($xml->variables->start_stamp);
						$start_time = strtotime($start_stamp);
						$start_year = date("Y", $start_time);
						$start_month = date("M", $start_time);
						$start_day = date("d", $start_time);

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
						if (!empty($this->settings->get('cdr', 'field'))) {
							foreach ($this->settings->get('cdr', 'field') as $field) {
								$fields = explode(",", $field);
								$field_name = end($fields);
								$this->fields[] = $field_name;
								if (!isset($this->array[$key][0][$field_name])) {
									if (count($fields) == 1) {
										$this->array[$key][0][$field_name] = urldecode($xml->variables->{$fields[0]});
									}
									if (count($fields) == 2) {
										$this->array[$key][0][$field_name] = urldecode($xml->{$fields[0]}->{$fields[1]});
									}
									if (count($fields) == 3) {
										$this->array[$key][0][$field_name] = urldecode($xml->{$fields[0]}->{$fields[1]}->{$fields[2]});
									}
									if (count($fields) == 4) {
										$this->array[$key][0][$field_name] = urldecode($xml->{$fields[0]}->{$fields[1]}->{$fields[2]}->{$fields[3]});
									}
									if (count($fields) == 5) {
										$this->array[$key][0][$field_name] = urldecode($xml->{$fields[0]}->{$fields[1]}->{$fields[2]}->{$fields[3]}->{$fields[4]});
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
							$domain_uuid = $this->database->select($sql, $parameters, 'column');
							unset($parameters);
						}

					//set values in the database
						if (!empty($domain_uuid)) {
							$this->array[$key][0]['domain_uuid'] = $domain_uuid;
						}
						if (!empty($domain_name)) {
							$this->array[$key][0]['domain_name'] = $domain_name;
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
							$path = $this->settings->get('switch', 'recordings').'/'.$domain_name.'/archive/'.$start_year.'/'.$start_month.'/'.$start_day;
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
							$path = $this->settings->get('switch', 'recordings').'/'.$domain_name.'/archive/'.$start_year.'/'.$start_month.'/'.$start_day;
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
							$this->array[$key][0]['record_path'] = $record_path;
							$this->array[$key][0]['record_name'] = $record_name;
							if (isset($record_length)) {
								$this->array[$key][0]['record_length'] = $record_length;
							}
							else {
								$this->array[$key][0]['record_length'] = urldecode($xml->variables->duration);
							}
						}

					//save the xml object to json
						$this->json = json_encode($xml);

					//save to the database in xml format
						if ($this->settings->get('cdr', 'format') == "xml" && $this->settings->get('cdr', 'storage') == "db") {
							$this->array[$key][0]['xml'] = $xml_string;
						}

					//build the call detail array with json decode
						$this->call_details = json_decode($this->json, true);

					//get the extension_uuid and then add it to the database fields array
						if (isset($xml->variables->extension_uuid)) {
							$this->array[$key][0]['extension_uuid'] = urldecode($xml->variables->extension_uuid);
						}
						else {
							if (isset($domain_uuid) && isset($xml->variables->dialed_user)) {
								$sql = "select extension_uuid from v_extensions ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$sql .= "and (extension = :dialed_user or number_alias = :dialed_user) ";
								$parameters['domain_uuid'] = $domain_uuid;
								$parameters['dialed_user'] = $xml->variables->dialed_user;
								$extension_uuid = $this->database->select($sql, $parameters, 'column');
								$this->array[$key][0]['extension_uuid'] = $extension_uuid;
								unset($parameters);
							}
							if (isset($domain_uuid) && isset($xml->variables->referred_by_user)) {
								$sql = "select extension_uuid from v_extensions ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$sql .= "and (extension = :referred_by_user or number_alias = :referred_by_user) ";
								$parameters['domain_uuid'] = $domain_uuid;
								$parameters['referred_by_user'] = $xml->variables->referred_by_user;
								$extension_uuid = $this->database->select($sql, $parameters, 'column');
								$this->array[$key][0]['extension_uuid'] = $extension_uuid;
								unset($parameters);
							}
							if (isset($domain_uuid) && isset($xml->variables->last_sent_callee_id_number)) {
								$sql = "select extension_uuid from v_extensions ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$sql .= "and (extension = :last_sent_callee_id_number or number_alias = :last_sent_callee_id_number) ";
								$parameters['domain_uuid'] = $domain_uuid;
								$parameters['last_sent_callee_id_number'] = $xml->variables->last_sent_callee_id_number;
								$extension_uuid = $this->database->select($sql, $parameters, 'column');
								$this->array[$key][0]['extension_uuid'] = $extension_uuid;
								unset($parameters);
							}
						}

					//save the call flow json
						$key = 'xml_cdr_flow';
						$this->array[$key][0]['xml_cdr_flow_uuid'] = uuid();
						$this->array[$key][0]['xml_cdr_uuid'] = $uuid;
						$this->array[$key][0]['domain_uuid'] = $domain_uuid ?? '';
						$this->array[$key][0]['call_flow'] = json_encode($this->call_flow());

					//save to the database in json format
						if ($this->settings->get('cdr', 'format') == "json" && $this->settings->get('cdr', 'storage') == "db") {
							$key = 'xml_cdr_json';
							$this->array[$key][0]['xml_cdr_json_uuid'] = uuid();
							$this->array[$key][0]['xml_cdr_uuid'] = $uuid;
							$this->array[$key][0]['domain_uuid'] = $domain_uuid ?? '';
							$this->array[$key][0]['json'] = $this->json;
						}

					//save the call log to the database
						if ($this->settings->get('cdr', 'call_log_enabled', false) && !empty($this->settings->get('switch', 'log')) && $this->settings->get('cdr', 'storage') == "db") {
							//get the log content
							$log_content = '';
							$handle = @fopen($this->settings->get('switch', 'log').'/freeswitch.log', "r");
							if ($handle) {
								while (!feof($handle)) {
									$line = stream_get_line($handle, 0, "\n");
									if (substr($line, 0, 36 ) === $uuid) {
										$log_content .= substr($line, 37, strlen($line))."\n";
									}
								}
								fclose($handle);
							}

							//save to the database
							if (!empty($log_content)) {
								$key = 'xml_cdr_logs';
								$this->array[$key][0]['xml_cdr_log_uuid'] = uuid();
								$this->array[$key][0]['xml_cdr_uuid'] = $uuid;
								$this->array[$key][0]['domain_uuid'] = $domain_uuid ?? '';
								$this->array[$key][0]['log_date'] = 'now()';
								$this->array[$key][0]['log_content'] = $log_content;
							}
						}

					//store xml cdr on the file system as a file
						if ($this->settings->get('cdr', 'storage') == "dir" && $error != "true") {
							if (!empty($uuid)) {
								$tmp_dir = $this->settings->get('switch', 'log').'/xml_cdr/archive/'.$start_year.'/'.$start_month.'/'.$start_day;
								if(!file_exists($tmp_dir)) {
									mkdir($tmp_dir, 0770, true);
								}
								if ($this->settings->get('cdr', 'format') == "xml") {
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
			if (!empty($this->settings->get('domain', 'time_zone'))) {
				$time_zone = $this->settings->get('domain', 'time_zone');
			}
			else {
				$time_zone = date_default_timezone_get();
			}

			//set the time zone for php
			date_default_timezone_set($time_zone);

			//get the destination select list
			if ($this->destinations) {
				$destination_array = $this->destinations->get('dialplan');
			}

			//add new rows when callee_id_number exists
			$new_rows = 0;
			foreach ($call_flow_array as $key => $row) {
				//for outbound calls update the times if the bridged_time to remove the call setup plus the ring time
				if ($this->call_direction === 'outbound') {
						if (isset($row["times"]["bridged_time"]) and $row["times"]["bridged_time"] > 0) {
							//change the end time for the current row
							$call_flow_array[$key]["times"]["profile_created_time"] = $row["times"]["bridged_time"];
						}
				}

				//add a new row to the call summary
				if (!empty($row["caller_profile"]["destination_number"])
					and !empty($row["caller_profile"]["callee_id_number"])
					and $this->call_direction !== 'outbound'
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
			}

			//format the times in the call flow array
			$i = 0;
			foreach ($call_flow_array as $key => $row) {
				foreach ($row["times"] as $name => $value) {
					if ($value > 0) {
						$call_flow_array[$i]["times"][$name.'stamp'] = date("Y-m-d H:i:s", round((float) $value / 1000000, 0));
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
						if ($this->call_direction == 'outbound') {
							$app = $this->find_app($destination_array, urldecode($row["caller_profile"]["username"]));
						}
						else {
							$app = $this->find_app($destination_array, urldecode($row["caller_profile"]["destination_number"]));
						}
					}

					//call centers
					if (!empty($app['application']) && $app['application'] == 'call_centers') {
						if (isset($row["caller_profile"]["transfer_source"])) {
							$app['status'] = 'answered'; //Out
						}
						else {
							$app['status'] = 'waited'; //In
						}
					}

					//call flows
					if (!empty($app['application']) && $app['application'] == 'call_flows') {
						$app['status'] = 'routed';
					}

					//conferences
					if (!empty($app['application']) && $app['application'] == 'conferences') {
						$app['status'] = 'answered';
					}

					//destinations
					if (!empty($app['application']) && $app['application'] == 'destinations') {
						$app['status'] = 'routed';
					}

					//extensions
					if (!empty($app['application']) && $app['application'] == 'extensions') {
						if (!empty($row["times"]["profile_created_time"]) && !empty($row["times"]["profile_end_time"]) && (floor($row["times"]["profile_end_time"] / 1000000) - floor($row["times"]["profile_created_time"] / 1000000)) > 0) {
							$app['status'] = 'answered';
						}
						else {
							$app['status'] = 'missed';
						}
					}

					//ivr menus
					if (!empty($app['application']) && $app['application'] == 'ivr_menus') {
						$app['status'] = 'routed';
					}

					//add the source if there is a value
					if (!empty($row["caller_profile"]["username"])) {
						$app_source = $this->find_app($destination_array, $row["caller_profile"]["username"]);
						$app['source_number'] = $row["caller_profile"]["username"];
						$app['source_uuid'] = $app_source['uuid'];
						$app['source_name'] = $app_source['name'];
						$app['source_label'] = $app_source['label'];
					}

					//outbound routes
					if ($this->call_direction == 'outbound') {
						$status = 'missed';
						if (!empty($row["times"]["answered_time"])) {
							$status = 'answered';
						}

						if (!empty($row["caller_profile"]["username"])) {
							//add to the application array
							$app['application'] = 'extensions';
							$app['status'] = $status;
							$app['name'] = '';
							$app['label'] = 'extensions';
						}
						elseif (empty($app['application'])) {
							$app['application'] = 'diaplans';
							$app['uuid'] = '';
							$app['status'] = $status;
							$app['name'] = 'Outbound';
							$app['label'] = 'Outbound';
						}
					}

					//ring groups
					if (!empty($app['application']) && $app['application'] == 'ring_groups') {
						$app['status'] = 'waited';
					}

					//time conditions
					if (!empty($app['application']) && $app['application'] == 'time_conditions') {
						$app['status'] = 'routed';
					}

					//valet park
					if (
						!empty($row["caller_profile"]["destination_number"])
						&& (
							substr($row["caller_profile"]["destination_number"], 0, 4) == 'park'
							|| (
								substr($row["caller_profile"]["destination_number"], 0, 3) == '*59'
								&& strlen($row["caller_profile"]["destination_number"]) > 3
							)
						)
						) {
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
					if (!empty($app['application']) && $app['application'] == 'conferences') {
						$skip_row = true;
					}

					//voicemails
					if (!empty($app['application']) && $app['application'] == 'voicemails') {
						$app['status'] = 'voicemail';
					}

					//debug - add the callee_id_number to the end of the status
					if (isset($_REQUEST['debug']) && $_REQUEST['debug'] == 'true'
						&& !empty($row["caller_profile"]["destination_number"])
						&& !empty($row["caller_profile"]["callee_id_number"])
						&& $row["caller_profile"]["destination_number"] !== $row["caller_profile"]["callee_id_number"]) {
							$app['status'] .= ' ('.$row["caller_profile"]["callee_id_number"].')';
					}

					//build the application urls
					if (!empty($app['application'])) {
						//build the source url
						$source_url = '';
						if (!empty($app["source_uuid"])) {
							$source_url = "/app/".($app['application'] ?? '')."/".$this->singular($app['application'] ?? '')."_edit.php?id=".($app["source_uuid"] ?? '');
						}

						//build the destination url
						$destination_url = '';
						$destination_url = "/app/".($app['application'] ?? '')."/".$this->singular($app['application'] ?? '')."_edit.php?id=".($app["uuid"] ?? '');
						$application_url = "/app/".($app['application'] ?? '')."/".($app['application'] ?? '').".php";
						if ($app['application'] == 'call_centers') {
							$destination_url = "/app/".($app['application'] ?? '')."/".$this->singular($app['application'] ?? '')."_queue_edit.php?id=".($app["uuid"] ?? '');
							$application_url = "/app/".($app['application'] ?? '')."/".$this->singular($app['application'] ?? '')."_queues.php";
						}
					}

					//add the application and destination details
					$language2 = new text;
					$text2 = $language2->get($this->settings->get('domain', 'language'), 'app/'.($app['application'] ?? ''));
					$call_flow_summary[$x]["application_name"] = ($app['application'] ?? '');
					$call_flow_summary[$x]["application_label"] = trim($text2['title-'.($app['application'] ?? '')] ?? '');
					$call_flow_summary[$x]["call_direction"] = $this->call_direction;

					$call_flow_summary[$x]["application_url"] = $application_url;
					if ($this->call_direction == 'outbound') {
						$call_flow_summary[$x]["source_uuid"] = ($app['source_uuid'] ?? '');
						$call_flow_summary[$x]["source_number"] = ($app['source_number'] ?? '');
						$call_flow_summary[$x]["source_label"] = ($app['source_label'] ?? '');
						$call_flow_summary[$x]["source_url"] = $destination_url;
						$call_flow_summary[$x]["source_name"] = $app['description'] ?? '';
						//$call_flow_summary[$x]["source_description"] = $app['description'] ?? '';
						$call_flow_summary[$x]["destination_uuid"] = '';
						$call_flow_summary[$x]["destination_number"] = '';
						$call_flow_summary[$x]["destination_label"] = '';
						$call_flow_summary[$x]["destination_url"] = '';
						$call_flow_summary[$x]["destination_description"] = '';
					}
					else {
						$call_flow_summary[$x]["source_uuid"] = ($app['source_uuid'] ?? '');
						$call_flow_summary[$x]["source_number"] = ($app['source_number'] ?? '');
						$call_flow_summary[$x]["source_label"] = ($app['source_label'] ?? '');
						$call_flow_summary[$x]["source_url"] = ($source_url ?? '');
						$call_flow_summary[$x]["destination_name"] = ($app['description'] ?? '');
						$call_flow_summary[$x]["destination_uuid"] = ($app['uuid'] ?? '');
						$call_flow_summary[$x]["destination_label"] = ($app['label'] ?? '');
						$call_flow_summary[$x]["destination_url"] = $destination_url ?? '';
						//$call_flow_summary[$x]["destination_description"] = $app['description'] ?? '';
					}
					$call_flow_summary[$x]["destination_number"] = $row["caller_profile"]["destination_number"];
					$call_flow_summary[$x]["destination_status"] = ($app['status'] ?? '');
					$call_flow_summary[$x]["destination_description"] = $app['description'] ?? '';
					//$call_flow_summary[$x]["application"] = $app;

					//set the start and epoch
					$profile_created_epoch = $row['times']['profile_created_time'] / 1000000;
					$profile_end_epoch = $row['times']['profile_end_time'] / 1000000;

					//add the call flow times
					$call_flow_summary[$x]["start_epoch"] = round($profile_created_epoch);
					$call_flow_summary[$x]["end_epoch"] = round($profile_end_epoch);
					$call_flow_summary[$x]["start_stamp"] =  date("Y-m-d H:i:s", (int)$profile_created_epoch);
					$call_flow_summary[$x]["end_stamp"] =  date("Y-m-d H:i:s", (int)$profile_end_epoch);
					$call_flow_summary[$x]["duration_seconds"] =  round($profile_end_epoch - $profile_created_epoch);
					$call_flow_summary[$x]["duration_formatted"] =  gmdate("G:i:s",(int)$call_flow_summary[$x]["duration_seconds"]);
					unset($app);
					$x++;
				}
			}
			unset($x);

			//set the last status to match the call detail record
			$call_flow_summary[count($call_flow_summary)-1]['destination_status'] = $this->status;

			//return the call flow summary array
			return $call_flow_summary;
		}

	//add a function to return the find_app
		public function find_app($destination_array, $detail_action) {

			//add the destinations to the destination array
			$sql = "select * from v_destinations ";
			$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
			$parameters['domain_uuid'] = $this->domain_uuid;
			$destinations = $this->database->select($sql, $parameters, 'all');
			if (!empty($destinations)) {
				$i = 0;
				foreach($destinations as $row) {
					$destination_array['destinations'][$i]['application'] = 'destinations';
					$destination_array['destinations'][$i]['destination_uuid'] = $row["destination_uuid"];
					$destination_array['destinations'][$i]['uuid'] = $row["destination_uuid"];
					$destination_array['destinations'][$i]['dialplan_uuid'] = $row["dialplan_uuid"];
					$destination_array['destinations'][$i]['destination_type'] = $row["destination_type"];
					$destination_array['destinations'][$i]['destination_prefix'] = $row["destination_prefix"];
					$destination_array['destinations'][$i]['destination_number'] = $row["destination_number"];
					$destination_array['destinations'][$i]['extension'] = $row["destination_prefix"] . $row["destination_number"];
					$destination_array['destinations'][$i]['destination_trunk_prefix'] = $row["destination_trunk_prefix"];
					$destination_array['destinations'][$i]['destination_area_code'] = $row["destination_area_code"];
					$destination_array['destinations'][$i]['context'] = $row["destination_context"];
					$destination_array['destinations'][$i]['label'] = $row["destination_description"];
					$destination_array['destinations'][$i]['destination_enabled'] = $row["destination_enabled"];
					$destination_array['destinations'][$i]['name'] = $row["destination_description"];
					$destination_array['destinations'][$i]['description'] = $row["destination_description"];
					//$destination_array[$i]['destination_caller_id_name'] = $row["destination_caller_id_name"];
					//$destination_array[$i]['destination_caller_id_number'] = $row["destination_caller_id_number"];
					$i++;
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
								if ('+'.($value['destination_prefix'] ?? '').$value['destination_number'] == $detail_action
									|| ($value['destination_prefix'] ?? '').$value['destination_number'] == $detail_action
									|| $value['destination_number'] == $detail_action
									|| ($value['destination_trunk_prefix'] ?? '').$value['destination_number'] == $detail_action
									|| '+'.($value['destination_prefix'] ?? '').($value['destination_area_code'] ?? '').$value['destination_number'] == $detail_action
									|| ($value['destination_prefix'] ?? '').($value['destination_area_code'] ?? '').$value['destination_number'] == $detail_action
									|| ($value['destination_area_code'] ?? '').$value['destination_number'] == $detail_action) {
										if (file_exists($_SERVER["PROJECT_ROOT"]."/app/".$application."/app_languages.php")) {
											$value['application'] = $application;
											return $value;
										}
								}
							}

							//find all other matching actions
							if (!empty($value['extension']) && $value['extension'] == $detail_action || preg_match('/^'.preg_quote($value['extension'] ?? '').'$/', $detail_action)) {
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

		public function move_to_failed($failed_file) {
			$xml_cdr_dir = $this->settings->get('switch', 'log', '/var/log/freeswitch').'/xml_cdr';
			if (!file_exists($xml_cdr_dir.'/failed')) {
				if (!mkdir($xml_cdr_dir.'/failed', 0660, true)) {
					die('Failed to create '.$xml_cdr_dir.'/failed');
				}
			}
			rename($xml_cdr_dir.'/'.$failed_file, $xml_cdr_dir.'/failed/'.$failed_file);
		}

		/**
		 * get xml from the filesystem and save it to the database
		 */
		public function read_files() {
			$xml_cdr_dir = $this->settings->get('switch', 'log').'/xml_cdr';
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
							if (isset($_SERVER["argv"][1]) && is_numeric((int)$_SERVER["argv"][1])) {
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

						//move the files that are too large or zero file size to the failed directory
							if ($import && (filesize($xml_cdr_dir.'/'.$file) >= 3000000 || filesize($xml_cdr_dir.'/'.$file) == 0)) {
								//echo "WARNING: File too large or zero file size. Moving $file to failed\n";
								if (!empty($xml_cdr_dir)) {
									if (!file_exists($xml_cdr_dir.'/failed')) {
										if (!mkdir($xml_cdr_dir.'/failed', 0660, true)) {
											die('Failed to create '.$xml_cdr_dir.'/failed');
										}
									}
									if (rename($xml_cdr_dir.'/'.$file, $xml_cdr_dir.'/failed/'.$file)) {
										//echo "Moved $file successfully\n";
									}
								}
							}

						//import the call detail files are less than 3 mb - 3 million bytes
							if ($import) {
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
						if ($this->settings->get('cdr', 'http_enabled', false)) {
							//get the contents of xml_cdr.conf.xml
								$conf_xml_string = file_get_contents($this->settings->get('switch', 'conf').'/autoload_configs/xml_cdr.conf.xml');

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
						if ($this->settings->get('cdr', 'http_enabled', false)) {
							openlog('FusionPBX', LOG_NDELAY, LOG_AUTH);
							syslog(LOG_WARNING, '['.$_SERVER['REMOTE_ADDR'].'] XML CDR import default setting http_enabled is not enabled. Line: '.__line__);
							closelog();

							echo "access denied\n";
							return;
						}
					}

				//check for the correct username and password
					if (!defined('STDIN')) {
						if ($this->settings->get('cdr', 'http_enabled', false)) {
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
				if (!empty($this->settings->get('domain', 'time_zone'))) {
					$time_zone = $this->settings->get('domain', 'time_zone');
				}
				else {
					$time_zone = date_default_timezone_get();
				}

			//set the time zone for php
				date_default_timezone_set($time_zone);

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
				$sql .= " and status = 'answered' \n";
				if (!$this->include_internal) {
					$sql .= "and (direction = 'inbound' or direction = 'outbound') \n";
				}
				$sql .= ") \n";
				$sql .= "as answered, \n";

				//missed
				$sql .= "count(*) \n";
				$sql .= "filter ( \n";
				$sql .= " where c.extension_uuid = e.extension_uuid \n";
				$sql .= " and status = 'missed' \n";
				$sql .= " and (cc_side is null or cc_side != 'agent') \n";
				if (!$this->include_internal) {
					$sql .= "and (direction = 'inbound' or direction = 'outbound') \n";
				}
				$sql .= ") \n";
				$sql .= "as missed, \n";

				//voicemail
				$sql .= "count(*) \n";
				$sql .= "filter ( \n";
				$sql .= " where c.extension_uuid = e.extension_uuid \n";
				$sql .= " and status = 'voicemail' \n";
				if (!$this->include_internal) {
					$sql .= "and (direction = 'inbound' or direction = 'outbound') \n";
				}
				$sql .= ") \n";
				$sql .= "as voicemail, \n";

				//no answer
				$sql .= "count(*) \n";
				$sql .= "filter ( \n";
				$sql .= " where c.extension_uuid = e.extension_uuid \n";
				$sql .= " and status = 'no_answer'\n";
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
				$sql .= " and status = 'busy'\n";
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
				$sql .= " voicemail_message, \n";
				$sql .= " status \n";
				$sql .= " from v_xml_cdr \n";
				if (!(!empty($_GET['show']) && $_GET['show'] === 'all' && permission_exists('xml_cdr_extension_summary_all'))) {
					$sql .= " where domain_uuid = :domain_uuid \n";
				}
				else {
					$sql .= " where true \n";
				}
				$sql .= "and leg = 'a' ";
				$sql .= "and extension_uuid is not null ";
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
				$summary = $this->database->select($sql, $parameters, 'all');
				unset($parameters);

			//return the array
				return $summary;
		}

		/**
		 * download the recordings
		 */
		public function download() {

			//check the permission
			if (!permission_exists('xml_cdr_view')) {
				//echo "permission denied";
				return;
			}

			//check for a valid uuid
			if (!is_uuid($this->recording_uuid)) {
				//echo "invalid uuid";
				return;
			}

			//get call recording from database
			$sql = "select record_name, record_path from v_xml_cdr ";
			$sql .= "where xml_cdr_uuid = :xml_cdr_uuid ";
			$parameters['xml_cdr_uuid'] = $this->recording_uuid;
			$row = $this->database->select($sql, $parameters, 'row');
			if (!empty($row) && is_array($row)) {
				$record_name = $row['record_name'];
				$record_path = $row['record_path'];
			}
			unset ($sql, $parameters, $row);

			//build full path
			$record_file = $record_path.'/'.$record_name;

			//download the file
			if ($record_file != '/' && file_exists($record_file)) {
				ob_clean();
				$fd = fopen($record_file, "rb");
				if ($this->binary) {
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
				if ($this->binary) {
					header("Content-Length: ".filesize($record_file));
				}
				ob_clean();

				//content-range
				if (isset($_SERVER['HTTP_RANGE']) && !$this->binary)  {
					$this->range_download($record_file);
				}

				fpassthru($fd);
			}

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
			header("Accept-Ranges: 0-".$length);
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
				// And make sure to get the end byte if specified
				if ($range[0] == '-') {
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
				$row = $this->database->select($sql, $parameters, 'row');
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
			$p = permissions::new();
			$p->add('call_recording_delete', 'temp');

			//execute delete
			$this->database->app_name = $this->app_name;
			$this->database->app_uuid = $this->app_uuid;
			$this->database->delete($array);
			unset($array);

			//revoke temporary permissions
			$p->delete('call_recording_delete', 'temp');

			//set message
			message::add($text['message-delete'].": ".$records_deleted);

			unset($records);
		} //method


		/**
		* define singular function to convert a word in english to singular
		*/
		public function singular($word) {
			//"-es" is used for words that end in "-x", "-s", "-z", "-sh", "-ch" in which case you add
			if (substr($word, -2) == "es") {
				if (substr($word, -4) == "sses") { // eg. 'addresses' to 'address'
					return substr($word,0,-2);
				}
				elseif (substr($word, -3) == "ses") { // eg. 'databases' to 'database' (necessary!)
					return substr($word,0,-1);
				}
				elseif (substr($word, -3) == "ies") { // eg. 'countries' to 'country'
					return substr($word,0,-3)."y";
				}
				elseif (substr($word, -3, 1) == "x") {
					return substr($word,0,-2);
				}
				elseif (substr($word, -3, 1) == "s") {
					return substr($word,0,-2);
				}
				elseif (substr($word, -3, 1) == "z") {
					return substr($word,0,-2);
				}
				elseif (substr($word, -4, 2) == "sh") {
					return substr($word,0,-2);
				}
				elseif (substr($word, -4, 2) == "ch") {
					return substr($word,0,-2);
				}
				else {
					return rtrim($word, "s");
				}
			}
			else {
				return rtrim($word, "s");
			}
		} //method

		/**
		 * Removes old entries for in the database xml_cdr, xml_cdr_flow, xml_cdr_json, xml_cdr_logs table
		 * see {@link https://github.com/fusionpbx/fusionpbx-app-maintenance/} FusionPBX Maintenance App
		 * @param settings $settings Settings object
		 * @return void
		 */
		public static function database_maintenance(settings $settings): void {
			//set table name for query
			$table = 'xml_cdr';

			//get a database connection
			$database = $settings->database();

			//get a list of domains
			$domains = maintenance::get_domains($database);
			foreach ($domains as $domain_uuid => $domain_name) {

				//get domain settings
				$domain_settings = new settings(['database' => $database, 'domain_uuid' => $domain_uuid]);

				//get the retention days for xml cdr table using 'cdr' and 'database_retention_days'
				$xml_cdr_retention_days = $domain_settings->get('cdr', 'database_retention_days', '');

				//get the retention days for xml cdr flow table
				if ($database->table_exists('xml_cdr_flow')) {
					$xml_cdr_flow_retention_days = $domain_settings->get('cdr', 'flow_database_retention_days', $xml_cdr_retention_days);
				} else {
					$xml_cdr_flow_retention_days = null;
				}

				//get the retention days for xml cdr json table
				if ($database->table_exists('xml_cdr_json')) {
					$xml_cdr_json_retention_days = $domain_settings->get('cdr', 'json_database_retention_days', $xml_cdr_retention_days);
				} else {
					$xml_cdr_json_retention_days = null;
				}

				//get the retention days for xml cdr logs table
				if ($database->table_exists('xml_cdr_logs')) {
					$xml_cdr_logs_retention_days = $domain_settings->get('cdr', 'logs_database_retention_days', $xml_cdr_retention_days);
				} else {
					$xml_cdr_logs_retention_days = null;
				}

				//ensure we have retention days
				if (!empty($xml_cdr_retention_days) && is_numeric((int)$xml_cdr_retention_days)) {

					//clear out old xml_cdr records
					$sql = "delete from v_{$table} WHERE insert_date < NOW() - INTERVAL '{$xml_cdr_retention_days} days'"
						. " and domain_uuid = '{$domain_uuid}'";
					$database->execute($sql);
					$code = $database->message['code'] ?? 0;
					//record result
					if ($code == 200) {
						maintenance_service::log_write(self::class, "Successfully removed entries older than $xml_cdr_retention_days", $domain_uuid);
					} else {
						$message = $database->message['message'] ?? "An unknown error has occurred";
						maintenance_service::log_write(self::class, "XML CDR " . "Unable to remove old database records. Error message: $message ($code)", $domain_uuid, maintenance_service::LOG_ERROR);
					}

					//clear out old xml_cdr_flow records
					if (!empty($xml_cdr_flow_retention_days)) {
						$sql = "delete from v_xml_cdr_flow WHERE insert_date < NOW() - INTERVAL '{$xml_cdr_flow_retention_days} days'"
							. " and domain_uuid = '{$domain_uuid}";
						$database->execute($sql);
						$code = $database->message['code'] ?? 0;
						//record result
						if ($database->message['code'] == 200) {
							maintenance_service::log_write(self::class, "Successfully removed XML CDR FLOW entries from $domain_name", $domain_uuid);
						} else {
							$message = $database->message['message'] ?? "An unknown error has occurred";
							maintenance_service::log_write(self::class, "XML CDR FLOW " . "Unable to remove old database records. Error message: $message ($code)", $domain_uuid, maintenance_service::LOG_ERROR);
						}
					}

					//clear out old xml_cdr_json records
					if (!empty($xml_cdr_json_retention_days)) {
						$sql = "DELETE FROM v_xml_cdr_json WHERE insert_date < NOW() - INTERVAL '{$xml_cdr_json_retention_days} days'"
							. " and domain_uuid = '{$domain_uuid}";
						$database->execute($sql);
						$code = $database->message['code'] ?? 0;
						//record result
						if ($database->message['code'] == 200) {
							maintenance_service::log_write(self::class, "Successfully removed XML CDR JSON entries from $domain_name", $domain_uuid);
						} else {
							$message = $database->message['message'] ?? "An unknown error has occurred";
							maintenance_service::log_write(self::class, "XML CDR JSON " . "Unable to remove old database records. Error message: $message ($code)", $domain_uuid, maintenance_service::LOG_ERROR);
						}
					}

					//clear out old xml_cdr_logs records
					if (!empty($xml_cdr_logs_retention_days)) {
						$sql = "DELETE FROM v_xml_cdr_logs WHERE insert_date < NOW() - INTERVAL '{$xml_cdr_logs_retention_days} days'"
							. " and domain_uuid = '{$domain_uuid}'";
						$database->execute($sql);
						$code = $database->message['code'] ?? 0;
						//record result
						if ($database->message['code'] === 200) {
							maintenance_service::log_write(self::class, "Successfully removed XML CDR LOG entries from $domain_name", $domain_uuid);
						} else {
							$message = $database->message['message'] ?? "An unknown error has occurred";
							maintenance_service::log_write(self::class, "XML CDR LOG " . "Unable to remove old database records. Error message: $message ($code)", $domain_uuid, maintenance_service::LOG_ERROR);
						}
					}
				}
			}

			//ensure logs are saved
			maintenance_service::log_flush();
		}

		/**
		 * Return CDR for the default settings category name instead of using the class name xml_cdr
		 * @return string Returns 'CDR' for the name
		 */
		public static function database_maintenance_category(): string {
			return "cdr";
		}

	} //class
