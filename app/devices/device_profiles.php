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
	Copyright (C) 2019 All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('device_profile_view')) {
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
	if (is_array($_POST["device_profiles"])) {
		$device_profiles = $_POST["device_profiles"];
		foreach($device_profiles as $row) {
			if ($row['action'] == 'delete') {
				$action = 'delete';
				break;
			}
		}
	}

//delete the device_profiles
	if (permission_exists('device_profile_delete')) {
		if ($action == "delete") {
			//download
				$obj = new device_profiles;
				$obj->delete($device_profiles);
			//delete message
				message::add($text['message-delete']);
		}
	}

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//validate the order by
	if (strlen($order_by) > 0) {
		$order_by = preg_replace('#[^a-zA-Z0-9_\-]#', '', $order_by);
	}

//validate the order
	switch ($order) {
		case 'asc':
			break;
		case 'desc':
			break;
		default:
			$order = '';
	}

//search string
	if (isset($_GET["search"])) {
		$search =  strtolower($_GET["search"]);
	}

//add the search
	if (isset($search)) {
		$sql_search = "and (";
		$sql_search .= "	lower(device_profile_name) like :search ";
		$sql_search .= "	or lower(device_profile_description) like :search ";
		$sql_search .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}

//additional includes
	require_once "resources/header.php";
	require_once "resources/paging.php";

//get the count
	$sql = "select count(device_profile_uuid) from v_device_profiles ";
	if ($_GET['show'] == "all" && permission_exists('device_profile_all')) {
		$sql .= "where 1 = 1 ";
	}
	else {
		$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	if (isset($sql_search)) {
		$sql .= $sql_search;
	}
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "&search=".$search;
	if ($_GET['show'] == "all" && permission_exists('device_profile_all')) {
		$param .= "&show=all";
	}
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls_mini, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page, true); //top
	list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page); //bottom
	$offset = $rows_per_page * $page;

//get the list
	$sql = "select * from v_device_profiles ";
	if ($_GET['show'] == "all" && permission_exists('device_profile_all')) {
		$sql .= "where 1 = 1 ";
	}
	else {
		$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	if (isset($sql_search)) {
		$sql .= $sql_search;
	}
	if (strlen($order_by) > 0) { $sql .= "order by $order_by $order "; }
	$sql .= "limit :rows_per_page offset :offset ";

	$parameters['rows_per_page'] = $rows_per_page;
	$parameters['offset'] = $offset;
	$database = new database;
	$device_profiles = $database->select($sql, $parameters, 'all');
	unset ($sql, $parameters);

//alternate the row style
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//define the checkbox_toggle function
	//echo "<script type=\"text/javascript\">\n";
	//echo "	function checkbox_toggle(item) {\n";
	//echo "		var inputs = document.getElementsByTagName(\"input\");\n";
	//echo "		for (var i = 0, max = inputs.length; i < max; i++) {\n";
	//echo "			if (inputs[i].type === 'checkbox') {\n";
	//echo "				if (document.getElementById('checkbox_all').checked == true) {\n";
	//echo "				inputs[i].checked = true;\n";
	//echo "			}\n";
	//echo "				else {\n";
	//echo "					inputs[i].checked = false;\n";
	//echo "				}\n";
	//echo "			}\n";
	//echo "		}\n";
	//echo "	}\n";
	//echo "</script>\n";

//show the content
	echo "<table width='100%' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' align='left' nowrap='nowrap'>\n";
	echo "			<b>".$text['title-device_profiles']." (".$num_rows.")</b>\n";
	echo "		</td>\n";
	echo "		<form method='get' action=''>\n";
	echo "			<td width='50%' style='vertical-align: top; text-align: right; white-space: nowrap;'>\n";

	if (permission_exists('device_profile_all')) {
		if ($_GET['show'] == 'all') {
			echo "		<input type='hidden' name='show' value='all'>";
		}
		else {
			echo "		<input type='button' class='btn' value='".$text['button-show_all']."' onclick=\"window.location='device_profiles.php?show=all';\">\n";
		}
	}

	//add buttons
	if (!isset($id)) {
		echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick='window.location=\"/app/devices/devices.php\"' value='".$text['button-back']."'>";
	}
	echo "				<input type='text' class='txt' style='width: 150px; margin-left: 15px;' name='search' id='search' value='".escape($search)."'>\n";
	echo "				<input type='submit' class='btn' name='submit' value='".$text['button-search']."'>\n";
	echo "			</td>\n";
	echo "		</form>\n";
	if ($paging_controls_mini != '') {
		echo "	<td valign='top' nowrap='nowrap' style='padding-left: 15px;'>".$paging_controls_mini."</td>\n";
	}
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td align='left' colspan='2'>\n";
	echo "			".$text['description-device_profiles']."<br /><br />\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";

	echo "<form method='post' action=''>\n";
	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	//echo "	<th style='width:30px;'>\n";
	//echo "		<input type='checkbox' name='checkbox_all' id='checkbox_all' value='' onclick=\"checkbox_toggle();\">\n";
	//echo "	</th>\n";
	if ($_GET['show'] == "all" && permission_exists('device_profile_all')) {
		echo th_order_by('domain_name', $text['label-domain'], $order_by, $order, $param);
	}
	echo th_order_by('device_profile_name', $text['label-device_profile_name'], $order_by, $order);
	echo th_order_by('device_profile_enabled', $text['label-device_profile_enabled'], $order_by, $order);
	echo th_order_by('device_profile_description', $text['label-device_profile_description'], $order_by, $order);
	echo "	<td class='list_control_icons'>";
	if (permission_exists('device_profile_add')) {
		echo "		<a href='device_profile_edit.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	else {
		echo "&nbsp;\n";
	}
	echo "	</td>\n";
	echo "</tr>\n";

	if (is_array($device_profiles) && @sizeof($device_profiles) != 0) {
		$x = 0;
		foreach($device_profiles as $row) {
			if (permission_exists('device_profile_edit')) {
				$tr_link = "href='device_profile_edit.php?id=".escape($row['device_profile_uuid'])."'";
			}
			echo "<tr ".$tr_link.">\n";
			//echo "	<td valign='top' class='".$row_style[$c]." tr_link_void' style='align: center; padding: 3px 3px 0px 8px;'>\n";
			//echo "		<input type='checkbox' name=\"device_profiles[$x][checked]\" id='checkbox_".$x."' value='true' onclick=\"if (!this.checked) { document.getElementById('chk_all_".$x."').checked = false; }\">\n";
			//echo "		<input type='hidden' name=\"device_profiles[$x][device_profile_uuid]\" value='".escape($row['device_profile_uuid'])."' />\n";
			//echo "	</td>\n";
			if ($_GET['show'] == "all" && permission_exists('device_profile_all')) {
				if (strlen($_SESSION['domains'][$row['domain_uuid']]['domain_name']) > 0) {
					$domain = $_SESSION['domains'][$row['domain_uuid']]['domain_name'];
				}
				else {
					$domain = $text['label-global'];
				}
				echo "	<td valign='top' class='".$row_style[$c]."'>".escape($domain)."</td>\n";
			}
			echo "	<td valign='top' class='".$row_style[$c]."' style=''>".escape($row['device_profile_name'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."' style=''>".escape($row['device_profile_enabled'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='row_stylebg' style=''>".escape($row['device_profile_description'])."&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>";
			if (permission_exists('device_profile_edit')) {
				echo "<a href='device_profile_edit.php?id=".escape($row['device_profile_uuid'])."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
			if (permission_exists('device_profile_delete')) {
				echo "				<a href=\"device_profile_delete.php?id=".escape($row['device_profile_uuid'])."&amp;a=delete\" alt='delete' onclick=\"return confirm('Do you really want to delete this?')\">".$v_link_label_delete."</a>\n";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			$x++;
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $device_profiles);
	} //end if results

	echo "<tr>\n";
	echo "	<td colspan='3' align='center'>\n";
	echo "		<br />\n";
	echo "		".$paging_controls;
	echo "	</td>\n";
	echo "</tr>\n";
	echo "</table>";
	echo "</form>\n";
	echo "<br /><br />";

//include the footer
	require_once "resources/footer.php";

?>
