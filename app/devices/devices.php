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

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
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
	$search = $_GET["search"];
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//get total devices count from the database
	$sql = "select count(*) from v_devices ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$total_devices = $database->select($sql, $parameters, 'column');
	unset($sql, $parameters);

//get the devices profiles
	$sql = "select * from v_device_profiles ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$device_profiles = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//prepare to page the results
	$sql = "select count(*) from v_devices as d ";
	if ($_GET['show'] == "all" && permission_exists('device_all')) {
		if (strlen($search) > 0) {
			$sql .= "where ";
		}
	}
	else {
		$sql .= "where (";
		$sql .= "	d.domain_uuid = :domain_uuid ";
		if (permission_exists('device_all')) {
			$sql .= "	or d.domain_uuid is null ";
		}
		$sql .= ") ";
		if (strlen($search) > 0) {
			$sql .= "and ";
		}
		$parameters['domain_uuid'] = $domain_uuid;
	}
	if (strlen($search) > 0) {
		$sql .= "(";
		$sql .= "	lower(d.device_mac_address) like :search ";
		$sql .= "	or lower(d.device_label) like :search ";
		$sql .= "	or lower(d.device_vendor) like :search ";
		$sql .= "	or lower(d.device_enabled) like :search ";
		$sql .= "	or lower(d.device_template) like :search ";
		$sql .= "	or lower(d.device_description) like :search ";
		$sql .= "	or lower(d.device_provisioned_method) like :search ";
		$sql .= "	or lower(d.device_provisioned_ip) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.strtolower($search).'%';
	}
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');
	unset($sql, $parameters);

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	if ($_GET['show'] == "all" && permission_exists('device_all')) {
		$param = "&search=".$search."&show=all";
	} else {
		$param = "&search=".$search;
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
	if ($_GET['show'] == "all" && permission_exists('device_all')) {
		//echo __line__."<br \>\n";
	}
	else {
		$sql .= "and (";
		$sql .= "	d.domain_uuid = :domain_uuid ";
		if (permission_exists('device_all')) {
			$sql .= "	or d.domain_uuid is null ";
		}
		$sql .= ") ";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	if (strlen($search) > 0) {
		$sql .= "and (";
		$sql .= "	lower(d.device_mac_address) like :search ";
		$sql .= "	or lower(d.device_label) like :search ";
		$sql .= "	or lower(d.device_vendor) like :search ";
		$sql .= "	or lower(d.device_enabled) like :search ";
		$sql .= "	or lower(d.device_template) like :search ";
		$sql .= "	or lower(d.device_description) like :search ";
		$sql .= "	or lower(d.device_provisioned_method) like :search ";
		$sql .= "	or lower(d.device_provisioned_ip) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.strtolower($search).'%';
	}
	if (strlen($order_by) == 0) {
		$sql .= "order by d.device_label, d.device_description asc ";
	}
	else {
		$sql .= "order by $order_by $order ";
	}
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$devices = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//alternate_found
	$device_alternate = false;
	foreach($devices as $row) {
		if (is_uuid($row['device_uuid_alternate'])) {
			$device_alternate = true;
			break;
		}
	}

//show the content
	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='100%' align='left' valign='top'>\n";
	echo "			<b>".$text['header-devices']." (".$num_rows.")</b>\n";
	echo "		</td>\n";
	echo "		<td align='right' nowrap='nowrap' valign='top'>\n";
	echo "			<form method='get' action=''>\n";
	if (permission_exists('device_all')) {
		if ($_GET['show'] == 'all') {
			echo "			<input type='hidden' name='show' value='all'>\n";
		}
		else {
			echo "			<input type='button' class='btn' value='".$text['button-show_all']."' onclick=\"window.location='devices.php?show=all';\">\n";
		}
	}
	if (permission_exists('device_vendor_view')) {
		echo "			<input type='button' class='btn' value='".$text['button-vendors']."' onclick=\"document.location.href='device_vendors.php';\">\n";
	}
	if (permission_exists('device_profile_view')) {
		echo "			<input type='button' class='btn' value='".$text['button-profiles']."' onclick=\"document.location.href='device_profiles.php';\">\n";
	}
	if (permission_exists('device_import')) {
		echo "			<input type='button' class='btn' alt='".$text['button-import']."' onclick=\"window.location='device_imports.php'\" value='".$text['button-import']."'>\n";
	}
	if (permission_exists('device_export')) {
		echo "			<input type='button' class='btn' value='".$text['button-export']."' onclick=\"window.location.href='device_download.php'\">\n";
	}
	echo "			<input type='text' class='txt' style='width: 150px; margin-left: 15px;' name='search' value='".escape($search)."'>\n";
	echo "			<input type='submit' class='btn' name='submit' value='".$text['button-search']."'>\n";
	echo "			</form>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2'>\n";
	echo "			".$text['description-devices'];
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "<br />\n";

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	if ($_GET['show'] == "all" && permission_exists('device_all')) {
		echo th_order_by('domain_name', $text['label-domain'], $order_by, $order, $param);
	}
	echo th_order_by('device_mac_address', $text['label-device_mac_address'], $order_by, $order);
	echo th_order_by('device_label', $text['label-device_label'], $order_by, $order);
	if ($device_alternate) {
		echo th_order_by('device_template', $text['label-device_uuid_alternate'], $order_by, $order);
	}
	echo th_order_by('device_vendor', $text['label-device_vendor'], $order_by, $order);
	echo th_order_by('device_template', $text['label-device_template'], $order_by, $order);
	echo "<th>". $text['label-device_profiles']."</th>\n";
	echo th_order_by('device_enabled', $text['label-device_enabled'], $order_by, $order);
	echo th_order_by('device_status', $text['label-device_status'], $order_by, $order);
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

	if (is_array($devices) && @sizeof($devices) != 0) {
		foreach($devices as $row) {

			$device_profile_name = '';
			foreach($device_profiles as $profile) {
				if ($profile['device_profile_uuid'] == $row['device_profile_uuid']) {
					$device_profile_name = $profile['device_profile_name'];
				}
			}

			$tr_link = (permission_exists('device_edit')) ? "href='device_edit.php?id=".escape($row['device_uuid'])."'" : null;
			echo "<tr ".$tr_link.">\n";
			if ($_GET['show'] == "all" && permission_exists('device_all')) {
				echo "	<td valign='top' class='".$row_style[$c]."'>".escape($_SESSION['domains'][$row['domain_uuid']]['domain_name'])."</td>\n";
			}
			echo "	<td valign='top' class='".$row_style[$c]."'>\n";
			echo (permission_exists('device_edit')) ? "<a href='device_edit.php?id=".escape($row['device_uuid'])."'>".format_mac(escape($row['device_mac_address']))."</a>" : format_mac(escape($row['device_mac_address']));
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['device_label'])."&nbsp;</td>\n";
			if ($device_alternate) {
				echo "	<td valign='top' class='".$row_style[$c]."'>\n";
				if (strlen($row['device_uuid_alternate']) > 0) {
					echo "		<a href='device_edit.php?id=".escape($row['device_uuid_alternate'])."' alt=''>".escape($row['alternate_label'])."</a>\n";
				}
				echo "	</td>\n";
			}
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['device_vendor'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['device_template'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($device_profile_name)."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$text['label-'.escape($row['device_enabled'])]."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['device_provisioned_date'])." - ".escape($row['device_provisioned_method'])." - <a href='http://".escape($row['device_provisioned_ip'])."' target='_blank'>".escape($row['device_provisioned_ip'])."</a>&nbsp;</td>\n";
			echo "	<td valign='top' class='row_stylebg'>".escape($row['device_description'])."&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>\n";
			if (permission_exists('device_edit')) {
				echo "<a href='device_edit.php?id=".$row['device_uuid']."' alt='".$text['button-edit']."'>$v_link_label_edit</a>\n";
			}
			if (permission_exists('device_delete')) {
				echo "<a href='device_delete.php?id=".$row['device_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		}
	}
	unset($devices, $row);

	echo "<tr>\n";
	echo "</table>\n";
	echo "<br />\n";

	echo $paging_controls."\n";
	echo "<br /><br />\n";

//include the footer
	require_once "resources/footer.php";

?>
