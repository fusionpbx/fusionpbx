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
	Portions created by the Initial Developer are Copyright (C) 2008 - 2021
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
	if (permission_exists('user_view')) {
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
	if (is_array($_POST['users'])) {
		$action = $_POST['action'];
		$search = $_POST['search'];
		$users = $_POST['users'];
	}

//check to see if contact details are in the view
	$sql = "select * from view_users ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$parameters = null;
	$database = new database;
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$row = $database->select($sql, $parameters, 'row');
	if (isset($row['contact_organization'])) {
		$show_contact_fields = true;
	}
	else {
		$show_contact_fields = false;
	}
	unset($parameters);

//process the http post data by action
	if ($action != '' && is_array($users) && @sizeof($users) != 0) {
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

		header('Location: users.php'.($search != '' ? '?search='.urlencode($search) : null));
		exit;
	}

//get order and order by
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//add the search string
	if (isset($_GET["search"])) {
		$search =  strtolower($_GET["search"]);
		$sql_search = " (";
		$sql_search .= "	lower(username) like :search ";
		$sql_search .= "	or lower(group_names) like :search ";
		$sql_search .= "	or lower(contact_organization) like :search ";
		$sql_search .= "	or lower(contact_name) like :search ";
		//$sql_search .= "	or lower(user_status) like :search ";
		$sql_search .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}

//get the count
	$sql = "select count(*) from view_users ";
	if ($_GET['show'] == "all" && permission_exists('user_all')) {
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
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = $search ? "&search=".$search : null;
	$param .= ($_GET['show'] == 'all' && permission_exists('user_all')) ? "&show=all" : null;
	$page = is_numeric($_GET['page']) ? $_GET['page'] : 0;
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the list
	$sql = "select domain_name, domain_uuid, user_uuid, username, group_names, ";
	if ($show_contact_fields) {
		$sql .= "contact_organization,contact_name, ";
	}
	$sql .= "cast(user_enabled as text) ";
	$sql .= "from view_users ";
	if ($_GET['show'] == "all" && permission_exists('user_all')) {
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
	$database = new database;
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
	echo "	<div class='heading'><b>".$text['title-users']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('user_add')) {
		if (!isset($id)) {
			echo button::create(['type'=>'button','label'=>$text['button-import'],'icon'=>$_SESSION['theme']['button_icon_import'],'style'=>'margin-right: 15px;','link'=>'user_imports.php']);
		}
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','link'=>'user_edit.php']);
	}
	if (permission_exists('user_add') && $users) {
		echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'id'=>'btn_copy','name'=>'btn_copy','style'=>'display: none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
	}
	if (permission_exists('user_edit') && $users) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$_SESSION['theme']['button_icon_toggle'],'id'=>'btn_toggle','name'=>'btn_toggle','style'=>'display: none;','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	}
	if (permission_exists('user_delete') && $users) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	if (permission_exists('user_all')) {
		if ($_GET['show'] == 'all') {
			echo "		<input type='hidden' name='show' value='all'>\n";
		}
		else {
			echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$_SESSION['theme']['button_icon_all'],'link'=>'?show=all']);
		}
	}
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown=''>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search']);
	//echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'users.php','style'=>($search == '' ? 'display: none;' : null)]);
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
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('user_add') || permission_exists('user_edit') || permission_exists('user_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".($users ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	if ($_GET['show'] == 'all' && permission_exists('user_all')) {
		echo th_order_by('domain_name', $text['label-domain'], $order_by, $order, null, null, $param);
	}
	echo th_order_by('username', $text['label-username'], $order_by, $order, null, null, $param);
	echo th_order_by('group_names', $text['label-groups'], $order_by, $order, null, null, $param);
	if ($show_contact_fields) {
		echo th_order_by('contact_organization', $text['label-organization'], $order_by, $order, null, null, $param);
		echo th_order_by('contact_name', $text['label-name'], $order_by, $order, null, null, $param);
	}
	//echo th_order_by('contact_name_family', $text['label-contact_name_family'], $order_by, $order);
	//echo th_order_by('user_status', $text['label-user_status'], $order_by, $order);
	//echo th_order_by('add_date', $text['label-add_date'], $order_by, $order);
	echo th_order_by('user_enabled', $text['label-user_enabled'], $order_by, $order, null, "class='center'", $param);
	if (permission_exists('user_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($users) && @sizeof($users) != 0) {
		$x = 0;
		foreach ($users as $row) {
			if (permission_exists('user_edit')) {
				$list_row_url = "user_edit.php?id=".urlencode($row['user_uuid']);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('user_add') || permission_exists('user_edit') || permission_exists('user_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='users[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='users[$x][uuid]' value='".escape($row['user_uuid'])."' />\n";
				echo "	</td>\n";
			}
			if ($_GET['show'] == 'all' && permission_exists('user_all')) {
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
			if ($show_contact_fields) {
				echo "	<td>".escape($row['contact_organization'])."</td>\n";
				echo "	<td>".escape($row['contact_name'])."</td>\n";
			}
			//echo "	<td>".escape($row['contact_name_given'])."</td>\n";
			//echo "	<td>".escape($row['contact_name_family'])."</td>\n";
			//echo "	<td>".escape($row['user_status'])."</td>\n";
			//echo "	<td>".escape($row['add_date'])."</td>\n";
			if (permission_exists('user_edit')) {
				echo "	<td class='no-link center'>\n";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.$row['user_enabled']],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
			}
			else {
				echo "	<td class='center'>\n";
				echo $text['label-'.$row['user_enabled']];
			}
			echo "	</td>\n";
			if (permission_exists('user_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>\n";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($users);
	}

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>
