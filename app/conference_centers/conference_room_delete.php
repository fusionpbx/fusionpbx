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
	Portions created by the Initial Developer are Copyright (C) 2008-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('conference_room_delete')) {
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

		$conference_room_uuid = $_GET["id"];

		//get the meeting_uuid
			$sql = "select meeting_uuid from v_conference_rooms ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$sql .= "and conference_room_uuid = :conference_room_uuid ";
			$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
			$parameters['conference_room_uuid'] = $conference_room_uuid;
			$database = new database;
			$meeting_uuid = $database->select($sql, $parameters, 'column');
			unset($sql, $parameters);

		//delete conference session
			$array['conference_rooms'][0]['conference_room_uuid'] = $conference_room_uuid;
			$array['conference_rooms'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
		//delete meeting users
			$array['meeting_users'][0]['meeting_uuid'] = $meeting_uuid;
			$array['meeting_users'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
		//delete meeting
			$array['meetings'][0]['meeting_uuid'] = $meeting_uuid;
			$array['meetings'][0]['domain_uuid'] = $_SESSION['domain_uuid'];

			$p = new permissions;
			$p->add('meeting_user_delete', 'temp');
			$p->add('meeting_delete', 'temp');

			$database = new database;
			$database->app_name = 'conference_centers';
			$database->app_uuid = '8d083f5a-f726-42a8-9ffa-8d28f848f10e';
			$database->delete($array);
			unset($array);

			$p->delete('meeting_user_delete', 'temp');
			$p->delete('meeting_delete', 'temp');

		//set message
			message::add($text['message-delete']);

	}

//redirect the user
	header("Location: conference_rooms.php");
	return;

?>
