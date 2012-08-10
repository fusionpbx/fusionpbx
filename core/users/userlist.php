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
if (permission_exists("user_view") || if_group("superadmin")) {
	//access granted
}
else {
	echo "access denied";
	exit;
}
//require_once "includes/header.php";
	require_once "includes/paging.php";

$order_by = $_GET["order_by"];
$order = $_GET["order"];
$field_name = $_REQUEST["field_name"];
$field_value = $_REQUEST["field_value"];

echo "<div align='center'>";
echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
echo "<tr class='border'>\n";
echo "	<td align=\"center\">\n";

//page title and description
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<form method='post' action=''>";
	echo "<tr>\n";
	echo "<td align='left' width='90%' nowrap><b>User Manager</b></td>\n";
	echo "<td align='right' nowrap='nowrap'>Search by:&nbsp;</td>";
	echo "<td align='left'>\n";
	echo "	<select name='field_name' style='width:150px' class='frm'>\n";
	echo "	<option value=''></option>\n";
	if ($field_name == "username") {
		echo "	<option value='username' selected='selected'>Username</option>\n";
	}
	else {
		echo "	<option value='username'>Username</option>\n";
	}
	//if ($field_name == "user_email") {
	//	echo "	<option value='user_email' selected='selected'>Email</option>\n";
	//}
	//else {
	//	echo "	<option value='user_email'>Email</option>\n";
	//}
	echo "	</select>\n";
	echo "</td>\n";
	echo "<td align='left' width='3px'>&nbsp;</td>";
	echo "<td align='left'><input type='text' class='txt' style='width: 150px' name='field_value' value='$field_value'></td>";
	echo "<td align='left' width='60px'><input type='submit' class='btn' name='submit' value='search'></td>";
	//echo "	<input type='button' class='btn' name='' alt='view' onclick=\"window.location='user_search.php'\" value='advanced'>&nbsp;\n";
	echo "</tr>\n";
	echo "</form>";

	echo "<tr>\n";
	echo "<td align='left' colspan='4'>\n";
	echo "Add, edit, delete, and search for users. \n";
	echo "<br />\n";
	echo "<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

//get the user list from the database
	$sql = "select * from v_users ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	if (strlen($field_name) > 0 && strlen($field_value) > 0) {
		$sql .= "and $field_name = '$field_value' ";
	}
	if (strlen($order_by)> 0) { $sql .= "order by $order_by $order "; }
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$num_rows = count($result);
	unset ($prep_statement, $result, $sql);
	$rows_per_page = 200;
	$param = "";
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; } 
	list($paging_controls, $rows_per_page, $var_3) = paging($num_rows, $param, $rows_per_page); 
	$offset = $rows_per_page * $page; 

	$sql = "select * from v_users ";
	$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
	if (strlen($field_name) > 0 && strlen($field_value) > 0) {
		$sql .= "and $field_name like '%$field_value%' ";
	}
	if (strlen($order_by)> 0) { 
		$sql .= "order by $order_by $order "; 
	}
	else {
		$sql .= "order by username ";
	}
	$sql .= " limit $rows_per_page offset $offset ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$result_count = count($result);
	unset ($prep_statement, $sql);

//alternate the row style
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//show the data
	echo "<div align='center'>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo th_order_by('username', 'Username', $order_by, $order);
	//echo th_order_by('user_email', 'Email', $order_by, $order);
	//echo th_order_by('user_template_name', 'Template', $order_by, $order);
	echo "<th>Enabled</th>\n";
	echo "<td align='right' width='42'>\n";
	if (permission_exists('user_add')) {
		echo "	<a href='signup.php' alt='add'>$v_link_label_add</a>\n";
	}
	echo "</td>\n";
	echo "<tr>\n";

	if ($result_count > 0) {
		foreach($result as $row) {
			echo "<tr >\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['username']."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['user_email']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['user_enabled']."&nbsp;</td>\n";
			echo "	<td valign='top' align='right'>\n";
			if (permission_exists('user_edit')) {
				echo "		<a href='usersupdate.php?id=".$row['user_uuid']."' alt='edit'>$v_link_label_edit</a>\n";
			}
			if (permission_exists('user_delete')) {
				echo "		<a href='userdelete.php?id=".$row['user_uuid']."' alt='delete' onclick=\"return confirm('Do you really want to delete this?')\">$v_link_label_delete</a>\n";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results

	echo "<tr>\n";
	echo "<td colspan='49' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap>$paging_controls</td>\n";
	echo "		<td width='33.3%' align='right'>\n";
	if (permission_exists('user_add')) {
		echo "			<a href='signup.php' alt='add'>$v_link_label_add</a>\n";
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

?>
