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
 Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";

//require_once "resources/header.php";
require_once "resources/paging.php";

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];


//prepare to page the results
	$sql = "select count(*) as num_rows from v_contact_settings ";
	$sql .= "where contact_uuid = '$contact_uuid' ";
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
	$rows_per_page = 100;
	$param = "";
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

//get the list
	$sql = "select * from v_contact_settings ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and contact_uuid = '$contact_uuid' ";
	if (strlen($order_by) == 0) {
		$sql .= "order by contact_setting_category, contact_setting_subcategory, contact_setting_order asc ";
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

//show the content
        
	echo "<table width='100%' border='0'>\n";
	echo "<tr>\n";
	echo "<td width='50%' align='left' nowrap='nowrap'><b>".$text['label-contact_properties']."</b></td>\n";
	echo "<td width='50%' align='right'>&nbsp;</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	echo "<br /><br />";
	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	if ($result_count > 0) {
		$previous_category = '';
		foreach($result as $row) {
			if ($previous_category != $row['contact_setting_category']) {
				echo "<tr>";
				echo "	<td colspan='6' align='left'>\n";
				echo "		<br />\n";
				echo "		<br />\n";
				echo "		<b>";
				echo "		".ucfirst($row['contact_setting_category']);
				echo "		</b>\n";
				echo "	</td>\n";
				echo "</tr>\n";
				echo "<tr>\n";
				echo "<th>".$text['label-subcategory']."</th>";
				echo "<th>".$text['label-type']."</th>";
				echo "<th>".$text['label-value']."</th>";
				echo "<th style='text-align: center;'>".$text['label-enabled']."</th>";
				echo "<th>".$text['label-description']."</th>";
				echo "<td class='list_control_icons'>";
				echo "<a href='contact_setting_edit.php?contact_uuid=".$contact_uuid."' alt='".$text['button-add']."'>$v_link_label_add</a>";
				echo "</td>\n";
				echo "</tr>\n";
			}
			$tr_link = " href='contact_setting_edit.php?contact_uuid=".$contact_uuid."&id=".$row['contact_setting_uuid']."'";
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			echo 	"<a href='contact_setting_edit.php?contact_uuid=".$contact_uuid."&id=".$row['contact_setting_uuid']."'>".$row['contact_setting_subcategory']."</a>";
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['contact_setting_name']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>\n";

			$category = $row['contact_setting_category'];
			$subcategory = $row['contact_setting_subcategory'];
			$name = $row['contact_setting_name'];
			if ($category == "callingcard" && $subcategory == "username" && $name == "var" ) {
				echo "		******** &nbsp;\n";
			}
			elseif ($category == "callingcard" && $subcategory == "password" && $name == "var" ) {
				echo "		******** &nbsp;\n";
			} else {
				echo 		$row['contact_setting_value'];
			}
			echo "		&nbsp;\n";
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."' style='text-align: center;'>".ucwords($row['contact_setting_enabled'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='row_stylebg'>".$row['contact_setting_description']."&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>";
				echo 	"<a href='contact_setting_edit.php?contact_uuid=".$row['contact_uuid']."&id=".$row['contact_setting_uuid']."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
				echo 	"<a href='contact_setting_delete.php?contact_uuid=".$row['contact_uuid']."&id=".$row['contact_setting_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			echo "	</td>\n";
			echo "</tr>\n";
			$previous_category = $row['contact_setting_category'];
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
	echo "		<td class='list_control_icons'>";
	echo 		"<a href='contact_setting_edit.php?contact_uuid=".$contact_uuid."' alt='".$text['button-add']."'>$v_link_label_add</a>";
	echo "		</td>\n";
	echo "	</tr>\n";
 	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>";


//include the footer
	//require_once "resources/footer.php";
?>
