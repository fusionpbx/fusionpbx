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
	Copyright (C) 2010 - 2016
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";

//define the dnd class
	class do_not_disturb {
		public $debug;
		public $domain_uuid;
		public $domain_name;
		public $extension_uuid;
		public $extension;
		public $enabled;

		//update the user_status
		public function user_status() {
			//update the status
				if ($this->enabled == "true") {
					//update the call center status
						$user_status = "Logged Out";
						$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
						if ($fp) {
							$switch_cmd .= "callcenter_config agent set status ".$_SESSION['username']."@".$this->domain_name." '".$user_status."'";
							$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
						}

					//update the database user_status
						$user_status = "Do Not Disturb";
						$sql  = "update v_users set ";
						$sql .= "user_status = :user_status ";
						$sql .= "where domain_uuid = :domain_uuid ";
						$sql .= "and username = :username ";
						$parameters['user_status'] = "Do Not Disturb";
						$parameters['domain_uuid'] = $this->domain_uuid;
						$parameters['username'] = $_SESSION['username'];
						$database = new database;
						$database->execute($sql);
				}
		}

		public function set() {
			//determine whether to update the dial string
				$sql = "select extension_uuid, extension, number_alias ";
				$sql .= "from v_extensions ";
				$sql .= "where domain_uuid = :domain_uuid ";
				if (is_uuid($this->extension_uuid)) {
					$sql .= "and extension_uuid = :extension_uuid ";
					$parameters['extension_uuid'] = $this->extension_uuid;
				}
				else {
					$sql .= "and extension = :extension ";
					$parameters['extension'] = $this->extension;
				}
				$parameters['domain_uuid'] = $this->domain_uuid;
				$database = new database;
				$row = $database->select($sql, $parameters, 'row');
				if (is_array($row) && @sizeof($row) != 0) {
					if (is_uuid($this->extension_uuid)) {
						$this->extension_uuid = $row["extension_uuid"];
					}
					if (strlen($this->extension) == 0) {
						if (strlen($row["number_alias"]) == 0) {
							$this->extension = $row["extension"];
						}
						else {
							$this->extension = $row["number_alias"];
						}
					}
				}
				unset($sql, $parameters, $row);

			//build extension update array
				$array['extensions'][0]['extension_uuid'] = $this->extension_uuid;
				$array['extensions'][0]['do_not_disturb'] = $this->enabled;

			//grant temporary permissions
				$p = new permissions;
				$p->add('extension_edit', 'temp');

			//execute update
				$database = new database;
				$database->app_name = 'calls';
				$database->app_uuid = '19806921-e8ed-dcff-b325-dd3e5da4959d';
				$database->save($array);
				unset($array);

			//revoke temporary permissions
				$p->delete('extension_edit', 'temp');

			//delete extension from the cache
				$cache = new cache;
				$cache->delete("directory:".$this->extension."@".$this->domain_name);
				if(strlen($this->number_alias) > 0){
					$cache->delete("directory:".$this->number_alias."@".$this->domain_name);
				}

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
				$this->permission = 'do_not_disturb';
				$this->list_page = 'calls.php';
				$this->table = 'extensions';
				$this->uuid_prefix = 'extension_';
				$this->toggle_field = 'do_not_disturb';
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

								//toggle feature
									$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $uuid;
									$array[$this->table][$x][$this->toggle_field] = $extension['state'] == $this->toggle_values[0] ? $this->toggle_values[1] : $this->toggle_values[0];

								//disable other features
									if ($array[$this->table][$x][$this->toggle_field] == $this->toggle_values[0]) { //true
										$array[$this->table][$x]['forward_all_enabled'] = $this->toggle_values[1]; //false
										$array[$this->table][$x]['follow_me_enabled'] = $this->toggle_values[1]; //false
										if (is_uuid($extension['follow_me_uuid'])) {
											$array['follow_me'][$x]['follow_me_uuid'] = $extension['follow_me_uuid'];
											$array['follow_me'][$x]['follow_me_enabled'] = $this->toggle_values[1]; //false
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
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->save($array);
									unset($array);

								//revoke temporary permissions
									$p->delete('extension_edit', 'temp');

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