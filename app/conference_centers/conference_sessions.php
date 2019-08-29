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
	Copyright (C) 2008-2019 All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
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
	$meeting_uuid = $_GET["id"];
	$order_by = $_GET["order_by"] != '' ? $_GET["order_by"] : 'start_epoch';
	$order = $_GET["order"] != '' ? $_GET["order"] : 'desc';

//add meeting_uuid to a session variable
	if (is_uuid($meeting_uuid)) {
		$_SESSION['meeting']['uuid'] = $meeting_uuid;
	}

//show the content
	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' align='left' nowrap='nowrap'><b>".$text['title-conference_sessions']."</b></td>\n";
	echo "		<td width='70%' align='right'><input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='conference_rooms.php'\" value='".$text['button-back']."'></td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td align='left' colspan='2'>\n";
	echo "			".$text['description-conference_sessions']."<br /><br />\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";

//prepare to page the results
	$sql = "select count(*) from v_conference_sessions ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and meeting_uuid = :meeting_uuid ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$parameters['meeting_uuid'] = $_SESSION['meeting']['uuid'];
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');
	unset($sql, $parameters);

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = '';
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

//get the list
	$sql = "select * from v_conference_sessions ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and meeting_uuid = :meeting_uuid ";
	$sql .= order_by($order_by, $order);
	$sql .= limit_offset($rows_per_page, $offset);
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$parameters['meeting_uuid'] = $_SESSION['meeting']['uuid'];
	$database = new database;
	$conference_sessions = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//set the row style
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//show the content
	echo "\n";
	echo "<style>\n";
	echo "audio {\n";
	echo "	width:320px;\n";
	echo "	height: 28px;\n";
	echo "	-moz-border-radius:3px;\n";
	echo "	-webkit-border-radius:3px;\n";
	echo "	border-radius:3px;\n";
	echo "	overflow:hidden;\n";
	echo "	display: block;\n";
	echo "}\n";
	echo "</style>\n";
	echo "\n";

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<th>".$text['label-time']."</th>\n";
	echo th_order_by('start_epoch', $text['label-start'], $order_by, $order);
	echo th_order_by('end_epoch', $text['label-end'], $order_by, $order);
	echo th_order_by('profile', $text['label-profile'], $order_by, $order);
	//echo th_order_by('recording', $text['label-recording'], $order_by, $order);
	echo "<th>".$text['label-tools']."</th>\n";
	echo "<td class='list_control_icon'>&nbsp;</td>\n";
	echo "</tr>\n";

	if (is_array($conference_sessions) && sizeof($conference_sessions) != 0) {
		foreach($conference_sessions as $row) {
			$tmp_year = date("Y", $row['start_epoch']);
			$tmp_month = date("M", $row['start_epoch']);
			$tmp_day = date("d", $row['start_epoch']);

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

			if (strlen( $row['start_epoch']) > 0) {
				$tr_link = "href='conference_session_details.php?uuid=".escape($row['conference_session_uuid'])."'";
				echo "<tr ".$tr_link.">\n";
				echo "	<td valign='top' class='".$row_style[$c]."'>".$time_difference."&nbsp;</td>\n";
				echo "	<td valign='top' class='".$row_style[$c]."'>".$start_date."&nbsp;</td>\n";
				echo "	<td valign='top' class='".$row_style[$c]."'>".$end_date."&nbsp;</td>\n";
				echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['profile'])."&nbsp;</td>\n";
				$tmp_dir = $_SESSION['switch']['recordings']['dir'].'/'.$_SESSION['domain_name'].'/archive/'.$tmp_year.'/'.$tmp_month.'/'.$tmp_day;
				$tmp_name = '';
				if (file_exists($tmp_dir.'/'.$row['conference_session_uuid'].'.mp3')) {
					$tmp_name = $row['conference_session_uuid'].".mp3";
				}
				elseif (file_exists($tmp_dir.'/'.$row['conference_session_uuid'].'.wav')) {
					$tmp_name = $row['conference_session_uuid'].".wav";
				}
				echo "	<td class='".$row_style[$c]."'>\n";
				if (strlen($tmp_name) > 0 && file_exists($tmp_dir.'/'.$tmp_name)) {
					echo "<table border='0' cellpadding='0' cellspacing='0'>\n";
					echo "</tr>\n";
					if (permission_exists('conference_session_play')) {
						echo "<td valign=\"bottom\">\n";
						echo "		<audio controls=\"controls\">\n";
  						echo "			<source src=\"download.php?id=".escape($row['conference_session_uuid'])."\" type=\"audio/x-wav\">\n";
						echo "		</audio>\n";
						//echo "		<a href=\"javascript:void(0);\" onclick=\"window.open('".PROJECT_PATH."/app/recordings/recording_play.php?a=download&type=moh&filename=".base64_encode('archive/'.$tmp_year.'/'.$tmp_month.'/'.$tmp_day.'/'.$tmp_name)."', 'play',' width=420,height=150,menubar=no,status=no,toolbar=no')\">\n";
						//echo "			".$text['label-play']."\n";
						//echo "		</a>\n";
						//echo "		&nbsp;\n";
						echo "</td>\n";
					}
					echo "<td>\n";
					echo "	&nbsp;\n";
					echo "</td>\n";
					echo "<td>\n";
					echo "		<a href=\"download.php?id=".escape($row['conference_session_uuid'])."\" valign='middle'>";
					//echo "			".$text['label-download']."\n";
					echo "			<input type='button' class='btn' name='' alt='".$text['label-download']."' value='".$text['label-download']."'>";
					echo "		</a>\n";
					echo "</td>\n";
					echo "</tr>\n";
					echo "</table>\n";
					//echo "		&nbsp;\n";
				}
				else {
					echo "&nbsp;";
				}
				echo "	</td>\n";
				echo "	<td class='list_control_icon'>\n";
				echo "		<a href='conference_session_details.php?uuid=".escape($row['conference_session_uuid'])."' alt='".$text['button-view']."'>\n";
				//echo "			<input type='button' class='btn' name='' alt='".$text['label-view']."' value='".$text['label-view']."'>";
				echo "			$v_link_label_view\n";
				echo "		</a>\n";
				echo "	</td>\n";
				echo "</tr>\n";
			}
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
