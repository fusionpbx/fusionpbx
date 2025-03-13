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
	if (permission_exists('music_on_hold_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//create the waveform file
	if (is_uuid($_GET['id']) && !empty($_GET['data'])) {

		//get the music_on_hold array
		$sql = "select music_on_hold_path from v_music_on_hold ";
		$sql .= "where music_on_hold_uuid = :id ";
		if (!permission_exists('music_on_hold_all')) {
			$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
			$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		}
		if (permission_exists('music_on_hold_domain')) {
			$sql .= "or domain_uuid is null ";
		}
		$parameters['id'] = $_GET['id'];
		$database = new database;
		$stream_path = $database->select($sql, $parameters ?? null, 'column');
		unset($sql, $parameters);

		//replace the sounds_dir variable in the path
		$stream_path = str_replace('$${sounds_dir}', $_SESSION['switch']['sounds']['dir'], $stream_path);
		$stream_path = str_replace('..', '', $stream_path);

		//get the file and sanitize it
		$stream_file = str_replace(['..','/',':'], '', basename($_GET['data']));

		//join the path and file name
		$stream_full_path = path_join($stream_path, $stream_file);

		//stream waveform file
		if (file_exists($stream_full_path)) {

			//temporary waveform image filename
			$temp_filename = 'waveform_'.$_GET['id'].'_'.rand(0000,9999).'.png';

			//create temporary waveform image, if doesn't exist
			if (file_exists($temp_filename)) {
				$wf = true;
			}
			else {
				//create temporary waveform image
				$waveform = new Waveform($stream_full_path);
				Waveform::$linesPerPixel = 1; // default: 8
				Waveform::$samplesPerLine = 512; // default: 512
				Waveform::$colorA = !empty($_SESSION['theme']['audio_player_waveform_color_a_leg']['text']) ? color_to_rgba_array($_SESSION['theme']['audio_player_waveform_color_a_leg']['text']) : [32,134,37,0.6]; // array rgba,  left (a-leg) wave color
				Waveform::$colorB = !empty($_SESSION['theme']['audio_player_waveform_color_b_leg']['text']) ? color_to_rgba_array($_SESSION['theme']['audio_player_waveform_color_b_leg']['text']) : [0,125,232,0.6]; // array rgba, right (b-leg) wave color
				Waveform::$backgroundColor = !empty($_SESSION['theme']['audio_player_waveform_color_background']['text']) ? color_to_rgba_array($_SESSION['theme']['audio_player_waveform_color_background']['text']) : [0,0,0,0]; // array rgba, default: transparent
				Waveform::$axisColor = !empty($_SESSION['theme']['audio_player_waveform_color_axis']['text']) ? color_to_rgba_array($_SESSION['theme']['audio_player_waveform_color_axis']['text']) : [0,0,0,0.3]; // array rgba
				Waveform::$singlePhase = filter_var($_SESSION['theme']['audio_player_waveform_single_phase']['boolean'] ?? false, FILTER_VALIDATE_BOOL) ? 'true': 'false'; // positive phase only - left (a-leg) top, right (b-leg) bottom
				Waveform::$singleAxis = Waveform::$singlePhase === true ? false : (filter_var($_SESSION['theme']['audio_player_waveform_single_axis']['boolean'] ?? false, FILTER_VALIDATE_BOOL) ? 'true': 'false'); // combine channels into single axis
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

		//delete temp waveform image
		@unlink($temp_filename);

	}

?>