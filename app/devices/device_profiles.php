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

//get the http values and set them as variables
	$search = $_GET["search"];
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//additional includes
	require_once "resources/header.php";
	$document['title'] = $text['title-profiles'];
	require_once "resources/paging.php";

//common sql
	$sql_where = "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
	if ($search != '') {
		$sql_where .= "and (";
		$sql_where .= " 	device_profile_name like :search ";
		$sql_where .= " 	or device_profile_description like :search ";
		$sql_where .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	$parameters['domain_uuid'] = $domain_uuid;

//prepare to page the results
	$sql = "select count(*) from v_device_profiles ";
	$sql .= $sql_where;
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "";
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

//get the device profiles
	$sql = str_replace('count(*)', '*', $sql);
	$sql .= order_by($order_by, $order, 'device_profile_name');
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$device_profiles = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//set row styles
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//show the content
	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='100%' align='left' valign='top'>";
	echo "			<b>".$text['header-profiles']."</b>";
	echo "			<br /><br />";
	echo "			".$text['description-profiles'];
	echo "		</td>\n";
	echo "		<td align='right' nowrap='nowrap' valign='top'>\n";
	echo "			<form method='get' action=''>\n";
	echo "			<input type='button' class='btn' alt='".$text['button-back']."' onclick=\"document.location='devices.php'\" value='".$text['button-back']."'>&nbsp;&nbsp;&nbsp;&nbsp;";
	echo "			<input type='text' class='txt' style='width: 150px' name='search' value='".escape($search)."'>";
	echo "			<input type='submit' class='btn' name='submit' value='".$text['button-search']."'>";
	echo "			</form>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "<br />";

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo th_order_by('name', $text['label-profile_name'], $order_by, $order);
	echo th_order_by('enabled', $text['label-profile_enabled'], $order_by, $order);
	echo th_order_by('description', $text['label-profile_description'], $order_by, $order);
	echo "<td class='list_control_icons'>\n";
	if (permission_exists('device_profile_add')) {
		echo "	<a href='device_profile_edit.php' alt='".$text['button-add']."'>".$v_link_label_add."</a>\n";
	}
	echo "</td>\n";
	echo "<tr>\n";

	if (is_array($device_profiles) && @sizeof($device_profiles) != 0) {
		foreach($device_profiles as $row) {
			$tr_link = (permission_exists('device_profile_edit')) ? "href='device_profile_edit.php?id=".escape($row['device_profile_uuid'])."'" : null;
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			echo (permission_exists('device_profile_edit')) ? "<a href='device_profile_edit.php?id=".escape($row['device_profile_uuid'])."'>".escape($row['device_profile_name'])."</a>" : escape($row['device_profile_name']);
			echo !is_uuid($row['domain_uuid']) ? "&nbsp;&nbsp;&nbsp;&nbsp;<span style='color: #888; font-size: 80%'>".$text['select-global']."</span>" : null;
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$text['label-'.escape($row['device_profile_enabled'])]."&nbsp;</td>\n";
			echo "	<td valign='top' class='row_stylebg'>".escape($row['device_profile_description'])."&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>";
			if (permission_exists('device_profile_edit')) {
				echo "<a href='device_profile_edit.php?id=".escape($row['device_profile_uuid'])."' alt='".$text['button-edit']."'>".$v_link_label_edit."</a>";
			}
			if (permission_exists('device_profile_delete')) {
				echo "<a href='device_profile_delete.php?id=".escape($row['device_profile_uuid'])."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">".$v_link_label_delete."</a>";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			$c = ($c == 0) ? 1 : 0;
		}
	}
	unset($device_profiles);

	echo "<tr>\n";
	echo "<td colspan='4'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap='nowrap'>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap='nowrap'>".$paging_controls."</td>\n";
	echo "		<td class='list_control_icons'>";
	if (permission_exists('device_profile_add')) {
		echo "		<a href='device_profile_edit.php' alt='".$text['button-add']."'>".$v_link_label_add."</a>";
	}
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
