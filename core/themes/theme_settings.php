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

//check permissions
	if (!permission_exists('theme_setting_view')) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

// Set variables from http GET parameters
	$page = is_numeric($_GET['page'] ?? '') ? $_GET['page'] : 0;
	$order_by = preg_replace('#[^a-zA-Z0-9_\-]#', '', $_GET['order_by'] ?? '');
	$order = ($_GET['order'] ?? '') === 'desc' ? 'desc' : 'asc';
	$search = $_GET['search'] ?? '';
	$show = $_GET['show'] ?? '';

// Build the query string
	$url_params = [];
	if (!empty($theme_uuid)) {
		$url_params['id'] = $theme_uuid;
	}
	if (!empty($page)) {
		$url_params['page'] = $page;
	}
	if (!empty($_GET['order_by'])) {
		$url_params['order_by'] = $order_by;
	}
	if (!empty($_GET['order'])) {
		$url_params['order'] = $order;
	}
	if (!empty($search)) {
		$url_params['search'] = $search;
	}
	if (!empty($show) && $show == 'all' && permission_exists('theme_all')) {
		$url_params['show'] = $show;
	}
	$query_string = http_build_query($url_params);

//get theme uuid
	if (permission_exists('theme_edit') && !empty($_REQUEST["id"]) && is_uuid($_REQUEST["id"])) {
		$theme_uuid = $_REQUEST["id"];
	}

//get http post variables and set them to php variables
	if (!empty($_POST)) {
		$action = $_POST["action"] ?? null;
		$theme_settings = $_POST['theme_settings'] ?? null;
	}

//process the http post data by action
	if (!empty($action) && !empty($theme_settings)) {

		//validate the token
		$token = new token;
		if (!$token->validate($_SERVER['PHP_SELF'])) {
			message::add($text['message-invalid_token'],'negative');
			header('Location: theme_settings.php'.($query_string ? '?'.$query_string : ''));
			exit;
		}

		//process the http post data by action
		switch ($action) {
			case 'copy':
				if (permission_exists('theme_setting_add')) {
					$obj = new themes;
					$obj->copy_settings($theme_settings);
				}
				break;
			case 'toggle':
				if (permission_exists('theme_setting_edit')) {
					$obj = new themes;
					$obj->toggle_settings($theme_settings);
				}
				break;
			case 'delete':
				if (permission_exists('theme_setting_delete')) {
					$obj = new themes;
					$obj->delete_settings($theme_settings);
				}
				break;
		}

		//redirect the user
		header('Location: theme_settings.php?id='.urlencode($theme_uuid));
		exit;
	}

//set from session variables
	$list_row_edit_button = $settings->get('theme', 'list_row_edit_button', 'false');

//get the count
	$sql = "select count(theme_setting_uuid) ";
	$sql .= "from v_theme_settings ";
	$sql .= "where true ";
	if (!empty($search)) {
		$sql .= "and ( ";
		$sql .= "	lower(theme_setting_name) like :search ";
		$sql .= "	or lower(theme_setting_type) like :search ";
		$sql .= "	or lower(theme_setting_value) like :search ";
		$sql .= "	or lower(theme_setting_description) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.strtolower($search).'%';
	}
	$sql .= "and theme_uuid = :theme_uuid ";
	$parameters['theme_uuid'] = $theme_uuid;
	$num_rows = $database->select($sql, $parameters ?? null, 'column');
	unset($sql, $parameters);

//get the list
	$sql = "select ";
	$sql .= "theme_setting_uuid, ";
	$sql .= "theme_setting_name, ";
	$sql .= "theme_setting_type, ";
	$sql .= "theme_setting_value, ";
	$sql .= "theme_setting_enabled, ";
	$sql .= "theme_setting_description ";
	$sql .= "from v_theme_settings ";
	$sql .= "where true ";
	if (!empty($search)) {
		$sql .= "and ( ";
		$sql .= "	lower(theme_setting_name) like :search ";
		$sql .= "	or lower(theme_setting_type) like :search ";
		$sql .= "	or lower(theme_setting_value) like :search ";
		$sql .= "	or lower(theme_setting_description) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.strtolower($search).'%';
	}
	$sql .= "and theme_uuid = :theme_uuid ";
	$parameters['theme_uuid'] = $theme_uuid;
	$sql .= order_by($order_by, $order, 'theme_setting_name', 'asc');
	$theme_settings = $database->select($sql, $parameters ?? null, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//additional includes
	$document['title'] = $text['title-theme_settings'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-theme_settings']."</b><div class='count'>".$num_rows."</div></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$settings->get('theme', 'button_icon_back', ''),'id'=>'btn_back','collapse'=>'hide-xs','style'=>'margin-right: 15px;','link'=>'theme_edit.php?id='.$theme_uuid]);
	if (permission_exists('theme_setting_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$settings->get('theme', 'button_icon_add'),'id'=>'btn_add','name'=>'btn_add','link'=>'theme_setting_edit.php?theme_uuid='.$theme_uuid]);
	}
	if (permission_exists('theme_setting_add') && $theme_settings) {
		echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$settings->get('theme', 'button_icon_copy'),'id'=>'btn_copy','name'=>'btn_copy','style'=>'display:none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
	}
	if (permission_exists('theme_setting_edit') && $theme_settings) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$settings->get('theme', 'button_icon_toggle'),'id'=>'btn_toggle','name'=>'btn_toggle','style'=>'display:none;','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	}
	if (permission_exists('theme_setting_delete') && $theme_settings) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$settings->get('theme', 'button_icon_delete'),'id'=>'btn_delete','name'=>'btn_delete','style'=>'display:none;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo "		<form id='form_search' class='inline' method='get'>\n";
	foreach ($url_params as $key => $value) {
		if (in_array($key, ['id', 'order_by', 'order', 'show'])) {
			echo "		<input type='hidden' name='".escape($key)."' value='".escape($value)."'>\n";
		}
	}
	echo "		<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown=''>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search']);
	if (!empty($paging_controls_mini)) {
		echo "	<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('theme_setting_add') && $theme_settings) {
		echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('copy'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('theme_setting_edit') && $theme_settings) {
		echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('theme_setting_delete') && $theme_settings) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['title_description-theme_settings']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search ?? '')."\">\n";

	echo "<div class='card'>\n";
	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('theme_setting_add') || permission_exists('theme_setting_edit') || permission_exists('theme_setting_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".empty($theme_settings ? "style='visibility: hidden;'" : null).">\n";
		echo "	</th>\n";
	}
	echo th_order_by('theme_setting_name', $text['label-theme_setting_name'], $order_by, $order, null, null, $url_params);
	echo th_order_by('theme_setting_type', $text['label-theme_setting_type'], $order_by, $order, null, null, $url_params);
	echo th_order_by('theme_setting_value', $text['label-theme_setting_value'], $order_by, $order, null, null, $url_params);
	echo th_order_by('theme_setting_enabled', $text['label-theme_setting_enabled'], $order_by, $order, null, "class='center'", $url_params);
	echo "	<th class='hide-sm-dn'>".$text['label-theme_setting_description']."</th>\n";
	if (permission_exists('theme_setting_edit') && $list_row_edit_button == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (!empty($theme_settings) && is_array($theme_settings) && @sizeof($theme_settings) != 0) {
		$x = 0;
		foreach ($theme_settings as $row) {
			$list_row_url = '';
			if (permission_exists('theme_setting_edit')) {
				$list_row_url = "theme_setting_edit.php?id=".urlencode($row['theme_setting_uuid']).'&theme_uuid='.$theme_uuid;
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('theme_setting_add') || permission_exists('theme_setting_edit') || permission_exists('theme_setting_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='theme_settings[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='theme_settings[$x][theme_setting_uuid]' value='".escape($row['theme_setting_uuid'])."' />\n";
				echo "	</td>\n";
			}
			echo "	<td>".escape($row['theme_setting_name'])."</td>\n";
			echo "	<td>".escape($row['theme_setting_type'])."</td>\n";
			echo "	<td>\n";
			if ((substr_count($row['theme_setting_name'], "_color") > 0 || str_starts_with($row['theme_setting_value'] ?? '', "rgb") || str_starts_with($row['theme_setting_value'] ?? '', "#")) && ($row['theme_setting_type'] == "text" || $row['theme_setting_type'] == 'array')) {
				echo "		".(img_spacer('15px', '15px', 'background: '.escape($row['theme_setting_value']).'; margin-right: 4px; vertical-align: middle; border: 1px solid '.(color_adjust($row['theme_setting_value'], -0.18)).'; padding: -1px;'));
				echo "<span style=\"font-family: 'Courier New'; line-height: 6pt;\">".escape($row['theme_setting_value'])."</span>\n";
			} else {
				echo escape($row['theme_setting_value']);
			}
			echo "	</td>\n";
			if (permission_exists('theme_setting_edit')) {
				echo "	<td class='no-link center'>\n";
				echo "		<input type='hidden' name='number_translations[$x][theme_setting_enabled]' value='".escape($row['theme_setting_enabled'])."' />\n";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.($row['theme_setting_enabled'] ? 'true' : 'false')],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
			}
			else {
				echo "	<td class='center'>\n";
				echo $text['label-'.$row['theme_setting_enabled']];
			}
			echo "	</td>\n";
			echo "	<td class='description overflow hide-sm-dn'>".escape($row['theme_setting_description'])."</td>\n";
			if (permission_exists('theme_setting_edit') && $list_row_edit_button == 'true') {
				echo "	<td class='action-button'>\n";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($theme_settings);
	}

	echo "</table>\n";
	echo "</div>\n";
	echo "<br />\n";
	echo "<div align='center'>".($paging_controls ?? '')."</div>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>
