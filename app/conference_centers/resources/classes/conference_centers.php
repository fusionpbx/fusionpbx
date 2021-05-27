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
Portions created by the Initial Developer are Copyright (C) 2008-2021
the Initial Developer. All Rights Reserved.

Contributor(s):
Mark J Crane <markjcrane@fusionpbx.com>
Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//define the conference centers class
if (!class_exists('conference_centers')) {
	class conference_centers {

		/**
		 * declare public variables
		 */
		public $domain_uuid;
		public $conference_room_uuid;
		public $order_by;
		public $order;
		public $rows_per_page;
		public $offset;
		public $search;
		public $count;
		public $created_by;

		public $toggle_field;

		/**
		 * declare private variables
		 */
		private $fields;

		private $app_name;
		private $app_uuid;
		private $permission_prefix;
		private $list_page;
		private $table;
		private $uuid_prefix;
		private $toggle_values;

		/**
		 * Called when the object is created
		 */
		public function __construct() {

			//assign private variables
				$this->app_name = 'conference_centers';
				$this->app_uuid = '8d083f5a-f726-42a8-9ffa-8d28f848f10e';

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
		 * count the conference rooms
		 */
		public function room_count() {
			//get the room count
				$not_admin = 1;
				if (permission_exists("conference_room_view_all")) {
					$not_admin = 0;
				}
				$sql = "select count(*) from v_conference_rooms as r ";
				if ($not_admin) {
					$sql .= ", v_conference_room_users as u ";
				}
				$sql .= "where r.domain_uuid = :domain_uuid ";
				if ($not_admin) {
					$sql .= "and r.conference_room_uuid = u.conference_room_uuid ";
					$sql .= "and u.user_uuid = :user_uuid ";
					$parameters['user_uuid'] = $user_uuid;
				}
				if (isset($this->conference_room_uuid)) {
					$sql .= "and r.conference_room_uuid = :conference_room_uuid ";
					$parameters['conference_room_uuid'] = $this->conference_room_uuid;
				}

				if (isset($this->created_by)) {
					$sql .= "and created_by = :created_by ";
					$parameters['created_by'] = $this->created_by;
				}
				$parameters['domain_uuid'] = $this->domain_uuid;
				$database = new database;
				return $database->select($sql, $parameters, 'column');
		}

		/**
		 * get the list of conference rooms
		 */
		public function rooms() {
			//get variables used to control the order
				$order_by = $this->order_by;
				$order = $this->order;

			//validate order by
				if (strlen($order_by) > 0) {
					$order_by = preg_replace('#[^a-zA-Z0-9_\-]#', '', $order_by);
				}

			//validate the order
				switch ($order) {
					case 'asc':
					case 'desc':
						break;
					default:
						$order = '';
				}

			//get the list of rooms
				$not_admin = 1;
				if (permission_exists("conference_room_view_all")) {
					$not_admin = 0;
				}

				$sql = "select ";
				$sql .= "r.domain_uuid, r.conference_room_uuid, r.conference_center_uuid, r.conference_room_name, r.max_members, ";
				$sql .= "wait_mod, announce_name, announce_count, announce_recording, mute, sounds, created, created_by, r.enabled, r.description, record, ";
				$sql .= "profile, r.moderator_pin, r.participant_pin ";
				if ($not_admin) {
					$sql .= ", u.conference_room_user_uuid, u.user_uuid ";
				}
				$sql .= "from v_conference_rooms as r ";
				if ($not_admin) {
					$sql .= ", v_conference_room_users as u ";
				}
				$sql .= "where r.domain_uuid = :domain_uuid ";
				if ($not_admin) {
					$sql .= "and r.conference_room_uuid = u.conference_room_uuid ";
					$sql .= "and u.user_uuid = :user_uuid ";
					$parameters['user_uuid'] = $_SESSION["user_uuid"];
				}
				//if (is_numeric($this->search)) {
				//	$sql .= "and p.member_pin = '".$this->search."' ";
				//	$parameters['domain_uuid'] = $this->domain_uuid;
				//}
				if (isset($this->created_by)) {
					$sql .= "and r.created_by = :created_by ";
					$parameters['created_by'] = $this->created_by;
				}
				if (strlen($this->order_by) == 0) {
					$sql .= "order by r.description, r.conference_room_uuid asc ";
				}
				else {
					$sql .= "order by $order_by $order ";
				}
				$sql .= "limit :rows_per_page offset :offset ";
				$parameters['domain_uuid'] = $this->domain_uuid;
				$parameters['rows_per_page'] = $this->rows_per_page;
				$parameters['offset'] = $this->offset;
				$database = new database;
				$conference_rooms = $database->select($sql, $parameters, 'all');

				if (is_array($conference_rooms)) {
					$x = 0;
					foreach($conference_rooms as $row) {
						//increment the array index
							if (isset($previous) && $row["conference_room_uuid"] != $previous) { $x++; }
						//build the array
							$result[$x]["domain_uuid"] = $row["domain_uuid"];
							$result[$x]["conference_room_uuid"] = $row["conference_room_uuid"];
							$result[$x]["conference_center_uuid"] = $row["conference_center_uuid"];
							//$result[$x]["meeting_uuid"] = $row["meeting_uuid"];
							$result[$x]["conference_room_name"] = $row["conference_room_name"];
							$result[$x]["max_members"] = $row["max_members"];
							$result[$x]["wait_mod"] = $row["wait_mod"];
							$result[$x]["announce_name"] = $row["announce_name"];
							$result[$x]["announce_count"] = $row["announce_count"];
							$result[$x]["announce_recording"] = $row["announce_recording"];
							$result[$x]["mute"] = $row["mute"];
							$result[$x]["record"] = $row["record"];
							$result[$x]["sounds"] = $row["sounds"];
							$result[$x]["profile"] = $row["profile"];
							$result[$x]["conference_room_user_uuid"] = $row["conference_room_user_uuid"];
							$result[$x]["user_uuid"] = $row["user_uuid"];
							$result[$x]["moderator_pin"] = $row["moderator_pin"];
							$result[$x]["participant_pin"] = $row["participant_pin"];
							$result[$x]["created"] = $row["created"];
							$result[$x]["created_by"] = $row["created_by"];
							$result[$x]["enabled"] = $row["enabled"];
							$result[$x]["description"] = $row["description"];
						//set the previous uuid
							$previous = $row["conference_room_uuid"];
					}
				}
				unset($sql, $parameters, $conference_rooms);
				return $result;
		}

		/**
		 * download the recordings
		 */
		public function download() {
			if (permission_exists('conference_session_play') || permission_exists('call_recording_play') || permission_exists('call_recording_download')) {

				//cache limiter
					session_cache_limiter('public');

				//get call recording from database
					if (is_uuid($_GET['id'])) {
						$conference_session_uuid = $_GET['id'];
						$sql = "select recording from v_conference_sessions ";
						$sql .= "where conference_session_uuid = :conference_session_uuid ";
						//$sql .= "and domain_uuid = :domain_uuid ";
						$parameters['conference_session_uuid'] = $conference_session_uuid;
						//$parameters['domain_uuid'] = $domain_uuid;
						$database = new database;
						$conference_sessions = $database->select($sql, $parameters, 'all');
						if (is_array($conference_sessions)) {
							foreach ($conference_sessions as &$row) {
								$recording = $row['recording'];
								break;
							}
						}
						unset($sql, $parameters, $conference_sessions);
					}

				//set the path for the directory
					$default_path = $_SESSION['switch']['call_recordings']['dir']."/".$_SESSION['domain_name'];
					
				//get the file path and name
					$record_path = dirname($recording);
					$record_name = basename($recording);

				//download the file
					if (file_exists($record_path.'/'.$record_name.'.wav')) {
						$record_name = $record_name.'.wav';
					}
					else {
						if (file_exists($record_path.'/'.$record_name.'.mp3')) {
							$record_name = $record_name.'.mp3';
						}
					}

				//download the file
					if (file_exists($record_path.'/'.$record_name)) {
						//content-range
						//if (isset($_SERVER['HTTP_RANGE']))  {
						//	range_download($full_recording_path);
						//}
						ob_clean();
						$fd = fopen($record_path.'/'.$record_name, "rb");
						if ($_GET['t'] == "bin") {
							header("Content-Type: application/force-download");
							header("Content-Type: application/octet-stream");
							header("Content-Type: application/download");
							header("Content-Description: File Transfer");
						}
						else {
							$file_ext = substr($record_name, -3);
							if ($file_ext == "wav") {
								header("Content-Type: audio/x-wav");
							}
							if ($file_ext == "mp3") {
								header("Content-Type: audio/mpeg");
							}
						}
						header('Content-Disposition: attachment; filename="'.$record_name.'"');
						header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
						header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
						// header("Content-Length: ".filesize($full_recording_path));
						ob_clean();
						fpassthru($fd);
					}

				//if base64, remove temp recording file
					//if ($_SESSION['conference']['storage_type']['text'] == 'base64' && $row['conference_recording_base64'] != '') {
					//	@unlink($record_path.'/'.$record_name);
					//}
			}
		} //end download method

		/**
		 * delete records
		 */
		public function delete_conference_centers($records) {

			//assign private variables
				$this->permission_prefix = 'conference_center_';
				$this->list_page = 'conference_centers.php';
				$this->table = 'conference_centers';
				$this->uuid_prefix = 'conference_center_';

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

						//build the delete array
							foreach ($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {

									//get the dialplan uuid
										$sql = "select dialplan_uuid from v_conference_centers ";
										$sql .= "where domain_uuid = :domain_uuid ";
										$sql .= "and conference_center_uuid = :conference_center_uuid ";
										$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
										$parameters['conference_center_uuid'] = $record['uuid'];
										$database = new database;
										$dialplan_uuid = $database->select($sql, $parameters, 'column');
										unset($sql, $parameters);

									//create array
										$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];
										$array[$this->table][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
										$array['dialplan_details'][$x]['dialplan_uuid'] = $dialplan_uuid;
										$array['dialplan_details'][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
										$array['dialplans'][$x]['dialplan_uuid'] = $dialplan_uuid;
										$array['dialplans'][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
								}
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {

								//grant temporary permissions
									$p = new permissions;
									$p->add('dialplan_detail_delete', 'temp');
									$p->add('dialplan_delete', 'temp');

								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
									unset($array);

								//revoke temporary permissions
									$p->delete('dialplan_detail_delete', 'temp');
									$p->delete('dialplan_delete', 'temp');

								//clear the cache
									$cache = new cache;
									$cache->delete("dialplan:".$_SESSION["domain_name"]);

								//clear the destinations session array
									if (isset($_SESSION['destinations']['array'])) {
										unset($_SESSION['destinations']['array']);
									}

								//apply settings reminder
									$_SESSION["reload_xml"] = true;

								//set message
									message::add($text['message-delete']);
							}
							unset($records);
					}
			}
		}

		public function delete_conference_rooms($records) {

			//assign private variables
				$this->permission_prefix = 'conference_room_';
				$this->list_page = 'conference_rooms.php';
				$this->table = 'conference_rooms';
				$this->uuid_prefix = 'conference_room_';

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

						//build the delete array
							foreach ($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {

									//create array
										$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];
										$array[$this->table][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
										$array['conference_room_users'][$x]['conference_room_uuid'] = $record['uuid'];
										$array['conference_room_users'][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
								}
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {

								//grant temporary permissions
									$p = new permissions;
									$p->add('conference_room_user_delete', 'temp');
									$p->add('conference_room_delete', 'temp');

								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
									unset($array);

								//revoke temporary permissions
									$p->delete('conference_room_user_delete', 'temp');
									$p->delete('conference_room_delete', 'temp');

								//set message
									message::add($text['message-delete']);
							}
							unset($records);
					}
			}
		}

		public function delete_conference_sessions($records) {

			//assign private variables
				$this->permission_prefix = 'conference_session_';
				$this->list_page = 'conference_sessions.php?id='.$this->conference_room_uuid;
				$this->table = 'conference_sessions';
				$this->uuid_prefix = 'conference_session_';

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

						//build the delete array
							foreach ($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {

									//create array
										$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];
										$array[$this->table][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
										$array['conference_session_details'][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];
										$array['conference_session_details'][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
								}
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {

								//grant temporary permissions
									$p = new permissions;
									$p->add('conference_session_detail_delete', 'temp');
									$p->add('conference_user_delete', 'temp');

								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
									unset($array);

								//revoke temporary permissions
									$p->delete('conference_session_detail_delete', 'temp');
									$p->delete('conference_user_delete', 'temp');

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
		public function toggle_conference_centers($records) {

			//assign private variables
				$this->permission_prefix = 'conference_center_';
				$this->list_page = 'conference_centers.php';
				$this->table = 'conference_centers';
				$this->uuid_prefix = 'conference_center_';
				$this->toggle_field = 'conference_center_enabled';
				$this->toggle_values = ['true','false'];

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
								$sql = "select ".$this->uuid_prefix."uuid as uuid, ".$this->toggle_field." as toggle, dialplan_uuid from v_".$this->table." ";
								$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
								$sql .= "and ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
								$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										$conference_centers[$row['uuid']]['state'] = $row['toggle'];
										$conference_centers[$row['uuid']]['dialplan_uuid'] = $row['dialplan_uuid'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//build update array
							$x = 0;
							foreach($conference_centers as $uuid => $conference_center) {
								$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $uuid;
								$array[$this->table][$x][$this->toggle_field] = $conference_center['state'] == $this->toggle_values[0] ? $this->toggle_values[1] : $this->toggle_values[0];
								$array['dialplans'][$x]['dialplan_uuid'] = $conference_center['dialplan_uuid'];
								$array['dialplans'][$x]['dialplan_enabled'] = $conference_center['state'] == $this->toggle_values[0] ? $this->toggle_values[1] : $this->toggle_values[0];
								$x++;
							}

						//save the changes
							if (is_array($array) && @sizeof($array) != 0) {

								//grant temporary permissions
									$p = new permissions;
									$p->add("dialplan_edit", "temp");

								//save the array
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->save($array);
									unset($array);

								//revoke temporary permissions
									$p->delete("dialplan_edit", "temp");

								//apply settings reminder
									$_SESSION["reload_xml"] = true;

								//clear the cache
									$cache = new cache;
									$cache->delete("dialplan:".$_SESSION["domain_name"]);

								//clear the destinations session array
									if (isset($_SESSION['destinations']['array'])) {
										unset($_SESSION['destinations']['array']);
									}

								//set message
									message::add($text['message-toggle']);

							}
							unset($records, $conference_centers, $conference_center);
					}

			}
		}

		public function toggle_conference_rooms($records) {

			//assign private variables
				$this->permission_prefix = 'conference_room_';
				$this->list_page = 'conference_rooms.php';
				$this->table = 'conference_rooms';
				$this->uuid_prefix = 'conference_room_';
				$this->toggle_values = ['true','false'];

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

						//validate submitted toggle field
							if (!in_array($this->toggle_field, ['record','wait_mod','announce_name','announce_count','announce_recording','mute','sounds','enabled'])) {
								header('Location: '.$this->list_page);
								exit;
							}

						//get current toggle state
							foreach($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[$x] = "'".$record['uuid']."'";
									if (($this->toggle_field == 'record' || $this->toggle_field == 'enabled') && is_uuid($record['meeting_uuid'])) {
										$meeting_uuid[$record['uuid']] = $record['meeting_uuid'];
									}
								}
							}
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->uuid_prefix."uuid as uuid, ".$this->toggle_field." as toggle from v_".$this->table." ";
								$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
								$sql .= "and ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
								$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										$states[$row['uuid']] = $row['toggle'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//build update array
							$x = 0;
							foreach ($states as $uuid => $state) {
								$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $uuid;
								$array[$this->table][$x][$this->toggle_field] = $state == $this->toggle_values[0] ? $this->toggle_values[1] : $this->toggle_values[0];

/*
								//if toggling to true, start recording
									if ($this->toggle_field == 'record' && is_uuid($meeting_uuid[$uuid]) && $state == $this->toggle_values[1]) {
										//prepare the values and commands
											$default_language = 'en';
											$default_dialect = 'us';
											$default_voice = 'callie';
// 											$recording_dir = $_SESSION['switch']['recordings']['dir'].'/'.$_SESSION['domain_name'].'/archive/'.date("Y").'/'.date("M").'/'.date("d");
//											$switch_cmd_record = "conference ".$meeting_uuid[$uuid]."@".$_SESSION['domain_name']." record ".$recording_dir.'/'.$meeting_uuid[$uuid].'.wav';
											$switch_cmd_notice = "conference ".$meeting_uuid[$uuid]."@".$_SESSION['domain_name']." play ".$_SESSION['switch']['sounds']['dir']."/".$default_language."/".$default_dialect."/".$default_voice."/ivr/ivr-recording_started.wav";
										//execute api commands
// 											if (!file_exists($recording_dir.'/'.$meeting_uuid[$uuid].'.wav')) {
												$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
												if ($fp) {
//													$switch_result = event_socket_request($fp, 'api '.$switch_cmd_record);
													$switch_result = event_socket_request($fp, 'api '.$switch_cmd_notice);
												}
// 											}
									}
*/
								$x++;
							}

						//save the changes
							if (is_array($array) && @sizeof($array) != 0) {

								//save the array
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->save($array);
									unset($array);

								//set message
									message::add($text['message-toggle']);

							}
							unset($records, $states, $state);
					}

			}
		}

		/**
		 * copy records
		 */
		/*
		public function copy_conference_centers($records) {

			//assign private variables
				$this->permission_prefix = 'conference_center_';
				$this->list_page = 'conference_centers.php';
				$this->table = 'conference_centers';
				$this->uuid_prefix = 'conference_center_';

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
								$sql = "select * from v_".$this->table." ";
								$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
								$sql .= "and ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
								$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $x => $row) {

										//copy data
											$array[$this->table][$x] = $row;

										//overwrite
											$array[$this->table][$x][$this->uuid_prefix.'uuid'] = uuid();
											$array[$this->table][$x]['_description'] = trim($row['_description'].' ('.$text['label-copy'].')');

									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//save the changes and set the message
							if (is_array($array) && @sizeof($array) != 0) {

								//save the array
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->save($array);
									unset($array);

								//set message
									message::add($text['message-copy']);

							}
							unset($records);
					}

			}
		}
		*/


	} //class
}

//example conference center
	/*
	$conference_center = new conference_centers;
	$conference_center->domain_uuid = $_SESSION['domain_uuid'];
	$conference_center->rows_per_page = 150;
	$conference_center->offset = 0;
	$conference_center->created_by = uuid;
	$conference_center->order_by = $order_by;
	$conference_center->order = $order;
	$result = $conference_center->rooms();
	print_r($result);
	*/

?>
