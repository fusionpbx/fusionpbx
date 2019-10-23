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
 Portions created by the Initial Developer are Copyright (C) 2016-2017
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('device_vendor_function_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//add the search term
	$search = $_GET["search"];
	if (strlen($search) > 0) {
		$sql_where = "and (";
		$sql_where .= "label like :search ";
		$sql_where .= "or name like :search ";
		$sql_where .= "or value like :search ";
		$sql_where .= "or enabled like :search ";
		$sql_where .= "or description like :search ";
		$sql_where .= ")";
		$parameters['search'] = '%'.$search.'%';
	}

//additional includes
	require_once "resources/header.php";
	require_once "resources/paging.php";

//prepare to page the results
	$sql = "select count(*) from v_device_vendor_functions ";
	$sql .= "where device_vendor_uuid = :device_vendor_uuid ";
	$sql .= $sql_where;
	$parameters['device_vendor_uuid'] = $device_vendor_uuid;
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
	$sql .= order_by($order_by, $order, 'name', 'asc');
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$vendor_functions = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//alternate the row style
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//show the content
	echo "<table width='100%' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' align='left' nowrap='nowrap'><b>".$text['title-device_vendor_functions']."</b></td>\n";
	//echo "		<form method='get' action=''>\n";
	//echo "			<td width='50%' style='vertical-align: top; text-align: right; white-space: nowrap;'>\n";
	//echo "				<input type='text' class='txt' style='width: 150px' name='search' id='search' value='".$search."'>\n";
	//echo "				<input type='submit' class='btn' name='submit' value='".$text['button-search']."'>\n";
	//echo "			</td>\n";
	//echo "		</form>\n";
	echo "	</tr>\n";
	echo "</table>\n";

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	//echo "<th>".$text['label-label']."</th>\n";
	echo th_order_by('name', $text['label-name'], $order_by, $order);
	echo th_order_by('value', $text['label-value'], $order_by, $order);
	echo "<th>".$text['label-groups']."</th>\n";
	echo th_order_by('enabled', $text['label-enabled'], $order_by, $order);
	echo th_order_by('description', $text['label-description'], $order_by, $order);
	echo "<td class='list_control_icons'>";
	if (permission_exists('device_vendor_function_add')) {
		echo "<a href='device_vendor_function_edit.php?device_vendor_uuid=".escape($_GET['id'])."' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	else {
		echo "&nbsp;\n";
	}
	echo "</td>\n";
	echo "<tr>\n";

	if (is_array($vendor_functions) && @sizeof($vendor_functions) != 0) {
		foreach($vendor_functions as $row) {

			//get the groups that have been assigned to the vendor functions
				$sql = "select ";
				$sql .= "fg.*, g.domain_uuid as group_domain_uuid ";
				$sql .= "from ";
				$sql .= "v_device_vendor_function_groups as fg, ";
				$sql .= "v_groups as g ";
				$sql .= "where ";
				$sql .= "fg.group_uuid = g.group_uuid ";
				$sql .= "and fg.device_vendor_uuid = :device_vendor_uuid ";
				$sql .= "and fg.device_vendor_function_uuid = :device_vendor_function_uuid ";
				$sql .= "order by ";
				$sql .= "g.domain_uuid desc, ";
				$sql .= "g.group_name asc ";
				$parameters['device_vendor_uuid'] = $device_vendor_uuid;
				$parameters['device_vendor_function_uuid'] = $row['device_vendor_function_uuid'];
				$database = new database;
				$vendor_function_groups = $database->select($sql, $parameters, 'all');
				unset($sql, $parameters);
				unset($group_list);
				foreach ($vendor_function_groups as &$sub_row) {
					$group_list[] = escape($sub_row["group_name"]).(($sub_row['group_domain_uuid'] != '') ? "@".escape($_SESSION['domains'][$sub_row['group_domain_uuid']]['domain_name']) : null);
				}
				$group_list = isset($group_list) ? implode(', ', $group_list) : '';
				unset ($vendor_function_groups);
			//build the edit link
				if (permission_exists('device_vendor_function_edit')) {
					$tr_link = "href='device_vendor_function_edit.php?device_vendor_uuid=".escape($row['device_vendor_uuid'])."&id=".escape($row['device_vendor_function_uuid'])."'";
				}
			//show the row of data
				echo "<tr ".$tr_link.">\n";
				//echo "	<td valign='top' class='".$row_style[$c]."'>".$text['label-'.escape($row['name'])]."&nbsp;</td>\n";
				echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['name'])." &nbsp;</td>\n";
				echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['value'])."&nbsp;</td>\n";
				echo "	<td valign='top' class='".$row_style[$c]."'>".escape($group_list)."&nbsp;</td>\n";
				echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['enabled'])."&nbsp;</td>\n";
				echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['description'])."&nbsp;</td>\n";
				echo "	<td class='list_control_icons'>";
				if (permission_exists('device_vendor_function_edit')) {
					echo "<a href='device_vendor_function_edit.php?device_vendor_uuid=".escape($row['device_vendor_uuid'])."&id=".escape($row['device_vendor_function_uuid'])."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
				}
				if (permission_exists('device_vendor_function_delete')) {
					echo "<a href='device_vendor_function_delete.php?device_vendor_uuid=".escape($row['device_vendor_uuid'])."&id=".escape($row['device_vendor_function_uuid'])."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
				}
				echo "	</td>\n";
				echo "</tr>\n";
			//toggle the value of the c variable
				if ($c==0) { $c=1; } else { $c=0; }
		}
		unset($vendor_functions, $row);
	}

	echo "<tr>\n";
	echo "<td colspan='7' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap='nowrap'>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap='nowrap'>$paging_controls</td>\n";
	echo "		<td class='list_control_icons'>";
	if (permission_exists('device_vendor_function_add')) {
		echo 		"<a href='device_vendor_function_edit.php?device_vendor_uuid=".escape($_GET['id'])."' alt='".$text['button-add']."'>$v_link_label_add</a>";
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
	echo "<br /><br />";

//include the footer
	require_once "resources/footer.php";

?>
