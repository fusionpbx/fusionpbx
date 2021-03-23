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

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
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
	if (count($_POST) > 0) {
		$menu_uuid = $_POST["menu_uuid"];
		$menu_name = $_POST["menu_name"];
		$menu_language = $_POST["menu_language"];
		$menu_description = $_POST["menu_description"];
	}

//process the http post
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: menu.php');
				exit;
			}

		//check for all required data
			$msg = '';
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
	if (count($_GET) > 0 && is_uuid($_GET["id"]) && $_POST["persistformvar"] != "true") {
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

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-menu'];
	require_once "resources/header.php";

//show the content
	echo "<form method='post' name='frm' id='frm'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['header-menu']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','collapse'=>'hide-xs','link'=>'menu.php']);
	echo button::create(['type'=>'button','label'=>$text['button-reload'],'icon'=>$_SESSION['theme']['button_icon_reload'],'collapse'=>'hide-xs','style'=>'margin-left: 15px;','link'=>'menu_reload.php?menu_uuid='.urlencode($menu_uuid).'&menu_language='.urlencode($menu_language)]);
	if (permission_exists('menu_restore') && $action == "update") {
		echo button::create(['type'=>'button','label'=>$text['button-restore_default'],'icon'=>'undo-alt','collapse'=>'hide-xs','onclick'=>"modal_open('modal-restore','btn_restore');"]);
	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','collapse'=>'hide-xs']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('menu_restore') && $action == "update") {
		echo modal::create(['id'=>'modal-restore','type'=>'confirmation','message'=>$text['confirm-restore'],'actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_restore','style'=>'float: right; margin-left: 15px;','collapse'=>'never','link'=>'menu_restore_default.php?menu_uuid='.urlencode($menu_uuid).'&menu_language='.urlencode($menu_language),'onclick'=>'modal_close();'])]);
	}

	echo $text['description-menu']."\n";
	echo "<br /><br />\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-name']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
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

	echo "</table>";
	echo "<br><br>";

	if ($action == "update") {
		echo "<input type='hidden' name='menu_uuid' value='".escape($menu_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//show the menu items
	if ($action == "update") {
		require_once "core/menu/menu_item_list.php";
	}

//include the footer
	require_once "resources/footer.php";

?>