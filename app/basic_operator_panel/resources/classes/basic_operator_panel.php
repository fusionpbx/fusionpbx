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
	Portions created by the Initial Developer are Copyright (C) 2008-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

/**
 * Define the operator_panel class
 */
if (!class_exists('basic_operator_panel')) {
	class basic_operator_panel {

		/**
		 * Define the variables
		 */
		public $domain_uuid;

		/**
		 * Called when the object is created
		 */
		public function __construct() {
			if (!isset($this->domain_uuid)) {
				$this->domain_uuid = $_SESSION['domain_uuid'];
			}
		}

		/**
		 * Get the call activity
		 */
		public function call_activity() {

			//define the global variable
				global $ext_user_status;

			//get the extensions and their user status
				$sql = "select ";
				$sql .= "e.extension, ";
				$sql .= "e.number_alias, ";
				$sql .= "e.effective_caller_id_name, ";
				$sql .= "e.effective_caller_id_number, ";
				$sql .= "e.call_group, ";
				$sql .= "e.description, ";
				$sql .= "u.user_uuid, ";
				$sql .= "u.user_status ";
				$sql .= "from ";
				$sql .= "v_extensions as e ";
				$sql .= "left outer join v_extension_users as eu on ( eu.extension_uuid = e.extension_uuid and eu.domain_uuid = :domain_uuid ) ";
				$sql .= "left outer join v_users as u on ( u.user_uuid = eu.user_uuid and u.domain_uuid = :domain_uuid ) ";
				$sql .= "where ";
				$sql .= "e.enabled = 'true' and ";
				$sql .= "e.domain_uuid = :domain_uuid ";
				$sql .= "order by ";
				$sql .= "e.extension asc ";
				$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
				$database = new database;
				$extensions = $database->select($sql, $parameters);

			//store extension status by user uuid
				if (isset($extensions)) {
					foreach ($extensions as $row) {
						if ($row['user_uuid'] != '') {
							$ext_user_status[$row['user_uuid']] = $row['user_status'];
							unset($row['user_status']);
						}
					}
				}

			//send the command
				$switch_result = event_socket::api('show channels as json');
				if ($switch_result !== false) {
					$fp = true;
					$json_array = json_decode($switch_result, true);
				} else {
					$fp = false;
				}

			//build the response
				$x = 0;
				if (isset($extensions)) {
					foreach ($extensions as $row) {
						$user = $row['extension'];
						if (!empty($row['number_alias'])) {
							$user = $row['number_alias'];
						}

						//add the extension details
							$array[$x] = $row;

						//set the call detail defaults
							$array[$x]["uuid"] = null;
							$array[$x]["direction"] = null;
							$array[$x]["created"] = null;
							$array[$x]["created_epoch"] = null;
							$array[$x]["name"] = null;
							$array[$x]["state"] = null;
							$array[$x]["cid_name"] = null;
							$array[$x]["cid_num"] = null;
							$array[$x]["ip_addr"] = null;
							$array[$x]["dest"] = null;
							$array[$x]["application"] = null;
							$array[$x]["application_data"] = null;
							$array[$x]["dialplan"] = null;
							$array[$x]["context"] = null;
							$array[$x]["read_codec"] = null;
							$array[$x]["read_rate"] = null;
							$array[$x]["read_bit_rate"] = null;
							$array[$x]["write_codec"] = null;
							$array[$x]["write_rate"] = null;
							$array[$x]["write_bit_rate"] = null;
							$array[$x]["secure"] = null;
							$array[$x]["hostname"] = null;
							$array[$x]["presence_id"] = null;
							$array[$x]["presence_data"] = null;
							$array[$x]["callstate"] = null;
							$array[$x]["callee_name"] = null;
							$array[$x]["callee_num"] = null;
							$array[$x]["callee_direction"] = null;
							$array[$x]["call_uuid"] = null;
							$array[$x]["sent_callee_name"] = null;
							$array[$x]["sent_callee_num"] = null;
							$array[$x]["destination"] = null;

						//add the active call details
							$found = false;
							if (isset($json_array['rows'])) {
								foreach ($json_array['rows'] as $field) {
									$presence_id = $field['presence_id'];
									$presence = explode("@", $presence_id);
									$presence_id = $presence[0];
									$presence_domain = $presence[1] ?? '';
									if ($user == $presence_id) {
										if ($presence_domain == $_SESSION['domain_name']) {
											$found = true;
											break;
										}
									}
								}
							}

						//normalize the array
							if ($found) {
								$array[$x]["uuid"] =  $field['uuid'];
								$array[$x]["direction"] = $field['direction'];
								$array[$x]["created"] = $field['created'];
								$array[$x]["created_epoch"] = $field['created_epoch'];
								$array[$x]["name"] = $field['name'];
								$array[$x]["state"] = $field['state'];
								$array[$x]["cid_name"] = $field['cid_name'];
								$array[$x]["cid_num"] = $field['cid_num'];
								$array[$x]["ip_addr"] = $field['ip_addr'];
								$array[$x]["dest"] = $field['dest'];
								$array[$x]["application"] = $field['application'];
								$array[$x]["application_data"] = $field['application_data'];
								$array[$x]["dialplan"] = $field['dialplan'];
								$array[$x]["context"] = $field['context'];
								$array[$x]["read_codec"] = $field['read_codec'];
								$array[$x]["read_rate"] = $field['read_rate'];
								$array[$x]["read_bit_rate"] = $field['read_bit_rate'];
								$array[$x]["write_codec"] = $field['write_codec'];
								$array[$x]["write_rate"] = $field['write_rate'];
								$array[$x]["write_bit_rate"] = $field['write_bit_rate'];
								$array[$x]["secure"] = $field['secure'];
								$array[$x]["hostname"] = $field['hostname'];
								$array[$x]["presence_id"] = $field['presence_id'];
								$array[$x]["presence_data"] = $field['presence_data'];
								$array[$x]["callstate"] = $field['callstate'];
								$array[$x]["callee_name"] = $field['callee_name'];
								$array[$x]["callee_num"] = $field['callee_num'];
								$array[$x]["callee_direction"] = $field['callee_direction'];
								$array[$x]["call_uuid"] = $field['call_uuid'];
								$array[$x]["sent_callee_name"] = $field['sent_callee_name'];
								$array[$x]["sent_callee_num"] = $field['sent_callee_num'];
								$array[$x]["destination"] = $user;

								//calculate and set the call length
								$call_length_seconds = time() - $array[$x]["created_epoch"];
								$call_length_hour = floor($call_length_seconds/3600);
								$call_length_min = floor($call_length_seconds/60 - ($call_length_hour * 60));
								$call_length_sec = $call_length_seconds - (($call_length_hour * 3600) + ($call_length_min * 60));
								$call_length_min = sprintf("%02d", $call_length_min);
								$call_length_sec = sprintf("%02d", $call_length_sec);
								$call_length = $call_length_hour.':'.$call_length_min.':'.$call_length_sec;
								$array[$x]['call_length'] = $call_length;

								//send the command
								if ($field['state'] != '') {
									if ($fp) {
										if (is_uuid($field['uuid'])) {
											$switch_cmd = 'uuid_dump '.$field['uuid'].' json';
											$dump_result = event_socket::api($switch_cmd);
											if ($dump_result !== false) {
												$dump_array = json_decode($dump_result, true);
											}
											if (is_array($dump_array)) {
												foreach ($dump_array as $dump_var_name => $dump_var_value) {
													$array[$x][$dump_var_name] = $dump_var_value;
												}
											}
										}
									}
								}

							}

						//increment the row
							$x++;
					}
				}

				//reindex array using extension instead of auto-incremented value
				$result = array();
				if (is_array($array)) {
					foreach ($array as $index => $subarray) {
						$extension = $subarray['extension'];
						if (is_array($subarray)) foreach ($subarray as $field => $value) {
							$result[$extension][$field] = $array[$index][$field];
							unset($array[$index][$field]);
						}
						unset($array[$subarray['extension']]['extension']);
						unset($array[$index]);
					}
				}

			//return array
				return $result;
		}
	}
}

?>
