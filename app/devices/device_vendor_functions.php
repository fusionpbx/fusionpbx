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
 Portions created by the Initial Developer are Copyright (C) 2016-2023
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('device_vendor_function_view')) {
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
	if (!empty($_POST['vendor_functions']) && is_array($_POST['vendor_functions'])) {
		$action = $_POST['action'];
		$device_vendor_uuid = $_POST['device_vendor_uuid'];
		$vendor_functions = $_POST['vendor_functions'];
	}

//process the http post data by action
	if (!empty($action) && !empty($vendor_functions) && is_array($vendor_functions) && @sizeof($vendor_functions) != 0) {
		switch ($action) {
			case 'toggle':
				if (permission_exists('device_vendor_function_edit')) {
					$obj = new device;
					$obj->device_vendor_uuid = $device_vendor_uuid;
					$obj->toggle_vendor_functions($vendor_functions);
				}
				break;
			case 'delete':
				if (permission_exists('device_vendor_function_delete')) {
					$obj = new device;
					$obj->device_vendor_uuid = $device_vendor_uuid;
					$obj->delete_vendor_functions($vendor_functions);
				}
				break;
		}

		header('Location: device_vendor_edit.php?id='.urlencode($device_vendor_uuid));
		exit;
	}

//get variables used to control the order
	$order_by = $_GET["order_by"] ?? null;
	$order = $_GET["order"] ?? null;

//prepare to page the results
	$sql = "select count(*) from v_device_vendor_functions ";
	$sql .= "where device_vendor_uuid = :device_vendor_uuid ";
	if (isset($_GET["search"])) {
		$sql .= "and (";
		$sql .= "	label like :search ";
		$sql .= "	or type like :search ";
		$sql .= "	or subtype like :search ";
		$sql .= "	or enabled like :search ";
		$sql .= "	or description like :search ";
		$sql .= ")";
		$parameters['search'] = '%'.$_GET["search"].'%';
	}
	$parameters['device_vendor_uuid'] = $device_vendor_uuid;
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');
	unset($sql, $parameters);

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "";
	if (isset($_GET['page'])) {
		$page = $_GET['page'];
		if (empty($page)) { $page = 0; $_GET['page'] = 0; }
		list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
		list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
		$offset = $rows_per_page * $page;
	}

//get the list
	$sql = "select * from v_device_vendor_functions ";
	$sql .= "where device_vendor_uuid = :device_vendor_uuid ";
	if (isset($_GET["search"])) {
		$sql .= "and (";
		$sql .= "	label like :search ";
		$sql .= "	or type like :search ";
		$sql .= "	or subtype like :search ";
		$sql .= "	or value like :search ";
		$sql .= "	or enabled like :search ";
		$sql .= "	or description like :search ";
		$sql .= ")";
		$parameters['search'] = '%'.$_GET["search"].'%';
	}
	$parameters['device_vendor_uuid'] = $device_vendor_uuid;
	$sql .= order_by($order_by, $order, 'type', 'asc');
	$sql .= limit_offset($rows_per_page, $offset ?? null);
	$database = new database;
	$vendor_functions = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create('/app/devices/device_vendor_functions.php');

//show the content
	echo "<form id='form_list' method='post' action='device_vendor_functions.php'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='device_vendor_uuid' value='".escape($device_vendor_uuid)."'>\n";

	echo "<div class='action_bar' id='action_bar_sub'>\n";
	echo "	<div class='heading'><b id='heading_sub'>".$text['title-device_vendor_functions']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','id'=>'action_bar_sub_button_back','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'collapse'=>'hide-xs','style'=>'margin-right: 15px; display: none;','link'=>'device_vendors.php']);
	if (permission_exists('device_vendor_function_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','link'=>'device_vendor_function_edit.php?device_vendor_uuid='.urlencode($_GET['id'])]);
	}
	if (permission_exists('device_vendor_function_edit') && $vendor_functions) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$_SESSION['theme']['button_icon_toggle'],'id'=>'btn_toggle','name'=>'btn_toggle','style'=>'display: none;','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	}
	if (permission_exists('device_vendor_function_delete') && $vendor_functions) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	if (!empty($paging_controls_mini)) {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('device_vendor_function_edit') && $vendor_functions) {
		echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('device_vendor_function_delete') && $vendor_functions) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('device_vendor_function_add') || permission_exists('device_vendor_function_edit') || permission_exists('device_vendor_function_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".(empty($vendor_functions) ? "style='visibility: hidden;'" : null).">\n";
		echo "	</th>\n";
	}
	echo th_order_by('type', $text['label-type'], $order_by, $order);
	echo th_order_by('subtype', $text['label-subtype'], $order_by, $order);
	echo th_order_by('value', $text['label-value'], $order_by, $order);
	echo "<th class='hide-sm-dn'>".$text['label-groups']."</th>\n";
	echo th_order_by('enabled', $text['label-enabled'], $order_by, $order, null, "class='center'");
	echo th_order_by('description', $text['label-description'], $order_by, $order, null, "class='hide-sm-dn'");
	if (permission_exists('device_vendor_function_edit') && !empty($_SESSION['theme']['list_row_edit_button']['boolean']) && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($vendor_functions) && @sizeof($vendor_functions) != 0) {
		$x = 0;
		foreach ($vendor_functions as $row) {

			//get the groups that have been assigned to the vendor functions
				$sql = "select ";
				$sql .= "fg.*, g.domain_uuid as group_domain_uuid ";
				$sql .= "from ";
				$sql .= "v_device_vendor_function_groups as fg, ";
				$sql .= "v_groups as g ";
				$sql .= "where ";
				$sql .= "fg.group_uuid = g.group_uuid ";
				$sql .= "and fg.device_vendor_uuid = :device_vendor_uuid ";
				$sql .= "and fg.device_vendor_function_uuid = :device_vendor_function_uuid ";
				$sql .= "order by ";
				$sql .= "g.domain_uuid desc, ";
				$sql .= "g.group_name asc ";
				$parameters['device_vendor_uuid'] = $device_vendor_uuid;
				$parameters['device_vendor_function_uuid'] = $row['device_vendor_function_uuid'];
				$database = new database;
				$vendor_function_groups = $database->select($sql, $parameters, 'all');
				unset($sql, $parameters);
				unset($group_list);
				foreach ($vendor_function_groups as &$sub_row) {
					$group_list[] = escape($sub_row["group_name"]).(($sub_row['group_domain_uuid'] != '') ? "@".escape($_SESSION['domains'][$sub_row['group_domain_uuid']]['domain_name']) : null);
				}
				$group_list = isset($group_list) ? implode(', ', $group_list) : '';
				unset ($vendor_function_groups);

			//show the row of data
				if (permission_exists('device_vendor_function_edit')) {
					$list_row_url = "device_vendor_function_edit.php?device_vendor_uuid=".urlencode($row['device_vendor_uuid'])."&id=".urlencode($row['device_vendor_function_uuid']);
				}
				echo "<tr class='list-row' href='".$list_row_url."'>\n";
				if (permission_exists('device_vendor_function_add') || permission_exists('device_vendor_function_edit') || permission_exists('device_vendor_function_delete')) {
					echo "	<td class='checkbox'>\n";
					echo "		<input type='checkbox' name='vendor_functions[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
					echo "		<input type='hidden' name='vendor_functions[$x][uuid]' value='".escape($row['device_vendor_function_uuid'])."' />\n";
					echo "	</td>\n";
				}

				echo "	<td>\n";
				if (permission_exists('device_vendor_function_edit')) {
					echo "	<a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['type'])."</a>\n";
				}
				else {
					echo "	".escape($row['type']);
				}
				echo "	</td>\n";

				echo "	<td>\n";
				if (permission_exists('device_vendor_function_edit')) {
					echo "	<a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['subtype'])."</a>\n";
				}
				else {
					echo "	".escape($row['subtype']);
				}
				echo "	</td>\n";

				echo "	<td>".escape($row['value'])."&nbsp;</td>\n";
				echo "	<td class='hide-sm-dn'>".escape($group_list)."&nbsp;</td>\n";
				if (permission_exists('device_vendor_function_edit')) {
					echo "	<td class='no-link center'>\n";
					echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.$row['enabled']],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
				}
				else {
					echo "	<td class='center'>\n";
					echo $text['label-'.$row['enabled']];
				}
				echo "	</td>\n";
				echo "	<td class='description overflow hide-sm-dn'>".escape($row['description'])."</td>\n";
				if (permission_exists('device_vendor_function_edit') && !empty($_SESSION['theme']['list_row_edit_button']['boolean']) && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
					echo "	<td class='action-button'>\n";
					echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
					echo "	</td>\n";
				}
				echo "</tr>\n";
				$x++;
		}
		unset($vendor_functions);
	}

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".($paging_controls ?? '')."</div>\n";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//make sub action bar sticky
	echo "<script>\n";

	echo "	window.addEventListener('scroll', function(){\n";
	echo "		action_bar_scroll('action_bar_sub', 260, heading_modify, heading_restore);\n";
	echo "	}, false);\n";

	echo "	function heading_modify() {\n";
	echo "		document.getElementById('action_bar_sub_button_back').style.display = 'inline-block';\n";
	echo "	}\n";

	echo "	function heading_restore() {\n";
	echo "		document.getElementById('action_bar_sub_button_back').style.display = 'none';\n";
	echo "	}\n";

	echo "</script>\n";

?>