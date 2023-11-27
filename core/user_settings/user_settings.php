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
 Portions created by the Initial Developer are Copyright (C) 2008-2020
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('user_setting_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//get the http post data
	if (!empty($_POST['action'])) {
		$action = $_POST['action'] ?? '';
		$user_uuid = $_POST['user_uuid'] ?? '';
		$user_settings = $_POST['user_settings'] ?? '';

		//process the http post data by action
			if (!empty($user_settings)) {
				switch ($action) {
					case 'toggle':
						if (permission_exists('user_setting_edit')) {
							$obj = new user_settings;
							$obj->user_uuid = $user_uuid;
							$obj->toggle($user_settings);
						}
						break;
					case 'delete':
						if (permission_exists('user_setting_delete')) {
							$obj = new user_settings;
							$obj->user_uuid = $user_uuid;
							$obj->delete($user_settings);
						}
						break;
				}
			}

		//redirect
			header('Location: '.PROJECT_PATH.'/core/users/user_edit.php?id='.urlencode($user_uuid));
			exit;
	}

/*
//toggle setting enabled
	if (
		is_uuid($_REQUEST["user_id"]) &&
		is_array($_REQUEST["id"]) &&
		!empty($_REQUEST["id"]) &&
		($_REQUEST['enabled'] === 'true' || $_REQUEST['enabled'] === 'false')
		) {

		//get input
			$user_setting_uuids = $_REQUEST["id"];
			$enabled = $_REQUEST['enabled'];

		//update setting
			$array['user_settings'][0]['user_setting_uuid'] = $user_setting_uuids[0];
			$array['user_settings'][0]['user_setting_enabled'] = $enabled;
			$database = new database;
			$database->app_name = 'user_settings';
			$database->app_uuid = '3a3337f7-78d1-23e3-0cfd-f14499b8ed97';
			$database->save($array);
			unset($array);

		//redirect
			message::add($text['message-update']);
			header("Location: /core/users/user_edit.php?id=".$_REQUEST["user_id"]);
			exit;
	}
*/

//get the variables
	$order_by = $_GET["order_by"] ?? '';
	$order = $_GET["order"] ?? '';

//set from session variables
	$list_row_edit_button = !empty($_SESSION['theme']['list_row_edit_button']['boolean']) ? $_SESSION['theme']['list_row_edit_button']['boolean'] : 'false';
	$button_icon_back = !empty($_SESSION['theme']['button_icon_back']) ? $_SESSION['theme']['button_icon_back'] : '';
	$button_icon_add = !empty($_SESSION['theme']['button_icon_add']) ? $_SESSION['theme']['button_icon_add'] : '';
	$button_icon_copy = !empty($_SESSION['theme']['button_icon_copy']) ? $_SESSION['theme']['button_icon_copy'] : '';
	$button_icon_toggle = !empty($_SESSION['theme']['button_icon_toggle']) ? $_SESSION['theme']['button_icon_toggle'] : '';
	$button_icon_all = !empty($_SESSION['theme']['button_icon_all']) ? $_SESSION['theme']['button_icon_all'] : '';
	$button_icon_delete = !empty($_SESSION['theme']['button_icon_delete']) ? $_SESSION['theme']['button_icon_delete'] : '';
	$button_icon_search = !empty($_SESSION['theme']['button_icon_search']) ? $_SESSION['theme']['button_icon_search'] : '';
	$button_icon_edit = !empty($_SESSION['theme']['button_icon_edit']) ? $_SESSION['theme']['button_icon_edit'] : '';
	$button_icon_reset = !empty($_SESSION['theme']['button_icon_reset']) ? $_SESSION['theme']['button_icon_reset'] : '';

//prepare to page the results
	$sql = "select count(*) from v_user_settings ";
	$sql .= "where user_uuid = :user_uuid ";
	$sql .= "and not ( ";
	$sql .= "(user_setting_category = 'domain' and user_setting_subcategory = 'language') ";
	$sql .= "or (user_setting_category = 'domain' and user_setting_subcategory = 'time_zone') ";
	$sql .= ") ";
	$parameters['user_uuid'] = $user_uuid;
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');
	unset($sql);

//prepare to page the results
	$rows_per_page = (!empty($_SESSION['domain']['paging']['numeric'])) ? $_SESSION['domain']['paging']['numeric'] : 100;
	$param = '';
	$paging_controls = '';
	if (isset($_GET['page'])) {
		$page = $_GET['page'];
		if (empty($page)) { $page = 0; $_GET['page'] = 0; }
		list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
		$offset = $rows_per_page * $page;
	}
	else {
		$offset = 0;
	}

//get the list
	$sql = "select user_setting_uuid, user_uuid, user_setting_category, user_setting_subcategory, user_setting_name, user_setting_value, cast(user_setting_enabled as text), user_setting_description ";
	$sql .= "from v_user_settings ";
	$sql .= "where user_uuid = :user_uuid ";
	$sql .= "and not ( ";
	$sql .= "(user_setting_category = 'domain' and user_setting_subcategory = 'language') ";
	$sql .= "or (user_setting_category = 'domain' and user_setting_subcategory = 'time_zone') ";
	$sql .= ") ";
	if (!empty($order_by)) {
		$sql .= "order by user_setting_category, user_setting_subcategory, user_setting_order asc ";
	}
	else {
		$sql .= order_by($order_by, $order);
	}
	$sql .= limit_offset($rows_per_page, $offset);
	$parameters['user_uuid'] = $user_uuid;
	$database = new database;
	$user_settings = $database->select($sql, $parameters, 'all');
	unset($sql, $sql_where, $parameters);

//create token
	$object = new token;
	$token = $object->create('/core/user_settings/user_settings.php');

//show the content
	echo "<div class='action_bar' id='action_bar_sub'>\n";
	echo "	<div class='heading'><b id='heading_sub'>".$text['header-user_settings']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','id'=>'action_bar_sub_button_back','label'=>$text['button-back'],'icon'=>$button_icon_back,'style'=>'margin-right: 15px; display: none;','link'=>'users.php']);
	if (permission_exists('user_setting_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$button_icon_add,'id'=>'btn_add','link'=>PROJECT_PATH.'/core/user_settings/user_setting_edit.php?user_uuid='.urlencode($_GET['id'])]);
	}
	if (permission_exists('user_setting_edit') && $user_settings) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$button_icon_toggle,'name'=>'btn_toggle','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	}
	if (permission_exists('user_setting_delete') && $user_settings) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$button_icon_delete,'name'=>'btn_delete','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('user_setting_edit') && $user_settings) {
		echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('user_setting_delete') && $user_settings) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['header_description-user_settings']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post' action='/core/user_settings/user_settings.php'>\n";
	echo "<input type='hidden' name='action' id='action' value=''>\n";
	echo "<input type='hidden' name='user_uuid' value='".$user_uuid."'>\n";

	echo "<table class='list'>\n";
	if (!empty($user_settings)) {
		$previous_user_setting_category = '';
		$x = 0;
		foreach ($user_settings as $row) {
			$user_setting_category = strtolower($row['user_setting_category']);

			$label_user_setting_category = $row['user_setting_category'];
			switch (strtolower($label_user_setting_category)) {
				case "api" : $label_user_setting_category = "API"; break;
				case "cdr" : $label_user_setting_category = "CDR"; break;
				case "ldap" : $label_user_setting_category = "LDAP"; break;
				case "ivr_menu" : $label_user_setting_category = "IVR Menu"; break;
				default:
					$label_user_setting_category = str_replace("_", " ", $label_user_setting_category);
					$label_user_setting_category = str_replace("-", " ", $label_user_setting_category);
					$label_user_setting_category = ucwords($label_user_setting_category);
			}

			if ($previous_user_setting_category != $row['user_setting_category']) {
				if ($previous_user_setting_category != '') {
					echo "</table>\n";

					echo "<br>\n";
				}
				echo "<b>".escape($label_user_setting_category)."</b><br>\n";

				echo "<table class='list'>\n";
				echo "<tr class='list-header'>\n";
				if (permission_exists('user_setting_add') || permission_exists('user_setting_edit') || permission_exists('user_setting_delete')) {
					echo "	<th class='checkbox'>\n";
					echo "		<input type='checkbox' id='checkbox_all_".$user_setting_category."' name='checkbox_all' onclick=\"list_all_toggle('".$user_setting_category."');\">\n";
					echo "	</th>\n";
				}
				echo "<th class='pct-35'>".$text['label-subcategory']."</th>";
				echo "<th class='pct-10 hide-sm-dn'>".$text['label-type']."</th>";
				echo "<th class='pct-30'>".$text['label-value']."</th>";
				echo "<th class='center'>".$text['label-enabled']."</th>";
				echo "<th class='pct-25 hide-sm-dn'>".$text['label-description']."</th>";
				if (permission_exists('user_setting_edit') && $list_row_edit_button == 'true') {
					echo "	<td class='action-button'>&nbsp;</td>\n";
				}
				echo "</tr>\n";
			}
			if (permission_exists('user_setting_edit')) {
				$list_row_url = PROJECT_PATH."/core/user_settings/user_setting_edit.php?user_uuid=".$row['user_uuid']."&id=".$row['user_setting_uuid'];
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('user_setting_add') || permission_exists('user_setting_edit') || permission_exists('user_setting_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='user_settings[$x][checked]' id='checkbox_".$x."' class='checkbox_".$user_setting_category."' value='true' onclick=\"if (!this.checked) { document.getElementById('checkbox_all_".$user_setting_category."').checked = false; }\">\n";
				echo "		<input type='hidden' name='user_settings[$x][uuid]' value='".escape($row['user_setting_uuid'])."' />\n";
				echo "	</td>\n";
			}
			echo "	<td class='overflow no-wrap'>";
			if (permission_exists('user_setting_edit')) {
				echo "	<a href='".$list_row_url."'>".escape($row['user_setting_subcategory'])."</a>";
			}
			else {
				echo escape($row['user_setting_subcategory']);
			}
			echo "	</td>\n";
			$setting_types = ['Array','Boolean','Code','Dir','Name','Numeric','Text','UUID'];
			echo "	<td class='hide-sm-dn'>".$setting_types[array_search(strtolower($row['user_setting_name']), array_map('strtolower',$setting_types))]."</td>\n";
			echo "	<td class='overflow no-wrap'>\n";
			$category = $row['user_setting_category'];
			$subcategory = $row['user_setting_subcategory'];
			$name = $row['user_setting_name'];
			if ($category == "domain" && $subcategory == "menu" && $name == "uuid" ) {
				$sql = "select * from v_menus ";
				$sql .= "where menu_uuid = :menu_uuid ";
				$parameters['menu_uuid'] = $row['user_setting_value'];
				$database = new database;
				$sub_result = $database->select($sql, $parameters, 'all');
				if (!empty($sub_result)) {
					foreach ($sub_result as &$sub_row) {
						echo escape($sub_row["menu_language"])." - ".escape($sub_row["menu_name"])."\n";
					}
				}
				unset($sql, $parameters, $sub_result, $sub_row);
			}
			else if ($category == "domain" && $subcategory == "template" && $name == "name" ) {
				echo "		".ucwords($row['user_setting_value']);
			}
			else if ($category == "domain" && $subcategory == "time_format" && $name == "text" ) {
				switch ($row['user_setting_value']) {
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
				echo "		".$text['label-'.escape($row['user_setting_value'])];
			}
			else if ($subcategory == 'password' || substr_count($subcategory, '_password') > 0 || $category == "login" && $subcategory == "password_reset_key" && $name == "text") {
				echo "		".str_repeat('*', strlen(escape($row['user_setting_value'])));
			}
			else if ($category == 'theme' && $subcategory == 'button_icons' && $name == 'text') {
				echo "		".$text['option-button_icons_'.$row['user_setting_value']]."\n";
			}
			else if ($category == 'theme' && $subcategory == 'menu_side_state' && $name == 'text') {
				echo "		".$text['option-'.$row['user_setting_value']]."\n";
			}
			else if ($category == 'theme' && $subcategory == 'menu_side_toggle' && $name == 'text') {
				echo "		".$text['option-'.$row['user_setting_value']]."\n";
			}
			else if ($category == 'theme' && $subcategory == 'menu_side_toggle_body_width' && $name == 'text') {
				echo "		".$text['option-'.$row['user_setting_value']]."\n";
			}
			else if ($category == 'theme' && $subcategory == 'input_toggle_style' && $name == 'text') {
				echo "		".$text['option-'.$row['user_setting_value']]."\n";
			}
			else if ($category == "theme" && substr_count($subcategory, "_color") > 0 && ($name == "text" || $name == 'array')) {
				echo "		".(img_spacer('15px', '15px', 'background: '.escape($row['user_setting_value']).'; margin-right: 4px; vertical-align: middle; border: 1px solid '.(color_adjust($row['user_setting_value'], -0.18)).'; padding: -1px;'));
				echo "<span style=\"font-family: 'Courier New'; line-height: 6pt;\">".escape($row['user_setting_value'])."</span>\n";
			}
			else if ($category == 'users' && $subcategory == 'username_format' && $name == 'text') {
				echo "		".$text['option-username_format_'.$row['user_setting_value']]."\n";
			}
			else if ($category == 'recordings' && $subcategory == 'storage_type' && $name == 'text') {
				echo "		".$text['label-'.$row['user_setting_value']]."\n";
			}
			else if ($category == 'destinations' && $subcategory == 'dialplan_mode' && $name == 'text') {
				echo "		".$text['label-'.$row['user_setting_value']]."\n";
			}
			else if ($category == 'destinations' && $subcategory == 'select_mode' && $name == 'text') {
				echo "		".$text['label-'.$row['user_setting_value']]."\n";
			}
			else if ($row['user_setting_value'] == 'true' || $row['user_setting_value'] == 'false') {
				echo "		".$text['label-'.$row['user_setting_value']]."\n";
			}
			else {
				if (!empty($row['user_setting_value']) && substr_count($row['user_setting_value'], "\n") > 0) {
					$lines = explode("\n", $row['user_setting_value']);
					if (!empty($lines) && is_array($lines) && @sizeof($lines) != 0) {
						foreach ($lines as $i => $line) {
							$lines[$i] = escape($line);
						}
						echo implode("<i class='fas fa-level-down-alt fa-rotate-90 fa-xs ml-2 mr-5' style='opacity: 0.3;'></i>", $lines);
					}
					unset($lines, $line);
				}
				else {
					echo escape($row['user_setting_value'])."\n";
				}
			}
			echo "	</td>\n";
			if (permission_exists('user_setting_edit')) {
				echo "	<td class='no-link center'>\n";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.$row['user_setting_enabled']],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
			}
			else {
				echo "	<td class='center'>\n";
				echo $text['label-'.$row['user_setting_enabled']];
			}
			echo "	</td>\n";
			echo "	<td class='description overflow hide-sm-dn' title=\"".escape($row['user_setting_description'])."\">".escape($row['user_setting_description'])."&nbsp;</td>\n";
			if (permission_exists('user_setting_edit') && $list_row_edit_button == 'true') {
				echo "	<td class='action-button'>\n";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$button_icon_edit,'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";

			//set the previous category
			$previous_user_setting_category = $row['user_setting_category'];
			$x++;
		}
	}
	unset($user_settings);

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//make sub action bar sticky
	echo "<script>\n";

	echo "	window.addEventListener('scroll', function(){\n";
	echo "		action_bar_scroll('action_bar_sub', 820, heading_modify, heading_restore);\n";
	echo "	}, false);\n";

	echo "	function heading_modify() {\n";
	echo "		document.getElementById('action_bar_sub_button_back').style.display = 'inline-block';\n";
	echo "	}\n";

	echo "	function heading_restore() {\n";
	echo "		document.getElementById('action_bar_sub_button_back').style.display = 'none';\n";
	echo "	}\n";

	echo "</script>\n";

?>
