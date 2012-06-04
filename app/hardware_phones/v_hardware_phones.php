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
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('phone_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}
require_once "includes/header.php";
require_once "includes/paging.php";

$order_by = $_GET["order_by"];
$order = $_GET["order"];

echo "<div align='center'>";
echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
echo "<tr class='border'>\n";
echo "	<td align=\"center\">\n";
echo "		<br>";

echo "<table width='100%' border='0'>\n";
echo "<tr>\n";
echo "<td width='50%' nowrap='nowrap' align='left'><b>Hardware Phone List</b></td>\n";
echo "<td width='50%' align='right'>&nbsp;</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td colspan='2' align='left'>\n";

echo "Phones in this list are automatically added to the list when they contact the provisioning \n";
echo "server or added manually by an administrator. \n";
echo "Items in this list can be assigned from the extensions page.<br /><br />\n";
echo "</td>\n";
echo "</tr>\n";
echo "</tr></table>\n";

$sql = "";
$sql .= " select * from v_hardware_phones ";
$sql .= " where domain_uuid = '$domain_uuid' ";
if (strlen($order_by)> 0) { $sql .= "order by $order_by $order "; }
$prep_statement = $db->prepare(check_sql($sql));
$prep_statement->execute();
$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
$num_rows = count($result);
unset ($prep_statement, $result, $sql);
$rows_per_page = 10;
$param = "";
$page = $_GET['page'];
if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; } 
list($paging_controls, $rows_per_page, $var_3) = paging($num_rows, $param, $rows_per_page); 
$offset = $rows_per_page * $page; 

$sql = "";
$sql .= " select * from v_hardware_phones ";
$sql .= " where domain_uuid = '$domain_uuid' ";
if (strlen($order_by)> 0) { $sql .= "order by $order_by $order "; }
$sql .= " limit $rows_per_page offset $offset ";
$prep_statement = $db->prepare(check_sql($sql));
$prep_statement->execute();
$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
$result_count = count($result);
unset ($prep_statement, $sql);

$c = 0;
$row_style["0"] = "row_style0";
$row_style["1"] = "row_style1";

echo "<div align='center'>\n";
echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
echo "<tr>\n";
echo th_order_by('phone_mac_address', 'MAC Address', $order_by, $order);
echo th_order_by('phone_template', 'Template', $order_by, $order);
echo th_order_by('phone_vendor', 'Vendor', $order_by, $order);
//echo th_order_by('phone_model', 'Model', $order_by, $order);
echo th_order_by('phone_provision_enable', 'Enabled', $order_by, $order);
echo th_order_by('phone_description', 'Description', $order_by, $order);
echo "<td align='right' width='42'>\n";
if (permission_exists('phone_add')) {
	echo "	<a href='v_hardware_phones_edit.php' alt='add'>$v_link_label_add</a>\n";
}
echo "</td>\n";
echo "<tr>\n";

if ($result_count == 0) { //no results
}
else { //received results
	foreach($result as $row) {
		echo "<tr >\n";
		echo "	<td valign='top' class='".$row_style[$c]."'>".$row[phone_mac_address]."&nbsp;</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."'>".$row[phone_template]."&nbsp;</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."'>".$row[phone_vendor]."&nbsp;</td>\n";
		//echo "	<td valign='top' class='".$row_style[$c]."'>".$row[phone_model]."&nbsp;</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."' width='10px'>".$row[phone_provision_enable]."&nbsp;</td>\n";
		echo "	<td valign='top' class='row_stylebg'>".$row[phone_description]."&nbsp;</td>\n";
		echo "	<td valign='top' align='right'>\n";
		if (permission_exists('phone_edit')) {
			echo "		<a href='v_hardware_phones_edit.php?id=".$row[hardware_phone_uuid]."' alt='edit'>$v_link_label_edit</a>\n";
		}
		if (permission_exists('phone_delete')) {
			echo "		<a href='v_hardware_phones_delete.php?id=".$row[hardware_phone_uuid]."' alt='delete' onclick=\"return confirm('Do you really want to delete this?')\">$v_link_label_delete</a>\n";
		}
		echo "	</td>\n";
		echo "</tr>\n";
		if ($c==0) { $c=1; } else { $c=0; }
	} //end foreach
	unset($sql, $result, $row_count);
} //end if results

echo "<tr>\n";
echo "<td colspan='7' align='left'>\n";
echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
echo "	<tr>\n";
echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
echo "		<td width='33.3%' align='center' nowrap>$paging_controls</td>\n";
echo "		<td width='33.3%' align='right'>\n";
if (permission_exists('phone_add')) {
	echo "			<a href='v_hardware_phones_edit.php' alt='add'>$v_link_label_add</a>\n";
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

require_once "includes/footer.php";
?>
