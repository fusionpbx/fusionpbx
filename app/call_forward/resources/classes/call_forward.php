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
	  Copyright (C) 2010 - 2023
	  All Rights Reserved.

	  Contributor(s):
	  Mark J Crane <markjcrane@fusionpbx.com>
	  Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
	  Errol Samuels <voiptology@gmail.com>

	 */

//define the call_forward class
	class call_forward {

		// set class constants
		const APP_NAME = 'calls';
		const APP_UUID = '19806921-e8ed-dcff-b325-dd3e5da4959d';
		const PERMISSION = 'call_forward';
		const LIST_PAGE = 'calls.php';
		const TABLE = 'extensions';
		const UUID_PREFIX = 'extension_';
		const TOGGLE_FIELD = 'forward_all_enabled';
		const TOGGLE_VALUES = ['true', 'false'];

		public $debug;
		public $domain_uuid;
		public $domain_name;
		public $extension_uuid;
		public $forward_all_destination;
		public $forward_all_enabled;
		public $accountcode;
		public $outbound_caller_id_name;
		public $outbound_caller_id_number;

		/**
		 * declare private variables
		 */
		private $extension;
		private $number_alias;
		private $toll_allow;

		public function set() {
			//create the database connection
			$database = new database;

			//determine whether to update the dial string
			$sql = "select * from v_extensions ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$sql .= "and extension_uuid = :extension_uuid ";
			$parameters['domain_uuid'] = $this->domain_uuid;
			$parameters['extension_uuid'] = $this->extension_uuid;
			$row = $database->select($sql, $parameters, 'row');
			if (is_array($row) && @sizeof($row) != 0) {
				$this->extension = $row["extension"];
				$this->number_alias = $row["number_alias"];
				$this->accountcode = $row["accountcode"];
				$this->toll_allow = $row["toll_allow"];
				$this->outbound_caller_id_name = $row["outbound_caller_id_name"];
				$this->outbound_caller_id_number = $row["outbound_caller_id_number"];
			}
			unset($sql, $parameters, $row);

			//build extension update array
			$array['extensions'][0]['extension_uuid'] = $this->extension_uuid;
			$array['extensions'][0]['forward_all_destination'] = strlen($this->forward_all_destination) != 0 ? $this->forward_all_destination : null;
			if (empty($this->forward_all_destination) || $this->forward_all_enabled == "false") {
				$array['extensions'][0]['forward_all_enabled'] = 'false';
			} else {
				$array['extensions'][0]['forward_all_enabled'] = 'true';
			}

			//grant temporary permissions
			$p = new permissions;
			$p->add('extension_add', 'temp');

			//execute update
			$database->app_name = 'calls';
			$database->app_uuid = '19806921-e8ed-dcff-b325-dd3e5da4959d';
			$database->save($array);
			unset($array);

			//revoke temporary permissions
			$p->delete('extension_add', 'temp');

			//delete extension from the cache
			$cache = new cache;
			$cache->delete("directory:" . $this->extension . "@" . $this->domain_name);
			if (!empty($this->number_alias)) {
				$cache->delete("directory:" . $this->number_alias . "@" . $this->domain_name);
			}
		}

		/**
		 * Toggle an array of call_forward records
		 * @param array $records array of records to toggle
		 */
		public function toggle(array $records) {

			//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'], 'negative');
				header('Location: ' . self::LIST_PAGE);
				exit;
			}

			//validate there are records to process
			if (count($records) < 1) return;

			//check we have permission for this action
			if (permission_exists(self::PERMISSION)) {

				//create the database connection
				$database = new database;

				//add multi-lingual support
				$language = new text;
				$text = $language->get();

				
				// initialize an empty array
				$uuids = [];
				$extensions = [];
				
				//get current toggle state
				foreach ($records as $x => $record) {
					if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['uuid'])) {
						$uuids[] = "'" . $record['uuid'] . "'";
					}
				}

				//toggle the checked records
				if (count($uuids) > 0) {
					$sql = "select " . self::UUID_PREFIX . "uuid as uuid, extension, number_alias, ";
					$sql .= "call_timeout, do_not_disturb, ";
					$sql .= "forward_all_enabled, forward_all_destination, ";
					$sql .= "forward_busy_enabled, forward_busy_destination, ";
					$sql .= "forward_no_answer_enabled, forward_no_answer_destination, ";
					$sql .= self::TOGGLE_FIELD . " as toggle, follow_me_uuid ";
					$sql .= "from v_" . self::TABLE . " ";
					$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
					$sql .= "and " . self::UUID_PREFIX . "uuid in (" . implode(', ', $uuids) . ") ";
					$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
					$rows = $database->select($sql, $parameters, 'all');
					if (is_array($rows) && @sizeof($rows) != 0) {
						foreach ($rows as $row) {
							$extensions[$row['uuid']]['extension'] = $row['extension'];
							$extensions[$row['uuid']]['number_alias'] = $row['number_alias'];
							$extensions[$row['uuid']]['call_timeout'] = $row['call_timeout'];
							$extensions[$row['uuid']]['do_not_disturb'] = $row['do_not_disturb'];
							$extensions[$row['uuid']]['forward_all_enabled'] = $row['forward_all_enabled'];
							$extensions[$row['uuid']]['forward_all_destination'] = $row['forward_all_destination'];
							$extensions[$row['uuid']]['forward_busy_enabled'] = $row['forward_busy_enabled'];
							$extensions[$row['uuid']]['forward_busy_destination'] = $row['forward_busy_destination'];
							$extensions[$row['uuid']]['forward_no_answer_enabled'] = $row['forward_no_answer_enabled'];
							$extensions[$row['uuid']]['forward_no_answer_destination'] = $row['forward_no_answer_destination'];
							$extensions[$row['uuid']]['state'] = $row['toggle'];
							$extensions[$row['uuid']]['follow_me_uuid'] = $row['follow_me_uuid'];
						}
					}
					unset($sql, $parameters, $rows, $row);
				}

				//build update array
				$x = 0;
				foreach ($extensions as $uuid => $extension) {

					//check destination
					$destination_exists = $extension['forward_all_destination'] != '' ? true : false;

					//determine new state
					$new_state = $extension['state'] == self::TOGGLE_VALUES[1] && $destination_exists ? self::TOGGLE_VALUES[0] : self::TOGGLE_VALUES[1];

					//toggle feature
					if ($new_state != $extension['state']) {
						$array[self::TABLE][$x][self::UUID_PREFIX . 'uuid'] = $uuid;
						$array[self::TABLE][$x][self::TOGGLE_FIELD] = $new_state;
					}

					//disable other features
					if ($new_state == self::TOGGLE_VALUES[0]) { //true
						$array[self::TABLE][$x]['do_not_disturb'] = self::TOGGLE_VALUES[1]; //false
						$array[self::TABLE][$x]['follow_me_enabled'] = self::TOGGLE_VALUES[1]; //false
						if (is_uuid($extension['follow_me_uuid'])) {
							$array['follow_me'][$x]['follow_me_uuid'] = $extension['follow_me_uuid'];
							$array['follow_me'][$x]['follow_me_enabled'] = self::TOGGLE_VALUES[1]; //false
						}
					}

					//increment counter
					$x++;
				}

				//save the changes
				if (is_array($array) && @sizeof($array) != 0) {

					//grant temporary permissions
					$p = new permissions;
					$p->add('extension_edit', 'temp');

					//save the array
					$database->app_name = self::APP_NAME;
					$database->app_uuid = self::APP_UUID;
					$database->save($array);
					unset($array);

					//revoke temporary permissions
					$p->delete('extension_edit', 'temp');

					//send feature event notify to the phone
					if (!empty($_SESSION['device']['feature_sync']['boolean']) && $_SESSION['device']['feature_sync']['boolean'] == "true") {
						foreach ($extensions as $uuid => $extension) {
							$feature_event_notify = new feature_event_notify;
							$feature_event_notify->domain_name = $_SESSION['domain_name'];
							$feature_event_notify->extension = $extension['extension'];
							$feature_event_notify->do_not_disturb = $extension['do_not_disturb'];
							$feature_event_notify->ring_count = ceil($extension['call_timeout'] / 6);
							$feature_event_notify->forward_all_enabled = $extension['forward_all_enabled'];
							$feature_event_notify->forward_busy_enabled = $extension['forward_busy_enabled'];
							$feature_event_notify->forward_no_answer_enabled = $extension['forward_no_answer_enabled'];
							//workarounds: send 0 as freeswitch doesn't send NOTIFY when destination values are nil
							$feature_event_notify->forward_all_destination = $extension['forward_all_destination'] != '' ? $extension['forward_all_destination'] : '0';
							$feature_event_notify->forward_busy_destination = $extension['forward_busy_destination'] != '' ? $extension['forward_busy_destination'] : '0';
							$feature_event_notify->forward_no_answer_destination = $extension['forward_no_answer_destination'] != '' ? $extension['forward_no_answer_destination'] : '0';
							$feature_event_notify->send_notify();
							unset($feature_event_notify);
						}
					}

					//synchronize configuration
					if (!empty($_SESSION['switch']['extensions']['dir']) && is_readable($_SESSION['switch']['extensions']['dir'])) {
						require_once "app/extensions/resources/classes/extension.php";
						$ext = new extension;
						$ext->xml();
						unset($ext);
					}

					//clear the cache
					$cache = new cache;
					foreach ($extensions as $uuid => $extension) {
						$cache->delete("directory:" . $extension['extension'] . "@" . $_SESSION['domain_name']);
						if ($extension['number_alias'] != '') {
							$cache->delete("directory:" . $extension['number_alias'] . "@" . $_SESSION['domain_name']);
						}
					}

					//set message
					message::add($text['message-toggle']);
				}
				unset($records, $extensions, $extension);
			}
		}

//function
	}

	// class
?>
