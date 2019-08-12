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
	Portions created by the Initial Developer are Copyright (C) 2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('pin_number_delete')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the id
	$pin_number_uuid = $_GET["id"];

//delete the data
	if (is_uuid($pin_number_uuid)) {
		//build array
			$array['pin_numbers'][0]['pin_number_uuid'] = $pin_number_uuid;
			$array['pin_numbers'][0]['domain_uuid'] = $domain_uuid;
		//delete pin_number
			$database = new database;
			$database->app_name = 'pin_numbers';
			$database->app_uuid = '4b88ccfb-cb98-40e1-a5e5-33389e14a388';
			$database->delete($array);
			unset($array);
		//set message
			message::add($text['message-delete']);
	}

//redirect the user
	header('Location: pin_numbers.php');
	exit;

?>