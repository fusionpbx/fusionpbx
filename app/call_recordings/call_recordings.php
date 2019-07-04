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
	Portions created by the Initial Developer are Copyright (C) 2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('call_recording_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the action
	if (strlen($_REQUEST["search"]) == 0 && is_array($_POST["call_recordings"])) {
		$call_recordings = $_POST["call_recordings"];
		foreach($call_recordings as $row) {
			if ($row['action'] === 'download') {
				$action = 'download';
				break;
			}
			if ($row['action'] === 'delete') {
				$action = 'delete';
				break;
			}
		}
	}

//download recordings
	if (permission_exists('call_recording_download_add')) {
		if ($action == "download") {
			//download
				$obj = new call_recording_downloads;
				$obj->save($call_recordings);
			//direct the user to the downloads
				header("Location: ".PROJECT_PATH."/app/call_recording_downloads/call_recording_downloads.php");
		}
	}

//delete the recordings
	if (permission_exists('call_recording_delete')) {
		if ($action === "delete") {
			//set the array
				$call_recordings = $_POST["call_recordings"];
			//download
				$obj = new call_recordings;
				$obj->delete($call_recordings);
			//delete message
				message::add($text['message-delete']);
		}
	}

//additional includes
	require_once "resources/header.php";
	require_once "resources/paging.php";

//get variables used to control the order
	$order_by = $_REQUEST["order_by"] != '' ? $_REQUEST["order_by"] : 'call_recording_date';
	$order = $_REQUEST["order"] != '' ? $_REQUEST["order"] : 'desc';

	//add the search term
	$search = strtolower($_REQUEST["search"]);
	if (strlen($search) > 0) {
		$sql_search = "and (";
		$sql_search .= "lower(call_recording_name) like :search ";
		$sql_search .= "or lower(call_recording_path) like :search ";
		$sql_search .= "or lower(call_direction) like :search ";
		$sql_search .= "or lower(call_recording_description) like :search ";
		$sql_search .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}

//prepare to page the results
	$sql = "select count(call_recording_uuid) from v_call_recordings ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= $sql_search;
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');
	unset($sql);

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "";
	$page = $_REQUEST['page'];
	if (strlen($page) == 0) { $page = 0; $_REQUEST['page'] = 0; }
	list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

//get the list
	$sql = "select * from v_call_recordings ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= $sql_search;
	$sql .= order_by($order_by, $order);
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$result = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//alternate the row style
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//define the checkbox_toggle function
	echo "<script type=\"text/javascript\">\n";
	echo "	function checkbox_toggle(item) {\n";
	echo "		var inputs = document.getElementsByTagName(\"input\");\n";
	echo "		for (var i = 0, max = inputs.length; i < max; i++) {\n";
	echo "			if (inputs[i].type === 'checkbox') {\n";
	echo "				if (document.getElementById('checkbox_all').checked == true) {\n";
	echo "				inputs[i].checked = true;\n";
	echo "			}\n";
	echo "				else {\n";
	echo "					inputs[i].checked = false;\n";
	echo "				}\n";
	echo "			}\n";
	echo "		}\n";
	echo "	}\n";
	echo "</script>\n";

//show the content
	echo "<form method='post' action=''>\n";
	echo "<table width='100%' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' align='left' nowrap='nowrap'><b>".$text['title-call_recordings']." (".$num_rows.")</b></td>\n";
	echo "			<td width='50%' style='vertical-align: top; text-align: right; white-space: nowrap;'>\n";
	if (permission_exists('call_recording_download_add')) {
		echo "				<button type='submit' class='btn btn-default' id='downloads' name=\"call_recordings[$x][action]\" alt='".$text['button-download']."' onclick=\"document.getElementById('downloads').value='download'\" value=''>".$text['button-downloads']."</span></button>\n";
		echo "				&nbsp; &nbsp; &nbsp; ";
	}
	echo "				<input type='text' class='txt' style='width: 150px' name='search' id='search' value='".escape($search)."'>\n";
	echo "				<input type='submit' class='btn' name='submit' value='".$text['button-search']."'>\n";
	echo "			</td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td align='left' colspan='2'>\n";
	echo "			".$text['title_description-call_recording']."<br /><br />\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";

	//echo "<style>\n";
	//echo "audio {\n";
	//echo "    background-color: #ffffff;\n";
	//echo "    background-color: rgba(0,0,0,0);\n";
	//echo "    -webkit-border-radius:7px 7px 7px 7px ;\n";
	//echo "    border-radius:7px 7px 7px 7px ;\n";
	//echo "}\n";
	//echo "</style>\n";
	echo "\n";

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "	<th style='width:30px;'>\n";
	echo "		<input type='checkbox' name='checkbox_all' id='checkbox_all' value='' onclick=\"checkbox_toggle();\">\n";
	echo "	</th>\n";
	echo th_order_by('call_recording_name', $text['label-call_recording_name'], $order_by, $order);
	echo "<th>".$text['label-recording']."</th>\n";
	//echo th_order_by('call_recording_path', $text['label-play'], $order_by, $order);
	echo th_order_by('call_recording_length', $text['label-call_recording_length'], $order_by, $order);
	echo th_order_by('call_recording_date', $text['label-call_recording_date'], $order_by, $order);
	echo th_order_by('call_direction', $text['label-call_direction'], $order_by, $order);
	echo th_order_by('call_recording_description', $text['label-call_recording_description'], $order_by, $order);
	//echo th_order_by('call_recording_base64', $text['label-call_recording_base64'], $order_by, $order);
	echo "	<td class='list_control_icons'>";
	if (permission_exists('call_recording_add')) {
		echo "		<a href='call_recording_edit.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	else {
		echo "&nbsp;\n";
	}
	echo "	</td>\n";
	echo "<tr>\n";

	if (is_array($result)) {
		$x = 0;
		foreach($result as $row) {
			//if (permission_exists('call_recording_play') && $recording_file_path != '') {
			//	echo "<tr id='recording_progress_bar_".escape($row['call_recording_uuid'])."' style='display: none3;'><td class='".$row_style[$c]." playback_progress_bar_background' style='padding: 0; border: none;' colspan='".((if_group("admin") || if_group("superadmin") || if_group("cdr")) ? ($col_count - 1) : $col_count)."'><span class='playback_progress_bar' id='recording_progress_".escape($row['call_recording_uuid'])."'></span></td></tr>\n";
			//}
			if (permission_exists('call_recording_edit')) {
				$tr_link = "href='call_recording_edit.php?id=".$row['call_recording_uuid']."'";
			}
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]." tr_link_void' style='align: center; padding: 3px 3px 0px 8px;'>\n";
			echo "		<input type='checkbox' name=\"call_recordings[$x][checked]\" id='checkbox_".$x."' value='true' onclick=\"if (!this.checked) { document.getElementById('chk_all_".$x."').checked = false; }\">\n";
			echo "		<input type='hidden' name=\"call_recordings[$x][call_recording_uuid]\" value='".escape($row['call_recording_uuid'])."' />\n";
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['call_recording_name'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]." row_style_slim tr_link_void' valign='top' align='center' nowrap='nowrap'>\n";
			//echo "		<audio controls=\"download\" preload=\"metadata\" style=\"width:200px;\">\n";
			//echo "			<source src=\"download.php?id=".escape($row['call_recording_uuid'])."\" type=\"audio/wav\">\n";
			//echo "		</audio>\n";
			//echo "		<a href=\"download.php?id=".escape($row['call_recording_uuid'])."&t=bin\">".$text['label-download']." ".$v_link_label_download."</a>\n";
			if (file_exists($row['call_recording_path'].'/'.$row['call_recording_name'])) {	
				if (permission_exists('call_recording_play')) {
					echo 	"<audio id='recording_audio_".escape($row['call_recording_uuid'])."' style='display: none;' preload='none' ontimeupdate=\"update_progress('".escape($row['call_recording_uuid'])."')\" onended=\"recording_reset('".escape($row['call_recording_uuid'])."');\" src=\"download.php?id=".escape($row['call_recording_uuid'])."\" type='".$recording_type."'></audio>";
					echo 	"<span id='recording_button_".escape($row['call_recording_uuid'])."' onclick=\"recording_play('".escape($row['call_recording_uuid'])."')\" title='".$text['label-play']." / ".$text['label-pause']."'>".$v_link_label_play."</span>";
				}
				if (permission_exists('call_recording_download')) {
					echo 	"<a href=\"download.php?id=".escape($row['call_recording_uuid'])."&t=bin\" title='".$text['label-download']."'>".$v_link_label_download."</a>";
				}
			}
			echo "	</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."' style=\"\">\n";
			//echo "		<a href=\"download.php?id=".escape($row['call_recording_uuid'])."&t=bin\">".$text['label-download']." ".$v_link_label_download."</a>\n";
			//echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['call_recording_length'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['call_recording_date'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['call_direction'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='row_stylebg'>".escape($row['call_recording_description'])."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['call_recording_base64'])."&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>";
			if (permission_exists('xml_cdr_details')) {
				echo "		<a href='/app/xml_cdr/xml_cdr_details.php?id=".escape($row['call_recording_uuid'])."' title='".$text['button-view']."'>$v_link_label_view</a>";
			}
			if (permission_exists('call_recording_edit')) {
				echo "<button type='button' class='btn btn-default list_control_icon' name='' alt='".$text['button-edit']."' onclick=\"window.location='call_recording_edit.php?id=".escape($row['call_recording_uuid'])."'\" value='edit'><span class='glyphicon glyphicon-pencil'></span></input>\n";
			}
			if (permission_exists('call_recording_delete')) {
				echo "<button type='submit' class='btn btn-default list_control_icon' name=\"call_recordings[$x][action]\" alt='".$text['button-delete']."' value='delete'><span class='glyphicon glyphicon-remove'></span></button>\n";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			$x++;
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($result);
	} //end if results

	echo "<tr>\n";
	echo "<td colspan='9' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap='nowrap'>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap='nowrap'>&nbsp;</td>\n";
	echo "		<td class='list_control_icons'>";
	if (permission_exists('call_recording_add')) {
		echo 		"<a href='call_recording_edit.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	else {
		echo 		"&nbsp;";
	}
	echo "		</td>\n";
	echo "	</tr>\n";
 	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>";
	echo "</form>\n";

	if (strlen($paging_controls) > 0) {
		echo "<br />";
		echo $paging_controls."\n";
	}

	echo "<br /><br />";

//include the footer
	require_once "resources/footer.php";

?>
