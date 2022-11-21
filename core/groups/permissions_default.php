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
	Portions created by the Initial Developer are Copyright (C) 2008-2014
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//check permisions
	if (!$included) {
		//set the include path
		$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
		set_include_path(parse_ini_file($conf[0])['document.root']);

		//includes files
		require_once "resources/require.php";
		require_once "resources/check_auth.php";
		if (permission_exists('group_edit')) {
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

//permission restore default
	require_once "core/groups/resources/classes/permission.php";
	$permission = new permission;
	$permission->db = $db;
	$permission->restore();

//redirect the users
	if (!$included) {
		//show a message to the user
		message::add($text['message-restore']);
		header("Location: groups.php");
		return;
	}

?>