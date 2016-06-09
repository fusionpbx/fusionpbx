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
	Portions created by the Initial Developer are Copyright (C) 2008-2014
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('conference_room_add') || permission_exists('conference_room_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$conference_room_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (count($_POST) > 0) {
		$conference_center_uuid = check_str($_POST["conference_center_uuid"]);
		$meeting_uuid = check_str($_POST["meeting_uuid"]);
		$conference_room_name = check_str($_POST['conference_room_name']);
		$moderator_pin = check_str($_POST["moderator_pin"]);
		$participant_pin = check_str($_POST["participant_pin"]);
		$profile = check_str($_POST["profile"]);
		$record = check_str($_POST["record"]);
		$user_uuid = check_str($_POST["user_uuid"]);
		$max_members = check_str($_POST["max_members"]);
		$start_datetime = check_str($_POST["start_datetime"]);
		$stop_datetime = check_str($_POST["stop_datetime"]);
		$wait_mod = check_str($_POST["wait_mod"]);
		$announce = check_str($_POST["announce"]);
		$sounds = check_str($_POST["sounds"]);
		$mute = check_str($_POST["mute"]);
		$created = check_str($_POST["created"]);
		$created_by = check_str($_POST["created_by"]);
		$enabled = check_str($_POST["enabled"]);
		$description = check_str($_POST["description"]);

		//remove any pin number formatting
		$moderator_pin = preg_replace('{\D}', '', $moderator_pin);
		$participant_pin = preg_replace('{\D}', '', $participant_pin);
	}

//get the conference centers array and set a default conference center
	$sql = "select * from v_conference_centers ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "order by conference_center_name asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$conference_centers = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
	if (strlen($conference_center_uuid) == 0) {
		$conference_center_uuid = $conference_centers[0]["conference_center_uuid"];
	}

//define fucntion get_meeting_pin - used to find a unique pin number
	function get_meeting_pin($length, $meeting_uuid) {
		global $db;
		$pin = generate_password($length,1);
		$sql = "select count(*) as num_rows from v_meetings ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		//$sql .= "and meeting_uuid <> '".$meeting_uuid."' ";
		$sql .= "and (moderator_pin = '".$pin."' or participant_pin = '".$pin."') ";
		$prep_statement = $db->prepare(check_sql($sql));
		if ($prep_statement) {
			$prep_statement->execute();
			$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
			if ($row['num_rows'] == 0) {
				return $pin;
			}
			else {
				get_meeting_pin($length, $uuid);
			}
		}
	}

//record announcment
	if ($record == "true") {
		//prepare the values
			$default_language = 'en';
			$default_dialect = 'us';
			$default_voice = 'callie';
			$switch_cmd = "conference ".$meeting_uuid."-".$_SESSION['domain_name']." play ".$_SESSION['switch']['sounds']['dir']."/".$default_language."/".$default_dialect."/".$default_voice."/ivr/ivr-recording_started.wav";
		//connect to event socket
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
			if ($fp) {
				$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
			}
	}

//generate the pins
	$sql = "select conference_center_pin_length from v_conference_centers ";
	$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
	if (strlen($conference_center_uuid) > 0) {
		$sql .= "and conference_center_uuid = '".$conference_center_uuid."' ";
	}
	$prep_statement = $db->prepare(check_sql($sql));
	if ($prep_statement) {
		$prep_statement->execute();
		$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
		$pin_length = $row['conference_center_pin_length'];
	}
	if (strlen($moderator_pin) == 0) {
		$moderator_pin = get_meeting_pin($pin_length, $meeting_uuid);
	}
	if (strlen($participant_pin) == 0) {
		$participant_pin = get_meeting_pin($pin_length, $meeting_uuid);
	}

//delete the user
	if ($_GET["a"] == "delete" && permission_exists('conference_room_delete')) {
		if (strlen($_REQUEST["meeting_user_uuid"]) > 0) {
			//set the variables
				$meeting_user_uuid = check_str($_REQUEST["meeting_user_uuid"]);
				$conference_room_uuid = check_str($_REQUEST["conference_room_uuid"]);
			//delete the extension from the ring_group
				$sql = "delete from v_meeting_users ";
				$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
				$sql .= "and meeting_user_uuid = '$meeting_user_uuid' ";
				$db->exec(check_sql($sql));
				unset($sql);
		}

		$_SESSION["message"] = $text['message-delete'];
		header("Location: conference_room_edit.php?id=".$conference_room_uuid);
		return;
	}


if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$conference_room_uuid = check_str($_POST["conference_room_uuid"]);
	}

	//check for a unique pin number and length
		if (strlen($moderator_pin) > 0 || strlen($participant_pin) > 0) {
			//make sure the moderator pin number is unique
				$sql = "select count(*) as num_rows from v_meetings ";
				$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
				if (strlen($meeting_uuid) > 0) {
					$sql .= "and meeting_uuid <> '".$meeting_uuid."' ";
				}
				$sql .= "and (moderator_pin = '".$moderator_pin."' or participant_pin = '".$moderator_pin."') ";
				$prep_statement = $db->prepare(check_sql($sql));
				if ($prep_statement) {
					$prep_statement->execute();
					$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
					if ($row['num_rows'] > 0) {
						$msg .= $text['message-unique_moderator_pin']."<br />\n";
					}
				}

			//make sure the participant pin number is unique
				$sql = "select count(*) as num_rows from v_meetings ";
				$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
				if (strlen($meeting_uuid) > 0) {
					$sql .= "and meeting_uuid <> '".$meeting_uuid."' ";
				}
				$sql .= "and (moderator_pin = '".$participant_pin."' or participant_pin = '".$participant_pin."') ";
				$prep_statement = $db->prepare(check_sql($sql));
				if ($prep_statement) {
					$prep_statement->execute();
					$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
					if ($row['num_rows'] > 0) {
						$msg .= $text['message-unique_participant_pin']."<br />\n";
					}
				}

			//additional checks
				if ($moderator_pin == $participant_pin) {
					$msg .= $text['message-non_unique_pin']."<br />\n";
				}
				if (strlen($moderator_pin) < $pin_length || strlen($participant_pin) < $pin_length) {
					$msg .= $text['message-minimum_pin_length']." ".$pin_length."<br />\n";
				}
		}

	//check for all required data
		//if (strlen($conference_center_uuid) == 0) { $msg .= "Please provide: Conference UUID<br>\n"; }
		//if (strlen($max_members) == 0) { $msg .= "Please provide: Max Members<br>\n"; }
		//if (strlen($start_datetime) == 0) { $msg .= "Please provide: Start Date/Time<br>\n"; }
		//if (strlen($stop_datetime) == 0) { $msg .= "Please provide: Stop Date/Time<br>\n"; }
		//if (strlen($wait_mod) == 0) { $msg .= "Please provide: Wait for the Moderator<br>\n"; }
		//if (strlen($profile) == 0) { $msg .= "Please provide: Conference Profile<br>\n"; }
		//if (strlen($announce) == 0) { $msg .= "Please provide: Announce<br>\n"; }
		//if (strlen($enter_sound) == 0) { $msg .= "Please provide: Enter Sound<br>\n"; }
		//if (strlen($mute) == 0) { $msg .= "Please provide: Mute<br>\n"; }
		//if (strlen($sounds) == 0) { $msg .= "Please provide: Sounds<br>\n"; }
		//if (strlen($created) == 0) { $msg .= "Please provide: Created<br>\n"; }
		//if (strlen($created_by) == 0) { $msg .= "Please provide: Created By<br>\n"; }
		//if (strlen($enabled) == 0) { $msg .= "Please provide: Enabled<br>\n"; }
		//if (strlen($description) == 0) { $msg .= "Please provide: Description<br>\n"; }
		if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
			require_once "resources/header.php";
			require_once "resources/persist_form_var.php";
			echo "<div align='center'>\n";
			echo "<table><tr><td>\n";
			echo $msg."<br />";
			echo "</td></tr></table>\n";
			persistformvar($_POST);
			echo "</div>\n";
			require_once "resources/footer.php";
			exit;
		}

	//add or update the database
		if ($_POST["persistformvar"] != "true") {

			if ($action == "add" && permission_exists('conference_room_add')) {
				//set default values
					if (strlen($profile) == 0) { $profile = 'default'; }
					if (strlen($record) == 0) { $record = 'false'; }
					if (strlen($max_members) == 0) { $max_members = 0; }
					if (strlen($wait_mod) == 0) { $wait_mod = 'true'; }
					if (strlen($announce) == 0) { $announce = 'true'; }
					if (strlen($mute) == 0) { $mute = 'false'; }
					if (strlen($enabled) == 0) { $enabled = 'true'; }
					if (strlen($sounds) == 0) { $sounds = 'false'; }

				//add a meeting
					$meeting_uuid = uuid();
					$sql = "insert into v_meetings ";
					$sql .= "(";
					$sql .= "domain_uuid, ";
					$sql .= "meeting_uuid, ";
					$sql .= "moderator_pin, ";
					$sql .= "participant_pin, ";
					$sql .= "enabled, ";
					$sql .= "description ";
					$sql .= ") ";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'$domain_uuid', ";
					$sql .= "'$meeting_uuid', ";
					$sql .= "'$moderator_pin', ";
					$sql .= "'$participant_pin', ";
					$sql .= "'$enabled', ";
					$sql .= "'$description' ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					unset($sql);

				//add a conference room
					$conference_room_uuid = uuid();
					$sql = "insert into v_conference_rooms ";
					$sql .= "(";
					$sql .= "domain_uuid, ";
					$sql .= "conference_room_uuid, ";
					$sql .= "conference_center_uuid, ";
					$sql .= "meeting_uuid, ";
					$sql .= "conference_room_name, ";
					$sql .= "profile, ";
					$sql .= "record, ";
					$sql .= "max_members, ";
					$sql .= "start_datetime, ";
					$sql .= "stop_datetime, ";
					$sql .= "wait_mod, ";
					$sql .= "announce, ";
					$sql .= "sounds, ";
					$sql .= "mute, ";
					$sql .= "created, ";
					$sql .= "created_by, ";
					$sql .= "enabled, ";
					$sql .= "description ";
					$sql .= ") ";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'$domain_uuid', ";
					$sql .= "'$conference_room_uuid', ";
					$sql .= "'$conference_center_uuid', ";
					$sql .= "'$meeting_uuid', ";
					$sql .= "'$conference_room_name', ";
					$sql .= "'$profile', ";
					$sql .= "'$record', ";
					$sql .= "'$max_members', ";
					$sql .= "'$start_datetime', ";
					$sql .= "'$stop_datetime', ";
					$sql .= "'$wait_mod', ";
					$sql .= "'$announce', ";
					$sql .= "'$sounds', ";
					$sql .= "'$mute', ";
					$sql .= "now(), ";
					$sql .= "'".$_SESSION['user_uuid']."', ";
					$sql .= "'$enabled', ";
					$sql .= "'$description' ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					unset($sql);

				//assign the logged in user to the meeting
					if (strlen($_SESSION["user_uuid"]) > 0) {
						$meeting_user_uuid = uuid();
						$sql = "insert into v_meeting_users ";
						$sql .= "(";
						$sql .= "domain_uuid, ";
						$sql .= "meeting_user_uuid, ";
						$sql .= "meeting_uuid, ";
						$sql .= "user_uuid ";
						$sql .= ") ";
						$sql .= "values ";
						$sql .= "(";
						$sql .= "'$domain_uuid', ";
						$sql .= "'$meeting_user_uuid', ";
						$sql .= "'$meeting_uuid', ";
						$sql .= "'".$_SESSION["user_uuid"]."' ";
						$sql .= ")";
						$db->exec(check_sql($sql));
						unset($sql);
					}

				$_SESSION["message"] = $text['message-add'];
			} //if ($action == "add")

			if ($action == "update" && permission_exists('conference_room_edit')) {
				//get the meeting_uuid
					if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
						$conference_room_uuid = check_str($_GET["id"]);
						$sql = "select * from v_conference_rooms ";
						$sql .= "where domain_uuid = '$domain_uuid' ";
						$sql .= "and conference_room_uuid = '$conference_room_uuid' ";
						$prep_statement = $db->prepare(check_sql($sql));
						$prep_statement->execute();
						$result = $prep_statement->fetchAll();
						foreach ($result as &$row) {
							$meeting_uuid = $row["meeting_uuid"];
						}
						unset ($prep_statement);
					}

				//update conference meetings
					$sql = "update v_meetings set ";
					$sql .= "moderator_pin = '$moderator_pin', ";
					$sql .= "participant_pin = '$participant_pin', ";
					$sql .= "enabled = '$enabled', ";
					$sql .= "description = '$description' ";
					$sql .= "where domain_uuid = '$domain_uuid' ";
					$sql .= "and meeting_uuid = '$meeting_uuid' ";
					$db->exec(check_sql($sql));
					unset($sql);

				//update the conference room
					$sql = "update v_conference_rooms set ";
					$sql .= "conference_center_uuid = '$conference_center_uuid', ";
					//$sql .= "meeting_uuid = '$meeting_uuid', ";
					$sql .= "conference_room_name = '$conference_room_name', ";
					if (strlen($profile) > 0) {
						$sql .= "profile = '$profile', ";
					}
					if (strlen($record) > 0) {
						$sql .= "record = '$record', ";
					}
					if (strlen($max_members) > 0) {
						$sql .= "max_members = '$max_members', ";
					}
					$sql .= "start_datetime = '".$start_datetime."', ";
					$sql .= "stop_datetime = '".$stop_datetime."', ";
					if (strlen($wait_mod) > 0) {
						$sql .= "wait_mod = '$wait_mod', ";
					}
					if (strlen($announce) > 0) {
						$sql .= "announce = '$announce', ";
					}
					//$sql .= "enter_sound = '$enter_sound', ";
					if (strlen($mute) > 0) {
						$sql .= "mute = '$mute', ";
					}
					$sql .= "sounds = '$sounds', ";
					if (strlen($enabled) > 0) {
						$sql .= "enabled = '$enabled', ";
					}
					$sql .= "description = '$description' ";
					$sql .= "where domain_uuid = '$domain_uuid' ";
					$sql .= "and conference_room_uuid = '$conference_room_uuid' ";
					$db->exec(check_sql($sql));
					unset($sql);

				$_SESSION["message"] = $text['message-update'];
			} //if ($action == "update")

			//assign the user to the meeting
				if (strlen($user_uuid) > 0 && $_SESSION["user_uuid"] != $user_uuid) {
					$meeting_user_uuid = uuid();
					$sql = "insert into v_meeting_users ";
					$sql .= "(";
					$sql .= "domain_uuid, ";
					$sql .= "meeting_user_uuid, ";
					$sql .= "meeting_uuid, ";
					$sql .= "user_uuid ";
					$sql .= ")";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'$domain_uuid', ";
					$sql .= "'$meeting_user_uuid', ";
					$sql .= "'$meeting_uuid', ";
					$sql .= "'$user_uuid' ";
					$sql .= ")";
					//echo $sql; //exit;
					$db->exec(check_sql($sql));
					unset($sql);

					$_SESSION["message"] = $text['message-add'];
				}

			header("Location: conference_room_edit.php?id=".$conference_room_uuid);
			return;

		} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
		//get the conference room details
			$conference_room_uuid = check_str($_REQUEST["id"]);
			$sql = "select * from v_conference_rooms as r, v_meetings as m ";
			$sql .= "where r.domain_uuid = '$domain_uuid' ";
			$sql .= "and r.meeting_uuid = m.meeting_uuid ";
			$sql .= "and r.conference_room_uuid = '$conference_room_uuid' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll();
			foreach ($result as &$row) {
				$conference_center_uuid = $row["conference_center_uuid"];
				$meeting_uuid = $row["meeting_uuid"];
				$moderator_pin = $row["moderator_pin"];
				$participant_pin = $row["participant_pin"];
				$conference_room_name = $row["conference_room_name"];
				$profile = $row["profile"];
				$record = $row["record"];
				$max_members = $row["max_members"];
				$start_datetime = $row["start_datetime"];
				$stop_datetime = $row["stop_datetime"];
				$wait_mod = $row["wait_mod"];
				$announce = $row["announce"];
				$sounds = $row["sounds"];
				$mute = $row["mute"];
				$created = $row["created"];
				$created_by = $row["created_by"];
				$enabled = $row["enabled"];
				$description = $row["description"];
			}
			unset ($prep_statement, $sql);
	}

//get the users array
	$sql = "SELECT * FROM v_users ";
	$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
	$sql .= "order by username asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$users = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	unset($prep_statement, $sql);

//get the users assigned to this meeting
	$sql = "SELECT * FROM v_users as u, v_meeting_users as m ";
	$sql .= "where u.user_uuid = m.user_uuid  ";
	$sql .= "and m.domain_uuid = '".$_SESSION['domain_uuid']."' ";
	$sql .= "and m.meeting_uuid = '$meeting_uuid' ";
	$sql .= "order by u.username asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$meeting_users = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	unset($prep_statement, $sql);

//set default profile
	if (strlen($profile) == 0) { $profile = 'default'; }

//get default pins
	if (strlen($moderator_pin) == 0) {
		$moderator_pin = get_meeting_pin($pin_length, $meeting_uuid);
	}
	if (strlen($participant_pin) == 0) {
		$participant_pin = get_meeting_pin($pin_length, $meeting_uuid);
	}

//format the pins
	if (strlen($moderator_pin) == 9)  {
		$moderator_pin = substr($moderator_pin, 0, 3) ."-".  substr($moderator_pin, 3, 3) ."-". substr($moderator_pin, -3)."\n";
	}
	if (strlen($participant_pin) == 9)  {
		$participant_pin = substr($participant_pin, 0, 3) ."-".  substr($participant_pin, 3, 3) ."-". substr($participant_pin, -3)."\n";
	}

//set default values
	if (strlen($record) == 0) { $record = 'false'; }
	if (strlen($max_members) == 0) { $max_members = 0; }
	if (strlen($wait_mod) == 0) { $wait_mod = 'true'; }
	if (strlen($announce) == 0) { $announce = 'true'; }
	if (strlen($mute) == 0) { $mute = 'false'; }
	if (strlen($sounds) == 0) { $sounds = 'false'; }
	if (strlen($enabled) == 0) { $enabled = 'true'; }

//show the header
	require_once "resources/header.php";

//show the content
	echo "<form method='post' name='frm' action=''>\n";

	echo "<table width='100%'  border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' valign='top' width='30%' nowrap='nowrap'><b>".$text['title-conference_rooms']."</b></td>\n";
	echo "<td width='70%' align='right' valign='top'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='conference_rooms.php'\" value='".$text['button-back']."'>\n";
	if (strlen($meeting_uuid) > 0) {
		echo "	<input type='button' class='btn' name='' alt='".$text['button-sessions']."' onclick=\"window.location='conference_sessions.php?id=".$meeting_uuid."'\" value='".$text['button-sessions']."'>\n";
		echo "	<input type='button' class='btn' name='' alt='".$text['button-view']."' onclick=\"window.location='".PROJECT_PATH."/app/conferences_active/conference_interactive.php?c=".$meeting_uuid."'\" value='".$text['button-view']."'>\n";
	}
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "<br />\n";
	echo "<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>".$text['label-conference_name']."</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='conference_center_uuid'>\n";
	foreach ($conference_centers as &$row) {
		if ($conference_center_uuid == $row["conference_center_uuid"]) {
			echo "		<option value='".$row["conference_center_uuid"]."' selected='selected'>".$row["conference_center_name"]."</option>\n";
		}
		else {
			echo "		<option value='".$row["conference_center_uuid"]."'>".$row["conference_center_name"]."</option>\n";
		}
	}
	unset ($prep_statement);
	echo "	</select>\n";
	echo "	<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>";
	echo "		<td class='vncell' valign='top'>".$text['label-room-name']."</td>";
	echo "		<td class='vtable' align='left'>";
	echo "  		<input class='formfld' type='text' name='conference_room_name' maxlength='255' value='$conference_room_name'>\n";
	echo "			<br />\n";
	echo "			".$text['description-room-name']."\n";
	echo "		</td>";
	echo "	</tr>";

	echo "	<tr>";
	echo "		<td class='vncell' valign='top'>".$text['label-moderator-pin']."</td>";
	echo "		<td class='vtable' align='left'>";
	echo "  		<input class='formfld' type='text' name='moderator_pin' maxlength='255' value='$moderator_pin'>\n";
	echo "			<br />\n";
	echo "			".$text['description-moderator_pin']."\n";
	echo "		</td>";
	echo "	</tr>";

	echo "	<tr>";
	echo "		<td class='vncell' valign='top'>".$text['label-participant-pin']."</td>";
	echo "		<td class='vtable' align='left'>";
	echo "  		<input class='formfld' type='text' name='participant_pin' maxlength='255' value='$participant_pin'>\n";
	echo "			<br />\n";
	echo "			".$text['description-participant-pin']."\n";
	echo "		</td>";
	echo "	</tr>";

	if (if_group("superadmin") || if_group("admin")) {
		echo "	<tr>";
		echo "		<td class='vncell' valign='top'>".$text['label-users']."</td>";
		echo "		<td class='vtable' align='left'>";
		if ($action == "update") {
			echo "			<table border='0' style='width : 235px;'>\n";
			foreach($meeting_users as $field) {
				echo "			<tr>\n";
				echo "				<td class='vtable'>".$field['username']."</td>\n";
				echo "				<td style='width: 25px;' align='right'>\n";
				if (permission_exists('conference_room_delete')) {
					echo "					<a href='conference_room_edit.php?meeting_user_uuid=".$field['meeting_user_uuid']."&conference_room_uuid=".$conference_room_uuid."&a=delete' alt='delete' onclick=\"return confirm(".$text['confirm-delete'].")\">$v_link_label_delete</a>\n";
				}
				echo "				</td>\n";
				echo "			</tr>\n";
			}
			echo "			</table>\n";
		}
		echo "			<br />\n";
		if (permission_exists('conference_room_add')) {
			echo "			<select name=\"user_uuid\" class='formfld' style='width: auto;'>\n";
			echo "			<option value=\"\"></option>\n";
			foreach($users as $field) {
				echo "			<option value='".$field['user_uuid']."'>".$field['username']."</option>\n";
			}
			echo "			</select>";
			if ($action == "update") {
				echo "			<input type=\"submit\" class='btn' value=\"".$text['button-add']."\">\n";
			}
			unset($users);
			echo "			<br>\n";
		}
		echo "			".$text['description-users']."\n";
		echo "		</td>";
		echo "	</tr>";
	}

	if (permission_exists('conference_room_profile')) {
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>".$text['label-profile']."</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='profile' maxlength='255' value='$profile'>\n";
		echo "	<br />\n";
		echo "	".$text['description-profile']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('conference_room_record')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>".$text['label-record']."</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<select class='formfld' name='record'>\n";
		echo "	<option value=''></option>\n";
		if ($record == "true") {
			echo "	<option value='true' selected='selected'>".$text['label-true']."</option>\n";
		}
		else {
			echo "	<option value='true'>".$text['label-true']."</option>\n";
		}
		if ($record == "false") {
			echo "	<option value='false' selected='selected'>".$text['label-false']."</option>\n";
		}
		else {
			echo "	<option value='false'>".$text['label-false']."</option>\n";
		}
		echo "	</select>\n";
		echo "<br />\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('conference_room_max_members')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>".$text['label-max-members']."</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  <input class='formfld' type='text' name='max_members' maxlength='255' value='$max_members'>\n";
		echo "<br />\n";
		echo "\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' nowrap='nowrap' width='30%'>".$text['label-schedule']."</td>\n";
	echo "<td class='vtable' width='70%' align='left' style='position: relative; min-width: 275px;'>\n";
	echo "		<input type='text' class='formfld datetimepicker' style='min-width: 115px; width: 115px; max-width: 115px;' name='start_datetime' id='start_datetime' placeholder='".$text['label-from']."' value='".$start_datetime."'>\n";
	echo "		<input type='text' class='formfld datetimepicker' style='min-width: 115px; width: 115px; max-width: 115px;' name='stop_datetime' id='stop_datetime' placeholder='".$text['label-to']."' value='".$stop_datetime."'>\n";
	echo "	<br>".$text['description-schedule'];
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('conference_room_wait_mod')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>".$text['label-wait_for_moderator']."</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<select class='formfld' name='wait_mod'>\n";
		echo "	<option value=''></option>\n";
		if ($wait_mod == "true") {
			echo "	<option value='true' selected='selected'>".$text['label-true']."</option>\n";
		}
		else {
			echo "	<option value='true'>".$text['label-true']."</option>\n";
		}
		if ($wait_mod == "false") {
			echo "	<option value='false' selected='selected'>".$text['label-false']."</option>\n";
		}
		else {
			echo "	<option value='false'>".$text['label-false']."</option>\n";
		}
		echo "	</select>\n";
		echo "<br />\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('conference_room_announce')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>".$text['label-announce']."</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<select class='formfld' name='announce'>\n";
		echo "	<option value=''></option>\n";
		if ($announce == "true") {
			echo "	<option value='true' selected='selected'>".$text['label-true']."</option>\n";
		}
		else {
			echo "	<option value='true'>".$text['label-true']."</option>\n";
		}
		if ($announce == "false") {
			echo "	<option value='false' selected='selected'>".$text['label-false']."</option>\n";
		}
		else {
			echo "	<option value='false'>".$text['label-false']."</option>\n";
		}
		echo "	</select>\n";
		echo "<br />\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	//echo "<tr>\n";
	//echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	//echo "	".$text['label-enter-sound']."\n";
	//echo "</td>\n";
	//echo "<td class='vtable' align='left'>\n";
	//echo "	<input class='formfld' type='text' name='enter_sound' maxlength='255' value=\"$enter_sound\">\n";
	//echo "<br />\n";
	//echo "\n";
	//echo "</td>\n";
	//echo "</tr>\n";

	if (permission_exists('conference_room_mute')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>".$text['label-mute']."</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<select class='formfld' name='mute'>\n";
		echo "	<option value=''></option>\n";
		if ($mute == "true") {
			echo "	<option value='true' selected='selected'>".$text['label-true']."</option>\n";
		}
		else {
			echo "	<option value='true'>".$text['label-true']."</option>\n";
		}
		if ($mute == "false") {
			echo "	<option value='false' selected='selected'>".$text['label-false']."</option>\n";
		}
		else {
			echo "	<option value='false'>".$text['label-false']."</option>\n";
		}
		echo "	</select>\n";
		echo "<br />\n";
		echo "\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('conference_room_profile')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>".$text['label-enabled']."</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<select class='formfld' name='enabled'>\n";
		echo "	<option value=''></option>\n";
		if ($enabled == "true") {
			echo "	<option value='true' selected='selected'>".$text['label-true']."</option>\n";
		}
		else {
			echo "	<option value='true'>".$text['label-true']."</option>\n";
		}
		if ($enabled == "false") {
			echo "	<option value='false' selected='selected'>".$text['label-false']."</option>\n";
		}
		else {
			echo "	<option value='false'>".$text['label-false']."</option>\n";
		}
		echo "	</select>\n";
		echo "<br />\n";
		echo "\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('conference_room_sounds')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>".$text['label-sounds']."</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<select class='formfld' name='sounds'>\n";
		echo "	<option value=''></option>\n";
		if ($sounds == "true") {
			echo "	<option value='true' selected='selected'>".$text['label-true']."</option>\n";
		}
		else {
			echo "	<option value='true'>".$text['label-true']."</option>\n";
		}
		if ($sounds == "false") {
			echo "	<option value='false' selected='selected'>".$text['label-false']."</option>\n";
		}
		else {
			echo "	<option value='false'>".$text['label-false']."</option>\n";
		}
		echo "	</select>\n";
		echo "<br />\n";
		echo "\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>".$text['label-description']."</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='description' maxlength='255' value=\"$description\">\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "	<td colspan='2' align='right'>\n";
	echo "		<br>";
	if ($action == "update") {
		echo "	<input type='hidden' name='conference_center_uuid' value='$conference_center_uuid'>\n";
		echo "	<input type='hidden' name='meeting_uuid' value='$meeting_uuid'>\n";
		echo "	<input type='hidden' name='conference_room_uuid' value='$conference_room_uuid'>\n";
	}
	echo "		<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "	</td>\n";
	echo "</tr>";

	echo "</table>";
	echo "<br>";

	echo "</form>";


//include the footer
	require_once "resources/footer.php";
?>
