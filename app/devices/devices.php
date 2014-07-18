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
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('device_view')) {
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

//get the http values and set them as variables
	$search = check_str($_GET["search"]);
	if (isset($_GET["order_by"])) {
		$order_by = check_str($_GET["order_by"]);
		$order = check_str($_GET["order"]);
	}

//additional includes
	require_once "resources/header.php";
	require_once "resources/paging.php";

//show the content
	echo "<br />";
	echo "<div align='center'>";
	echo "<table width='100%' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' align='left' nowrap='nowrap'><b>".$text['header-devices']."</b></td>\n";
	echo "		<form method='get' action=''>\n";
	echo "			<td width='30%' align='right'>\n";
	echo "				<input type='text' class='txt' style='width: 150px' name='search' value='$search'>";
	echo "				<input type='submit' class='btn' name='submit' value='".$text['button-search']."'>";
	echo "			</td>\n";
	echo "		</form>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td align='left' colspan='2'>\n";
	echo "			".$text['description-devices']."<br /><br />\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";

	//prepare to page the results
		$sql = "select count(*) as num_rows from v_devices ";
		$sql .= "where (domain_uuid = '$domain_uuid' or domain_uuid is null) ";
		if (strlen($search) > 0) {
			$sql .= "and (";
			$sql .= "	device_mac_address like '%".$search."%' ";
			$sql .= " 	or device_label like '%".$search."%' ";
			$sql .= " 	or device_vendor like '%".$search."%' ";
			$sql .= " 	or device_provision_enable like '%".$search."%' ";
			$sql .= " 	or device_template like '%".$search."%' ";
			$sql .= " 	or device_description like '%".$search."%' ";
			$sql .= ") ";
		}
		//if (strlen($order_by)> 0) { $sql .= "order by $order_by $order "; }
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
		$rows_per_page = 150;
		$param = "";
		$page = $_GET['page'];
		if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
		list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page);
		$offset = $rows_per_page * $page;

	//get the list
		$sql = "select * from v_devices ";
		$sql .= "where (domain_uuid = '$domain_uuid' or domain_uuid is null) ";
		if (strlen($search) > 0) {
			$sql .= "and (";
			$sql .= "	device_mac_address like '%".$search."%' ";
			$sql .= " 	or device_label like '%".$search."%' ";
			$sql .= " 	or device_vendor like '%".$search."%' ";
			$sql .= " 	or device_provision_enable like '%".$search."%' ";
			$sql .= " 	or device_template like '%".$search."%' ";
			$sql .= " 	or device_description like '%".$search."%' ";
			$sql .= ") ";
		}
		if (strlen($order_by) == 0) {
			$sql .= "order by device_label, device_description asc ";
		}
		else {
			$sql .= "order by $order_by $order ";
		}
		$sql .= "limit $rows_per_page offset $offset ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		$result_count = count($result);
		unset ($prep_statement, $sql);

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<div align='center'>\n";
	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	//echo th_order_by('device_uuid', $text['label-device_uuid'], $order_by, $order);
	echo th_order_by('device_mac_address', $text['label-device_mac_address'], $order_by, $order);
	echo th_order_by('device_label', $text['label-device_label'], $order_by, $order);
	echo th_order_by('device_vendor', $text['label-device_vendor'], $order_by, $order);
	//echo th_order_by('device_model', $text['label-device_model'], $order_by, $order);
	//echo th_order_by('device_firmware_version', $text['label-device_firmware_version'], $order_by, $order);
	echo th_order_by('device_provision_enable', $text['label-device_provision_enable'], $order_by, $order);
	echo th_order_by('device_template', $text['label-device_template'], $order_by, $order);
	//echo th_order_by('device_username', $text['label-device_username'], $order_by, $order);
	//echo th_order_by('device_password', $text['label-device_password'], $order_by, $order);
	//echo th_order_by('device_time_zone', $text['label-device_time_zone'], $order_by, $order);
	echo th_order_by('device_description', $text['label-device_description'], $order_by, $order);
	echo "<td align='right' width='42'>\n";
	if (permission_exists('device_add')) {
		echo "	<a href='device_edit.php' alt='".$text['button-add']."'>$v_link_label_add</a>\n";
	}
	else {
		echo "	&nbsp;\n";
	}
	echo "</td>\n";
	echo "<tr>\n";

	if ($result_count > 0) {
		foreach($result as $row) {
			$device_mac_address = $row[device_mac_address];
			$device_mac_address = substr($device_mac_address, 0,2).'-'.substr($device_mac_address, 2,2).'-'.substr($device_mac_address, 4,2).'-'.substr($device_mac_address, 6,2).'-'.substr($device_mac_address, 8,2).'-'.substr($device_mac_address, 10,2);

			$tr_link = (permission_exists('device_edit')) ? "href='device_edit.php?id=".$row['device_uuid']."'" : null;
			echo "<tr ".$tr_link.">\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['device_uuid']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			if (permission_exists('device_edit')) {
				echo "<a href='device_edit.php?id=".$row['device_uuid']."'>".$row['device_mac_address']."</a>";
			}
			else {
				echo $row['device_mac_address'];
			}
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['device_label']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['device_vendor']."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['device_model']."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['device_firmware_version']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['device_provision_enable']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['device_template']."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['device_username']."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['device_password']."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['device_time_zone']."&nbsp;</td>\n";
			echo "	<td valign='top' class='row_stylebg'>".$row['device_description']."&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>";
			if (permission_exists('device_edit')) {
				echo "<a href='device_edit.php?id=".$row['device_uuid']."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
			if (permission_exists('device_delete')) {
				echo "<a href='device_delete.php?id=".$row['device_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results

	echo "<tr>\n";
	echo "<td colspan='8' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap='nowrap'>&nbsp;</td>\n";
	echo "		<td width='33.3%' nowrap='nowrap'>&nbsp;</td>\n";
	echo "		<td width='33.3%' nowrap='nowrap'>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap='nowrap'>$paging_controls</td>\n";
	echo "		<td class='list_control_icons'>";
	if (permission_exists('device_add')) {
		echo "<a href='device_edit.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	else {
		echo "&nbsp;";
	}
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>";
	echo "</div>";
	echo "<br /><br />";

//include the footer
	require_once "resources/footer.php";
?>