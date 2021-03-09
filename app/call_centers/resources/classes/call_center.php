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
	Copyright (C) 2015 - 2016
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

/**
 * cache class provides an abstracted cache
 *
 * @method string dialplan - builds the dialplan for call center
 */
//define the call center class
	if (!class_exists('call_center')) {
		class call_center {
			/**
			 * define the variables
			 */
			public $domain_uuid;
			public $call_center_queue_uuid;
			public $dialplan_uuid;
			public $queue_name;
			public $queue_description;
			public $destination_number;
			public $queue_cc_exit_keys;

			/**
			* declare private variables
			*/
			private $app_name;
			private $app_uuid;
			private $permission_prefix;
			private $list_page;
			private $table;
			private $uuid_prefix;

			/**
			 * Called when the object is created
			 */
			public function __construct() {
				//assign private variables
					$this->app_name = 'call_center';
					$this->app_uuid = '95788e50-9500-079e-2807-fd530b0ea370';
			}

			/**
			 * Called when there are no references to a particular object
			 * unset the variables used in the class
			 */
			public function __destruct() {
				foreach ($this as $key => $value) {
					unset($this->$key);
				}
			}

			/**
			 * Add a dialplan for call center
			 * @var string $domain_uuid		the multi-tenant id
			 * @var string $value	string to be cached
			 */
			public function dialplan() {

				//delete previous dialplan
					if (is_uuid($this->dialplan_uuid)) {
						//build delete array
							$array['dialplans'][0]['dialplan_uuid'] = $this->dialplan_uuid;
							$array['dialplans'][0]['domain_uuid'] = $this->domain_uuid;
							$array['dialplan_details'][0]['dialplan_uuid'] = $this->dialplan_uuid;
							$array['dialplan_details'][0]['domain_uuid'] = $this->domain_uuid;

						//grant temporary permissions
							$p = new permissions;
							$p->add('dialplan_delete', 'temp');
							$p->add('dialplan_detail_delete', 'temp');

						//execute delete
							$database = new database;
							$database->app_name = 'call_centers';
							$database->app_uuid = '95788e50-9500-079e-2807-fd530b0ea370';
							$database->delete($array);
							unset($array);

						//revoke temporary permissions
							$p->delete('dialplan_delete', 'temp');
							$p->delete('dialplan_detail_delete', 'temp');
					}

				//build the dialplan array
					$dialplan["app_uuid"] = "95788e50-9500-079e-2807-fd530b0ea370";
					$dialplan["domain_uuid"] = $this->domain_uuid;
					$dialplan["dialplan_name"] = ($this->queue_name != '') ? $this->queue_name : format_phone($this->destination_number);
					$dialplan["dialplan_number"] = $this->destination_number;
					$dialplan["dialplan_context"] = $_SESSION['domain_name'];
					$dialplan["dialplan_continue"] = "false";
					$dialplan["dialplan_order"] = "210";
					$dialplan["dialplan_enabled"] = "true";
					$dialplan["dialplan_description"] = $this->queue_description;
					$dialplan_detail_order = 10;

				//add the public condition
					$y = 1;
					$dialplan["dialplan_details"][$y]["domain_uuid"] = $this->domain_uuid;
					$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "condition";
					$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "\${caller_id_name}";
					$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "^([^#]+#)(.*)$";
					$dialplan["dialplan_details"][$y]["dialplan_detail_break"] = "never";
					$dialplan["dialplan_details"][$y]["dialplan_detail_group"] = "1";
					$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $y * 10;
					$y++;
					$dialplan["dialplan_details"][$y]["domain_uuid"] = $this->domain_uuid;
					$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
					$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
					$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "caller_id_name=$2";
					$dialplan["dialplan_details"][$y]["dialplan_detail_group"] = "1";
					$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $y * 10;
					$y++;
					$dialplan["dialplan_details"][$y]["domain_uuid"] = $this->domain_uuid;
					$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "condition";
					$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "destination_number";
					$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "^".$this->destination_number."\$";
					$dialplan["dialplan_details"][$y]["dialplan_detail_break"] = "";
					$dialplan["dialplan_details"][$y]["dialplan_detail_group"] = "2";
					$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $y * 10;
					$y++;
					$dialplan["dialplan_details"][$y]["domain_uuid"] = $this->domain_uuid;
					$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
					$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "answer";
					$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "";
					$dialplan["dialplan_details"][$y]["dialplan_detail_group"] = "2";
					$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $y * 10;
					$y++;
					$dialplan["dialplan_details"][$y]["domain_uuid"] = $this->domain_uuid;
					$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
					$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
					$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "hangup_after_bridge=true";
					$dialplan["dialplan_details"][$y]["dialplan_detail_group"] = "2";
					$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $y * 10;
					$y++;

					if (strlen($this->queue_cid_prefix) > 0) {
						$dialplan["dialplan_details"][$y]["domain_uuid"] = $this->domain_uuid;
						$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
						$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
						$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "effective_caller_id_name=".$this->queue_cid_prefix."#\${caller_id_name}";
						$dialplan["dialplan_details"][$y]["dialplan_detail_group"] = "2";
						$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $y * 10;
						$y++;
					}

					if (strlen($this->queue_greeting) > 0) {
						$dialplan["dialplan_details"][$y]["domain_uuid"] = $this->domain_uuid;
						$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
						$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "sleep";
						$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "1000";
						$dialplan["dialplan_details"][$y]["dialplan_detail_group"] = "2";
						$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $y * 10;
						$y++;
					}

					if (strlen($this->queue_greeting) > 0) {
						$dialplan["dialplan_details"][$y]["domain_uuid"] = $this->domain_uuid;
						$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
						$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "playback";
						$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "".$this->queue_greeting;
						$dialplan["dialplan_details"][$y]["dialplan_detail_group"] = "2";
						$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $y * 10;
						$y++;
					}

					if (strlen($this->queue_cc_exit_keys) > 0) {
						$dialplan["dialplan_details"][$y]["domain_uuid"] = $this->domain_uuid;
						$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
						$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
						$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "cc_exit_keys=".$this->queue_cc_exit_keys;
						$dialplan["dialplan_details"][$y]["dialplan_detail_group"] = "2";
						$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $y * 10;
						$y++;
					}

					$dialplan["dialplan_details"][$y]["domain_uuid"] = $this->domain_uuid;
					$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
					$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "callcenter";
					$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = $this->queue_name.'@'.$_SESSION["domain_name"];
					$dialplan["dialplan_details"][$y]["dialplan_detail_group"] = "2";
					$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $y * 10;
					$y++;

					if (strlen($this->queue_timeout_action) > 0) {
						$action_array = explode(":",$this->queue_timeout_action);
						$dialplan["dialplan_details"][$y]["domain_uuid"] = $this->domain_uuid;
						$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
						$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = $action_array[0];
						$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = substr($this->queue_timeout_action, strlen($action_array[0])+1, strlen($this->queue_timeout_action));
						$dialplan["dialplan_details"][$y]["dialplan_detail_group"] = "2";
						$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $y * 10;
						$y++;
					}

					$dialplan["dialplan_details"][$y]["domain_uuid"] = $this->domain_uuid;
					$dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
					$dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "hangup";
					$dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "";
					$dialplan["dialplan_details"][$y]["dialplan_detail_group"] = "2";
					$dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $y * 10;

				//prepare the array
					$array["dialplans"][0] = $dialplan;

				//add temporary permissions
					$p = new permissions;
					$p->add("dialplan_add", 'temp');
					$p->add("dialplan_detail_add", 'temp');
					$p->add("dialplan_edit", 'temp');
					$p->add("dialplan_detail_edit", 'temp');

				//save the dialplan
					$database = new database;
					$database->app_name = 'call_centers';
					$database->app_uuid = '95788e50-9500-079e-2807-fd530b0ea370';
					$database->save($array);
					$dialplan_response = $database->message;
					$this->dialplan_uuid = $dialplan_response['uuid'];
					unset($array);

				//remove temporary permissions
					$p->delete("dialplan_add", 'temp');
					$p->delete("dialplan_detail_add", 'temp');
					$p->delete("dialplan_edit", 'temp');
					$p->delete("dialplan_detail_edit", 'temp');

				//build call center queue update array
					$array['call_center_queues'][0]['call_center_queue_uuid'] = $this->call_center_queue_uuid;
					$array['call_center_queues'][0]['dialplan_uuid'] = $this->dialplan_uuid;

				//grant temporary permissions
					$p = new permissions;
					$p->add('call_center_queue_edit', 'temp');

				//execute update
					$database = new database;
					$database->app_name = 'call_centers';
					$database->app_uuid = '95788e50-9500-079e-2807-fd530b0ea370';
					$database->save($array);
					unset($array);

				//revoke temporary permissions
					$p->delete('call_center_queue_edit', 'temp');

				//clear the cache
					$cache = new cache;
					$cache->delete("dialplan:".$_SESSION['domain_name']);

				//return the dialplan_uuid
					return $dialplan_response;

			}

			/**
			* delete records
			*/
			public function delete_queues($records) {

				//assign private variables
					$this->permission_prefix = 'call_center_queue_';
					$this->list_page = 'call_center_queues.php';
					$this->table = 'call_center_queues';
					$this->uuid_prefix = 'call_center_queue_';

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

							//filter out unchecked, build where clause for below
								foreach ($records as $x => $record) {
									if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
										$uuids[] = "'".$record['uuid']."'";
									}
								}

							//get necessary details
								if (is_array($uuids) && @sizeof($uuids) != 0) {
									$sql = "select ".$this->uuid_prefix."uuid as uuid, dialplan_uuid, queue_name from v_".$this->table." ";
									$sql .= "where domain_uuid = :domain_uuid ";
									$sql .= "and ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
									$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
									$database = new database;
									$rows = $database->select($sql, $parameters, 'all');
									if (is_array($rows) && @sizeof($rows) != 0) {
										foreach ($rows as $row) {
											$call_center_queues[$row['uuid']]['dialplan_uuid'] = $row['dialplan_uuid'];
											$call_center_queues[$row['uuid']]['queue_name'] = $row['queue_name'];
										}
									}
									unset($sql, $parameters, $rows, $row);
								}

							//build the delete array
								$x = 0;
								foreach ($call_center_queues as $call_center_queue_uuid => $call_center_queue) {
									$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $call_center_queue_uuid;
									$array[$this->table][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
									$array['dialplans'][$x]['dialplan_uuid'] = $call_center_queue['dialplan_uuid'];
									$array['dialplans'][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
									$array['dialplan_details'][$x]['dialplan_uuid'] = $call_center_queue['dialplan_uuid'];
									$array['dialplan_details'][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
									$array['call_center_tiers'][$x][$this->uuid_prefix.'uuid'] = $call_center_queue_uuid;
									$array['call_center_tiers'][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
									$x++;
									$array['call_center_tiers'][$x]['queue_name'] = $call_center_queue['queue_name']."@".$_SESSION['domain_name'];
									$array['call_center_tiers'][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
									$x++;
								}

							//delete the checked rows
								if (is_array($array) && @sizeof($array) != 0) {

									//setup the event socket connection
										$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);

									//delete the queue in the switch
										if ($fp) {
											foreach ($uuids as $uuid) {
												$cmd = "api callcenter_config queue unload ".$uuid;
												$response = event_socket_request($fp, $cmd);
											}
										}

									//grant temporary permissions
										$p = new permissions;
										$p->add('call_center_tier_delete', 'temp');
										$p->add('dialplan_delete', 'temp');
										$p->add('dialplan_detail_delete', 'temp');

									//execute delete
										$database = new database;
										$database->app_name = $this->app_name;
										$database->app_uuid = $this->app_uuid;
										$database->delete($array);
										unset($array);

									//revoke temporary permissions
										$p->delete('call_center_tier_delete', 'temp');
										$p->delete('dialplan_delete', 'temp');
										$p->delete('dialplan_detail_delete', 'temp');

									//clear the cache
										$cache = new cache;
										$cache->delete("dialplan:".$_SESSION["domain_name"]);
										remove_config_from_cache('configuration:callcenter.conf');

									//clear the destinations session array
										if (isset($_SESSION['destinations']['array'])) {
											unset($_SESSION['destinations']['array']);
										}

									//synchronize configuration
										save_call_center_xml();

									//apply settings reminder
										$_SESSION["reload_xml"] = true;

									//set message
										message::add($text['message-delete']);
								}
								unset($records);
						}
				}
			}

			public function delete_agents($records) {

				//assign private variables
					$this->permission_prefix = 'call_center_agent_';
					$this->list_page = 'call_center_agents.php';
					$this->table = 'call_center_agents';
					$this->uuid_prefix = 'call_center_agent_';

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

							//filter out unchecked
								foreach ($records as $x => $record) {
									if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
										$uuids[] = $record['uuid'];
									}
								}

							//build the delete array
								if (is_array($uuids) && @sizeof($uuids) != 0) {
									foreach ($uuids as $x => $uuid) {
										$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $uuid;
										$array[$this->table][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
										$array['call_center_tiers'][$x]['call_center_agent_uuid'] = $uuid;
										$array['call_center_tiers'][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
									}
								}

							//delete the checked rows
								if (is_array($array) && @sizeof($array) != 0) {

									//setup the event socket connection
										$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);

									//delete the agent in the switch
										if ($fp) {
											foreach ($uuids as $uuid) {
												$cmd = "api callcenter_config agent del ".$uuid;
												$response = event_socket_request($fp, $cmd);
											}
										}

									//grant temporary permissions
										$p = new permissions;
										$p->add('call_center_tier_delete', 'temp');

									//execute delete
										$database = new database;
										$database->app_name = $this->app_name;
										$database->app_uuid = $this->app_uuid;
										$database->delete($array);
										unset($array);

									//revoke temporary permissions
										$p->delete('call_center_tier_delete', 'temp');

									//synchronize configuration
										save_call_center_xml();
										remove_config_from_cache('configuration:callcenter.conf');

									//set message
										message::add($text['message-delete']);
								}
								unset($records);
						}
				}
			}


			/**
			* copy records
			*/
			public function copy_queues($records) {

				//assign private variables
					$this->permission_prefix = 'call_center_queue_';
					$this->list_page = 'call_center_queues.php';
					$this->table = 'call_center_queues';
					$this->uuid_prefix = 'call_center_queue_';

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
								foreach ($records as $x => $record) {
									if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
										$uuids[] = "'".$record['uuid']."'";
									}
								}

							//create insert array from existing data
								if (is_array($uuids) && @sizeof($uuids) != 0) {

									//primary table
										$sql = "select * from v_".$this->table." ";
										$sql .= "where ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
										$database = new database;
										$rows = $database->select($sql, $parameters, 'all');
										if (is_array($rows) && @sizeof($rows) != 0) {
											$y = 0;
											foreach ($rows as $x => $row) {
												$new_call_center_queue_uuid = uuid();
												$new_dialplan_uuid = uuid();

												//copy data
													$array[$this->table][$x] = $row;

												//overwrite
													$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $new_call_center_queue_uuid;
													$array[$this->table][$x]['dialplan_uuid'] = $new_dialplan_uuid;
													$array[$this->table][$x]['queue_description'] = trim($row['queue_description'].' ('.$text['label-copy'].')');

												//call center tiers sub table
													$sql_2 = "select * from v_call_center_tiers where call_center_queue_uuid = :call_center_queue_uuid";
													$parameters_2['call_center_queue_uuid'] = $row['call_center_queue_uuid'];
													$database = new database;
													$rows_2 = $database->select($sql_2, $parameters_2, 'all');
													if (is_array($rows_2) && @sizeof($rows_2) != 0) {
														foreach ($rows_2 as $row_2) {

															//copy data
																$array['call_center_tiers'][$y] = $row_2;

															//overwrite
																$array['call_center_tiers'][$y]['call_center_tier_uuid'] = uuid();
																$array['call_center_tiers'][$y]['call_center_queue_uuid'] = $new_call_center_queue_uuid;

															$y++;
														}
													}
													unset($sql_2, $parameters_2, $rows_2, $row_2);

												//call center queue dialplan record
													$sql_3 = "select * from v_dialplans where dialplan_uuid = :dialplan_uuid";
													$parameters_3['dialplan_uuid'] = $row['dialplan_uuid'];
													$database = new database;
													$dialplan = $database->select($sql_3, $parameters_3, 'row');
													if (is_array($dialplan) && @sizeof($dialplan) != 0) {

														//copy data
															$array['dialplans'][$x] = $dialplan;

														//overwrite
															$array['dialplans'][$x]['dialplan_uuid'] = $new_dialplan_uuid;
															$dialplan_xml = $dialplan['dialplan_xml'];
															$dialplan_xml = str_replace($row['call_center_queue_uuid'], $new_call_center_queue_uuid, $dialplan_xml); //replace source call_center_queue_uuid with new
															$dialplan_xml = str_replace($dialplan['dialplan_uuid'], $new_dialplan_uuid, $dialplan_xml); //replace source dialplan_uuid with new
															$array['dialplans'][$x]['dialplan_xml'] = $dialplan_xml;
															$array['dialplans'][$x]['dialplan_description'] = trim($dialplan['dialplan_description'].' ('.$text['label-copy'].')');

													}
													unset($sql_3, $parameters_3, $dialplan);
											}
										}
										unset($sql, $parameters, $rows, $row);
								}

							//save the changes and set the message
								if (is_array($array) && @sizeof($array) != 0) {

									//grant temporary permissions
										$p = new permissions;
										$p->add('call_center_tier_add', 'temp');
										$p->add('dialplan_add', 'temp');

									//save the array
										$database = new database;
										$database->app_name = $this->app_name;
										$database->app_uuid = $this->app_uuid;
										$database->save($array);
										unset($array);

									//revoke temporary permissions
										$p->delete('call_center_tier_add', 'temp');
										$p->delete('dialplan_add', 'temp');

									//apply settings reminder
										$_SESSION["reload_xml"] = true;

									//clear the cache
										$cache = new cache;
										$cache->delete("dialplan:".$_SESSION["domain_name"]);

									//set message
										message::add($text['message-copy']);

								}
								unset($records);
						}

				}
			} //method

		} //class
	}

/*
$o = new call_center;
$c->domain_uuid = "";
$c->dialplan_uuid = "";
$c->queue_name = "";
$c->queue_cid_prefix = "";
$c->queue_timeout_action = "";
$c->queue_description = "";
$c->destination_number = "";
$c->queue_cc_exit_keys = "";
$c->dialplan();
*/

?>
