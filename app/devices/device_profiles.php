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
	Copyright (C) 2019 All Rights Reserved.

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
	if (permission_exists('device_profile_view')) {
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
	if (is_array($_POST['profiles'])) {
		$action = $_POST['action'];
		$search = $_POST['search'];
		$profiles = $_POST['profiles'];
	}

//get the search
	$search = strtolower($_REQUEST["search"]);
	$fields = strtolower($_REQUEST["fields"]);

//process the http post data by action
	if ($action != '' && is_array($profiles) && @sizeof($profiles) != 0) {
		switch ($action) {
			case 'copy':
				if (permission_exists('device_profile_add')) {
					$obj = new device;
					$obj->copy_profiles($profiles);
				}
				break;
			case 'toggle':
				if (permission_exists('device_profile_edit')) {
					$obj = new device;
					$obj->toggle_profiles($profiles);
				}
				break;
			case 'delete':
				if (permission_exists('device_profile_delete')) {
					$obj = new device;
					$obj->delete_profiles($profiles);
				}
				break;
		}

		header('Location: device_profiles.php'.($search != '' ? '?search='.urlencode($search).'&fields='.urlencode($fields) : null));
		exit;
	}

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//add the search term
	if (strlen($search) > 0) {
		$sql_search = "and (";
		$sql_search .= "	lower(device_profile_name) like :search ";
		$sql_search .= "	or lower(device_profile_description) like :search ";
		if ($fields == 'all' || $fields == 'keys') {
			$sql_search .= "	or device_profile_uuid in ( ";
			$sql_search .= "		select dpk.device_profile_uuid from v_device_profile_keys as dpk ";
			$sql_search .= "		where dpk.profile_key_value like :search ";
			$sql_search .= "		or dpk.profile_key_label like :search ";
			$sql_search .= "	) ";
		}
		if ($fields == 'all' || $fields == 'settings') {
			$sql_search .= "	or device_profile_uuid in ( ";
			$sql_search .= "		select dps.device_profile_uuid from v_device_profile_settings as dps ";
			$sql_search .= "		where dps.profile_setting_name like :search ";
			$sql_search .= "		or dps.profile_setting_value like :search ";
			$sql_search .= "		or dps.profile_setting_description like :search ";
			$sql_search .= "	) ";
		}
		$sql_search .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}

//get the count
	$sql = "select count(*) from v_device_profiles ";
	$sql .= "where true ";
	if ($_GET['show'] != "all" || !permission_exists('device_profile_all')) {
		$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	$sql .= $sql_search;
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	if ($search) {
		$param = "&search=".$search;
		$param .= "&fields=".$fields;
	}
	if ($_GET['show'] == "all" && permission_exists('device_profile_all')) {
		$param .= "&show=all";
	}
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page); //bottom
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true); //top
	$offset = $rows_per_page * $page;

//get the list
	$sql = str_replace('count(*)', '*', $sql);
	$sql .= order_by($order_by, $order, 'device_profile_name', 'asc');
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$device_profiles = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	$document['title'] = $text['title-device_profiles'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-device_profiles']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>'devices.php']);
	if (permission_exists('device_profile_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','link'=>'device_profile_edit.php']);
	}
	if (permission_exists('device_profile_add') && $device_profiles) {
		echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'name'=>'btn_copy','onclick'=>"modal_open('modal-copy','btn_copy');"]);
	}
	if (permission_exists('device_profile_edit') && $device_profiles) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$_SESSION['theme']['button_icon_toggle'],'name'=>'btn_toggle','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	}
	if (permission_exists('device_profile_delete') && $device_profiles) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	if (permission_exists('device_profile_all')) {
		if ($_GET['show'] == 'all') {
			echo "		<input type='hidden' name='show' value='all'>";
		}
		else {
			echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$_SESSION['theme']['button_icon_all'],'link'=>'?show=all']);
		}
	}

	echo 		"<select class='formfld' name='fields' id='select_fields' style='width: auto; margin-left: 15px;' onchange=\"if (document.getElementById('search').value != '') { this.form.submit(); }\">\n";
	echo "			<option value=''>".$text['label-fields']."...</option>\n";
	echo "			<option value=''>".$text['label-default']."</option>\n";
	echo "			<option value='keys' ".($fields == 'keys' ? " selected='selected'" : null).">".$text['label-keys']."</option>\n";
	echo "			<option value='settings' ".($fields == 'settings' ? " selected='selected'" : null).">".$text['label-settings']."</option>\n";
	echo "			<option value='all' ".($fields == 'all' ? " selected='selected'" : null).">".$text['label-all']."</option>\n";
	echo "		</select>";
	echo 		"<input type='text' class='txt list-search' name='search' id='search' style='margin-left: 0 !important;' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown='list_search_reset();'>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search','style'=>($search != '' ? 'display: none;' : null)]);
	echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'device_profiles.php','style'=>($search == '' ? 'display: none;' : null)]);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('device_profile_add') && $device_profiles) {
		echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('copy'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('device_profile_edit') && $device_profiles) {
		echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('device_profile_delete') && $device_profiles) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['description-device_profiles']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";
	echo "<input type='hidden' name='fields' value=\"".escape($fields)."\">\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('device_profile_add') || permission_exists('device_profile_edit') || permission_exists('device_profile_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle();' ".($device_profiles ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	if ($_GET['show'] == "all" && permission_exists('device_profile_all')) {
		echo th_order_by('domain_name', $text['label-domain'], $order_by, $order, $param);
	}
	echo th_order_by('device_profile_name', $text['label-device_profile_name'], $order_by, $order);
	echo th_order_by('device_profile_enabled', $text['label-device_profile_enabled'], $order_by, $order, null, "class='center'");
	echo th_order_by('device_profile_description', $text['label-device_profile_description'], $order_by, $order, null, "class='hide-xs'");
	if (permission_exists('device_profile_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($device_profiles) && @sizeof($device_profiles) != 0) {
		$x = 0;
		foreach($device_profiles as $row) {
			if (permission_exists('device_profile_edit')) {
				$list_row_url = "device_profile_edit.php?id=".urlencode($row['device_profile_uuid']);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('device_profile_add') || permission_exists('device_profile_edit') || permission_exists('device_profile_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='profiles[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='profiles[$x][uuid]' value='".escape($row['device_profile_uuid'])."' />\n";
				echo "	</td>\n";
			}
			if ($_GET['show'] == "all" && permission_exists('device_profile_all')) {
				if (strlen($_SESSION['domains'][$row['domain_uuid']]['domain_name']) > 0) {
					$domain = $_SESSION['domains'][$row['domain_uuid']]['domain_name'];
				}
				else {
					$domain = $text['label-global'];
				}
				echo "	<td>".escape($domain)."</td>\n";
			}
			echo "	<td>";
			if (permission_exists('device_profile_edit')) {
				echo "	<a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['device_profile_name'])."</a>\n";
			}
			else {
				echo "	".escape($row['device_profile_name'])."\n";
			}
			echo "	</td>\n";
			if (permission_exists('device_profile_edit')) {
				echo "	<td class='no-link center'>";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.$row['device_profile_enabled']],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
			}
			else {
				echo "	<td class='center'>";
				echo $text['label-'.$row['device_profile_enabled']];
			}
			echo "	</td>\n";
			echo "	<td class='description overflow hide-xs'>".escape($row['device_profile_description'])."&nbsp;</td>\n";
			if (permission_exists('device_profile_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($device_profiles);
	}

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>