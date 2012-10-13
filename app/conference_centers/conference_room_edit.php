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
	Portions created by the Initial Developer are Copyright (C) 2008-2012
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
require_once "root.php";
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('conference_room_add') || permission_exists('conference_room_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$conference_room_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$conference_center_uuid = check_str($_POST["conference_center_uuid"]);
		$member_pin = check_str($_POST["member_pin"]);
		$member_type = check_str($_POST["member_type"]);
		$user_uuid = check_str($_POST["user_uuid"]);
		$max_members = check_str($_POST["max_members"]);
		$wait_mod = check_str($_POST["wait_mod"]);
		$announce = check_str($_POST["announce"]);
		$enter_sound = check_str($_POST["enter_sound"]);
		$mute = check_str($_POST["mute"]);
		$created = check_str($_POST["created"]);
		$created_by = check_str($_POST["created_by"]);
		$enabled = check_str($_POST["enabled"]);
		$description = check_str($_POST["description"]);

		//remove any pin number formatting
		$member_pin = preg_replace('{\D}', '', $member_pin);
	}

//delete the user
	if ($_GET["a"] == "delete" && permission_exists('conference_room_add') && permission_exists('conference_room_edit')) {
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
		if (strlen($_REQUEST["meeting_pin_uuid"]) > 0) {
			//set the variables
				$meeting_pin_uuid = check_str($_REQUEST["meeting_pin_uuid"]);
				$conference_room_uuid = check_str($_REQUEST["conference_room_uuid"]);
			//delete the pin number
				$sql = "delete from v_meeting_pins ";
				$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
				$sql .= "and meeting_pin_uuid = '$meeting_pin_uuid' ";
				$db->exec(check_sql($sql));
				unset($sql);
		}
		//redirect the browser
			require_once "includes/header.php";
			echo "<meta http-equiv=\"refresh\" content=\"2;url=conference_room_edit.php?id=$conference_room_uuid\">\n";
			echo "<div align='center'>Delete Complete</div>";
			require_once "includes/footer.php";
			return;
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$conference_room_uuid = check_str($_POST["conference_room_uuid"]);
	}

	//check for all required data
		//if (strlen($conference_center_uuid) == 0) { $msg .= "Please provide: Conference UUID<br>\n"; }
		//if (strlen($max_members) == 0) { $msg .= "Please provide: Max Members<br>\n"; }
		//if (strlen($wait_mod) == 0) { $msg .= "Please provide: Wait for the Moderator<br>\n"; }
		if (strlen($announce) == 0) { $msg .= "Please provide: Announce<br>\n"; }
		//if (strlen($enter_sound) == 0) { $msg .= "Please provide: Enter Sound<br>\n"; }
		if (strlen($mute) == 0) { $msg .= "Please provide: Mute<br>\n"; }
		//if (strlen($created) == 0) { $msg .= "Please provide: Created<br>\n"; }
		//if (strlen($created_by) == 0) { $msg .= "Please provide: Created By<br>\n"; }
		if (strlen($enabled) == 0) { $msg .= "Please provide: Enabled<br>\n"; }
		//if (strlen($description) == 0) { $msg .= "Please provide: Description<br>\n"; }
		if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
			require_once "includes/header.php";
			require_once "includes/persistformvar.php";
			echo "<div align='center'>\n";
			echo "<table><tr><td>\n";
			echo $msg."<br />";
			echo "</td></tr></table>\n";
			persistformvar($_POST);
			echo "</div>\n";
			require_once "includes/footer.php";
			return;
		}

	//add or update the database
		if ($_POST["persistformvar"] != "true") {

			if ($action == "add" && permission_exists('conference_room_add')) {
				//add a meeting
					$meeting_uuid = uuid();
					$sql = "insert into v_meetings ";
					$sql .= "(";
					$sql .= "domain_uuid, ";
					$sql .= "meeting_uuid, ";
					//$sql .= "created, ";
					//$sql .= "created_by, ";
					$sql .= "meeting_enabled, ";
					$sql .= "meeting_description ";
					$sql .= ")";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'$domain_uuid', ";
					$sql .= "'$meeting_uuid', ";
					//$sql .= "'$created', ";
					//$sql .= "'$created_by', ";
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
					$sql .= "max_members, ";
					$sql .= "wait_mod, ";
					$sql .= "announce, ";
					$sql .= "enter_sound, ";
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
					$sql .= "'$max_members', ";
					$sql .= "'$wait_mod', ";
					$sql .= "'$announce', ";
					$sql .= "'$enter_sound', ";
					$sql .= "'$mute', ";
					$sql .= "now(), ";
					$sql .= "'".$_SESSION['user_uuid']."', ";
					$sql .= "'$enabled', ";
					$sql .= "'$description' ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					unset($sql);
			} //if ($action == "add")

			if ($action == "update" && permission_exists('conference_room_edit')) {
				//get the meeting_uuid
					if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
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
					$sql .= "max_members = '$max_members', ";
					$sql .= "wait_mod = '$wait_mod', ";
					$sql .= "announce = '$announce', ";
					$sql .= "enter_sound = '$enter_sound', ";
					$sql .= "mute = '$mute', ";
					$sql .= "enabled = '$enabled', ";
					$sql .= "description = '$description' ";
					$sql .= "where domain_uuid = '$domain_uuid' ";
					$sql .= "and conference_room_uuid = '$conference_room_uuid' ";
					//echo $sql; //exit;
					$db->exec(check_sql($sql));
					unset($sql);
			} //if ($action == "update")

			//assign the user to the meeting
				if (strlen($user_uuid) > 0) {
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
				}

			//assign the pin numbers to the meeting
				if (strlen($member_pin) > 0) {
					$meeting_pin_uuid = uuid();
					$sql = "insert into v_meeting_pins ";
					$sql .= "(";
					$sql .= "domain_uuid, ";
					$sql .= "meeting_pin_uuid, ";
					$sql .= "meeting_uuid, ";
					$sql .= "member_pin, ";
					$sql .= "member_type ";
					$sql .= ")";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'$domain_uuid', ";
					$sql .= "'$meeting_pin_uuid', ";
					$sql .= "'$meeting_uuid', ";
					$sql .= "'$member_pin', ";
					$sql .= "'$member_type' ";
					$sql .= ")";
					//echo $sql; //exit;
					$db->exec(check_sql($sql));
					unset($sql);
				}

			//redirect the user
				require_once "includes/header.php";
				echo "<meta http-equiv=\"refresh\" content=\"2;url=conference_room_edit.php?id=$conference_room_uuid\">\n";
				echo "<div align='center'>\n";
				echo "Update Complete\n";
				echo "</div>\n";
				require_once "includes/footer.php";
				return;

		} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$conference_room_uuid = check_str($_GET["id"]);
		$sql = "select * from v_conference_rooms ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and conference_room_uuid = '$conference_room_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll();
		foreach ($result as &$row) {
			$conference_center_uuid = $row["conference_center_uuid"];
			$meeting_uuid = $row["meeting_uuid"];
			$max_members = $row["max_members"];
			$wait_mod = $row["wait_mod"];
			$announce = $row["announce"];
			$enter_sound = $row["enter_sound"];
			$mute = $row["mute"];
			$created = $row["created"];
			$created_by = $row["created_by"];
			$enabled = $row["enabled"];
			$description = $row["description"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//set default values
	if (strlen($max_members) == 0) { $max_members = 0; }
	if (strlen($wait_mod) == 0) { $wait_mod = 'false'; }
	if (strlen($announce) == 0) { $announce = 'true'; }
	if ($action == "add") {
		if (strlen($enter_sound) == 0) { $enter_sound = 'tone_stream://%(200,0,500,600,700)'; }
	}
	if (strlen($mute) == 0) { $mute = 'false'; }
	if (strlen($enabled) == 0) { $enabled = 'true'; }

//show the header
	require_once "includes/header.php";

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing=''>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "		<br>";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<div align='center'>\n";
	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap'><b>Conference Room</b></td>\n";
	echo "<td width='70%' align='right'>\n";
	if (strlen($meeting_uuid) > 0) {
		echo "	<input type='button' class='btn' name='' alt='back' onclick=\"window.location='/app/conferences_active/conference_interactive.php?c=".$meeting_uuid."'\" value='View'>\n";
	}
	echo "	<input type='button' class='btn' name='' alt='back' onclick=\"window.location='conference_rooms.php'\" value='Back'>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Conference Name:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='conference_center_uuid'>\n";
	$sql = "select * from v_conference_centers ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "order by conference_center_name asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$x = 0;
	$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
	foreach ($result as &$row) {
		if ($conference_center_uuid == $row["conference_center_uuid"]) {
			echo "		<option value='".$row["conference_center_uuid"]."' selected='selected'>".$row["conference_center_name"]."</option>\n";
		}
		else {
			echo "		<option value='".$row["conference_center_uuid"]."'>".$row["conference_center_name"]."</option>\n";
		}
		$x++;
	}
	unset ($prep_statement);
	echo "	</select>\n";
	echo "	<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>";
	echo "		<td class='vncell' valign='top'>Users:</td>";
	echo "		<td class='vtable' align='left'>";
	if ($action == "update") {
		echo "			<table border='0' style='width : 235px;'>\n";
		$sql = "SELECT * FROM v_users as u, v_meeting_users as m ";
		$sql .= "where u.user_uuid = m.user_uuid  ";
		$sql .= "and m.domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and m.meeting_uuid = '$meeting_uuid' ";
		$sql .= "order by u.username asc ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		$result_count = count($result);
		foreach($result as $field) {
			echo "			<tr>\n";
			echo "				<td class='vtable'>".$field['username']."</td>\n";
			echo "				<td style='width : 25px;' align='right'>\n";
			echo "					<a href='conference_room_edit.php?meeting_user_uuid=".$field['meeting_user_uuid']."&conference_room_uuid=".$conference_room_uuid."&a=delete' alt='delete' onclick=\"return confirm('Do you really want to delete this?')\">$v_link_label_delete</a>\n";
			echo "				</td>\n";
			echo "			</tr>\n";
		}
		echo "			</table>\n";
	}
	echo "			<br />\n";
	$sql = "SELECT * FROM v_users ";
	$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
	$sql .= "order by username asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	echo "			<select name=\"user_uuid\" class='frm'>\n";
	echo "			<option value=\"\"></option>\n";
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach($result as $field) {
		echo "			<option value='".$field['user_uuid']."'>".$field['username']."</option>\n";
	}
	echo "			</select>";
	if ($action == "update") {
		echo "			<input type=\"submit\" class='btn' value=\"Add\">\n";
	}
	unset($sql, $result);
	echo "			<br>\n";
	echo "			Assign the user to the conference room.\n";
	echo "			<br />\n";
	echo "		</td>";
	echo "	</tr>";

	echo "	<tr>";
	echo "		<td class='vncell' valign='top'>PIN Numbers:</td>";
	echo "		<td class='vtable' align='left'>";
	if ($action == "update") {
		echo "			<table border='0' style='width : 235px;'>\n";
		$sql = "SELECT * FROM v_meeting_pins ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and meeting_uuid = '".$meeting_uuid."' ";
		$sql .= "order by member_pin asc ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		$result_count = count($result);
		foreach($result as $field) {
			echo "			<tr>\n";
			echo "				<td class='vtable'>".$field['member_pin']."</td>\n";
			echo "				<td class='vtable'>".$field['member_type']."</td>\n";
			echo "				<td style='width : 25px;' align='right'>\n";
			echo "					<a href='conference_room_edit.php?meeting_pin_uuid=".$field['meeting_pin_uuid']."&conference_room_uuid=".$conference_room_uuid."&a=delete' alt='delete' onclick=\"return confirm('Do you really want to delete this?')\">$v_link_label_delete</a>\n";
			echo "				</td>\n";
			echo "			</tr>\n";
		}
		echo "			</table>\n";
	}
	echo "			<br />\n";
	echo "			<table border='0' style='width : 245px;'>\n";
	echo "				<tr>\n";
	echo "				<td nowrap='nowrap'>\n";
	echo "					<input class='formfld' type='text' name='member_pin' style='width : 125px;' value=''>\n";
	echo "				</td>\n";
	echo "				<td>\n";
	echo "					<select class='formfld' name='member_type' style='width : 100px;'>\n";
	if ($mute == "participant") { 
		echo "				<option value='participant' selected='selected'>participant</option>\n";
	}
	else {
		echo "				<option value='participant'>participant</option>\n";
	}
	if ($mute == "moderator") { 
		echo "				<option value='moderator' selected='selected'>moderator</option>\n";
	}
	else {
		echo "				<option value='moderator'>moderator</option>\n";
	}
	echo "					</select>\n";
	echo "				</td>\n";
	if ($action == "update") {
		echo "				<td>\n";
		echo "					<input type=\"submit\" class='btn' value=\"Add\">\n";
		echo "				</td>\n";
	}
	echo "				</tr>\n";
	echo "			</table>\n";
	unset($sql, $result);
	echo "			Pin numbers for the moderators or participants.\n";
	echo "			<br />\n";
	echo "		</td>";
	echo "	</tr>";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Max Members:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='max_members' maxlength='255' value='$max_members'>\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Wait for Moderator:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='wait_mod'>\n";
	echo "	<option value=''></option>\n";
	if ($announce == "true") { 
		echo "	<option value='true' selected='selected'>true</option>\n";
	}
	else {
		echo "	<option value='true'>true</option>\n";
	}
	if ($announce == "false") { 
		echo "	<option value='false' selected='selected'>false</option>\n";
	}
	else {
		echo "	<option value='false'>false</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Announce:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='announce'>\n";
	echo "	<option value=''></option>\n";
	if ($announce == "true") { 
		echo "	<option value='true' selected='selected'>true</option>\n";
	}
	else {
		echo "	<option value='true'>true</option>\n";
	}
	if ($announce == "false") { 
		echo "	<option value='false' selected='selected'>false</option>\n";
	}
	else {
		echo "	<option value='false'>false</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Enter Sound:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='enter_sound' maxlength='255' value=\"$enter_sound\">\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Mute:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='mute'>\n";
	echo "	<option value=''></option>\n";
	if ($mute == "true") { 
		echo "	<option value='true' selected='selected'>true</option>\n";
	}
	else {
		echo "	<option value='true'>true</option>\n";
	}
	if ($mute == "false") { 
		echo "	<option value='false' selected='selected'>false</option>\n";
	}
	else {
		echo "	<option value='false'>false</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	/*
	if ($action == "update" && permission_exists('conference_room_edit')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	Created:\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<table border='0' width='100%' cellpadding='0' cellspacing='0'>\n";
		echo "	<tr>\n";
		echo "	<td valign='top' align='top'><input name='created' readonly class='formfld' value='$created' type='text' class='formfld' onclick='popUpCalendar(this, this, \"mm/dd/yyyy\");'></td>\n";
		echo "	<td valign='middle' align='top' width='20' align='right'><img src='/images/icon_calendar.gif' onclick='popUpCalendar(this, frm.created, \"mm/dd/yyyy\");'></td>\n";
		echo "	</tr>\n";
		echo "	</table>\n";
		echo "<br />\n";
		echo "\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	Created By:\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='created_by' maxlength='255' value=\"$created_by\">\n";
		echo "<br />\n";
		echo "\n";
		echo "</td>\n";
		echo "</tr>\n";
	}
	*/

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Enabled:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='enabled'>\n";
	echo "	<option value=''></option>\n";
	if ($enabled == "true") { 
		echo "	<option value='true' selected='selected'>true</option>\n";
	}
	else {
		echo "	<option value='true'>true</option>\n";
	}
	if ($enabled == "false") { 
		echo "	<option value='false' selected='selected'>false</option>\n";
	}
	else {
		echo "	<option value='false'>false</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Description:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='description' maxlength='255' value=\"$description\">\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='conference_room_uuid' value='$conference_room_uuid'>\n";
	}
	echo "				<input type='submit' name='submit' class='btn' value='Save'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";

//include the footer
	require_once "includes/footer.php";
?>