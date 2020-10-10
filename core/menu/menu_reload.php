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
	Portions created by the Initial Developer are Copyright (C) 2020
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	include "root.php";
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

//get the http value and set as a php variable
	$menu_uuid = $_REQUEST["menu_uuid"];

//unset the sesssion menu array
	unset($_SESSION['menu']['array']);

//get the menu array and save it to the session
	$menu = new menu;
	$menu->menu_uuid = $_SESSION['domain']['menu']['uuid'];
	$_SESSION['menu']['array'] = $menu->menu_array();
	unset($menu);

//redirect the user
	message::add($text['message-reload']);
	header("Location: ".PROJECT_PATH."/core/menu/menu_edit.php?id=".urlencode($menu_uuid));
	return;

?>