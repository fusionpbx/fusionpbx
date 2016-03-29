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
if (permission_exists('call_center_queue_view')) {
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
	$document['title'] = $text['title-call_center_queues'];
	require_once "resources/paging.php";

//get http variables and set as php variables
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//show the content
	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "<tr>\n";
	echo "<td width='50%' align='left' nowrap='nowrap'><b>".$text['header-call_center_queues']."</b></td>\n";
	echo "<td width='50%' align='right'>\n";
	echo "	<input type='button' class='btn' value='".$text['button-agents']."' alt='".$text['button-agents']."' onclick=\"window.location='call_center_agents.php'\">\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	echo $text['description-call_center_queues']."<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</tr></table>\n";

	//get total call center queues count from the database
		$sql = "select count(*) as num_rows from v_call_center_queues where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$prep_statement = $db->prepare($sql);
		if ($prep_statement) {
			$prep_statement->execute();
			$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
			$total_call_center_queues = $row['num_rows'];
		}
		unset($prep_statement, $row);

	//prepare to page the results (reuse $sql from above)
		if (strlen($order_by) == 0) {
			$order_by = 'queue_name';
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

		$sql = "select * from v_call_center_queues ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		if (strlen($order_by) == 0) {
			$order_by = 'queue_name';
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
	echo th_order_by('queue_name', $text['label-queue_name'], $order_by, $order);
	echo th_order_by('queue_extension', $text['label-extension'], $order_by, $order);
	echo th_order_by('queue_strategy', $text['label-strategy'], $order_by, $order);
	//echo th_order_by('queue_moh_sound', $text['label-music_on_hold'], $order_by, $order);
	//echo th_order_by('queue_record_template', $text['label-record_template'], $order_by, $order);
	//echo th_order_by('queue_time_base_score', $text['label-time_base_score'], $order_by, $order);
	//echo th_order_by('queue_max_wait_time', $text['label-max_wait_time'], $order_by, $order);
	//echo th_order_by('queue_max_wait_time_with_no_agent', $text['label-max_wait_time_with_no_agent'], $order_by, $order);
	echo th_order_by('queue_tier_rules_apply', $text['label-tier_rules_apply'], $order_by, $order);
	//echo th_order_by('queue_tier_rule_wait_second', $text['label-tier_rule_wait_second'], $order_by, $order);
	//echo th_order_by('queue_tier_rule_no_agent_no_wait', $text['label-tier_rule_no_agent_no_wait'], $order_by, $order);
	//echo th_order_by('queue_discard_abandoned_after', $text['label-discard_abandoned_after'], $order_by, $order);
	//echo th_order_by('queue_abandoned_resume_allowed', $text['label-abandoned_resume_allowed'], $order_by, $order);
	//echo th_order_by('queue_tier_rule_wait_multiply_level', $text['label-tier_rule_wait_multiply_level'], $order_by, $order);
	echo th_order_by('queue_description', $text['label-description'], $order_by, $order);
	echo "<td class='list_control_icons'>";
	if (permission_exists('call_center_queue_add')) {
		if ($_SESSION['limit']['call_center_queues']['numeric'] == '' || ($_SESSION['limit']['call_center_queues']['numeric'] != '' && $total_call_center_queues < $_SESSION['limit']['call_center_queues']['numeric'])) {
			echo "<a href='call_center_queue_edit.php' alt='".$text['button-add']."'>".$v_link_label_add."</a>";
		}
	}
	echo "</td>\n";
	echo "</tr>\n";

	if ($result_count > 0) {
		foreach($result as $row) {
			$tr_link = (permission_exists('call_center_queue_edit')) ? "href='call_center_queue_edit.php?id=".$row[call_center_queue_uuid]."'" : null;
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			if (permission_exists('call_center_queue_edit')) {
				echo "<a href='call_center_queue_edit.php?id=".$row[call_center_queue_uuid]."'>".$row[queue_name]."</a>";
			}
			else {
				echo $row[queue_name];
			}
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row[queue_extension]."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row[queue_strategy]."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row[queue_moh_sound]."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row[queue_record_template]."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row[queue_time_base_score]."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row[queue_max_wait_time]."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row[queue_max_wait_time_with_no_agent]."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".ucwords($row[queue_tier_rules_apply])."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row[queue_tier_rule_wait_second]."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row[queue_tier_rule_no_agent_no_wait]."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row[queue_discard_abandoned_after]."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row[queue_abandoned_resume_allowed]."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row[queue_tier_rule_wait_multiply_level]."&nbsp;</td>\n";
			echo "	<td valign='top' class='row_stylebg'>".$row[queue_description]."&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>";
			if (permission_exists('call_center_queue_edit')) {
				echo "<a href='call_center_queue_edit.php?id=".$row[call_center_queue_uuid]."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
			if (permission_exists('call_center_queue_delete')) {
				echo "<a href='call_center_queue_delete.php?id=".$row[call_center_queue_uuid]."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results

	echo "<tr>\n";
	echo "<td colspan='17' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap>$paging_controls</td>\n";
	echo "		<td class='list_control_icons'>";
	if (permission_exists('call_center_queue_add')) {
		if ($_SESSION['limit']['call_center_queues']['numeric'] == '' || ($_SESSION['limit']['call_center_queues']['numeric'] != '' && $total_call_center_queues < $_SESSION['limit']['call_center_queues']['numeric'])) {
			echo "<a href='call_center_queue_edit.php' alt='".$text['button-add']."'>".$v_link_label_add."</a>";
		}
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
