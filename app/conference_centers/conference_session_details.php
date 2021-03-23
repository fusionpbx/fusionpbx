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
	require_once "resources/paging.php";

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

//set variables from the http values
	$order_by = $_GET["order_by"] != '' ? $_GET["order_by"] : 'start_epoch';
	$order = $_GET['order'] != '' ? $_GET['order'] : 'asc';
	$conference_session_uuid = $_GET["uuid"];

//add meeting_uuid to a session variable
	if (is_uuid($conference_session_uuid)) {
		$_SESSION['meeting']['session_uuid'] = $conference_session_uuid;
	}

//get the list
	$sql = "select * from v_conference_sessions ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and conference_session_uuid = :conference_session_uuid ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$parameters['conference_session_uuid'] = $_SESSION['meeting']['session_uuid'];
	$database = new database;
	$row = $database->select($sql, $parameters, 'row');
	if (is_array($row) && sizeof($row) != 0) {
		$meeting_uuid = $row["meeting_uuid"];
		$recording = $row["recording"];
		$start_epoch = $row["start_epoch"];
		$end_epoch = $row["end_epoch"];
		$profile = $row["profile"];
	}
	unset($sql, $parameters, $row);

//set the year, month and day based on the session start epoch
	$tmp_year = date("Y", $start_epoch);
	$tmp_month = date("M", $start_epoch);
	$tmp_day = date("d", $start_epoch);

//prepare to page the results
	$sql = "select count(*) from v_conference_session_details ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and conference_session_uuid = :conference_session_uuid ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$parameters['conference_session_uuid'] = $_SESSION['meeting']['session_uuid'];
	$num_rows = $database->select($sql, $parameters, 'column');
	unset($sql, $parameters);

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = '';
	$page = is_numeric($_GET['page']) ? $_GET['page'] : 0;
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the list
	$sql = "select * from v_conference_session_details ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and conference_session_uuid = :conference_session_uuid ";
	$sql .= order_by($order_by, $order);
	$sql .= limit_offset($rows_per_page, $offset);
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$parameters['conference_session_uuid'] = $_SESSION['meeting']['session_uuid'];
	$conference_session_details = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//include the header
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-conference_session_details']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','link'=>'conference_sessions.php']);
	$tmp_dir = $_SESSION['switch']['recordings']['dir'].'/'.$_SESSION['domain_name'].'/archive/'.$tmp_year.'/'.$tmp_month.'/'.$tmp_day;
	$tmp_name = '';
	if (file_exists($tmp_dir.'/'.$row['conference_session_uuid'].'.mp3')) {
		$tmp_name = $row['conference_session_uuid'].".mp3";
	}
	elseif (file_exists($tmp_dir.'/'.$row['conference_session_uuid'].'.wav')) {
		$tmp_name = $row['conference_session_uuid'].".wav";
	}
	if (strlen($tmp_name) > 0 && file_exists($tmp_dir.'/'.$tmp_name)) {
		echo button::create(['type'=>'button','label'=>$text['button-download'],'icon'=>$_SESSION['theme']['button_icon_download'],'style'=>'margin-left: 15px;','link'=>'../recordings/recordings.php?a=download&type=rec&t=bin&filename='.base64_encode('archive/'.$tmp_year.'/'.$tmp_month.'/'.$tmp_day.'/'.$tmp_name)]);
		if (permission_exists('conference_session_play')) {
			echo button::create(['type'=>'button','label'=>$text['button-play'],'icon'=>$_SESSION['theme']['button_icon_play'],'onclick'=>"window.open('".PROJECT_PATH."/app/recordings/recording_play.php?a=download&type=moh&filename=".urlencode('archive/'.$tmp_year.'/'.$tmp_month.'/'.$tmp_day.'/'.$tmp_name)."', 'play',' width=420,height=150,menubar=no,status=no,toolbar=no');"]);
		}
	}
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['description-conference_session_details']."\n";
	echo "<br /><br />\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
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
	if (permission_exists('conference_session_details') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($conference_session_details) && sizeof($conference_session_details) != 0) {
		foreach($conference_session_details as $row) {
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
			if (permission_exists('conference_session_details')) {
				$list_row_url = "../xml_cdr/xml_cdr_details.php?id=".urlencode($row['uuid']);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			//echo "	<td>".$row['meeting_uuid']."&nbsp;</td>\n";
			//echo "	<td>".$row['conference_session_uuid']."&nbsp;</td>\n";
			echo "	<td>";
			if (permission_exists('conference_session_details')) {
				echo "	<a href='".$list_row_url."' title=\"".$text['button-view']."\">".escape($row['caller_id_name'])."</a>\n";
			}
			else {
				echo "	".escape($row['caller_id_name']);
			}
			echo "&nbsp;</td>\n";
			echo "	<td>".escape($row['caller_id_number'])."&nbsp;</td>\n";
			echo "	<td>".ucwords(escape($row['moderator']))."&nbsp;</td>\n";
			echo "	<td>".escape($row['network_addr'])."&nbsp;</td>\n";
			echo "	<td>".$time_difference."&nbsp;</td>\n";
			echo "	<td>".$start_date."&nbsp;</td>\n";
			echo "	<td>".$end_date."&nbsp;</td>\n";
			if (permission_exists('conference_session_details') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>\n";
				echo button::create(['type'=>'button','title'=>$text['button-view'],'icon'=>$_SESSION['theme']['button_icon_view'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";

		}
		unset($conference_session_details);
	}

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";

//include the footer
	require_once "resources/footer.php";

?>
