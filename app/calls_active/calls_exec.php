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

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes fileshp";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('call_active_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//authorized referrer
	if (stristr($_SERVER["HTTP_REFERER"], '/calls_active.php') === false) {
		echo "access denied";
		exit;
	}

//authorized commands
	if ($_REQUEST['action'] == 'hangup' && permission_exists('call_active_hangup')) {

		//validate the token
			$token = new token;
			if (!$token->validate('/app/calls_active/calls_active_inc.php')) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: calls_active.php');
				exit;
			}

		//verify submitted call uuids
			if (is_array($_POST['calls']) && @sizeof($_POST['calls']) != 0) {
				foreach ($_POST['calls'] as $call) {
					if ($call['checked'] == 'true' && is_uuid($call['uuid'])) {
						$calls[] = $call['uuid'];
					}
				}
			}
			if (is_uuid($_REQUEST['uuid'])) {
				$calls[] = $_REQUEST['uuid'];
			}

		//iterate through calls
			if (is_array($calls) && @sizeof($calls) != 0) {

				//setup the event socket connection
					$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);

				//execute hangup command
					foreach ($calls as $call_uuid) {
						$switch_result = event_socket_request($fp, 'api uuid_kill '.$call_uuid);
					}

				//set message
					message::add($text['message-calls_ended'].': '.@sizeof($calls),'positive');

			}

		//redirect
			header('Location: calls_active.php');
			exit;

	}
	else {
		echo "access denied";
		exit;
	}

?>