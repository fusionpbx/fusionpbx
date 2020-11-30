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
		public $order_by;
		public $order;
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

		public function __construct() {

			//assign private variables
				$this->app_name = 'voicemail';
				$this->app_uuid = 'b523c2d2-64cd-46f1-9520-ca4b4098e044';
				$this->permission_prefix = 'voicemail_';
				$this->list_page = 'voicemails.php';
				$this->table = 'voicemails';
				$this->uuid_prefix = 'voicemail_';
				$this->toggle_field = 'voicemail_enabled';
				$this->toggle_values = ['true','false'];

			//set the domain_uuid if not provided
				if (strlen($this->domain_uuid) == 0) {
					$this->domain_uuid = $_SESSION['domain_uuid'];
				}

		}

		public function __destruct() {
			foreach ($this as $key => $value) {
				unset($this->$key);
			}
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
					$database = new database;
					$voicemail_id = $database->select($sql, $parameters, 'column');
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

			//set the voicemail id and voicemail uuid arrays
				if (isset($_SESSION['user']['extension'])) {
					foreach ($_SESSION['user']['extension'] as $index => $row) {
						$voicemail_ids[$index] = is_numeric($row['number_alias']) ? $row['number_alias'] : $row['user'];
					}
				}
				if (isset($_SESSION['user']['voicemail'])) {
					foreach ($_SESSION['user']['voicemail'] as $row) {
						if (strlen($row['voicemail_uuid']) > 0) {
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
						if (is_array($voicemail_uuids)) {
							foreach($voicemail_uuids as $row) {
								if ($voicemail_uuid == $row['voicemail_uuid']) {
									$sql .= "and voicemail_uuid = :voicemail_uuid ";
									$parameters['voicemail_uuid'] = $row['voicemail_uuid'];
									$found = true;
								}
							}
						}
						//id requested is not owned by the user return no results
						if (!$found) {
							$sql .= "and voicemail_uuid is null ";
						}
					}
				}
				else {
					if (is_array($voicemail_ids) && @sizeof($voicemail_ids) != 0) {
						//show only the assigned voicemail ids
						$sql .= "and ";
						if (is_numeric($this->voicemail_id) && in_array($this->voicemail_id, $voicemail_ids)) {
							$sql_where = 'voicemail_id = :voicemail_id ';
							$parameters['voicemail_id'] = $this->voicemail_id;
						}
						else {
							$x = 0;
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
				$database = new database;
				$result = $database->select($sql, $parameters, 'all');
				unset($sql, $parameters);
				return $result;
		}

		public function messages() {
			//get the voicemails
				$voicemails = $this->voicemails();

			//add the voicemail messages to the array
				if (is_array($voicemails)) {
					foreach ($voicemails as &$row) {
						//get the voicemail messages
						$this->voicemail_uuid = $row['voicemail_uuid'];
						$this->voicemail_id = $row['voicemail_id'];
						$result = $this->voicemail_messages();
						$voicemail_count = count($result);
						$row['messages'] = $result;
					}
				}

			//return the array
				return $voicemails;
		}

		public function voicemail_messages() {

			//check if for valid input
				if (!is_numeric($this->voicemail_id) || !is_uuid($this->domain_uuid)) {
					return false;
				}

			//get the message from the database
				$sql = "select * from v_voicemail_messages as m, v_voicemails as v ";
				$sql .= "where m.domain_uuid = :domain_uuid ";
				$sql .= "and m.voicemail_uuid = v.voicemail_uuid ";
				if (is_array($this->voicemail_id) && @sizeof($this->voicemail_id) != 0) {
					$x = 0;
					$sql .= "and ( ";
					foreach ($this->voicemail_id as $row) {
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
					$parameters['voicemail_id'] = $this->voicemail_id;
				}
				if (strlen($this->order_by) == 0) {
					$sql .= "order by v.voicemail_id, m.created_epoch desc ";
				}
				else {
					$sql .= "order by v.voicemail_id, m.".$this->order_by." ".$this->order." ";
				}
				$parameters['domain_uuid'] = $this->domain_uuid;
				$database = new database;
				$result = $database->select($sql, $parameters, 'all');
				unset($sql, $parameters);
			
			//update the array with additional information
				if (is_array($result)) {
					foreach($result as &$row) {
						//set the greeting directory
						$path = $_SESSION['switch']['voicemail']['dir'].'/default/'.$_SESSION['domain_name'].'/'.$row['voicemail_id'];
						if (file_exists($path.'/msg_'.$row['voicemail_message_uuid'].'.wav')) {
							$row['file_path'] = $path.'/msg_'.$row['voicemail_message_uuid'].'.wav';
						}
						if (file_exists($path.'/msg_'.$row['voicemail_message_uuid'].'.mp3')) {
							$row['file_path'] = $path.'/msg_'.$row['voicemail_message_uuid'].'.mp3';
						}
						$row['file_size'] = filesize($row['file_path']);
						$row['file_size_label'] = byte_convert($row['file_size']);
						$row['file_ext'] = substr($row['file_path'], -3);

						$message_minutes = floor($row['message_length'] / 60);
						$message_seconds = $row['message_length'] % 60;
						//use International System of Units (SI) - Source: https://en.wikipedia.org/wiki/International_System_of_Units
						$row['message_length_label'] = ($message_minutes > 0 ? $message_minutes.' min' : null).($message_seconds > 0 ? ' '.$message_seconds.' s' : null);
						$row['created_date'] = date("j M Y g:i a",$row['created_epoch']);
					}
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
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}

						//get necessary voicemail details
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->uuid_prefix."uuid as uuid, voicemail_id from v_".$this->table." ";
								$sql .= "where ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
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
										$this->domain_uuid = $_SESSION['domain_uuid'];
										$this->message_waiting();

									//build the delete array
										$array[$this->table][$x]['voicemail_uuid'] = $voicemail_uuid;
										$array[$this->table][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
										$array['voicemail_options'][$x]['voicemail_uuid'] = $voicemail_uuid;
										$array['voicemail_options'][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
										$array['voicemail_messages'][$x]['voicemail_uuid'] = $voicemail_uuid;
										$array['voicemail_messages'][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
										$array['voicemail_destinations'][$x]['voicemail_uuid'] = $voicemail_uuid;
										$array['voicemail_destinations'][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
										if (is_numeric($voicemail_id)) {
											$array['voicemail_greetings'][$x]['voicemail_id'] = $voicemail_id;
											$array['voicemail_greetings'][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
										}
										$x++;
								}
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {

								//grant temporary permissions
									$p = new permissions;
									$p->add('voicemail_delete', 'temp');
									$p->add('voicemail_option_delete', 'temp');
									$p->add('voicemail_message_delete', 'temp');
									$p->add('voicemail_destination_delete', 'temp');
									$p->add('voicemail_greeting_delete', 'temp');

								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
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
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									//build the delete array
										$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];
										$array[$this->table][$x]['voicemail_uuid'] = $this->voicemail_uuid;
										$array[$this->table][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
								}
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {
								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
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
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									//build the delete array
										$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];
										$array[$this->table][$x]['voicemail_uuid'] = $this->voicemail_uuid;
										$array[$this->table][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
								}
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {
								//grant temporary permissions
									$p = new permissions;
									$p->add('voicemail_destination_delete', 'temp');

								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
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
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}

						//get necessary voicemail details
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->uuid_prefix."uuid as uuid, voicemail_id, ".$this->toggle_field." as toggle from v_".$this->table." ";
								$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
								$sql .= "and ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
								$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
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
										$this->domain_uuid = $_SESSION['domain_uuid'];
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
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->save($array);
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
				$database = new database;
				return $database->select($sql, $parameters, 'column');
				unset($sql, $parameters);

		}

		public function message_waiting() {
			//get the voicemail id
				$this->get_voicemail_id();

			//send the message waiting status
				$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
				if ($fp) {
					$switch_cmd .= "luarun app.lua voicemail mwi ".$this->voicemail_id."@".$_SESSION['domain_name'];
					$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
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
				$p = new permissions;
				$p->add('voicemail_message_delete', 'temp');

			//execute delete
				$database = new database;
				$database->app_name = 'voicemails';
				$database->app_uuid = 'b523c2d2-64cd-46f1-9520-ca4b4098e044';
				$database->delete($array);
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
				$database = new database;
				$new_status = $database->select($sql, $parameters, 'column') != 'saved' ? 'saved' : null;
				unset($sql, $parameters);

			//build message status update array
				$array['voicemail_messages'][0]['voicemail_message_uuid'] = $this->voicemail_message_uuid;
				$array['voicemail_messages'][0]['message_status'] = $new_status;

			//grant temporary permissions
				$p = new permissions;
				$p->add('voicemail_message_edit', 'temp');

			//execute update
				$database = new database;
				$database->app_name = 'voicemails';
				$database->app_uuid = 'b523c2d2-64cd-46f1-9520-ca4b4098e044';
				$database->save($array);
				unset($array);

			//revoke temporary permissions
				$p->delete('voicemail_message_edit', 'temp');

			//check the message waiting status
				$this->message_waiting();
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
				$p = new permissions;
				$p->add('voicemail_message_edit', 'temp');

			//execute update
				$database = new database;
				$database->app_name = 'voicemails';
				$database->app_uuid = 'b523c2d2-64cd-46f1-9520-ca4b4098e044';
				$database->save($array);
				unset($array);

			//revoke temporary permissions
				$p->delete('voicemail_message_edit', 'temp');

			//check the message waiting status
				$this->message_waiting();
		}

		public function message_download() {

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
				$path = $_SESSION['switch']['voicemail']['dir'].'/default/'.$_SESSION['domain_name'].'/'.$this->voicemail_id;

			//prepare base64 content from db, if enabled
				if ($_SESSION['voicemail']['storage_type']['text'] == 'base64') {
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
					$database = new database;
					$message_base64 = $database->select($sql, $parameters, 'column');
					if ($message_base64 != '') {
						$message_decoded = base64_decode($message_base64);
						file_put_contents($path.'/msg_'.$this->voicemail_message_uuid.'.ext', $message_decoded);
						$finfo = finfo_open(FILEINFO_MIME_TYPE); //determine mime type (requires PHP >= 5.3.0, must be manually enabled on Windows)
						$file_mime = finfo_file($finfo, $path.'/msg_'.$this->voicemail_message_uuid.'.ext');
						finfo_close($finfo);
						switch ($file_mime) {
							case 'audio/x-wav':
							case 'audio/wav':
								$file_ext = 'wav';
								break;
							case 'audio/mpeg':
							case 'audio/mp3':
								$file_ext = 'mp3';
								break;
						}
						rename($path.'/msg_'.$this->voicemail_message_uuid.'.ext', $path.'/msg_'.$this->voicemail_message_uuid.'.'.$file_ext);
					}
					unset($sql, $parameters, $message_base64, $message_decoded);
				}

			//prepare and stream the file
				if (file_exists($path.'/msg_'.$this->voicemail_message_uuid.'.wav')) {
					$file_path = $path.'/msg_'.$this->voicemail_message_uuid.'.wav';
				}
				if (file_exists($path.'/msg_'.$this->voicemail_message_uuid.'.mp3')) {
					$file_path = $path.'/msg_'.$this->voicemail_message_uuid.'.mp3';
				}
				if ($file_path != '') {
					//content-range
					if (isset($_SERVER['HTTP_RANGE']) && $this->type != 'bin')  {
						$this->range_download($file_path);
					}

					$fd = fopen($file_path, "rb");
					if ($this->type == 'bin') {
						header("Content-Type: application/force-download");
						header("Content-Type: application/octet-stream");
						header("Content-Type: application/download");
						header("Content-Description: File Transfer");
						$file_ext = pathinfo($file_path, PATHINFO_EXTENSION);
						switch ($file_ext) {
							case "wav" : header('Content-Disposition: attachment; filename="msg_'.$this->voicemail_message_uuid.'.wav"'); break;
							case "mp3" : header('Content-Disposition: attachment; filename="msg_'.$this->voicemail_message_uuid.'.mp3"'); break;
							case "ogg" : header('Content-Disposition: attachment; filename="msg_'.$this->voicemail_message_uuid.'.ogg"'); break;
						}
					}
					else {
						$file_ext = pathinfo($file_path, PATHINFO_EXTENSION);
						switch ($file_ext) {
							case "wav" : header("Content-Type: audio/x-wav"); break;
							case "mp3" : header("Content-Type: audio/mpeg"); break;
							case "ogg" : header("Content-Type: audio/ogg"); break;
						}
					}
					header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
					header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // date in the past
					if ($this->type == 'bin') {
						header("Content-Length: ".filesize($file_path));
					}
					ob_end_clean();
					fpassthru($fd);
				}

			//if base64, remove temp file
				if ($_SESSION['voicemail']['storage_type']['text'] == 'base64') {
					@unlink($path.'/msg_'.$this->voicemail_message_uuid.'.'.$file_ext);
				}

		}

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
				if ($range0 == '-') {
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


	}

//example voicemail messages
	//require_once "app/voicemails/resources/classes/voicemail.php";
	//$voicemail = new voicemail;
	//$voicemail->db = $db;
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
	if (strlen($value['user']) > 0) {

	}
}
*/

?>