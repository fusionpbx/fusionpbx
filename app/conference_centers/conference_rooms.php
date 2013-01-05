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
if (permission_exists('conference_room_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

//additional includes
	require_once "includes/header.php";
	require_once "includes/paging.php";

//if the $_GET array exists then process it
	if (count($_GET) > 0) {
		//get http GET variables and set them as php variables
			$conference_room_uuid = check_str($_GET["conference_room_uuid"]);
			$record = check_str($_GET["record"]);
			$wait_mod = check_str($_GET["wait_mod"]);
			$announce = check_str($_GET["announce"]);
			$mute = check_str($_GET["mute"]);
			$enabled = check_str($_GET["enabled"]);

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
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"center\">\n";
	echo "		<br />";

	echo "<table width='100%' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' align='left' nowrap='nowrap'><b>".$text['title-conference-rooms']."</b></td>\n";
	echo "		<td width='50%' align='right'>&nbsp;</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";

	//prepare to page the results
		$sql = "select count(*) as num_rows from v_conference_rooms as r, v_meeting_users as u ";
		$sql .= "where r.domain_uuid = '$domain_uuid' ";
		$sql .= "and r.meeting_uuid = u.meeting_uuid ";
		if (!if_group("admin") && !if_group("superadmin")) {
			$sql .= "and u.user_uuid = '".$_SESSION["user_uuid"]."' ";
		}
		//$sql .= "and r.meeting_uuid = 'fbd2214a-39db-4a93-bd84-3fd830f63dba' ";
		if (strlen($order_by)> 0) { $sql .= "order by $order_by $order "; }
		$prep_statement = $db->prepare($sql);
		if ($prep_statement) {
			$prep_statement->execute();
			$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
			if ($row['num_rows'] > 0) {
				$num_rows = $row['num_rows'];
			}
			else {
				$num_rows = '0';
			}
		}

	//prepare to page the results
		$rows_per_page = 10;
		$param = "";
		$page = $_GET['page'];
		if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; } 
		list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page); 
		$offset = $rows_per_page * $page; 

	//get the list
		$sql = "select * from v_conference_rooms as r, v_meeting_users as u ";
		$sql .= "where r.domain_uuid = '$domain_uuid' ";
		$sql .= "and r.meeting_uuid = u.meeting_uuid ";
		if (!if_group("admin") && !if_group("superadmin")) {
			$sql .= "and u.user_uuid = '".$_SESSION["user_uuid"]."' ";
		}
		if (strlen($order_by)> 0) { $sql .= "order by $order_by $order "; }
		$sql .= "limit $rows_per_page offset $offset ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll();
		$result_count = count($result);
		unset ($prep_statement, $sql);

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<div align='center'>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";

	//echo th_order_by('conference_center_uuid', 'Conference UUID', $order_by, $order);
	//echo th_order_by('meeting_uuid', 'Meeting UUID', $order_by, $order);
	echo th_order_by('profile', $text['label-profile'], $order_by, $order);
	echo th_order_by('record', $text['label-record'], $order_by, $order);
	//echo th_order_by('max_members', 'Max', $order_by, $order);
	echo th_order_by('wait_mod', $text['label-wait-moderator'], $order_by, $order);
	echo th_order_by('announce', $text['label-announce'], $order_by, $order);
	//echo th_order_by('enter_sound', 'Enter Sound', $order_by, $order);
	echo th_order_by('mute', $text['label-mute'], $order_by, $order);
	//echo th_order_by('created', 'Created', $order_by, $order);
	//echo th_order_by('created_by', 'Created By', $order_by, $order);
	echo th_order_by('enabled', $text['label-enabled'], $order_by, $order);
	echo "<th>".$text['label-count']."</th>\n";
	echo "<th>".$text['label-tools']."</th>\n";
	echo th_order_by('description', $text['label-description'], $order_by, $order);
	echo "<td align='right' width='42' nowrap='nowrap'>\n";
	if (permission_exists('conference_room_add')) {
		echo "	<a href='conference_room_edit.php' alt='add'>$v_link_label_add</a>\n";
	}
	else {
		echo "	&nbsp;\n";
	}
	echo "</td>\n";
	echo "<tr>\n";

	if ($result_count > 0) {
		foreach($result as $row) {
			$meeting_uuid = $row['meeting_uuid'];
			echo "<tr >\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['conference_center_uuid']."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['meeting_uuid']."&nbsp;</td>\n";
			echo "	<td valign='middle' class='".$row_style[$c]."'>".$row['profile']."&nbsp;</td>\n";
			echo "	<td valign='middle' class='".$row_style[$c]."'>";
			if ($row['record'] == "true") {
				echo "		<a href=\"?conference_room_uuid=".$row['conference_room_uuid']."&record=false\">".$text['label-true']."</a>";
			}
			else {
				echo "		<a href=\"?conference_room_uuid=".$row['conference_room_uuid']."&record=true\">".$text['label-false']."</a>";
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

			//echo "	<td valign='middle' class='".$row_style[$c]."'>".$row['enter_sound']."&nbsp;</td>\n";
			echo "	<td valign='middle' class='".$row_style[$c]."'>";
			if ($row['mute'] == "true") {
				echo "		<a href=\"?conference_room_uuid=".$row['conference_room_uuid']."&mute=false\">".$text['label-true']."</a>";
			}
			else {
				echo "		<a href=\"?conference_room_uuid=".$row['conference_room_uuid']."&mute=true\">".$text['label-false']."</a>";
			}
			echo "		&nbsp;\n";
			echo "	</td>\n";
			//echo "	<td valign='middle' class='".$row_style[$c]."'>".$row['created']."&nbsp;</td>\n";
			//echo "	<td valign='middle' class='".$row_style[$c]."'>".$row['created_by']."&nbsp;</td>\n";
			echo "	<td valign='middle' class='".$row_style[$c]."'>";
			if ($row['enabled'] == "true") {
				echo "		<a href=\"?conference_room_uuid=".$row['conference_room_uuid']."&enabled=false\">".$text['label-true']."</a>";
			}
			else {
				echo "		<a href=\"?conference_room_uuid=".$row['conference_room_uuid']."&enabled=true\">".$text['label-false']."</a>";
			}
			echo "		&nbsp;\n";
			echo "	</td>\n";
			if (strlen($conference[$meeting_uuid]["session_uuid"])) {
				echo "	<td valign='middle' class='".$row_style[$c]."'>".$conference[$meeting_uuid]["member_count"]."&nbsp;</td>\n";
			}
			else {
				echo "	<td valign='middle' class='".$row_style[$c]."'>0</td>\n";
			}
			echo "	<td valign='middle' class='".$row_style[$c]."'>\n";
			echo "		<a href='".PROJECT_PATH."/app/conferences_active/conference_interactive.php?c=".$row['meeting_uuid']."'>".$text['label-view']."</a>&nbsp;\n";
			echo "		<a href='conference_sessions.php?id=".$row['meeting_uuid']."'>".$text['label-sessions']."</a>\n";
			echo "	</td>\n";
			echo "	<td valign='middle' class='row_stylebg' width='20%' nowrap='nowrap'>".$row['description']."&nbsp;</td>\n";
			echo "	<td valign='top' align='right'>\n";
			if (permission_exists('conference_room_edit')) {
				echo "		<a href='conference_room_edit.php?id=".$row['conference_room_uuid']."' alt='edit'>$v_link_label_edit</a>\n";
			}
			if (permission_exists('conference_room_delete')) {
				echo "		<a href='conference_room_delete.php?id=".$row['conference_room_uuid']."' alt='delete' onclick=\"return confirm('Do you really want to delete this?')\">$v_link_label_delete</a>\n";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results

	echo "<tr>\n";
	echo "<td colspan='12' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap>$paging_controls</td>\n";
	echo "		<td width='33.3%' align='right'>\n";
	if (permission_exists('conference_room_add')) {
		echo "			<a href='conference_room_edit.php' alt='add'>$v_link_label_add</a>\n";
	}
	else {
		echo "			&nbsp;\n";
	}
	echo "		</td>\n";
	echo "	</tr>\n";
 	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "</div>";
	echo "<br /><br />";
	echo "<br /><br />";

	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "</div>";
	echo "<br /><br />";

//include the footer
	require_once "includes/footer.php";
?>