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
        if (permission_exists('dialplan_view') || permission_exists('inbound_route_view') || permission_exists('outbound_route_view')) {
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
	if (is_array($_POST['dialplans'])) {
		$action = $_POST['action'];
		$dialplans = $_POST['dialplans'];
		$search = $_POST['search'];
		$order_by = $_POST['order_by'];
		$order = $_POST['order'];
	}

//get the app uuid
	if (is_uuid($_REQUEST["app_uuid"])) {
		$app_uuid = $_REQUEST["app_uuid"];
	}

//process the http post data by action
	if ($action != '' && is_array($dialplans) && @sizeof($dialplans) != 0) {

		//define redirect parameters and url
			if (is_uuid($app_uuid)) { $params[] = "app_uuid=".urlencode($app_uuid); }
			if ($search) { $params[] = "search=".urlencode($search); }
			if ($order_by) { $params[] = "order_by=".urlencode($order_by); }
			if ($order) { $params[] = "order=".urlencode($order); }
			$list_page = 'dialplans.php'.($params ? '?'.implode('&', $params) : null);
			unset($params);

		//process action
			switch ($action) {
				case 'copy':
					if (permission_exists('dialplan_add')) {
						$obj = new dialplan;
						$obj->app_uuid = $app_uuid;
						$obj->list_page = $list_page;
						$obj->copy($dialplans);
					}
					break;
				case 'toggle':
					if (permission_exists('dialplan_edit')) {
						$obj = new dialplan;
						$obj->app_uuid = $app_uuid;
						$obj->list_page = $list_page;
						$obj->toggle($dialplans);
					}
					break;
				case 'delete':
					if (permission_exists('dialplan_delete')) {
						$obj = new dialplan;
						$obj->app_uuid = $app_uuid;
						$obj->list_page = $list_page;
						$obj->delete($dialplans);
					}
					break;
			}

		//redirect
			header('Location: '.$list_page);
			exit;
	}

//get order and order by and sanatize the values
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//make sure all dialplans with context of public have the inbound route app_uuid
	if ($app_uuid == 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4') {
		$sql = "update v_dialplans set ";
		$sql .= "app_uuid = 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4' ";
		$sql .= "where dialplan_context = 'public' ";
		$sql .= "and app_uuid is null; ";
		$database = new database;
		$database->execute($sql);
		unset($sql);
	}

//add the search term
	if (isset($_GET["search"])) {
		$search = strtolower($_GET["search"]);
	}

//get the number of rows in the dialplan
	$sql = "select count(*) from v_dialplans ";
	if ($_GET['show'] == "all" && permission_exists('dialplan_all')) {
		$sql .= "where true ";
	}
	else {
		$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	if (!is_uuid($app_uuid)) {
		//hide inbound routes
			$sql .= "and app_uuid <> 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4' ";
			$sql .= "and dialplan_context <> 'public' ";
		//hide outbound routes
			//$sql .= "and app_uuid <> '8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3' ";
	}
	else {
		if ($app_uuid == 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4') {
			$sql .= "and (app_uuid = :app_uuid or dialplan_context = 'public') ";
		}
		else {
			$sql .= "and app_uuid = :app_uuid ";
		}
		$parameters['app_uuid'] = $app_uuid;
	}
	if (isset($search)) {
		$sql .= "and (";
		$sql .= " 	lower(dialplan_context) like :search ";
		$sql .= " 	or lower(dialplan_name) like :search ";
		$sql .= " 	or lower(dialplan_number) like :search ";
		$sql .= " 	or lower(dialplan_continue) like :search ";
		$sql .= " 	or lower(dialplan_enabled) like :search ";
		$sql .= " 	or lower(dialplan_description) like :search ";
		if (is_numeric($search)) {
			$sql .= " 	or dialplan_order = :search_numeric ";
			$parameters['search_numeric'] = $search;
		}
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//prepare the paging
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$params[] = "app_uuid=".$app_uuid;
	if ($search) { $params[] = "search=".$search; }
	if ($order_by) { $params[] = "order_by=".$order_by; }
	if ($order) { $params[] = "order=".$order; }
	if ($_GET['show'] == "all" && permission_exists('dialplan_all')) {
		$params[] .= "show=all";
	}
	$param = $params ? implode('&', $params) : null;
	unset($params);
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the list of dialplans
	$sql = "select * from v_dialplans ";
	if ($_GET['show'] == "all" && permission_exists('dialplan_all')) {
		$sql .= "where true ";
	}
	else {
		$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	if (!is_uuid($app_uuid)) {
		//hide inbound routes
			$sql .= "and app_uuid <> 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4' ";
			$sql .= "and dialplan_context <> 'public' ";
		//hide outbound routes
			//$sql .= "and app_uuid <> '8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3' ";
	}
	else {
		if ($app_uuid == 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4') {
			$sql .= "and (app_uuid = :app_uuid or dialplan_context = 'public') ";
		}
		else {
			$sql .= "and app_uuid = :app_uuid ";
		}
		$parameters['app_uuid'] = $app_uuid;
	}
	if (isset($search)) {
		$sql .= "and (";
		$sql .= " 	lower(dialplan_context) like :search ";
		$sql .= " 	or lower(dialplan_name) like :search ";
		$sql .= " 	or lower(dialplan_number) like :search ";
		$sql .= " 	or lower(dialplan_continue) like :search ";
		$sql .= " 	or lower(dialplan_enabled) like :search ";
		$sql .= " 	or lower(dialplan_description) like :search ";
		if (is_numeric($search)) {
			$sql .= " 	or dialplan_order = :search_numeric ";
			$parameters['search_numeric'] = $search;
		}
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	if ($order_by != '') {
		if ($order_by == 'dialplan_name' || $order_by == 'dialplan_description') {
			$sql .= 'order by lower('.$order_by.') '.$order.' ';
		}
		else {
			$sql .= order_by($order_by, $order);
		}
	}
	else {
		$sql .= "order by dialplan_order asc, lower(dialplan_name) asc ";
	}
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$dialplans = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	switch ($app_uuid) {
		case "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4": $document['title'] = $text['title-inbound_routes']; break;
		case "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3": $document['title'] = $text['title-outbound_routes']; break;
		case "16589224-c876-aeb3-f59f-523a1c0801f7": $document['title'] = $text['title-queues']; break;
		case "4b821450-926b-175a-af93-a03c441818b1": $document['title'] = $text['title-time_conditions']; break;
		default: $document['title'] = $text['title-dialplan_manager'];
	}
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>";
	switch ($app_uuid) {
		case "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4": echo $text['header-inbound_routes']; break;
		case "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3": echo $text['header-outbound_routes']; break;
		case "16589224-c876-aeb3-f59f-523a1c0801f7": echo $text['header-queues']; break;
		case "4b821450-926b-175a-af93-a03c441818b1": echo $text['header-time_conditions']; break;
		default: echo $text['header-dialplan_manager'];
	}
	echo " (".$num_rows.")</b>";
	echo 	"</div>\n";
	echo "	<div class='actions'>\n";
	if ($app_uuid == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4" && permission_exists('inbound_route_add')) { $button_add_url = PROJECT_PATH."/app/dialplan_inbound/dialplan_inbound_add.php"; }
	else if ($app_uuid == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3" && permission_exists('outbound_route_add')) { $button_add_url = PROJECT_PATH."/app/dialplan_outbound/dialplan_outbound_add.php"; }
	else if ($app_uuid == "16589224-c876-aeb3-f59f-523a1c0801f7" && permission_exists('fifo_add')) { $button_add_url = PROJECT_PATH."/app/fifo/fifo_add.php"; }
	else if ($app_uuid == "4b821450-926b-175a-af93-a03c441818b1" && permission_exists('time_condition_add')) { $button_add_url = PROJECT_PATH."/app/time_conditions/time_condition_edit.php"; }
	else if (permission_exists('dialplan_add')) { $button_add_url = PROJECT_PATH."/app/dialplans/dialplan_add.php"; }
	if ($button_add_url) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','link'=>$button_add_url]);
	}
	if ($dialplans) {
		if (
			($app_uuid == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4" && permission_exists('inbound_route_copy')) ||
			($app_uuid == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3" && permission_exists('outbound_route_copy')) ||
			($app_uuid == "16589224-c876-aeb3-f59f-523a1c0801f7" && permission_exists('fifo_add')) ||
			($app_uuid == "4b821450-926b-175a-af93-a03c441818b1" && permission_exists('time_condition_add')) ||
			permission_exists('dialplan_add')
			) {
			echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'id'=>'btn_copy','name'=>'btn_copy','style'=>'display: none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
		}
		if (
			($app_uuid == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4" && permission_exists('inbound_route_edit')) ||
			($app_uuid == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3" && permission_exists('outbound_route_edit')) ||
			($app_uuid == "16589224-c876-aeb3-f59f-523a1c0801f7" && permission_exists('fifo_edit')) ||
			($app_uuid == "4b821450-926b-175a-af93-a03c441818b1" && permission_exists('time_condition_edit')) ||
			permission_exists('dialplan_edit')
			) {
			echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$_SESSION['theme']['button_icon_toggle'],'id'=>'btn_toggle','name'=>'btn_toggle','style'=>'display: none;','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
		}
		if (
			($app_uuid == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4" && permission_exists('inbound_route_delete')) ||
			($app_uuid == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3" && permission_exists('outbound_route_delete')) ||
			($app_uuid == "16589224-c876-aeb3-f59f-523a1c0801f7" && permission_exists('fifo_delete')) ||
			($app_uuid == "4b821450-926b-175a-af93-a03c441818b1" && permission_exists('time_condition_delete')) ||
			permission_exists('dialplan_delete')
			) {
			echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
		}
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	if (permission_exists('dialplan_all')) {
		if ($_GET['show'] == 'all' && permission_exists('dialplan_all')) {
			echo "		<input type='hidden' name='show' value='all'>";
		}
		else {
			if (is_uuid($app_uuid)) { $params[] = "app_uuid=".urlencode($app_uuid); }
			if ($search) { $params[] = "search=".urlencode($search); }
			if ($order_by) { $params[] = "order_by=".urlencode($order_by); }
			if ($order) { $params[] = "order=".urlencode($order); }
			echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$_SESSION['theme']['button_icon_all'],'link'=>'?show=all'.($params ? '&'.implode('&', $params) : null)]);
			unset($params);
		}
	}
	if (is_uuid($app_uuid)) {
		echo 	"<input type='hidden' name='app_uuid' value='".escape($app_uuid)."'>";
	}
	if ($order_by) {
		echo 	"<input type='hidden' name='order_by' value='".escape($order_by)."'>";
	}
	if ($order) {
		echo 	"<input type='hidden' name='order' value='".escape($order)."'>";
	}
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown=''>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search']);
	$params[] = "app_uuid=".urlencode($app_uuid);
	if ($order_by) { $params[] = "order_by=".urlencode($order_by); }
	if ($order) { $params[] = "order=".urlencode($order); }
	if ($_GET['show'] && permission_exists('dialplan_all')) { $params[] = "show=".urlencode($_GET['show']); }
	//echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'dialplans.php'.($params ? '?'.implode('&', $params) : null),'style'=>($search == '' ? 'display: none;' : null)]);
	unset($params);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if ($dialplans) {
		if (
			($app_uuid == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4" && permission_exists('inbound_route_copy')) ||
			($app_uuid == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3" && permission_exists('outbound_route_copy')) ||
			($app_uuid == "16589224-c876-aeb3-f59f-523a1c0801f7" && permission_exists('fifo_add')) ||
			($app_uuid == "4b821450-926b-175a-af93-a03c441818b1" && permission_exists('time_condition_add')) ||
			permission_exists('dialplan_add')
			) {
			echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('copy'); list_form_submit('form_list');"])]);
		}
		if (
			($app_uuid == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4" && permission_exists('inbound_route_edit')) ||
			($app_uuid == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3" && permission_exists('outbound_route_edit')) ||
			($app_uuid == "16589224-c876-aeb3-f59f-523a1c0801f7" && permission_exists('fifo_edit')) ||
			($app_uuid == "4b821450-926b-175a-af93-a03c441818b1" && permission_exists('time_condition_edit')) ||
			permission_exists('dialplan_edit')
			) {
			echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
		}
		if (
			($app_uuid == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4" && permission_exists('inbound_route_delete')) ||
			($app_uuid == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3" && permission_exists('outbound_route_delete')) ||
			($app_uuid == "16589224-c876-aeb3-f59f-523a1c0801f7" && permission_exists('fifo_delete')) ||
			($app_uuid == "4b821450-926b-175a-af93-a03c441818b1" && permission_exists('time_condition_delete')) ||
			permission_exists('dialplan_delete')
			) {
			echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
		}
	}

	switch ($app_uuid) {
		case "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4": echo $text['description-inbound_routes']; break;
		case "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3": echo $text['description-outbound_routes']; break;
		case "16589224-c876-aeb3-f59f-523a1c0801f7": echo $text['description-queues']; break;
		case "4b821450-926b-175a-af93-a03c441818b1": echo $text['description-time_conditions']; break;
		default: echo $text['description-dialplan_manager'.(if_group("superadmin") ? '-superadmin' : null)];
	}
	echo "\n<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='app_uuid' name='app_uuid' value='".escape($app_uuid)."'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";
	echo "<input type='hidden' name='order_by' value=\"".escape($order_by)."\">\n";
	echo "<input type='hidden' name='order' value=\"".escape($order)."\">\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (
		($app_uuid == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4" && (permission_exists('inbound_route_copy') || permission_exists('inbound_route_edit') || permission_exists('inbound_route_delete'))) ||
		($app_uuid == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3" && (permission_exists('outbound_route_copy') || permission_exists('outbound_route_edit') || permission_exists('outbound_route_delete'))) ||
		($app_uuid == "16589224-c876-aeb3-f59f-523a1c0801f7" && (permission_exists('fifo_add') || permission_exists('fifo_edit') || permission_exists('fifo_delete'))) ||
		($app_uuid == "4b821450-926b-175a-af93-a03c441818b1" && (permission_exists('time_condition_add') || permission_exists('time_condition_edit') || permission_exists('time_condition_delete'))) ||
		permission_exists('dialplan_add') || permission_exists('dialplan_edit') || permission_exists('dialplan_delete')
		) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".($dialplans ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	if ($_GET['show'] == "all" && permission_exists('dialplan_all')) {
		echo "<th>".$text['label-domain']."</th>\n";
	}
	if ($search) { $params[] = "search=".urlencode($search); }
	if ($_GET['show'] == 'all' && permission_exists('dialplan_all')) { $params[] = "show=all"; }
	echo th_order_by('dialplan_name', $text['label-name'], $order_by, $order, $app_uuid, null, ($params ? implode('&', $params) : null));
	echo th_order_by('dialplan_number', $text['label-number'], $order_by, $order, $app_uuid, null, ($params ? implode('&', $params) : null));
	if (permission_exists('dialplan_context')) {
		echo th_order_by('dialplan_context', $text['label-context'], $order_by, $order, $app_uuid, null, ($params ? implode('&', $params) : null));
	}
	echo th_order_by('dialplan_order', $text['label-order'], $order_by, $order, $app_uuid, "class='center shrink'", ($params ? implode('&', $params) : null));
	echo th_order_by('dialplan_enabled', $text['label-enabled'], $order_by, $order, $app_uuid, "class='center'", ($params ? implode('&', $params) : null));
	echo th_order_by('dialplan_description', $text['label-description'], $order_by, $order, $app_uuid, "class='hide-sm-dn' style='min-width: 100px;'", ($params ? implode('&', $params) : null));
	unset($params);
	if ((
		($app_uuid == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4" && permission_exists('inbound_route_edit')) ||
		($app_uuid == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3" && permission_exists('outbound_route_edit')) ||
		($app_uuid == "16589224-c876-aeb3-f59f-523a1c0801f7" && permission_exists('fifo_edit')) ||
		($app_uuid == "4b821450-926b-175a-af93-a03c441818b1" && permission_exists('time_condition_edit')) ||
		permission_exists('dialplan_edit')) && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true'
		) {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($dialplans) && @sizeof($dialplans) != 0) {
		$x = 0;
		foreach ($dialplans as $row) {

			if ($row['app_uuid'] == "4b821450-926b-175a-af93-a03c441818b1") {
				if (permission_exists('time_condition_edit') || permission_exists('dialplan_edit')) {
					$list_row_url = PROJECT_PATH."/app/time_conditions/time_condition_edit.php?id=".urlencode($row['dialplan_uuid']).(is_uuid($app_uuid) ? "&app_uuid=".urlencode($app_uuid) : null);
				}
			}
			else if (
				($row['app_uuid'] == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4" && permission_exists('inbound_route_edit')) ||
				($row['app_uuid'] == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3" && permission_exists('outbound_route_edit')) ||
				($row['app_uuid'] == "16589224-c876-aeb3-f59f-523a1c0801f7" && permission_exists('fifo_edit')) ||
				permission_exists('dialplan_edit')
				) {
				$list_row_url = "dialplan_edit.php?id=".urlencode($row['dialplan_uuid']).(is_uuid($app_uuid) ? "&app_uuid=".urlencode($app_uuid) : null);
			}
			else {
				unset($list_row_url);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (
				(!is_uuid($app_uuid) && (permission_exists('dialplan_add') || permission_exists('dialplan_edit') || permission_exists('dialplan_delete'))) ||
				($app_uuid == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4" && (permission_exists('inbound_route_copy') || permission_exists('inbound_route_edit') || permission_exists('inbound_route_delete'))) ||
				($app_uuid == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3" && (permission_exists('outbound_route_copy') || permission_exists('outbound_route_edit') || permission_exists('outbound_route_delete'))) ||
				($app_uuid == "16589224-c876-aeb3-f59f-523a1c0801f7" && (permission_exists('fifo_add') || permission_exists('fifo_edit') || permission_exists('fifo_delete'))) ||
				($app_uuid == "4b821450-926b-175a-af93-a03c441818b1" && (permission_exists('time_condition_add') || permission_exists('time_condition_edit') || permission_exists('time_condition_delete')))
				) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='dialplans[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='dialplans[$x][uuid]' value='".escape($row['dialplan_uuid'])."' />\n";
				echo "	</td>\n";
			}
			if ($_GET['show'] == "all" && permission_exists('dialplan_all')) {
				if (strlen($_SESSION['domains'][$row['domain_uuid']]['domain_name']) > 0) {
					$domain = $_SESSION['domains'][$row['domain_uuid']]['domain_name'];
				}
				else {
					$domain = $text['label-global'];
				}
				echo "	<td>".escape($domain)."</td>\n";
			}
			echo "	<td>";
			if ($list_row_url) {
				echo "<a href='".$list_row_url."'>".escape($row['dialplan_name'])."</a>";
			}
			else {
				echo escape($row['dialplan_name']);
			}
			echo "	</td>\n";
			echo "	<td>".((strlen($row['dialplan_number']) > 0) ? escape(format_phone($row['dialplan_number'])) : "&nbsp;")."</td>\n";
			if (permission_exists('dialplan_context')) {
				echo "	<td>".escape($row['dialplan_context'])."</td>\n";
			}
			echo "	<td class='center'>".escape($row['dialplan_order'])."</td>\n";
			if (
				(!is_uuid($app_uuid) && permission_exists('dialplan_edit')) ||
				($row['app_uuid'] == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4" && permission_exists('inbound_route_edit')) ||
				($row['app_uuid'] == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3" && permission_exists('outbound_route_edit')) ||
				($row['app_uuid'] == "16589224-c876-aeb3-f59f-523a1c0801f7" && permission_exists('fifo_edit')) ||
				($row['app_uuid'] == "4b821450-926b-175a-af93-a03c441818b1" && permission_exists('time_condition_edit'))
				) {
				echo "	<td class='no-link center'>";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.$row['dialplan_enabled']],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
			}
			else {
				echo "	<td class='center'>";
				echo $text['label-'.$row['dialplan_enabled']];
			}
			echo "	</td>\n";
			echo "	<td class='description overflow hide-sm-dn'>".escape($row['dialplan_description'])."&nbsp;</td>\n";
			if ($_SESSION['theme']['list_row_edit_button']['boolean'] == 'true' && (
				(!is_uuid($app_uuid) && permission_exists('dialplan_edit')) ||
				($row['app_uuid'] == "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4" && permission_exists('inbound_route_edit')) ||
				($row['app_uuid'] == "8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3" && permission_exists('outbound_route_edit')) ||
				($row['app_uuid'] == "16589224-c876-aeb3-f59f-523a1c0801f7" && permission_exists('fifo_edit')) ||
				($row['app_uuid'] == "4b821450-926b-175a-af93-a03c441818b1" && permission_exists('time_condition_edit'))
				)) {
				echo "	<td class='action-button'>";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($dialplans);
	}

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>
