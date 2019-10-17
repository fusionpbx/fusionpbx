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
	Portions created by the Initial Developer are Copyright (C) 2013 - 2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('ring_group_delete')) {
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
	$ring_group_destination_uuid = $_GET["id"];
	$ring_group_uuid = $_GET["ring_group_uuid"];

//delete ring_group_destination
	if (is_uuid($ring_group_destination_uuid) && is_uuid($ring_group_uuid)) {
		//build array
			$array['ring_group_destinations'][0]['ring_group_destination_uuid'] = $ring_group_destination_uuid;
		//execute delete
			$database = new database;
			$database->app_name = 'ring_groups';
			$database->app_uuid = '1d61fb65-1eec-bc73-a6ee-a6203b4fe6f2';
			$database->delete($array);
		//set message
			message::add($text['message-delete']);
		//redirect
			header("Location: ring_group_edit.php?id=".$ring_group_uuid);
			exit;
	}

//default redirect
	header("Location: ring_groups.php");
	exit;

?>
