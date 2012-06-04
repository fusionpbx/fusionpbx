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
if (if_group("admin") || if_group("superadmin")) {
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

//get the agent status session array
	//unset($_SESSION["array_agent_status"]);
	if (!is_array($_SESSION["array_agent_status"])) {
		$sql = "SELECT var_name, var_value FROM v_vars ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and var_cat = 'Queues Agent Status' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach($result as $field) {
			$_SESSION["array_agent_status"][$field[var_value]] = $field[var_name];
		}
	}

//send the content to the browser
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"center\">\n";
	echo "		<br>";


	echo "<table width='100%' border='0'>\n";
	echo "<tr>\n";
	echo "<td width='50%' nowrap='nowrap' align='left'><b>Fifo Agent Status Log List</b></td>\n";
	echo "<td width='50%' align='right'>&nbsp;</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td colspan='2' align='left'>\n";
	echo "Agent Status History<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</tr></table>\n";


	$sql = "";
	$sql .= " select * from v_fifo_agent_status_logs ";
	if (strlen($order_by)> 0) { $sql .= "order by $order_by $order "; }
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

	$sql = "";
	$sql .= " select * from v_fifo_agent_status_logs ";
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
	echo th_order_by('username', 'Username', $order_by, $order);
	echo th_order_by('agent_status', 'Status', $order_by, $order);
	echo th_order_by('uuid', 'UUID', $order_by, $order);
	echo th_order_by('add_date', 'Add Date', $order_by, $order);
	echo "<td align='right' width='42'>\n";
	echo "	<a href='v_fifo_agent_status_logs_edit.php' alt='add'>$v_link_label_add</a>\n";
	//echo "	<input type='button' class='btn' name='' alt='add' onclick=\"window.location='v_fifo_agent_status_logs_edit.php'\" value='+'>\n";
	echo "</td>\n";
	echo "<tr>\n";

	if ($result_count == 0) { //no results
	}
	else { //received results
		foreach($result as $row) {
			//print_r( $row );
			//set the php variables
				$agent_status = $row[agent_status];

			//get the agent description
				$agent_status_desc = $_SESSION["array_agent_status"][$agent_status];

			echo "<tr >\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row[username]."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$agent_status_desc."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row[uuid]."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row[add_date]."</td>\n";
			echo "	<td valign='top' align='right'>\n";
			echo "		<a href='v_fifo_agent_status_logs_edit.php?id=".$row[fifo_agent_status_log_id]."' alt='edit'>$v_link_label_edit</a>\n";
			echo "		<a href='v_fifo_agent_status_logs_delete.php?id=".$row[fifo_agent_status_log_id]."' alt='delete' onclick=\"return confirm('Do you really want to delete this?')\">$v_link_label_delete</a>\n";
			//echo "		<input type='button' class='btn' name='' alt='edit' onclick=\"window.location='v_fifo_agent_status_logs_edit.php?id=".$row[fifo_agent_status_log_id]."'\" value='e'>\n";
			//echo "		<input type='button' class='btn' name='' alt='delete' onclick=\"if (confirm('Are you sure you want to delete this?')) { window.location='v_fifo_agent_status_logs_delete.php?id=".$row[fifo_agent_status_log_id]."' }\" value='x'>\n";
			echo "	</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results


	echo "<tr>\n";
	echo "<td colspan='5' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap>$paging_controls</td>\n";
	echo "		<td width='33.3%' align='right'>\n";
	echo "			<a href='v_fifo_agent_status_logs_edit.php' alt='add'>$v_link_label_add</a>\n";
	//echo "		<input type='button' class='btn' name='' alt='add' onclick=\"window.location='v_fifo_agent_status_logs_edit.php'\" value='+'>\n";
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
unset ($result_count);
unset ($result);
unset ($key);
unset ($val);
unset ($c);
?>
