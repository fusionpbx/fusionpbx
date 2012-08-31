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
if (permission_exists('virtual_tables_view')) {
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

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"center\">\n";
	echo "		<br>";

	echo "<table width='100%' border='0'>\n";
	echo "<tr>\n";
	echo "<td width='50%' align=\"left\" nowrap=\"nowrap\"><b>Virtual Table Field List</b></td>\n";
	echo "<td width='50%'  align=\"right\">&nbsp;</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align=\"left\" colspan=\"2\">\n";
	echo "Lists the fields in the virtual database.<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</tr></table>\n";

	if (strlen($order_by) == 0) {
		$order_by = 'virtual_field_order';
		$order = 'asc';
	}

	$sql = "select * from v_virtual_table_fields ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and virtual_table_uuid = '$virtual_table_uuid' ";
	if (strlen($order_by)> 0) { $sql .= "order by $order_by $order "; }
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
	echo th_order_by('virtual_field_label', 'Label', $order_by, $order);
	echo th_order_by('virtual_field_name', 'Name', $order_by, $order);
	echo th_order_by('virtual_field_type', 'Type', $order_by, $order);
	echo th_order_by('virtual_field_column', 'Column', $order_by, $order);
	echo th_order_by('virtual_field_required', 'Required', $order_by, $order);
	echo th_order_by('virtual_field_order', 'Field Order', $order_by, $order);
	echo th_order_by('virtual_field_order_tab', 'Tab Order', $order_by, $order);
	echo th_order_by('virtual_field_description', 'Description', $order_by, $order);
	echo "<td align='right' width='42'>\n";
	if (permission_exists('virtual_tables_view')) {
		echo "	<a href='v_virtual_table_fields_edit.php?virtual_table_uuid=".$virtual_table_uuid."' alt='add'>$v_link_label_add</a>\n";
	}
	echo "</td>\n";
	echo "<tr>\n";

	if ($result_count > 0) {
		foreach($result as $row) {
			echo "<tr >\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['virtual_field_label']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['virtual_field_name']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['virtual_field_type']."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['virtual_field_column']."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['virtual_field_required']."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['virtual_field_order']."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['virtual_field_order_tab']."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['virtual_field_description']."&nbsp;</td>\n";
			echo "	<td valign='top' align='right'>\n";
			if (permission_exists('virtual_tables_edit')) {
				echo "		<a href='v_virtual_table_fields_edit.php?virtual_table_uuid=".$row['virtual_table_uuid']."&id=".$row['virtual_table_field_uuid']."' alt='edit'>$v_link_label_edit</a>\n";
			}
			if (permission_exists('virtual_tables_delete')) {
				echo "		<a href='v_virtual_table_fields_delete.php?virtual_table_uuid=".$row['virtual_table_uuid']."&id=".$row['virtual_table_field_uuid']."' alt='delete' onclick=\"return confirm('Do you really want to delete this?')\">$v_link_label_delete</a>\n";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results

	echo "<tr>\n";
	echo "<td colspan='9' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='right'>\n";
	if (permission_exists('virtual_tables_add')) {
		echo "			<a href='v_virtual_table_fields_edit.php?virtual_table_uuid=".$virtual_table_uuid."' alt='add'>$v_link_label_add</a>\n";
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

//include the footer
	require_once "includes/footer.php";

?>
