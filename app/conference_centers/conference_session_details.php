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
	Copyright (C) 2008-2012 All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('conference_session_view')) {
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

//set variables from the http values
	$order_by = check_str($_GET["order_by"]);
	$order = check_str($_GET["order"]);
	$conference_session_uuid = check_str($_GET["uuid"]);

//add meeting_uuid to a session variable
	if (strlen($conference_session_uuid) > 0) {
		$_SESSION['meeting']['session_uuid'] = $conference_session_uuid;
	}

//get the list
	$sql = "select * from v_conference_sessions ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and conference_session_uuid = '".$_SESSION['meeting']['session_uuid']."' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll();
	foreach ($result as &$row) {
		$meeting_uuid = $row["meeting_uuid"];
		$recording = $row["recording"];
		$start_epoch = $row["start_epoch"];
		$end_epoch = $row["end_epoch"];
		$profile = $row["profile"];
	}
	unset ($prep_statement);

//set the year, month and day based on the session start epoch
	$tmp_year = date("Y", $start_epoch);
	$tmp_month = date("M", $start_epoch);
	$tmp_day = date("d", $start_epoch);

//show the content
	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' align='left' nowrap='nowrap'><b>".$text['title-conference_session_details']."</b></td>\n";
	echo "		<td width='70%' align='right'>\n";
	$tmp_dir = $_SESSION['switch']['recordings']['dir'].'/'.$_SESSION['domain_name'].'/archive/'.$tmp_year.'/'.$tmp_month.'/'.$tmp_day;
	$tmp_name = '';
	if (file_exists($tmp_dir.'/'.$row['conference_session_uuid'].'.mp3')) {
		$tmp_name = $row['conference_session_uuid'].".mp3";
	}
	elseif (file_exists($tmp_dir.'/'.$row['conference_session_uuid'].'.wav')) {
		$tmp_name = $row['conference_session_uuid'].".wav";
	}
	if (strlen($tmp_name) > 0 && file_exists($tmp_dir.'/'.$tmp_name)) {
		if (permission_exists('conference_session_play')) {
			echo "		<a href=\"javascript:void(0);\" onclick=\"window.open('".PROJECT_PATH."/app/recordings/recording_play.php?a=download&type=moh&filename=".base64_encode('archive/'.$tmp_year.'/'.$tmp_month.'/'.$tmp_day.'/'.$tmp_name)."', 'play',' width=420,height=150,menubar=no,status=no,toolbar=no')\">\n";
			//echo "			".$text['label-play']."\n";
			echo "			<input type='button' class='btn' name='' alt='".$text['label-play']."' onclick=\"\" value='".$text['label-play']."'></a>\n";
			//echo "		\n";
			echo "		&nbsp;\n";
		}
		echo "		<a href=\"../recordings/recordings.php?a=download&type=rec&t=bin&filename=".base64_encode("archive/".$tmp_year."/".$tmp_month."/".$tmp_day."/".$tmp_name)."\">\n";
		//echo "			".$text['label-download']."\n";
		echo "			<input type='button' class='btn' name='' alt='".$text['label-download']."' onclick=\"\" value='".$text['label-download']."'></a>\n";
		//echo "		\n";
		//echo "			<input type='button' class='btn' name='' alt='".$text['label-download']."' onclick=\"window.location='".PROJECT_PATH."/app/recordings/recording_play.php?a=download&type=moh&filename=".base64_encode('archive/'.$tmp_year.'/'.$tmp_month.'/'.$tmp_day.'/'.$tmp_name)."'\" value='".$text['label-download']."'>\n";
		echo "		&nbsp;\n";
	}
	echo "			<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='conference_sessions.php'\" value='".$text['button-back']."'>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td align='left' colspan='2'>\n";
	echo "			".$text['description-conference_session_details']."<br /><br />\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";

	//prepare to page the results
		$sql = "select count(*) as num_rows from v_conference_session_details ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and conference_session_uuid = '".$_SESSION['meeting']['session_uuid']."' ";
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
		$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
		$param = "";
		$page = $_GET['page'];
		if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
		list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page);
		$offset = $rows_per_page * $page;

	//get the list
		$sql = "select * from v_conference_session_details ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and conference_session_uuid = '".$_SESSION['meeting']['session_uuid']."' ";
		if (strlen($order_by) == 0) {
			$sql .= "order by start_epoch asc ";
		}
		else {
			$sql .= "order by $order_by $order ";
		}
		$sql .= "limit $rows_per_page offset $offset ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll();
		$result_count = count($result);
		unset ($prep_statement, $sql);

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	//echo th_order_by('meeting_uuid', 'Meeting UUID', $order_by, $order);
	//echo th_order_by('conference_uuid', 'Conference UUID', $order_by, $order);
	//echo th_order_by('username', $text['label-username'], $order_by, $order);
	//echo th_order_by('uuid', $text['label-uuid'], $order_by, $order);
	echo th_order_by('caller_id_name', $text['label-caller-id-name'], $order_by, $order);
	echo th_order_by('caller_id_number', $text['label-caller-id-number'], $order_by, $order);
	echo th_order_by('moderator', $text['label-moderator'], $order_by, $order);
	echo th_order_by('network_addr', $text['label-network-address'], $order_by, $order);
	echo "<th>".$text['label-time']."</th>\n";
	echo th_order_by('start_epoch', $text['label-start'], $order_by, $order);
	echo th_order_by('end_epoch', $text['label-end'], $order_by, $order);
	if (permission_exists('conference_session_details')) {
		echo "<td class='list_control_icon'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if ($result_count > 0) {
		foreach($result as $row) {
			if (defined('TIME_24HR') && TIME_24HR == 1) {
				$start_date = date("j M Y H:i:s", $row['start_epoch']);
				$end_date = date("j M Y H:i:s", $row['end_epoch']);
			} else {
				$start_date = date("j M Y h:i:sa", $row['start_epoch']);
				$end_date = date("j M Y h:i:sa", $row['end_epoch']);
			}
			$time_difference = '';
			if (strlen($row['end_epoch']) > 0) {
				$time_difference = $row['end_epoch'] - $row['start_epoch'];
				$time_difference = gmdate("G:i:s", $time_difference);
			}
			$tr_link = (permission_exists('conference_session_details')) ? "href='/app/xml_cdr/xml_cdr_details.php?uuid=".$row['uuid']."'" : null;
			echo "<tr ".$tr_link.">\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['meeting_uuid']."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['conference_session_uuid']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['caller_id_name']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['caller_id_number']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".ucwords($row['moderator'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['network_addr']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$time_difference."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$start_date."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$end_date."&nbsp;</td>\n";
			if (permission_exists('conference_session_details')) {
				echo "	<td class='list_control_icon'>";
				echo "		<a href='/app/xml_cdr/xml_cdr_details.php?uuid=".$row['uuid']."' alt='".$text['button-view']."'>$v_link_label_view</a>";
				echo "	</td>\n";
			}
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
	echo "			&nbsp;\n";
	echo "		</td>\n";
	echo "	</tr>\n";
 	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br /><br />";

//include the footer
	require_once "resources/footer.php";
?>