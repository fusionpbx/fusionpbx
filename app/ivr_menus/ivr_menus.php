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
	Portions created by the Initial Developer are Copyright (C) 2008-2026
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J. Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (!permission_exists('ivr_menu_view')) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

// Set variables from http GET parameters
	$page = is_numeric($_GET['page'] ?? '') ? $_GET['page'] : 0;
	$order_by = preg_replace('#[^a-zA-Z0-9_\-]#', '', ($_GET['order_by'] ?? 'ivr_menu_name'));
	$order = ($_GET['order'] ?? '') === 'desc' ? 'desc' : 'asc';
	$sort = $order_by == 'ivr_menu_extension' ? 'natural' : null;
	$search = $_GET['search'] ?? '';
	$show = $_GET['show'] ?? '';

// Build the query string
	$param = [];
	if (!empty($page)) {
		$param['page'] = $page;
	}
	if (!empty($_GET['order_by'])) {
		$param['order_by'] = $order_by;
	}
	if (!empty($_GET['order'])) {
		$param['order'] = $order;
	}
	if (!empty($search)) {
		$param['search'] = $search;
	}
	if (!empty($show) && $show == 'all' && permission_exists('ivr_menu_all')) {
		$param['show'] = $show;
	}
	$query_string = http_build_query($param);

//define defaults
	$action = '';
	$ivr_menus = '';

//get posted data
	if (!empty($_POST['ivr_menus'])) {
		$action = $_POST['action'];
		$ivr_menus = $_POST['ivr_menus'];
	}

//get total ivr menu count from the database, check limit, if defined
	if (!empty($settings->get('limit', 'ivr_menus'))) {
		$sql = "select count(*) as num_rows from v_ivr_menus where domain_uuid = :domain_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
		$total_ivr_menus = $database->select($sql, $parameters, 'column');
		unset($sql, $parameters);

		if ($action == 'copy' && $total_ivr_menus >= $settings->get('limit', 'ivr_menus')) {
			message::add($text['message-maximum_ivr_menus'].' '.$settings->get('limit', 'ivr_menus'), 'negative');
			header('Location: ivr_menus.php'.($query_string ? '?'.$query_string : ''));
			exit;
		}
	}

//process the http post data by action
	if (!empty($action) && is_array($ivr_menus) && @sizeof($ivr_menus) != 0) {
		switch ($action) {
			case 'copy':
				if (permission_exists('ivr_menu_add')) {
					$obj = new ivr_menu;
					$obj->copy($ivr_menus);
				}
				break;
			case 'toggle':
				if (permission_exists('ivr_menu_edit')) {
					$obj = new ivr_menu;
					$obj->toggle($ivr_menus);
				}
				break;
			case 'delete':
				if (permission_exists('ivr_menu_delete')) {
					$obj = new ivr_menu;
					$obj->delete($ivr_menus);
				}
				break;
		}

		header('Location: ivr_menus.php'.($query_string ? '?'.$query_string : ''));
		exit;
	}

//set from session variables
	$list_row_edit_button = $settings->get('theme', 'list_row_edit_button', false);

//prepare to page the results
	$sql = "select count(*) from v_ivr_menus ";
	if ($show == "all" && permission_exists('ivr_menu_all')) {
		$sql .= "where true ";
	}
	else {
		$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	}
	if (!empty($search)) {
		$sql .= "and (";
		$sql .= "	lower(ivr_menu_name) like :search ";
		$sql .= "	or lower(ivr_menu_extension) like :search ";
		$sql .= "	or lower(ivr_menu_description) like :search ";
		$sql .= ")";
		$parameters['search'] = '%'.lower_case($search).'%';
	}
	$num_rows = $database->select($sql, $parameters ?? [], 'column');

//prepare to page the results
	$rows_per_page = $settings->get('domain', 'paging', 50);
	list($paging_controls, $rows_per_page) = paging($num_rows, $query_string, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $query_string, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the list
	$sql = "select ";
	$sql .= "ivr_menu_uuid, ";
	$sql .= "domain_uuid, ";
	$sql .= "ivr_menu_name, ";
	$sql .= "ivr_menu_extension, ";
	$sql .= "cast(ivr_menu_enabled as text), ";
	$sql .= "ivr_menu_description ";
	$sql .= "from v_ivr_menus ";
	if ($show == "all" && permission_exists('ivr_menu_all')) {
		$sql .= "where true ";
	}
	else {
		$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	}
	if (!empty($search)) {
		$sql .= "and (";
		$sql .= "	lower(ivr_menu_name) like :search ";
		$sql .= "	or lower(ivr_menu_extension) like :search ";
		$sql .= "	or lower(ivr_menu_description) like :search ";
		$sql .= ")";
		$parameters['search'] = '%'.lower_case($search).'%';
	}
	$sql .= order_by($order_by, $order, 'ivr_menu_name', 'asc', $sort);
	$sql .= limit_offset($rows_per_page, $offset);
	$ivr_menus = $database->select($sql, $parameters ?? [], 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//additional includes
	$document['title'] = $text['title-ivr_menus'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-ivr_menus']."</b><div class='count'>".number_format($num_rows)."</div></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('ivr_menu_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$settings->get('theme', 'button_icon_add'),'id'=>'btn_add','link'=>'ivr_menu_edit.php'.($query_string ? '?'.$query_string : '')]);
	}
	if (permission_exists('ivr_menu_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$settings->get('theme', 'button_icon_copy'),'id'=>'btn_copy','name'=>'btn_copy','style'=>'display: none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
	}
	if (permission_exists('ivr_menu_edit') && $ivr_menus) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$settings->get('theme', 'button_icon_toggle'),'id'=>'btn_toggle','name'=>'btn_toggle','style'=>'display: none;','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	}
	if (permission_exists('ivr_menu_delete') && $ivr_menus) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$settings->get('theme', 'button_icon_delete'),'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	foreach ($param as $key => $value) {
		if ($key !== 'search' && $key !== 'page') {
			echo "		<input type='hidden' name='".escape($key)."' value='".escape($value)."'>\n";
		}
	}
	if ($show !== 'all' && permission_exists('ivr_menu_all')) {
		echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$settings->get('theme', 'button_icon_all'),'link'=>'?show=all']);
	}
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown=''>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$settings->get('theme', 'button_icon_search'),'type'=>'submit','id'=>'btn_search']);
	//echo button::create(['label'=>$text['button-reset'],'icon'=>$settings->get('theme', 'button_icon_reset'),'type'=>'button','id'=>'btn_reset','link'=>'ivr_menus.php','style'=>($search == '' ? 'display: none;' : null)]);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('ivr_menu_add') && $ivr_menus) {
		echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('copy'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('ivr_menu_edit') && $ivr_menus) {
		echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('ivr_menu_delete') && $ivr_menus) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['description-ivr_menu']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";

	echo "<div class='card'>\n";
	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('ivr_menu_add') || permission_exists('ivr_menu_edit') || permission_exists('ivr_menu_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".(!empty($ivr_menus) ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	if ($show == "all" && permission_exists('ivr_menu_all')) {
		echo th_order_by('domain_name', $text['label-domain'], $order_by, $order, null, "class='shrink'", $query_string);
	}
	echo th_order_by('ivr_menu_name', $text['label-name'], $order_by, $order, null, null, $query_string);
	echo th_order_by('ivr_menu_extension', $text['label-extension'], $order_by, $order, null, null, $query_string);
	echo th_order_by('ivr_menu_enabled', $text['label-enabled'], $order_by, $order, null, "class='center'", $query_string);
	echo th_order_by('ivr_menu_description', $text['label-description'], $order_by, $order, null, "class='hide-sm-dn'", $query_string);
	if (permission_exists('ivr_menu_edit') && $list_row_edit_button) {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (!empty($ivr_menus)) {
		$x = 0;
		foreach($ivr_menus as $row) {
			$list_row_url = '';
			if (permission_exists('ivr_menu_edit')) {
				$list_row_url = "ivr_menu_edit.php?id=".urlencode($row['ivr_menu_uuid']).($query_string ? '&'.$query_string : '');
				if ($row['domain_uuid'] != $_SESSION['domain_uuid'] && permission_exists('domain_select')) {
					$list_row_url .= '&domain_uuid='.urlencode($row['domain_uuid']).'&domain_change=true';
				}
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('ivr_menu_add') || permission_exists('ivr_menu_edit') || permission_exists('ivr_menu_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='ivr_menus[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='ivr_menus[$x][uuid]' value='".escape($row['ivr_menu_uuid'])."' />\n";
				echo "	</td>\n";
			}
			if ($show == "all" && permission_exists('ivr_menu_all')) {
				if (!empty($_SESSION['domains'][$row['domain_uuid']]['domain_name'])) {
					$domain = $_SESSION['domains'][$row['domain_uuid']]['domain_name'];
				}
				else {
					$domain = $text['label-global'];
				}
				echo "	<td>".escape($domain)."</td>\n";
			}
			echo "	<td>";
			if (permission_exists('ivr_menu_edit')) {
				echo "<a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['ivr_menu_name'])."</a>";
			}
			else {
				echo escape($row['ivr_menu_name']);
			}
			echo "	</td>\n";
			echo "	<td>".escape($row['ivr_menu_extension'])."&nbsp;</td>\n";
			if (permission_exists('ivr_menu_edit')) {
				echo "	<td class='no-link center'>";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.$row['ivr_menu_enabled']],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
			}
			else {
				echo "	<td class='center'>";
				echo $text['label-'.$row['ivr_menu_enabled']];
			}
			echo "	</td>\n";
			echo "	<td class='description overflow hide-sm-dn'>".escape($row['ivr_menu_description'])."&nbsp;</td>\n";
			if (permission_exists('ivr_menu_edit') && $list_row_edit_button) {
				echo "	<td class='action-button'>";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$settings->get('theme', 'button_icon_edit'),'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
	}
	unset($ivr_menus);

	echo "</table>\n";
	echo "</div>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>

