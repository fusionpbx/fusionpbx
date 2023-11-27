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
	Portions created by the Initial Developer are Copyright (C) 2018-2020
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('group_view')) {
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
	$groups = $_POST['groups'] ?? '';
	$action = $_POST['action'] ?? '';
	$search = $_REQUEST["search"] ?? '';
	$show = $_GET["show"] ?? '';

//process the http post data by action
	if ($action != '' && is_array($groups) && @sizeof($groups) != 0) {
		switch ($action) {
			case 'copy':
				if (permission_exists('group_add')) {
					$obj = new groups;
					$obj->copy($groups);
				}
				break;
			case 'toggle':
				if (permission_exists('group_edit')) {
					$obj = new groups;
					$obj->toggle($groups);
				}
				break;
			case 'delete':
				if (permission_exists('group_delete')) {
					$obj = new groups;
					$obj->delete($groups);
				}
				break;
		}

		header('Location: groups.php'.($search != '' ? '?search='.urlencode($search) : null));
		exit;
	}

//get order and order by
	$order_by = $_GET["order_by"] ?? '';
	$order = $_GET["order"] ?? '';

//add the search string
	if (isset($search)) {
		$search =  strtolower($search);
	}

//set from session variables
	$list_row_edit_button = !empty($_SESSION['theme']['list_row_edit_button']['boolean']) ? $_SESSION['theme']['list_row_edit_button']['boolean'] : 'false';

//get the count
	$sql = "select count(*) from view_groups \n";
	$sql .= "where true \n";
	if ($show == 'all' && permission_exists('group_all')) {
		$sql .= "and (domain_uuid is not null or domain_uuid is null) ";
	}
	else {
		$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	if (!empty($search)) {
		$sql .= "and ( \n";
		$sql .= "	lower(group_name) like :search \n";
		$sql .= "	or lower(group_description) like :search \n";
		$sql .= ") \n";
		$parameters['search'] = '%'.$search.'%';
	}
	$database = new database;
	$num_rows = $database->select($sql, $parameters ?? '', 'column');

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = $search ? "&search=".$search : null;
	$param = ($show == 'all' && permission_exists('group_all')) ? "&show=all" : null;
	$page = !empty($_GET['page']) ? $_GET['page'] : 0;
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the list
	$sql = str_replace('count(*)', '*', $sql);
	$sql .= order_by($order_by, $order, 'group_name', 'asc');
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$groups = $database->select($sql, $parameters ?? '', 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	$document['title'] = $text['title-groups'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-groups']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-users'],'icon'=>$_SESSION['theme']['button_icon_users'],'onclick'=>"window.location='../users/users.php'"]);
	echo button::create(['type'=>'button','label'=>$text['button-restore_default'],'icon'=>$_SESSION['theme']['button_icon_sync'],'style'=>'margin-right: 15px;','link'=>'permissions_default.php']);
	if (permission_exists('group_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','link'=>'group_edit.php']);
	}
	if (permission_exists('group_add') && $groups) {
		echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'id'=>'btn_copy','name'=>'btn_copy','style'=>'display: none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
	}
	if (permission_exists('group_edit') && $groups) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$_SESSION['theme']['button_icon_toggle'],'id'=>'btn_toggle','name'=>'btn_toggle','style'=>'display: none;','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	}
	if (permission_exists('group_delete') && $groups) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	if (permission_exists('group_all')) {
		if ($show == 'all') {
			echo "		<input type='hidden' name='show' value='all'>\n";
		}
		else {
			echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$_SESSION['theme']['button_icon_all'],'link'=>'?show=all']);
		}
	}
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown=''>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search']);
	//echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'groups.php','style'=>($search == '' ? 'display: none;' : null)]);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('group_add') && $groups) {
		echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('copy'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('group_edit') && $groups) {
		echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('group_delete') && $groups) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['description-groups']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('group_add') || permission_exists('group_edit') || permission_exists('group_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".(!empty($groups) ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	if ($show == 'all' && permission_exists('group_all')) {
		echo th_order_by('domain_name', $text['label-domain'], $order_by, $order);
	}
	echo th_order_by('group_name', $text['label-group_name'], $order_by, $order);
	echo "	<th class='shrink' colspan='2'>".$text['label-tools']."</th>\n";
	echo th_order_by('group_level', $text['label-group_level'], $order_by, $order, null, "class='center'");
	echo th_order_by('group_protected', $text['label-group_protected'], $order_by, $order, null, "class='center'");
	echo "	<th class='pct-30 hide-sm-dn'>".$text['label-group_description']."</th>\n";
	if (permission_exists('group_edit') && $list_row_edit_button == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($groups) && @sizeof($groups) != 0) {
		$x = 0;
		foreach ($groups as $row) {
			if (permission_exists('group_edit')) {
				$list_row_url = "group_edit.php?id=".urlencode($row['group_uuid']);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('group_add') || permission_exists('group_edit') || permission_exists('group_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='groups[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='groups[$x][uuid]' value='".escape($row['group_uuid'])."' />\n";
				echo "	</td>\n";
			}
			if ($show == 'all' && permission_exists('group_all')) {
echo "	<td>".escape($row['domain_name'])."</td>\n";
			}
			echo "	<td>\n";
			if (permission_exists('group_edit')) {
				echo "	<a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['group_name'])."</a>\n";
			}
			else {
				echo "	".escape($row['group_name']);
			}
			echo "	</td>\n";
			echo "	<td class='no-link no-wrap pr-15'><a href='group_permissions.php?group_uuid=".urlencode($row['group_uuid'])."'>".$text['label-group_permissions']." (".$row['group_permissions'].")</a></td>\n";
			echo "	<td class='no-link no-wrap'><a href='group_members.php?group_uuid=".urlencode($row['group_uuid'])."'>".$text['label-group_members']." (".$row['group_members'].")</a></td>\n";
			echo "	<td class='center'>".escape($row['group_level'])."</td>\n";
			if (permission_exists('group_edit')) {
				echo "	<td class='no-link center'>\n";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.$row['group_protected']],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
			}
			else {
				echo "	<td class='center'>\n";
				echo $text['label-'.$row['group_protected']];
			}
			echo "	</td>\n";
			echo "	<td class='description overflow hide-sm-dn'>".escape($row['group_description'])."</td>\n";
			if (permission_exists('group_edit') && $list_row_edit_button == 'true') {
				echo "	<td class='action-button'>\n";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($groups);
	}

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>
