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
	Portions created by the Initial Developer are Copyright (C) 2008-2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!permission_exists('call_broadcast_view')) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the http get variables and set them to php variables
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//get the count
	$sql = "select count(*) from v_call_broadcasts ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$database = new database;
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$num_rows = $database->select($sql, $parameters, 'column');
	unset($sql, $parameters);

//prepare the paging
	require_once "resources/paging.php";
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "";
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page, $var_3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

//get the call call broadcasts
	$sql = "select * from v_call_broadcasts ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= order_by($order_by, $order);
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$result = $database->select($sql, $parameters, 'all');

//set the row style
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//add the header
	require_once "resources/header.php";

//show the content
	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'><tr>\n";
	echo "<td width='50%' nowrap='nowrap' align='left'><b>".$text['title']."</b></td>\n";
	echo "<td width='50%' align='right'>&nbsp;</td>\n";
	echo "</tr></table>\n";
	echo "<br>";

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo th_order_by('broadcast_name', $text['label-name'], $order_by, $order);
	echo th_order_by('broadcast_concurrent_limit', $text['label-concurrent-limit'], $order_by, $order);
	echo th_order_by('broadcast_description', $text['label-description'], $order_by, $order);
	//echo th_order_by('recordingid', 'Recording', $order_by, $order);
	echo "<td class='list_control_icons'>";
	if (permission_exists('call_broadcast_add')) {
		echo "<a href='call_broadcast_edit.php' alt='add'>$v_link_label_add</a>";
	}
	echo "</td>\n";
	echo "</tr>\n";

	if (is_array($result) && @sizeof($result) != 0) {
		foreach($result as $row) {
			$tr_link = (permission_exists('call_broadcast_edit')) ? "href='call_broadcast_edit.php?id=".$row['call_broadcast_uuid']."'" : null;
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			if (permission_exists('call_broadcast_edit')) {
				echo "<a href='call_broadcast_edit.php?id=".escape($row['call_broadcast_uuid'])."'>".escape($row['broadcast_name'])."</a>";
			}
			else {
				echo escape($row['broadcast_name']);
			}
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['broadcast_concurrent_limit'])."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['recordingid']."</td>\n";
			echo "	<td valign='top' class='row_stylebg'>".escape($row['broadcast_description'])."&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>";
			if (permission_exists('call_broadcast_edit')) {
				echo "<a href='call_broadcast_edit.php?id=".escape($row['call_broadcast_uuid'])."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
			if (permission_exists('call_broadcast_delete')) {
				echo "<a href='call_broadcast_delete.php?id=".escape($row['call_broadcast_uuid'])."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete-info']."')\">$v_link_label_delete</a>";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		}
	}
	unset($sql, $result);

	echo "<tr>\n";
	echo "<td colspan='5' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr class='tr_link_void'>\n";
	echo "		<td width='33.3%' nowrap='nowrap'>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap='nowrap'>$paging_controls</td>\n";
	echo "		<td class='list_control_icons'>";
	if (permission_exists('call_broadcast_add')) {
		echo 		"<a href='call_broadcast_edit.php' alt='add'>$v_link_label_add</a>";
	}
	echo "		</td>\n";
	echo "	</tr>\n";
 	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br><br>";

//include the footer
	require_once "resources/footer.php";

?>
