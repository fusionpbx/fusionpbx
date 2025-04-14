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
	Portions created by the Initial Developer are Copyright (C) 2018-2024
	the Initial Developer. All Rights Reserved.
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('fifo_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//add the settings object
	$settings = new settings(["domain_uuid" => $_SESSION['domain_uuid'], "user_uuid" => $_SESSION['user_uuid']]);

//set from session variables
	$list_row_edit_button = $settings->get('theme', 'list_row_edit_button', 'false');

//get the http post data
	if (!empty($_POST['fifo']) && is_array($_POST['fifo'])) {
		$action = $_POST['action'];
		$search = $_POST['search'];
		$fifo = $_POST['fifo'];
	}

//process the http post data by action
	if (!empty($action) && !empty($fifo) && is_array($fifo) && @sizeof($fifo) != 0) {

		//validate the token
		$token = new token;
		if (!$token->validate($_SERVER['PHP_SELF'])) {
			message::add($text['message-invalid_token'],'negative');
			header('Location: fifo.php');
			exit;
		}

		//send the array to the database class
		switch ($action) {
// 			case 'copy':
// 				if (permission_exists('fifo_add')) {
// 					$obj = new fifo;
// 					$obj->copy($fifo);
// 				}
// 				break;
			case 'toggle':
				if (permission_exists('fifo_edit')) {
					$obj = new fifo;
					$obj->toggle($fifo);
				}
				break;
			case 'delete':
				if (permission_exists('fifo_delete')) {
					$obj = new fifo;
					$obj->delete($fifo);
				}
				break;
		}

		//redirect the user
		header('Location: fifo.php'.($search != '' ? '?search='.urlencode($search) : null));
		exit;
	}

//get order and order by
	$order_by = $_GET["order_by"] ?? null;
	$order = $_GET["order"] ?? null;

//define the variables
	$search = '';
	$show = '';
	$list_row_url = '';

//add the search variable
	if (!empty($_GET["search"])) {
		$search = strtolower($_GET["search"]);
	}

//add the show variable
	if (!empty($_GET["show"])) {
		$show = $_GET["show"];
	}

//get the count
	$sql = "select count(fifo_uuid) ";
	$sql .= "from v_fifo ";
	if (permission_exists('fifo_all') && $show == 'all') {
		$sql .= "where true ";
	}
	else {
		$sql .= "where domain_uuid = :domain_uuid ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	}
	if (!empty($search)) {
		$sql .= "and ( ";
		$sql .= "	lower(fifo_name) like :search ";
		$sql .= "	or lower(fifo_extension) like :search ";
		$sql .= "	or lower(fifo_description) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	$database = new database;
	$num_rows = $database->select($sql, $parameters ?? null, 'column');
	unset($sql, $parameters);

//prepare to page the results
	$rows_per_page = $settings->get('domain', 'paging', 50);
	$param = !empty($search) ? "&search=".$search : null;
	$param .= (!empty($_GET['page']) && $show == 'all' && permission_exists('fifo_all')) ? "&show=all" : null;
	$page = !empty($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 0;
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the list
	$sql = "select ";
	$sql .= "fifo_uuid, ";
	$sql .= "fifo_name, ";
	$sql .= "fifo_extension, ";
	$sql .= "fifo_agent_status, ";
	$sql .= "fifo_agent_queue, ";
	$sql .= "fifo_music, ";
	$sql .= "u.domain_uuid, ";
	$sql .= "d.domain_name, ";
	$sql .= "fifo_order, ";
	$sql .= "cast(fifo_enabled as text), ";
	$sql .= "fifo_description ";
	$sql .= "from v_fifo as u, v_domains as d ";
	if (permission_exists('fifo_all') && $show == 'all') {
		$sql .= "where true ";
	}
	else {
		$sql .= "where u.domain_uuid = :domain_uuid ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	}
	if (!empty($search)) {
		$sql .= "and ( ";
		$sql .= "	lower(fifo_name) like :search ";
		$sql .= "	or lower(fifo_extension) like :search ";
		$sql .= "	or lower(fifo_description) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	$sql .= "and u.domain_uuid = d.domain_uuid ";
	$sql .= order_by($order_by, $order, '', '');
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$fifo = $database->select($sql, $parameters ?? null, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//additional includes
	$document['title'] = $text['title-fifos'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-fifos']."</b><div class='count'>".number_format($num_rows)."</div></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('fifo_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$settings->get('theme', 'button_icon_add'),'id'=>'btn_add','name'=>'btn_add','link'=>'fifo_edit.php']);
	}
// 	if (permission_exists('fifo_add') && $fifo) {
// 		echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$settings->get('theme', 'button_icon_copy'),'id'=>'btn_copy','name'=>'btn_copy','style'=>'display:none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
// 	}
	if (permission_exists('fifo_edit') && $fifo) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$settings->get('theme', 'button_icon_toggle'),'id'=>'btn_toggle','name'=>'btn_toggle','style'=>'display:none;','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	}
	if (permission_exists('fifo_delete') && $fifo) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$settings->get('theme', 'button_icon_delete'),'id'=>'btn_delete','name'=>'btn_delete','style'=>'display:none;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	if (permission_exists('fifo_all')) {
		if ($show == 'all') {
			echo "		<input type='hidden' name='show' value='all'>\n";
		}
		else {
			echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$settings->get('theme', 'button_icon_all'),'link'=>'?show=all&search='.$search]);
		}
	}
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown=''>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$settings->get('theme', 'button_icon_search'),'type'=>'submit','id'=>'btn_search']);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

// 	if (permission_exists('fifo_add') && $fifo) {
// 		echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('copy'); list_form_submit('form_list');"])]);
// 	}
	if (permission_exists('fifo_edit') && $fifo) {
		echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('fifo_delete') && $fifo) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['title_description-fifo']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search ?? '')."\">\n";

	echo "<div class='card'>\n";
	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('fifo_add') || permission_exists('fifo_edit') || permission_exists('fifo_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".empty($fifo ? "style='visibility: hidden;'" : null).">\n";
		echo "	</th>\n";
	}
	if ($show == 'all' && permission_exists('fifo_all')) {
		echo th_order_by('domain_name', $text['label-domain'], $order_by, $order);
	}
	echo th_order_by('fifo_name', $text['label-fifo_name'], $order_by, $order);
	echo th_order_by('fifo_extension', $text['label-fifo_extension'], $order_by, $order);
	echo th_order_by('fifo_agent_status', $text['label-fifo_agent_status'], $order_by, $order);
	echo th_order_by('fifo_agent_queue', $text['label-fifo_agent_queue'], $order_by, $order);
	echo th_order_by('fifo_order', $text['label-fifo_order'], $order_by, $order);
	echo th_order_by('fifo_enabled', $text['label-enabled'], $order_by, $order, null, "class='center'");
	echo "	<th class='hide-sm-dn'>".$text['label-fifo_description']."</th>\n";
	if (permission_exists('fifo_edit') && $list_row_edit_button == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (!empty($fifo) && is_array($fifo) && @sizeof($fifo) != 0) {
		$x = 0;
		foreach ($fifo as $row) {
			if (permission_exists('fifo_edit')) {
				$list_row_url = "fifo_edit.php?id=".urlencode($row['fifo_uuid']);
				if ($row['domain_uuid'] != $_SESSION['domain_uuid'] && permission_exists('domain_select')) {
					$list_row_url .= '&domain_uuid='.urlencode($row['domain_uuid']).'&domain_change=true';
				}
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('fifo_add') || permission_exists('fifo_edit') || permission_exists('fifo_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='fifo[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='fifo[$x][uuid]' value='".escape($row['fifo_uuid'])."' />\n";
				echo "	</td>\n";
			}
			if ($show == 'all' && permission_exists('fifo_all')) {
				echo "	<td>".escape($row['domain_name'])."</td>\n";
			}
			echo "	<td>\n";
			if (permission_exists('fifo_edit')) {
				echo "	<a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['fifo_name'])."</a>\n";
			}
			else {
				echo "	".escape($row['fifo_name']);
			}
			echo "	</td>\n";
			echo "	<td>".escape($row['fifo_extension'])."</td>\n";
			echo "	<td>".escape($row['fifo_agent_status'])."</td>\n";
			echo "	<td>".escape($row['fifo_agent_queue'])."</td>\n";
			echo "	<td>".escape($row['fifo_order'])."</td>\n";
			if (permission_exists('fifo_edit')) {
				echo "	<td class='no-link center'>\n";
				echo "		<input type='hidden' name='number_translations[$x][fifo_enabled]' value='".escape($row['fifo_enabled'])."' />\n";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.$row['fifo_enabled']],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
			}
			else {
				echo "	<td class='center'>\n";
				echo $text['label-'.$row['fifo_enabled']];
			}
			echo "	</td>\n";
			echo "	<td class='description overflow hide-sm-dn'>".escape($row['fifo_description'])."</td>\n";
			if (permission_exists('fifo_edit') && $list_row_edit_button == 'true') {
				echo "	<td class='action-button'>\n";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$settings->get('theme', 'button_icon_edit'),'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($fifo);
	}

	echo "</table>\n";
	echo "</div>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>