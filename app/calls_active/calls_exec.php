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

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!permission_exists('call_active_view')) {
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
			$calls = [];
			if (is_array($_POST['calls']) && @sizeof($_POST['calls']) != 0) {
				foreach ($_POST['calls'] as $call) {
					if (is_array($call) && ($call['checked'] ?? '') == 'true' && is_uuid($call['uuid'] ?? '')) {
						$calls[] = $call['uuid'];
					}
				}
			}
			if (!empty($_REQUEST['uuid']) && is_uuid($_REQUEST['uuid'])) {
				$calls[] = $_REQUEST['uuid'];
			}

		//limit the commands to channels of the current domain, superadmin is exempt
			$domain_name = $_SESSION['domain_name'] ?? '';
			$channel_in_domain = function (?string $uuid) use ($domain_name): bool {
				if (if_group('superadmin')) {
					return true;
				}
				if ($uuid === '' || !is_uuid($uuid)) {
					return false;
				}

				//get the domain of the channel from the switch
				$channel_domain = trim((string) event_socket::api('uuid_getvar ' . $uuid . ' domain_name'));
				if ($channel_domain === '' || $channel_domain === '_undef_' || stripos($channel_domain, '-ERR') !== false) {
					//fall back to the presence id which is user@domain
						$presence_id = str_replace('%40', '@', trim((string) event_socket::api('uuid_getvar ' . $uuid . ' presence_id')));
						$presence = explode('@', $presence_id);
						$channel_domain = $presence[1] ?? '';
				}

				//compare the domain, a channel without a known domain is not allowed
				return $channel_domain !== '' && $channel_domain === $domain_name;
			};

		//remove calls outside the current domain before executing anything
			$calls = array_values(array_filter($calls, $channel_in_domain));

		//iterate through calls
			if (count($calls) > 0) {

				//setup the event socket connection
					$event_socket = event_socket::create();

				//execute hangup command
					if ($event_socket->is_connected()) foreach ($calls as $call_uuid) {
						event_socket::async("uuid_kill ".$call_uuid);
					}

				//set message
					message::add($text['message-calls_ended'].': '.count($calls),'positive');

			}

		//redirect
			header('Location: calls_active.php');
			exit;

	}
	else if ($_REQUEST['action'] == 'eavesdrop' && permission_exists('call_active_eavesdrop')) {

		//validate the token
		$token = new token;
		if (!$token->validate('/app/calls_active/calls_active_inc.php')) {
			echo "access denied";
			exit;
		}

		//set the allowed regex number pattern
		$number_pattern = '/^[0-9A-Za-z*#+._-]+$/';

		//get the values from HTTP
		$channnel_uuid = $_GET['chan_uuid'] ?? '';
		$extension = $_GET['ext'] ?? '';
		$destination = $_GET['destination'] ?? '';

		//deny access if the variables are not valid
		if (!is_uuid($channnel_uuid) || !preg_match($number_pattern, (string) $extension) || !preg_match($number_pattern, (string) $destination)) {
			echo "access denied";
			exit;
		}

		//limit the command to channels of the current domain, superadmin is exempt
			$domain_name = $_SESSION['domain_name'] ?? '';
			$channel_in_domain = function (?string $uuid) use ($domain_name): bool {
				if (if_group('superadmin')) {
					return true;
				}
				if ($uuid === '' || !is_uuid($uuid)) {
					return false;
				}

				//get the domain of the channel from the switch
				$channel_domain = trim((string) event_socket::api('uuid_getvar ' . $uuid . ' domain_name'));
				if ($channel_domain === '' || $channel_domain === '_undef_' || stripos($channel_domain, '-ERR') !== false) {
					//fall back to the presence id which is user@domain
						$presence_id = str_replace('%40', '@', trim((string) event_socket::api('uuid_getvar ' . $uuid . ' presence_id')));
						$presence = explode('@', $presence_id);
						$channel_domain = $presence[1] ?? '';
				}

				//compare the domain, a channel without a known domain is not allowed
				return $channel_domain !== '' && $channel_domain === $domain_name;
			};
			if (!$channel_in_domain($channnel_uuid)) {
				echo "access denied";
				exit;
			}

		$api_cmd = 'bgapi originate {origination_caller_id_name='.$text['label-eavesdrop'].',origination_caller_id_number='.$extension.'}user/'.$destination.'@'.$_SESSION['domain_name'].' &eavesdrop('.$channnel_uuid.')';

		//run the command
		$switch_result = event_socket::api($api_cmd);

	}
	else {
		echo "access denied";
		exit;
	}

?>
