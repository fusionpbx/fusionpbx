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
	if (!empty($_GET['id'])) {

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
		$greeting_dir = $_SESSION['switch']['voicemail']['dir'].'/default/'.$_SESSION['domains'][$_SESSION['domain_uuid']]['domain_name'].'/'.$voicemail_id;

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

		//stream waveform file
		if (file_exists($full_file_path)) {

			//temporary waveform image filename
			$temp_filename = 'waveform_'.$_GET['id'].'_'.rand(0000,9999).'.png';

			//create temporary waveform image, if doesn't exist
			if (file_exists($greeting_dir.'/'.$temp_filename)) {
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
				$wf = $waveform->getWaveform($greeting_dir.'/'.$temp_filename, 1600, $height ?? 180); // input: png filename returns boolean true/false, or 'base64' returns base64 string
			}

			//stream image to browser
			if ($wf === true && file_exists($greeting_dir.'/'.$temp_filename)) {

				ob_clean();
				$fd = fopen($greeting_dir.'/'.$temp_filename, 'rb');
				header("Content-Type: application/force-download");
				header("Content-Type: application/octet-stream");
				header("Content-Type: application/download");
				header("Content-Description: File Transfer");
				header("Content-Type: image/png");
				header('Content-Disposition: attachment; filename="'.$temp_filename.'"');
				header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
				header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
				header("Content-Length: ".filesize($greeting_dir.'/'.$temp_filename));
				ob_clean();

				fpassthru($fd);

			}

		}

		//if base64, remove temp file
		if (!empty($_SESSION['voicemail']['storage_type']['text']) && $_SESSION['voicemail']['storage_type']['text'] == 'base64' && !empty($row['greeting_base64'])) {
			if ($greeting_id != $selected_greeting_id) {
				@unlink($greeting_dir.'/'.$greeting_filename);
			}
		}
		unset($row);

		//delete temp waveform image
		@unlink($greeting_dir.'/'.$temp_filename);

	}

?>