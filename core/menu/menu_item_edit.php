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
	Portions created by the Initial Developer are Copyright (C) 2008-2022
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files;
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('menu_add') || permission_exists('menu_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		return;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the menu_uuid
	$menu_uuid = $_REQUEST["id"];
	$menu_item_uuid = $_REQUEST['menu_item_uuid'];
	$group_uuid_name = $_REQUEST['group_uuid_name'];
	$menu_item_group_uuid = $_REQUEST['menu_item_group_uuid'];

//delete the group from the menu item
	if ($_REQUEST["a"] == "delete" && permission_exists("menu_delete") && is_uuid($menu_item_group_uuid)) {
		//delete the group from the users
			$array['menu_item_groups'][0]['menu_item_group_uuid'] = $menu_item_group_uuid;
			$database = new database;
			$database->app_name = 'menu';
			$database->app_uuid = 'f4b3b3d2-6287-489c-2a00-64529e46f2d7';
			$database->delete($array);
			unset($array);
		//redirect the browser
			message::add($text['message-delete']);
			header("Location: menu_item_edit.php?id=".urlencode($menu_uuid)."&menu_item_uuid=".urlencode($menu_item_uuid)."&menu_uuid=".urlencode($menu_uuid));
			return;
	}

//action add or update
	if (is_uuid($_REQUEST["menu_item_uuid"])) {
		$action = "update";
		$menu_item_uuid = $_REQUEST["menu_item_uuid"];
	}
	else {
		$action = "add";
	}

//get the HTTP POST variables and set them as PHP variables
	if (count($_POST) > 0) {
		$menu_uuid = $_POST["menu_uuid"];
		$menu_item_uuid = $_POST["menu_item_uuid"];
		$menu_item_title = $_POST["menu_item_title"];
		$menu_item_link = $_POST["menu_item_link"];
		$menu_item_category = $_POST["menu_item_category"];
		$menu_item_icon = $_POST["menu_item_icon"];
		$menu_item_description = $_POST["menu_item_description"];
		$menu_item_protected = $_POST["menu_item_protected"];
		//$menu_item_uuid = $_POST["menu_item_uuid"];
		$menu_item_parent_uuid = $_POST["menu_item_parent_uuid"];
		$menu_item_order = $_POST["menu_item_order"];
	}

//sanitize the menu link
	$menu_item_link = preg_replace('#[^a-zA-Z0-9_:\-\.\&\=\?\/]#', '', $menu_item_link);

//when a HTTP POST is available then process it
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		if ($action == "update") {
			$menu_item_uuid = $_POST["menu_item_uuid"];
		}

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: menu.php');
				exit;
			}

		//check for all required data
			$msg = '';
			if (strlen($menu_item_title) == 0) { $msg .= $text['message-required'].$text['label-title']."<br>\n"; }
			if (strlen($menu_item_category) == 0) { $msg .= $text['message-required'].$text['label-category']."<br>\n"; }
			//if (strlen($menu_item_link) == 0) { $msg .= $text['message-required'].$text['label-link']."<br>\n"; }
			if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
				require_once "resources/header.php";
				require_once "resources/persist_form_var.php";
				echo "<div align='center'>\n";
				echo "<table><tr><td>\n";
				echo $msg."<br />";
				echo "</td></tr></table>\n";
				persistformvar($_POST);
				echo "</div>\n";
				require_once "resources/footer.php";
				return;
			}

		//add or update the database
		if ($_POST["persistformvar"] != "true") {
			//get the language from the menu
				$sql = "select menu_language from v_menus ";
				$sql .= "where menu_uuid = :menu_uuid ";
				$parameters['menu_uuid'] = $menu_uuid;
				$database = new database;
				$menu_language = $database->select($sql, $parameters, 'column');
				unset($sql, $parameters);

			//get the highest menu item order
				if (!is_uuid($menu_item_parent_uuid)) {
					$sql = "select menu_item_order from v_menu_items ";
					$sql .= "where menu_uuid = :menu_uuid ";
					$sql .= "and menu_item_parent_uuid is null ";
					$sql .= "order by menu_item_order desc ";
					$sql .= "limit 1 ";
					$parameters['menu_uuid'] = $menu_uuid;
					$database = new database;
					$highest_menu_item_order = $database->select($sql, $parameters, 'column');
					unset($sql, $parameters);
				}

			//add a menu item
				if ($action == "add" && permission_exists('menu_add')) {
					$menu_item_uuid = uuid();
					$array['menu_items'][0]['menu_uuid'] = $menu_uuid;
					$array['menu_items'][0]['menu_item_title'] = $menu_item_title;
					$array['menu_items'][0]['menu_item_link'] = $menu_item_link;
					$array['menu_items'][0]['menu_item_category'] = $menu_item_category;
					$array['menu_items'][0]['menu_item_icon'] = $menu_item_icon;
					$array['menu_items'][0]['menu_item_description'] = $menu_item_description;
					$array['menu_items'][0]['menu_item_protected'] = $menu_item_protected;
					$array['menu_items'][0]['menu_item_uuid'] = $menu_item_uuid;
					if (!is_uuid($menu_item_parent_uuid)) {
						$array['menu_items'][0]['menu_item_parent_uuid'] = null;
						$array['menu_items'][0]['menu_item_order'] = ($highest_menu_item_order + 1);
					}
					else {
						$array['menu_items'][0]['menu_item_parent_uuid'] = $menu_item_parent_uuid;
					}
					$array['menu_items'][0]['menu_item_add_user'] = $_SESSION["username"];
					$array['menu_items'][0]['menu_item_add_date'] = 'now()';
					$database = new database;
					$database->app_name = 'menu';
					$database->app_uuid = 'f4b3b3d2-6287-489c-2a00-64529e46f2d7';
					$database->save($array);
					unset($array);
				}

			//update the menu item
				if ($action == "update" && permission_exists('menu_edit')) {
					$array['menu_items'][0]['menu_uuid'] = $menu_uuid;
					$array['menu_items'][0]['menu_item_title'] = $menu_item_title;
					$array['menu_items'][0]['menu_item_link'] = $menu_item_link;
					$array['menu_items'][0]['menu_item_category'] = $menu_item_category;
					$array['menu_items'][0]['menu_item_icon'] = $menu_item_icon;
					$array['menu_items'][0]['menu_item_description'] = $menu_item_description;
					$array['menu_items'][0]['menu_item_protected'] = $menu_item_protected;
					$array['menu_items'][0]['menu_item_uuid'] = $menu_item_uuid;
					if (!is_uuid($menu_item_parent_uuid)) {
						$array['menu_items'][0]['menu_item_parent_uuid'] = null;
						$array['menu_items'][0]['menu_item_order'] = is_numeric($menu_item_order) ? $menu_item_order : ($highest_menu_item_order + 1);
					}
					else {
						$array['menu_items'][0]['menu_item_parent_uuid'] = $menu_item_parent_uuid;
					}
					$array['menu_items'][0]['menu_item_add_user'] = $_SESSION["username"];
					$array['menu_items'][0]['menu_item_add_date'] = 'now()';
					$database = new database;
					$database->app_name = 'menu';
					$database->app_uuid = 'f4b3b3d2-6287-489c-2a00-64529e46f2d7';
					$database->save($array);
					unset($array);
				}

			//update child menu items to protected true or false
				$sql = "update v_menu_items ";
				$sql .= "set menu_item_protected = :menu_item_protected ";
				$sql .= "where menu_item_parent_uuid = :menu_item_parent_uuid ";
				$parameters['menu_item_parent_uuid'] = $menu_item_uuid;
				$parameters['menu_item_protected'] = $menu_item_protected;
				$database = new database;
				$database->execute($sql, $parameters);
				unset($parameters);

			//add a group to the menu
				if ($_REQUEST["a"] != "delete" && strlen($group_uuid_name) > 0 && permission_exists('menu_add')) {
					$group_data = explode('|', $group_uuid_name);
					$group_uuid = $group_data[0];
					$group_name = $group_data[1];
					//add the group to the menu
						if (is_uuid($menu_item_uuid)) {
							$menu_item_group_uuid = uuid();
							$array['menu_item_groups'][0]['menu_item_group_uuid'] = $menu_item_group_uuid;
							$array['menu_item_groups'][0]['menu_uuid'] = $menu_uuid;
							$array['menu_item_groups'][0]['menu_item_uuid'] = $menu_item_uuid;
							$array['menu_item_groups'][0]['group_name'] = $group_name;
							$array['menu_item_groups'][0]['group_uuid'] = $group_uuid;
							$database = new database;
							$database->app_name = 'menu';
							$database->app_uuid = 'f4b3b3d2-6287-489c-2a00-64529e46f2d7';
							$database->save($array);
							unset($array);
						}
				}

			//add the menu item label
				if ($_REQUEST["a"] != "delete" && strlen($menu_item_title) > 0 && permission_exists('menu_add')) {
					$sql = "select count(*) from v_menu_languages ";
					$sql .= "where menu_item_uuid = :menu_item_uuid ";
					$sql .= "and menu_language = :menu_language ";
					$parameters['menu_item_uuid'] = $menu_item_uuid;
					$parameters['menu_language'] = $menu_language;
					$database = new database;
					$num_rows = $database->select($sql, $parameters, 'column');
					if ($num_rows == 0) {
						$array['menu_languages'][0]['menu_language_uuid'] = uuid();
						$array['menu_languages'][0]['menu_uuid'] = $menu_uuid;
						$array['menu_languages'][0]['menu_item_uuid'] = $menu_item_uuid;
						$array['menu_languages'][0]['menu_language'] = $menu_language;
						$array['menu_languages'][0]['menu_item_title'] = $menu_item_title;
						$database = new database;
						$database->app_name = 'menu';
						$database->app_uuid = 'f4b3b3d2-6287-489c-2a00-64529e46f2d7';
						$database->save($array);
						unset($array);
					}
					else {
						$sql  = "update v_menu_languages set ";
						$sql .= "menu_item_title = :menu_item_title ";
						$sql .= "where menu_uuid = :menu_uuid ";
						$sql .= "and menu_item_uuid = :menu_item_uuid ";
						$sql .= "and menu_language = :menu_language ";
						$parameters['menu_item_title'] = $menu_item_title;
						$parameters['menu_uuid'] = $menu_uuid;
						$parameters['menu_item_uuid'] = $menu_item_uuid;
						$parameters['menu_language'] = $menu_language;
						$database = new database;
						$database->execute($sql, $parameters);
					}
					unset($sql, $parameters, $num_rows);
				}

			//set response message
				if ($action == "add") {
					message::add($text['message-add']);
				}
				if ($action == "update") {
					message::add($text['message-update']);
				}

			//redirect the user
				if ($_REQUEST['submit'] == $text['button-add']) {
					header("Location: menu_item_edit.php?id=".urlencode($menu_uuid)."&menu_item_uuid=".urlencode($menu_item_uuid)."&menu_uuid=".urlencode($menu_uuid));
				}
				else {
					header("Location: menu_edit.php?id=".urlencode($menu_uuid));
				}
				return;
		}
	}

//pre-populate the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
		$menu_item_uuid = $_GET["menu_item_uuid"];

		$sql = "select * from v_menu_items ";
		$sql .= "where menu_uuid = :menu_uuid ";
		$sql .= "and menu_item_uuid = :menu_item_uuid ";
		$parameters['menu_uuid'] = $menu_uuid;
		$parameters['menu_item_uuid'] = $menu_item_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && sizeof($row) != 0) {
			$menu_item_title = $row["menu_item_title"];
			$menu_item_link = $row["menu_item_link"];
			$menu_item_category = $row["menu_item_category"];
			$menu_item_icon = $row["menu_item_icon"];
			$menu_item_description = $row["menu_item_description"];
			$menu_item_protected = $row["menu_item_protected"];
			$menu_item_parent_uuid = $row["menu_item_parent_uuid"];
			$menu_item_order = $row["menu_item_order"];
			$menu_item_add_user = $row["menu_item_add_user"];
			$menu_item_add_date = $row["menu_item_add_date"];
			//$menu_item_del_user = $row["menu_item_del_user"];
			//$menu_item_del_date = $row["menu_item_del_date"];
			$menu_item_mod_user = $row["menu_item_mod_user"];
			$menu_item_mod_date = $row["menu_item_mod_date"];
		}
		unset($sql, $parameters, $row);
	}

//get the the menu items
	$sql = "select * from v_menu_items ";
	$sql .= "where menu_uuid = :menu_uuid ";
	$sql .= "order by menu_item_title asc ";
	$parameters['menu_uuid'] = $menu_uuid;
	$database = new database;
	$menu_items = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//get the assigned groups
	$sql = "select ";
	$sql .= "	mig.*, g.group_name, g.domain_uuid as group_domain_uuid ";
	$sql .= "from ";
	$sql .= "	v_menu_item_groups as mig, ";
	$sql .= "	v_groups as g ";
	$sql .= "where ";
	$sql .= "	mig.group_uuid = g.group_uuid ";
	$sql .= "	and mig.menu_uuid = :menu_uuid ";
	$sql .= "	and mig.menu_item_uuid = :menu_item_uuid ";
	$sql .= "order by ";
	$sql .= "	g.domain_uuid desc, ";
	$sql .= "	g.group_name asc ";
	$parameters['menu_uuid'] = $menu_uuid;
	$parameters['menu_item_uuid'] = $menu_item_uuid;
	$database = new database;
	$menu_item_groups = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//set the assigned_groups array
	if (is_array($menu_item_groups) && sizeof($menu_item_groups) != 0) {
		$assigned_groups = array();
		foreach ($menu_item_groups as $field) {
			if (strlen($field['group_name']) > 0) {
				if (is_uuid($field['group_uuid'])) {
					$assigned_groups[] = $field['group_uuid'];
				}
			}
		}
	}

//get the groups
	$sql = "select * from v_groups ";
	$sql .= "where (domain_uuid is null or domain_uuid = :domain_uuid) ";
	if (is_array($assigned_groups) && sizeof($assigned_groups) != 0) {
		$sql .= "and group_uuid not in ('".implode("','",$assigned_groups)."') ";
	}
	$sql .= "order by domain_uuid desc, group_name asc ";
	$database = new database;
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$groups = $database->select($sql, $parameters, 'all');
	unset($sql, $sql_where, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	$document['title'] = $text['title-menu_item'];
	require_once "resources/header.php";

	echo "<form name='frm' id='frm' method='post'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['header-menu_item']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>'menu_edit.php?id='.urlencode($menu_uuid)]);
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo "<table width='100%' cellpadding='0' cellspacing='0'>\n";

	echo "	<tr>";
	echo "		<td width='30%' class='vncellreq'>".$text['label-title']."</td>";
	echo "		<td width='70%' class='vtable'><input type='text' class='formfld' name='menu_item_title' value='".escape($menu_item_title)."'></td>";
	echo "	</tr>";

	echo "	<tr>";
	echo "		<td class='vncell'>".$text['label-link']."</td>";
	echo "		<td class='vtable'><input type='text' class='formfld' name='menu_item_link' value='".escape($menu_item_link)."'></td>";
	echo "	</tr>";

	echo "	<tr>";
	echo "		<td class='vncell'>".$text['label-category']."</td>";
	echo "		<td class='vtable'>";
	echo "            <select name=\"menu_item_category\" class='formfld'>\n";
	if ($menu_item_category == "internal") { echo "<option value=\"internal\" selected>".$text['option-internal']."</option>\n"; } else { echo "<option value=\"internal\">".$text['option-internal']."</option>\n"; }
	if ($menu_item_category == "external") { echo "<option value=\"external\" selected>".$text['option-external']."</option>\n"; } else { echo "<option value=\"external\">".$text['option-external']."</option>\n"; }
	if ($menu_item_category == "email") { echo "<option value=\"email\" selected>".$text['option-email']."</option>\n"; } else { echo "<option value=\"email\">".$text['option-email']."</option>\n"; }
	echo "            </select>";
	echo "        </td>";
	echo "	</tr>";

	echo "	<tr>";
	echo "		<td class='vncell'>".$text['label-icon']."</td>";
	echo "		<td class='vtable' style='vertical-align: bottom;'>";
	if (file_exists($_SERVER["PROJECT_ROOT"].'/resources/fontawesome/fas_icons.php')) {
		include 'resources/fontawesome/fas_icons.php';
		if (is_array($font_awesome_solid_icons) && @sizeof($font_awesome_solid_icons) != 0) {
			// rebuild and sort array
			foreach ($font_awesome_solid_icons as $i => $icon_class) {
				$icon_label = str_replace('fa-', '', $icon_class);
				$icon_label = str_replace('-', ' ', $icon_label);
				$icon_label = ucwords($icon_label);
				$icons[$icon_class] = $icon_label;
			}
			asort($icons, SORT_STRING);
			echo "<table cellpadding='0' cellspacing='0' border='0'>\n";
			echo "	<tr>\n";
			echo "		<td>\n";
			echo "			<select class='formfld' name='menu_item_icon' id='menu_item_icon' onchange=\"$('#icons').slideUp(); $('#grid_icon').fadeIn();\">\n";
			echo "				<option value=''></option>\n";
			foreach ($icons as $icon_class => $icon_label) {
				$selected = ($menu_item_icon == $icon_class) ? "selected" : null;
				echo "			<option value='".escape($icon_class)."' ".$selected.">".escape($icon_label)."</option>\n";
			}
			echo "			</select>\n";
			echo "		</td>\n";
			echo "		<td style='padding: 0 0 0 5px;'>\n";
			echo "			<button id='grid_icon' type='button' class='btn btn-default list_control_icon' style='font-size: 15px; padding-top: 1px; padding-left: 3px;' onclick=\"$('#icons').fadeIn(); $(this).fadeOut();\"><span class='fas fa-th'></span></button>";
			echo "		</td>\n";
			echo "	</tr>\n";
			echo "</table>\n";
			echo "<div id='icons' style='clear: both; display: none; margin-top: 8px; padding-top: 10px; color: #000; max-height: 400px; overflow: auto;'>\n";
			foreach ($icons as $icon_class => $icon_label) {
				echo "<span class='fas ".escape($icon_class)." fa-fw' style='font-size: 24px; float: left; margin: 0 8px 8px 0; cursor: pointer; opacity: 0.3;' title='".escape($icon_label)."' onclick=\"$('#menu_item_icon').val('".escape($icon_class)."'); $('#icons').slideUp(); $('#grid_icon').fadeIn();\" onmouseover=\"this.style.opacity='1';\" onmouseout=\"this.style.opacity='0.3';\"></span>\n";
			}
			echo "</div>";
		}
	}
	else {
		echo "		<input type='text' class='formfld' name='menu_item_icon' value='".escape($menu_item_icon)."'>";
	}
	echo "		</td>";
	echo "	</tr>";

	echo "	<tr>";
	echo "		<td class='vncell'>".$text['label-parent_menu']."</td>";
	echo "		<td class='vtable'>";
	echo "<select name=\"menu_item_parent_uuid\" class='formfld'>\n";
	echo "<option value=\"\"></option>\n";
	foreach($menu_items as $field) {
			if ($menu_item_parent_uuid == $field['menu_item_uuid']) {
				echo "<option value='".escape($field['menu_item_uuid'])."' selected>".escape($field['menu_item_title'])."</option>\n";
			}
			else {
				echo "<option value='".escape($field['menu_item_uuid'])."'>".escape($field['menu_item_title'])."</option>\n";
			}
	}
	echo "</select>";
	unset($sql, $result);
	echo "		</td>";
	echo "	</tr>";

	echo "	<tr>";
	echo "		<td class='vncell' valign='top'>".$text['label-groups']."</td>";
	echo "		<td class='vtable'>";
	if (is_array($menu_item_groups) && sizeof($menu_item_groups) != 0) {
		echo "<table cellpadding='0' cellspacing='0' border='0'>\n";
		foreach($menu_item_groups as $field) {
			if (strlen($field['group_name']) > 0) {
				echo "<tr>\n";
				echo "	<td class='vtable' style='white-space: nowrap; padding-right: 30px;' nowrap='nowrap'>";
				echo $field['group_name'].(($field['group_domain_uuid'] != '') ? "@".$_SESSION['domains'][$field['group_domain_uuid']]['domain_name'] : null);
				echo "	</td>\n";
				if (permission_exists('group_member_delete') || if_group("superadmin")) {
					echo "	<td class='list_control_icons' style='width: 25px;'>";
					echo 		"<a href='menu_item_edit.php?id=".escape($field['menu_uuid'])."&menu_item_group_uuid=".escape($field['menu_item_group_uuid'])."&menu_item_uuid=".escape($menu_item_uuid)."&a=delete' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">".$v_link_label_delete."</a>";
					echo "	</td>";
				}
				echo "</tr>\n";
			}
		}
		echo "</table>\n";
		echo "<br />\n";
	}
	if (is_array($groups)) {
		echo "<select name='group_uuid_name' class='formfld' style='width: auto; margin-right: 3px;'>\n";
		echo "	<option value=''></option>\n";
		foreach($groups as $row) {
			if ($field['group_level'] <= $_SESSION['user']['group_level']) {
				if (!is_array($assigned_groups) || !in_array($row["group_uuid"], $assigned_groups)) {
					echo "	<option value='".$row['group_uuid']."|".$row['group_name']."'>".$row['group_name'].(($row['domain_uuid'] != '') ? "@".$_SESSION['domains'][$row['domain_uuid']]['domain_name'] : null)."</option>\n";
				}
			}
		}
		echo "</select>";
		echo button::create(['type'=>'submit','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'collapse'=>'never']);
	}
	echo "		</td>";
	echo "	</tr>";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-protected']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='menu_item_protected'>\n";
	if ($menu_item_protected == "false") {
		echo "    <option value='false' selected='selected' >".$text['label-false']."</option>\n";
	}
	else {
		echo "    <option value='false'>".$text['label-false']."</option>\n";
	}
	if ($menu_item_protected == "true") {
		echo "    <option value='true' selected='selected' >".$text['label-true']."</option>\n";
	}
	else {
		echo "    <option value='true'>".$text['label-true']."</option>\n";
	}
	echo "    </select><br />\n";
	echo $text['description-protected']."<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	if ($action == "update") {
		if ($menu_item_parent_uuid == "") {
			echo "	<tr>";
			echo "		<td class='vncell'>".$text['label-menu_order']."</td>";
			echo "		<td class='vtable'><input type='text' class='formfld' name='menu_item_order' value='".escape($menu_item_order)."'></td>";
			echo "	</tr>";
		}
	}

	echo "	<tr>";
	echo "		<td class='vncell'>".$text['label-description']."</td>";
	echo "		<td class='vtable'><input type='text' class='formfld' name='menu_item_description' value='".escape($menu_item_description)."'></td>";
	echo "	</tr>";

	echo "</table>";
	echo "<br><br>";

	if (permission_exists('menu_add') || permission_exists('menu_edit')) {
		if ($action == "update") {
			echo "<input type='hidden' name='menu_item_uuid' value='".escape($menu_item_uuid)."'>";
		}
		echo "<input type='hidden' name='menu_uuid' value='".escape($menu_uuid)."'>";
		echo "<input type='hidden' name='menu_item_uuid' value='".escape($menu_item_uuid)."'>";
		echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	}

	echo "</form>";

//include the footer
  require_once "resources/footer.php";

?>
