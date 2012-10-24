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
include "root.php";
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('fax_extension_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}
require_once "includes/header.php";
require_once "includes/paging.php";

//add multi-lingual support
	echo "<!--\n";
	require_once "app_languages.php";
	echo "-->\n";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

//get the http get values and set them as php variables
	$order_by = check_str($_GET["order_by"]);
	$order = check_str($_GET["order"]);

//show the content
	echo "<div align='center'>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"center\">\n";
	echo "		<br>\n";
	echo "		<table width=\"100%\" border=\"0\" cellpadding=\"6\" cellspacing=\"0\">\n";
	echo "			<tr>\n";
	echo "				<td align='left'>\n";
	echo "					<p><span class=\"vexpl\"><span class=\"red\"><strong>FAX<br></strong></span>\n";
	echo "					".$text['description']."\n";
	echo "					</p>\n";
	echo "				</td>\n";
	echo "			</tr>\n";
	echo "		</table>\n";
	echo "		<br />\n";

	if (if_group("superadmin") || if_group("admin")) {
		//show all fax extensions
		$sql = "select count(*) as num_rows from v_fax ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
	}
	else {
		//show only assigned fax extensions
		$sql = "select count(*) as num_rows from v_fax as f, v_fax_users as u ";
		$sql .= "where f.fax_uuid = u.fax_uuid ";
		$sql .= "and f.domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and u.user_uuid = '".$_SESSION['user_uuid']."' ";
	}
	if ($prep_statement) {
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
		if ($row['num_rows'] > 0) {
			$num_rows = $row['num_rows'];
		}
		else {
			$num_rows = '0';
		}
	}
	unset($prep_statement, $result);

	$rows_per_page = 150;
	$param = "";
	$page = check_str($_GET['page']);
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; } 
	list($paging_controls, $rows_per_page, $var_3) = paging($num_rows, $param, $rows_per_page); 
	$offset = $rows_per_page * $page;

	if (if_group("superadmin") || if_group("admin")) {
		//show all fax extensions
		$sql = "select * from v_fax ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
	}
	else {
		//show only assigned fax extensions
		$sql = "select * from v_fax as f, v_fax_users as u ";
		$sql .= "where f.fax_uuid = u.fax_uuid ";
		$sql .= "and f.domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and u.user_uuid = '".$_SESSION['user_uuid']."' ";
	}
	if (strlen($order_by)> 0) { $sql .= "order by $order_by $order "; }
	$sql .= " limit $rows_per_page offset $offset ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
	$result_count = count($result);
	unset ($prep_statement, $sql);

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<div align='center'>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo th_order_by('fax_extension', $text['label-extension'], $order_by, $order);
	echo th_order_by('fax_name', $text['label-name'], $order_by, $order);
	echo th_order_by('fax_email', $text['label-email'], $order_by, $order);
	echo th_order_by('fax_description', $text['label-description'], $order_by, $order);
	echo "<td align='right' width='42'>\n";
	if (permission_exists('fax_extension_add')) {
		echo "	<a href='fax_edit.php' alt='add'>$v_link_label_add</a>\n";
	}
	echo "</td>\n";
	echo "<tr>\n";

	if ($result_count > 0) {
		foreach($result as $row) {
			echo "<tr >\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['fax_extension']."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['fax_name']."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['fax_email']."&nbsp;</td>\n";
			echo "	<td valign='top' class='row_stylebg' width='35%'>".$row['fax_description']."&nbsp;</td>\n";
			echo "	<td valign='top' align='right'>\n";
			if (permission_exists('fax_extension_edit')) {
				echo "		<a href='fax_view.php?id=".$row['fax_uuid']."' alt='edit'>$v_link_label_edit</a>\n";
			}
			if (permission_exists('fax_extension_delete')) {
				echo "		<a href='fax_delete.php?id=".$row['fax_uuid']."' alt='delete' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results

	echo "<tr>\n";
	echo "<td colspan='5'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap>$paging_controls</td>\n";
	echo "		<td width='33.3%' align='right'>\n";
	if (permission_exists('fax_extension_add')) {
		echo "			<a href='fax_edit.php' alt='add'>$v_link_label_add</a>\n";
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

//show the footer
	require_once "includes/footer.php";
?>