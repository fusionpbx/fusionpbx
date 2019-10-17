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
	Portions created by the Initial Developer are Copyright (C) 2008-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('exec_sql')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//add the header and title
	require_once "resources/header.php";
	$document['title'] = $text['title-databases'];

//include paging
	require_once "resources/paging.php";

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//show the content

	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' align='left' nowrap='nowrap'><b>".$text['header-databases']."</b></td>\n";
	echo "		<td width='50%' align='right'>";
	echo "		<input type='button' class='btn' alt='".$text['button-back']."' onclick=\"document.location.href='exec.php';\" value='".$text['button-back']."'>\n";
	if (if_group("superadmin")) {
		echo "	<input type='button' class='btn' alt='".$text['button-manage']."' onclick=\"document.location.href='/core/databases/databases.php';\" value='".$text['button-manage']."'>\n";
	}
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td align='left' colspan='2'>\n";
	echo "			".$text['description-databases'].".<br /><br />\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";

	//prepare to page the results
		$sql = "select count(*) from v_databases ";
		$database = new database;
		$num_rows = $database->select($sql, null, 'column');

	//prepare to page the results
		$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
		$param = "";
		$page = $_GET['page'];
		if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
		list($paging_controls, $rows_per_page, $var_3) = paging($num_rows, $param, $rows_per_page);
		$offset = $rows_per_page * $page;

	//get the  list
		$sql = str_replace('count(*)', '*', $sql);
		$sql .= order_by($order_by, $order);
		$sql .= limit_offset($rows_per_page, $offset);
		$database = new database;
		$result = $database->select($sql, null, 'all');
		unset($sql);

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo th_order_by('database_type', $text['label-type'], $order_by, $order);
	echo th_order_by('database_host', $text['label-host'], $order_by, $order);
	echo th_order_by('database_name', $text['label-name'], $order_by, $order);
	echo th_order_by('database_description', $text['label-description'], $order_by, $order);
	echo "<td class='list_control_icons' style='width: 25px;'>&nbsp;</td>\n";
	echo "<tr>\n";

	if (is_array($result) && @sizeof($result) != 0) {
		foreach($result as $row) {
			$tr_link = "href='exec.php?id=".escape($row['database_uuid'])."'";
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['database_type'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['database_host'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'><a href='exec.php?id=".escape($row['database_uuid'])."'>".escape($row['database_name'])."</a>&nbsp;</td>\n";
			echo "	<td valign='top' class='row_stylebg'>".escape($row['database_description'])."&nbsp;</td>\n";
			echo "	<td class='list_control_icons' style='width: 25px;'>";
			echo "		<a href='exec.php?id=".escape($row['database_uuid'])."' alt='".$text['button-edit']."'>".$v_link_label_edit."</a>\n";
			echo "	</td>\n";
			echo "</tr>\n";
			$c = ($c == 0) ? 1 : 0;
		}
	}
	unset($result, $row);

	echo "</table>";
	echo "<br><br>";

//include the footer
	require_once "resources/footer.php";
?>
