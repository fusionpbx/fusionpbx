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
if (permission_exists('schema_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//require_once "resources/header.php";
require_once "resources/paging.php";

$order_by = $_GET["order_by"];
$order = $_GET["order"];

//show the content
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"center\">\n";
	echo "		<br>";

	echo "<table width='100%' border='0'>\n";
	echo "<tr>\n";
	echo "<td width='50%' align=\"left\" nowrap=\"nowrap\"><b>".$text['header-fields']."</b></td>\n";
	echo "<td width='50%'  align=\"right\">&nbsp;</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align=\"left\" colspan=\"2\">\n";
	echo $text['description-fields']."<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	if (strlen($order_by) == 0) {
		$order_by = 'field_order';
		$order = 'asc';
	}

	$sql = "select * from v_schema_fields ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and schema_uuid = '$schema_uuid' ";
	if (strlen($order_by)> 0) { $sql .= "order by $order_by $order "; }
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$result_count = count($result);
	unset ($prep_statement, $sql);

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo th_order_by('field_label', $text['label-field_label'], $order_by, $order);
	echo th_order_by('field_name', $text['label-field_name'], $order_by, $order);
	echo th_order_by('field_type', $text['label-field_type'], $order_by, $order);
	echo th_order_by('field_column', $text['label-field_column'], $order_by, $order);
	echo th_order_by('field_required', $text['label-field_required'], $order_by, $order);
	echo th_order_by('field_list_hidden', $text['label-field_visibility'], $order_by, $order);
	echo th_order_by('field_search_by', $text['label-field_search_by'], $order_by, $order);
	echo th_order_by('field_order', $text['label-field_order'], $order_by, $order);
	echo th_order_by('field_order_tab', $text['label-field_tab_order'], $order_by, $order);
	echo th_order_by('field_description', $text['label-field_description'], $order_by, $order);
	echo "<td class='list_control_icons'>";
	if (permission_exists('schema_view')) {
		echo "<a href='schema_field_edit.php?schema_uuid=".$schema_uuid."' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	echo "</td>\n";
	echo "</tr>\n";

	if ($result_count > 0) {
		foreach($result as $row) {
			$tr_link = (permission_exists('schema_edit')) ? "href='schema_field_edit.php?schema_uuid=".$row['schema_uuid']."&id=".$row['schema_field_uuid']."'" : null;
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['field_label']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			if (permission_exists('schema_edit')) {
				echo "<a href='schema_field_edit.php?schema_uuid=".$row['schema_uuid']."&id=".$row['schema_field_uuid']."'>".$row['field_name']."</a>";
			}
			else {
				echo $row['field_name'];
			}
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			switch ($row['field_type']) {
				case "text" : echo $text['option-text']; break;
				case "numeric" : echo $text['option-number']; break;
				case "date" : echo $text['option-date']; break;
				case "email" : echo $text['option-email']; break;
				case "label" : echo $text['option-label']; break;
				case "phone" : echo $text['option-phone']; break;
				case "checkbox" : echo $text['option-check_box']; break;
				case "textarea" : echo $text['option-text_area']; break;
				case "select" : echo $text['option-select']; break;
				case "hidden" : echo $text['option-hidden']; break;
				case "uuid" : echo $text['option-uuid']; break;
				case "password" : echo $text['option-password']; break;
				case "pin_number" : echo $text['option-pin_number']; break;
				case "image" : echo $text['option-image_upload']; break;
				case "upload_file" : echo $text['option-file_upload']; break;
				case "url" : echo $text['option-url']; break;
				case "mod_date" : echo $text['option-modified_date']; break;
				case "mod_user" : echo $text['option-modified_user']; break;
				default : echo $row['field_type'];
			}
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['field_column']."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			if ($row['field_required'] == 'yes') {
				echo $text['option-true'];
			}
			else if ($row['field_required'] == 'no') {
				echo $text['option-false'];
			}
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			if ($row['field_list_hidden'] == 'show') {
				echo $text['option-visible'];
			}
			else if ($row['field_list_hidden'] == 'hide') {
				echo $text['option-hidden'];
			}
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			if ($row['field_search_by'] == 'yes') {
				echo $text['option-true'];
			}
			else if ($row['field_search_by'] == 'no') {
				echo $text['option-false'];
			}
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['field_order']."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['field_order_tab']."</td>\n";
			echo "	<td valign='top' class='row_stylebg'>".$row['field_description']."&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>";
			if (permission_exists('schema_edit')) {
				echo "<a href='schema_field_edit.php?schema_uuid=".$row['schema_uuid']."&id=".$row['schema_field_uuid']."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
			if (permission_exists('schema_delete')) {
				echo "<a href='schema_field_delete.php?schema_uuid=".$row['schema_uuid']."&id=".$row['schema_field_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results

	echo "<tr>\n";
	echo "<td colspan='11' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap>&nbsp;</td>\n";
	echo "		<td class='list_control_icons'>";
	if (permission_exists('schema_add')) {
		echo 		"<a href='schema_field_edit.php?schema_uuid=".$schema_uuid."' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	echo "		</td>\n";
	echo "	</tr>\n";
 	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>";

	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "<br><br>";

//include the footer
//	require_once "resources/footer.php";

?>