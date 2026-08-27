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
	Portions created by the Initial Developer are Copyright (C) 2026
	the Initial Developer. All Rights Reserved.
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (!permission_exists('paging_view')) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//connect to the database
	$database = database::new();

//add the settings object
	$settings = new settings(["domain_uuid" => $_SESSION['domain_uuid'], "user_uuid" => $_SESSION['user_uuid']]);

//set from session variables
	$list_row_edit_button = $settings->get('theme', 'list_row_edit_button', 'false');

// Set variables from http GET parameters
	$page = is_numeric($_GET['page'] ?? '') ? $_GET['page'] : 0;
	$order_by = preg_replace('#[^a-zA-Z0-9_\-]#', '', ($_GET['order_by'] ?? ''));
	$order = ($_GET['order'] ?? '') === 'desc' ? 'desc' : 'asc';
	$search = $_GET['search'] ?? '';
	$show = $_GET['show'] ?? '';
	$list_row_url = '';

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
	$query_string = http_build_query($param);

//get the http post data
	if (!empty($_POST['paging'])) {
		$action = $_POST['action'] ?? null;
		$paging = $_POST['paging'];
	}

//process the http post data by action
	if (!empty($action) && !empty($paging) && is_array($paging) && @sizeof($paging) != 0) {

		//validate the token
		$token = new token;
		if (!$token->validate($_SERVER['PHP_SELF'])) {
			message::add($text['message-invalid_token'],'negative');
			header('Location: paging.php'.($query_string ? '?'.$query_string : ''));
			exit;
		}

		//process the http post data by action
		switch ($action) {
			case 'copy':
				if (permission_exists('paging_add')) {
					$obj = new paging;
					$obj->copy($paging);
				}
				break;
			case 'toggle':
				if (permission_exists('paging_edit')) {
					$obj = new paging;
					$obj->toggle($paging);
				}
				break;
			case 'delete':
				if (permission_exists('paging_delete')) {
					$obj = new paging;
					$obj->delete($paging);
				}
				break;
		}

		//redirect the user
		header('Location: paging.php'.($query_string ? '?'.$query_string : ''));
		exit;
	}

//get the count
	$sql = "select count(paging_uuid) ";
	$sql .= "from v_paging ";
	$sql .= "where true ";
	if (!empty($search)) {
		$sql .= "and ( ";
		$sql .= "	lower(paging_extension) like :search ";
		$sql .= "	or lower(paging_pin_number) like :search ";
		$sql .= "	or lower(paging_caller_id_name) like :search ";
		$sql .= "	or lower(paging_caller_id_number) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	$num_rows = $database->select($sql, $parameters ?? null, 'column');
	unset($sql, $parameters);

//prepare to page the results
	$rows_per_page = $settings->get('domain', 'paging', 50);
	list($paging_controls, $rows_per_page) = paging($num_rows, $query_string, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $query_string, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the list
	$sql = "select ";
	$sql .= "paging_uuid, ";
	$sql .= "paging_name, ";
	$sql .= "paging_extension, ";
	$sql .= "dialplan_uuid, ";
	$sql .= "paging_pin_number, ";
	$sql .= "paging_caller_id_name, ";
	$sql .= "paging_caller_id_number, ";
	$sql .= "paging_sound, ";
	$sql .= "cast(paging_delay as text), ";
	$sql .= "cast(paging_mute as text), ";
	$sql .= "cast(paging_destination_status as text), ";
	$sql .= "cast(paging_hangup_all as text), ";
	$sql .= "paging_schedule_hangup, ";
	$sql .= "cast(paging_enabled as text), ";
	$sql .= "paging_description ";
	$sql .= "from v_paging ";
	if (!empty($search)) {
		$sql .= "where ( ";
		$sql .= "	lower(paging_extension) like :search ";
		$sql .= "	or lower(paging_pin_number) like :search ";
		$sql .= "	or lower(paging_caller_id_name) like :search ";
		$sql .= "	or lower(paging_caller_id_number) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	$sql .= order_by($order_by, $order, '', '');
	$sql .= limit_offset($rows_per_page, $offset);
	$paging = $database->select($sql, $parameters ?? null, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//additional includes
	$document['title'] = $text['title-paging'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-paging']."</b><div class='count'>".$num_rows."</div></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('paging_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','name'=>'btn_add','link'=>'paging_edit.php']);
	}
	if (permission_exists('paging_add') && $paging) {
		echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'id'=>'btn_copy','name'=>'btn_copy','style'=>'display:none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
	}
	if (permission_exists('paging_edit') && $paging) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$_SESSION['theme']['button_icon_toggle'],'id'=>'btn_toggle','name'=>'btn_toggle','style'=>'display:none;','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	}
	if (permission_exists('paging_delete') && $paging) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display:none;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown=''>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search']);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('paging_add') && $paging) {
		echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('copy'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('paging_edit') && $paging) {
		echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('paging_delete') && $paging) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['title_description-paging']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";

	echo "<div class='card'>\n";
	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('paging_add') || permission_exists('paging_edit') || permission_exists('paging_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".empty($paging ? "style='visibility: hidden;'" : null).">\n";
		echo "	</th>\n";
	}
	echo th_order_by('paging_name', $text['label-paging_name'], $order_by, $order, null, null, $query_string);
	echo th_order_by('paging_extension', $text['label-paging_extension'], $order_by, $order, null, null, $query_string);
	echo th_order_by('paging_delay', $text['label-paging_delay'], $order_by, $order, null, "class='center'", $query_string);
	echo th_order_by('paging_mute', $text['label-paging_mute'], $order_by, $order, null, "class='center'", $query_string);
	echo th_order_by('paging_hangup_all', $text['label-paging_hangup_all'], $order_by, $order, null, "class='center'", $query_string);
	echo th_order_by('paging_schedule_hangup', $text['label-paging_schedule_hangup'], $order_by, $order, null, null, $query_string);
	echo th_order_by('paging_enabled', $text['label-enabled'], $order_by, $order, null, "class='center'", $query_string);
	echo "	<th class='hide-sm-dn'>".$text['label-paging_description']."</th>\n";
	if (permission_exists('paging_edit') && $list_row_edit_button == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (!empty($paging) && is_array($paging) && @sizeof($paging) != 0) {
		$x = 0;
		foreach ($paging as $row) {
			if (permission_exists('paging_edit')) {
				$list_row_url = "paging_edit.php?id=".urlencode($row['paging_uuid']);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('paging_add') || permission_exists('paging_edit') || permission_exists('paging_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='paging[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='paging[$x][uuid]' value='".escape($row['paging_uuid'])."' />\n";
				echo "	</td>\n";
			}
			echo "	<td>\n";
			if (permission_exists('paging_edit')) {
				echo "	<a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['paging_name'])."</a>\n";
			}
			else {
				echo "	".escape($row['paging_name']);
			}
			echo "	</td>\n";
			echo "	<td>".$row['paging_extension']."&nbsp;</td>\n";
			echo "	<td class='center'>".$text['label-'.$row['paging_delay']]."&nbsp;</td>\n";
			echo "	<td class='center'>".$text['label-'.$row['paging_mute']]."&nbsp;</td>\n";
			echo "	<td class='center'>".$text['label-'.$row['paging_hangup_all']]."&nbsp;</td>\n";
			echo "	<td>".escape($row['paging_schedule_hangup'])."</td>\n";
			if (permission_exists('paging_edit')) {
				echo "	<td class='no-link center'>\n";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.$row['paging_enabled']],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
			}
			else {
				echo "	<td class='center'>\n";
				echo $text['label-'.$row['paging_enabled']];
			}
			echo "	</td>\n";
			echo "	<td class='description overflow hide-sm-dn'>".escape($row['paging_description'])."</td>\n";
			if (permission_exists('paging_edit') && $list_row_edit_button == 'true') {
				echo "	<td class='action-button'>\n";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($paging);
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
