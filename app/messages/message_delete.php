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
	Portions created by the Initial Developer are Copyright (C) 2016-2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!permission_exists('message_delete')) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the id
	$message_uuids = $_REQUEST['messages'];

//delete the message
	if (is_array($message_uuids) && @sizeof($message_uuids) != 0) {

		//delete message
			foreach ($message_uuids as $index => $message_uuid) {
				$array['messages'][$index]['message_uuid'] = $message_uuid;
				$array['messages'][$index]['domain_uuid'] = $domain_uuid;
			}

			$database = new database;
			$database->app_name = 'messages';
			$database->app_uuid = '4a20815d-042c-47c8-85df-085333e79b87';
			$database->delete($array);
			unset($array);

		//set message
			message::add($text['message-delete']);

	}

//redirect the user
	header('Location: messages_log.php');
	exit;

?>