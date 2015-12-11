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

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('bdr_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//additional includes
	require_once "resources/header.php";
	require_once "resources/paging.php";
	
if ($_SESSION['server']['bdr_fusionpbx_enable']['boolean'] == true) {	
//get the  my node id
	$sql = "select bdr.bdr_get_local_nodeid()";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll();
	$result_count = count($result);
	unset ($prep_statement, $sql);
	$replace[] = '(';
	$replace[] = ')';
	$my_node_id = explode(',',str_replace($replace,'',$result[0]['bdr_get_local_nodeid']));

//get the  node list
	$sql = "select * from bdr.bdr_nodes";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll();
	$result_count = count($result);
	unset ($prep_statement, $sql);

	$sql = "SELECT bdr.bdr_version()";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$resultversion = $prep_statement->fetchAll();
	unset ($prep_statement, $sql);

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<th class='th' colspan='2' align='left'>BDR Information</th>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td width='20%' class='vncell' style='text-align: left;'>\n";
	echo "BDR Version\n";
	echo "</td>\n";
	echo "<td class='row_style1'>\n";
	echo $resultversion[0]['bdr_version'] . "\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td width='20%' class='vncell' style='text-align: left;'>\n";
	echo "Node Count\n";
	echo "</td>\n";
	echo "<td class='row_style1'>\n";
	echo "	$result_count\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n<br/><br/>";

//table headers
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";
	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
//	echo "<tr>\n";
//	echo "<td class=\"th\" colspan=\"8\" align=\"left\">Nodes</td>\n";
//	echo "</tr>\n";
	echo "<tr>\n";
	echo "<th class=\"th\" align=\"left\">ID</th>\n";
	echo "<th class=\"th\" align=\"left\">Status</th>\n";
	echo "<th class=\"th\" align=\"left\">Name</th>\n";
	echo "<th class=\"th\" align=\"left\">Local</th>\n";
	echo "<th class=\"th\" align=\"left\">Database</th>\n";
	echo "<th class=\"th\" align=\"left\">Active</th>\n";
	echo "<th class=\"th\" align=\"left\">Retained Bytes</th>\n";
	echo "<th class=\"th\" align=\"left\">Lag Bytes</th>\n";
	echo "<th>&nbsp;</th>\n";
	echo "</tr>\n";
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";
	foreach ($result as $row) {
	
		//get the status, bytes, database list
		$sql = "SELECT slot_name, database, active, pg_xlog_location_diff(pg_current_xlog_insert_location(), restart_lsn) AS retained_bytes";
		$sql .= " FROM pg_replication_slots WHERE plugin = 'bdr'";
		$sql .= " AND slot_name like '%" . $row['node_sysid'] . "%' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result1 = $prep_statement->fetchAll();
		unset ($prep_statement, $sql);
		
		//get lag bytes
		$sql = "select pg_xlog_location_diff(pg_current_xlog_insert_location(), flush_location) AS lag_bytes";
		$sql .= " FROM pg_stat_replication WHERE ";
		$sql .= " application_name like '%" . $row['node_sysid'] . "%' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result2 = $prep_statement->fetchAll();
		unset ($prep_statement, $sql);
		
		echo "<tr>\n";
		echo "	<td valign='top' class='".$row_style[$c]."'>" . $row['node_sysid'] . "</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."'>";
		if ($row['node_status'] == "r") { 
			echo "Ready"; 
		} else if ($row['node_status'] == "k") { 
			echo "Removed/Killed"; 
		} else if ($row['node_status'] == "i") { 
			echo "Initializing"; 
		} else if ($row['node_status'] == "b") { 
			echo "Bootstrapping"; 
		} else if ($row['node_status'] == "c") { 
			echo "Catching Up"; 
		} else if ($row['node_status'] == "o") { 
			echo "Caught Up/Waiting"; 
		} else { 
			echo "Unknown"; 
		}
		echo "</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."'>" . $row['node_name'] . "</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."'>";
		if ($row['node_sysid'] == $my_node_id[0]) { echo "Yes"; $is_current = 1; } else { echo "No"; $is_current = 0; }
		echo "</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."'>" . $result1[0]['database'] . "</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."'>";
		if ($is_current != 1) {if ($result1[0]['active'] == 1) { echo "Yes"; } else { echo "No"; }};
		echo "</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."'>" . $result1[0]['retained_bytes'] . "</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."'>" . $result2[0]['lag_bytes'] . "</td>\n";
		echo "	<td class='list_control_icons'>";
/*		if (permission_exists('extension_edit')) {
			echo "<a href='extension_edit.php?id=".$row['extension_uuid']."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
		}*/
		if (permission_exists('bdr_delete')) {
			echo "<a href='bdr_delete.php?action=delete&id=".$row['node_name']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
		}
		echo "</td>\n";

		echo "</tr>\n";
		if ($c==0) { $c=1; } else { $c=0; }
	}
 	echo "	</table>\n";

}
//include the footer
	require_once "resources/footer.php";

?>