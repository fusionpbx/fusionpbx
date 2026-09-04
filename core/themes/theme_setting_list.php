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

//set from session variables
	$list_row_edit_button = $settings->get('theme', 'list_row_edit_button', 'false');

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
	$sql = "select count(theme_setting_uuid) ";
	$sql .= "from v_theme_settings ";
	$sql .= "where true ";
	if (!empty($search)) {
		$sql .= "and ( ";
		$sql .= "	lower(theme_setting_category) like :search ";
		$sql .= "	or lower(theme_setting_subcategory) like :search ";
		$sql .= "	or lower(theme_setting_name) like :search ";
		$sql .= "	or lower(theme_setting_value) like :search ";
		$sql .= "	or lower(theme_setting_description) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	$sql .= "and theme_uuid = :theme_uuid ";
	$parameters['theme_uuid'] = $theme_uuid;
	$num_rows = $database->select($sql, $parameters ?? null, 'column');
	unset($sql, $parameters);

//get the list
	$sql = "select ";
	$sql .= "theme_setting_uuid, ";
	$sql .= "theme_setting_category, ";
	$sql .= "theme_setting_subcategory, ";
	$sql .= "theme_setting_name, ";
	$sql .= "theme_setting_value, ";
	$sql .= "cast(theme_setting_enabled as text), ";
	$sql .= "theme_setting_description ";
	$sql .= "from v_theme_settings ";
	$sql .= "where true ";
	if (!empty($search)) {
		$sql .= "and ( ";
		$sql .= "	lower(theme_setting_category) like :search ";
		$sql .= "	or lower(theme_setting_subcategory) like :search ";
		$sql .= "	or lower(theme_setting_name) like :search ";
		$sql .= "	or lower(theme_setting_value) like :search ";
		$sql .= "	or lower(theme_setting_description) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	$sql .= "and theme_uuid = :theme_uuid ";
	$parameters['theme_uuid'] = $theme_uuid;
	$sql .= order_by($order_by, $order, 'theme_setting_subcategory', 'asc');
	$sql .= limit_offset($rows_per_page, $offset);
	$theme_settings = $database->select($sql, $parameters ?? null, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create('/core/themes/theme_edit.php');

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['label-settings']."</b><div class='count'>".$num_rows."</div></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('theme_setting_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','name'=>'btn_add','link'=>'theme_setting_edit.php?theme_uuid='.$theme_uuid]);
	}
	if (permission_exists('theme_setting_add') && $theme_settings) {
		echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'id'=>'btn_copy','name'=>'btn_copy','style'=>'display:none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
	}
	if (permission_exists('theme_setting_edit') && $theme_settings) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$_SESSION['theme']['button_icon_toggle'],'id'=>'btn_toggle','name'=>'btn_toggle','style'=>'display:none;','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	}
	if (permission_exists('theme_setting_delete') && $theme_settings) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display:none;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
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

	echo "<form id='form_list' method='post' action=''>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='theme_uuid' value='".escape($theme_uuid)."'>\n";

	echo "<div class='card'>\n";
	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('theme_setting_add') || permission_exists('theme_setting_edit') || permission_exists('theme_setting_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".empty($theme_settings ? "style='visibility: hidden;'" : null).">\n";
		echo "	</th>\n";
	}
	echo th_order_by('theme_setting_category', $text['label-theme_setting_category'], $order_by, $order);
	echo th_order_by('theme_setting_subcategory', $text['label-theme_setting_subcategory'], $order_by, $order);
	echo th_order_by('theme_setting_name', $text['label-theme_setting_type'], $order_by, $order);
	echo th_order_by('theme_setting_value', $text['label-theme_setting_value'], $order_by, $order);
	echo th_order_by('theme_setting_enabled', $text['label-theme_setting_enabled'], $order_by, $order, null, "class='center'");
	echo "	<th class='hide-sm-dn'>".$text['label-theme_setting_description']."</th>\n";
	if (permission_exists('theme_setting_edit') && $list_row_edit_button == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (!empty($theme_settings) && is_array($theme_settings) && @sizeof($theme_settings) != 0) {
		$x = 0;
		foreach ($theme_settings as $row) {
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
			echo "	<td>\n";
			if (permission_exists('theme_setting_edit')) {
				echo "	<a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['theme_setting_category'])."</a>\n";
			}
			else {
				echo "	".escape($row['theme_setting_category']);
			}
			echo "	</td>\n";
			echo "	<td>".escape($row['theme_setting_subcategory'])."</td>\n";
			echo "	<td>".escape($row['theme_setting_name'])."</td>\n";
			echo "	<td>\n";
			if (substr_count($row['theme_setting_subcategory'], "_color") > 0 && ($row['theme_setting_name'] == "text" || $row['theme_setting_name'] == 'array')) {
				echo "		".(img_spacer('15px', '15px', 'background: '.escape($row['theme_setting_value']).'; margin-right: 4px; vertical-align: middle; border: 1px solid '.(color_adjust($row['theme_setting_value'], -0.18)).'; padding: -1px;'));
				echo "<span style=\"font-family: 'Courier New'; line-height: 6pt;\">".escape($row['theme_setting_value'])."</span>\n";
			} else {
				echo escape($row['theme_setting_value']);
			}
			echo "	</td>\n";
			if (permission_exists('theme_setting_edit')) {
				echo "	<td class='no-link center'>\n";
				echo "		<input type='hidden' name='number_translations[$x][theme_setting_enabled]' value='".escape($row['theme_setting_enabled'])."' />\n";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.$row['theme_setting_enabled']],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
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
	echo "<div align='center'>".$paging_controls."</div>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";
