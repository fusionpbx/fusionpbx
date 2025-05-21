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
 Portions created by the Initial Developer are Copyright (C) 2008-2024
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
*/

//define the voicemail class
	class voicemail {

		/**
		 * declare public variables
		 */
		public $domain_uuid;
		public $domain_name;
		public $voicemail_uuid;
		public $voicemail_id;
		public $voicemail_message_uuid;
		public $user_uuid;
		public $order_by;
		public $order;
		public $offset;
		public $type;

		/**
		 * declare private variables
		 */
		private $app_name;
		private $app_uuid;
		private $permission_prefix;
		private $list_page;
		private $table;
		private $uuid_prefix;
		private $toggle_field;
		private $toggle_values;

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

		public function __construct(array $params = []) {

			//set the domain_uuid if not provided
				if (!empty($params['domain_uuid']) && is_uuid($params['domain_uuid'])) {
					$this->domain_uuid = $params['domain_uuid'];
				} else {
					$this->domain_uuid = $_SESSION['domain_uuid'] ?? '';
				}

			//set the user_uuid if not provided
				if (!empty($params['user_uuid']) && is_uuid($params['user_uuid'])) {
					$this->user_uuid = $params['user_uuid'];
				} else {
					$this->user_uuid = $_SESSION['user_uuid'] ?? '';
				}

			//database connection
				if (empty($params['database'])) {
					$this->database = database::new();
				} else {
					$this->database = $params['database'];
				}

			//assign the settings object
				if (empty($params['settings'])) {
					$this->settings = new settings(['database' => $this->database, 'domain_uuid' => $this->domain_uuid, 'user_uuid' => $this->user_uuid]);
				}
				else {
					$this->settings = $params['settings'];
				}

			//assign private variables
				$this->app_name = 'voicemail';
				$this->app_uuid = 'b523c2d2-64cd-46f1-9520-ca4b4098e044';
				$this->permission_prefix = 'voicemail_';
				$this->list_page = 'voicemails.php';
				$this->table = 'voicemails';
				$this->uuid_prefix = 'voicemail_';
				$this->toggle_field = 'voicemail_enabled';
				$this->toggle_values = ['true','false'];

		}

		public function get_voicemail_id() {

			//check if for valid input
				if (!is_uuid($this->voicemail_uuid) || !is_uuid($this->domain_uuid)) {
					return false;
				}

			//get the voicemail id if it isn't set already
				if (!isset($this->voicemail_id)) {
					$sql = "select voicemail_id from v_voicemails ";
					$sql .= "where domain_uuid = :domain_uuid ";
					$sql .= "and voicemail_uuid = :voicemail_uuid ";
					$parameters['domain_uuid'] = $this->domain_uuid;
					$parameters['voicemail_uuid'] = $this->voicemail_uuid;
					$voicemail_id = $this->database->select($sql, $parameters, 'column');
					if (is_numeric($voicemail_id)) {
						$this->voicemail_id = $voicemail_id;
					}
					unset($sql, $parameters, $voicemail_id);
				}
		}

		public function voicemails() {

			//check if for valid input
				if (!is_uuid($this->domain_uuid)) {
					return false;
				}

			//get the assigned extensions
				$sql = "select e.extension_uuid, e.extension, e.number_alias, e.enabled, e.description ";
				$sql .= "from v_extensions e, v_extension_users eu ";
				$sql .= "where e.extension_uuid = eu.extension_uuid ";
				$sql .= "and eu.user_uuid = :user_uuid ";
				$sql .= "and e.domain_uuid = :domain_uuid ";
				$sql .= "order by e.extension asc ";
				$parameters['domain_uuid'] = $this->domain_uuid;
				$parameters['user_uuid'] = $this->user_uuid;
				$assigned_extensions = $this->database->select($sql, $parameters, 'all');
				unset($sql, $parameters);

			//set the voicemail id arrays
				$voicemail_ids = [];
				if (isset($assigned_extensions)) {
					foreach ($assigned_extensions as $index => $row) {
						$voicemail_ids[] = (is_numeric($row['number_alias'])) ? $row['number_alias'] : $row['extension'];
					}
				}

			//get the assigned voicemails
				$assigned_voicemails = [];
				if (!empty($voicemail_ids) && @sizeof($voicemail_ids) != 0) {
					$sql = "select * from v_voicemails ";
					$sql .= "where voicemail_id  in (";
					foreach($voicemail_ids as $i => $voicemail_id) {
						if ($i > 0) { $sql .= ","; }
						$sql .= ":voicemail_id_".$i;
						$parameters['voicemail_id_'.$i] = $voicemail_id;
					}
					$sql .= ") ";
					$sql .= "and domain_uuid = :domain_uuid ";
					$parameters['domain_uuid'] = $this->domain_uuid;
					$assigned_voicemails = $this->database->select($sql, $parameters, 'all');
					unset($sql, $parameters);
				}

			//set the voicemail uuid arrays
				$voicemail_uuids = [];
				if (isset($assigned_voicemails)) {
					foreach ($assigned_voicemails as $row) {
						if (!empty($row['voicemail_uuid'])) {
							$voicemail_uuids[]['voicemail_uuid'] = $row['voicemail_uuid'];
						}
					}
				}

			//get the uuid and voicemail_id
				$sql = "select * from v_voicemails ";
				$sql .= "where domain_uuid = :domain_uuid ";
				if (is_uuid($this->voicemail_uuid)) {
					if (permission_exists('voicemail_delete')) {
						//view specific voicemail box usually reserved for an admin or superadmin
						$sql .= "and voicemail_uuid = :voicemail_uuid ";
						$parameters['voicemail_uuid'] = $this->voicemail_uuid;
					}
					else {
						//ensure that the requested voicemail box is assigned to this user
						$found = false;
						foreach($voicemail_uuids as $row) {
							if ($this->voicemail_uuid == $row['voicemail_uuid']) {
								$sql .= "and voicemail_uuid = :voicemail_uuid ";
								$parameters['voicemail_uuid'] = $row['voicemail_uuid'];
								$found = true;
							}
						}
						//id requested is not owned by the user return no results
						if (!$found) {
							$sql .= "and voicemail_uuid is null ";
						}
					}
				}
				else {
					if (!empty($voicemail_ids) && @sizeof($voicemail_ids) != 0) {
						//show only the assigned voicemail ids
						$sql .= "and ";
						if (is_numeric($this->voicemail_id) && in_array($this->voicemail_id, $voicemail_ids)) {
							$sql_where = 'voicemail_id = :voicemail_id ';
							$parameters['voicemail_id'] = $this->voicemail_id;
						}
						else {
							$x = 0;
							$sql_where = '';
							foreach($voicemail_ids as $voicemail_id) {
								$sql_where_or[] = "voicemail_id = :voicemail_id_".$x;
								$parameters['voicemail_id_'.$x] = $voicemail_id;
								$x++;
							}
							$sql_where .= '('.implode(' or ', $sql_where_or).') ';
						}
						$sql .= $sql_where;
						unset($sql_where_or);
					}
					else {
						//no assigned voicemail ids so return no results
						$sql .= "and voicemail_uuid is null ";
					}
				}
				$sql .= "order by voicemail_id asc ";
				$parameters['domain_uuid'] = $this->domain_uuid;
				$result = $this->database->select($sql, $parameters, 'all');
				unset($sql, $parameters);
				return $result;
		}

		public function messages() {

			//get the voicemails
				$voicemails = $this->voicemails();

			//add the voicemail messages to the array
				if (is_array($voicemails)) {
					$i = 0;
					foreach ($voicemails as $row) {
						//get the voicemail messages
						$voicemails[$i]['messages'] = $this->voicemail_messages($row['voicemail_id']);
						$i++;
					}
				}

			//return the array
				return $voicemails;
		}

		private function voicemail_messages($voicemail_id): array {

			//check if for valid input
				if (!is_numeric($voicemail_id) || !is_uuid($this->domain_uuid)) {
					return [];
				}

			//set the time zone
				$time_zone = $this->settings->get('domain', 'time_zone', date_default_timezone_get());

			//get the message from the database
				$sql = "select *, ";
				$sql .= "to_char(timezone(:time_zone, to_timestamp(m.created_epoch)), 'DD Mon YYYY') as created_date_formatted, \n";
				$sql .= "to_char(timezone(:time_zone, to_timestamp(m.created_epoch)), 'HH12:MI:SS am') as created_time_formatted \n";
				$sql .= "from v_voicemail_messages as m, v_voicemails as v ";
				$sql .= "where m.domain_uuid = :domain_uuid ";
				$sql .= "and m.voicemail_uuid = v.voicemail_uuid ";
				if (is_array($voicemail_id) && @sizeof($voicemail_id) != 0) {
					$x = 0;
					$sql .= "and ( ";
					foreach ($voicemail_id as $row) {
						$sql_where_or[] = "v.voicemail_id = :voicemail_id_".$x;
						$parameters['voicemail_id_'.$x] = $row['voicemail_id'];
						$x++;
					}
					$sql .= implode(' or ', $sql_where_or);
					$sql .= ") ";
					unset($sql_where_or);
				}
				else {
					$sql .= "and v.voicemail_id = :voicemail_id ";
					$parameters['voicemail_id'] = $voicemail_id;
				}
				if (empty($this->order_by)) {
					$sql .= "order by v.voicemail_id, m.created_epoch desc ";
				}
				else {
					$sql .= "order by v.voicemail_id, m.".$this->order_by." ".$this->order." ";
				}
				//if paging offset defined, apply it along with rows per page
				if (isset($this->offset)) {
					$rows_per_page = $this->settings->get('domain', 'paging', 50);
					$offset = isset($this->offset) && is_numeric($this->offset) ? $this->offset : 0;
					$sql .= limit_offset($rows_per_page, $offset);
				}
				$parameters['domain_uuid'] = $this->domain_uuid;
				$parameters['time_zone'] = $time_zone;
				$result = $this->database->select($sql, $parameters, 'all');
				unset($sql, $parameters);

			//update the array with additional information
				if (is_array($result)) {
					foreach ($result as $i => $row) {
						//set the greeting directory
						$path = $this->settings->get('switch', 'voicemail', '/var/lib/freeswitch/storage').'/default/'.$_SESSION['domain_name'].'/'.$row['voicemail_id'];
						if (file_exists($path.'/msg_'.$row['voicemail_message_uuid'].'.wav')) {
							$result[$i]['file_path'] = $path.'/msg_'.$row['voicemail_message_uuid'].'.wav';
						}
						if (file_exists($path.'/msg_'.$row['voicemail_message_uuid'].'.mp3')) {
							$result[$i]['file_path'] = $path.'/msg_'.$row['voicemail_message_uuid'].'.mp3';
						}
						$result[$i]['file_size'] = filesize($result[$i]['file_path'] ?? '');
						$result[$i]['file_size_label'] = byte_convert($result[$i]['file_size'] ?? 0);
						$result[$i]['file_ext'] = substr($result[$i]['file_path'] ?? '', -3);

						$message_minutes = floor($row['message_length'] / 60);
						$message_seconds = $row['message_length'] % 60;

						//use International System of Units (SI) - Source: https://en.wikipedia.org/wiki/International_System_of_Units
						$result[$i]['message_length_label'] = ($message_minutes > 0 ? $message_minutes.' min' : '').($message_seconds > 0 ? ' '.$message_seconds.' s' : '');
						$result[$i]['created_date'] = date("j M Y g:i a",$row['created_epoch']);
					}
				}
				else {
					$result = [];
				}
				return $result;
		}

		public function voicemail_delete($records) {
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

						//filter out unchecked sip profiles
							foreach ($records as $x => $record) {
								if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}

						//get necessary voicemail details
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->uuid_prefix."uuid as uuid, voicemail_id from v_".$this->table." ";
								$sql .= "where ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
								$rows = $this->database->select($sql, $parameters ?? null, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										$voicemail_ids[$row['uuid']] = $row['voicemail_id'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//loop through voicemail ids
							if (is_array($voicemail_ids) && @sizeof($voicemail_ids) != 0) {
								$x = 0;
								foreach ($voicemail_ids as $voicemail_uuid => $voicemail_id) {

									//delete voicemail message recording and greeting files
										if (is_numeric($voicemail_id)) {
											$file_path = $_SESSION['switch']['voicemail']['dir']."/default/".$_SESSION['domain_name']."/".$voicemail_id;
											foreach (glob($file_path."/*.*") as $file_name) {
												@unlink($file_name);
											}
											@rmdir($file_path);
										}

									//reset message waiting indicator status
										$this->voicemail_id = $voicemail_id;
										$this->voicemail_uuid = $voicemail_uuid;
										$this->domain_uuid = $this->domain_uuid;
										$this->message_waiting();

									//build the delete array
										$array[$this->table][$x]['voicemail_uuid'] = $voicemail_uuid;
										$array[$this->table][$x]['domain_uuid'] = $this->domain_uuid;
										$array['voicemail_options'][$x]['voicemail_uuid'] = $voicemail_uuid;
										$array['voicemail_options'][$x]['domain_uuid'] = $this->domain_uuid;
										$array['voicemail_messages'][$x]['voicemail_uuid'] = $voicemail_uuid;
										$array['voicemail_messages'][$x]['domain_uuid'] = $this->domain_uuid;
										$array['voicemail_destinations'][$x]['voicemail_uuid'] = $voicemail_uuid;
										$array['voicemail_destinations'][$x]['domain_uuid'] = $this->domain_uuid;
										if (is_numeric($voicemail_id)) {
											$array['voicemail_greetings'][$x]['voicemail_id'] = $voicemail_id;
											$array['voicemail_greetings'][$x]['domain_uuid'] = $this->domain_uuid;
										}
										$x++;
										$array['voicemail_destinations'][$x]['voicemail_uuid_copy'] = $voicemail_uuid;
										$array['voicemail_destinations'][$x]['domain_uuid'] = $this->domain_uuid;
										$x++;
								}
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {

								//grant temporary permissions
									$p = permissions::new();
									$p->add('voicemail_delete', 'temp');
									$p->add('voicemail_option_delete', 'temp');
									$p->add('voicemail_message_delete', 'temp');
									$p->add('voicemail_destination_delete', 'temp');
									$p->add('voicemail_greeting_delete', 'temp');

								//execute delete
									$this->database->app_name = $this->app_name;
									$this->database->app_uuid = $this->app_uuid;
									$this->database->delete($array);
									unset($array);

								//revoke temporary permissions
									$p->delete('voicemail_delete', 'temp');
									$p->delete('voicemail_option_delete', 'temp');
									$p->delete('voicemail_message_delete', 'temp');
									$p->delete('voicemail_destination_delete', 'temp');
									$p->delete('voicemail_greeting_delete', 'temp');

								//clear the destinations session array
									if (isset($_SESSION['destinations']['array'])) {
										unset($_SESSION['destinations']['array']);
									}

								//set message
									message::add($text['message-delete']);
							}
							unset($records, $voicemail_ids);
					}
			}
		}

		public function voicemail_options_delete($records) {
			//assign private variables
				$this->permission_prefix = 'voicemail_option_';
				$this->list_page = 'voicemail_edit.php?id='.$this->voicemail_uuid;
				$this->table = 'voicemail_options';
				$this->uuid_prefix = 'voicemail_option_';

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

						//filter out unchecked sip profiles
							foreach ($records as $x => $record) {
								if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['uuid'])) {
									//build the delete array
										$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];
										$array[$this->table][$x]['voicemail_uuid'] = $this->voicemail_uuid;
										$array[$this->table][$x]['domain_uuid'] = $this->domain_uuid;
								}
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {
								//execute delete
									$this->database->app_name = $this->app_name;
									$this->database->app_uuid = $this->app_uuid;
									$this->database->delete($array);
									unset($array);
							}
							unset($records);
					}
			}
		}

		public function voicemail_destinations_delete($records) {
			//assign private variables
				$this->list_page = 'voicemail_edit.php?id='.$this->voicemail_uuid;
				$this->table = 'voicemail_destinations';
				$this->uuid_prefix = 'voicemail_destination_';

			if (permission_exists('voicemail_forward')) {

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

						//filter out unchecked sip profiles
							foreach ($records as $x => $record) {
								if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['uuid'])) {
									//build the delete array
										$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];
										$array[$this->table][$x]['voicemail_uuid'] = $this->voicemail_uuid;
										$array[$this->table][$x]['domain_uuid'] = $this->domain_uuid;
								}
							}

						//delete the checked rows
							if (!empty($array) && is_array($array) && @sizeof($array) != 0) {
								//grant temporary permissions
									$p = permissions::new();
									$p->add('voicemail_destination_delete', 'temp');

								//execute delete
									$this->database->app_name = $this->app_name;
									$this->database->app_uuid = $this->app_uuid;
									$this->database->delete($array);
									unset($array);

								//revoke temporary permissions
									$p->delete('voicemail_destination_delete', 'temp');
							}
							unset($records);
					}
			}
		}

		public function voicemail_toggle($records) {
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

						//filter out unchecked sip profiles
							foreach ($records as $x => $record) {
								if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}

						//get necessary voicemail details
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->uuid_prefix."uuid as uuid, voicemail_id, ".$this->toggle_field." as toggle from v_".$this->table." ";
								$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
								$sql .= "and ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
								$parameters['domain_uuid'] = $this->domain_uuid;
								$rows = $this->database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										$voicemails[$row['uuid']]['state'] = $row['toggle'];
										$voicemails[$row['uuid']]['id'] = $row['voicemail_id'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//loop through voicemails
							if (is_array($voicemails) && @sizeof($voicemails) != 0) {
								$x = 0;
								foreach ($voicemails as $voicemail_uuid => $voicemail) {

									//reset message waiting indicator status
										$this->voicemail_id = $voicemail['id'];
										$this->voicemail_uuid = $voicemail_uuid;
										$this->domain_uuid = $this->domain_uuid;
										$this->message_waiting();

									//build update array
										$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $voicemail_uuid;
										$array[$this->table][$x][$this->toggle_field] = $voicemail['state'] == $this->toggle_values[0] ? $this->toggle_values[1] : $this->toggle_values[0];
										$x++;
								}
							}

						//save the changes
							if (is_array($array) && @sizeof($array) != 0) {

								//save the array
									$this->database->app_name = $this->app_name;
									$this->database->app_uuid = $this->app_uuid;
									$this->database->save($array);
									unset($array);

								//clear the destinations session array
									if (isset($_SESSION['destinations']['array'])) {
										unset($_SESSION['destinations']['array']);
									}

								//set message
									message::add($text['message-toggle']);
							}
							unset($records, $voicemails);
					}

			}
		}

		public function message_count() {

			//check if for valid input
				if (!is_uuid($this->voicemail_uuid) || !is_uuid($this->domain_uuid)) {
					return false;
				}

			//return the message count
				$sql = "select count(*) from v_voicemail_messages ";
				$sql .= "where domain_uuid = :domain_uuid ";
				$sql .= "and voicemail_uuid = :voicemail_uuid ";
				$parameters['domain_uuid'] = $this->domain_uuid;
				$parameters['voicemail_uuid'] = $this->voicemail_uuid;
				return $this->database->select($sql, $parameters, 'column');
				unset($sql, $parameters);

		}

		public function message_waiting() {
			//get the voicemail id
				$this->get_voicemail_id();

			//send the message waiting status

				$esl = event_socket::create();
				if ($esl->is_connected()) {
					$switch_cmd = "luarun app.lua voicemail mwi ".$this->voicemail_id."@".$_SESSION['domain_name'];
					$switch_result = event_socket::api($switch_cmd);
				}
		}

		public function message_delete() {

			//get the voicemail id
				$this->get_voicemail_id();

			//check if for valid input
				if (!is_numeric($this->voicemail_id)
					|| !is_uuid($this->voicemail_uuid)
					|| !is_uuid($this->domain_uuid)
					|| !is_uuid($this->voicemail_message_uuid)
					) {
					return false;
				}

			//delete the recording
				$file_path = $_SESSION['switch']['voicemail']['dir']."/default/".$_SESSION['domain_name']."/".$this->voicemail_id;
				if (is_uuid($this->voicemail_message_uuid)) {
					foreach (glob($file_path."/intro_msg_".$this->voicemail_message_uuid.".*") as $file_name) {
						unlink($file_name);
					}
					foreach (glob($file_path."/intro_".$this->voicemail_message_uuid.".*") as $file_name) {
						unlink($file_name);
					}
					foreach (glob($file_path."/msg_".$this->voicemail_message_uuid.".*") as $file_name) {
						unlink($file_name);
					}
				}
				else {
					foreach (glob($file_path."/msg_*.*") as $file_name) {
						unlink($file_name); //remove all recordings
					}
				}

			//build delete array
				$array['voicemail_messages'][0]['domain_uuid'] = $this->domain_uuid;
				$array['voicemail_messages'][0]['voicemail_uuid'] = $this->voicemail_uuid;
				if (is_uuid($this->voicemail_message_uuid)) {
					$array['voicemail_messages'][0]['voicemail_message_uuid'] = $this->voicemail_message_uuid;
				}

			//grant temporary permissions
				$p = permissions::new();
				$p->add('voicemail_message_delete', 'temp');

			//execute delete
				$this->database->app_name = $this->app_name;
				$this->database->app_name = $this->app_uuid;
				$this->database->delete($array);
				unset($array);

			//revoke temporary permissions
				$p->delete('voicemail_message_delete', 'temp');

			//check the message waiting status
				$this->message_waiting();
		}

		public function message_toggle() {

			//check if for valid input
				if (!is_uuid($this->voicemail_uuid)
					|| !is_uuid($this->domain_uuid)
					|| !is_uuid($this->voicemail_message_uuid)
					) {
					return false;
				}

			//get message status
				$sql = "select message_status from v_voicemail_messages ";
				$sql .= "where voicemail_message_uuid = :voicemail_message_uuid ";
				$parameters['voicemail_message_uuid'] = $this->voicemail_message_uuid;
				$new_status = $this->database->select($sql, $parameters, 'column') != 'saved' ? 'saved' : null;
				unset($sql, $parameters);

			//build message status update array
				$array['voicemail_messages'][0]['voicemail_message_uuid'] = $this->voicemail_message_uuid;
				$array['voicemail_messages'][0]['message_status'] = $new_status;

			//grant temporary permissions
				$p = permissions::new();
				$p->add('voicemail_message_edit', 'temp');

			//execute update
				$this->database->app_name = $this->app_name;
				$this->database->app_name = $this->app_uuid;
				$this->database->save($array);
				unset($array);

			//revoke temporary permissions
				$p->delete('voicemail_message_edit', 'temp');

			//check the message waiting status
				$this->message_waiting();
		}

		public function message_resend() {

			//check if for valid input
			if (!is_uuid($this->voicemail_uuid)
				|| !is_uuid($this->domain_uuid)
				|| !is_uuid($this->voicemail_message_uuid)
				) {
				return false;
			}

			//add multi-lingual support
			$language = new text;
			$text = $language->get();

			//add the settings object
			$settings = new settings(["domain_uuid" => $this->domain_uuid, "user_uuid" => $this->user_uuid]);
			$email_from = $settings->get('email', 'smtp_from', '');
			$email_from_name = $settings->get('email', 'smtp_from_name', 'PBX');
			$switch_scripts = $settings->get('switch', 'scripts', '/usr/share/freeswitch/scripts');
			$switch_voicemail = $settings->get('switch', 'voicemail', '/var/lib/freeswitch/storage/voicemail');
			$language_dialect = $settings->get('domain', 'language', 'en-us');
			$time_zone = $settings->get('domain', 'time_zone', 'UTC');
			$display_domain_name = $settings->get('voicemail', 'display_domain_name', 'false');

			//get voicemail message details
			$sql = "select ";
			$sql .= "	vm.*, ";
			$sql .= "	to_char(timezone(:time_zone, to_timestamp(vm.created_epoch)), 'Day DD Mon YYYY HH:MI:SS PM') as message_date, ";
			$sql .= "	v.voicemail_id, ";
			$sql .= "	v.voicemail_mail_to, ";
			$sql .= "	v.voicemail_description, ";
			$sql .= "	v.voicemail_file, ";
			$sql .= "	d.domain_name ";
			$sql .= "from ";
			$sql .= "	v_voicemail_messages as vm ";
			$sql .= "	left join v_voicemails as v on vm.voicemail_uuid = v.voicemail_uuid ";
			$sql .= "	left join v_domains as d on vm.domain_uuid = d.domain_uuid ";
			$sql .= "where ";
			$sql .= "	vm.voicemail_message_uuid = :voicemail_message_uuid ";
			$sql .= "limit 1" ;
			$parameters['time_zone'] = $time_zone;
			$parameters['voicemail_message_uuid'] = $this->voicemail_message_uuid;
			$message = $this->database->select($sql, $parameters, 'row');
			unset($sql, $parameters);

			//retrieve appropriate email template
			$sql = "select ";
			$sql .= "	template_subject, ";
			$sql .= "	template_body ";
			$sql .= "from ";
			$sql .= "	v_email_templates ";
			$sql .= "where ";
			$sql .= "	template_language = :template_language ";
			$sql .= "	and template_category = 'voicemail' ";
			$sql .= "	and template_subcategory = '".(!empty($message['message_transcription']) ? 'transcription' : 'default')."' ";
			$sql .= "	and template_type = 'html' ";
			$sql .= "	and template_enabled = 'true' ";
			$sql .= "	and (domain_uuid = :domain_uuid or domain_uuid is null) ";
			$sql .= "limit 1 ";
			$parameters['template_language'] = $language_dialect;
			$parameters['domain_uuid'] = $this->domain_uuid;
			$template = $this->database->select($sql, $parameters, 'row');
			unset($sql, $parameters);

			//determine formatted voicemail name
			$voicemail_name_formatted = $message['voicemail_id'];
			if ($display_domain_name == 'true') {
				$voicemail_name_formatted = $message['voicemail_id'].'@'.$message['domain_name'];
			}
			if (!empty($message['voicemail_description'])) {
				$voicemail_name_formatted .= ' ('.$message['voicemail_description'].')';
			}

			//replace subject variables
			if (!empty($template['template_subject'])) {
				$template['template_subject'] = str_replace('${caller_id_name}', $message['caller_id_name'], $template['template_subject']);
				$template['template_subject'] = str_replace('${caller_id_number}', $message['caller_id_number'], $template['template_subject']);
				$template['template_subject'] = str_replace('${message_date}', $message['message_date'], $template['template_subject']);
				$template['template_subject'] = str_replace('${message_duration}', '0'.gmdate("G:i:s", ($message['message_length'] ?? 0)), $template['template_subject']);
				$template['template_subject'] = str_replace('${account}', $voicemail_name_formatted, $template['template_subject']);
				$template['template_subject'] = str_replace('${voicemail_id}', $message['voicemail_id'], $template['template_subject']);
				$template['template_subject'] = str_replace('${voicemail_description}', $message['voicemail_description'], $template['template_subject']);
				$template['template_subject'] = str_replace('${voicemail_name_formatted}', $voicemail_name_formatted, $template['template_subject']);
				$template['template_subject'] = str_replace('${domain_name}', $message['domain_name'], $template['template_subject']);
			}
			else {
				$template['template_subject'] = $text['label-voicemail_from'].' '.$message['caller_id_name'].' <'.$message['caller_id_number'].'> 0'.gmdate("G:i:s", ($message['message_length'] ?? 0));
			}

			//encode subject
			$template['template_subject'] = trim(iconv_mime_encode(null, $template['template_subject'], ['scheme'=>'B','output-charset'=>'utf-8', 'line-break-chars'=>"\n"]), ': ');

			//determine voicemail message file path and type
			$voicemail_message_path = $switch_voicemail.'/default/'.$message['domain_name'].'/'.$message['voicemail_id'];
			if (
				!empty($message['message_base64']) &&
				!file_exists($voicemail_message_path.'/msg_'.$message['voicemail_message_uuid'].'.wav') &&
				!file_exists($voicemail_message_path.'/msg_'.$message['voicemail_message_uuid'].'.mp3')
				) {
				$voicemail_message_decoded = base64_decode($message['message_base64']);
				file_put_contents($voicemail_message_path.'/msg_'.$message['voicemail_message_uuid'].'.ext', $voicemail_message_decoded);
				$finfo = finfo_open(FILEINFO_MIME_TYPE);
				$voicemail_message_file_mime = finfo_file($finfo, $voicemail_message_path.'/msg_'.$message['voicemail_message_uuid'].'.ext');
				finfo_close($finfo);
				unset($voicemail_message_decoded);
				switch ($voicemail_message_file_mime) {
					case 'audio/x-wav':
					case 'audio/wav':
						$voicemail_message_file_ext = 'wav';
						break;
					case 'audio/mpeg':
					case 'audio/mp3':
						$voicemail_message_file_ext = 'mp3';
						break;
				}
				rename($voicemail_message_path.'/msg_'.$message['voicemail_message_uuid'].'.ext', $voicemail_message_path.'/msg_'.$message['voicemail_message_uuid'].'.'.$voicemail_message_file_ext);
				$voicemail_message_file = 'msg_'.$message['voicemail_message_uuid'].'.'.$voicemail_message_file_ext;
			}
			else {
				if (file_exists($voicemail_message_path.'/msg_'.$message['voicemail_message_uuid'].'.wav')) { $voicemail_message_file_ext = 'wav'; }
				if (file_exists($voicemail_message_path.'/msg_'.$message['voicemail_message_uuid'].'.mp3')) { $voicemail_message_file_ext = 'mp3'; }
				$voicemail_message_file = 'msg_'.$message['voicemail_message_uuid'].'.'.$voicemail_message_file_ext;
				$voicemail_message_file_mime = mime_content_type($voicemail_message_path.'/msg_'.$message['voicemail_message_uuid'].'.'.$voicemail_message_file_ext);
			}

			//determine voicemail intro file path
			if (
				!empty($message['message_intro_base64']) &&
				!file_exists($voicemail_message_path.'/intro_'.$message['voicemail_message_uuid'].'.wav') &&
				!file_exists($voicemail_message_path.'/intro_'.$message['voicemail_message_uuid'].'.mp3')
				) {
				$voicemail_intro_decoded = base64_decode($message['message_intro_base64']);
				file_put_contents($voicemail_message_path.'/intro_'.$message['voicemail_message_uuid'].'.'.$voicemail_message_file_ext, $voicemail_intro_decoded);
				$voicemail_intro_file = 'intro_'.$message['voicemail_message_uuid'].'.'.$voicemail_message_file_ext;
			}
			else {
				$voicemail_intro_file = 'intro_'.$message['voicemail_message_uuid'].'.'.$voicemail_message_file_ext;
			}

			//combine voicemail intro and message files
			$sox = system('which sox');
			if (file_exists($voicemail_message_path.'/'.$voicemail_intro_file) && !empty($sox)) {
				$voicemail_combined_file = 'intro_msg_'.$message['voicemail_message_uuid'].'.'.$voicemail_message_file_ext;
				exec($sox.' '.$voicemail_message_path.'/'.$voicemail_intro_file.' '.$voicemail_message_path.'/'.$voicemail_message_file.' '.$voicemail_message_path.'/'.$voicemail_combined_file);
				if (file_exists($voicemail_message_path.'/'.$voicemail_combined_file)) {
					$message['message_combined_base64'] = base64_encode(file_get_contents($voicemail_message_path.'/'.$voicemail_combined_file));
				}
			}

			//replace body variables
			if (!empty($template['template_body'])) {
				$template['template_body'] = str_replace('${caller_id_name}', $message['caller_id_name'], $template['template_body']);
				$template['template_body'] = str_replace('${caller_id_number}', $message['caller_id_number'], $template['template_body']);
				$template['template_body'] = str_replace('${message_date}', $message['message_date'], $template['template_body']);
				$template['template_body'] = str_replace('${message_text}', $message['message_transcription'], $template['template_body']);
				$template['template_body'] = str_replace('${message_duration}', '0'.gmdate("G:i:s", ($message['message_length'] ?? 0)), $template['template_body']);
				$template['template_body'] = str_replace('${account}', $voicemail_name_formatted, $template['template_body']);
				$template['template_body'] = str_replace('${voicemail_id}', $message['voicemail_id'], $template['template_body']);
				$template['template_body'] = str_replace('${voicemail_description}', $message['voicemail_description'], $template['template_body']);
				$template['template_body'] = str_replace('${voicemail_name_formatted}', $voicemail_name_formatted, $template['template_body']);
				$template['template_body'] = str_replace('${domain_name}', $message['domain_name'], $template['template_body']);
				$template['template_body'] = str_replace('${sip_to_user}', $message['voicemail_id'], $template['template_body']);
				$template['template_body'] = str_replace('${dialed_user}', $message['voicemail_id'], $template['template_body']);
				if (!empty($message['voicemail_file'])) {
					if ($message['voicemail_file'] == 'attach' && (file_exists($voicemail_message_path.'/'.$voicemail_combined_file) || file_exists($voicemail_message_path.'/'.$voicemail_message_file))) {
						$template['template_body'] = str_replace('${message}', $text['label-attached'], $template['template_body']);
					}
					else if ($message['voicemail_file'] == 'link') {
						$template['template_body'] = str_replace('${message}', "<a href='https://".$message['domain_name'].PROJECT_PATH.'/app/voicemails/voicemail_messages.php?action=download&id='.$message['voicemail_id'].'&voicemail_uuid='.$message['voicemail_uuid'].'&uuid='.$message['voicemail_message_uuid']."&t=bin'>".$text['label-download']."</a>", $template['template_body']);
					}
					else { // listen
						$template['template_body'] = str_replace('${message}', "<a href='https://".$message['domain_name'].PROJECT_PATH.'/app/voicemails/voicemail_messages.php?action=autoplay&id='.$message['voicemail_uuid'].'&uuid='.$message['voicemail_message_uuid'].'&vm='.$message['voicemail_id']."'>".$text['label-listen']."</a>", $template['template_body']);
					}
				}
			}
			else {
				$template['template_body'] = "<html>\n<body>\n";
				if (!empty($message['caller_id_name']) && $message['caller_id_name'] != $message['caller_id_number']) {
					$template['template_body'] .= $message['caller_id_name']."<br>\n";
				}
				$template['template_body'] .= $message['caller_id_number']."<br>\n";
				$template['template_body'] .= $message['message_date']."<br>\n";
				if (!empty($message['voicemail_file'])) {
					if ($message['voicemail_file'] == 'attach' && (file_exists($voicemail_message_path.'/'.$voicemail_combined_file) || file_exists($voicemail_message_path.'/'.$voicemail_message_file))) {
						$template['template_body'] .= "<br>\n".$text['label-attached'];
					}
					else if ($message['voicemail_file'] == 'link') {
						$template['template_body'] .= "<br>\n<a href='https://".$message['domain_name'].PROJECT_PATH.'/app/voicemails/voicemail_messages.php?action=download&id='.$message['voicemail_id'].'&voicemail_uuid='.$message['voicemail_uuid'].'&uuid='.$message['voicemail_message_uuid']."&t=bin'>".$text['label-download'].'</a>';
					}
					else { // listen
						$template['template_body'] .= "<br>\n<a href='https://".$message['domain_name'].PROJECT_PATH.'/app/voicemails/voicemail_messages.php?action=autoplay&id='.$message['voicemail_uuid'].'&uuid='.$message['voicemail_message_uuid'].'&vm='.$message['voicemail_id']."'>".$text['label-listen'].'</a>';
					}
				}
				$template['template_body'] .= "\n</body>\n</html>";
			}

			//build message status update array
			$array['email_queue'][0]['email_queue_uuid'] = $email_queue_uuid = uuid();
			$array['email_queue'][0]['domain_uuid'] = $this->domain_uuid;
			$array['email_queue'][0]['hostname'] = gethostname();
			$array['email_queue'][0]['email_date'] = 'now()';
			$array['email_queue'][0]['email_from'] = $email_from_name.'<'.$email_from.'>';
			$array['email_queue'][0]['email_to'] = $message['voicemail_mail_to'];
			$array['email_queue'][0]['email_subject'] = $template['template_subject'];
			$array['email_queue'][0]['email_body'] = $template['template_body'];
			$array['email_queue'][0]['email_status'] = 'waiting';
			$array['email_queue'][0]['email_uuid'] = $this->voicemail_message_uuid;
			$array['email_queue'][0]['email_transcription'] = $message['message_transcription'];
			$array['email_queue'][0]['insert_date'] = 'now()';
			$array['email_queue'][0]['insert_user'] = $this->user_uuid;

			//add voicemail file details (and/or base64) to queue attachments
			if (!empty($message['voicemail_file']) && $message['voicemail_file'] == 'attach' && (file_exists($voicemail_message_path.'/'.$voicemail_combined_file) || file_exists($voicemail_message_path.'/'.$voicemail_message_file))) {
				$array['email_queue_attachments'][0]['email_queue_attachment_uuid'] = uuid();
				$array['email_queue_attachments'][0]['domain_uuid'] = $this->domain_uuid;
				$array['email_queue_attachments'][0]['email_queue_uuid'] = $email_queue_uuid;
				$array['email_queue_attachments'][0]['email_attachment_type'] = $voicemail_message_file_ext;
				$array['email_queue_attachments'][0]['email_attachment_path'] = $voicemail_message_path;
				$array['email_queue_attachments'][0]['email_attachment_name'] = $voicemail_combined_file ?? $voicemail_message_file;
				$array['email_queue_attachments'][0]['email_attachment_base64'] = $message['message_combined_base64'] ?? $message['message_base64'];
				$array['email_queue_attachments'][0]['email_attachment_cid'] = !empty($message['message_combined_base64']) || !empty($message['message_base64']) ? uuid() : null;
				$array['email_queue_attachments'][0]['email_attachment_mime_type'] = $voicemail_message_file_mime;
				$array['email_queue_attachments'][0]['insert_date'] = 'now()';
				$array['email_queue_attachments'][0]['insert_user'] = $this->user_uuid;
			}

			//grant temporary permissions
			$p = permissions::new();
			$p->add('email_queue_add', 'temp');
			$p->add('email_queue_attachment_add', 'temp');

			//execute update
			$this->database->app_name = $this->app_name;
			$this->database->app_name = $this->app_uuid;
			$this->database->save($array);
			unset($array);

			//revoke temporary permissions
			$p->delete('email_queue_add', 'temp');
			$p->delete('email_queue_attachment_add', 'temp');

			//remove temp file from base64 output
			if (!empty($message['message_base64']) && file_exists($voicemail_message_path.'/'.$voicemail_message_file)) {
				@unlink($voicemail_message_path.'/'.$voicemail_message_file);
				@unlink($voicemail_message_path.'/'.$voicemail_intro_file);
				@unlink($voicemail_message_path.'/'.$voicemail_combined_file);
			}

		}

		public function message_transcribe() {

			//get the voicemail id
			$this->get_voicemail_id();

			//check if for valid input
			if (!is_numeric($this->voicemail_id)
				|| !is_uuid($this->voicemail_uuid)
				|| !is_uuid($this->domain_uuid)
				|| !is_uuid($this->voicemail_message_uuid)
				) {
				return false;
			}

			//add the settings object
			$settings = new settings(["domain_uuid" => $this->domain_uuid, "user_uuid" => $this->user_uuid]);
			$transcribe_enabled = $settings->get('transcribe', 'enabled', false);
			$transcribe_engine = $settings->get('transcribe', 'engine', '');
			$switch_voicemail = $settings->get('switch', 'voicemail', '/var/lib/freeswitch/storage/voicemail');

			//transcribe multiple recordings
			if ($transcribe_enabled && !empty($transcribe_engine)) {

				//get voicemail message base64
				$sql = "select message_base64 from v_voicemail_messages where voicemail_message_uuid = :voicemail_message_uuid ";
				$parameters['voicemail_message_uuid'] = $this->voicemail_message_uuid;
				$voicemail_message_base64 = $this->database->select($sql, $parameters, 'column');
				unset($sql, $parameters);

				//define voicemail message file path
				$voicemail_message_path = $switch_voicemail.'/default/'.$_SESSION['domain_name'].'/'.$this->voicemail_id;

				//determine voicemail message file properties (decode if base64)
				if (
					!empty($voicemail_message_base64) &&
					!file_exists($voicemail_message_path.'/msg_'.$this->voicemail_message_uuid.'.wav') &&
					!file_exists($voicemail_message_path.'/msg_'.$this->voicemail_message_uuid.'.mp3')
					) {
					$voicemail_message_decoded = base64_decode($voicemail_message_base64);
					file_put_contents($voicemail_message_path.'/msg_'.$this->voicemail_message_uuid.'.ext', $voicemail_message_decoded);
					$finfo = finfo_open(FILEINFO_MIME_TYPE);
					$voicemail_message_file_mime = finfo_file($finfo, $voicemail_message_path.'/msg_'.$this->voicemail_message_uuid.'.ext');
					finfo_close($finfo);
					switch ($voicemail_message_file_mime) {
						case 'audio/x-wav':
						case 'audio/wav':
							$voicemail_message_file_ext = 'wav';
							break;
						case 'audio/mpeg':
						case 'audio/mp3':
							$voicemail_message_file_ext = 'mp3';
							break;
					}
					unset($voicemail_message_decoded, $voicemail_message_file_mime);
					rename($voicemail_message_path.'/msg_'.$this->voicemail_message_uuid.'.ext', $voicemail_message_path.'/msg_'.$this->voicemail_message_uuid.'.'.$voicemail_message_file_ext);
					$voicemail_message_file = 'msg_'.$this->voicemail_message_uuid.'.'.$voicemail_message_file_ext;
				}
				else {
					if (file_exists($voicemail_message_path.'/msg_'.$this->voicemail_message_uuid.'.wav')) { $voicemail_message_file_ext = 'wav'; }
					if (file_exists($voicemail_message_path.'/msg_'.$this->voicemail_message_uuid.'.mp3')) { $voicemail_message_file_ext = 'mp3'; }
					$voicemail_message_file = 'msg_'.$this->voicemail_message_uuid.'.'.$voicemail_message_file_ext;
				}
				unset($voicemail_message_file_ext);

				//add the transcribe object
				$transcribe = new transcribe($settings);

				//transcribe the voicemail message file
				$transcribe->audio_path = $voicemail_message_path;
				$transcribe->audio_filename = basename($voicemail_message_file);
				$message_transcription = $transcribe->transcribe();

				//build voicemail message data array
				if (!empty($message_transcription)) {
					$array['voicemail_messages'][0]['voicemail_message_uuid'] = $this->voicemail_message_uuid;
					$array['voicemail_messages'][0]['message_transcription'] = $message_transcription;
				}

				//update the checked rows
				if (is_array($array) && @sizeof($array) != 0) {

					//grant temporary permissions
					$p = permissions::new();
					$p->add('voicemail_message_edit', 'temp');

					//execute update
					$this->database->app_name = $this->app_name;
					$this->database->app_name = $this->app_uuid;
					$this->database->save($array);
					unset($array);

					//revoke temporary permissions
					$p->delete('voicemail_message_edit', 'temp');

				}

				//remove temp file from base64 output
				if (!empty($voicemail_message_base64) && file_exists($voicemail_message_path.'/'.$voicemail_message_file)) {
					@unlink($voicemail_message_path.'/'.$voicemail_message_file);
				}

				return !empty($message_transcription) ? true : false;

			}

		}

		public function message_saved() {

			//check if for valid input
				if (!is_uuid($this->voicemail_uuid)
					|| !is_uuid($this->domain_uuid)
					|| !is_uuid($this->voicemail_message_uuid)
					) {
					return false;
				}

			//build message status update array
				$array['voicemail_messages'][0]['voicemail_message_uuid'] = $this->voicemail_message_uuid;
				$array['voicemail_messages'][0]['message_status'] = 'saved';

			//grant temporary permissions
				$p = permissions::new();
				$p->add('voicemail_message_edit', 'temp');

			//execute update
				$this->database->app_name = $this->app_name;
				$this->database->app_name = $this->app_uuid;
				$this->database->save($array);
				unset($array);

			//revoke temporary permissions
				$p->delete('voicemail_message_edit', 'temp');

			//check the message waiting status
				$this->message_waiting();
		}

		/**
		 * download the voicemail message intro
		 * @param string domain_name if domain name is not passed, then will be used from the session variable (if available) to generate the voicemail file path
		 */
		public function message_intro_download(string $domain_name = '') {

			//check domain name
			if (empty($domain_name)) {
				$domain_name = $_SESSION['domain_name'] ?? '';
			}

			//check if for valid input
			if (!is_numeric($this->voicemail_id)
				|| !is_uuid($this->voicemail_uuid)
				|| !is_uuid($this->domain_uuid)
				|| !is_uuid($this->voicemail_message_uuid)
				) {
				return false;
			}

			//change the message status
			$this->message_saved();

			//set source folder path
			$path = realpath($this->settings->get('switch','voicemail','/var/lib/freeswitch/storage/voicemail').'/default/'.$domain_name).'/'.$this->voicemail_id;

			//prepare base64 content from the database, if enabled
			if ($this->settings->get('voicemail','storage_type','') == 'base64') {
				$sql = "select message_intro_base64 ";
				$sql .= "from ";
				$sql .= "v_voicemail_messages as m, ";
				$sql .= "v_voicemails as v ";
				$sql .= "where ";
				$sql .= "m.voicemail_uuid = v.voicemail_uuid ";
				$sql .= "and v.voicemail_id = :voicemail_id ";
				$sql .= "and m.voicemail_uuid = :voicemail_uuid ";
				$sql .= "and m.domain_uuid = :domain_uuid ";
				$sql .= "and m.voicemail_message_uuid = :voicemail_message_uuid ";
				$parameters['voicemail_id'] = $this->voicemail_id;
				$parameters['voicemail_uuid'] = $this->voicemail_uuid;
				$parameters['domain_uuid'] = $this->domain_uuid;
				$parameters['voicemail_message_uuid'] = $this->voicemail_message_uuid;
				$message_intro_base64 = $this->database->select($sql, $parameters, 'column');
				if ($message_intro_base64 != '') {
					$message_intro_decoded = base64_decode($message_intro_base64);
					$file_ext = $this->settings->get('voicemail','file_ext','mp3');
					file_put_contents($path.'/intro_'.$this->voicemail_message_uuid.'.'.$file_ext, $message_intro_decoded);
				}
				unset($sql, $parameters, $message_intro_base64, $message_intro_decoded);
			}

			//prepare and stream the file
			if (file_exists($path.'/intro_'.$this->voicemail_message_uuid.'.wav')) {
				$file_path = $path.'/intro_'.$this->voicemail_message_uuid.'.wav';
			}
			else if (file_exists($path.'/intro_'.$this->voicemail_message_uuid.'.mp3')) {
				$file_path = $path.'/intro_'.$this->voicemail_message_uuid.'.mp3';
			}
			else {
				return false;
			}

			if (empty($file_path)) {
				return false;
			}

			$fd = fopen($file_path, "rb");
			if ($this->type == 'bin') {
				header("Content-Type: application/force-download");
				header("Content-Type: application/octet-stream");
				header("Content-Type: application/download");
				header("Content-Description: File Transfer");
				$file_ext = pathinfo($file_path, PATHINFO_EXTENSION);
				switch ($file_ext) {
					case "wav": header('Content-Disposition: attachment; filename="intro_'.$this->voicemail_message_uuid.'.wav"'); break;
					case "mp3": header('Content-Disposition: attachment; filename="intro_'.$this->voicemail_message_uuid.'.mp3"'); break;
					case "ogg": header('Content-Disposition: attachment; filename="intro_'.$this->voicemail_message_uuid.'.ogg"'); break;
				}
			}
			else {
				$file_ext = pathinfo($file_path, PATHINFO_EXTENSION);
				switch ($file_ext) {
					case "wav": header("Content-Type: audio/x-wav"); break;
					case "mp3": header("Content-Type: audio/mpeg"); break;
					case "ogg": header("Content-Type: audio/ogg"); break;
				}
			}
			header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
			header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // date in the past
			if ($this->type == 'bin') {
				header("Content-Length: ".filesize($file_path));
			}
			ob_end_clean();

			//content-range
			if (isset($_SERVER['HTTP_RANGE']) && $this->type != 'bin')  {
				$this->range_download($file_path);
			}

			fpassthru($fd);

			//if base64, remove temp file
			if ($this->settings->get('voicemail','storage_type','') == 'base64') {
				@unlink($path.'/intro_'.$this->voicemail_message_uuid.'.'.$file_ext);
			}

		}

		/**
		 * download the voicemail message
		 * @param string domain_name if domain name is not passed, then will be used from the session variable (if available) to generate the voicemail file path
		 */
		public function message_download(string $domain_name = '') {

			//check domain name
			if (empty($domain_name)) {
				$domain_name = $_SESSION['domain_name'] ?? '';
			}

			//check if for valid input
			if (!is_numeric($this->voicemail_id)
				|| !is_uuid($this->voicemail_uuid)
				|| !is_uuid($this->domain_uuid)
				|| !is_uuid($this->voicemail_message_uuid)
				) {
				return false;
			}

			//change the message status
			$this->message_saved();

			//set source folder path
			$path = realpath($this->settings->get('switch','voicemail','/var/lib/freeswitch/storage/voicemail').'/default/'.$domain_name).'/'.$this->voicemail_id;

			//prepare base64 content from the database, if enabled
			if ($this->settings->get('voicemail','storage_type','') == 'base64') {
				$sql = "select message_base64 ";
				$sql .= "from ";
				$sql .= "v_voicemail_messages as m, ";
				$sql .= "v_voicemails as v ";
				$sql .= "where ";
				$sql .= "m.voicemail_uuid = v.voicemail_uuid ";
				$sql .= "and v.voicemail_id = :voicemail_id ";
				$sql .= "and m.voicemail_uuid = :voicemail_uuid ";
				$sql .= "and m.domain_uuid = :domain_uuid ";
				$sql .= "and m.voicemail_message_uuid = :voicemail_message_uuid ";
				$parameters['voicemail_id'] = $this->voicemail_id;
				$parameters['voicemail_uuid'] = $this->voicemail_uuid;
				$parameters['domain_uuid'] = $this->domain_uuid;
				$parameters['voicemail_message_uuid'] = $this->voicemail_message_uuid;
				$message_base64 = $this->database->select($sql, $parameters, 'column');
				if ($message_base64 != '') {
					$message_decoded = base64_decode($message_base64);
					$file_ext = $this->settings->get('voicemail','file_ext','mp3');
					file_put_contents($path.'/msg_'.$this->voicemail_message_uuid.'.'.$file_ext, $message_decoded);
				}
				unset($sql, $parameters, $message_base64, $message_decoded);
			}

			//prepare and stream the file
			if (file_exists($path.'/msg_'.$this->voicemail_message_uuid.'.wav')) {
				$file_path = $path.'/msg_'.$this->voicemail_message_uuid.'.wav';
			}
			else if (file_exists($path.'/msg_'.$this->voicemail_message_uuid.'.mp3')) {
				$file_path = $path.'/msg_'.$this->voicemail_message_uuid.'.mp3';
			}
			else {
				return false;
			}

			if (empty($file_path)) {
				return false;
			}

			$fd = fopen($file_path, "rb");
			if ($this->type == 'bin') {
				header("Content-Type: application/force-download");
				header("Content-Type: application/octet-stream");
				header("Content-Type: application/download");
				header("Content-Description: File Transfer");
				$file_ext = pathinfo($file_path, PATHINFO_EXTENSION);
				switch ($file_ext) {
					case "wav": header('Content-Disposition: attachment; filename="msg_'.$this->voicemail_message_uuid.'.wav"'); break;
					case "mp3": header('Content-Disposition: attachment; filename="msg_'.$this->voicemail_message_uuid.'.mp3"'); break;
					case "ogg": header('Content-Disposition: attachment; filename="msg_'.$this->voicemail_message_uuid.'.ogg"'); break;
				}
			}
			else {
				$file_ext = pathinfo($file_path, PATHINFO_EXTENSION);
				switch ($file_ext) {
					case "wav": header("Content-Type: audio/x-wav"); break;
					case "mp3": header("Content-Type: audio/mpeg"); break;
					case "ogg": header("Content-Type: audio/ogg"); break;
				}
			}
			header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
			header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // date in the past
			if ($this->type == 'bin') {
				header("Content-Length: ".filesize($file_path));
			}
			ob_end_clean();

			//content-range
			if (isset($_SERVER['HTTP_RANGE']) && $this->type != 'bin')  {
				$this->range_download($file_path);
			}

			fpassthru($fd);

			//if base64, remove temp file
			if ($this->settings->get('voicemail','storage_type','') == 'base64') {
				@unlink($path.'/msg_'.$this->voicemail_message_uuid.'.'.$file_ext);
			}

		}

		/*
		 * range download method (helps safari play audio sources)
		 */
		private function range_download($file) {
			$esl = @fopen($file, 'rb');

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
				if (!empty($range0) && $range0 == '-') {
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
				fseek($esl, $start);
				header('HTTP/1.1 206 Partial Content');
			}
			// Notify the client the byte range we'll be outputting
			header("Content-Range: bytes $start-$end/$size");
			header("Content-Length: $length");

			// Start buffered download
			$buffer = 1024 * 8;
			while(!feof($esl) && ($p = ftell($esl)) <= $end) {
				if ($p + $buffer > $end) {
					// In case we're only outputtin a chunk, make sure we don't
					// read past the length
					$buffer = $end - $p + 1;
				}
				set_time_limit(0); // Reset time limit for big files
				echo fread($esl, $buffer);
				flush(); // Free up memory. Otherwise large files will trigger PHP's memory limit.
			}

		}

		/**
		 * Removes old entries for in the database voicemails table
		 * see {@link https://github.com/fusionpbx/fusionpbx-app-maintenance/} FusionPBX Maintenance App
		 * @param settings $settings Settings object
		 * @return void
		 */
		public static function database_maintenance(settings $settings): void {
			//set table name for query
			//$table = self::TABLE;
			$table = 'voicemail_messages';

			//get a database connection
			$database = $settings->database();

			//get a list of domains
			$domains = maintenance::get_domains($database);
			foreach ($domains as $domain_uuid => $domain_name) {
				//get domain settings
				$domain_settings = new settings(['database' => $database, 'domain_uuid' => $domain_uuid]);

				//ensure we have a retention day
				$retention_days = $domain_settings->get('voicemail', maintenance::DATABASE_SUBCATEGORY, '');
				if (!empty($retention_days) && is_numeric($retention_days)) {
					//clear out old records
					$sql = "delete from v_{$table} WHERE to_timestamp(created_epoch) < NOW() - INTERVAL '{$retention_days} days'"
					. " and domain_uuid = '{$domain_uuid}'";
					$database->execute($sql);
					$code = $database->message['code'] ?? 0;
					if ($database->message['code'] == 200) {
						maintenance_service::log_write(self::class, "Successfully removed entries older than $retention_days", $domain_uuid);
					} else {
						$message = $database->message['message'] ?? "An unknown error has occurred";
						maintenance_service::log_write(self::class, "Unable to remove old database records. Error message: $message ($code)", $domain_uuid, maintenance_service::LOG_ERROR);
					}
				}
			}

			//ensure logs are saved
			maintenance_service::log_flush();
		}

		/**
		 * Called by the maintenance system to remove old files
		 * @param settings $settings Settings object
		 */
		public static function filesystem_maintenance(settings $settings): void {
			//get a list of domains
			$domains = maintenance::get_domains($settings->database());

			//loop through domains to handle domains with different defaults
			foreach ($domains as $domain_uuid => $domain_name) {

				//get settings for this domain
				$domain_settings = new settings(['database' => $settings->database(), 'domain_uuid' => $domain_uuid]);

				//get the switch voicemail location
				$voicemail_location = $domain_settings->get('switch', 'voicemail', '/var/lib/freeswitch/storage/voicemail') . '/default';

				//get the filesystem retention days
				$retention_days = $domain_settings->get('voicemail', maintenance::FILESYSTEM_SUBCATEGORY, '');
				if (!empty($retention_days)) {

					//get all wav and mp3 voicemail files
					$mp3_files = glob("$voicemail_location/$domain_name/*/msg_*.mp3");
					$wav_files = glob("$voicemail_location/$domain_name/*/msg_*.wav");
					$mp3_intro_files = glob("$voicemail_location/$domain_name/*/intro_*.mp3");
					$wav_intro_files = glob("$voicemail_location/$domain_name/*/intro_*.wav");
					$domain_voicemail_files = array_merge($mp3_files, $wav_files, $mp3_intro_files, $wav_intro_files);

					//delete individually
					foreach ($domain_voicemail_files as $file) {

						//check modified date on file
						if (maintenance_service::days_since_modified($file) > $retention_days) {

							//date is older so remove
							if (unlink($file)) {
								//successfully deleted
								maintenance_service::log_write(self::class, "Removed $file from voicemails", $domain_uuid);
							} else {
								//failed to delete file
								maintenance_service::log_write(self::class, "Unable to remove $file", $domain_uuid, maintenance_service::LOG_ERROR);
							}
						}
					}
				}
				else {
					//log retention days not valid
					maintenance_service::log_write(self::class, "Retention days not set or not a valid number", $domain_uuid, maintenance_service::LOG_ERROR);
				}
			}

			//ensure logs are saved
			maintenance_service::log_flush();
		}

	}

//example voicemail messages
	//$voicemail = new voicemail;
	//$voicemail->voicemail_uuid = $voicemail_uuid;
	//$voicemail->order_by = $order_by;
	//$voicemail->order = $order;
	//$result = $voicemail->messages();
	//$result_count = count($result);

/*
Array
(
    [user] => 1002
    [extension_uuid] => e163fc03-f180-459b-aa12-7ed87fcb6e2c
    [outbound_caller_id_name] => FusionPBX
    [outbound_caller_id_number] => 12089068227
)
Array
(
    [user] => 1020
    [extension_uuid] => ecfb23df-7c59-4286-891e-2abdc48856ac
    [outbound_caller_id_name] => Mark J Crane
    [outbound_caller_id_number] => 12089068227
)

foreach ($_SESSION['user']['extension'] as $value) {
	if (!empty($value['user'])) {

	}
}
*/

?>
