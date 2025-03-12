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
	if (permission_exists('recording_play')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//create the waveform file
	if ($_GET['id'] && !empty($_GET['data']) && !empty($_GET['type'])) {

		//determine path of audio file by type
		if ($_GET['type'] == 'recordings') {

			$slash = substr($_GET['data'],0,1) != '/' ? '/' : null;
			$full_file_path = $_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name'].$slash.str_replace($_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name'], '', $_GET['data']);

		}
		else if ($_GET['type'] == 'sounds') {

			//get first installed language (like en/us/callie)
			$language_paths = glob($_SESSION["switch"]['sounds']['dir']."/*/*/*");
			foreach ($language_paths as $key => $path) {
				$path = str_replace($_SESSION["switch"]['sounds']['dir'].'/', "", $path);
				$path_array = explode('/', $path);
				if (count($path_array) <> 3 || strlen($path_array[0]) <> 2 || strlen($path_array[1]) <> 2) {
					unset($language_paths[$key]);
				}
				$language_paths[$key] = str_replace($_SESSION["switch"]['sounds']['dir']."/","",$language_paths[$key] ?? '');
				if (empty($language_paths[$key])) {
					unset($language_paths[$key]);
				}
			}
			$language_path = $language_paths[0];

			//determine the path for sound file
			$filename_parts = explode('/', str_replace('..', '', $_GET['data']));
			if (!is_array($filename_parts) || @sizeof($filename_parts) != 2) { exit; }
			$path = $_SESSION['switch']['sounds']['dir'].'/'.$language_path.'/'.$filename_parts[0].'/8000/';

			//build full path to sound file
			$full_file_path = $path.$filename_parts[1];

		}

		//stream waveform file
		if (file_exists($full_file_path)) {

			//temporary waveform image filename
			$temp_filename = 'waveform_'.$_GET['id'].'_'.rand(0000,9999).'.png';

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

		//delete temp waveform image
		@unlink($temp_filename);

	}

?>