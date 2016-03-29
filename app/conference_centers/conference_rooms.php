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
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('conference_room_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//additional includes
	require_once "resources/header.php";
	require_once "resources/paging.php";

//get the meeting_uuid using the pin number
	$search = check_str($_GET["search"]);
	$search = preg_replace('{\D}', '', $search);
	if (strlen($search) > 0) {
		$sql = "select * from v_meetings ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and (moderator_pin = '".$search."' or participant_pin = '".$search."') ";
		$prep_statement = $db->prepare(check_sql($sql));
		if ($prep_statement) {
			$prep_statement->execute();
			$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
			$meeting_uuid = $row['meeting_uuid'];
		}
	}

//if the $_GET array exists then process it
	if (count($_GET) > 0 && strlen($_GET["search"]) == 0) {
		//get http GET variables and set them as php variables
			$conference_room_uuid = check_str($_GET["conference_room_uuid"]);
			$record = check_str($_GET["record"]);
			$wait_mod = check_str($_GET["wait_mod"]);
			$announce = check_str($_GET["announce"]);
			$mute = check_str($_GET["mute"]);
			$sounds = check_str($_GET["sounds"]);
			$enabled = check_str($_GET["enabled"]);
			$meeting_uuid = check_str($_GET["meeting_uuid"]);

		//record announcement
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

		//update the conference room
			$sql = "update v_conference_rooms set ";
			if (strlen($record) > 0) {
				$sql .= "record = '$record' ";
			}
			if (strlen($wait_mod) > 0) {
				$sql .= "wait_mod = '$wait_mod' ";
			}
			if (strlen($announce) > 0) {
				$sql .= "announce = '$announce' ";
			}
			if (strlen($mute) > 0) {
				$sql .= "mute = '$mute' ";
			}
			if (strlen($sounds) > 0) {
				$sql .= "sounds = '$sounds' ";
			}
			if (strlen($enabled) > 0) {
				$sql .= "enabled = '$enabled' ";
			}
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and conference_room_uuid = '$conference_room_uuid' ";
			//echo $sql; //exit;
			$db->exec(check_sql($sql));
			unset($sql);
	}

//get conference array
	$switch_cmd = "conference xml_list";
	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
	if (!$fp) {
		//connection to even socket failed
	}
	else {
		$xml_str = trim(event_socket_request($fp, 'api '.$switch_cmd));
		try {
			$xml = new SimpleXMLElement($xml_str, true);
		}
		catch(Exception $e) {
			//echo $e->getMessage();
		}
		foreach ($xml->conference as $row) {
			//convert the xml object to an array
				$json = json_encode($row);
				$row = json_decode($json, true);
			//set the variables
				$conference_name = $row['@attributes']['name'];
				$session_uuid = $row['@attributes']['uuid'];
				$member_count = $row['@attributes']['member-count'];
			//show the conferences that have a matching domain
				$tmp_domain = substr($conference_name, -strlen($_SESSION['domain_name']));
				if ($tmp_domain == $_SESSION['domain_name']) {
					$meeting_uuid = substr($conference_name, 0, strlen($conference_name) - strlen('-'.$_SESSION['domain_name']));
					$conference[$meeting_uuid]["conference_name"] = $conference_name;
					$conference[$meeting_uuid]["session_uuid"] = $session_uuid;
					$conference[$meeting_uuid]["member_count"] = $member_count;
				}
		}
	}

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//show the content
	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "	<tr>\n";
	echo "		<form method='get' action=''>\n";
	echo "			<td width='50%' align='left' valign='top' nowrap='nowrap'><b>".$text['title-conference_rooms']."</b></td>\n";
	echo "			<td width='50%' align='right' valign='top'>\n";
	echo "				<input type='text' class='txt' style='width: 150px' name='search' value='$search'>";
	echo "				<input type='submit' class='btn' name='submit' value='".$text['button-search']."'>";
	echo "			</td>\n";
	echo "		</form>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "<br /><br>\n";

	//get the conference room count
		require_once "app/conference_centers/resources/classes/conference_center.php";
		$conference_center = new conference_center;
		$conference_center->db = $db;
		$conference_center->domain_uuid = $_SESSION['domain_uuid'];
		if (strlen($meeting_uuid) > 0) {
			$conference_center->meeting_uuid = $meeting_uuid;
		}
		if (strlen($search) > 0) {
			$conference_center->search = $search;
		}
		$row_count = $conference_center->room_count();

	//prepare to page the results
		$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
		$param = "";
		$page = check_str($_GET['page']);
		if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
		list($paging_controls, $rows_per_page, $var3) = paging($row_count, $param, $rows_per_page);
		$offset = $rows_per_page * $page;

	//get the conference rooms
		$conference_center->rows_per_page = $rows_per_page;
		$conference_center->offset = $offset;
		$conference_center->order_by = $order_by;
		$conference_center->order = $order;
		if (strlen($meeting_uuid) > 0) {
			$conference_center->meeting_uuid = $meeting_uuid;
		}
		if (strlen($search) > 0) {
			$conference_center->search = $search;
		}
		$result = $conference_center->rooms();
		$result_count = $conference_center->count;

	//prepare to alternate the row styles
		$c = 0;
		$row_style["0"] = "row_style0";
		$row_style["1"] = "row_style1";

	//table header
		echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr>\n";
		//echo th_order_by('conference_center_uuid', 'Conference UUID', $order_by, $order);
		//echo th_order_by('meeting_uuid', 'Meeting UUID', $order_by, $order);
		echo "<th nowrap='nowrap'>".$text['label-name']."</th>\n";
		echo "<th nowrap='nowrap'>".$text['label-moderator-pin']."</th>\n";
		echo "<th nowrap='nowrap'>".$text['label-participant-pin']."</th>\n";
		//echo th_order_by('profile', $text['label-profile'], $order_by, $order);
		echo th_order_by('record', $text['label-record'], $order_by, $order);
		//echo th_order_by('max_members', 'Max', $order_by, $order);
		echo th_order_by('wait_mod', $text['label-wait_moderator'], $order_by, $order);
		echo th_order_by('announce', $text['label-announce'], $order_by, $order);
		//echo th_order_by('enter_sound', 'Enter Sound', $order_by, $order);
		echo th_order_by('mute', $text['label-mute'], $order_by, $order);
		echo th_order_by('sounds', $text['label-sounds'], $order_by, $order);
		echo "<th>".$text['label-members']."</th>\n";
		echo "<th>".$text['label-tools']."</th>\n";
		if (permission_exists('conference_room_enabled')) {
			echo th_order_by('enabled', $text['label-enabled'], $order_by, $order);
		}
		echo th_order_by('description', $text['label-description'], $order_by, $order);
		echo "<td align='right' width='42' nowrap='nowrap'>\n";
		if (permission_exists('conference_room_add')) {
			echo "	<a href='conference_room_edit.php' alt='add'>$v_link_label_add</a>\n";
		}
		else {
			echo "	&nbsp;\n";
		}
		echo "</td>\n";
		echo "</tr>\n";

	//table data
		if ($result_count > 0) {
			foreach($result as $row) {
				$meeting_uuid = $row['meeting_uuid'];
				$conference_room_name = $row['conference_room_name'];
				$moderator_pin = $row['moderator_pin'];
				$participant_pin = $row['participant_pin'];
				if (strlen($moderator_pin) == 9)  {
					$moderator_pin = substr($moderator_pin, 0, 3) ."-".  substr($moderator_pin, 3, 3) ."-". substr($moderator_pin, -3)."\n";
				}
				if (strlen($participant_pin) == 9)  {
					$participant_pin = substr($participant_pin, 0, 3) ."-".  substr($participant_pin, 3, 3) ."-". substr($participant_pin, -3)."\n";
				}

				$tr_link = (permission_exists('conference_room_edit')) ? "href='conference_room_edit.php?id=".$row['conference_room_uuid']."'" : null;
				echo "<tr ".$tr_link.">\n";
				echo "	<td valign='middle' class='".$row_style[$c]."'>".(($conference_room_name != '') ? "<a ".$tr_link.">".$conference_room_name."</a>" : "&nbsp;")."</td>\n";
				echo "	<td valign='middle' class='".$row_style[$c]."'>".$moderator_pin."</td>\n";
				echo "	<td valign='middle' class='".$row_style[$c]."'>".$participant_pin."</td>\n";
				//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['conference_center_uuid']."&nbsp;</td>\n";
				//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['meeting_uuid']."&nbsp;</td>\n";
				//echo "	<td valign='middle' class='".$row_style[$c]."'>".$row['profile']."&nbsp;</td>\n";
				echo "	<td valign='middle' class='".$row_style[$c]."'>";
				if ($row['record'] == "true") {
					echo "		<a href=\"?conference_room_uuid=".$row['conference_room_uuid']."&record=false&meeting_uuid=".$meeting_uuid."\">".$text['label-true']."</a>";
				}
				else {
					echo "		<a href=\"?conference_room_uuid=".$row['conference_room_uuid']."&record=true&meeting_uuid=".$meeting_uuid."\">".$text['label-false']."</a>";
				}
				echo "		&nbsp;\n";
				echo "	</td>\n";
				//echo "	<td valign='middle' class='".$row_style[$c]."'>".$row['max_members']."&nbsp;</td>\n";
				echo "	<td valign='middle' class='".$row_style[$c]."'>";
				if ($row['wait_mod'] == "true") {
					echo "		<a href=\"?conference_room_uuid=".$row['conference_room_uuid']."&wait_mod=false\">".$text['label-true']."</a>";
				}
				else {
					echo "		<a href=\"?conference_room_uuid=".$row['conference_room_uuid']."&wait_mod=true\">".$text['label-false']."</a>";
				}
				echo "		&nbsp;\n";
				echo "	</td>\n";
				echo "	<td valign='middle' class='".$row_style[$c]."'>";
				if ($row['announce'] == "true") {
					echo "		<a href=\"?conference_room_uuid=".$row['conference_room_uuid']."&announce=false\">".$text['label-true']."</a>";
				}
				else {
					echo "		<a href=\"?conference_room_uuid=".$row['conference_room_uuid']."&announce=true\">".$text['label-false']."</a>";
				}
				echo "		&nbsp;\n";
				echo "	</td>\n";

				echo "	<td valign='middle' class='".$row_style[$c]."'>";
				if ($row['mute'] == "true") {
					echo "		<a href=\"?conference_room_uuid=".$row['conference_room_uuid']."&mute=false\">".$text['label-true']."</a>&nbsp;";
				}
				else {
					echo "		<a href=\"?conference_room_uuid=".$row['conference_room_uuid']."&mute=true\">".$text['label-false']."</a>&nbsp;";
				}
				echo "	</td>\n";

				echo "	<td valign='middle' class='".$row_style[$c]."'>";
				if ($row['sounds'] == "true") {
					echo "		<a href=\"?conference_room_uuid=".$row['conference_room_uuid']."&sounds=false\">".$text['label-true']."</a>";
				}
				else {
					echo "		<a href=\"?conference_room_uuid=".$row['conference_room_uuid']."&sounds=true\">".$text['label-false']."</a>";
				}
				echo "		&nbsp;\n";
				echo "	</td>\n";

				if (strlen($conference[$meeting_uuid]["session_uuid"])) {
					echo "	<td valign='middle' class='".$row_style[$c]."'>".$conference[$meeting_uuid]["member_count"]."&nbsp;</td>\n";
				}
				else {
					echo "	<td valign='middle' class='".$row_style[$c]."'>0</td>\n";
				}
				echo "	<td valign='middle' class='".$row_style[$c]."' nowrap='nowrap'>\n";
				echo "		<a href='".PROJECT_PATH."/app/conferences_active/conference_interactive.php?c=".$row['meeting_uuid']."'>".$text['label-view']."</a>&nbsp;\n";
				echo "		<a href='conference_sessions.php?id=".$row['meeting_uuid']."'>".$text['label-sessions']."</a>\n";
				echo "	</td>\n";

				if (permission_exists('conference_room_enabled')) {
					echo "	<td valign='middle' class='".$row_style[$c]."'>";
					if ($row['enabled'] == "true") {
						echo "		<a href=\"?conference_room_uuid=".$row['conference_room_uuid']."&enabled=false\">".$text['label-true']."</a>";
					}
					else {
						echo "		<a href=\"?conference_room_uuid=".$row['conference_room_uuid']."&enabled=true\">".$text['label-false']."</a>";
					}
					echo "		&nbsp;\n";
					echo "	</td>\n";
				}

				echo "	<td valign='middle' class='row_stylebg'>";
				echo "		".$row['description']."\n";
				echo "		&nbsp;\n";
				echo "	</td>\n";

				echo "	<td class='list_control_icons'>";
				if (permission_exists('conference_room_edit')) {
					echo "<a href='conference_room_edit.php?id=".$row['conference_room_uuid']."' alt='".$text['label-edit']."'>$v_link_label_edit</a>";
				}
				if (permission_exists('conference_room_delete')) {
					echo "<a href='conference_room_delete.php?id=".$row['conference_room_uuid']."' alt='".$text['label-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
				}
				echo "	</td>\n";

				echo "</tr>\n";
				if ($c==0) { $c=1; } else { $c=0; }
			} //end foreach
			unset($sql, $result, $row_count);
		} //end if results

	//show paging
		echo "<tr>\n";
		echo "<td colspan='13' align='left'>\n";
		echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
		echo "	<tr>\n";
		echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
		echo "		<td width='33.3%' align='center' nowrap>$paging_controls</td>\n";
		echo "		<td class='list_control_icons'>";
		if (permission_exists('conference_room_add')) {
			echo 		"<a href='conference_room_edit.php' alt='add'>$v_link_label_add</a>";
		}
		echo "		</td>\n";
		echo "	</tr>\n";
		echo "	</table>\n";
		echo "</td>\n";
		echo "</tr>\n";

//close the tables
	echo "</table>";
	echo "<br><br>";

//include the footer
	require_once "resources/footer.php";
?>