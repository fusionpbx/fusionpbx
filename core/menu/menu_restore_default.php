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
	Portions created by the Initial Developer are Copyright (C) 2008-2012
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('menu_restore')) {
	//access granted
}
else {
	echo "access denied";
	return;
}

//get the http value and set as a php variable
	$menu_uuid = check_str($_REQUEST["menu_uuid"]);
	$menu_language = check_str($_REQUEST["menu_language"]);

//menu restore default
	require_once "includes/classes/menu.php";
	$menu = new menu;
	$menu->db = $db;
	$menu->menu_uuid = $menu_uuid;
	$menu->menu_language = $menu_language;
	$menu->delete();
	$menu->restore();

//unset the menu session variable
	$_SESSION["menu"] = "";

//unset the default template
	$_SESSION["template_content"] = '';

//show a message to the user
	require_once "includes/header.php";
	echo "<meta http-equiv=\"refresh\" content=\"2;url=".PROJECT_PATH."/core/menu/menu_edit.php?id=$menu_uuid\">\n";
	echo "<div align='center'>\n";
	echo "Restore Complete\n";
	echo "</div>\n";
	require_once "includes/footer.php";
	return;

?>