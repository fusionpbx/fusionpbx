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
	Portions created by the Initial Developer are Copyright (C) 2010-2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('ring_group_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//add the search term
	$search = strtolower($_GET["search"]);
	if (strlen($search) > 0) {
		$sql_search = "and (";
		$sql_search .= "lower(ring_group_name) like :search ";
		$sql_search .= "or lower(ring_group_extension) like :search ";
		$sql_search .= "or lower(ring_group_description) like :search ";
		$sql_search .= "or lower(ring_group_enabled) like :search ";
		$sql_search .= "or lower(ring_group_strategy) like :search ";
		$sql_search .= ")";
		$parameters['search'] = '%'.$search.'%';
	}	

//additional includes
	require_once "resources/header.php";
	require_once "resources/paging.php";

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//show the content
	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' align='left' nowrap='nowrap'><b>".$text['title-ring_groups']."</b></td>\n";
	echo "		<form method='get' action=''>\n";
	echo "			<td width='50%' style='vertical-align: top; text-align: right; white-space: nowrap;'>\n";
	echo "				<input type='text' class='txt' style='width: 150px' name='search' id='search' value='".escape($search)."'>\n";
	echo "				<input type='submit' class='btn' name='submit' value='".$text['button-search']."'>\n";
	echo "			</td>\n";
	echo "		</form>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td align='left' colspan='2'>\n";
	echo "			".$text['description']."<br /><br />\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";

//get total ring group count
	$sql = "select count(*) from v_ring_groups ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$total_ring_groups = $database->select($sql, $parameters, 'column');

//get filtered ring group count
	$sql .= $search;
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "";
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

//get the list
	$sql = str_replace('count(*)', '*', $sql);
	if (strlen($order_by) == 0) {
		$sql .= "order by ring_group_name asc, ring_group_extension asc ";
	}
	else {
		$sql .= order_by($order_by, $order);
	}
	$sql .= limit_offset($rows_per_page, $offset);
	$ring_groups = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//set the row styles
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//show the content
	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo th_order_by('ring_group_name', $text['label-name'], $order_by, $order);
	echo th_order_by('ring_group_extension', $text['label-extension'], $order_by, $order);
	echo th_order_by('ring_group_strategy', $text['label-strategy'], $order_by, $order);
	echo th_order_by('ring_group_forward_enabled', $text['label-forwarding'], $order_by, $order);
	echo th_order_by('ring_group_enabled', $text['label-enabled'], $order_by, $order);
	echo th_order_by('ring_group_description', $text['header-description'], $order_by, $order);
	echo "<td class='list_control_icons'>";
	if (permission_exists('ring_group_add')) {
		if ($_SESSION['limit']['ring_groups']['numeric'] == '' || ($_SESSION['limit']['ring_groups']['numeric'] != '' && $total_ring_groups < $_SESSION['limit']['ring_groups']['numeric'])) {
			echo "<a href='ring_group_edit.php' alt='add'>".$v_link_label_add."</a>";
		}
	}
	echo "</td>\n";
	echo "</tr>\n";

	if (is_array($ring_groups) && @sizeof($ring_groups) != 0) {
		foreach($ring_groups as $row) {
			$tr_link = (permission_exists('ring_group_edit')) ? "href='ring_group_edit.php?id=".$row['ring_group_uuid']."'" : null;
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			if (permission_exists('ring_group_edit')) {
				echo "<a href='ring_group_edit.php?id=".escape($row['ring_group_uuid'])."'>".escape($row['ring_group_name'])."</a>";
			}
			else {
				echo $row['ring_group_name'];
			}
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['ring_group_extension'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$text['option-'.escape($row['ring_group_strategy'])]."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".(($row['ring_group_forward_enabled'] == 'true') ? format_phone(escape($row['ring_group_forward_destination'])) : null)."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$text['label-'.escape($row['ring_group_enabled'])]."&nbsp;</td>\n";
			echo "	<td valign='top' class='row_stylebg'>".escape($row['ring_group_description'])."&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>";
			if (permission_exists('ring_group_edit')) {
				echo "<a href='ring_group_edit.php?id=".escape($row['ring_group_uuid'])."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
			if (permission_exists('ring_group_delete')) {
				echo "<a href='ring_group_delete.php?id=".escape($row['ring_group_uuid'])."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			$c = $c ? 0 : 1;
		}
	}
	unset($ring_groups, $row);

	echo "<tr>\n";
	echo "</table>";
	echo "<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap>$paging_controls</td>\n";
	echo "		<td class='list_control_icons'>";
	if (permission_exists('ring_group_add')) {
		if ($_SESSION['limit']['ring_groups']['numeric'] == '' || ($_SESSION['limit']['ring_groups']['numeric'] != '' && $total_ring_groups < $_SESSION['limit']['ring_groups']['numeric'])) {
			echo "<a href='ring_group_edit.php' alt='add'>".$v_link_label_add."</a>";
		}
	}
	echo "		</td>\n";
	echo "	</tr>\n";
 	echo "</table>\n";
	echo "<br><br>";

//include the footer
	require_once "resources/footer.php";

?>
