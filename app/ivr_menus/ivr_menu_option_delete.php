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
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('ivr_menu_delete')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the http values as variables
	if (count($_GET)>0) {
		$id = check_str($_GET["id"]);
		$ivr_menu_uuid = check_str($_GET["ivr_menu_uuid"]);
	}

//delete the ivr menu option
	if (strlen($id)>0) {
		//include the ivr menu class
			require_once "resources/classes/database.php";
			require_once "resources/classes/ivr_menu.php";
			$ivr = new ivr_menu;
			$ivr->domain_uuid = $_SESSION["domain_uuid"];
			$ivr->ivr_menu_option_uuid = $id;
			$ivr->delete();
	}

//redirect the user
	$_SESSION['message'] = $text['message-delete'];
	header('Location: ivr_menu_edit.php?id='.$ivr_menu_uuid);

?>