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
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('conference_session_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}
require_once "includes/header.php";
require_once "includes/paging.php";

//set variables from the http values
	$order_by = check_str($_GET["order_by"]);
	$order = check_str($_GET["order"]);

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"center\">\n";
	echo "		<br />";

	echo "<table width='100%' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' align='left' nowrap='nowrap'><b>Conference Sessions</b></td>\n";
	echo "		<td width='70%' align='right'><input type='button' class='btn' name='' alt='back' onclick=\"window.location='conference_centers.php'\" value='Back'></td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td align='left' colspan='2'>\n";
	echo "			Conference Sessions are log details about the conferences.<br /><br />\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";

	//prepare to page the results
		$sql = "select count(*) as num_rows from v_conference_sessions ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
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
		$rows_per_page = 150;
		$param = "";
		$page = $_GET['page'];
		if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; } 
		list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page); 
		$offset = $rows_per_page * $page; 

	//get the list
		$sql = "select * from v_conference_sessions ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		if (strlen($order_by) == 0) {
			$sql .= "order by start_epoch desc ";
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

	echo "<div align='center'>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	//echo th_order_by('meeting_uuid', 'Meeting UUID', $order_by, $order);
	echo "<th>Time</th>\n";
	echo th_order_by('start_epoch', 'Start', $order_by, $order);
	echo th_order_by('end_epoch', 'End', $order_by, $order);
	echo th_order_by('moderator', 'Moderator', $order_by, $order);
	echo th_order_by('recording', 'Recording', $order_by, $order);
	echo "<th>Tools</th>\n";
	echo "<tr>\n";

	if ($result_count > 0) {
		foreach($result as $row) {
			$tmp_year = date("Y", strtotime($row['start_epoch']));
			$tmp_month = date("M", strtotime($row['start_epoch']));
			$tmp_day = date("d", strtotime($row['start_epoch']));

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

			echo "<tr >\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['meeting_uuid']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$time_difference."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$start_date."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$end_date."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['moderator']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>\n";
/*
			$tmp_dir = $_SESSION['switch']['recordings']['dir'].'/archive/'.$tmp_year.'/'.$tmp_month.'/'.$tmp_day;
			$tmp_name = '';
			elseif (file_exists($tmp_dir.'/'.$row['conference_session_uuid'].'.wav')) {
				$tmp_name = $row['conference_session_uuid'].".wav";
			}
			elseif (file_exists($tmp_dir.'/'.$row['conference_session_uuid'].'.mp3')) {
				$tmp_name = $row['conference_session_uuid'].".mp3";
			}

			echo "	<td valign='top' class='".$row_style[$c]."'>\n";
			if (strlen($tmp_name) > 0 && file_exists($tmp_dir.'/'.$tmp_name)) {
				echo "		<a href=\"javascript:void(0);\" onclick=\"window.open('".PROJECT_PATH."/app/recordings/v_recordings_play.php?a=download&type=moh&filename=".base64_encode('archive/'.$tmp_year.'/'.$tmp_month.'/'.$tmp_day.'/'.$tmp_name)."', 'play',' width=420,height=150,menubar=no,status=no,toolbar=no')\">\n";
				echo "			play\n";
				echo "		</a>\n";
				echo "		&nbsp;\n";
				echo "		<a href=\"../recordings/v_recordings.php?a=download&type=rec&t=bin&filename=".base64_encode("archive/".$tmp_year."/".$tmp_month."/".$tmp_day."/".$tmp_name)."\">\n";
				echo "			download\n";
				echo "		</a>\n";
				echo "		&nbsp;\n";
			}
			echo "		<a href='conference_session_details.php?uuid=".$row['conference_session_uuid']."'>Details</a>&nbsp;\n";
			echo "	</td>\n";
*/
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