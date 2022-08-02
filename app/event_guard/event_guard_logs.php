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
	Portions created by the Initial Developer are Copyright (C) 2022
	the Initial Developer. All Rights Reserved.
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('event_guard_log_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the http post data
	if (is_array($_POST['event_guard_logs'])) {
		$action = $_POST['action'];
		$search = $_POST['search'];
		$event_guard_logs = $_POST['event_guard_logs'];
	}

//process the http post data by action
	if ($action != '' && is_array($event_guard_logs) && @sizeof($event_guard_logs) != 0) {

		switch ($action) {
			case 'copy':
				if (permission_exists('event_guard_log_add')) {
					$obj = new event_guard;
					$obj->copy($event_guard_logs);
				}
				break;
			case 'toggle':
				if (permission_exists('event_guard_log_edit')) {
					$obj = new event_guard;
					$obj->toggle($event_guard_logs);
				}
				break;
			case 'delete':
				if (permission_exists('event_guard_log_delete')) {
					$obj = new event_guard;
					$obj->delete($event_guard_logs);
				}
				break;
		}

		//redirect the user
		header('Location: event_guard_logs.php'.($search != '' ? '?search='.urlencode($search) : null));
		exit;
	}

//get order and order by
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//add the search
	if (isset($_GET["search"])) {
		$search = strtolower($_GET["search"]);
	}

//get the count
	$sql = "select count(event_guard_log_uuid) ";
	$sql .= "from v_event_guard_logs ";
	if (isset($search)) {
		$sql .= "where (";
		$sql .= "	hostname like :search ";
		$sql .= "	or filter like :search ";
		$sql .= "	or ip_address like :search ";
		$sql .= "	or extension like :search ";
		$sql .= "	or user_agent like :search ";
		$sql .= "	or log_status like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');
	unset($sql, $parameters);

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = $search ? "&search=".$search : null;
	$page = is_numeric($_GET['page']) ? $_GET['page'] : 0;
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the list
	$sql = "select ";
	$sql .= "event_guard_log_uuid, ";
	$sql .= "hostname, ";
	$sql .= "log_date, ";
	$sql .= "filter, ";
	$sql .= "ip_address, ";
	$sql .= "extension, ";
	$sql .= "user_agent, ";
	$sql .= "log_status ";
	$sql .= "from v_event_guard_logs ";
	if (isset($_GET["search"])) {
		$sql .= "where (";
		$sql .= "	hostname like :search ";
		$sql .= "	or filter like :search ";
		$sql .= "	or ip_address like :search ";
		$sql .= "	or extension like :search ";
		$sql .= "	or user_agent like :search ";
		$sql .= "	or log_status like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	$sql .= order_by($order_by, $order, 'log_date', 'desc');
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$event_guard_logs = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//additional includes
	$document['title'] = $text['title-event_guard_logs'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-event_guard_logs']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('event_guard_log_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','name'=>'btn_add','link'=>'event_guard_log_edit.php']);
	}
	if (permission_exists('event_guard_log_add') && $event_guard_logs) {
		echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'id'=>'btn_copy','name'=>'btn_copy','style'=>'display:none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
	}
	if (permission_exists('event_guard_log_edit') && $event_guard_logs) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$_SESSION['theme']['button_icon_toggle'],'id'=>'btn_toggle','name'=>'btn_toggle','style'=>'display:none;','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	}
	if (permission_exists('event_guard_log_delete') && $event_guard_logs) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display:none;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown='list_search_reset();'>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search','style'=>($search != '' ? 'display: none;' : null)]);
	echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'event_guard_logs.php','style'=>($search == '' ? 'display: none;' : null)]);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('event_guard_log_add') && $event_guard_logs) {
		echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('copy'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('event_guard_log_edit') && $event_guard_logs) {
		echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('event_guard_log_delete') && $event_guard_logs) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['title_description-event_guard_logs']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('event_guard_log_add') || permission_exists('event_guard_log_edit') || permission_exists('event_guard_log_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".($event_guard_logs ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	echo th_order_by('hostname', $text['label-hostname'], $order_by, $order);
	echo th_order_by('log_date', $text['label-log_date'], $order_by, $order);
	echo th_order_by('filter', $text['label-filter'], $order_by, $order);
	echo th_order_by('ip_address', $text['label-ip_address'], $order_by, $order);
	echo th_order_by('extension', $text['label-extension'], $order_by, $order);
	echo th_order_by('user_agent', $text['label-user_agent'], $order_by, $order);
	echo th_order_by('log_status', $text['label-log_status'], $order_by, $order);
	if (permission_exists('event_guard_log_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($event_guard_logs) && @sizeof($event_guard_logs) != 0) {
		$x = 0;
		foreach ($event_guard_logs as $row) {
			if (permission_exists('event_guard_log_edit')) {
				$list_row_url = "event_guard_log_edit.php?id=".urlencode($row['event_guard_log_uuid']);
			}
			echo "<tr class='list-row'>\n";
			if (permission_exists('event_guard_log_add') || permission_exists('event_guard_log_edit') || permission_exists('event_guard_log_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='event_guard_logs[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='event_guard_logs[$x][event_guard_log_uuid]' value='".escape($row['event_guard_log_uuid'])."' />\n";
				echo "	</td>\n";
			}
			echo "	<td>\n";
			if (permission_exists('event_guard_log_edit')) {
				echo "	<a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['hostname'])."</a>\n";
			}
			else {
				echo "	".escape($row['hostname']);
			}
			echo "	</td>\n";
			echo "	<td><a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['log_date'])."</a></td>\n";
			echo "	<td><a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['filter'])."</a></td>\n";
			echo "	<td><a href=\"https://search.arin.net/rdap/?query=".escape($row['ip_address'])."\" target=\"_blank\">".escape($row['ip_address'])."</a></td>\n";
			echo "	<td>".escape($row['extension'])."</td>\n";
			echo "	<td>".escape($row['user_agent'])."</td>\n";
			echo "	<td>".escape($row['log_status'])."</td>\n";
			if (permission_exists('event_guard_log_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>\n";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($event_guard_logs);
	}

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>
