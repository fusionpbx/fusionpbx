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
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('stream_view')) {
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
	if (is_array($_POST["streams"])) {
		$streams = $_POST["streams"];
		foreach($streams as $row) {
			if ($row['action'] == 'delete') {
				$action = 'delete';
				break;
			}
		}
	}

//delete the streams
	if (permission_exists('stream_delete')) {
		if ($action == "delete") {
			//download
				$obj = new streams;
				$obj->delete($streams);
			//delete message
				message::add($text['message-delete']);
			//redirect
				header('Location: streams.php');
				exit;
		}
	}

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//add the search term
	$search = strtolower($_GET["search"]);
	if (strlen($search) > 0) {
		$sql_search = "and (";
		$sql_search .= "lower(stream_name) like :search ";
		$sql_search .= "or lower(stream_location) like :search ";
		$sql_search .= "or lower(stream_enabled) like :search ";
		$sql_search .= "or lower(domain_uuid) like :search ";
		$sql_search .= "or lower(stream_description) like :search ";
		$sql_search .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}

//additional includes
	require_once "resources/header.php";
	require_once "resources/paging.php";

//prepare to page the results
	$sql = "select count(*) from v_streams ";
	$sql .= "where true ";
	$sql .= $sql_search;
	if (!($_GET['show'] == "all" && permission_exists('stream_all'))) {
		$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	$database = new database;
	$num_rows = $database->select($sql, (is_array($parameters) && @sizeof($parameters) != 0 ? $parameters : null), 'column');

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "&search=".$search;
	if ($_GET['show'] == "all" && permission_exists('stream_all')) {
		$param .= "&show=all";
	}
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

//get the list
	$sql = str_replace('count(*)', '*', $sql);
	$sql .= order_by($order_by, $order);
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$streams = $database->select($sql, (is_array($parameters) && @sizeof($parameters) != 0 ? $parameters : null), 'all');
	unset($sql, $parameters);

//alternate the row style
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//audio control styles
	echo "<style>\n";
	echo "	audio {\n";
	echo "		margin-bottom: -5px;\n";
	echo "		width: 100%;\n";
	echo "		height: 35px;\n";
	echo "	}\n";
	echo "</style>\n";

//define the checkbox_toggle function
	echo "<script type=\"text/javascript\">\n";
	echo "	function checkbox_toggle(item) {\n";
	echo "		var inputs = document.getElementsByTagName(\"input\");\n";
	echo "		for (var i = 0, max = inputs.length; i < max; i++) {\n";
	echo "		    if (inputs[i].type === 'checkbox') {\n";
	echo "		       	if (document.getElementById('checkbox_all').checked == true) {\n";
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
	echo "<table width='100%' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' align='left' nowrap='nowrap'><b>".$text['title-streams']."</b></td>\n";
	echo "		<form method='get' action=''>\n";
	echo "			<td width='50%' style='vertical-align: top; text-align: right; white-space: nowrap;'>\n";

	if (permission_exists('stream_all')) {
		if ($_GET['show'] == 'all') {
			echo "		<input type='hidden' name='show' value='all'>";
		}
		else {
			echo "		<input type='button' class='btn' value='".$text['button-show_all']."' onclick=\"window.location='streams.php?show=all';\">\n";
		}
	}

	echo "				<input type='text' class='txt' style='width: 150px; margin-left: 15px;' name='search' id='search' value='".escape($search)."'>\n";
	echo "				<input type='submit' class='btn' name='submit' value='".$text['button-search']."'>\n";
	echo "			</td>\n";
	echo "		</form>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td align='left' colspan='2'>\n";
	echo "			".$text['title_description-stream']."<br /><br />\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";

	echo "<form method='post' action=''>\n";
	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "	<th style='width:30px;'>\n";
	echo "		<input type='checkbox' name='checkbox_all' id='checkbox_all' value='' onclick=\"checkbox_toggle();\">\n";
	echo "	</th>\n";
	if ($_GET['show'] == "all" && permission_exists('stream_all')) {
		echo th_order_by('domain_name', $text['label-domain'], $order_by, $order, $param);
	}
	echo th_order_by('stream_name', $text['label-stream_name'], $order_by, $order);
	echo "	<th>".$text['label-play']."</th>\n";
	//echo th_order_by('stream_location', $text['label-stream_location'], $order_by, $order);
	echo th_order_by('stream_enabled', $text['label-stream_enabled'], $order_by, $order);
	echo th_order_by('stream_description', $text['label-stream_description'], $order_by, $order);
	echo "	<td class='list_control_icons'>";
	if (permission_exists('stream_add')) {
		echo "		<a href='stream_edit.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	else {
		echo "&nbsp;\n";
	}
	echo "	</td>\n";
	echo "<tr>\n";

	if (is_array($streams)) {
		$x = 0;
		foreach($streams as $row) {
			if (permission_exists('stream_edit')) {
				$tr_link = "href='stream_edit.php?id=".escape($row['stream_uuid'])."'";
			}
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]." tr_link_void' style='align: center; padding: 3px 3px 0px 8px;'>\n";
			echo "		<input type='checkbox' name=\"streams[$x][checked]\" id='checkbox_".$x."' value='true' onclick=\"if (!this.checked) { document.getElementById('chk_all_".$x."').checked = false; }\">\n";
			echo "		<input type='hidden' name=\"streams[$x][stream_uuid]\" value='".escape($row['stream_uuid'])."' />\n";
			echo "	</td>\n";
			if ($_GET['show'] == "all" && permission_exists('stream_all')) {
				if (strlen($_SESSION['domains'][$row['domain_uuid']]['domain_name']) > 0) {
					$domain = $_SESSION['domains'][$row['domain_uuid']]['domain_name'];
				}
				else {
					$domain = $text['label-global'];
				}
				echo "	<td valign='top' class='".$row_style[$c]."' style='width: 15%; white-space: nowrap;'>".$domain."</td>\n";
			}
			echo "	<td valign='top' class='".$row_style[$c]."' style='width: 15%; white-space: nowrap;'>".escape($row['stream_name'])."&nbsp;</td>\n";

			echo "	<td valign='top' class='".$row_style[$c]."' style='white-space: nowrap; padding: 0;'>\n";
			if (strlen($row['stream_location']) > 0) {
				$location_parts = explode('://',$row['stream_location']);
				if ($location_parts[0] == "shout") {
					echo "<audio src=\"http://".$location_parts[1]."\" controls=\"controls\" />\n";
				}
			}
			echo "	</td>\n";

			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['stream_location'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."' style='white-space: nowrap;'>".($row['stream_enabled'] == 'true' ? $text['label-true'] : $text['label-false'])."</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['domain_uuid'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='row_stylebg' style='width: 20%; white-space: nowrap;'>".escape($row['stream_description'])."&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>";
			if (permission_exists('stream_edit')) {
				echo "<a href='stream_edit.php?id=".$row['stream_uuid']."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
			if (permission_exists('stream_delete')) {
				echo "<button type='submit' class='btn btn-default list_control_icon' name=\"streams[$x][action]\" alt='".$text['button-delete']."' value='delete'><span class='fas fa-minus'></span></button>";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			$x++;
			$c = $c ? 0 : 1;
		}
	}
	unset($streams, $row);

	echo "<tr>\n";
	echo "<td colspan='7' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap='nowrap'>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap='nowrap'>$paging_controls</td>\n";
	echo "		<td class='list_control_icons'>";
	if (permission_exists('stream_add')) {
		echo 		"<a href='stream_edit.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
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
	echo "<br /><br />";

//include the footer
	require_once "resources/footer.php";

?>