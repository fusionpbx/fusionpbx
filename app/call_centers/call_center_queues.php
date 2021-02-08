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
	Portions created by the Initial Developer are Copyright (C) 2008-2020
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permisission
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

//get posted data
	if (is_array($_POST['call_center_queues'])) {
		$action = $_POST['action'];
		$search = $_POST['search'];
		$call_center_queues = $_POST['call_center_queues'];
	}

//process the http post data by action
	if ($action != '' && is_array($call_center_queues) && @sizeof($call_center_queues) != 0) {
		switch ($action) {
			case 'copy':
				if (permission_exists('call_center_queue_add')) {
					$obj = new call_center;
					$obj->copy_queues($call_center_queues);
				}
				break;
			case 'delete':
				if (permission_exists('call_center_queue_delete')) {
					$obj = new call_center;
					$obj->delete_queues($call_center_queues);
				}
				break;
		}

		header('Location: call_center_queues.php'.($search != '' ? '?search='.urlencode($search) : null));
		exit;
	}

//get http variables and set as php variables
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

//get total call center queues count from the database
	$sql = "select count(*) from v_call_center_queues ";
	$sql .= "where domain_uuid = :domain_uuid ";
	if (isset($sql_search)) {
		$sql .= "and ".$sql_search;
	}
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "&search=".$search;
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the list
	$sql = str_replace('count(*)', '*', $sql);
	$sql .= order_by($order_by, $order, 'queue_name', 'asc');
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$result = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//includes and title
	$document['title'] = $text['title-call_center_queues'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['header-call_center_queues']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('call_center_imports')) {
		echo button::create(['type'=>'button','label'=>$text['button-import'],'icon'=>$_SESSION['theme']['button_icon_import'],'link'=>PROJECT_PATH.'/app/call_center_imports/call_center_imports.php?type=call_center_queues']);
	}
	if (permission_exists('call_center_agent_view')) {
		echo button::create(['type'=>'button','label'=>$text['button-agents'],'icon'=>'users','link'=>'call_center_agents.php']);
	}
	if (permission_exists('call_center_wallboard')) {
		echo button::create(['type'=>'button','label'=>$text['button-wallboard'],'icon'=>'th','link'=>PROJECT_PATH.'/app/call_center_wallboard/call_center_wallboard.php']);
	}
	$margin_left = permission_exists('call_center_agent_view') || permission_exists('call_center_wallboard') ? 'margin-left: 15px;' : null;
	if (permission_exists('call_center_queue_add') && (!is_numeric($_SESSION['limit']['call_center_queues']['numeric']) || $num_rows <= $_SESSION['limit']['call_center_queues']['numeric'])) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','style'=>$margin_left,'link'=>'call_center_queue_edit.php']);
		unset($margin_left);
	}
	if (permission_exists('call_center_queue_add') && $result && (!is_numeric($_SESSION['limit']['call_center_queues']['numeric']) || $num_rows <= $_SESSION['limit']['call_center_queues']['numeric'])) {
		echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'name'=>'btn_copy','style'=>$margin_left,'onclick'=>"modal_open('modal-copy','btn_copy');"]);
		unset($margin_left);
	}
	if (permission_exists('call_center_queue_delete') && $result) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','style'=>$margin_left,'onclick'=>"modal_open('modal-delete','btn_delete');"]);
		unset($margin_left);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown='list_search_reset();'>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search','style'=>($search != '' ? 'display: none;' : null)]);
	echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'call_center_queues.php','style'=>($search == '' ? 'display: none;' : null)]);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('call_center_queue_add') && $result && (!is_numeric($_SESSION['limit']['call_center_queues']['numeric']) || $num_rows <= $_SESSION['limit']['call_center_queues']['numeric'])) {
		echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('copy'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('call_center_queue_delete') && $result) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['description-call_center_queues']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('call_center_queue_add') || permission_exists('call_center_queue_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle();' ".($result ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
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
	echo th_order_by('queue_description', $text['label-description'], $order_by, $order, null, "class='hide-sm-dn'");
	if (permission_exists('call_center_queue_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($result)) {
		$x = 0;
		foreach($result as $row) {
			if (permission_exists('call_center_queue_edit')) {
				$list_row_url = "call_center_queue_edit.php?id=".urlencode($row['call_center_queue_uuid']);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('call_center_queue_add') || permission_exists('call_center_queue_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='call_center_queues[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='call_center_queues[$x][uuid]' value='".escape($row['call_center_queue_uuid'])."' />\n";
				echo "	</td>\n";
			}
			echo "	<td>";
			if (permission_exists('call_center_queue_edit')) {
				echo "	<a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['queue_name'])."</a>";
			}
			else {
				echo "	".escape($row[queue_name]);
			}
			echo "	</td>\n";
			echo "	<td>".escape($row['queue_extension'])."</td>\n";
			echo "	<td>".escape($row['queue_strategy'])."</td>\n";
			//echo "	<td>".escape($row[queue_moh_sound])."&nbsp;</td>\n";
			//echo "	<td>".escape($row[queue_record_template])."&nbsp;</td>\n";
			//echo "	<td>".escape($row[queue_time_base_score])."&nbsp;</td>\n";
			//echo "	<td>".escape($row[queue_max_wait_time])."&nbsp;</td>\n";
			//echo "	<td>".escape($row[queue_max_wait_time_with_no_agent])."&nbsp;</td>\n";
			echo "	<td>".ucwords(escape($row['queue_tier_rules_apply']))."</td>\n";
			//echo "	<td>".escape($row[queue_tier_rule_wait_second])."&nbsp;</td>\n";
			//echo "	<td>".escape($row[queue_tier_rule_no_agent_no_wait])."&nbsp;</td>\n";
			//echo "	<td>".escape($row[queue_discard_abandoned_after])."&nbsp;</td>\n";
			//echo "	<td>".escape($row[queue_abandoned_resume_allowed])."&nbsp;</td>\n";
			//echo "	<td>".escape($row[queue_tier_rule_wait_multiply_level])."&nbsp;</td>\n";
			echo "	<td class='description overflow hide-sm-dn'>".escape($row['queue_description'])."</td>\n";
			if (permission_exists('call_center_queue_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($result);
	}

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>\n";

//show the footer
	require_once "resources/footer.php";

?>
