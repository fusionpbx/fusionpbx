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
	Portions created by the Initial Developer are Copyright (C) 2008 - 2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J. Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('ivr_menu_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//define defaults
	$action = '';
	$search = '';
	$ivr_menus = '';

//get posted data
	if (!empty($_POST['ivr_menus'])) {
		$action = $_POST['action'];
		$search = $_POST['search'];
		$ivr_menus = $_POST['ivr_menus'];
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

		header('Location: ivr_menus.php'.(!empty($search) ? '?search='.urlencode($search) : null));
		exit;
	}

//get order and order by
	$order_by = $_GET["order_by"] ?? '';
	$order = $_GET["order"] ?? '';
	$sort = $order_by == 'ivr_menu_extension' ? 'natural' : null;

//add the search variable
	$search = $_GET["search"] ?? '';
	$show = $_GET["show"] ?? '';

//set from session variables
	$list_row_edit_button = !empty($_SESSION['theme']['list_row_edit_button']['boolean']) ? $_SESSION['theme']['list_row_edit_button']['boolean'] : 'false';

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
		$search = strtolower($search);
		$sql .= "and (";
		$sql .= "	lower(ivr_menu_name) like :search ";
		$sql .= "	or lower(ivr_menu_extension) like :search ";
		$sql .= "	or lower(ivr_menu_enabled) like :search ";
		$sql .= "	or lower(ivr_menu_description) like :search ";
		$sql .= ")";
		$parameters['search'] = '%'.$search.'%';
	}
	$database = new database;
	$num_rows = $database->select($sql, $parameters ?? '', 'column');

//prepare to page the results
	$rows_per_page = (!empty($_SESSION['domain']['paging']['numeric'])) ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "&search=".urlencode($search);
	if ($show == "all" && permission_exists('ivr_menu_all')) {
		$param .= "&show=all";
	}
	$page = !empty($_GET['page']) ? $_GET['page'] : 0;
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the list
	$sql = "select * from v_ivr_menus ";
	if ($show == "all" && permission_exists('ivr_menu_all')) {
		$sql .= "where true ";
	}
	else {
		$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	}
	if (!empty($search)) {
		$search = strtolower($search);
		$sql .= "and (";
		$sql .= "	lower(ivr_menu_name) like :search ";
		$sql .= "	or lower(ivr_menu_extension) like :search ";
		$sql .= "	or lower(ivr_menu_enabled) like :search ";
		$sql .= "	or lower(ivr_menu_description) like :search ";
		$sql .= ")";
		$parameters['search'] = '%'.$search.'%';
	}
	$sql .= order_by($order_by, $order, 'ivr_menu_name', 'asc', $sort);
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$ivr_menus = $database->select($sql, $parameters ?? '', 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//additional includes
	$document['title'] = $text['title-ivr_menus'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-ivr_menus']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('ivr_menu_add') && (empty($_SESSION['limit']['ivr_menus']['numeric']) || $num_rows < $_SESSION['limit']['ivr_menus']['numeric'])) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','link'=>'ivr_menu_edit.php']);
	}
	if (permission_exists('ivr_menu_add') && $ivr_menus && (empty($_SESSION['limit']['ivr_menus']['numeric']) || $num_rows < $_SESSION['limit']['ivr_menus']['numeric'])) {
		echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'id'=>'btn_copy','name'=>'btn_copy','style'=>'display: none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
	}
	if (permission_exists('ivr_menu_edit') && $ivr_menus) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$_SESSION['theme']['button_icon_toggle'],'id'=>'btn_toggle','name'=>'btn_toggle','style'=>'display: none;','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	}
	if (permission_exists('ivr_menu_delete') && $ivr_menus) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	if (permission_exists('ivr_menu_all')) {
		if ($show == 'all') {
			echo "		<input type='hidden' name='show' value='all'>";
		}
		else {
			echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$_SESSION['theme']['button_icon_all'],'link'=>'?type=&show=all'.($search != '' ? "&search=".urlencode($search) : null)]);
		}
	}
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown=''>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search']);
	//echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'ivr_menus.php','style'=>($search == '' ? 'display: none;' : null)]);
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
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('ivr_menu_add') || permission_exists('ivr_menu_edit') || permission_exists('ivr_menu_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".(!empty($ivr_menus) ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	if ($show == "all" && permission_exists('ivr_menu_all')) {
		echo th_order_by('domain_name', $text['label-domain'], $order_by, $order, $param, "class='shrink'");
	}
	echo th_order_by('ivr_menu_name', $text['label-name'], $order_by, $order);
	echo th_order_by('ivr_menu_extension', $text['label-extension'], $order_by, $order);
	echo th_order_by('ivr_menu_enabled', $text['label-enabled'], $order_by, $order, null, "class='center'");
	echo th_order_by('ivr_menu_description', $text['label-description'], $order_by, $order, null, "class='hide-sm-dn'");
	if (permission_exists('ivr_menu_edit') && $list_row_edit_button == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (!empty($ivr_menus)) {
		$x = 0;
		foreach($ivr_menus as $row) {
			if (permission_exists('ivr_menu_edit')) {
				$list_row_url = "ivr_menu_edit.php?id=".urlencode($row['ivr_menu_uuid']);
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
			if (permission_exists('ivr_menu_edit') && $list_row_edit_button == 'true') {
				echo "	<td class='action-button'>";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
	}
	unset($ivr_menus);

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>
