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
	Portions created by the Initial Developer are Copyright (C) 2008-2017
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Matthew Vale <github@mafoo.org>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('number_translation_add') || permission_exists('number_translation_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//set the variables
	$cmd = $_GET['cmd'];
	$rdr = $_GET['rdr'];

//create the event socket connection
	$esl = event_socket::create();
	if ($esl->is_connected()) {
		//reloadxml
			if ($cmd == "api reloadxml") {
				message::add(rtrim(event_socket::command($cmd)), 'alert');
				unset($cmd);
			}

		//reload mod_translate
			if ($cmd == "api reload mod_translate") {
				message::add(rtrim(event_socket::command($cmd)), 'alert');
				unset($cmd);
			}

	}

//redirect the user
	if ($rdr == "false") {
		//redirect false
		echo $response;
	}
	else {
		header("Location: number_translations.php");
	}

?>
