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
if (permission_exists('service_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

require_once "resources/header.php";
require_once "resources/paging.php";

$order_by = $_GET["order_by"];
$order = $_GET["order"];

if (strlen($_GET["a"]) > 0) {
	$service_uuid = $_GET["id"];
	$sql = "select * from v_services ";
	$sql .= "where service_uuid = '$service_uuid' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		$domain_uuid = $row["domain_uuid"];
		$service_name = $row["service_name"];
		$service_type = $row["service_type"];
		$service_data = $row["service_data"];
		$service_cmd_start = $row["service_cmd_start"];
		$service_cmd_stop = $row["service_cmd_stop"];
		$service_description = $row["service_description"];
		break; //limit to 1 row
	}
	unset ($prep_statement);

	if ($_GET["a"] == "stop") {
		$_SESSION["message"] = $text['message-stopping'].': '.$service_name;
		shell_exec($service_cmd_stop);
	}
	if ($_GET["a"] == "start") {
		$_SESSION["message"] = $text['message-starting'].': '.$service_name;
		shell_exec($service_cmd_start);
	}
	header("Location: services.php");
	return;
}

//check if a process is running
	function is_process_running($pid) {
		$status = shell_exec( 'ps -p ' . $pid );
		$status_array = explode ("\n", $status);
		if (strlen(trim($status_array[1])) > 0) {
			return true;
		}
		else {
			return false;
		}
	}

	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"center\">\n";
	echo "		<br>";

	echo "<table width='100%' border='0'>\n";
	echo "<tr>\n";
	echo "<td width='50%' align='left' nowrap='nowrap'><b>Services</b></td>\n";
	echo "<td width='50%' align='right'>&nbsp;</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	echo "Shows a list of processes, the status of the process and provides control to start and stop the process.<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</tr></table>\n";

	$sql = "select * from v_services ";
	if (strlen($order_by)> 0) { $sql .= "order by $order_by $order "; }
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$num_rows = count($result);
	unset ($prep_statement, $result, $sql);
	$rows_per_page = 10;
	$param = "";
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page, $var_3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

	$sql = "select * from v_services ";
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
	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo th_order_by('service_name', 'Name', $order_by, $order);
	echo "<th>Status</th>\n";
	echo "<th>Action</th>\n";
	echo th_order_by('service_description', 'Description', $order_by, $order);
	echo "<td class='list_control_icons'>";
	if (permission_exists('service_add')) {
		echo "<a href='service_edit.php' alt='add'>$v_link_label_add</a>";
	}
	echo "</td>\n";
	echo "</tr>\n";

	if ($result_count == 0) {
		//no results
	}
	else { //received results
		foreach($result as $row) {
			$tr_link = (permission_exists('service_edit')) ? "href='service_edit.php?id=".$row[service_uuid]."'" : null;
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			if (permission_exists('service_edit')) {
				echo "<a href='service_edit.php?id=".$row[service_uuid]."'>".$row[service_name]."</a>";
			}
			else {
				echo $row[service_name];
			}
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>\n";
			$pid = file_get_contents($row[service_data]);
			if (is_process_running($pid)) {
				echo "<strong>Running</strong>";
			}
			else {
				echo "<strong>Stopped</strong>";
			}
			echo "</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>\n";
			if (is_process_running($pid)) {
				echo "		<a href='services.php?id=".$row[service_uuid]."&a=stop' alt='stop'>Stop</a>";
			}
			else {
				echo "		<a href='services.php?id=".$row[service_uuid]."&a=start' alt='start'>Start</a>";
			}
			echo "</td>\n";
			echo "	<td valign='top' class='row_stylebg'>".$row[service_description]."&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>";
			if (permission_exists('service_edit')) {
				echo "<a href='service_edit.php?id=".$row[service_uuid]."' alt='edit'>$v_link_label_edit</a>";
			}
			if (permission_exists('service_delete')) {
				echo "<a href='service_delete.php?id=".$row[service_uuid]."' alt='delete' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			}
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
	echo "		<td class='list_control_icons'>";
	if (permission_exists('service_add')) {
		echo 		"<a href='service_edit.php' alt='add'>$v_link_label_add</a>";
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
	require_once "resources/footer.php";

?>