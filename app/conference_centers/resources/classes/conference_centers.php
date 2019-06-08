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
 Portions created by the Initial Developer are Copyright (C) 2008-2018
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
 Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//define the conference center class
	class conference_centers {

		public $db;
		public $domain_uuid;
		public $meeting_uuid;
		public $order_by;
		public $order;
		public $rows_per_page;
		public $offset;
		private $fields;
		public $search;
		public $count;
		public $created_by;

		/**
		 * Called when the object is created
		 */
		public function __construct() {
			//connect to the database if not connected
			if (!$this->db) {
				require_once "resources/classes/database.php";
				$database = new database;
				$database->connect();
				$this->db = $database->db;
			}
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
				$sql = "select count(*) as num_rows from v_conference_rooms as r, v_meetings as p ";
				if ($not_admin) {
					$sql .= "v_meeting_users as u, ";
				}
				$sql .= "where r.domain_uuid = :domain_uuid ";
				$sql .= "and r.meeting_uuid = p.meeting_uuid ";
				if ($not_admin) {
					$sql .= "and r.meeting_uuid = u.meeting_uuid ";
					$sql .= "and u.user_uuid = :user_uuid ";
					$parameters['user_uuid'] = $user_uuid;
				}
				if (isset($this->meeting_uuid)) {
					$sql .= "and r.meeting_uuid = :meeting_uuid ";
					$parameters['meeting_uuid'] = $this->meeting_uuid;
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
						break;
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
				$fields = "r.domain_uuid, r.conference_room_uuid, r.conference_center_uuid, r.meeting_uuid, r.conference_room_name, max_members, ";
				$fields .= "wait_mod, announce, mute, sounds, created, created_by, r.enabled, r.description, record, ";
				$fields .= "profile, moderator_pin, participant_pin";
				if ($not_admin) {
					$fields .= ", meeting_user_uuid, user_uuid";
				}
				$sql = "select ".$fields." from v_conference_rooms as r, v_meetings as p ";
				if ($not_admin) {
					$sql .= ", v_meeting_users as u ";
				}
				$sql .= "where r.domain_uuid = :domain_uuid ";
				$sql .= "and r.meeting_uuid = p.meeting_uuid ";
				if ($not_admin) {
					$sql .= "and r.meeting_uuid = u.meeting_uuid ";
					$sql .= "and u.user_uuid = :user_uuid ";
					$parameters['user_uuid'] = $_SESSION["user_uuid"];
				}
				//if (is_numeric($this->search)) {
				//	$sql .= "and p.member_pin = '".$this->search."' ";
				//	$parameters['domain_uuid'] = $this->domain_uuid;
				//}
				if (isset($this->search)) {
					$sql .= "and r.meeting_uuid = :meeting_uuid ";
					$parameters['meeting_uuid'] = $this->meeting_uuid;
				}
				if (isset($this->created_by)) {
					$sql .= "and r.created_by = :created_by ";
					$parameters['created_by'] = $this->created_by;
				}
				if (strlen($this->order_by) == 0) {
					$sql .= "order by r.description, r.meeting_uuid asc ";
				} else {
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
							$result[$x]["meeting_uuid"] = $row["meeting_uuid"];
							$result[$x]["conference_room_name"] = $row["conference_room_name"];
							$result[$x]["max_members"] = $row["max_members"];
							$result[$x]["wait_mod"] = $row["wait_mod"];
							$result[$x]["announce"] = $row["announce"];
							$result[$x]["mute"] = $row["mute"];
							$result[$x]["record"] = $row["record"];
							$result[$x]["sounds"] = $row["sounds"];
							$result[$x]["profile"] = $row["profile"];
							$result[$x]["meeting_user_uuid"] = $row["meeting_user_uuid"];
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
					unset($conference_rooms);
				}
				unset ($parameters, $sql);
				return $result;
		}

		/**
		 * download the recordings
		 */
		public function download() {
			if (permission_exists('call_recording_play') || permission_exists('call_recording_download')) {

				//cache limiter
					session_cache_limiter('public');

				//get call recording from database
					if (is_uuid($_GET['id'])) {
						$conference_session_uuid = check_str($_GET['id']);
					}
					if ($conference_session_uuid != '') {
						$sql = "select recording from v_conference_sessions ";
						$sql .= "where conference_session_uuid = :conference_session_uuid ";
						//$sql .= "and domain_uuid = '".$domain_uuid."' \n";
						$parameters['conference_session_uuid'] = $conference_session_uuid;
						$database = new database;
						$conference_sessions = $database->select($sql, $parameters, 'all');
						if (is_array($conference_sessions)) {
							foreach($conference_sessions as &$row) {
								$recording = $row['recording'];
								break;
							}
						}
						unset ($sql, $prep_statement, $conference_sessions);
					}

				//set the path for the directory
					$default_path = $_SESSION['switch']['call_recordings']['dir']."/".$_SESSION['domain_name'];
					
				//get the file path and name
					$record_path = dirname($recording);
					$record_name = basename($recording);

				//download the file
					if (file_exists($record_path . '/' . $record_name . '.wav')) {
						$record_name = $record_name . '.wav';
					}
					else {
						if (file_exists($record_path . '/' . $record_name . '.mp3')) {
							$record_name = $record_name . '.mp3';
						}
					}

				//download the file
					if (file_exists($record_path . '/' . $record_name)) {
						//content-range
						//if (isset($_SERVER['HTTP_RANGE']))  {
						//	range_download($full_recording_path);
						//}
						ob_clean();
						$fd = fopen($record_path . '/' . $record_name, "rb");
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
						// header("Content-Length: " . filesize($full_recording_path));
						ob_clean();
						fpassthru($fd);
					}

				//if base64, remove temp recording file
					//if ($_SESSION['conference']['storage_type']['text'] == 'base64' && $row['conference_recording_base64'] != '') {
					//	@unlink($record_path . '/' . $record_name);
					//}
			}
		} //end download method

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
