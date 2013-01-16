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
	Portions created by the Initial Developer are Copyright (C) 2008-2012
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
require_once "root.php";
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('ivr_menu_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

//additional includes
	require_once "includes/header.php";
	require_once "includes/paging.php";

//get the http values and set them as variables
	if (isset($_GET["order_by"])) {
		$order_by['0']['name'] = check_str($_GET["order_by"]);
		$order_by['0']['order'] = check_str($_GET["order"]);
	}
	else {
		$order_by['0']['name'] = 'ivr_menu_name';
		$order_by['0']['order'] = 'asc';
	}

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"center\">\n";
	echo "		<br>";

	//show the content header
		echo "<table width='100%' border='0'>\n";
		echo "<tr>\n";
		echo "<td width='50%' nowrap='nowrap' align='left'><b>IVR Menu</b></td>\n";
		echo "<td width='50%' align='right'>&nbsp;</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td colspan='2' align='left'>\n";
		echo "The IVR Menu plays a recording or a pre-defined phrase that presents the caller with options to choose from. \n";
		echo "Each option has a corresponding destination. The destinations can be extensions, voicemail, other IVR menus, call groups, FAX extensions, and more. <br /><br />\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</tr>\n";
		echo "</table>\n";

	//get the count
		require_once "includes/classes/database.php";
		require_once "resources/classes/switch_ivr_menu.php";
		$ivr = new switch_ivr_menu;
		$ivr->domain_uuid = $_SESSION["domain_uuid"];
		$ivr->table = "v_ivr_menus";
		$where[0]['name'] = 'domain_uuid';
		$where[0]['value'] = $_SESSION["domain_uuid"];
		$where[0]['operator'] = '=';
		$ivr->where = $where;
		$num_rows = $ivr->count();

	//prepare to page the results
		$rows_per_page = 150;
		$param = "";
		if (!isset($_GET['page'])) { $_GET['page'] = 0; }
		$_GET['page'] = check_str($_GET['page']);
		list($paging_controls, $rows_per_page, $var_3) = paging($num_rows, $param, $rows_per_page); 
		$offset = $rows_per_page * $_GET['page']; 

	//get the list from the db
		if (isset($order_by)) {
			if (count($order_by) > 0) {
				$ivr->order_by = $order_by;
			}
		}
		$result = $ivr->find();
		$result_count = count($result);
		unset ($prep_statement, $sql);

		$c = 0;
		$row_style["0"] = "row_style0";
		$row_style["1"] = "row_style1";

		echo "<div align='center'>\n";
		echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr>\n";
		echo th_order_by('ivr_menu_name', 'Name', $order_by[0]['name'], $order_by[0]['order']);
		echo th_order_by('ivr_menu_extension', 'Extension', $order_by[0]['name'], $order_by[0]['order']);
		echo th_order_by('ivr_menu_direct_dial', 'Direct Dial', $order_by[0]['name'], $order_by[0]['order']);
		echo th_order_by('ivr_menu_enabled', 'Enabled', $order_by[0]['name'], $order_by[0]['order']);
		echo th_order_by('ivr_menu_description', 'Description', $order_by[0]['name'], $order_by[0]['order']);
		echo "<td align='right' width='42'>\n";
		if (permission_exists('ivr_menu_add')) {
			echo "	<a href='ivr_menu_edit.php' alt='add'>$v_link_label_add</a>\n";
		}
		echo "</td>\n";
		echo "<tr>\n";

		if ($result_count > 0) {
			foreach($result as $row) {
				$ivr_menu_name = str_replace("-", " ", $row['ivr_menu_name']);
				echo "<tr >\n";
				echo "	<td valign='top' class='".$row_style[$c]."'>".$ivr_menu_name."</td>\n";
				echo "	<td valign='top' class='".$row_style[$c]."'>".$row['ivr_menu_extension']."&nbsp;</td>\n";
				echo "	<td valign='top' class='".$row_style[$c]."'>".$row['ivr_menu_direct_dial']."</td>\n";
				echo "	<td valign='top' class='".$row_style[$c]."'>".$row['ivr_menu_enabled']."</td>\n";
				echo "	<td valign='top' class='row_stylebg'>".$row['ivr_menu_description']."&nbsp;</td>\n";
				echo "	<td valign='top' align='right'>\n";
				if (permission_exists('ivr_menu_edit')) {
					echo "		<a href='ivr_menu_edit.php?id=".$row['ivr_menu_uuid']."' alt='edit'>$v_link_label_edit</a>\n";
				}
				if (permission_exists('ivr_menu_delete')) {
					echo "		<a href='ivr_menu_delete.php?id=".$row['ivr_menu_uuid']."' alt='delete' onclick=\"return confirm('Do you really want to delete this?')\">$v_link_label_delete</a>\n";
				}
				echo "	</td>\n";
				echo "</tr>\n";
				if ($c==0) { $c=1; } else { $c=0; }
			} //end foreach
			unset($sql, $result, $row_count);
		} //end if results

		echo "<tr>\n";
		echo "<td colspan='6' align='left'>\n";
		echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
		echo "	<tr>\n";
		echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
		echo "		<td width='33.3%' align='center' nowrap>$paging_controls</td>\n";
		echo "		<td width='33.3%' align='right'>\n";
		if (permission_exists('ivr_menu_add')) {
			echo "			<a href='ivr_menu_edit.php' alt='add'>$v_link_label_add</a>\n";
		}
		echo "		</td>\n";
		echo "	</tr>\n";
		echo "	</table>\n";
		echo "</td>\n";
		echo "</tr>\n";

	echo "</table>";
	echo "</div>";
	echo "<br><br>";
	echo "<br><br>";

	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "</div>";
	echo "<br><br>";

//show the footer
	require_once "includes/footer.php";
?>
