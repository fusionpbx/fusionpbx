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

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('time_condition_view')) {
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
	if (is_array($_POST['time_conditions'])) {
		$action = $_POST['action'];
		$search = $_POST['search'];
		$time_conditions = $_POST['time_conditions'];
	}

//process the http post data by action
	if ($action != '' && is_array($time_conditions) && @sizeof($time_conditions) != 0) {
		switch ($action) {
			case 'copy':
				if (permission_exists('time_condition_add')) {
					$obj = new time_conditions;
					$obj->copy($time_conditions);
				}
				break;
			case 'toggle':
				if (permission_exists('time_condition_edit')) {
					$obj = new time_conditions;
					$obj->toggle($time_conditions);
				}
				break;
			case 'delete':
				if (permission_exists('time_condition_delete')) {
					$obj = new time_conditions;
					$obj->delete($time_conditions);
				}
				break;
		}

		header('Location: time_conditions.php'.($search != '' ? '?search='.urlencode($search) : null));
		exit;
	}

//get order and order by
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//add the search string
	$search = strtolower($_GET["search"]);
	if (strlen($search) > 0) {
		$sql_search = "and (";
		$sql_search .= " 	lower(dialplan_context) like :search ";
		$sql_search .= " 	or lower(dialplan_name) like :search ";
		$sql_search .= " 	or lower(dialplan_number) like :search ";
		$sql_search .= " 	or lower(dialplan_continue) like :search ";
		if (is_numeric($search)) {
			$sql_search .= " 	or dialplan_order = :search ";
		}
		$sql_search .= " 	or lower(dialplan_enabled) like :search ";
		$sql_search .= " 	or lower(dialplan_description) like :search ";
		$sql_search .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}

//get the number of rows in the dialplan
	$sql = "select count(dialplan_uuid) from v_dialplans ";
	$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
	$sql .= "and app_uuid = '4b821450-926b-175a-af93-a03c441818b1' ";
	$sql .= $sql_search;
	$parameters['domain_uuid'] = $domain_uuid;
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//prepare to page data
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = $search ? "&search=".$search : null;
	$page = is_numeric($_GET['page']) ? $_GET['page'] : 0;
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the data
	$sql = str_replace('count(dialplan_uuid)', '*', $sql);
	$sql .= $order_by != '' ? order_by($order_by, $order) : " order by dialplan_order asc, dialplan_name asc ";
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$dialplans = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//includes
	$document['title'] = $text['title-time_conditions'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['header-time_conditions']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('time_condition_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','link'=>'time_condition_edit.php']);
	}
	if (permission_exists('time_condition_add') && $dialplans) {
		echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'name'=>'btn_copy','onclick'=>"modal_open('modal-copy','btn_copy');"]);
	}
	if (permission_exists('time_condition_edit') && $dialplans) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$_SESSION['theme']['button_icon_toggle'],'name'=>'btn_toggle','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	}
	if (permission_exists('time_condition_delete') && $dialplans) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown='list_search_reset();'>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search','style'=>($search != '' ? 'display: none;' : null)]);
	echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'time_conditions.php','style'=>($search == '' ? 'display: none;' : null)]);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('time_condition_add') && $dialplans) {
		echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('copy'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('time_condition_edit') && $dialplans) {
		echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('time_condition_delete') && $dialplans) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['description-time_conditions']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('time_condition_edit') || permission_exists('time_condition_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle();' ".($dialplans ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	echo th_order_by('dialplan_name', $text['label-name'], $order_by, $order, null, null, ($search != '' ? "search=".$search : null));
	echo th_order_by('dialplan_number', $text['label-number'], $order_by, $order, null, null, ($search != '' ? "search=".$search : null));
	if (permission_exists('time_condition_context')) {
		echo th_order_by('dialplan_context', $text['label-context'], $order_by, $order, null, null, ($search != '' ? "search=".$search : null));
	}
	echo th_order_by('dialplan_order', $text['label-order'], $order_by, $order, null, "class='center'", ($search != '' ? "search=".$search : null));
	echo th_order_by('dialplan_enabled', $text['label-enabled'], $order_by, $order, null, "class='center'", ($search != '' ? "search=".$search : null));
	echo th_order_by('dialplan_description', $text['label-description'], $order_by, $order, null, "class='hide-sm-dn'", ($search != '' ? "search=".$search : null));
	if (permission_exists('time_condition_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($dialplans) && @sizeof($dialplans) != 0) {
		$x = 0;
		foreach ($dialplans as $row) {
			if (permission_exists('time_condition_edit')) {
				$list_row_url = "time_condition_edit.php?id=".urlencode($row['dialplan_uuid']);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('time_condition_add') || permission_exists('time_condition_edit') || permission_exists('time_condition_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='time_conditions[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='time_conditions[$x][uuid]' value='".escape($row['dialplan_uuid'])."' />\n";
				echo "	</td>\n";
			}
			echo "	<td>";
			if (permission_exists('time_condition_edit')) {
				echo "	<a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['dialplan_name'])."</a>\n";
			}
			else {
				echo "	".escape($row['dialplan_name']);
			}
			echo "	</td>\n";
			echo "	<td>".($row['dialplan_number'] != '' ? $row['dialplan_number'] : "&nbsp;")."</td>\n";
			if (permission_exists('time_condition_context')) {
				echo "	<td>".escape($row['dialplan_context'])."</td>\n";
			}
			echo "	<td class='center'>".escape($row['dialplan_order'])."</td>\n";
			if (permission_exists('time_condition_edit')) {
				echo "	<td class='no-link center'>\n";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.$row['dialplan_enabled']],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
			}
			else {
				echo "	<td class='center'>\n";
				echo $text['label-'.$row['dialplan_enabled']];
			}
			echo "	</td>\n";
			echo "	<td class='description overflow hide-sm-dn'>".$row['dialplan_description']."&nbsp;</td>\n";
			if (permission_exists('time_condition_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>\n";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
	}
	unset($dialplans);

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>