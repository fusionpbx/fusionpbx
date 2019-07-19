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
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('menu_add') || permission_exists('menu_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//action add or update
	if (is_uuid($_REQUEST["id"])) {
		$action = "update";
		$menu_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$menu_uuid = $_POST["menu_uuid"];
		$menu_name = $_POST["menu_name"];
		$menu_language = $_POST["menu_language"];
		$menu_description = $_POST["menu_description"];
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$menu_uuid = $_POST["menu_uuid"];
	}

	//check for all required data
		//if (strlen($menu_name) == 0) { $msg .= $text['message-required'].$text['label-name']."<br>\n"; }
		//if (strlen($menu_language) == 0) { $msg .= $text['message-required'].$text['label-language']."<br>\n"; }
		//if (strlen($menu_description) == 0) { $msg .= $text['message-required'].$text['label-description']."<br>\n"; }
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
		if ($action == "add") {
			//create a new unique id
				$menu_uuid = uuid();

			//start a new menu
				$array['menus'][0]['menu_uuid'] = $menu_uuid;
				$array['menus'][0]['menu_name'] = $menu_name;
				$array['menus'][0]['menu_language'] = $menu_language;
				$array['menus'][0]['menu_description'] = $menu_description;
				$database = new database;
				$database->app_name = 'menu';
				$database->app_uuid = 'f4b3b3d2-6287-489c-2a00-64529e46f2d7';
				$database->save($array);
				unset($array);

			//add the default items in the menu
				require_once "resources/classes/menu.php";
				$menu = new menu;
				$menu->db = $db;
				$menu->menu_uuid = $menu_uuid;
				$menu->menu_language = $menu_language;
				$menu->restore();

			//redirect the user back to the main menu
				message::add($text['message-add']);
				header("Location: menu.php");
				return;
		} //if ($action == "add")

		if ($action == "update") {
			//update the menu
				$array['menus'][0]['menu_uuid'] = $menu_uuid;
				$array['menus'][0]['menu_name'] = $menu_name;
				$array['menus'][0]['menu_language'] = $menu_language;
				$array['menus'][0]['menu_description'] = $menu_description;
				$database = new database;
				$database->app_name = 'menu';
				$database->app_uuid = 'f4b3b3d2-6287-489c-2a00-64529e46f2d7';
				$database->save($array);
				unset($array);

			//redirect the user back to the main menu
				message::add($text['message-update']);
				header("Location: menu.php");
				return;
		}
	}
}

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$menu_uuid = $_GET["id"];
		$sql = "select * from v_menus ";
		$sql .= "where menu_uuid = :menu_uuid ";
		$parameters['menu_uuid'] = $menu_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && sizeof($row) != 0) {
			$menu_uuid = $row["menu_uuid"];
			$menu_name = $row["menu_name"];
			$menu_language = $row["menu_language"];
			$menu_description = $row["menu_description"];
		}
		unset($sql, $parameters, $row);
	}

//show the header
	require_once "resources/header.php";
	if ($action == "update") {
		$document['title'] = $text['title-menu-edit'];
	}
	if ($action == "add") {
		$document['title'] = $text['title-menu-add'];
	}

//show the content
	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	if ($action == "add") {
		echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['header-menu-add']."</b></td>\n";
	}
	if ($action == "update") {
		echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['header-menu-edit']."</b></td>\n";
	}
	echo "<td width='70%' align='right'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='menu.php'\" value='".$text['button-back']."'>\n";
	if (permission_exists('menu_restore') && $action == "update") {
		echo "	<input type='button' class='btn' value='".$text['button-restore_default']."' onclick=\"document.location.href='menu_restore_default.php?menu_uuid=$menu_uuid&menu_language=$menu_language';\" />";
	}
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	if ($action == "update") {
		echo $text['description-menu-edit'];
	}
	if ($action == "add") {
		echo $text['description-menu-add'];
	}
	echo "<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='menu_name' maxlength='255' value=\"".escape($menu_name)."\">\n";
	echo "<br />\n";
	echo "\n";
	echo $text['description-name']."</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-language']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='menu_language' maxlength='255' value=\"".escape($menu_language)."\">\n";
	echo "<br />\n";
	echo $text['description-language']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='menu_description' maxlength='255' value=\"".escape($menu_description)."\">\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "		<input type='hidden' name='menu_uuid' value='".escape($menu_uuid)."'>\n";
	}
	echo "			<br>";
	echo "			<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";
	echo "</form>";

//show the menu items
	if ($action == "update") {
		require_once "core/menu/menu_item_list.php";
	}

//include the footer
	require_once "resources/footer.php";
?>
