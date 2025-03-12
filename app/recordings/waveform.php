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

//initialize the database connection
	$database = database::new();

//create the waveform file
	if (is_uuid($_GET['id']) || !empty($_GET['data'])) {

		//set the path for the directory
		$path = $_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name'];

		//get recording details from db
		if (is_uuid($_GET['id'])) {
			$sql = "select recording_filename, recording_base64 ";
			$sql .= "from v_recordings ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$sql .= "and recording_uuid = :recording_uuid ";
			$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
			$parameters['recording_uuid'] = $_GET['id'];
			$row = $database->select($sql, $parameters, 'row');
			if (is_array($row) && @sizeof($row) != 0) {
				$recording_filename = $row['recording_filename'];
				if (!empty($_SESSION['recordings']['storage_type']['text']) && $_SESSION['recordings']['storage_type']['text'] == 'base64' && !empty($row['recording_base64'])) {
					$recording_decoded = base64_decode($row['recording_base64']);
					file_put_contents($path.'/'.$recording_filename, $recording_decoded);
				}
			}
			unset($sql, $parameters, $row, $recording_decoded);
		}
		else if ($_GET['data']) {
			$recording_filename = str_replace($path, '', $_GET['data']);
		}

		//build full path
		$slash = substr($recording_filename,0,1) != '/' ? '/' : null;
		$full_file_path = $path.$slash.$recording_filename;

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