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
if (permission_exists('extension_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}
require_once "includes/header.php";
require_once "includes/paging.php";

//get the http values and set them as variables
	if (isset($_GET["order_by"])) {
		$order_by = check_str($_GET["order_by"]);
		$order = check_str($_GET["order"]);
	}

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"center\">\n";
	echo "      <br>";

	//show the content header
		echo "<table width=\"100%\" border=\"0\" cellpadding=\"6\" cellspacing=\"0\">\n";
		echo "  <tr>\n";
		echo "	<td align='left'><b>Hot Desking</b><br>\n";
		echo "		Login into hot desking with an ID and your voicemail password to direct your calls to a remote extension. Then make and receive calls as if you were at your extension.\n";
		echo "	</td>\n";
		echo "  </tr>\n";
		echo "</table>\n";
		echo "<br />";

	//get the number of rows in v_extensions 
		$sql = "select count(*) as num_rows from v_extensions ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and unique_id is not null ";
		$prep_statement = $db->prepare(check_sql($sql));
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
		unset($prep_statement, $result);

	//prepare to page the results
		$rows_per_page = 150;
		$param = "";
		if (!isset($_GET['page'])) { $_GET['page'] = 0; }
		$_GET['page'] = check_str($_GET['page']);
		list($paging_controls, $rows_per_page, $var_3) = paging($num_rows, $param, $rows_per_page); 
		$offset = $rows_per_page * $_GET['page']; 

	//get the extension list
		$sql = "select * from v_extensions ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and unique_id is not null ";
		if (isset($order_by)) {
			$sql .= "order by $order_by $order ";
		}
		else {
			$sql .= "order by extension asc ";
		}
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
		echo th_order_by('extension', 'Extension', $order_by, $order);
		echo th_order_by('unique_id', 'Unique ID', $order_by, $order);
		echo th_order_by('dial_user', 'Forward To', $order_by, $order);
		//echo th_order_by('dial_domain', 'Domain', $order_by, $order);
		//echo th_order_by('call_group', 'Domain', $order_by, $order);
		echo th_order_by('description', 'Description', $order_by, $order);
		echo "<td align='right' width='42'>\n";
		if (permission_exists('extension_add')) {
			echo "	<a href='extension_edit.php' alt='add'>$v_link_label_add</a>\n";
		}
		echo "</td>\n";
		echo "<tr>\n";

		if ($result_count > 0) {
			foreach($result as $row) {
				echo "<tr >\n";
				echo "	<td valign='top' class='".$row_style[$c]."'>".$row['extension']."&nbsp;</td>\n";
				echo "	<td valign='top' class='".$row_style[$c]."'>".$row['unique_id']."&nbsp;</td>\n";
				if (strlen($row['dial_user']) > 0) {
					echo "	<td valign='top' class='".$row_style[$c]."'>".$row['dial_user']."@".$row['dial_domain']."&nbsp;</td>\n";
				}
				else {
					echo "	<td valign='top' class='".$row_style[$c]."'>&nbsp;</td>\n";
				}
				//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['dial_domain']."&nbsp;</td>\n";
				//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['domain_uuid']."&nbsp;</td>\n";
				echo "	<td valign='top' class='row_stylebg' width='30%'>".$row['description']."&nbsp;</td>\n";
				echo "	<td valign='top' align='right'>\n";
				if (permission_exists('extension_edit')) {
					echo "		<a href='extension_edit.php?id=".$row['extension_uuid']."' alt='edit'>$v_link_label_edit</a>\n";
				}
				if (permission_exists('extension_delete')) {
					echo "		<a href='extension_delete.php?id=".$row['extension_uuid']."' alt='delete' onclick=\"return confirm('Do you really want to delete this?')\">$v_link_label_delete</a>\n";
				}
				echo "	</td>\n";
				echo "</tr>\n";
				if ($c==0) { $c=1; } else { $c=0; }
			} //end foreach
			unset($sql, $result, $row_count);
		} //end if results

		echo "<tr>\n";
		echo "<td colspan='6' align='left'>\n";
		echo "	<table border='0' width='100%' cellpadding='0' cellspacing='0'>\n";
		echo "	<tr>\n";
		echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
		echo "		<td width='33.3%' align='center' nowrap>$paging_controls</td>\n";
		echo "		<td width='33.3%' align='right'>\n";
		if (permission_exists('extension_add')) {
			echo "			<a href='extension_edit.php' alt='add'>$v_link_label_add</a>\n";
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