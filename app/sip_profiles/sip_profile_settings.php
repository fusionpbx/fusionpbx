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
if (permission_exists('sip_profile_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}
require_once "resources/header.php";
require_once "resources/paging.php";

//get variables used to control the order
	$order_by = ($_GET["order_by"] != '') ? $_GET["order_by"] : 'sip_profile_setting_name';
	$order = ($_GET["order"] != '') ? $_GET["order"] : 'asc';

//show the content
	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td align='left' width='50%' nowrap='nowrap'><b>".$text['header_settings']."</b></td>\n";
	echo "		<td width='50%' align='right'>&nbsp;</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";

	//prepare to page the results
		$sql = "select count(*) as num_rows from v_sip_profile_settings ";
		$sql .= "where sip_profile_uuid = '$sip_profile_uuid' ";
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
		$rows_per_page = 200;
		$param = "&id=".$sip_profile_uuid;
		$page = $_GET['page'];
		if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
		list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page);
		$offset = $rows_per_page * $page;

	//get the sip_profile list
		$sql = "select * from v_sip_profile_settings ";
		$sql .= "where sip_profile_uuid = '$sip_profile_uuid' ";
		if (isset($order_by)) { $sql .= "order by $order_by $order "; }
		$sql .= " limit $rows_per_page offset $offset ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		unset ($prep_statement, $sql);

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo th_order_by('sip_profile_setting_name', $text['label-setting_name'], $order_by, $order, null, null, "id=".$sip_profile_uuid);
	echo th_order_by('sip_profile_setting_value', $text['label-setting_value'], $order_by, $order, null, null, "id=".$sip_profile_uuid);
	echo th_order_by('sip_profile_setting_enabled', $text['label-setting_enabled'], $order_by, $order, null, null, "id=".$sip_profile_uuid);
	echo th_order_by('sip_profile_setting_description', $text['label-setting_description'], $order_by, $order, null, null, "id=".$sip_profile_uuid);
	echo "<td class='list_control_icons'>";
	if (permission_exists('sip_profile_setting_add')) {
		echo "<a href='sip_profile_setting_edit.php?sip_profile_uuid=".$_GET['id']."' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	echo "</td>\n";
	echo "</tr>\n";

	if ($num_rows > 0) {
		foreach($result as $row) {
			$tr_link = (permission_exists('sip_profile_setting_edit')) ? "href='sip_profile_setting_edit.php?sip_profile_uuid=".$row['sip_profile_uuid']."&id=".$row['sip_profile_setting_uuid']."'" : null;
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			if (permission_exists('sip_profile_setting_edit')) {
				echo "<a href='sip_profile_setting_edit.php?sip_profile_uuid=".$row['sip_profile_uuid']."&id=".$row['sip_profile_setting_uuid']."'>".$row['sip_profile_setting_name']."</a>";
			}
			else {
				echo $row['sip_profile_setting_name'];
			}
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['sip_profile_setting_value']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			echo "		<a href='?spid=".$row['sip_profile_uuid']."&spsid=".$row['sip_profile_setting_uuid']."&enabled=".(($row['sip_profile_setting_enabled'] == 'true') ? 'false' : 'true')."'>".(($row['sip_profile_setting_enabled'] == 'true') ? $text['option-true'] : $text['option-false'])."</a>";
			echo "	</td>\n";
			echo "	<td valign='top' class='row_stylebg'>".$row['sip_profile_setting_description']."&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>";
			if (permission_exists('sip_profile_setting_edit')) {
				echo "<a href='sip_profile_setting_edit.php?sip_profile_uuid=".$row['sip_profile_uuid']."&id=".$row['sip_profile_setting_uuid']."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
			if (permission_exists('sip_profile_setting_delete')) {
				echo "<a href='sip_profile_setting_delete.php?sip_profile_uuid=".$row['sip_profile_uuid']."&id=".$row['sip_profile_setting_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results

	echo "<tr>\n";
	echo "<td colspan='5' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap>$paging_controls</td>\n";
	echo "		<td class='list_control_icons'>";
	if (permission_exists('sip_profile_setting_add')) {
		echo 		"<a href='sip_profile_setting_edit.php?sip_profile_uuid=".$_GET['id']."' alt='".$text['button-add']."'>$v_link_label_add</a>";
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