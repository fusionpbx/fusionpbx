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
	if (permission_exists('music_on_hold_delete')) {
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
	$music_on_hold_uuid = $_GET["id"];


if (is_uuid($music_on_hold_uuid)) {

	//delete the data
		$array['music_on_hold'][0]['music_on_hold_uuid'] = $music_on_hold_uuid;

		$database = new database;
		$database->app_name = 'music_on_hold';
		$database->app_uuid = '1dafe0f8-c08a-289b-0312-15baf4f20f81';
		$database->delete($array);
		unset($array);

	//clear the cache
		$cache = new cache;
		$cache->delete("configuration:local_stream.conf");

	//reload mod local stream
		$music = new switch_music_on_hold;
		$music->reload();

	//set messsage
		message::add($text['message-delete']);
}

//redirect the user
	header('Location: music_on_hold.php');
	exit;

?>
