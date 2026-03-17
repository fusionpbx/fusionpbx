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
	Portions created by the Initial Developer are Copyright (C) 2008-2025
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (!permission_exists('user_view')) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set variables
	$order_by = $_REQUEST["order_by"] ?? '';
	$order = $_REQUEST["order"] ?? '';
	$page = !empty($_REQUEST['page']) && is_numeric($_REQUEST['page']) ? $_REQUEST['page'] : 0;
	$search = $_REQUEST["search"] ?? '';
	$show = $_REQUEST["show"] ?? '';
	$context = $_REQUEST["context"] ?? '';

//get the http post data
	if (!empty($_POST['users'])) {
		$action = $_POST['action'] ?? '';
		$users = $_POST['users'] ?? '';
	}

//get total user count from the database, check limit, if defined
	if (permission_exists('user_add') && !empty($action) && $action == 'copy' && !empty($settings->get('limit', 'users'))) {
		$sql = "select count(*) ";
		$sql .= "from v_users ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$num_rows = $database->select($sql, $parameters, 'column');
		unset($sql, $parameters);

		if ($num_rows >= $settings->get('limit', 'users')) {
			message::add($text['message-maximum_users'].' '.$settings->get('limit', 'users'), 'negative');
			header('Location: users.php?'.(!empty($order_by) ? '&order_by='.$order_by.'&order='.$order : null).(isset($page) && is_numeric($page) ? '&page='.$page : null).(!empty($search) ? '&search='.urlencode($search) : null));
			exit;
		}
	}

//process the http post data by action
	if (!empty($action) && is_array($users) && @sizeof($users) != 0) {
		switch ($action) {
			case 'copy':
				if (permission_exists('user_add')) {
					$obj = new users;
					$obj->copy($users);
				}
				break;
			case 'toggle':
				if (permission_exists('user_edit')) {
					$obj = new users;
					$obj->toggle($users);
				}
				break;
			case 'delete':
				if (permission_exists('user_delete')) {
					$obj = new users;
					$obj->delete($users);
				}
				break;
		}

		header('Location: users.php?'.(!empty($order_by) ? '&order_by='.$order_by.'&order='.$order : null).(isset($page) && is_numeric($page) ? '&page='.$page : null).(!empty($search) ? '&search='.urlencode($search) : null));
		exit;
	}

//set from session variables
	$list_row_edit_button = $settings->get('theme', 'list_row_edit_button', false);

//add the search string
	if (!empty($search)) {
		$search =  strtolower($_GET["search"]);
		$sql_search = " (";
		$sql_search .= "	lower(username) like :search ";
		$sql_search .= "	or lower(group_names) like :search ";
		$sql_search .= "	or lower(contact_organization) like :search ";
		$sql_search .= "	or lower(contact_name) like :search ";
		$sql_search .= "	or lower(contact_note) like :search ";
		$sql_search .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}

//get the count
	$sql = "select count(*) from view_users ";
	if ($show == "all" && permission_exists('user_all')) {
		if (isset($sql_search)) {
			$sql .= "where ".$sql_search;
		}
		else {
			$sql.= "where true ";
		}
	}
	else {
		$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
		if (!empty($sql_search)) {
			$sql .= "and ".$sql_search;
		}
		$parameters['domain_uuid'] = $domain_uuid;
	}
	$sql .= "and ( ";
	$sql .= "	group_level <= :group_level ";
	$sql .= "	or group_level is null ";
	$sql .= ") ";
	$parameters['group_level'] = $_SESSION['user']['group_level'];
	$num_rows = $database->select($sql, $parameters, 'column');

//prepare to page the results
	$rows_per_page = $settings->get('domain', 'paging', 50);
	$param = '';
	if (!empty($search)) {
		$param .= "&search=".$search;
		$param .= !empty($fields) ? "&fields=".$fields : null;
	}
	if ($show == "all" && permission_exists('user_all')) {
		$param .= "&show=all";
	}
	if (!empty($order_by)) {
		$param .= "&order_by=".$order_by;
	}
	if (!empty($order)) {
		$param .= "&order=".$order;
	}
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
	$offset = $rows_per_page * $page;
	if (!empty($order_by)) {
		$param = str_replace("&order_by=".$order_by, '', $param);
	}
	if (!empty($order)) {
		$param = str_replace("&order=".$order, '', $param);
	}

//get the list
	$sql = "select domain_name, domain_uuid, user_uuid, username, group_names, ";
	$sql .= "contact_organization,contact_name,contact_note, ";
	$sql .= "cast(user_enabled as text) ";
	$sql .= "from view_users ";
	if ($show == "all" && permission_exists('user_all')) {
		if (isset($sql_search)) {
			$sql .= "where ".$sql_search;
		}
		else {
			$sql.= "where true ";
		}
	}
	else {
		$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
		if (isset($sql_search)) {
			$sql .= "and ".$sql_search;
		}
		$parameters['domain_uuid'] = $domain_uuid;
	}
	$sql .= "and ( ";
	$sql .= "	group_level <= :group_level ";
	$sql .= "	or group_level is null ";
	$sql .= ") ";
	$parameters['group_level'] = $_SESSION['user']['group_level'];
	$sql .= order_by($order_by, $order, 'username', 'asc');
	$sql .= limit_offset($rows_per_page, $offset);
	$users = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	$document['title'] = $text['title-users'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-users']."</b><div class='count'>".number_format($num_rows)."</div></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('user_add')) {
		if (!isset($id)) {
			echo button::create(['type'=>'button','label'=>$text['button-import'],'icon'=>$settings->get('theme', 'button_icon_import'),'style'=>'margin-right: 15px;','link'=>'user_imports.php']);
		}
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$settings->get('theme', 'button_icon_add'),'id'=>'btn_add','link'=>'user_edit.php']);
	}
	if (permission_exists('user_add') && $users) {
		echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$settings->get('theme', 'button_icon_copy'),'id'=>'btn_copy','name'=>'btn_copy','style'=>'display: none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
	}
	if (permission_exists('user_edit') && $users) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$settings->get('theme', 'button_icon_toggle'),'id'=>'btn_toggle','name'=>'btn_toggle','style'=>'display: none;','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	}
	if (permission_exists('user_delete') && $users) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$settings->get('theme', 'button_icon_delete'),'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	if (permission_exists('user_all')) {
		if ($show == 'all') {
			echo "		<input type='hidden' name='show' value='all'>\n";
		}
		else {
			echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$settings->get('theme', 'button_icon_all'),'link'=>'?show=all']);
		}
	}
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown=''>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$settings->get('theme', 'button_icon_search'),'type'=>'submit','id'=>'btn_search']);
	//echo button::create(['label'=>$text['button-reset'],'icon'=>$settings->get('theme', 'button_icon_reset'),'type'=>'button','id'=>'btn_reset','link'=>'users.php','style'=>($search == '' ? 'display: none;' : null)]);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('user_add') && $users) {
		echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('copy'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('user_edit') && $users) {
		echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('user_delete') && $users) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['description-users']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='order_by' value=\"".escape($order_by)."\">\n";
	echo "<input type='hidden' name='order' value=\"".escape($order)."\">\n";
	echo "<input type='hidden' name='page' value=\"".escape($page)."\">\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";

	echo "<div class='card'>\n";
	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('user_add') || permission_exists('user_edit') || permission_exists('user_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".(!empty($users) ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	if ($show == 'all' && permission_exists('user_all')) {
		echo th_order_by('domain_name', $text['label-domain'], $order_by, $order, null, null, $param);
	}
	echo th_order_by('username', $text['label-username'], $order_by, $order, null, null, $param);
	echo th_order_by('group_names', $text['label-groups'], $order_by, $order, null, null, $param);
	echo th_order_by('contact_organization', $text['label-organization'], $order_by, $order, null, null, $param);
	echo th_order_by('contact_name', $text['label-name'], $order_by, $order, null, null, $param);
	//echo th_order_by('contact_name_family', $text['label-contact_name_family'], $order_by, $order);
	//echo th_order_by('user_status', $text['label-user_status'], $order_by, $order);
	//echo th_order_by('add_date', $text['label-add_date'], $order_by, $order);
	echo th_order_by('contact_note', $text['label-contact_note'], $order_by, $order, null, "class='center'", $param);
	echo th_order_by('user_enabled', $text['label-user_enabled'], $order_by, $order, null, "class='center'", $param);
	if (permission_exists('user_edit') && $list_row_edit_button) {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";
	if (is_array($users) && @sizeof($users) != 0) {
		$x = 0;
		foreach ($users as $row) {
			$list_row_url = '';
			if (permission_exists('user_edit')) {
				$list_row_url = "user_edit.php?id=".urlencode($row['user_uuid']).(!empty($order_by) ? '&order_by='.$order_by.'&order='.$order : null).(is_numeric($page) ? '&page='.urlencode($page) : null).(!empty($search) ? '&search='.$search : null);
				if ($row['domain_uuid'] != $_SESSION['domain_uuid'] && permission_exists('domain_select')) {
					$list_row_url .= '&domain_uuid='.urlencode($row['domain_uuid']).'&domain_change=true';
				}
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('user_add') || permission_exists('user_edit') || permission_exists('user_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='users[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='users[$x][uuid]' value='".escape($row['user_uuid'])."' />\n";
				echo "	</td>\n";
			}
			if ($show == 'all' && permission_exists('user_all')) {
				echo "	<td>".escape($_SESSION['domains'][$row['domain_uuid']]['domain_name'])."</td>\n";
			}
			echo "	<td>\n";
			if (permission_exists('user_edit')) {
				echo "	<a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['username'])."</a>\n";
			}
			else {
				echo "	".escape($row['username']);
			}
			echo "	</td>\n";
			echo "	<td>".escape($row['group_names'])."</td>\n";
			echo "	<td>".escape($row['contact_organization'])."</td>\n";
			echo "	<td>".escape($row['contact_name'])."</td>\n";
			//echo "	<td>".escape($row['contact_name_given'])."</td>\n";
			//echo "	<td>".escape($row['contact_name_family'])."</td>\n";
			//echo "	<td>".escape($row['user_status'])."</td>\n";
			//echo "	<td>".escape($row['add_date'])."</td>\n";
			echo "	<td>".escape($row['contact_note'])."</td>\n";
			if (permission_exists('user_edit')) {
				echo "	<td class='no-link center'>\n";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.$row['user_enabled']],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
			}
			else {
				echo "	<td class='center'>\n";
				echo $text['label-'.$row['user_enabled']];
			}
			echo "	</td>\n";
			if (permission_exists('user_edit') && $list_row_edit_button) {
				echo "	<td class='action-button'>\n";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$settings->get('theme', 'button_icon_edit'),'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($users);
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