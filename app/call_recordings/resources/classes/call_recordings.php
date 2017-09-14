<?php

/**
 * call_recordings class
 *
 * @method null download
 */
if (!class_exists('call_recordings')) {
	class call_recordings {

		public $db;

		/**
		 * Called when the object is created
		 */
		public function __construct() {
			//connect to the database if not connected
			if (!$this->db) {
				require_once "resources/classes/database.php";
				$database = new database;
				$database->connect();
				$this->db = $database->db;
			}
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

//			if (permission_exists('call_recording_play') || permission_exists('call_recording_download')) {

				//cache limiter
					session_cache_limiter('public');

				//get call recording from database
					$call_recording_uuid = check_str($_GET['id']);
					if ($call_recording_uuid != '') {
						$sql = "select call_recording_name, call_recording_path, call_recording_base64 from v_call_recordings ";
						$sql .= "where call_recording_uuid = '".$call_recording_uuid."' ";
						//$sql .= "and domain_uuid = '".$domain_uuid."' \n";
						$prep_statement = $this->db->prepare(check_sql($sql));
						$prep_statement->execute();
						$call_recordings = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
						if (is_array($call_recordings)) {
							foreach($call_recordings as &$row) {
								$call_recording_name = $row['call_recording_name'];
								$call_recording_path = $row['call_recording_path'];
								if ($_SESSION['call_recordings']['storage_type']['text'] == 'base64' && $row['call_recording_base64'] != '') {
									file_put_contents($path.'/'.$call_recording_name, base64_decode($row['call_recording_base64']));
								}
								break;
							}
						}
						unset ($sql, $prep_statement, $call_recordings);
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
						//exit;
						//echo "file_ext: ".$file_ext."<br />\n";
						//echo "type: ".$_GET['t']."<br />\n";
						//exit;
						header('Content-Disposition: attachment; filename="'.$call_recording_name.'"');
						header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
						header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
						// header("Content-Length: " . filesize($full_recording_path));
						ob_clean();
						fpassthru($fd);
					}

				//if base64, remove temp recording file
					if ($_SESSION['call_recordings']['storage_type']['text'] == 'base64' && $row['call_recording_base64'] != '') {
						@unlink($full_recording_path);
					}
//			}
		} //end download method
	}  //end the class
}

/*
$obj = new call_recordings;
$obj->download('all');
*/

?>