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
	Copyright (C) 2008-2015 All Rights Reserved.

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

//additional includes
	require_once "resources/header.php";
	require_once "resources/paging.php";

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the http values and set them as variables
	$search = check_str($_GET["search"]);
	if (isset($_GET["order_by"])) {
		$order_by = check_str($_GET["order_by"]);
		$order = check_str($_GET["order"]);
	}

//get total devices count from the database
	$sql = "select count(*) as num_rows from v_devices where domain_uuid = '".$_SESSION['domain_uuid']."' ";
	$prep_statement = $db->prepare($sql);
	if ($prep_statement) {
		$prep_statement->execute();
		$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
		$total_devices = $row['num_rows'];
	}
	unset($sql, $prep_statement, $row);

//prepare to page the results
	$sql = "select count(*) as num_rows from v_devices as d ";
	if ($_GET['showall'] && permission_exists('device_all')) {
		if (strlen($search) > 0) {
			$sql .= "where ";
		}
	} else {
		$sql .= "where (";
		$sql .= "	d.domain_uuid = '$domain_uuid' ";
		if (permission_exists('device_all')) {
			$sql .= "	or d.domain_uuid is null ";
		}
		$sql .= ") ";
		if (strlen($search) > 0) {
			$sql .= "and ";
		}
	}
	if (strlen($search) > 0) {
		$sql .= "(";
		$sql .= "	d.device_mac_address like '%".$search."%' ";
		$sql .= "	or d.device_label like '%".$search."%' ";
		$sql .= "	or d.device_vendor like '%".$search."%' ";
		$sql .= "	or d.device_enabled like '%".$search."%' ";
		$sql .= "	or d.device_template like '%".$search."%' ";
		$sql .= "	or d.device_description like '%".$search."%' ";
		$sql .= ") ";
	}
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
	if ($_GET['showall'] && permission_exists('device_all')) {
		$param = "&showall=true";
	} else {
		$param = "";
	}
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

//get the list
	$sql = "select d.*, d2.device_label as alternate_label ";
	$sql .= "from v_devices as d, v_devices as d2 ";
	$sql .= "where ( ";
	$sql .= "	d.device_uuid_alternate = d2.device_uuid  ";
	$sql .= "	or d.device_uuid_alternate is null and d.device_uuid = d2.device_uuid ";
	$sql .= ") ";
	if ($_GET['showall'] && permission_exists('device_all')) {
		//echo __line__."<br \>\n";
	} else {
		$sql .= "and (";
		$sql .= "	d.domain_uuid = '$domain_uuid' ";
		if (permission_exists('device_all')) {
			$sql .= "	or d.domain_uuid is null ";
		}
		$sql .= ") ";
	}
	if (strlen($search) > 0) {
		$sql .= "and (";
		$sql .= "	d.device_mac_address like '%".$search."%' ";
		$sql .= "	or d.device_label like '%".$search."%' ";
		$sql .= "	or d.device_vendor like '%".$search."%' ";
		$sql .= "	or d.device_enabled like '%".$search."%' ";
		$sql .= "	or d.device_template like '%".$search."%' ";
		$sql .= "	or d.device_description like '%".$search."%' ";
		$sql .= ") ";
	}
	if (strlen($order_by) == 0) {
		$sql .= "order by d.device_label, d.device_description asc ";
	}
	else {
		$sql .= "order by $order_by $order ";
	}
	$sql .= "limit $rows_per_page offset $offset ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$devices = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	unset ($prep_statement, $sql);

//alternate_found
	$device_alternate = false;
	foreach($devices as $row) {
		if (strlen($row['device_uuid_alternate']) > 0) {
			$device_alternate = true;
			break;
		}
	}

//show the content
	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='100%' align='left' valign='top'>";
	echo "			<b>".$text['header-devices']." (".$num_rows.")</b>";
	echo "			<br /><br />";
	echo "			".$text['description-devices'];
	echo "		</td>\n";
	echo "		<td align='right' nowrap='nowrap' valign='top'>\n";
	echo "			<form method='get' action=''>\n";
	if (permission_exists('device_all')) {
		if ($_GET['showall'] == 'true') {
			echo "	<input type='hidden' name='showall' value='true'>";
		}
		else {
			echo "	<input type='button' class='btn' value='".$text['button-show_all']."' onclick=\"window.location='devices.php?showall=true';\">\n";
		}
	}
	if (permission_exists('device_profile_view')) {
		echo "		<input type='button' class='btn' value='".$text['button-profiles']."' onclick=\"document.location.href='device_profiles.php';\">&nbsp;&nbsp;&nbsp;&nbsp;";
	}
	echo "			<input type='text' class='txt' style='width: 150px' name='search' value='".$search."'>";
	echo "			<input type='submit' class='btn' name='submit' value='".$text['button-search']."'>";
	echo "			</form>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "<br />";

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	if ($_GET['showall'] && permission_exists('device_all')) {
		echo th_order_by('domain_name', $text['label-domain'], $order_by, $order, $param);
	}
	echo th_order_by('device_mac_address', $text['label-device_mac_address'], $order_by, $order);
	echo th_order_by('device_label', $text['label-device_label'], $order_by, $order);
	if ($device_alternate) {
		echo th_order_by('device_template', $text['label-device_uuid_alternate'], $order_by, $order);
	}
	echo th_order_by('device_vendor', $text['label-device_vendor'], $order_by, $order);
	echo th_order_by('device_template', $text['label-device_template'], $order_by, $order);
	echo th_order_by('device_enabled', $text['label-device_enabled'], $order_by, $order);
	echo th_order_by('device_description', $text['label-device_description'], $order_by, $order);
	echo "<td class='list_control_icons'>\n";
	if (permission_exists('device_add')) {
		if ($_SESSION['limit']['devices']['numeric'] == '' || ($_SESSION['limit']['devices']['numeric'] != '' && $total_devices < $_SESSION['limit']['devices']['numeric'])) {
			echo "	<a href='device_edit.php' alt='".$text['button-add']."'>".$v_link_label_add."</a>\n";
		}
	}
	else {
		echo "	&nbsp;\n";
	}
	echo "</td>\n";
	echo "<tr>\n";

	if (count($devices) > 0) {
		foreach($devices as $row) {
			$tr_link = (permission_exists('device_edit')) ? "href='device_edit.php?id=".$row['device_uuid']."'" : null;
			echo "<tr ".$tr_link.">\n";
			if ($_GET['showall'] && permission_exists('device_all')) {
				echo "	<td valign='top' class='".$row_style[$c]."'>".$_SESSION['domains'][$row['domain_uuid']]['domain_name']."</td>\n";
			}
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			echo (permission_exists('device_edit')) ? "<a href='device_edit.php?id=".$row['device_uuid']."'>".format_mac($row['device_mac_address'])."</a>" : format_mac($row['device_mac_address']);
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['device_label']."&nbsp;</td>\n";
			if ($device_alternate) {
				echo "	<td valign='top' class='".$row_style[$c]."'>\n";
				if (strlen($row['device_uuid_alternate']) > 0) {
					echo "		<a href='device_edit.php?id=".$row['device_uuid_alternate']."' alt=''>".$row['alternate_label']."</a>\n";
				}
				echo "	</td>\n";
			}
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['device_vendor']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['device_template']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$text['label-'.$row['device_enabled']]."&nbsp;</td>\n";
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
		unset($sql, $devices, $row_count);
	} //end if results

	echo "<tr>\n";
	echo "<td colspan='8'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap='nowrap'>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap='nowrap'>".$paging_controls."</td>\n";
	echo "		<td class='list_control_icons'>";
	if (permission_exists('device_add')) {
		if ($_SESSION['limit']['devices']['numeric'] == '' || ($_SESSION['limit']['devices']['numeric'] != '' && $total_devices < $_SESSION['limit']['devices']['numeric'])) {
			echo "		<a href='device_edit.php' alt='".$text['button-add']."'>".$v_link_label_add."</a>";
		}
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
