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
	Portions created by the Initial Developer are Copyright (C) 2008-2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('sms_view')) {
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
	$search = check_str($_GET["search"]);
	$order_by = check_str($_GET["order_by"]);
	$order = check_str($_GET["order"]);

require_once "resources/header.php";
$document['title'] = $text['title-sms'];

require_once "resources/paging.php";

//get total extension count from the database
	$sql = "select ";
	$sql .= "(select count(*) from v_sms_destinations where domain_uuid = '".$_SESSION['domain_uuid']."' ".$sql_mod.") as num_rows ";
	if ($db_type == "pgsql") {
		$sql .= ",(select count(*) as count from v_sms_destinations ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= ") as numeric_sms ";
	}
	$prep_statement = $db->prepare($sql);
	if ($prep_statement) {
		$prep_statement->execute();
		$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
		$total_sms_destinations = $row['num_rows'];
		if (($db_type == "pgsql") or ($db_type == "mysql")) {
			$numeric_sms = $row['numeric_sms'];
		}
	}
	unset($prep_statement, $row);

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "&search=".$search;
	if (!isset($_GET['page'])) { $_GET['page'] = 0; }
	$_GET['page'] = check_str($_GET['page']);
	list($paging_controls_mini, $rows_per_page, $var_3) = paging($total_sms_destinations, $param, $rows_per_page, true); //top
	list($paging_controls, $rows_per_page, $var_3) = paging($total_sms_destinations, $param, $rows_per_page); //bottom
	$offset = $rows_per_page * $_GET['page'];

//to cast or not to cast
	if ($db_type == "pgsql") {
		$order_text = ($total_sms_destinations == $numeric_sms) ? "cast(destination as bigint)" : "destination asc";
	}
	else {
		//$order_text = "extension asc"; //extension doesn't exist in this table 8/23/2018/jblack
		$order_text = "destination asc";
	}

//get the extensions
	$sql = "select * from v_sms_destinations ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= $sql_mod; //add search mod from above
	if (strlen($order_by) > 0) {
		$sql .= ($order_by == 'destination') ? "order by $order_text ".$order." " : "order by ".$order_by." ".$order." ";
	}
	else {
		$sql .= "order by $order_text ";
	}
	$sql .= "limit $rows_per_page offset $offset ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$sms_destinations = $prep_statement->fetchAll(PDO::FETCH_NAMED);
//echo $sql;
	unset ($prep_statement, $sql);
//show the content
	echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
	echo "  <tr>\n";
	echo "	<td align='left' width='100%'><b>".$text['header-sms']." (".$total_sms_destinations.")</b><br>\n";
	echo "		".$text['description-sms']."\n";
	echo "	</td>\n";
	echo "		<form method='get' action=''>\n";
	echo "			<td style='vertical-align: top; text-align: right; white-space: nowrap;'>\n";
	if (if_group("superadmin")) {
		echo "				<input type='button' class='btn' style='margin-right: 15px;' value='".$text['button-mdr']."' onclick=\"window.location.href='sms_mdr.php'\">\n";
	}
		echo "				<input type='button' class='btn' style='margin-right: 15px;' value='".$text['button-broadcast']."' onclick=\"window.location.href='sms_broadcast.php'\">\n";

	echo "				<input type='text' class='txt' style='width: 150px' name='search' id='search' value='".$search."'>";
	echo "				<input type='submit' class='btn' name='submit' value='".$text['button-search']."'>";
	if ($paging_controls_mini != '') {
		echo 			"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "			</td>\n";
	echo "		</form>\n";
	echo "  </tr>\n";
	echo "</table>\n";
	echo "<br />";

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<form name='frm' method='post' action='sms_delete.php'>\n";
	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	if (permission_exists('sms_delete') && is_array($sms_destinations)) {
		echo "<th style='width: 30px; text-align: center; padding: 0px;'><input type='checkbox' id='chk_all' onchange=\"(this.checked) ? check('all') : check('none');\"></th>";
	}
	echo th_order_by('destination', $text['label-destination'], $order_by, $order);
	echo th_order_by('carrier', $text['label-carrier'], $order_by, $order);
	echo th_order_by('enabled', $text['label-enabled'], $order_by, $order);
	echo th_order_by('description', $text['label-description'], $order_by, $order);
	echo "<td class='list_control_icon'>\n";
	if (permission_exists('sms_add')) {
			echo "<a href='sms_edit.php' alt='".$text['button-add']."'>".$v_link_label_add."</a>";
	}
	if (permission_exists('sms_delete') && is_array($sms_destinations)) {
		echo "<a href='javascript:void(0);' onclick=\"if (confirm('".$text['confirm-delete']."')) { document.forms.frm.submit(); }\" alt='".$text['button-delete']."'>".$v_link_label_delete."</a>";
	}
	echo "</td>\n";
	echo "</tr>\n";

	if (is_array($sms_destinations)) {

		foreach($sms_destinations as $row) {
			$tr_link = (permission_exists('sms_edit')) ? " href='sms_edit.php?id=".$row['sms_destination_uuid']."'" : null;
			echo "<tr ".$tr_link.">\n";
			if (permission_exists('sms_delete')) {
				echo "	<td valign='top' class='".$row_style[$c]." tr_link_void' style='text-align: center; vertical-align: middle; padding: 0px;'>";
				echo "		<input type='checkbox' name='id[]' id='checkbox_".$row['sms_destination_uuid']."' value='".$row['sms_destination_uuid']."' onclick=\"if (!this.checked) { document.getElementById('chk_all').checked = false; }\">";
				echo "	</td>";
				$ext_ids[] = 'checkbox_'.$row['sms_destination_uuid'];
			}
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			if (permission_exists('sms_edit')) {
				echo "<a href='sms_edit.php?id=".$row['sms_destination_uuid']."'>".$row['destination']."</a>";
			}
			else {
				echo $row['destination'];
			}
			echo "</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['carrier']."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".ucwords($row['enabled'])."</td>\n";
			echo "	<td valign='top' class='row_stylebg' width='30%'>".$row['description']."&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>";
			if (permission_exists('sms_edit')) {
				echo "<a href='sms_edit.php?id=".$row['sms_destination_uuid']."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
			if (permission_exists('sms_delete')) {
				echo "<a href='sms_delete.php?id[]=".$row['sms_destination_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			}
			echo "</td>\n";
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;
		}
		unset($sms_destinations, $row);
	}

	if (is_array($sms_destinations)) {
		echo "<tr>\n";
		echo "	<td colspan='20' class='list_control_icons'>\n";
		if (permission_exists('sms_add')) {
				echo "<a href='sms_edit.php' alt='".$text['button-add']."'>".$v_link_label_add."</a>";
		}
		if (permission_exists('sms_delete')) {
			echo "<a href='javascript:void(0);' onclick=\"if (confirm('".$text['confirm-delete']."')) { document.forms.frm.submit(); }\" alt='".$text['button-delete']."'>".$v_link_label_delete."</a>";
		}
		echo "	</td>\n";
		echo "</tr>\n";
	}

	echo "</table>";
	echo "</form>";

	if (strlen($paging_controls) > 0) {
		echo "<center>".$paging_controls."</center>\n";
	}

	echo "<br /><br />".((is_array($sms_destinations)) ? "<br /><br />" : null);

	// check or uncheck all checkboxes
	if (sizeof($ext_ids) > 0) {
		echo "<script>\n";
		echo "	function check(what) {\n";
		echo "		document.getElementById('chk_all').checked = (what == 'all') ? true : false;\n";
		foreach ($ext_ids as $ext_id) {
			echo "		document.getElementById('".$ext_id."').checked = (what == 'all') ? true : false;\n";
		}
		echo "	}\n";
		echo "</script>\n";
	}

	if (is_array($sms_destinations)) {
		// check all checkboxes
		key_press('ctrl+a', 'down', 'document', null, null, "check('all');", true);

		// delete checked
		key_press('delete', 'up', 'document', array('#search'), $text['confirm-delete'], 'document.forms.frm.submit();', true);
	}

//show the footer
	require_once "resources/footer.php";
?>
