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
 Portions created by the Initial Developer are Copyright (C) 2008-2013
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
 Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//define the conference center class
	class conference_center {
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
				$sql .= "where r.domain_uuid = '".$this->domain_uuid."' ";
				$sql .= "and r.meeting_uuid = p.meeting_uuid ";
				if ($not_admin) {
					$sql .= "and r.meeting_uuid = u.meeting_uuid ";
					$sql .= "and u.user_uuid = '".$_SESSION["user_uuid"]."' ";
				}
				if (isset($this->search)) {
					$sql .= "and r.meeting_uuid = '".$this->meeting_uuid."' ";
				}

				if (isset($this->created_by)) {
					$sql .= "and created_by = '".$this->created_by."' ";
				}

				$prep_statement = $this->db->prepare(check_sql($sql));
				if ($prep_statement) {
					$prep_statement->execute();
					$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
					if ($row['num_rows'] > 0) {
						return $row['num_rows'];
					}
					else {
						return 0;
					}
				}
		}

		public function rooms() {
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
				$sql .= "where r.domain_uuid = '".$this->domain_uuid."' ";
				$sql .= "and r.meeting_uuid = p.meeting_uuid ";
				if ($not_admin) {
					$sql .= "and r.meeting_uuid = u.meeting_uuid ";
					$sql .= "and u.user_uuid = '".$_SESSION["user_uuid"]."' ";
				}
				//if (is_numeric($this->search)) {
				//	$sql .= "and p.member_pin = '".$this->search."' ";
				//}
				if (isset($this->search)) {
					$sql .= "and r.meeting_uuid = '".$this->meeting_uuid."' ";
				}
				if (isset($this->created_by)) {
					$sql .= "and r.created_by = '".$this->created_by."' ";
				}
				if (strlen($this->order_by) == 0) {
					$sql .= "order by r.description, r.meeting_uuid asc ";
				} else {
					$sql .= "order by $this->order_by $this->order ";
				}
				$sql .= "limit $this->rows_per_page offset $this->offset ";
				$prep_statement = $this->db->prepare(check_sql($sql));
				if ($prep_statement) {
					$prep_statement->execute();
					$rows = $prep_statement->fetchAll(PDO::FETCH_NAMED);
					$this->count = count($rows);
					if ($this->count > 0) {
						$x = 0;
						foreach($rows as $row) {
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
						unset($rows);
					}
					unset ($prep_statement, $sql);
				}
				return $result;
		}
	}

//example conference center
	/*
	require_once "app/conference_centers/resources/classes/conference_center.php";
	$conference_center = new conference_center;
	$conference_center->db = $db;
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
