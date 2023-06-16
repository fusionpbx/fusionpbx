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
	Portions created by the Initial Developer are Copyright (C) 2018 - 2023
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

/**
 * call_recordings class
 *
 * @method null download
 */
if (!class_exists('call_recordings')) {
	class call_recordings {

		/**
		* declare the variables
		*/
		private $app_name;
		private $app_uuid;
		private $name;
		private $table;
		private $description_field;
		private $location;
		public $recording_uuid;
		public $binary;

		/**
		 * called when the object is created
		 */
		public function __construct() {
			//assign the variables
				$this->app_name = 'call_recordings';
				$this->app_uuid = '56165644-598d-4ed8-be01-d960bcb8ffed';
				$this->name = 'call_recording';
				$this->table = 'call_recordings';
				$this->description_field = 'call_recording_description';
				$this->location = 'call_recordings.php';
		}

		/**
		 * delete rows from the database
		 */
		public function delete($records) {
			if (permission_exists($this->name.'_delete')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->location);
						exit;
					}

				//delete multiple records
					if (is_array($records) && @sizeof($records) != 0) {
						//build the delete array
							$x = 0;
							foreach ($records as $record) {
								//add to the array
									if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['uuid'])) {
										//get the information to delete
											$sql = "select call_recording_name, call_recording_path ";
											$sql .= "from view_call_recordings ";
											$sql .= "where call_recording_uuid = :call_recording_uuid ";
											$parameters['call_recording_uuid'] = $record['uuid'];
											$database = new database;
											$field = $database->select($sql, $parameters, 'row');
											if (is_array($field) && @sizeof($field) != 0) {
												//delete the file on the file system
													if (file_exists($field['call_recording_path'].'/'.$field['call_recording_name'])) {
														unlink($field['call_recording_path'].'/'.$field['call_recording_name']);
													}
												//build call recording delete array
													$array['xml_cdr'][$x]['xml_cdr_uuid'] = $record['uuid'];
													$array['xml_cdr'][$x]['record_path'] = null;
													$array['xml_cdr'][$x]['record_name'] = null;
													$array['xml_cdr'][$x]['record_length'] = null;
												//increment the id
													$x++;
											}
											unset($sql, $parameters, $field);
									}
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {

								//add temporary permissions
									$p = new permissions;
									$p->add('xml_cdr_edit', 'temp');

								//remove record_path, record_name and record_length
									$database = new database;
									$database->app_name = 'xml_cdr';
									$database->app_uuid = '4a085c51-7635-ff03-f67b-86e834422848';
									$database->save($array, false);
									$message = $database->message;
									unset($array);

								//remove the temporary permissions
									$p->delete('xml_cdr_edit', 'temp');

								//set message
									message::add($text['message-delete']);

							}
							unset($records);
					}
			}
		}

		/**
		 * download the recordings
		 */
		public function download() {
			if (permission_exists('call_recording_play') || permission_exists('call_recording_download')) {

				//get call recording from database
					if (is_uuid($this->recording_uuid)) {
						$sql = "select call_recording_name, call_recording_path ";
						if (!empty($_SESSION['call_recordings']['storage_type']['text']) && $_SESSION['call_recordings']['storage_type']['text'] == 'base64' && $row['call_recording_base64'] != '') {
							$sql = ", call_recording_base64 ";
						}
						$sql .= "from view_call_recordings ";
						$sql .= "where call_recording_uuid = :call_recording_uuid ";
						$parameters['call_recording_uuid'] = $this->recording_uuid;
						$database = new database;
						$row = $database->select($sql, $parameters, 'row');
						if (is_array($row) && @sizeof($row) != 0) {
							$call_recording_name = $row['call_recording_name'];
							$call_recording_path = $row['call_recording_path'];
							if (!empty($_SESSION['call_recordings']['storage_type']['text']) && $_SESSION['call_recordings']['storage_type']['text'] == 'base64' && $row['call_recording_base64'] != '') {
								file_put_contents($path.'/'.$call_recording_name, base64_decode($row['call_recording_base64']));
							}
						}
						unset($sql, $parameters, $row);
					}

				//build full path
					$full_recording_path = $call_recording_path.'/'.$call_recording_name;

				//download the file
					if ($full_recording_path != '/' && file_exists($full_recording_path)) {
 						ob_clean();
						$fd = fopen($full_recording_path, "rb");
						if ($this->binary) {
							header("Content-Type: application/force-download");
							header("Content-Type: application/octet-stream");
							header("Content-Type: application/download");
							header("Content-Description: File Transfer");
						}
						else {
							$file_ext = pathinfo($call_recording_name, PATHINFO_EXTENSION);
							switch ($file_ext) {
								case "wav" : header("Content-Type: audio/x-wav"); break;
								case "mp3" : header("Content-Type: audio/mpeg"); break;
								case "ogg" : header("Content-Type: audio/ogg"); break;
							}
						}
						header('Content-Disposition: attachment; filename="'.$call_recording_name.'"');
						header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
						header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
						if ($this->binary) {
							header("Content-Length: ".filesize($full_recording_path));
						}
 						ob_clean();

						//content-range
						if (isset($_SERVER['HTTP_RANGE']) && !$this->binary)  {
							$this->range_download($full_recording_path);
						}

 						fpassthru($fd);
					}

				//if base64, remove temp recording file
					if (!empty($_SESSION['call_recordings']['storage_type']['text']) && $_SESSION['call_recordings']['storage_type']['text'] == 'base64' && $row['call_recording_base64'] != '') {
						@unlink($full_recording_path);
					}
			}

		} //method

		/*
		 * range download method (helps safari play audio sources)
		 */
		private function range_download($file) {
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
			header("Accept-Ranges: 0-".$length);
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
				if ($range[0] == '-') {
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

	} //class
}

?>
