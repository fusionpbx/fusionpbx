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
if (permission_exists('contacts_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}
require_once "includes/header.php";
require_once "includes/paging.php";

//get the search criteria
	$search_all = $_GET["search_all"];

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"center\">\n";
	echo "		<br>";

	echo "<table width=\"100%\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\">\n";
	echo "	<tr>\n";
	echo "	<td align=\"left\" valign=\"top\"><strong>Contacts</strong><br>\n";
	echo "		The contact is a list of individuals and organizations.\n";
	echo "	</td>\n";
	echo "	<td align=\"right\" valign=\"top\">\n";
	echo "		<form method=\"GET\" name=\"frm_search\" action=\"\">\n";
	echo "			<input class=\"formfld\" type=\"text\" name=\"search_all\" value=\"$search_all\">\n";
	echo "			<input class=\"btn\" type=\"submit\" name=\"submit\" value=\"Search All\">\n";
	echo "		</form>\n";
	echo "	</td>\n";
	if (permission_exists('contacts_add')) {
		echo "	<td align=\"right\" valign=\"top\" width=\"50px\">\n";
		echo "		<input type='button' class='btn' name='' alt='back' onclick=\"window.location='contact_import.php'\" value='Import'>\n";
		echo "	</td>\n";
	}
	echo "	</tr>\n";
	echo "</table>\n";

	//prepare to page the results
		$sql = "";
		$sql .= " select count(*) as num_rows from v_contacts ";
		$sql .= " where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		if (strlen($search_all) > 0) {
			if (is_numeric($search_all)) {
				$sql .= "and contact_uuid in (select contact_uuid from v_contact_phones where phone_number like '%".$search_all."%') \n";
			}
			else {
				$sql .= "and contact_uuid in (\n";
				$sql .= "	select contact_uuid from v_contacts ";
				$sql .= "	where domain_uuid = '".$_SESSION['domain_uuid']."' \n";
				$sql .= "	and (\n";
				$sql .= "	contact_organization like '%".$search_all."%' or \n";
				$sql .= "	contact_name_given like '%".$search_all."%' or \n";
				$sql .= "	contact_name_family like '%".$search_all."%' or \n";
				$sql .= "	contact_nickname like '%".$search_all."%' or \n";
				$sql .= "	contact_title like '%".$search_all."%' or \n";
				$sql .= "	contact_role like '%".$search_all."%' or \n";
				$sql .= "	contact_email like '%".$search_all."%' or \n";
				$sql .= "	contact_url like '%".$search_all."%' or \n";
				$sql .= "	contact_time_zone like '%".$search_all."%' or \n";
				$sql .= "	contact_note like '%".$search_all."%' or \n";
				$sql .= "	contact_type like '%".$search_all."%'\n";
				$sql .= "	)\n";
				$sql .= ")\n";
			}
		}
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
		$rows_per_page = 30;
		$param = "";
		$page = $_GET['page'];
		if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; } 
		list($paging_controls, $rows_per_page, $var_3) = paging($num_rows, $param, $rows_per_page); 
		$offset = $rows_per_page * $page; 

	//get the  list
		$sql = "select * from v_contacts ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		if (strlen($search_all) > 0) {
			if (is_numeric($search_all)) {
				$sql .= "and contact_uuid in (select contact_uuid from v_contact_phones where phone_number like '%".$search_all."%') \n";
			}
			else {
				$sql .= "and contact_uuid in (\n";
				$sql .= "	select contact_uuid from v_contacts where domain_uuid = '".$_SESSION['domain_uuid']."' \n";
				$sql .= "	and (\n";
				$sql .= "	contact_organization like '%".$search_all."%' or \n";
				$sql .= "	contact_name_given like '%".$search_all."%' or \n";
				$sql .= "	contact_name_family like '%".$search_all."%' or \n";
				$sql .= "	contact_nickname like '%".$search_all."%' or \n";
				$sql .= "	contact_title like '%".$search_all."%' or \n";
				$sql .= "	contact_role like '%".$search_all."%' or \n";
				$sql .= "	contact_email like '%".$search_all."%' or \n";
				$sql .= "	contact_url like '%".$search_all."%' or \n";
				$sql .= "	contact_time_zone like '%".$search_all."%' or \n";
				$sql .= "	contact_note like '%".$search_all."%' or \n";
				$sql .= "	contact_type like '%".$search_all."%'\n";
				$sql .= "	)\n";
				$sql .= ")\n";
			}
		}
		if (strlen($order_by)> 0) { $sql .= "order by $order_by $order "; }
		$sql .= "limit $rows_per_page offset $offset ";
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
	echo th_order_by('contact_type', 'Type', $order_by, $order);
	echo th_order_by('contact_organization', 'Organization', $order_by, $order);
	echo th_order_by('contact_name_given', 'First Name', $order_by, $order);
	echo th_order_by('contact_name_family', 'Last Name', $order_by, $order);
	echo th_order_by('contact_nickname', 'Nickname', $order_by, $order);
	echo th_order_by('contact_title', 'Title', $order_by, $order);
	echo th_order_by('contact_role', 'Role', $order_by, $order);
	//echo th_order_by('contact_email', 'Email', $order_by, $order);
	//echo th_order_by('contact_url', 'URL', $order_by, $order);
	//echo th_order_by('contact_time_zone', 'Time Zone', $order_by, $order);
	//echo th_order_by('contact_note', 'Notes', $order_by, $order);
	echo "<td align='right' width='42'>\n";
	echo "	<a href='contacts_edit.php' alt='add'>$v_link_label_add</a>\n";
	echo "</td>\n";
	echo "<tr>\n";

	if ($result_count > 0) {
		foreach($result as $row) {
			echo "<tr >\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".ucwords($row['contact_type'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['contact_organization']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['contact_name_given']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['contact_name_family']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['contact_nickname']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['contact_title']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['contact_role']."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['contact_email']."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['contact_url']."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['contact_time_zone']."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['contact_note']."&nbsp;</td>\n";
			echo "	<td valign='top' align='right'>\n";
			echo "		<a href='contacts_edit.php?id=".$row['contact_uuid']."&query_string=".urlencode($_SERVER["QUERY_STRING"])."' alt='edit'>$v_link_label_edit</a>\n";
			echo "		<a href='contacts_delete.php?id=".$row['contact_uuid']."' alt='delete' onclick=\"return confirm('Do you really want to delete this?')\">$v_link_label_delete</a>\n";
			echo "	</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results

	echo "<tr>\n";
	echo "<td colspan='15' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap>$paging_controls</td>\n";
	echo "		<td width='33.3%' align='right'>\n";
	echo "			<a href='contacts_edit.php' alt='add'>$v_link_label_add</a>\n";
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