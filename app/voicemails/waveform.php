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
	Portions created by the Initial Developer are Copyright (C) 2024
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

	use maximal\audio\Waveform;

//check permisions
	if (permission_exists('voicemail_message_view') || permission_exists('voicemail_greeting_play')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//create the waveform file
	if (!empty($_GET['id']) && !empty($_GET['type'])) {

		//generate random number
		$rand = rand(0000,9999);

		//determine type and get audio file path
		switch ($_GET['type']) {

			case 'message':

				if (!empty($_GET['data'])) {

					//determine voicemail id and voicemail uuid from data field
					$data = explode('|',trim($_GET['data']));
					$voicemail_id = $data[0];
					$voicemail_uuid = $data[1];
					unset($data);

					//set source folder path
					$path = $_SESSION['switch']['voicemail']['dir'].'/default/'.$_SESSION['domain_name'].'/'.$voicemail_id;

					//prepare base64 content from db, if enabled
					if (
						is_numeric($voicemail_id) &&
						is_uuid($voicemail_uuid) &&
						is_uuid($_GET['id']) &&
						!empty($_SESSION['voicemail']['storage_type']['text']) &&
						$_SESSION['voicemail']['storage_type']['text'] == 'base64'
						) {

						$sql = "select message_base64 ";
						$sql .= "from ";
						$sql .= "v_voicemail_messages as m, ";
						$sql .= "v_voicemails as v ";
						$sql .= "where ";
						$sql .= "m.voicemail_uuid = v.voicemail_uuid ";
						$sql .= "and v.voicemail_id = :voicemail_id ";
						$sql .= "and m.voicemail_uuid = :voicemail_uuid ";
						$sql .= "and m.domain_uuid = :domain_uuid ";
						$sql .= "and m.voicemail_message_uuid = :voicemail_message_uuid ";
						$parameters['voicemail_id'] = $voicemail_id;
						$parameters['voicemail_uuid'] = $voicemail_uuid;
						$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
						$parameters['voicemail_message_uuid'] = $_GET['id'];
						$database = new database;
						$message_base64 = $database->select($sql, $parameters, 'column');
						if (!empty($message_base64)) {
							$message_decoded = base64_decode($message_base64);
							file_put_contents($path.'/waveform_'.$_GET['id'].'_'.$rand.'.ext', $message_decoded);
							$finfo = finfo_open(FILEINFO_MIME_TYPE); //determine mime type (requires PHP >= 5.3.0, must be manually enabled on Windows)
							$file_mime = finfo_file($finfo, $path.'/waveform_'.$_GET['id'].'_'.$rand.'.ext');
							finfo_close($finfo);
							switch ($file_mime) {
								case 'audio/x-wav':
								case 'audio/wav':
									$file_ext = 'wav';
									break;
								case 'audio/mpeg':
								case 'audio/mp3':
									$file_ext = 'mp3';
									break;
							}
							rename($path.'/waveform_'.$_GET['id'].'_'.$rand.'.ext', $path.'/waveform_'.$_GET['id'].'_'.$rand.'.'.$file_ext);
						}
						unset($sql, $parameters, $message_base64, $message_decoded);
					}

					//prepare full file path
					if (file_exists($path.'/waveform_'.$_GET['id'].'_'.$rand.'.wav')) {
						$file_ext = 'wav';
						$full_file_path = $path.'/waveform_'.$_GET['id'].'_'.$rand.'.wav';
					}
					else if (file_exists($path.'/waveform_'.$_GET['id'].'_'.$rand.'.mp3')) {
						$file_ext = 'mp3';
						$full_file_path = $path.'/waveform_'.$_GET['id'].'_'.$rand.'.mp3';
					}
					else {
						if (file_exists($path.'/msg_'.$_GET['id'].'.wav')) {
							copy($path.'/msg_'.$_GET['id'].'.wav', $path.'/waveform_'.$_GET['id'].'_'.$rand.'.wav');
							if (file_exists($path.'/waveform_'.$_GET['id'].'_'.$rand.'.wav')) {
								$file_ext = 'wav';
								$full_file_path = $path.'/waveform_'.$_GET['id'].'_'.$rand.'.wav';
							}
						}
						else if (file_exists($path.'/msg_'.$_GET['id'].'.mp3')) {
							copy($path.'/msg_'.$_GET['id'].'.mp3', $path.'/waveform_'.$_GET['id'].'_'.$rand.'.mp3');
							if (file_exists($path.'/waveform_'.$_GET['id'].'_'.$rand.'.mp3')) {
								$file_ext = 'mp3';
								$full_file_path = $path.'/waveform_'.$_GET['id'].'_'.$rand.'.mp3';
							}
						}
					}

				}
				break;

			case 'recorded_name':

				//used below to search the array to determine if an extension is assigned to the user
				function extension_assigned($number) {
					foreach ($_SESSION['user']['extension'] as $row) {
						if ((is_numeric($row['number_alias']) && $row['number_alias'] == $number) || $row['user'] == $number) {
							return true;
						}
					}
					return false;
				}

				//define name recording directory
				if (
					!empty($_GET['data']) &&
					is_numeric($_GET['data']) &&
					extension_assigned($_GET['data']) &&
					!empty($_SESSION['switch']['storage']['dir'])
					) {
					$full_file_path = $_SESSION['switch']['storage']['dir'].'/voicemail/default/'.$_SESSION['domains'][$_SESSION['domain_uuid']]['domain_name'].'/'.$_GET['data'].'/recorded_name.wav';
				}
				break;

			case 'greeting':

				//determine voicemail id and voicemail greeting uuid from data field
				$data = explode('|',trim($_GET['data']));
				$voicemail_id = $data[0];
				$voicemail_greeting_uuid = $data[1];
				unset($data);

				//get currently selected greeting
				$sql = "select greeting_id from v_voicemails ";
				$sql .= "where domain_uuid = :domain_uuid ";
				$sql .= "and voicemail_id = :voicemail_id ";
				$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
				$parameters['voicemail_id'] = $voicemail_id;
				$database = new database;
				$selected_greeting_id = $database->select($sql, $parameters, 'column');
				unset($sql, $parameters);

				//define greeting directory
				$greeting_dir = $_SESSION['switch']['storage']['dir'].'/voicemail/default/'.$_SESSION['domains'][$_SESSION['domain_uuid']]['domain_name'].'/'.$voicemail_id;

				//get voicemail greeting details from db
				$sql = "select greeting_filename, greeting_base64, greeting_id ";
				$sql .= "from v_voicemail_greetings ";
				$sql .= "where domain_uuid = :domain_uuid ";
				$sql .= "and voicemail_greeting_uuid = :voicemail_greeting_uuid ";
				$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
				$parameters['voicemail_greeting_uuid'] = $voicemail_greeting_uuid;
				$database = new database;
				$row = $database->select($sql, $parameters, 'row');
				if (!empty($row) && is_array($row) && @sizeof($row) != 0) {
					$greeting_filename = $row['greeting_filename'];
					$greeting_id = $row['greeting_id'];
					if (!empty($_SESSION['voicemail']['storage_type']['text']) && $_SESSION['voicemail']['storage_type']['text'] == 'base64' && !empty($row['greeting_base64'])) {
						$greeting_decoded = base64_decode($row['greeting_base64']);
						file_put_contents($greeting_dir.'/'.$greeting_filename, $greeting_decoded);
					}
				}
				unset($sql, $greeting_decoded);

				//define full audio file path
				$full_file_path = $greeting_dir.'/'.$greeting_filename;
				break;

		}

		//stream waveform file
		if (file_exists($full_file_path)) {

			//temporary waveform image filename
			$temp_filename = 'waveform_'.$_GET['id'].'_'.$rand.'.png';

			//create temporary waveform image, if doesn't exist
			if (file_exists($temp_filename)) {
				$wf = true;
			}
			else {
				//create temporary waveform image
				$waveform = new Waveform($full_file_path);
				Waveform::$linesPerPixel = 1; // default: 8
				Waveform::$samplesPerLine = 512; // default: 512
				Waveform::$colorA = !empty($_SESSION['theme']['audio_player_waveform_color_a_leg']['text']) ? color_to_rgba_array($_SESSION['theme']['audio_player_waveform_color_a_leg']['text']) : [32,134,37,0.6]; // array rgba,  left (a-leg) wave color
				Waveform::$colorB = !empty($_SESSION['theme']['audio_player_waveform_color_b_leg']['text']) ? color_to_rgba_array($_SESSION['theme']['audio_player_waveform_color_b_leg']['text']) : [0,125,232,0.6]; // array rgba, right (b-leg) wave color
				Waveform::$backgroundColor = !empty($_SESSION['theme']['audio_player_waveform_color_background']['text']) ? color_to_rgba_array($_SESSION['theme']['audio_player_waveform_color_background']['text']) : [0,0,0,0]; // array rgba, default: transparent
				Waveform::$axisColor = !empty($_SESSION['theme']['audio_player_waveform_color_axis']['text']) ? color_to_rgba_array($_SESSION['theme']['audio_player_waveform_color_axis']['text']) : [0,0,0,0.3]; // array rgba
				Waveform::$singlePhase = filter_var($_SESSION['theme']['audio_player_waveform_single_phase']['boolean'] ?? false, FILTER_VALIDATE_BOOL) ? 'true': 'false'; // positive phase only - left (a-leg) top, right (b-leg) bottom
				Waveform::$singleAxis = filter_var($_SESSION['theme']['audio_player_waveform_single_axis']['boolean'] ?? true, FILTER_VALIDATE_BOOL) ? 'true': 'false'; // combine channels into single axis
				$height = !empty($_SESSION['theme']['audio_player_waveform_height']['text']) && is_numeric(str_replace('px','',$_SESSION['theme']['audio_player_waveform_height']['text'])) ? 2.2 * (int) str_replace('px','',$_SESSION['theme']['audio_player_waveform_height']['text']) : null;
				$wf = $waveform->getWaveform($temp_filename, 1600, $height ?? 180); // input: png filename returns boolean true/false, or 'base64' returns base64 string
			}

			//stream image to browser
			if ($wf === true && file_exists($temp_filename)) {

				ob_clean();
				$fd = fopen($temp_filename, 'rb');
				header("Content-Type: application/force-download");
				header("Content-Type: application/octet-stream");
				header("Content-Type: application/download");
				header("Content-Description: File Transfer");
				header("Content-Type: image/png");
				header('Content-Disposition: attachment; filename="'.$temp_filename.'"');
				header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
				header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
				header("Content-Length: ".filesize($temp_filename));
				ob_clean();

				fpassthru($fd);

			}

		}

		//if base64, remove temp audio file
		switch ($_GET['type']) {

			case 'message':
				if (file_exists($path.'/waveform_'.$_GET['id'].'_'.$rand.'.'.$file_ext)) {
					@unlink($path.'/waveform_'.$_GET['id'].'_'.$rand.'.'.$file_ext);
				}
				break;

			case 'greeting':
				if (!empty($_SESSION['voicemail']['storage_type']['text']) && $_SESSION['voicemail']['storage_type']['text'] == 'base64' && !empty($row['greeting_base64'])) {
					if ($greeting_id != $selected_greeting_id) {
						@unlink($greeting_dir.'/'.$greeting_filename);
					}
				}
				unset($row);
				break;

		}

		//delete temp waveform image
		@unlink($temp_filename);

	}

?>