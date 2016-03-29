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
if (permission_exists('call_center_active_view')) {
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
	$document['title'] = $text['title-active_call_center'];
	require_once "resources/paging.php";

//get the variables
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//show the content
	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "<tr>\n";
	echo "<td width='50%' align=\"left\" nowrap=\"nowrap\"><b>".$text['header-active_call_center']."</b></td>\n";
	echo "<td width='50%' align=\"right\">\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align=\"left\" colspan='2'>\n";
	echo $text['description-active_call_center']."<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</tr></table>\n";

	$sql = "select * from v_call_center_queues ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	if (strlen($order_by)> 0) { $sql .= "order by $order_by $order "; }
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
	//echo th_order_by('queue_tier_rules_apply', $text['label-tier_rules_apply'], $order_by, $order);
	//echo th_order_by('queue_tier_rule_wait_second', $text['label-tier_rule_wait_second'], $order_by, $order);
	//echo th_order_by('queue_tier_rule_no_agent_no_wait', $text['label-tier_rule_no_agent_no_wait'], $order_by, $order);
	//echo th_order_by('queue_discard_abandoned_after', $text['label-discard_abandoned_after'], $order_by, $order);
	//echo th_order_by('queue_abandoned_resume_allowed', $text['label-abandoned_resume_allowed'], $order_by, $order);
	//echo th_order_by('queue_tier_rule_wait_multiply_level', $text['label-tier_rule_wait_multiply_level'], $order_by, $order);
	echo th_order_by('queue_description', $text['label-description'], $order_by, $order);
	echo "<td class='list_control_icon'>\n";
	//echo "	<a href='call_center_queue_edit.php' alt='add'>$v_link_label_add</a>\n";
	echo "</td>\n";
	echo "</tr>\n";

	if ($result_count == 0) { //no results
	}
	else { //received results
		foreach($result as $row) {
			$tr_link = "href='".PROJECT_PATH."/app/call_center_active/call_center_active.php?queue_name=".$row[queue_name]."'";
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]."'><a href='".PROJECT_PATH."/app/call_center_active/call_center_active.php?queue_name=".$row[queue_name]."'>".$row[queue_name]."</a></td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row[queue_extension]."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row[queue_strategy]."</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row[queue_moh_sound]."</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row[queue_record_template]."</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row[queue_time_base_score]."</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row[queue_max_wait_time]."</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row[queue_max_wait_time_with_no_agent]."</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row[queue_tier_rules_apply]."</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row[queue_tier_rule_wait_second]."</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row[queue_tier_rule_no_agent_no_wait]."</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row[queue_discard_abandoned_after]."</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row[queue_abandoned_resume_allowed]."</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row[queue_tier_rule_wait_multiply_level]."</td>\n";
			echo "	<td valign='top' class='row_stylebg'>".$row[queue_description]."&nbsp;</td>\n";
			echo "	<td class='list_control_icon'>\n";
			echo "		<a href='".PROJECT_PATH."/app/call_center_active/call_center_active.php?queue_name=".$row[queue_name]."' alt='".$text['button-view']."'>$v_link_label_view</a>\n";
			//echo "		<a href='call_center_queue_delete.php?id=".$row[call_center_queue_uuid]."' alt='delete' onclick=\"return confirm('Do you really want to delete this?')\">$v_link_label_delete</a>\n";
			//echo "		<input type='button' class='btn' name='' alt='edit' onclick=\"window.location='call_center_queue_edit.php?id=".$row[call_center_queue_uuid]."'\" value='e'>\n";
			//echo "		<input type='button' class='btn' name='' alt='delete' onclick=\"if (confirm('Are you sure you want to delete this?')) { window.location='call_center_queue_delete.php?id=".$row[call_center_queue_uuid]."' }\" value='x'>\n";
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
	echo "		<td width='33.3%' align='right'>\n";
	//echo "			<a href='call_center_queue_edit.php' alt='add'>$v_link_label_add</a>\n";
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
