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
	Portions created by the Initial Developer are Copyright (C) 2008-2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('var_delete')) {
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
	$var_uuid = $_GET["id"];

//delete the data
	if (is_uuid($var_uuid)) {
		//build array
			$array['vars'][0]['var_uuid'] = $var_uuid;
		//execute delete
			$database = new database;
			$database->app_name = 'vars';
			$database->app_uuid = '54e08402-c1b8-0a9d-a30a-f569fc174dd8';
			$database->delete($array);
			unset($array);
		//rewrite the xml
			save_var_xml();
		//set message
			message::add($text['message-delete']);
	}

//redirect
	header("Location: vars.php");
	exit;

?>