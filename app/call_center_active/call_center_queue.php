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
	Portions created by the Initial Developer are Copyright (C) 2008-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
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

//get the variables
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//add the search term
	$search = strtolower($_GET["search"]);
	if (strlen($search) > 0) {
		$sql_search = " (";
		$sql_search .= "lower(queue_name) like :search ";
		$sql_search .= "or lower(queue_description) like :search ";
		$sql_search .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}

//get the call center queue count
	$sql = "select count(*) from v_call_center_queues ";
	$sql .= "where domain_uuid = :domain_uuid ";
	if (isset($sql_search)) {
		$sql .= "and ".$sql_search;
	}
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//paging the records
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "&search=".$search;
	$page = is_numeric($_GET['page']) ? $_GET['page'] : 0;
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the call center queues
	$sql = str_replace('count(*)', '*', $sql);
	$sql .= order_by($order_by, $order);
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$call_center_queues = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//include header
	$document['title'] = $text['title-active_call_center'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['header-active_call_center']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown='list_search_reset();'>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search','style'=>($search != '' ? 'display: none;' : null)]);
	echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'call_center_queue.php','style'=>($search == '' ? 'display: none;' : null)]);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['description-active_call_center']."\n";
	echo "<br /><br />\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
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
	echo "</tr>\n";

	if (is_array($call_center_queues)) {
		$x = 0;
		foreach($call_center_queues as $row) {
			$list_row_url = PROJECT_PATH."/app/call_center_active/call_center_active.php?queue_name=".escape($row['call_center_queue_uuid'])."&name=".urlencode(escape($row['queue_name']));
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			echo "	<td><a href='".$list_row_url."'>".escape($row['queue_name'])."</a></td>\n";
			echo "	<td>".escape($row['queue_extension'])."</td>\n";
			echo "	<td>".escape($row['queue_strategy'])."</td>\n";
			//echo "	<td>".escape($row[queue_moh_sound])."</td>\n";
			//echo "	<td>".escape($row[queue_record_template])."</td>\n";
			//echo "	<td>".escape($row[queue_time_base_score])."</td>\n";
			//echo "	<td>".escape($row[queue_max_wait_time])."</td>\n";
			//echo "	<td>".escape($row[queue_max_wait_time_with_no_agent])."</td>\n";
			//echo "	<td>".escape($row[queue_tier_rules_apply])."</td>\n";
			//echo "	<td>".escape($row[queue_tier_rule_wait_second])."</td>\n";
			//echo "	<td>".escape($row[queue_tier_rule_no_agent_no_wait])."</td>\n";
			//echo "	<td>".escape($row[queue_discard_abandoned_after])."</td>\n";
			//echo "	<td>".escape($row[queue_abandoned_resume_allowed])."</td>\n";
			//echo "	<td>".escape($row[queue_tier_rule_wait_multiply_level])."</td>\n";
			echo "	<td class='description overflow hide-xs'>".escape($row['queue_description'])."</td>\n";
			echo "</tr>\n";
			$x++;
		}
		unset($call_center_queues);
	}

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";

//show the footer
	require_once "resources/footer.php";

?>