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
	Portions created by the Initial Developer are Copyright (C) 2026
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//send a json response and exit
	function recording_call_response(array $array, int $code = 200) {
		http_response_code($code);
		header('Content-Type: application/json');
		echo json_encode($array);
		exit;
	}

//check permissions
	if (!recording_call::allowed('recording') && !recording_call::allowed('greeting')) {
		recording_call_response(['error' => $text['message-recording_call_denied']], 403);
	}

//only accept post requests
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		recording_call_response(['error' => $text['message-recording_call_denied']], 405);
	}

//validate the token, the status request only reads the state of a call started by this session
	$action = $_POST['action'] ?? '';
	if ($action != 'status') {
		$token = new token;
		if (!$token->validate('/app/recordings/recording_call.php')) {
			recording_call_response(['error' => $text['message-invalid_token']], 403);
		}
	}

//set the variables
	$domain_uuid = $_SESSION['domain_uuid'];
	$domain_name = $_SESSION['domain_name'];
	$user_uuid = $_SESSION['user_uuid'];
	$database = database::new();
	$settings = new settings(['domain_uuid' => $domain_uuid, 'user_uuid' => $user_uuid]);
	$recordings_dir = $settings->get('switch', 'recordings').'/'.$domain_name;
	$type = ($_POST['type'] ?? '') == 'greeting' ? 'greeting' : 'recording';

//check that the event socket is available
	$esl = event_socket::create();
	if (!$esl->is_connected()) {
		recording_call_response(['error' => $text['message-recording_call_failed']], 500);
	}

//start the call
	if ($action == 'call') {

		//check the permission for the type
			if (!recording_call::allowed($type)) {
				recording_call_response(['error' => $text['message-recording_call_denied']], 403);
			}

		//get the extension, it must be one of the extensions this user is allowed to call
			$extension_uuid = $_POST['extension_uuid'] ?? '';
			$extension = null;
			$extensions = recording_call::extensions($type);
			foreach ($extensions as $row) {
				if (is_uuid($extension_uuid) && $row['extension_uuid'] === $extension_uuid) {
					$extension = $row;
					break;
				}
			}
			if (empty($extension) || !preg_match('/^[a-zA-Z0-9_\-\.\+\*#]+$/', $extension['extension'])) {
				recording_call_response(['error' => $text['message-recording_call_invalid_extension']], 400);
			}

		//record a voicemail greeting, or re-record an existing recording, or add a new recording
			$recording_uuid = $_POST['recording_uuid'] ?? '';
			$voicemail_id = '';
			$greeting_id = '';
			if ($type == 'greeting') {
				//get the voicemail, users without voicemail_view can only use the voicemail of their own extensions
					$voicemail_id = $_POST['voicemail_id'] ?? '';
					if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $voicemail_id)) {
						recording_call_response(['error' => $text['message-recording_call_denied']], 400);
					}
					if (!permission_exists('voicemail_view')) {
						$assigned = false;
						foreach ($extensions as $row) {
							if ($row['extension'] == $voicemail_id || (is_numeric($row['number_alias']) && $row['number_alias'] == $voicemail_id)) {
								$assigned = true;
								break;
							}
						}
						if (!$assigned) {
							recording_call_response(['error' => $text['message-recording_call_denied']], 403);
						}
					}
					$sql = "select count(*) from v_voicemails ";
					$sql .= "where domain_uuid = :domain_uuid ";
					$sql .= "and voicemail_id = :voicemail_id ";
					$parameters['domain_uuid'] = $domain_uuid;
					$parameters['voicemail_id'] = $voicemail_id;
					$voicemail_exists = $database->select($sql, $parameters, 'column') > 0;
					unset($sql, $parameters);
					if (!$voicemail_exists) {
						recording_call_response(['error' => $text['message-recording_call_denied']], 400);
					}

				//find the next available greeting id, a voicemail can have up to 9 greetings
					$text_greetings = $language->get(null, 'app/voicemail_greetings');
					$greeting_dir = $settings->get('switch', 'voicemail').'/default/'.$domain_name.'/'.$voicemail_id;
					$sql = "select greeting_id from v_voicemail_greetings ";
					$sql .= "where domain_uuid = :domain_uuid ";
					$sql .= "and voicemail_id = :voicemail_id ";
					$parameters['domain_uuid'] = $domain_uuid;
					$parameters['voicemail_id'] = $voicemail_id;
					$rows = $database->select($sql, $parameters, 'all');
					unset($sql, $parameters);
					$used = !empty($rows) && is_array($rows) ? array_map('intval', array_column($rows, 'greeting_id')) : [];
					for ($i = 1; $i <= 9; $i++) {
						if (!in_array($i, $used) && !file_exists($greeting_dir.'/greeting_'.$i.'.wav') && !file_exists($greeting_dir.'/greeting_'.$i.'.mp3') && !file_exists($greeting_dir.'/greeting_'.$i.'.ogg')) {
							$greeting_id = $i;
							break;
						}
					}
					if (empty($greeting_id)) {
						recording_call_response(['error' => $text_greetings['message-maximum_voicemail_greetings'].' 9'], 400);
					}

				//set the greeting name and file name
					$recording_name = recording_call::name($_POST['recording_name'] ?? '');
					if (empty($recording_name)) {
						$recording_name = $text_greetings['label-greeting'].' '.$greeting_id;
					}
					$recording_filename = 'greeting_'.$greeting_id.'.wav';
					$recording_path = $greeting_dir.'/'.$recording_filename;
					$recording_uuid = uuid();
			}
			else if (!empty($recording_uuid)) {
				if (!is_uuid($recording_uuid) || !permission_exists('recording_edit')) {
					recording_call_response(['error' => $text['message-recording_call_denied']], 403);
				}
				$sql = "select recording_name, recording_filename from v_recordings ";
				$sql .= "where domain_uuid = :domain_uuid ";
				$sql .= "and recording_uuid = :recording_uuid ";
				$parameters['domain_uuid'] = $domain_uuid;
				$parameters['recording_uuid'] = $recording_uuid;
				$row = $database->select($sql, $parameters, 'row');
				unset($sql, $parameters);
				if (empty($row) || !preg_match('/^[a-zA-Z0-9_\-\.]+\.(wav|mp3)$/', $row['recording_filename']) || str_contains($row['recording_filename'], '..')) {
					recording_call_response(['error' => $text['message-recording_call_denied']], 400);
				}
				$recording_name = $row['recording_name'];
				$recording_filename = $row['recording_filename'];
			}
			else {
				if (!permission_exists('recording_add')) {
					recording_call_response(['error' => $text['message-recording_call_denied']], 403);
				}

				//set the recording name
					$recording_name = recording_call::name($_POST['recording_name'] ?? '');
					if (empty($recording_name)) {
						$recording_name = 'Recording '.date('Y-m-d His');
					}

				//set a unique file name
					$recording_base = trim(preg_replace('#[^a-zA-Z0-9_\-]#', '_', $recording_name), '_');
					if (empty($recording_base)) {
						$recording_base = 'recording';
					}
					$recording_base = substr($recording_base, 0, 200);
					$sql = "select recording_filename from v_recordings where domain_uuid = :domain_uuid ";
					$parameters['domain_uuid'] = $domain_uuid;
					$existing = $database->select($sql, $parameters, 'all');
					unset($sql, $parameters);
					$existing = !empty($existing) && is_array($existing) ? array_column($existing, 'recording_filename') : [];
					$i = 1;
					do {
						$recording_filename = $recording_base.($i > 1 ? '_'.$i : '').'.wav';
						$i++;
					} while (in_array($recording_filename, $existing) || file_exists($recordings_dir.'/'.$recording_filename));

				$recording_uuid = uuid();
			}
			if ($type == 'recording') {
				$recording_path = $recordings_dir.'/'.$recording_filename;
			}

		//set the caller id shown on the phone
			$caller_id_name = $text['label-recording_call_caller_id'];

		//build the originate command
			$call_uuid = uuid();
			$variables = [];
			$variables[] = "origination_uuid=".$call_uuid;
			$variables[] = "origination_caller_id_name='".$caller_id_name."'";
			$variables[] = "origination_caller_id_number=".$extension['extension'];
			$variables[] = "originate_timeout=30";
			$variables[] = "ignore_early_media=true";
			$variables[] = "domain_uuid=".$domain_uuid;
			$variables[] = "domain_name=".$domain_name;
			$variables[] = "recording_call_uuid=".$recording_uuid;
			$variables[] = "recording_call_user_uuid=".$user_uuid;
			$variables[] = "recording_call_filename=".$recording_filename;
			$variables[] = "recording_call_name='".$recording_name."'";
			$variables[] = "recording_call_type=".$type;
			if ($type == 'greeting') {
				$variables[] = "recording_call_voicemail_id=".$voicemail_id;
				$variables[] = "recording_call_greeting_id=".$greeting_id;
			}
			$command = "bgapi originate {".implode(',', $variables)."}user/".$extension['extension']."@".$domain_name." &lua('app.lua recording_call')";
			$response = event_socket::command($command);
			$reply = is_array($response) ? ($response['Reply-Text'] ?? '') : (string)$response;
			if (!str_starts_with(trim($reply), '+OK')) {
				recording_call_response(['error' => $text['message-recording_call_failed']], 500);
			}

		//remember the call for the status requests
			$_SESSION['recording_call'][$call_uuid] = [
				'time' => time(),
				'recording_uuid' => $recording_uuid,
				'recording_name' => $recording_name,
				'recording_filename' => $recording_filename,
				'recording_path' => $recording_path,
				'type' => $type,
			];

			recording_call_response(['call_uuid' => $call_uuid, 'recording_uuid' => $recording_uuid]);
	}

//get the call status, or hang up the call
	if ($action == 'status' || $action == 'hangup') {

		//the call must have been started by this session
			$call_uuid = $_POST['call_uuid'] ?? '';
			if (!is_uuid($call_uuid) || empty($_SESSION['recording_call'][$call_uuid])) {
				recording_call_response(['error' => $text['message-recording_call_ended']], 404);
			}
			$call = $_SESSION['recording_call'][$call_uuid];

		//hang up the call
			if ($action == 'hangup') {
				event_socket::api("uuid_kill ".$call_uuid);
				recording_call_response(['state' => 'ended']);
			}

		//the call is still up, return the state set by the script
			$exists = trim((string)event_socket::api("uuid_exists ".$call_uuid)) === 'true';
			if ($exists || time() - $call['time'] < 3) {
				$state = $exists ? trim((string)event_socket::api("uuid_getvar ".$call_uuid." recording_call_state")) : '';
				if (!in_array($state, ['answered', 'recording', 'review', 'saved', 'deleted', 'failed'])) {
					$state = 'calling';
				}
				$_SESSION['recording_call'][$call_uuid]['state'] = $state;
				recording_call_response(['state' => $state, 'done' => false]);
			}

		//the call has ended, check if the recording was saved
			unset($_SESSION['recording_call'][$call_uuid]);
			$recording_path = $call['recording_path'];
			clearstatcache(true, $recording_path);
			if ($call['type'] == 'greeting') {
				$sql = "select count(*) from v_voicemail_greetings ";
				$sql .= "where domain_uuid = :domain_uuid ";
				$sql .= "and voicemail_greeting_uuid = :recording_uuid ";
			}
			else {
				$sql = "select count(*) from v_recordings ";
				$sql .= "where domain_uuid = :domain_uuid ";
				$sql .= "and recording_uuid = :recording_uuid ";
			}
			$sql .= "and coalesce(update_date, insert_date) >= to_timestamp(:time) ";
			$parameters['domain_uuid'] = $domain_uuid;
			$parameters['recording_uuid'] = $call['recording_uuid'];
			$parameters['time'] = $call['time'] - 5;
			$saved = $database->select($sql, $parameters, 'column') > 0 && file_exists($recording_path);

			//when the caller hangs up the script saves the recording after the channel is gone, allow it a moment
			if (!$saved && in_array($call['state'] ?? '', ['recording', 'review'])) {
				session_write_close();
				for ($i = 0; $i < 6 && !$saved; $i++) {
					usleep(500000);
					clearstatcache(true, $recording_path);
					$saved = $database->select($sql, $parameters, 'column') > 0 && file_exists($recording_path);
				}
			}
			unset($sql, $parameters);
			if ($saved) {
				recording_call_response([
					'state' => 'saved',
					'done' => true,
					'recording_uuid' => $call['recording_uuid'],
					'recording_name' => $call['recording_name'],
					'recording_filename' => $call['recording_filename'],
					'recording_path' => $recording_path,
				]);
			}
			$state = in_array($call['state'] ?? '', ['deleted', 'failed']) ? $call['state'] : 'ended';
			recording_call_response(['state' => $state, 'done' => true]);
	}

//unknown action
	recording_call_response(['error' => $text['message-recording_call_denied']], 400);
