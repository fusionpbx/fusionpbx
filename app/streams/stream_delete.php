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
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('stream_delete')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get id
	$stream_uuid = $_GET["id"];

//delete the data
	if (is_uuid($stream_uuid)) {

		//build array
			$array['streams'][0]['stream_uuid'] = $stream_uuid;
			$array['streams'][0]['domain_uuid'] = $domain_uuid;

		//execute delete
			$database = new database;
			$database->app_name = 'streams';
			$database->app_uuid = 'ffde6287-aa18-41fc-9a38-076d292e0a38';
			$database->delete($array);
			unset($array);

		//set message
			message::add($text['message-delete']);

	}

//redirect
	header('Location: streams.php');
	exit;

?>