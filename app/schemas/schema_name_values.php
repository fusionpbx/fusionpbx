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
if (permission_exists('schema_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}
require_once "resources/header.php";
require_once "resources/paging.php";

//get the http values
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//show the content
	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "<tr>\n";
	echo "<td width='50%' align=\"left\" nowrap=\"nowrap\"><b>".$text['header-name_values']."</b></td>\n";
	echo "<td width='50%' align=\"right\">&nbsp;</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align=\"left\" nowrap=\"nowrap\" colspan='2'>\n";
	echo $text['description-name_values']."<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</tr></table>\n";

	//$sql = "select * from v_schema_name_values ";
	//$sql .= "where domain_uuid = '$domain_uuid' ";
	//$sql .= "and schema_field_uuid = '$schema_field_uuid' ";
	//if (strlen($order_by)> 0) { $sql .= "order by $order_by $order "; }
	//$prep_statement = $db->prepare(check_sql($sql));
	//$prep_statement->execute();
	//$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	//$num_rows = count($result);
	//unset ($prep_statement, $result, $sql);
	//$rows_per_page = 10;
	//$param = "";
	//$page = $_GET['page'];
	//if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	//list($paging_controls, $rows_per_page, $var_3) = paging($num_rows, $param, $rows_per_page);
	//$offset = $rows_per_page * $page;

	$sql = "select * from v_schema_name_values ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and schema_field_uuid = '$schema_field_uuid' ";
	if (strlen($order_by)> 0) { $sql .= "order by $order_by $order "; }
	//$sql .= " limit $rows_per_page offset $offset ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$result_count = count($result);
	unset ($prep_statement, $sql);

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo th_order_by('data_types_name', $text['label-name_value_name'], $order_by, $order);
	echo th_order_by('data_types_value', $text['label-name_value_value'], $order_by, $order);
	echo "<td align='right' width='42'>\n";
	echo "	<a href='schema_name_value_edit.php?schema_uuid=".$row["schema_uuid"]."&schema_field_uuid=".$row["schema_field_uuid"]."' alt='".$text['button-add']."'>$v_link_label_add</a>\n";
	echo "</td>\n";
	echo "<tr>\n";

	if ($result_count > 0) {
		foreach($result as $row) {
			echo "<tr >\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row[data_types_name]."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row[data_types_value]."</td>\n";
			echo "	<td valign='top' align='right'>\n";
			echo "		<a href='schema_name_value_edit.php?schema_uuid=".$row["schema_uuid"]."&schema_field_uuid=".$row["schema_field_uuid"]."&id=".$row["schema_name_value_uuid"]."' alt='".$text['button-edit']."'>$v_link_label_edit</a>\n";
			echo "		<a href='schema_name_value_delete.php?schema_uuid=".$row["schema_uuid"]."&schema_field_uuid=".$row["schema_field_uuid"]."&id=".$row["schema_name_value_uuid"]."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
			echo "	</td>\n";
			echo "</tr>\n";
			$c = ($c==0) ? 1 : 0;
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results

	echo "<tr>\n";
	echo "<td colspan='3' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
	echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
	//echo "		<td width='33.3%' align='center' nowrap>$paging_controls</td>\n";
	echo "		<td width='33.3%' align='right'>\n";
	echo "			<a href='schema_name_value_edit.php?schema_uuid=".$row["schema_uuid"]."&schema_field_uuid=".$row["schema_field_uuid"]."' alt='".$text['button-add']."'>$v_link_label_add</a>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
 	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br><br>";
	echo "</div>";

//show the footer
	require_once "resources/footer.php";

?>