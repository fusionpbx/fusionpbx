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
	require_once "resources/check_auth.php";

//check permissions
	if (!permission_exists('access_control_view')) {
		echo "access denied"; exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//additional includes
	require_once "resources/header.php";
	require_once "resources/paging.php";

//prepare to page the results
	$sql = "select count(*) from v_access_controls ";
	$database = new database;
	$num_rows = $database->select($sql, null, 'column');

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = '';
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

//get the list
	$sql = "select * from v_access_controls ";
	$sql .= order_by($order_by, $order);
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$access_controls = $database->select($sql, null, 'all');

//alternate the row style
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//show the content
	echo "<b>".$text['title-access_controls']."</b>\n";
	echo "<br /><br />\n";
	echo $text['description-access_control']."\n";
	echo "<br /><br />\n";

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo th_order_by('access_control_name', $text['label-access_control_name'], $order_by, $order);
	echo th_order_by('access_control_default', $text['label-access_control_default'], $order_by, $order);
	echo th_order_by('access_control_description', $text['label-access_control_description'], $order_by, $order);
	echo "<td class='list_control_icons'>";
	if (permission_exists('access_control_add')) {
		echo "<a href='access_control_edit.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	else {
		echo "&nbsp;\n";
	}
	echo "</td>\n";
	echo "<tr>\n";

	if (is_array($access_controls)) {
		foreach($access_controls as $row) {
			if (permission_exists('access_control_edit')) {
				$tr_link = "href='access_control_edit.php?id=".escape($row['access_control_uuid'])."'";
			}
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]."'><a ".$tr_link.">".escape($row['access_control_name'])."</a></td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['access_control_default'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['access_control_description'])."&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>";
			if (permission_exists('access_control_edit')) {
				echo "<a href='access_control_edit.php?id=".escape($row['access_control_uuid'])."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
			if (permission_exists('access_control_delete')) {
				echo "<a href='access_control_delete.php?id=".escape($row['access_control_uuid'])."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			$c = $c == 1 ? 0 : 1;
		} //end foreach
		unset($sql, $access_controls);
	} //end if results

	echo "</table>\n";
	if (permission_exists('access_control_add')) {
		echo "<div style='float: right;'>\n";
		echo "	<a href='access_control_edit.php' alt=\"".$text['button-add']."\">".$v_link_label_add."</a>";
		echo "</div>\n";
	}
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";

//include the footer
	require_once "resources/footer.php";

?>
