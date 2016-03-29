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
if (permission_exists('device_setting_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

require_once "resources/header.php";
require_once "resources/paging.php";

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];
	$device_uuid = check_str($_GET["id"]);

//show the content
	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' align='left' nowrap='nowrap'><b>Device Settings</b></td>\n";
	echo "		<td width='50%' align='right'>&nbsp;</td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td align='left' colspan='2'>\n";
	echo "			Settings used for each device.<br /><br />\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";

	//prepare to page the results
		$sql = "select count(*) as num_rows from v_devices_settings ";
		$sql .= "where device_uuid = '$device_uuid' ";
		$sql .= "and domain_uuid = '$domain_uuid' ";
		if (strlen($order_by)> 0) { $sql .= "order by $order_by $order "; }
		$prep_statement = $db->prepare($sql);
		if ($prep_statement) {
		$prep_statement->execute();
			$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
			if ($row['num_rows'] > 0) {
				$num_rows = $row['num_rows'];
			}
			else {
				$num_rows = '0';
			}
		}

	//prepare to page the results
		$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
		$param = "";
		$page = $_GET['page'];
		if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
		list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page);
		$offset = $rows_per_page * $page;

	//get the list
		$sql = "select * from v_device_settings ";
		$sql .= "where device_uuid = '$device_uuid' ";
		if (strlen($order_by)> 0) { $sql .= "order by $order_by $order "; }
		$sql .= "limit $rows_per_page offset $offset ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		$result_count = count($result);
		unset ($prep_statement, $sql);
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	if ($result_count > 0) {
		$previous_category = '';
		foreach($result as $row) {
			if ($previous_category != $row['device_setting_category']) {
				echo "<tr><td colspan='5' align='left'>\n";
				echo "	<br />\n";
				echo "	<br />\n";
				echo "	<b>".ucfirst($row['device_setting_category'])."</b>&nbsp;</td></tr>\n";
				echo "<tr>\n";
				echo th_order_by('device_setting_subcategory', $text['label-category'], $order_by, $order);
				echo th_order_by('device_setting_name', $text['label-type'], $order_by, $order);
				echo th_order_by('device_setting_value', $text['label-value'], $order_by, $order);
				echo th_order_by('device_setting_enabled', $text['label-enabled'], $order_by, $order);
				echo th_order_by('device_setting_description', $text['label-description'], $order_by, $order);
				echo "<td align='right' width='42'>\n";
				if (permission_exists('device_setting_add')) {
					echo "	<a href='device_setting_edit.php?device_uuid=".$_GET['id']."' alt='".$text['button-add']."'>$v_link_label_add</a>\n";
				}
				else {
					echo "	&nbsp;\n";
				}

				echo "</td>\n";
				echo "</tr>\n";
			}
			echo "<tr >\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['device_setting_subcategory']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['device_setting_name']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['device_setting_value']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['device_setting_enabled']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['device_setting_description']."&nbsp;</td>\n";
			echo "	<td valign='top' align='right'>\n";
			if (permission_exists('device_setting_edit')) {
			echo "		<a href='device_setting_edit.php?device_uuid=".$row['device_uuid']."&id=".$row['device_setting_uuid']."' alt='".$text['button-edit']."'>$v_link_label_edit</a>\n";
			}
			if (permission_exists('device_setting_delete')) {
			echo "		<a href='device_setting_delete.php?device_uuid=".$row['device_uuid']."&id=".$row['device_setting_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			$previous_category = $row['device_setting_category'];
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
	if (permission_exists('device_setting_add')) {
		echo "			<a href='device_setting_edit.php?device_uuid=".$_GET['id']."' alt='".$text['button-add']."'>$v_link_label_add</a>\n";
	}
	else {
		echo "			&nbsp;\n";
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