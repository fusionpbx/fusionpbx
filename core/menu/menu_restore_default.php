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

//check permissions
	if (!$included) {
		include "root.php";
		require_once "resources/require.php";
		require_once "resources/check_auth.php";
		if (permission_exists('menu_restore')) {
			//access granted
		}
		else {
			echo "access denied";
			return;
		}
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the http value and set as a php variable
	if (!$included) {
		$menu_uuid = $_REQUEST["menu_uuid"];
		$menu_language = $_REQUEST["menu_language"];
	}

//menu restore default
	require_once "resources/classes/menu.php";
	$menu = new menu;
	$menu->menu_uuid = $menu_uuid;
	$menu->menu_language = $menu_language;
	$menu->delete_unprotected();
	$menu->restore();
	unset($menu);

//get the menu array and save it to the session
	$menu = new menu;
	$menu->menu_uuid = $_SESSION['domain']['menu']['uuid'];
	$_SESSION['menu']['array'] = $menu->menu_array();
	unset($menu);

//redirect
	if (!$included) {
		//show a message to the user
		message::add($text['message-restore']);
		header("Location: ".PROJECT_PATH."/core/menu/menu_edit.php?id=".urlencode($menu_uuid));
		return;
	}

?>
