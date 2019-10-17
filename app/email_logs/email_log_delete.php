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
	Copyright (C) 2008-2019
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('email_log_delete')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get posted values, if any
	$email_log_uuid = $_REQUEST["id"];
	$showall = $_REQUEST['showall'];

	if (is_uuid($email_log_uuid)) {
		$array['email_logs'][0]['email_log_uuid'] = $email_log_uuid;
		if (!permission_exists('email_log_all') || $_REQUEST['showall'] != 'true') {
			$array['email_logs'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
		}

		$database = new database;
		$database->app_name = 'email_logs';
		$database->app_uuid = 'bd64f590-9a24-468d-951f-6639ac728694';
		$database->delete($array);
		unset($array);

		message::add($text['message-delete']);
	}

//redirect user
	header("Location: email_logs.php".($showall == 'true' ? '?showall=true' : null));
	exit;

?>
