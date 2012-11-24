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
if (permission_exists('call_center_agents_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}
require_once "includes/header.php";
require_once "includes/paging.php";

//get http values and set them to php variables
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//show content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"center\">\n";
	echo "		<br>";

	echo "<table width='100%' border='0'>\n";
	echo "<tr>\n";
	echo "<td width='50%' align='left' nowrap='nowrap'><b>Call Center Agent List</b></td>\n";
	echo "<td width='50%' align='right'>\n";
	echo "	<input type='button' class='btn' name='' alt='add' onclick=\"window.location='call_center_agent_status.php'\" value='Status'>\n";
	echo "	<input type='button' class='btn' name='' alt='add' onclick=\"window.location='call_center_queues.php'\" value='Back'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	echo "List of call center agents.<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</tr></table>\n";

	$sql = "select * from v_call_center_agents ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	if (strlen($order_by) == 0) {
		$order_by = 'agent_name';
		$order = 'asc';
	}
	else {
		$sql .= "order by $order_by $order ";
	}
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$num_rows = count($result);
	unset ($prep_statement, $result, $sql);
	$rows_per_page = 100;
	$param = "";
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; } 
	list($paging_controls, $rows_per_page, $var_3) = paging($num_rows, $param, $rows_per_page); 
	$offset = $rows_per_page * $page; 

	$sql = "select * from v_call_center_agents ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	if (strlen($order_by) == 0) {
		$order_by = 'agent_name';
		$order = 'asc';
	}
	else {
		$sql .= "order by $order_by $order ";
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
	//echo th_order_by('domain_uuid', 'domain_uuid', $order_by, $order);
	echo th_order_by('agent_name', 'Agent Name', $order_by, $order);
	echo th_order_by('agent_type', 'Type', $order_by, $order);
	echo th_order_by('agent_call_timeout', 'Call Timeout', $order_by, $order);
	echo th_order_by('agent_contact', 'Contact', $order_by, $order);
	echo th_order_by('agent_max_no_answer', 'Max No Answer', $order_by, $order);
	echo th_order_by('agent_status', 'Status', $order_by, $order);
	//echo th_order_by('agent_wrap_up_time', 'Wrap Up Time', $order_by, $order);
	//echo th_order_by('agent_reject_delay_time', 'Reject Delay Time', $order_by, $order);
	//echo th_order_by('agent_busy_delay_time', 'Busy Delay Time', $order_by, $order);
	echo "<td align='right' width='42'>\n";
	if (permission_exists('call_center_agents_add')) {
		echo "	<a href='call_center_agent_edit.php' alt='add'>$v_link_label_add</a>\n";
	}
	echo "</td>\n";
	echo "<tr>\n";

	if ($result_count == 0) { //no results
	}
	else { //received results
		foreach($result as $row) {
			echo "<tr >\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row[domain_uuid]."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row[agent_name]."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row[agent_type]."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row[agent_call_timeout]."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row[agent_contact]."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row[agent_max_no_answer]."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row[agent_status]."&nbsp;</td>\n";			
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row[agent_wrap_up_time]."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row[agent_reject_delay_time]."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row[agent_busy_delay_time]."&nbsp;</td>\n";
			echo "	<td valign='top' align='right'>\n";
			if (permission_exists('call_center_agents_edit')) {
				echo "		<a href='call_center_agent_edit.php?id=".$row[call_center_agent_uuid]."' alt='edit'>$v_link_label_edit</a>\n";
			}
			if (permission_exists('call_center_agents_delete')) {
				echo "		<a href='call_center_agent_delete.php?id=".$row[call_center_agent_uuid]."' alt='delete' onclick=\"return confirm('Do you really want to delete this?')\">$v_link_label_delete</a>\n";
			}
			//echo "		<input type='button' class='btn' name='' alt='edit' onclick=\"window.location='call_center_agent_edit.php?id=".$row[call_center_agent_uuid]."'\" value='e'>\n";
			//echo "		<input type='button' class='btn' name='' alt='delete' onclick=\"if (confirm('Are you sure you want to delete this?')) { window.location='call_center_agent_delete.php?id=".$row[call_center_agent_uuid]."' }\" value='x'>\n";
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
	echo "		<td width='33.3%' align='center' nowrap>$paging_controls</td>\n";
	echo "		<td width='33.3%' align='right'>\n";
	if (permission_exists('call_center_agents_add')) {
		echo "			<a href='call_center_agent_edit.php' alt='add'>$v_link_label_add</a>\n";
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