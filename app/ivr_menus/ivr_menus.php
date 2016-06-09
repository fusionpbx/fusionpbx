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
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('ivr_menu_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//additional includes
	require_once "resources/header.php";
	$document['title'] = $text['title-ivr_menus'];
	require_once "resources/paging.php";

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
	echo "<b>".$text['header-ivr_menus']."</b>\n";
	echo "<br /><br />\n";
	echo $text['description-ivr_menus']."\n";
	echo "<br /><br />\n";

//get the count
	require_once "resources/classes/database.php";
	require_once "resources/classes/ivr_menu.php";
	$ivr = new ivr_menu;
	$ivr->domain_uuid = $_SESSION["domain_uuid"];
	$ivr->table = "v_ivr_menus";
	$where[0]['name'] = 'domain_uuid';
	$where[0]['value'] = $_SESSION["domain_uuid"];
	$where[0]['operator'] = '=';
	$ivr->where = $where;
	$num_rows = $ivr->count();

//use total ivr menu count from the database
	$total_ivr_menus = $num_rows;

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
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

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo th_order_by('ivr_menu_name', $text['label-name'], $order_by[0]['name'], $order_by[0]['order']);
	echo th_order_by('ivr_menu_extension', $text['label-extension'], $order_by[0]['name'], $order_by[0]['order']);
	echo th_order_by('ivr_menu_direct_dial', $text['label-direct_dial'], $order_by[0]['name'], $order_by[0]['order']);
	echo th_order_by('ivr_menu_enabled', $text['label-enabled'], $order_by[0]['name'], $order_by[0]['order']);
	echo th_order_by('ivr_menu_description', $text['label-description'], $order_by[0]['name'], $order_by[0]['order']);
	echo "<td class='list_control_icons'>";
	if (permission_exists('ivr_menu_add')) {
		if ($_SESSION['limit']['ivr_menus']['numeric'] == '' || ($_SESSION['limit']['ivr_menus']['numeric'] != '' && $total_ivr_menus < $_SESSION['limit']['ivr_menus']['numeric'])) {
			echo "<a href='ivr_menu_edit.php' alt='".$text['button-add']."'>".$v_link_label_add."</a>";
		}
	}
	echo "</td>\n";
	echo "</tr>\n";

	if ($result_count > 0) {
		foreach($result as $row) {
			$ivr_menu_name = str_replace("-", " ", $row['ivr_menu_name']);
			$tr_link = (permission_exists('ivr_menu_edit')) ? "href='ivr_menu_edit.php?id=".$row['ivr_menu_uuid']."'" : null;
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			if (permission_exists('ivr_menu_edit')) {
				echo "<a href='ivr_menu_edit.php?id=".$row['ivr_menu_uuid']."'>".$ivr_menu_name."</a>";
			}
			else {
				echo $ivr_menu_name;
			}
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['ivr_menu_extension']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".ucwords($row['ivr_menu_direct_dial'])."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$text['label-'.$row['ivr_menu_enabled']]."</td>\n";
			echo "	<td valign='top' class='row_stylebg'>".$row['ivr_menu_description']."&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>";
			if (permission_exists('ivr_menu_edit')) {
				echo "<a href='ivr_menu_edit.php?id=".$row['ivr_menu_uuid']."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
			if (permission_exists('ivr_menu_delete')) {
				echo "<a href='ivr_menu_delete.php?id=".$row['ivr_menu_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results

	if (permission_exists('ivr_menu_add')) {
		if ($_SESSION['limit']['ivr_menus']['numeric'] == '' || ($_SESSION['limit']['ivr_menus']['numeric'] != '' && $total_ivr_menus < $_SESSION['limit']['ivr_menus']['numeric'])) {
			echo "<tr>\n";
			echo "	<td colspan='5' align='left'>&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>";
			echo 		"<a href='ivr_menu_edit.php' alt='".$text['button-add']."'>".$v_link_label_add."</a>";
			echo "	</td>\n";
			echo "</tr>\n";
		}
	}
	echo "</table>\n";
	echo "<br>";

	echo "<div align='center'>".$paging_controls."</div>\n";
	echo "<br><br>";

//show the footer
	require_once "resources/footer.php";
?>
