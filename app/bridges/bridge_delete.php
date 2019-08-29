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
	Portions created by the Initial Developer are Copyright (C) 2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('bridge_delete')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//delete the data
	if (is_uuid($_GET["id"])) {

		//get the id
			$bridge_uuid = $_GET["id"];

			$array['bridges'][0]['bridge_uuid'] = $bridge_uuid;
			$array['bridges'][0]['domain_uuid'] = $_SESSION['domain_uuid'];

			$database = new database;
			$database->app_name = 'bridges';
			$database->app_uuid = 'a6a7c4c5-340a-43ce-bcbc-2ed9bab8659d';
			$database->delete($array);
			unset($array);

		//add the message
			message::add($text['message-delete']);

	}

//redirect the user
	header('Location: bridges.php');


?>
