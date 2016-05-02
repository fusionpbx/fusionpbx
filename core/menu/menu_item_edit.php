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
	Portions created by the Initial Developer are Copyright (C) 2008-2015
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
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
	$menu_uuid = check_str($_REQUEST["id"]);
	$menu_item_uuid = check_str($_REQUEST['menu_item_uuid']);
	$group_uuid_name = check_str($_REQUEST['group_uuid_name']);
	$menu_item_group_uuid = check_str($_REQUEST['menu_item_group_uuid']);

//delete the group from the menu item
	if ($_REQUEST["a"] == "delete" && permission_exists("menu_delete") && $menu_item_group_uuid != '') {
		//delete the group from the users
			$sql = "delete from v_menu_item_groups  ";
			$sql .= "where menu_item_group_uuid = '".$menu_item_group_uuid."' ";
			$db->exec(check_sql($sql));
		//redirect the browser
			$_SESSION["message"] = $text['message-delete'];
			header("Location: menu_item_edit.php?id=".$menu_uuid."&menu_item_uuid=".$menu_item_uuid."&menu_uuid=".$menu_uuid);
			return;
	}

//action add or update
	if (isset($_REQUEST["menu_item_uuid"])) {
		if (strlen($_REQUEST["menu_item_uuid"]) > 0) {
			$action = "update";
			$menu_item_uuid = check_str($_REQUEST["menu_item_uuid"]);
		}
		else {
			$action = "add";
		}
	}
	else {
		$action = "add";
	}

//clear the menu session so it will rebuild with the update
	$_SESSION["menu"] = "";

//get the HTTP POST variables and set them as PHP variables
	if (count($_POST) > 0) {
		$menu_uuid = check_str($_POST["menu_uuid"]);
		$menu_item_uuid = check_str($_POST["menu_item_uuid"]);
		$menu_item_title = check_str($_POST["menu_item_title"]);
		$menu_item_link = check_str($_POST["menu_item_link"]);
		$menu_item_category = check_str($_POST["menu_item_category"]);
		$menu_item_icon = check_str($_POST["menu_item_icon"]);
		$menu_item_description = check_str($_POST["menu_item_description"]);
		$menu_item_protected = check_str($_POST["menu_item_protected"]);
		//$menu_item_uuid = check_str($_POST["menu_item_uuid"]);
		$menu_item_parent_uuid = check_str($_POST["menu_item_parent_uuid"]);
		$menu_item_order = check_str($_POST["menu_item_order"]);
	}

//when a HTTP POST is available then process it
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		if ($action == "update") {
			$menu_item_uuid = check_str($_POST["menu_item_uuid"]);
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
				$sql = "SELECT menu_language FROM v_menus ";
				$sql .= "where menu_uuid = '$menu_uuid' ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				foreach ($result as &$row) {
					$menu_language = $row['menu_language'];
				}

			//get the highest menu item order
				if (strlen($menu_item_parent_uuid) == 0) {
					$sql = "SELECT menu_item_order FROM v_menu_items ";
					$sql .= "where menu_uuid = '$menu_uuid' ";
					$sql .= "and menu_item_parent_uuid is null ";
					$sql .= "order by menu_item_order desc ";
					$sql .= "limit 1 ";
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
					$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
					foreach ($result as &$row) {
						$highest_menu_item_order = $row['menu_item_order'];
					}
					unset($prep_statement);
				}

			//add a menu item
				if ($action == "add" && permission_exists('menu_add')) {
					$menu_item_uuid = uuid();
					$sql = "insert into v_menu_items ";
					$sql .= "(";
					$sql .= "menu_uuid, ";
					$sql .= "menu_item_title, ";
					$sql .= "menu_item_link, ";
					$sql .= "menu_item_category, ";
					$sql .= "menu_item_icon, ";
					$sql .= "menu_item_description, ";
					$sql .= "menu_item_protected, ";
					$sql .= "menu_item_uuid, ";
					$sql .= "menu_item_parent_uuid, ";
					if (strlen($menu_item_parent_uuid) == 0) {
						$sql .= "menu_item_order, ";
					}
					$sql .= "menu_item_add_user, ";
					$sql .= "menu_item_add_date ";
					$sql .= ")";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'$menu_uuid', ";
					$sql .= "'$menu_item_title', ";
					$sql .= "'$menu_item_link', ";
					$sql .= "'$menu_item_category', ";
					$sql .= "'$menu_item_icon', ";
					$sql .= "'$menu_item_description', ";
					$sql .= "'$menu_item_protected', ";
					$sql .= "'".$menu_item_uuid."', ";
					if (strlen($menu_item_parent_uuid) == 0) {
						$sql .= "null, ";
						$sql .= "'".($highest_menu_item_order+1)."', ";
					}
					else {
						$sql .= "'$menu_item_parent_uuid', ";
					}
					$sql .= "'".$_SESSION["username"]."', ";
					$sql .= "now() ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					unset($sql);
				}

			//update the menu item
				if ($action == "update" && permission_exists('menu_edit')) {
					$sql  = "update v_menu_items set ";
					$sql .= "menu_item_title = '$menu_item_title', ";
					$sql .= "menu_item_link = '$menu_item_link', ";
					$sql .= "menu_item_category = '$menu_item_category', ";
					$sql .= "menu_item_icon = '$menu_item_icon', ";
					$sql .= "menu_item_description = '$menu_item_description', ";
					$sql .= "menu_item_protected = '$menu_item_protected', ";
					if (strlen($menu_item_parent_uuid) == 0) {
						$sql .= "menu_item_parent_uuid = null, ";
						if (strlen($menu_item_order) > 0) {
							$sql .= "menu_item_order = '$menu_item_order', ";
						}
						else {
							$sql .= "menu_item_order = '".($highest_menu_item_order+1)."', ";
						}
					}
					else {
						$sql .= "menu_item_parent_uuid = '$menu_item_parent_uuid', ";
					}
					$sql .= "menu_item_mod_user = '".$_SESSION["username"]."', ";
					$sql .= "menu_item_mod_date = now() ";
					$sql .= "where menu_uuid = '$menu_uuid' ";
					$sql .= "and menu_item_uuid = '$menu_item_uuid' ";
					$count = $db->exec(check_sql($sql));
				}

			//add a group to the menu
				if ($_REQUEST["a"] != "delete" && strlen($group_uuid_name) > 0 && permission_exists('menu_add')) {
					$group_data = explode('|', $group_uuid_name);
					$group_uuid = $group_data[0];
					$group_name = $group_data[1];
					//add the group to the menu
						if (strlen($menu_item_uuid) > 0) {
							$menu_item_group_uuid = uuid();
							$sql_insert = "insert into v_menu_item_groups ";
							$sql_insert .= "(";
							$sql_insert .= "menu_item_group_uuid, ";
							$sql_insert .= "menu_uuid, ";
							$sql_insert .= "menu_item_uuid, ";
							$sql_insert .= "group_name, ";
							$sql_insert .= "group_uuid ";
							$sql_insert .= ")";
							$sql_insert .= "values ";
							$sql_insert .= "(";
							$sql_insert .= "'".$menu_item_group_uuid."', ";
							$sql_insert .= "'".$menu_uuid."', ";
							$sql_insert .= "'".$menu_item_uuid."', ";
							$sql_insert .= "'".$group_name."', ";
							$sql_insert .= "'".$group_uuid."' ";
							$sql_insert .= ")";
							$db->exec($sql_insert);
						}
				}

			//add title to menu languages
				if ($_REQUEST["a"] != "delete" && strlen($menu_item_title) > 0 && permission_exists('menu_add')) {
					$sql = "select count(*) as num_rows from v_menu_languages ";
					$sql .= "where menu_item_uuid = '".$menu_item_uuid."' ";
					$sql .= "and menu_language = '$menu_language' ";
					$prep_statement = $db->prepare($sql);
					$prep_statement->execute();
					$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
					if ($row['num_rows'] == 0) {
						$sql_insert = "insert into v_menu_languages ";
						$sql_insert .= "(";
						$sql_insert .= "menu_language_uuid, ";
						$sql_insert .= "menu_uuid, ";
						$sql_insert .= "menu_item_uuid, ";
						$sql_insert .= "menu_language, ";
						$sql_insert .= "menu_item_title ";
						$sql_insert .= ")";
						$sql_insert .= "values ";
						$sql_insert .= "(";
						$sql_insert .= "'".uuid()."', ";
						$sql_insert .= "'".$menu_uuid."', ";
						$sql_insert .= "'".$menu_item_uuid."', ";
						$sql_insert .= "'".$menu_language."', ";
						$sql_insert .= "'".$menu_item_title."' ";
						$sql_insert .= ")";
						$db->exec($sql_insert);
					}
					else {
						$sql  = "update v_menu_languages set ";
						$sql .= "menu_item_title = '$menu_item_title' ";
						$sql .= "where menu_uuid = '$menu_uuid' ";
						$sql .= "and menu_item_uuid = '$menu_item_uuid' ";
						$sql .= "and menu_language = '$menu_language' ";
						$count = $db->exec(check_sql($sql));
					}
				}

			//set response message
				if ($action == "add") {
					$_SESSION["message"] = $text['message-add'];
				}
				if ($action == "update") {
					$_SESSION["message"] = $text['message-update'];
				}

			//redirect the user
				if ($_REQUEST['submit'] == $text['button-add']) {
					header("Location: menu_item_edit.php?id=".$menu_uuid."&menu_item_uuid=".$menu_item_uuid."&menu_uuid=".$menu_uuid);
				}
				else {
					header("Location: menu_edit.php?id=".$menu_uuid);
				}
				return;
		} //if ($_POST["persistformvar"] != "true")
	} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$menu_item_uuid = $_GET["menu_item_uuid"];

		$sql = "select * from v_menu_items ";
		$sql .= "where menu_uuid = '$menu_uuid' ";
		$sql .= "and menu_item_uuid = '$menu_item_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
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
	}

//include the header
	require_once "resources/header.php";
	if ($action == "update") {
		$document['title'] = $text['title-menu_item-edit'];
	}
	if ($action == "add") {
		$document['title'] = $text['title-menu_item-add'];
	}

	echo "<form method='post' action=''>";
	echo "<table width='100%' cellpadding='0' cellspacing='0'>";
	echo "<tr>\n";
	echo "<td width='30%' align='left' valign='top' nowrap><b>";
	if ($action == "update") {
		echo $text['header-menu_item-edit'];
	}
	if ($action == "add") {
		echo $text['header-menu_item-add'];
	}
	echo "</b></td>\n";
	echo "<td width='70%' align='right' valign='top'>";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='menu_edit.php?id=".$menu_uuid."'\" value='".$text['button-back']."'>";
	echo "	<input type='submit' class='btn' name='submit' value='".$text['button-save']."'>\n";
	echo "	<br><br>";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>";
	echo "		<td class='vncellreq'>".$text['label-title']."</td>";
	echo "		<td class='vtable'><input type='text' class='formfld' name='menu_item_title' value='$menu_item_title'></td>";
	echo "	</tr>";

	echo "	<tr>";
	echo "		<td class='vncellreq'>".$text['label-link']."</td>";
	echo "		<td class='vtable'><input type='text' class='formfld' name='menu_item_link' value='$menu_item_link'></td>";
	echo "	</tr>";

	echo "	<tr>";
	echo "		<td class='vncellreq'>".$text['label-category']."</td>";
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
	if (file_exists($_SERVER["PROJECT_ROOT"].'/resources/bootstrap/glyphicons.json')) {
		$tmp_array = json_decode(file_get_contents($_SERVER["PROJECT_ROOT"].'/resources/bootstrap/glyphicons.json'), true);
		if (is_array($tmp_array['icons']) && sizeof($tmp_array['icons']) > 0) {
			// rebuild and sort array
			foreach ($tmp_array['icons'] as $i => $glyphicon) {
				$tmp_string = str_replace('glyphicon-', '', $glyphicon['id']);
				$tmp_string = str_replace('-', ' ', $tmp_string);
				$tmp_string = ucwords($tmp_string);
				$glyphicons[$glyphicon['id']] = $tmp_string;
			}
			asort($glyphicons, SORT_STRING);
			echo "<table cellpadding='0' cellspacing='0' border='0'>\n";
			echo "	<tr>\n";
			echo "		<td>\n";
			echo "			<select class='formfld' name='menu_item_icon' id='menu_item_icon' onchange=\"$('#glyphicons').slideUp(); $('#grid_icon').fadeIn();\">\n";
			echo "				<option value=''></option>\n";
			foreach ($glyphicons as $glyphicon_class => $glyphicon_name) {
				$selected = ($menu_item_icon == $glyphicon_class) ? "selected" : null;
				echo "			<option value='".$glyphicon_class."' ".$selected.">".$glyphicon_name."</option>\n";
			}
			echo "			</select>\n";
			echo "		</td>\n";
			echo "		<td style='padding: 0 0 0 5px;'>\n";
			echo "			<button id='grid_icon' type='button' class='btn btn-default list_control_icon' style='font-size: 15px; padding-top: 1px; padding-left: 3px;' onclick=\"$('#glyphicons').slideToggle(); $(this).fadeOut();\"><span class='glyphicon glyphicon-th'></span></button>";
			echo "		</td>\n";
			echo "	</tr>\n";
			echo "</table>\n";
			echo "<div id='glyphicons' style='clear: both; display: none; padding-top: 10px; color: #000;'>";
			foreach ($glyphicons as $glyphicon_class => $glyphicon_name) {
				echo "<span class='glyphicon ".$glyphicon_class."' style='font-size: 24px; float: left; margin: 0 8px 8px 0; cursor: pointer; opacity: 0.3;' title='".$glyphicon_name."' onclick=\"$('#menu_item_icon').val('".$glyphicon_class."'); $('#glyphicons').slideUp(); $('#grid_icon').fadeIn();\" onmouseover=\"this.style.opacity='1';\" onmouseout=\"this.style.opacity='0.3';\"></span>\n";
			}
			echo "</div>";
		}
	}
	else {
		echo "		<input type='text' class='formfld' name='menu_item_icon' value='".$menu_item_icon."'>";
	}
	echo "		</td>";
	echo "	</tr>";

	echo "	<tr>";
	echo "		<td class='vncell'>".$text['label-parent_menu']."</td>";
	echo "		<td class='vtable'>";
	$sql = "SELECT * FROM v_menu_items ";
	$sql .= "where menu_uuid = '$menu_uuid' ";
	$sql .= "order by menu_item_title asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	echo "<select name=\"menu_item_parent_uuid\" class='formfld'>\n";
	echo "<option value=\"\"></option>\n";
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach($result as $field) {
			if ($menu_item_parent_uuid == $field['menu_item_uuid']) {
				echo "<option value='".$field['menu_item_uuid']."' selected>".$field['menu_item_title']."</option>\n";
			}
			else {
				echo "<option value='".$field['menu_item_uuid']."'>".$field['menu_item_title']."</option>\n";
			}
	}
	echo "</select>";
	unset($sql, $result);
	echo "		</td>";
	echo "	</tr>";

	echo "	<tr>";
	echo "		<td class='vncell' valign='top'>".$text['label-groups']."</td>";
	echo "		<td class='vtable'>";

	//group list
	$sql = "select ";
	$sql .= "	mig.*, g.domain_uuid as group_domain_uuid ";
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
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->bindParam(':menu_uuid', $menu_uuid);
	$prep_statement->bindParam(':menu_item_uuid', $menu_item_uuid);
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$result_count = count($result);
	if ($result_count > 0) {
		echo "<table cellpadding='0' cellspacing='0' border='0'>\n";
		foreach($result as $field) {
			if (strlen($field['group_name']) > 0) {
				echo "<tr>\n";
				echo "	<td class='vtable' style='white-space: nowrap; padding-right: 30px;' nowrap='nowrap'>";
				echo $field['group_name'].(($field['group_domain_uuid'] != '') ? "@".$_SESSION['domains'][$field['group_domain_uuid']]['domain_name'] : null);
				echo "	</td>\n";
				if (permission_exists('group_member_delete') || if_group("superadmin")) {
					echo "	<td class='list_control_icons' style='width: 25px;'>";
					echo 		"<a href='menu_item_edit.php?id=".$field['menu_uuid']."&menu_item_group_uuid=".$field['menu_item_group_uuid']."&menu_item_uuid=".$menu_item_uuid."&a=delete' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">".$v_link_label_delete."</a>";
					echo "	</td>";
				}
				echo "</tr>\n";
				$assigned_groups[] = $field['group_uuid'];
			}
		}
		echo "</table>\n";
	}
	unset($sql, $prep_statement, $result, $result_count);

	//group select
	$sql = "select * from v_groups ";
	if (sizeof($assigned_groups) > 0) {
		$sql .= "where group_uuid not in ('".implode("','",$assigned_groups)."') ";
	}
	$sql .= "order by domain_uuid desc, group_name asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$result_count = count($result);
	if ($result_count > 0) {
		echo "<br />\n";
		echo "<select name='group_uuid_name' class='formfld' style='width: auto; margin-right: 3px;'>\n";
		echo "	<option value=''></option>\n";
		foreach($result as $field) {
			if ($field['group_name'] == "superadmin" && !if_group("superadmin")) { continue; }	//only show the superadmin group to other superadmins
			if ($field['group_name'] == "admin" && (!if_group("superadmin") && !if_group("admin") )) { continue; }	//only show the admin group to other admins
			if (!in_array($field["group_uuid"], $assigned_groups)) {
				echo "	<option value='".$field['group_uuid']."|".$field['group_name']."'>".$field['group_name'].(($field['domain_uuid'] != '') ? "@".$_SESSION['domains'][$field['domain_uuid']]['domain_name'] : null)."</option>\n";
			}
		}
		echo "</select>";
		echo "<input type='submit' class='btn' name='submit' value=\"".$text['button-add']."\">\n";
	}
	unset($sql, $prep_statement, $result);

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
			echo "		<td class='vtable'><input type='text' class='formfld' name='menu_item_order' value='$menu_item_order'></td>";
			echo "	</tr>";
		}
	}

	echo "	<tr>";
	echo "		<td class='vncell'>".$text['label-description']."</td>";
	echo "		<td class='vtable'><input type='text' class='formfld' name='menu_item_description' value='$menu_item_description'></td>";
	echo "	</tr>";

	if (permission_exists('menu_add') || permission_exists('menu_edit')) {
		echo "	<tr>\n";
		echo "		<td colspan='2' align='right'>\n";
		echo "			<table width='100%'>";
		echo "			<tr>";
		echo "			<td align='left'>";
		echo "			</td>\n";
		echo "			<td align='right'>";
		if ($action == "update") {
			echo "			<input type='hidden' name='menu_item_uuid' value='$menu_item_uuid'>";
		}
		echo "				<input type='hidden' name='menu_uuid' value='$menu_uuid'>";
		echo "				<input type='hidden' name='menu_item_uuid' value='$menu_item_uuid'>";
		echo "				<br>";
		echo "				<input type='submit' class='btn' name='submit' value='".$text['button-save']."'>\n";
		echo "			</td>";
		echo "			</tr>";
		echo "			</table>";
		echo "		</td>";
		echo "	</tr>";
	}
	echo "</table>";
	echo "<br><br>";
	echo "</form>";

//include the footer
  require_once "resources/footer.php";
?>