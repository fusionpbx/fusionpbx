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
	Portions created by the Initial Developer are Copyright (C) 2008 - 2022
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

//check permissions
	if (permission_exists('default_setting_view')) {
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
	$search = $_REQUEST['search'];
	$default_setting_category = $_REQUEST['default_setting_category'];
	if (is_array($_POST['default_settings'])) {
		$action = $_POST['action'];
		$domain_uuid = $_POST['domain_uuid'];
		$default_settings = $_POST['default_settings'];
	}

//sanitize the variables
	$action = preg_replace('#[^a-zA-Z0-9_\-\.]#', '', $action);
	$search = preg_replace('#[^a-zA-Z0-9_\-\. ]#', '', $search);
	$default_setting_category = preg_replace('#[^a-zA-Z0-9_\-\.]#', '', $default_setting_category);

//build the query string
	$query_string = '';
	if ($search != '') {
		$query_string .= 'search='.urlencode($search);
	}
	if ($default_setting_category != '') {
		if ($query_string == '') { $query_string = ''; } else { $query_string .= '&'; }
		$query_string .= 'default_setting_category='.urlencode($default_setting_category);
	}

//process the http post data by action
	if ($action != '' && is_array($default_settings) && @sizeof($default_settings) != 0) {
		switch ($action) {
			case 'copy':
				if (permission_exists('default_setting_add')) {
					$obj = new default_settings;
					$obj->domain_uuid = $domain_uuid;
					$obj->copy($default_settings);
				}
				break;
			case 'toggle':
				if (permission_exists('default_setting_edit')) {
					$obj = new default_settings;
					$obj->toggle($default_settings);
				}
				break;
			case 'delete':
				if (permission_exists('default_setting_delete')) {
					$obj = new default_settings;
					$obj->delete($default_settings);
				}
				break;
		}
		header('Location: default_settings.php?'.($query_string != '' ? $query_string : null));
		exit;
	}

//get order and order by
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//get the count
	$sql = "select count(default_setting_uuid) from v_default_settings ";
	if (isset($search) && strlen($search) > 0) {
		$sql .= "where (";
		$sql .= "	lower(default_setting_category) like :search ";
		$sql .= "	or lower(default_setting_subcategory) like :search ";
		$sql .= "	or lower(default_setting_name) like :search ";
		$sql .= "	or lower(default_setting_value) like :search ";
		$sql .= "	or lower(default_setting_description) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	if (isset($default_setting_category) && strlen($default_setting_category) > 0) {
		$sql .= (stripos($sql,'WHERE') === false) ? 'where ' : 'and ';
		$sql .= "lower(default_setting_category) = :default_setting_category ";
		$parameters['default_setting_category'] = strtolower($default_setting_category);
	}
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//get the list
	$sql = "select default_setting_uuid, default_setting_category, default_setting_subcategory, default_setting_name, ";
	$sql .= "default_setting_value, cast(default_setting_enabled as text), default_setting_description ";
	$sql .= "from v_default_settings ";
	if (isset($search) && strlen($search) > 0) {
		$sql .= "where (";
		$sql .= "	lower(default_setting_category) like :search ";
		$sql .= "	or lower(default_setting_subcategory) like :search ";
		$sql .= "	or lower(default_setting_name) like :search ";
		$sql .= "	or lower(default_setting_value) like :search ";
		$sql .= "	or lower(default_setting_description) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	if (isset($default_setting_category) && strlen($default_setting_category) > 0) {
		$sql .= (stripos($sql,'WHERE') === false) ? 'where ' : 'and ';
		$sql .= "lower(default_setting_category) = :default_setting_category ";
		$parameters['default_setting_category'] = strtolower($default_setting_category);
	}
	$sql .= order_by($order_by, $order, 'default_setting_category, default_setting_subcategory, default_setting_order', 'asc');
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$default_settings = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//get default setting categories
	$sql = "select ";
	$sql .= "distinct(d1.default_setting_category), ";
	$sql .= "( ";
	$sql .= "	select ";
	$sql .= "	count(d2.default_setting_category) ";
	$sql .= "	from v_default_settings as d2 ";
	$sql .= "	where d2.default_setting_category = d1.default_setting_category ";
	if (isset($search) && strlen($search) > 0) {
		$sql .= "	and (";
		$sql .= "		lower(d2.default_setting_category) like :search ";
		$sql .= "		or lower(d2.default_setting_subcategory) like :search ";
		$sql .= "		or lower(d2.default_setting_name) like :search ";
		$sql .= "		or lower(d2.default_setting_value) like :search ";
		$sql .= "		or lower(d2.default_setting_description) like :search ";
		$sql .= "	) ";
		$parameters['search'] = '%'.$search.'%';
	}
	$sql .= ") as quantity ";
	$sql .= "from v_default_settings as d1 ";
	$sql .= "order by d1.default_setting_category asc ";
	$database = new database;
	$rows = $database->select($sql, $parameters, 'all');
	if (is_array($rows) && @sizeof($rows) != 0) {
		foreach ($rows as $row) {
			$default_setting_categories[$row['default_setting_category']] += $row['quantity'];
		}
	}
	unset($sql, $rows, $row);

//get the list of categories
	if (is_array($default_setting_categories) && @sizeof($default_setting_categories) != 0) {
		foreach ($default_setting_categories as $default_setting_category => $quantity) {
			$category = strtolower($default_setting_category);
			switch ($category) {
				case "api" : $category = "API"; break;
				case "cdr" : $category = "CDR"; break;
				case "ldap" : $category = "LDAP"; break;
				case "ivr_menu" : $category = "IVR Menu"; break;
				default:
					$category = str_replace("_", " ", $category);
					$category = str_replace("-", " ", $category);
					$category = ucwords($category);
			}
			$categories[$default_setting_category]['formatted'] = $category;
			$categories[$default_setting_category]['count'] = $quantity;
		}
		ksort($categories);
		unset($default_setting_categories, $default_setting_category, $category);
	}

//get the list of installed apps from the core and mod directories
	$config_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/*/*/app_config.php");
	$x=0;
	foreach ($config_list as $config_path) {
		include($config_path);
		$x++;
	}
	$x = 0;
	foreach ($apps as $app) {
		if (is_array($app['default_settings'])) {
			foreach ($app['default_settings'] as $setting) {
				$setting_array[$x] = ($setting);
				$setting_array[$x]['app_uuid'] = $app['uuid'];
				$x++;
			}
		}
	}

//create a function to find matching row in array and return the row or boolean
	function find_in_array($search_array, $field, $value, $type = 'boolean') {
		foreach($search_array as $row) {
			if ($row[$field] == $value) {
				if ($type == 'boolean') {
					return true;
				}
				if ($type == 'row') {
					return $row;
				}
				break;
			}
		}
		if ($type == 'boolean') {
			return false;
		}
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	$document['title'] = $text['title-default_settings'];
	require_once "resources/header.php";

//copy settings javascript
	if (permission_exists("domain_select") && permission_exists("domain_setting_add") && count($_SESSION['domains']) > 1) {
		echo "<script language='javascript' type='text/javascript'>\n";
		echo "	function show_domains() {\n";
		echo "		document.getElementById('action').value = 'copy';\n";
		echo "		document.getElementById('btn_copy').style.display = 'none'; \n";
		echo "		document.getElementById('btn_copy_cancel').style.display = 'inline'; \n";
		echo "		document.getElementById('target_domain_uuid').style.display = 'inline'; \n";
		echo "		document.getElementById('btn_paste').style.display = 'inline'; \n";
		echo "	}";
		echo "	function hide_domains() {\n";
		echo "		document.getElementById('action').value = '';\n";
		echo "		document.getElementById('btn_copy_cancel').style.display = 'none'; \n";
		echo "		document.getElementById('target_domain_uuid').style.display = 'none'; \n";
		echo "		document.getElementById('target_domain_uuid').selectedIndex = 0;\n";
		echo "		document.getElementById('btn_paste').style.display = 'none'; \n";
		echo "		document.getElementById('btn_copy').style.display = 'inline'; \n";
		echo "	}\n";
		echo "</script>";
	}

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-default_settings']." (".number_format($num_rows).")</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['label-domain'],'icon'=>$_SESSION['theme']['button_icon_all'],'style'=>'','link'=>PROJECT_PATH.'/core/domain_settings/domain_settings.php?id='.$domain_uuid]);
	echo button::create(['label'=>$text['button-reload'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'button_reload','link'=>'default_settings_reload.php'.($search != '' ? '?search='.urlencode($search) : null),'style'=>'margin-right: 15px;']);
	if (permission_exists('default_setting_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','link'=>'default_setting_edit.php?'.$query_string]);
	}
	if (permission_exists('default_setting_add') && $default_settings) {
		if (permission_exists("domain_select") && permission_exists("domain_setting_add") && count($_SESSION['domains']) > 1) {
			echo button::create(['type'=>'button','label'=>$text['button-copy'],'id'=>'btn_copy','name'=>'btn_copy','style'=>'display: none;','icon'=>$_SESSION['theme']['button_icon_copy'],'id'=>'btn_copy','onclick'=>'show_domains();']);
			echo button::create(['type'=>'button','label'=>$text['button-cancel'],'id'=>'btn_copy_cancel','icon'=>$_SESSION['theme']['button_icon_cancel'],'style'=>'display: none;','onclick'=>'hide_domains();']);
			echo 		"<select name='domain_uuid' class='formfld' style='display: none; width: auto;' id='target_domain_uuid' onchange=\"document.getElementById('domain_uuid').value = this.options[this.selectedIndex].value;\">\n";
			echo "			<option value=''>".$text['label-domain']."...</option>\n";
			foreach ($_SESSION['domains'] as $domain) {
				echo "		<option value='".escape($domain["domain_uuid"])."'>".escape($domain["domain_name"])."</option>\n";
			}
			echo "		</select>";
			echo button::create(['type'=>'button','label'=>$text['button-paste'],'icon'=>$_SESSION['theme']['button_icon_paste'],'id'=>'btn_paste','style'=>'display: none;','onclick'=>"if (confirm('".$text['confirm-copy']."')) { list_action_set('copy'); list_form_submit('form_list'); } else { this.blur(); return false; }"]);
		}
	}
	if (permission_exists('default_setting_edit') && $default_settings) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$_SESSION['theme']['button_icon_toggle'],'id'=>'btn_toggle','name'=>'btn_toggle','style'=>'display: none;','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	}
	if (permission_exists('default_setting_delete') && $default_settings) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	if (is_array($categories) && @sizeof($categories) != 0) {
		echo 		"<select name='default_setting_category' class='formfld' style='width: auto; margin-left: 15px;' id='select_category' onchange='this.form.submit();'>\n";
		echo "			<option value=''>".$text['label-category']."...</option>\n";
		echo "			<option value=''>".$text['label-all']."</option>\n";
		foreach ($categories as $category_name => $category) {
			$selected = ($_GET['default_setting_category'] == $category_name) ? " selected='selected'" : "";
			echo "		<option value='".escape($category_name)."' $selected>".escape($category['formatted']).($category['count'] ? " (".$category['count'].")" : null)."</option>\n";
		}
		echo "			<option disabled='disabled'>\n";
		echo "			<option value=''>".$text['label-all']."</option>\n";
		echo "		</select>";
	}
	echo 		"<input type='text' class='txt list-search' name='search' id='search' style='margin-left: 0 !important;' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown=''>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search']);
	//echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'default_settings.php','style'=>($search == '' ? 'display: none;' : null)]);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('default_setting_edit') && $default_settings) {
		echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('default_setting_delete') && $default_settings) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['description-default_settings']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";
	echo "<input type='hidden' name='domain_uuid' id='domain_uuid'>";

	if (is_array($default_settings) && @sizeof($default_settings) != 0) {
		$x = 0;
		foreach ($default_settings as $row) {
			$default_setting_category = strtolower($row['default_setting_category']);
			$default_setting_category = preg_replace('#[^a-zA-Z0-9_\-\.]#', '', $default_setting_category);

			$label_default_setting_category = $row['default_setting_category'];
			$label_default_setting_category = preg_replace('#[^a-zA-Z0-9_\-\. ]#', '', $label_default_setting_category);

			//check if the default setting uuid exists in the array
			$field = find_in_array($setting_array, 'default_setting_uuid',  $row['default_setting_uuid'], 'row');
			unset($setting_bold, $enabled_bold, $default_value, $default_enabled);
			if (is_array($field)) {
				if ($row['default_setting_value'] !== $field['default_setting_value']) {
					$setting_bold = 'font-weight:bold;';
				}
				if (strlen($field['default_setting_value']) > 0) {
					$default_value = 'Default: '.$field['default_setting_value'];
				}
				else {
					$default_value = 'Default: null';
				}
				if ($row['default_setting_enabled'] != $field['default_setting_enabled']) {
					$default_enabled = $field['default_setting_enabled'];
					$enabled_bold = true;
				}
			}
			else {
				$default_value = 'Custom';
				$setting_bold = 'font-weight:bold;';
			}
			unset($field);

			switch (strtolower($label_default_setting_category)) {
				case "api" : $label_default_setting_category = "API"; break;
				case "cdr" : $label_default_setting_category = "CDR"; break;
				case "ldap" : $label_default_setting_category = "LDAP"; break;
				case "ivr_menu" : $label_default_setting_category = "IVR Menu"; break;
				default:
					$label_default_setting_category = str_replace("_", " ", $label_default_setting_category);
					$label_default_setting_category = str_replace("-", " ", $label_default_setting_category);
					$label_default_setting_category = ucwords($label_default_setting_category);
			}

			if ($previous_default_setting_category != $row['default_setting_category']) {
				if ($previous_default_setting_category != '') {
					echo "</table>\n";
					echo "<br />\n";
					echo "</div>\n";
				}
				echo "<div class='category' id='category_".$default_setting_category."'>\n";
				echo "<b>".escape($label_default_setting_category)."</b><br>\n";

				echo "<table class='list'>\n";
				echo "<tr class='list-header'>\n";
				if (permission_exists('default_setting_add') || permission_exists('default_setting_edit') || permission_exists('default_setting_delete')) {
					echo "	<th class='checkbox'>\n";
					echo "		<input type='checkbox' id='checkbox_all_".$default_setting_category."' name='checkbox_all' onclick=\"list_all_toggle('".$default_setting_category."'); checkbox_on_change(this);\">\n";
					echo "	</th>\n";
				}
				if ($_GET['show'] == 'all' && permission_exists('default_setting_all')) {
					echo th_order_by('domain_name', $text['label-domain'], $order_by, $order);
				}
				echo th_order_by('default_setting_subcategory', $text['label-subcategory'], $order_by, $order, null, "class='pct-35'");
				echo th_order_by('default_setting_name', $text['label-type'], $order_by, $order, null, "class='pct-10 hide-sm-dn'");
				echo th_order_by('default_setting_value', $text['label-value'], $order_by, $order, null, "class='pct-30'");
				echo th_order_by('default_setting_enabled', $text['label-enabled'], $order_by, $order, null, "class='center'");
				echo "	<th class='pct-25 hide-sm-dn'>".$text['label-description']."</th>\n";
				if (permission_exists('default_setting_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
					echo "	<td class='action-button'>&nbsp;</td>\n";
				}
				echo "</tr>\n";
			}
			if (permission_exists('default_setting_edit')) {
				$list_row_url = "default_setting_edit.php?id=".urlencode($row['default_setting_uuid']).'&'.$query_string;
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('default_setting_add') || permission_exists('default_setting_edit') || permission_exists('default_setting_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='default_settings[$x][checked]' id='checkbox_".$x."' class='checkbox_".$default_setting_category."' value='true' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all_".$default_setting_category."').checked = false; }\">\n";
				echo "		<input type='hidden' name='default_settings[$x][uuid]' value='".escape($row['default_setting_uuid'])."' />\n";
				echo "	</td>\n";
			}
			if ($_GET['show'] == 'all' && permission_exists('default_setting_all')) {
				echo "	<td>".escape($_SESSION['domains'][$row['domain_uuid']]['domain_name'])."</td>\n";
			}
			echo "	<td class='overflow no-wrap'>";
			if (permission_exists('default_setting_edit')) {
				echo "<a href='".$list_row_url."'>".escape($row['default_setting_subcategory'])."</a>";
			}
			else {
				echo escape($row['default_setting_subcategory']);
			}
			echo "	</td>\n";
			echo "	<td class='hide-sm-dn'>".escape($row['default_setting_name'])."</td>\n";
			echo "	<td class='overflow no-wrap' title=\"".escape($default_value)."\" style=\"".$setting_bold."\">\n";

			$category = $row['default_setting_category'];
			$subcategory = $row['default_setting_subcategory'];
			$name = $row['default_setting_name'];
			if ($category == "domain" && $subcategory == "menu" && $name == "uuid" ) {
				$sql = "select * from v_menus ";
				$sql .= "where menu_uuid = :menu_uuid ";
				$parameters['menu_uuid'] = $row['default_setting_value'];
				$database = new database;
				$sub_result = $database->select($sql, $parameters, 'all');
				foreach ($sub_result as &$sub_row) {
					echo $sub_row["menu_language"]." - ".$sub_row["menu_name"]."\n";
				}
				unset($sql, $sub_result, $sub_row);
			}
			else if ($category == "domain" && $subcategory == "template" && $name == "name" ) {
				echo "		".ucwords($row['default_setting_value']);
			}
			else if ($category == "domain" && $subcategory == "time_format" && $name == "text" ) {
				switch ($row['default_setting_value']) {
					case '12h': echo $text['label-12-hour']; break;
					case '24h': echo $text['label-24-hour']; break;
				}
			}
			else if (
				( $category == "theme" && $subcategory == "menu_main_icons" && $name == "boolean" ) ||
				( $category == "theme" && $subcategory == "menu_sub_icons" && $name == "boolean" ) ||
				( $category == "theme" && $subcategory == "menu_brand_type" && $name == "text" ) ||
				( $category == "theme" && $subcategory == "menu_style" && $name == "text" ) ||
				( $category == "theme" && $subcategory == "menu_position" && $name == "text" ) ||
				( $category == "theme" && $subcategory == "body_header_brand_type" && $name == "text" ) ||
				( $category == "theme" && $subcategory == "logo_align" && $name == "text" )
				) {
				echo "		".$text['label-'.$row['default_setting_value']];
			}
			else if ($category == 'theme' && $subcategory == 'custom_css_code' && $name == 'text') {
				echo "		[...]\n";
			}
			else if ($subcategory == 'password' || substr_count($subcategory, '_password') > 0 || $category == "login" && $subcategory == "password_reset_key" && $name == "text" || substr_count($subcategory, '_secret') > 0) {
				echo "		".str_repeat('*', strlen($row['default_setting_value']));
			}
			else if ($category == 'theme' && $subcategory == 'button_icons' && $name == 'text') {
				echo "		".$text['option-button_icons_'.$row['default_setting_value']]."\n";
			}
			else if ($category == 'theme' && $subcategory == 'menu_side_state' && $name == 'text') {
				echo "		".$text['option-'.$row['default_setting_value']]."\n";
			}
			else if ($category == 'theme' && $subcategory == 'menu_side_toggle' && $name == 'text') {
				echo "		".$text['option-'.$row['default_setting_value']]."\n";
			}
			else if ($category == 'theme' && $subcategory == 'menu_side_toggle_body_width' && $name == 'text') {
				echo "		".$text['option-'.$row['default_setting_value']]."\n";
			}
			else if ($category == 'theme' && $subcategory == 'menu_side_item_main_sub_close' && $name == 'text') {
				echo "		".$text['option-'.$row['default_setting_value']]."\n";
			}
			else if (substr_count($subcategory, "_color") > 0 && ($name == "text" || $name == 'array')) {
				echo "		".(img_spacer('15px', '15px', 'background: '.escape($row['default_setting_value']).'; margin-right: 4px; vertical-align: middle; border: 1px solid '.(color_adjust($row['default_setting_value'], -0.18)).'; padding: -1px;'));
				echo "<span style=\"font-family: 'Courier New'; line-height: 6pt;\">".escape($row['default_setting_value'])."</span>\n";
			}
			else if ($category == 'users' && $subcategory == 'username_format' && $name == 'text') {
				echo "		".$text['option-username_format_'.$row['default_setting_value']]."\n";
			}
			else if ($category == 'recordings' && $subcategory == 'storage_type' && $name == 'text') {
				echo "		".$text['label-'.$row['default_setting_value']]."\n";
			}
			else if ($category == 'destinations' && $subcategory == 'dialplan_mode' && $name == 'text') {
				echo "		".$text['label-'.$row['default_setting_value']]."\n";
			}
			else if ($category == 'destinations' && $subcategory == 'select_mode' && $name == 'text') {
				echo "		".$text['label-'.$row['default_setting_value']]."\n";
			}
			else {
				echo "		".escape($row['default_setting_value'])."\n";
			}
			echo "	</td>\n";
			if (permission_exists('default_setting_edit')) {
				echo "	<td class='no-link center'>\n";
				if (isset($enabled_bold)) {
					echo button::create(['type'=>'submit','class'=>'link','style'=>'font-weight:bold', 'label'=>$text['label-'.$row['default_setting_enabled']],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
				}
				else {
					echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.$row['default_setting_enabled']],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
				}
			}
			else {
				echo "	<td class='center' title=\"".escape($default_enabled)."\" style=\"".$setting_bold."\">\n";
				echo $text['label-'.$row['default_setting_enabled']];
			}
			echo "	</td>\n";
			echo "	<td class='description overflow hide-sm-dn' title=\"".escape($row['default_setting_description'])."\">".escape($row['default_setting_description'])."</td>\n";
			if (permission_exists('default_setting_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>\n";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";

			//set the previous category
			$previous_default_setting_category = $row['default_setting_category'];
			$x++;
		}
		unset($default_settings);
	}

	echo "</table>\n";
	echo "<br />\n";
	echo "</div>\n";

	echo "<div align='center'>".$paging_controls."</div>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>
