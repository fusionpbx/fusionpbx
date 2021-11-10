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
	Copyright (C) 2010 - 2019
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
	Salvatore Caruso <salvatore.caruso@nems.it>
	Riccardo Granchi <riccardo.granchi@nems.it>
	Errol Samuels <voiptology@gmail.com>
*/
include "root.php";

//define the follow me class
	class follow_me {
		public $domain_uuid;
		private $domain_name;
		public $db_type;
		public $follow_me_uuid;
		public $cid_name_prefix;
		public $cid_number_prefix;
		public $accountcode;
		public $follow_me_enabled;
		public $follow_me_ignore_busy;
		public $outbound_caller_id_name;
		public $outbound_caller_id_number;
		private $extension;
		private $number_alias;
		private $toll_allow;

		public $destination_data_1;
		public $destination_type_1;
		public $destination_delay_1;
		public $destination_prompt_1;
		public $destination_timeout_1;

		public $destination_data_2;
		public $destination_type_2;
		public $destination_delay_2;
		public $destination_prompt_2;
		public $destination_timeout_2;

		public $destination_data_3;
		public $destination_type_3;
		public $destination_delay_3;
		public $destination_prompt_3;
		public $destination_timeout_3;

		public $destination_data_4;
		public $destination_type_4;
		public $destination_delay_4;
		public $destination_prompt_4;
		public $destination_timeout_4;

		public $destination_data_5;
		public $destination_type_5;
		public $destination_delay_5;
		public $destination_prompt_5;
		public $destination_timeout_5;

		public $destination_timeout = 0;
		public $destination_order = 1;

		public function add() {

			//build follow me insert array
				$array['follow_me'][0]['follow_me_uuid'] = $this->follow_me_uuid;
				$array['follow_me'][0]['domain_uuid'] = $this->domain_uuid;
				$array['follow_me'][0]['cid_name_prefix'] = $this->cid_name_prefix;
				if (strlen($this->cid_number_prefix) > 0) {
					$array['follow_me'][0]['cid_number_prefix'] = $this->cid_number_prefix;
				}

				$array['follow_me'][0]['follow_me_enabled'] = $this->follow_me_enabled;
				$array['follow_me'][0]['follow_me_ignore_busy'] = $this->follow_me_ignore_busy;
			//grant temporary permissions
				$p = new permissions;
				$p->add('follow_me_add', 'temp');
			//execute insert
				$database = new database;
				$database->app_name = 'calls';
				$database->app_uuid = '19806921-e8ed-dcff-b325-dd3e5da4959d';
				$database->save($array);
				unset($array);
			//revoke temporary permissions
				$p->delete('follow_me_add', 'temp');

				$this->follow_me_destinations();

		}

		public function update() {

			//build follow me update array
				$array['follow_me'][0]['follow_me_uuid'] = $this->follow_me_uuid;
				$array['follow_me'][0]['cid_name_prefix'] = $this->cid_name_prefix;
				$array['follow_me'][0]['cid_number_prefix'] = $this->cid_number_prefix;
				$array['follow_me'][0]['follow_me_enabled'] = $this->follow_me_enabled;
				$array['follow_me'][0]['follow_me_ignore_busy'] = $this->follow_me_ignore_busy;
			//grant temporary permissions
				$p = new permissions;
				$p->add('follow_me_add', 'temp');
			//execute update
				$database = new database;
				$database->app_name = 'calls';
				$database->app_uuid = '19806921-e8ed-dcff-b325-dd3e5da4959d';
				$database->save($array);
				unset($array);
			//revoke temporary permissions
				$p->delete('follow_me_add', 'temp');

				$this->follow_me_destinations();

		}

		public function follow_me_destinations() {

			//delete related follow me destinations
				$array['follow_me_destinations'][0]['follow_me_uuid'] = $this->follow_me_uuid;
				//grant temporary permissions
					$p = new permissions;
					$p->add('follow_me_destination_delete', 'temp');
				//execute delete
					$database = new database;
					$database->app_name = 'calls';
					$database->app_uuid = '19806921-e8ed-dcff-b325-dd3e5da4959d';
					$database->delete($array);
					unset($array);
				//revoke temporary permissions
					$p->delete('follow_me_destination_delete', 'temp');

			//build follow me destinations insert array
				$x = 0;
				if (strlen($this->destination_data_1) > 0) {
					$array['follow_me_destinations'][$x]['follow_me_destination_uuid'] = uuid();
					$array['follow_me_destinations'][$x]['domain_uuid'] = $this->domain_uuid;
					$array['follow_me_destinations'][$x]['follow_me_uuid'] = $this->follow_me_uuid;
					$array['follow_me_destinations'][$x]['follow_me_destination'] = $this->destination_data_1;
					$array['follow_me_destinations'][$x]['follow_me_timeout'] = $this->destination_timeout_1;
					$array['follow_me_destinations'][$x]['follow_me_delay'] = $this->destination_delay_1;
					$array['follow_me_destinations'][$x]['follow_me_prompt'] = $this->destination_prompt_1;
					$array['follow_me_destinations'][$x]['follow_me_order'] = '1';
					$this->destination_order++;
					$x++;
				}
				if (strlen($this->destination_data_2) > 0) {
					$array['follow_me_destinations'][$x]['follow_me_destination_uuid'] = uuid();
					$array['follow_me_destinations'][$x]['domain_uuid'] = $this->domain_uuid;
					$array['follow_me_destinations'][$x]['follow_me_uuid'] = $this->follow_me_uuid;
					$array['follow_me_destinations'][$x]['follow_me_destination'] = $this->destination_data_2;
					$array['follow_me_destinations'][$x]['follow_me_timeout'] = $this->destination_timeout_2;
					$array['follow_me_destinations'][$x]['follow_me_delay'] = $this->destination_delay_2;
					$array['follow_me_destinations'][$x]['follow_me_prompt'] = $this->destination_prompt_2;
					$array['follow_me_destinations'][$x]['follow_me_order'] = '2';
					$this->destination_order++;
					$x++;
				}
				if (strlen($this->destination_data_3) > 0) {
					$array['follow_me_destinations'][$x]['follow_me_destination_uuid'] = uuid();
					$array['follow_me_destinations'][$x]['domain_uuid'] = $this->domain_uuid;
					$array['follow_me_destinations'][$x]['follow_me_uuid'] = $this->follow_me_uuid;
					$array['follow_me_destinations'][$x]['follow_me_destination'] = $this->destination_data_3;
					$array['follow_me_destinations'][$x]['follow_me_timeout'] = $this->destination_timeout_3;
					$array['follow_me_destinations'][$x]['follow_me_delay'] = $this->destination_delay_3;
					$array['follow_me_destinations'][$x]['follow_me_prompt'] = $this->destination_prompt_3;
					$array['follow_me_destinations'][$x]['follow_me_order'] = '3';
					$this->destination_order++;
					$x++;
				}
				if (strlen($this->destination_data_4) > 0) {
					$array['follow_me_destinations'][$x]['follow_me_destination_uuid'] = uuid();
					$array['follow_me_destinations'][$x]['domain_uuid'] = $this->domain_uuid;
					$array['follow_me_destinations'][$x]['follow_me_uuid'] = $this->follow_me_uuid;
					$array['follow_me_destinations'][$x]['follow_me_destination'] = $this->destination_data_4;
					$array['follow_me_destinations'][$x]['follow_me_timeout'] = $this->destination_timeout_4;
					$array['follow_me_destinations'][$x]['follow_me_delay'] = $this->destination_delay_4;
					$array['follow_me_destinations'][$x]['follow_me_prompt'] = $this->destination_prompt_4;
					$array['follow_me_destinations'][$x]['follow_me_order'] = '4';
					$this->destination_order++;
					$x++;
				}
				if (strlen($this->destination_data_5) > 0) {
					$array['follow_me_destinations'][$x]['follow_me_destination_uuid'] = uuid();
					$array['follow_me_destinations'][$x]['domain_uuid'] = $this->domain_uuid;
					$array['follow_me_destinations'][$x]['follow_me_uuid'] = $this->follow_me_uuid;
					$array['follow_me_destinations'][$x]['follow_me_destination'] = $this->destination_data_5;
					$array['follow_me_destinations'][$x]['follow_me_timeout'] = $this->destination_timeout_5;
					$array['follow_me_destinations'][$x]['follow_me_delay'] = $this->destination_delay_5;
					$array['follow_me_destinations'][$x]['follow_me_prompt'] = $this->destination_prompt_5;
					$array['follow_me_destinations'][$x]['follow_me_order'] = '5';
					$this->destination_order++;
					$x++;
				}
				if (is_array($array) && @sizeof($array) != 0) {
					//grant temporary permissions
						$p = new permissions;
						$p->add('follow_me_destination_add', 'temp');
					//execute insert
						$database = new database;
						$database->app_name = 'calls';
						$database->app_uuid = '19806921-e8ed-dcff-b325-dd3e5da4959d';
						$database->save($array);
						unset($array);
					//revoke temporary permissions
						$p->delete('follow_me_destination_add', 'temp');
				}
		}

		public function set() {

			//get the extension_uuid
				$parameters['follow_me_uuid'] = $this->follow_me_uuid;
				$sql = "select extension_uuid from v_extensions ";
				$sql .= "where follow_me_uuid = :follow_me_uuid ";
				$database = new database;
				$result = $database->select($sql, $parameters);
				$extension_uuid = $result[0]['extension_uuid'];

			//grant temporary permissions
				$p = new permissions;
				$p->add("follow_me_edit", 'temp');
				$p->add("extension_edit", 'temp');

			//add follow me to the array
				$array['follow_me'][0]["follow_me_uuid"] = $this->follow_me_uuid;
				$array['follow_me'][0]["domain_uuid"] = $this->domain_uuid;

			//add extensions to the array
				$array['extensions'][0]["extension_uuid"] = $extension_uuid;
				$array['extensions'][0]["dial_domain"] = $this->domain_name;
				$array['extensions'][0]["follow_me_destinations"] = '';
				$array['extensions'][0]["follow_me_enabled"] = $this->follow_me_enabled;

			//save the destination
				$database = new database;
				$database->app_name = 'follow_me';
				$database->app_uuid = '19806921-e8ed-dcff-b325-dd3e5da4959d';
				$database->save($array);

			//remove the temporary permission
				$p->delete("follow_me_edit", 'temp');
				$p->delete("extension_edit", 'temp');

		} //function


		/**
		 * declare private variables
		 */
		private $app_name;
		private $app_uuid;
		private $permission;
		private $list_page;
		private $table;
		private $uuid_prefix;
		private $toggle_field;
		private $toggle_values;

		/**
		 * toggle records
		 */
		public function toggle($records) {

			//assign private variables
				$this->app_name = 'calls';
				$this->app_uuid = '19806921-e8ed-dcff-b325-dd3e5da4959d';
				$this->permission = 'follow_me';
				$this->list_page = 'calls.php';
				$this->table = 'extensions';
				$this->uuid_prefix = 'extension_';
				$this->toggle_field = 'follow_me_enabled';
				$this->toggle_values = ['true','false'];

			if (permission_exists($this->permission)) {

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
								$sql = "select ".$this->uuid_prefix."uuid as uuid, extension, number_alias, ";
								$sql .= "call_timeout, do_not_disturb, ";
								$sql .= "forward_all_enabled, forward_all_destination, ";
								$sql .= "forward_busy_enabled, forward_busy_destination, ";
								$sql .= "forward_no_answer_enabled, forward_no_answer_destination, ";
								$sql .= $this->toggle_field." as toggle, follow_me_uuid ";
								$sql .= "from v_".$this->table." ";
								$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
								$sql .= "and ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
								$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
								$database = new database;
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

								//count destinations
									$destinations_exist = false;
									if (
										$extension['state']	== $this->toggle_values[1] //false becoming true
										&& is_uuid($extension['follow_me_uuid'])
										) {
										$sql .= "select count(*) from v_follow_me_destinations where follow_me_uuid = :follow_me_uuid";
										$parameters['follow_me_uuid'] = $extension['follow_me_uuid'];
										$database = new database;
										$num_rows = $database->select($sql, $parameters, 'column');
										$destinations_exist = $num_rows ? true : false;
										unset($sql, $parameters, $num_rows);
									}

								//determine new state
									$new_state = $extension['state'] == $this->toggle_values[1] && $destinations_exist ? $this->toggle_values[0] : $this->toggle_values[1];

								//toggle feature
									if ($new_state != $extension['state']) {
										$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $uuid;
										$array[$this->table][$x][$this->toggle_field] = $new_state;
										if (is_uuid($extension['follow_me_uuid'])) {
											$array['follow_me'][$x]['follow_me_uuid'] = $extension['follow_me_uuid'];
											$array['follow_me'][$x]['follow_me_enabled'] = $new_state;
										}
									}

								//disable other features
									if ($new_state == $this->toggle_values[0]) { //true
										$array[$this->table][$x]['forward_all_enabled'] = $this->toggle_values[1]; //false
										$array[$this->table][$x]['do_not_disturb'] = $this->toggle_values[1]; //false
									}

								//increment counter
									$x++;

							}

						//save the changes
							if (is_array($array) && @sizeof($array) != 0) {

								//grant temporary permissions
									$p = new permissions;
									$p->add('extension_edit', 'temp');
									$p->add('follow_me_edit', 'temp');

								//save the array
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->save($array);
									unset($array);

								//revoke temporary permissions
									$p->delete('extension_edit', 'temp');
									$p->delete('follow_me_edit', 'temp');

								//send feature event notify to the phone
									if ($_SESSION['device']['feature_sync']['boolean'] == "true") {
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
									if (is_readable($_SESSION['switch']['extensions']['dir'])) {
										require_once "app/extensions/resources/classes/extension.php";
										$ext = new extension;
										$ext->xml();
										unset($ext);
									}

								//clear the cache
									$cache = new cache;
									foreach ($extensions as $uuid => $extension) {
										$cache->delete("directory:".$extension['extension']."@".$_SESSION['domain_name']);
										if ($extension['number_alias'] != '') {
											$cache->delete("directory:".$extension['number_alias']."@".$_SESSION['domain_name']);
										}
									}

								//set message
									message::add($text['message-toggle']);

							}
							unset($records, $extensions, $extension);
					}

			}

		} //function

	} //class

?>
