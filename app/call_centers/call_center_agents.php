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
if (permission_exists('call_center_agent_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//includes and title
	require_once "resources/header.php";
	$document['title'] = $text['title-call_center_agents'];
	require_once "resources/paging.php";

//get http values and set them to php variables
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//show content
	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "<tr>\n";
	echo "<td width='50%' align='left' nowrap='nowrap'><b>".$text['header-call_center_agents']."</b></td>\n";
	echo "<td width='50%' align='right'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='call_center_queues.php'\" value='".$text['button-back']."'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-status']."' onclick=\"window.location='call_center_agent_status.php'\" value='".$text['button-status']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	echo $text['description-call_center_agents']."<br /><br />\n";
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
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
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

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	//echo th_order_by('domain_uuid', 'domain_uuid', $order_by, $order);
	echo th_order_by('agent_name', $text['label-agent_name'], $order_by, $order);
	echo th_order_by('agent_id', $text['label-agent_id'], $order_by, $order);
	echo th_order_by('agent_type', $text['label-type'], $order_by, $order);
	echo th_order_by('agent_call_timeout', $text['label-call_timeout'], $order_by, $order);
	echo th_order_by('agent_contact', $text['label-contact'], $order_by, $order);
	echo th_order_by('agent_max_no_answer', $text['label-max_no_answer'], $order_by, $order);
	echo th_order_by('agent_status', $text['label-default_status'], $order_by, $order);
	//echo th_order_by('agent_wrap_up_time', $text['label-wrap_up_time'], $order_by, $order);
	//echo th_order_by('agent_reject_delay_time', $text['label-reject_delay_time'], $order_by, $order);
	//echo th_order_by('agent_busy_delay_time', $text['label-busy_delay_time'], $order_by, $order);
	echo "<td class='list_control_icons'>";
	if (permission_exists('call_center_agent_add')) {
		echo "<a href='call_center_agent_edit.php' alt='".$text['button-add']."'>".$v_link_label_add."</a>";
	}
	echo "</td>\n";
	echo "</tr>\n";

	if ($result_count == 0) { //no results
	}
	else { //received results
		foreach($result as $row) {
			$tr_link = (permission_exists('call_center_agent_edit')) ? "href='call_center_agent_edit.php?id=".$row['call_center_agent_uuid']."'" : null;
			echo "<tr ".$tr_link.">\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row[domain_uuid]."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			if (permission_exists('call_center_agent_edit')) {
				echo "<a href='call_center_agent_edit.php?id=".$row['call_center_agent_uuid']."'>".$row['agent_name']."</a>";
			}
			else {
				echo $row['agent_name'];
			}
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['agent_id']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['agent_type']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['agent_call_timeout']."&nbsp;</td>\n";
			$agent_contact = $row['agent_contact'];
			// parse out gateway uuid
			$bridge_statement = explode('/', $row['agent_contact']);
			if ($bridge_statement[0] == 'sofia' && $bridge_statement[1] == 'gateway' && is_uuid($bridge_statement[2])) {
				// retrieve gateway name from db
				$sql = "select gateway from v_gateways where gateway_uuid = '".$bridge_statement[2]."' ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				if (count($result) > 0) {
					$gateway_name = $result[0]['gateway'];
					$agent_contact = str_replace($bridge_statement[2], $gateway_name, $agent_contact);
				}
				unset ($prep_statement, $sql, $bridge_statement);
			}
			echo "	<td valign='top' class='".$row_style[$c]."'>".$agent_contact."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['agent_max_no_answer']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['agent_status']."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row[agent_wrap_up_time]."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row[agent_reject_delay_time]."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row[agent_busy_delay_time]."&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>\n";
			if (permission_exists('call_center_agent_edit')) {
				echo "<a href='call_center_agent_edit.php?id=".$row['call_center_agent_uuid']."' alt='".$text['button-edit']."'>".$v_link_label_edit."</a>";
			}
			if (permission_exists('call_center_agent_delete')) {
				echo "<a href='call_center_agent_delete.php?id=".$row['call_center_agent_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">".$v_link_label_delete."</a>";
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
	echo "		<td class='list_control_icons'>";
	if (permission_exists('call_center_agent_add')) {
		echo 		"<a href='call_center_agent_edit.php' alt='".$text['button-add']."'>".$v_link_label_add."</a>";
	}
	echo "		</td>\n";
	echo "	</tr>\n";
 	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br><br>";

//show the footer
	require_once "resources/footer.php";
?>