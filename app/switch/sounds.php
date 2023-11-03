<?php
/*
 *	FusionPBX
 *	Version: MPL 1.1
 *
 *	The contents of this file are subject to the Mozilla Public License Version
 *	1.1 (the "License"); you may not use this file except in compliance with
 *	the License. You may obtain a copy of the License at
 *	http://www.mozilla.org/MPL/
 *
 *	Software distributed under the License is distributed on an "AS IS" basis,
 *	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 *	for the specific language governing rights and limitations under the
 *	License.
 *
 *	The Original Code is FusionPBX
 *
 *	The Initial Developer of the Original Code is
 *	Mark J Crane <markjcrane@fusionpbx.com>
 *	Portions created by the Initial Developer are Copyright (C) 2023
 *	the Initial Developer. All Rights Reserved.
 *
 *	Contributor(s):
 *	Mark J Crane <markjcrane@fusionpbx.com>
 */

//set the max php execution time
	ini_set('max_execution_time', 7200);

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check the permission
	if (permission_exists('recording_play') || permission_exists('recording_download')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//set additional variables
	$action = $_REQUEST["action"] ?? '';

//download the sound
	if ($action == "download") {

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
			$filename_parts = explode('/', str_replace('..', '', $_GET['filename']));
			if (!is_array($filename_parts) || @sizeof($filename_parts) != 2) { exit; }
			$path = $_SESSION['switch']['sounds']['dir'].'/'.$language_path.'/'.$filename_parts[0].'/8000/';

		//set sound filename
			$sound_filename = $filename_parts[1];

		//build full path
			$full_sound_path = $path.$sound_filename;

		//send the headers and then the data stream
			if (file_exists($full_sound_path)) {

				$fd = fopen($full_sound_path, "rb");
				switch (pathinfo($sound_filename, PATHINFO_EXTENSION)) {
					case "wav" : header("Content-Type: audio/x-wav"); break;
					case "mp3" : header("Content-Type: audio/mpeg"); break;
					case "ogg" : header("Content-Type: audio/ogg"); break;
				}
				header('Content-Disposition: attachment; filename="'.$filename_parts[0].'_'.$filename_parts[1].'"');
				header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
				header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

				ob_clean();

				//content-range
				if (isset($_SERVER['HTTP_RANGE']) && (empty($_GET['t']) || $_GET['t'] != "bin"))  {
					range_download($full_sound_path);
				}

				fpassthru($fd);
			}

	}

//define the download function (helps safari play audio sources)
	function range_download($file) {
		$fp = @fopen($file, 'rb');

		$size   = filesize($file); // File size
		$length = $size;           // Content length
		$start  = 0;               // Start byte
		$end    = $size - 1;       // End byte
		// Now that we've gotten so far without errors we send the accept range header
		/* At the moment we only support single ranges.
		* Multiple ranges requires some more work to ensure it works correctly
		* and comply with the spesifications: http://www.w3.org/Protocols/rfc2616/rfc2616-sec19.html#sec19.2
		*
		* Multirange support annouces itself with:
		* header('Accept-Ranges: bytes');
		*
		* Multirange content must be sent with multipart/byteranges mediatype,
		* (mediatype = mimetype)
		* as well as a boundry header to indicate the various chunks of data.
		*/
		header("Accept-Ranges: 0-$length");
		// header('Accept-Ranges: bytes');
		// multipart/byteranges
		// http://www.w3.org/Protocols/rfc2616/rfc2616-sec19.html#sec19.2
		if (isset($_SERVER['HTTP_RANGE'])) {

			$c_start = $start;
			$c_end   = $end;
			// Extract the range string
			list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
			// Make sure the client hasn't sent us a multibyte range
			if (strpos($range, ',') !== false) {
				// (?) Shoud this be issued here, or should the first
				// range be used? Or should the header be ignored and
				// we output the whole content?
				header('HTTP/1.1 416 Requested Range Not Satisfiable');
				header("Content-Range: bytes $start-$end/$size");
				// (?) Echo some info to the client?
				exit;
			}
			// If the range starts with an '-' we start from the beginning
			// If not, we forward the file pointer
			// And make sure to get the end byte if spesified
			if ($range == '-') {
				// The n-number of the last bytes is requested
				$c_start = $size - substr($range, 1);
			}
			else {
				$range  = explode('-', $range);
				$c_start = $range[0];
				$c_end   = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
			}
			/* Check the range and make sure it's treated according to the specs.
			* http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
			*/
			// End bytes can not be larger than $end.
			$c_end = ($c_end > $end) ? $end : $c_end;
			// Validate the requested range and return an error if it's not correct.
			if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {

				header('HTTP/1.1 416 Requested Range Not Satisfiable');
				header("Content-Range: bytes $start-$end/$size");
				// (?) Echo some info to the client?
				exit;
			}
			$start  = $c_start;
			$end    = $c_end;
			$length = $end - $start + 1; // Calculate new content length
			fseek($fp, $start);
			header('HTTP/1.1 206 Partial Content');
		}
		// Notify the client the byte range we'll be outputting
		header("Content-Range: bytes $start-$end/$size");
		header("Content-Length: $length");

		// Start buffered download
		$buffer = 1024 * 8;
		while(!feof($fp) && ($p = ftell($fp)) <= $end) {
			if ($p + $buffer > $end) {
				// In case we're only outputtin a chunk, make sure we don't
				// read past the length
				$buffer = $end - $p + 1;
			}
			set_time_limit(0); // Reset time limit for big files
			echo fread($fp, $buffer);
			flush(); // Free up memory. Otherwise large files will trigger PHP's memory limit.
		}

		fclose($fp);
	}

?>