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
 Portions created by the Initial Developer are Copyright (C) 2008 - 2019
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
*/

//define the call_flows class
if (!class_exists('call_flows')) {
	class call_flows {

		/**
		 * declare public variables
		 */
		public $toggle_field;

		/**
		 * declare private variables
		 */
		private $app_name;
		private $app_uuid;
		private $permission_prefix;
		private $list_page;
		private $table;
		private $uuid_prefix;
		private $toggle_values;

		/**
		 * called when the object is created
		 */
		public function __construct() {

			//assign private variables
				$this->app_name = 'call_flows';
				$this->app_uuid = 'b1b70f85-6b42-429b-8c5a-60c8b02b7d14';
				$this->permission_prefix = 'call_flow_';
				$this->list_page = 'call_flows.php';
				$this->table = 'call_flows';
				$this->uuid_prefix = 'call_flow_';
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

		/**
		 * delete records
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

				//delete multiple records
					if (is_array($records) && @sizeof($records) != 0) {

						//filter out unchecked call flows, build where clause for below
							foreach ($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}

						//get necessary call flow details
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->uuid_prefix."uuid as uuid, dialplan_uuid, call_flow_context from v_".$this->table." ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$sql .= "and ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
								$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										$call_flows[$row['uuid']]['dialplan_uuid'] = $row['dialplan_uuid'];
										$call_flow_contexts[] = $row['call_flow_context'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//build the delete array
							$x = 0;
							foreach ($call_flows as $call_flow_uuid => $call_flow) {
								$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $call_flow_uuid;
								$array[$this->table][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
								$array['dialplans'][$x]['dialplan_uuid'] = $call_flow['dialplan_uuid'];
								$array['dialplans'][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
								$array['dialplan_details'][$x]['dialplan_uuid'] = $call_flow['dialplan_uuid'];
								$array['dialplan_details'][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
								$x++;
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {

								//grant temporary permissions
									$p = new permissions;
									$p->add('dialplan_delete', 'temp');
									$p->add('dialplan_detail_delete', 'temp');

								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
									unset($array);

								//revoke temporary permissions
									$p->delete('dialplan_delete', 'temp');
									$p->delete('dialplan_detail_delete', 'temp');

								//apply settings reminder
									$_SESSION["reload_xml"] = true;

								//clear the cache
									if (is_array($call_flow_contexts) && @sizeof($call_flow_contexts) != 0) {
										$call_flow_contexts = array_unique($call_flow_contexts);
										$cache = new cache;
										foreach ($call_flow_contexts as $call_flow_context) {
											$cache->delete("dialplan:".$call_flow_context);
										}
									}

								//clear the destinations session array
									if (isset($_SESSION['destinations']['array'])) {
										unset($_SESSION['destinations']['array']);
									}

								//set message
									message::add($text['message-delete']);
							}
							unset($records);
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
								$sql = "select ".$this->uuid_prefix."uuid as uuid, ".$this->toggle_field." as toggle, ";
								$sql .= "dialplan_uuid, call_flow_feature_code, call_flow_context from v_".$this->table." ";
								$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
								$sql .= "and ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
								$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										$call_flows[$row['uuid']]['state'] = $row['toggle'];
										$call_flows[$row['uuid']]['dialplan_uuid'] = $row['dialplan_uuid'];
										$call_flows[$row['uuid']]['call_flow_feature_code'] = $row['call_flow_feature_code'];
										$call_flow_contexts[] = $row['call_flow_context'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//build update array
							$x = 0;
							foreach($call_flows as $uuid => $call_flow) {
								$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $uuid;
								$array[$this->table][$x][$this->toggle_field] = $call_flow['state'] == $this->toggle_values[0] ? $this->toggle_values[1] : $this->toggle_values[0];
								if ($this->toggle_field == 'call_flow_enabled') {
									$array['dialplans'][$x]['dialplan_uuid'] = $call_flow['dialplan_uuid'];
									$array['dialplans'][$x]['dialplan_enabled'] = $call_flow['state'] == $this->toggle_values[0] ? $this->toggle_values[1] : $this->toggle_values[0];
								}
								$x++;
							}

						//save the changes
							if (is_array($array) && @sizeof($array) != 0) {

								//grant temporary permissions
									$p = new permissions;
									$p->add('dialplan_edit', 'temp');

								//save the array
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->save($array);
									unset($array);

								//revoke temporary permissions
									$p->delete('dialplan_edit', 'temp');

								//apply settings reminder
									$_SESSION["reload_xml"] = true;

								//clear the cache
									if (is_array($call_flow_contexts) && @sizeof($call_flow_contexts) != 0) {
										$call_flow_contexts = array_unique($call_flow_contexts);
										$cache = new cache;
										foreach ($call_flow_contexts as $call_flow_context) {
											$cache->delete("dialplan:".$call_flow_context);
										}
									}

								//clear the destinations session array
									if (isset($_SESSION['destinations']['array'])) {
										unset($_SESSION['destinations']['array']);
									}

								//set message
									message::add($text['message-toggle']);
							}
							unset($records);

						//toggle the presence
							if ($this->toggle_field != 'call_flow_enabled') {
								foreach($call_flows as $uuid => $row) {
									//prepare the event
									$cmd = "sendevent PRESENCE_IN\n";
									$cmd .= "proto: flow\n";
									$cmd .= "login: ".$row['call_flow_feature_code']."@".$_SESSION['domain_name']."\n";
									$cmd .= "from: ".$row['call_flow_feature_code']."@".$_SESSION['domain_name']."\n";
									$cmd .= "status: Active (1 waiting)\n";
									$cmd .= "rpid: unknown\n";
									$cmd .= "event_type: presence\n";
									$cmd .= "alt_event_type: dialog\n";
									$cmd .= "event_count: 1\n";
									$cmd .= "unique-id: ".uuid()."\n";
									$cmd .= "Presence-Call-Direction: outbound\n";
									if ($call_flow['state'] == 'true') {
										$cmd .= "answer-state: confirmed\n";
									}
									else {
										$cmd .= "answer-state: terminated\n";
									}

									//send the event
									$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
									$switch_result = event_socket_request($fp, $cmd);
								}
							}
							unset($call_flows);

					}

			}
		}

		/**
		 * copy records
		 */
		public function copy($records) {
			if (permission_exists($this->permission_prefix.'add')) {

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

				//copy the checked records
					if (is_array($records) && @sizeof($records) != 0) {

						//get checked records
							foreach($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}

						//create insert array from existing data
							if (is_array($uuids) && @sizeof($uuids) != 0) {

								//primary table
									$sql = "select * from v_".$this->table." ";
									$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
									$sql .= "and ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
									$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
									$database = new database;
									$rows = $database->select($sql, $parameters, 'all');
									if (is_array($rows) && @sizeof($rows) != 0) {
										foreach ($rows as $x => $row) {
											$new_call_flow_uuid = uuid();
											$new_dialplan_uuid = uuid();

											//copy data
												$array[$this->table][$x] = $row;

											//overwrite
												$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $new_call_flow_uuid;
												$array[$this->table][$x]['dialplan_uuid'] = $new_dialplan_uuid;
												$array[$this->table][$x]['call_flow_description'] = trim($row['call_flow_description'].' ('.$text['label-copy'].')');

											//call flow dialplan record
												$sql_2 = "select * from v_dialplans where dialplan_uuid = :dialplan_uuid";
												$parameters_2['dialplan_uuid'] = $row['dialplan_uuid'];
												$database = new database;
												$dialplan = $database->select($sql_2, $parameters_2, 'row');
												if (is_array($dialplan) && @sizeof($dialplan) != 0) {

													//copy data
														$array['dialplans'][$x] = $dialplan;

													//overwrite
														$array['dialplans'][$x]['dialplan_uuid'] = $new_dialplan_uuid;
														$dialplan_xml = $dialplan['dialplan_xml'];
														$dialplan_xml = str_replace($row['call_flow_uuid'], $new_call_flow_uuid, $dialplan_xml); //replace source call_flow_uuid with new
														$dialplan_xml = str_replace($dialplan['dialplan_uuid'], $new_dialplan_uuid, $dialplan_xml); //replace source dialplan_uuid with new
														$array['dialplans'][$x]['dialplan_xml'] = $dialplan_xml;
														$array['dialplans'][$x]['dialplan_description'] = trim($dialplan['dialplan_description'].' ('.$text['label-copy'].')');

												}
												unset($sql_2, $parameters_2, $dialplan);

											//create call flow context array
												$call_flow_contexts = $row['call_flow_context'];
										}
									}
									unset($sql, $parameters, $rows, $row);
							}

						//save the changes and set the message
							if (is_array($array) && @sizeof($array) != 0) {

								//grant temporary permissions
									$p = new permissions;
									$p->add('dialplan_add', 'temp');

								//save the array
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->save($array);
									unset($array);

								//revoke temporary permissions
									$p->delete('dialplan_add', 'temp');

								//apply settings reminder
									$_SESSION["reload_xml"] = true;

								//clear the cache
									if (is_array($call_flow_contexts) && @sizeof($call_flow_contexts) != 0) {
										$call_flow_contexts = array_unique($call_flow_contexts);
										$cache = new cache;
										foreach ($call_flow_contexts as $call_flow_context) {
											$cache->delete("dialplan:".$call_flow_context);
										}
									}

								//set message
									message::add($text['message-copy']);

							}
							unset($records);
					}

			}
		} //method

	} //class
}

?>
