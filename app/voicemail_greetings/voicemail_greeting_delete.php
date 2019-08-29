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
	Portions created by the Initial Developer are Copyright (C) 2008-2012
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('voicemail_greeting_delete')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get ids
	$voicemail_greeting_uuid = $_GET["id"];
	$voicemail_id = $_GET["voicemail_id"];

if (is_uuid($voicemail_greeting_uuid) && $voicemail_id != '') {
	//get the greeting filename
		$sql = "select greeting_filename ";
		$sql .= "from v_voicemail_greetings ";
		$sql .= "where voicemail_greeting_uuid = :voicemail_greeting_uuid ";
		$sql .= "and domain_uuid = :domain_uuid ";
		$sql .= "and voicemail_id = :voicemail_id ";
		$parameters['voicemail_greeting_uuid'] = $voicemail_greeting_uuid;
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['voicemail_id'] = $voicemail_id;
		$database = new database;
		$greeting_filename = $database->select($sql, $parameters, 'column');
		unset($sql, $parameters);

	//build delete array
		$array['voicemail_greetings'][0]['voicemail_greeting_uuid'] = $voicemail_greeting_uuid;
		$array['voicemail_greetings'][0]['domain_uuid'] = $domain_uuid;
		$array['voicemail_greetings'][0]['voicemail_id'] = $voicemail_id;

	//execute delete
		$database = new database;
		$database->app_name = 'voicemail_greetings';
		$database->app_uuid = 'e4b4fbee-9e4d-8e46-3810-91ba663db0c2';
		$database->delete($array);
		unset($array);

	//set the greeting directory
		$v_greeting_dir = $_SESSION['switch']['storage']['dir'].'/voicemail/default/'.$_SESSION['domains'][$domain_uuid]['domain_name'].'/'.$voicemail_id;

	//delete the recording file
		@unlink($v_greeting_dir."/".$greeting_filename);

	//set message
		message::add($text['message-delete']);
}

//redirect
	header("Location: voicemail_greetings.php?id=".$voicemail_id);
	exit;

?>