<?php

/**
 * call_recordings class
 *
 * @method null download
 */
if (!class_exists('call_recordings')) {
	class call_recordings {

		/**
		 * Called when the object is created
		 */
		public function __construct() {

		}

		/**
		 * Called when there are no references to a particular object
		 * unset the variables used in the class
		 */
		public function __destruct() {
			foreach ($this as $key => $value) {
				unset($this->$key);
			}
		}

		/**
		 * download the recordings
		 */
		public function download() {
			if (permission_exists('call_recording_play') || permission_exists('call_recording_download')) {

				//cache limiter
					session_cache_limiter('public');

				//get call recording from database
					$call_recording_uuid = $_GET['id'];
					if (is_uuid($call_recording_uuid)) {
						$sql = "select call_recording_name, call_recording_path, call_recording_base64 ";
						$sql .= "from v_call_recordings ";
						$sql .= "where call_recording_uuid = :call_recording_uuid ";
						$parameters['call_recording_uuid'] = $call_recording_uuid;
						$database = new database;
						$row = $database->select($sql, $parameters, 'row');
						if (is_array($row) && @sizeof($row) != 0) {
							$call_recording_name = $row['call_recording_name'];
							$call_recording_path = $row['call_recording_path'];
							if ($_SESSION['call_recordings']['storage_type']['text'] == 'base64' && $row['call_recording_base64'] != '') {
								file_put_contents($path.'/'.$call_recording_name, base64_decode($row['call_recording_base64']));
							}
						}
						unset($sql, $parameters, $row);
					}

				//set the path for the directory
					$default_path = $_SESSION['switch']['call_recordings']['dir']."/".$_SESSION['domain_name'];

				//build full path
					$full_recording_path = $call_recording_path . '/' . $call_recording_name;

				//download the file
					if (file_exists($full_recording_path)) {
						//content-range
						//if (isset($_SERVER['HTTP_RANGE']))  {
						//	range_download($full_recording_path);
						//}
						ob_clean();
						$fd = fopen($full_recording_path, "rb");
						if ($_GET['t'] == "bin") {
							header("Content-Type: application/force-download");
							header("Content-Type: application/octet-stream");
							header("Content-Type: application/download");
							header("Content-Description: File Transfer");
						}
						else {
							$file_ext = substr($call_recording_name, -3);
							if ($file_ext == "wav") {
								header("Content-Type: audio/x-wav");
							}
							if ($file_ext == "mp3") {
								header("Content-Type: audio/mpeg");
							}
						}
						header('Content-Disposition: attachment; filename="'.$call_recording_name.'"');
						header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
						header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
						// header("Content-Length: ".filesize($full_recording_path));
						ob_clean();
						fpassthru($fd);
					}

				//if base64, remove temp recording file
					if ($_SESSION['call_recordings']['storage_type']['text'] == 'base64' && $row['call_recording_base64'] != '') {
						@unlink($full_recording_path);
					}
			}
		} //end download method

		/**
		 * delete the recordings
		 */
		public function delete($id) {
			if (permission_exists('call_recording_delete')) {

				//cache limiter
					session_cache_limiter('public');

				//delete single call recording
					if (is_uuid($id)) {
						//build delete array
							$array['call_recordings'][]['call_recording_uuid'] = $id;
						//grant temporary permissions
							$p = new permissions;
							$p->add('call_recording_delete', 'temp');
						//execute delete
							$database = new database;
							$database->app_name = 'call_recordings';
							$database->app_uuid = '56165644-598d-4ed8-be01-d960bcb8ffed';
							$database->delete($array);
							unset($array);
						//revoke temporary permissions
							$p->delete('call_recording_delete', 'temp');
					}

				//delete multiple call recordings
					if (is_array($id) && @sizeof($id) != 0) {
						//set the array
							$call_recordings = $id;
						//get the action
							foreach ($call_recordings as $row) {
								if ($row['action'] == 'delete') {
									$action = 'delete';
									break;
								}
							}
						//delete the checked rows
							if ($action == 'delete') {
								foreach ($call_recordings as $row) {
									if ($row['checked'] == 'true') {
										//get the information to delete
											$sql = "select call_recording_name, call_recording_path ";
											$sql .= "from v_call_recordings ";
											$sql .= "where call_recording_uuid = :call_recording_uuid ";
											$parameters['call_recording_uuid'] = $row['call_recording_uuid'];
											$database = new database;
											$field = $database->select($sql, $parameters, 'row');
											if (is_array($field) && @sizeof($field) != 0) {
												//delete the file on the file system
													if (file_exists($field['call_recording_path'].'/'.$field['call_recording_name'])) {
														unlink($field['call_recording_path'].'/'.$field['call_recording_name']);
													}
												//build call recording delete array
													$array['call_recordings'][]['call_recording_uuid'] = $row['call_recording_uuid'];

											}
											unset($sql, $parameters, $field);
									}
								}
								if (is_array($array) && @sizeof($array) != 0) {
									//grant temporary permissions
										$p = new permissions;
										$p->add('call_recording_delete', 'temp');
									//execute delete
										$database = new database;
										$database->app_name = 'call_recordings';
										$database->app_uuid = '56165644-598d-4ed8-be01-d960bcb8ffed';
										$database->delete($array);
										unset($array);
									//revoke temporary permissions
										$p->delete('call_recording_delete', 'temp');
								}
							}
							unset($call_recordings, $row);
					}
			}
		} //end the delete function

	}  //end the class
}

/*
$obj = new call_recordings;
$obj->download('all');
*/

?>