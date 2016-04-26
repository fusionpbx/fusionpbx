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
 Portions created by the Initial Developer are Copyright (C) 2008-2016
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
*/

//define the voicemail class
	class voicemail {
		public $db;
		public $domain_uuid;
		public $domain_name;
		public $voicemail_uuid;
		public $voicemail_id;
		public $voicemail_message_uuid;
		public $order_by;
		public $order;

		public function voicemails() {
			//set the voicemail_uuid
				if (strlen($_REQUEST["id"]) > 0) {
					$voicemail_uuid = check_str($_REQUEST["id"]);
				}

			//set the voicemail id and voicemail uuid arrays
				if (isset($_SESSION['user']['extension'])) foreach ($_SESSION['user']['extension'] as $index => $row) {
					if (strlen($row['number_alias']) > 0) {
						$voicemail_ids[$index]['voicemail_id'] = $row['number_alias'];
					}
					else {
						$voicemail_ids[$index]['voicemail_id'] = $row['user'];
					}
				}
				if (isset($_SESSION['user']['voicemail'])) foreach ($_SESSION['user']['voicemail'] as $row) {
					if (strlen($row['voicemail_uuid']) > 0) {
						$voicemail_uuids[]['voicemail_uuid'] = $row['voicemail_uuid'];
					}
				}

			//get the uuid and voicemail_id
				$sql = "select * from v_voicemails ";
				$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
				if (strlen($this->voicemail_uuid) > 0) {
					if (permission_exists('voicemail_delete')) {
						//view specific voicemail box usually reserved for an admin or superadmin
						$sql .= "and voicemail_uuid = '".$this->voicemail_uuid."' ";
					}
					else {
						//ensure that the requested voicemail box is assigned to this user
						$found = false;
						foreach($voicemail_uuids as $row) {
							if ($voicemail_uuid == $row['voicemail_uuid']) {
								$sql .= "and voicemail_uuid = '".$row['voicemail_uuid']."' ";
								$found = true;
							}
							$x++;
						}
						//id requested is not owned by the user return no results
						if (!$found) {
							$sql .= "and voicemail_uuid is null ";
						}
					}
				}
				else {
					$x = 0;
					if (count($voicemail_ids) > 0) {
						//show only the assigned voicemail ids
						$sql .= "and (";
						foreach($voicemail_ids as $row) {
							if ($x == 0) {
								$sql .= "voicemail_id = '".$row['voicemail_id']."' ";
							}
							else {
								$sql .= " or voicemail_id = '".$row['voicemail_id']."'";
							}
							$x++;
						}
						$sql .= ")";
					}
					else {
						//no assigned voicemail ids so return no results
						$sql .= "and voicemail_uuid is null ";
					}
				}
				$sql .= "order by voicemail_id asc ";
				$prep_statement = $this->db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				unset ($prep_statement);
				return $result;
		}

		public function messages() {
			//get the voicemails
				$voicemails = $this->voicemails();

			//add the voicemail messages to the array
				foreach ($voicemails as &$row) {
					//get the voicemail messages
					$this->voicemail_uuid = $row['voicemail_uuid'];
					$this->voicemail_id = $row['voicemail_id'];
					$result = $this->voicemail_messages();
					$voicemail_count = count($result);
					$row['messages'] = $result;
				}

			//return the array
				return $voicemails;
		}

		public function voicemail_messages() {
			$sql = "select * from v_voicemail_messages as m, v_voicemails as v ";
			$sql .= "where m.domain_uuid = '$this->domain_uuid' ";
			$sql .= "and m.voicemail_uuid = v.voicemail_uuid ";
			if (is_array($this->voicemail_id)) {
				$sql .= "and (";
				$x = 0;
				foreach($this->voicemail_id as $row) {
					if ($x > 0) {
						$sql .= "or ";
					}
					$sql .= "v.voicemail_id = '".$row['voicemail_id']."' ";
					$x++;
				}
				$sql .= ") ";
			}
			else {
				$sql .= "and v.voicemail_id = '$this->voicemail_id' ";
			}
			if (strlen($this->order_by) == 0) {
				$sql .= "order by v.voicemail_id, m.created_epoch desc ";
			}
			else {
				$sql .= "order by v.voicemail_id, m.$this->order_by $this->order ";
			}
			//$sql .= "limit $this->rows_per_page offset $this->offset ";
			$prep_statement = $this->db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
			$result_count = count($result);
			unset ($prep_statement, $sql);
			if ($result_count > 0) {
				foreach($result as &$row) {
					//set the greeting directory
					$path = $_SESSION['switch']['voicemail']['dir'].'/default/'.$_SESSION['domain_name'].'/'.$row['voicemail_id'];
					if (file_exists($path.'/msg_'.$row['voicemail_message_uuid'].'.wav')) {
						$row['file_path'] = $path.'/msg_'.$row['voicemail_message_uuid'].'.wav';
					}
					if (file_exists($path.'/msg_'.$row['voicemail_message_uuid'].'.mp3')) {
						$row['file_path'] = $path.'/msg_'.$row['voicemail_message_uuid'].'.mp3';
					}
					$row['file_size'] = filesize($row['file_path']);
					$row['file_size_label'] = byte_convert($row['file_size']);
					$row['file_ext'] = substr($row['file_path'], -3);

					$message_length = $row['message_length'];
					if ($message_length < 60 ) {
						$message_length = $message_length. " sec";
					}
					else {
						$message_length = round(($message_length/60), 2). " min";
					}
					$row['message_length_label'] = $message_length;
					$row['created_date'] = date("j M Y g:i a",$row['created_epoch']);
				}
			}
			return $result;
		}

		public function voicemail_delete() {
			//delete voicemail messages
				$this->message_delete();

			//delete voicemail recordings folder (includes greetings)
				$file_path = $_SESSION['switch']['voicemail']['dir']."/default/".$_SESSION['domain_name']."/".$this->voicemail_id;
				foreach (glob($file_path."/*.*") as $file_name) {
					unlink($file_name);
				}
				@rmdir($file_path);

			//delete voicemail destinations
				$sql = "delete from v_voicemail_destinations ";
				$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
				$sql .= "and voicemail_uuid = '".$this->voicemail_uuid."' ";
				$prep_statement = $this->db->prepare(check_sql($sql));
				$prep_statement->execute();
				unset($sql, $prep_statement);

			//delete voicemail greetings
				$sql = "delete from v_voicemail_greetings ";
				$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
				$sql .= "and voicemail_id = '".$this->voicemail_id."' ";
				$prep_statement = $this->db->prepare(check_sql($sql));
				$prep_statement->execute();
				unset($sql, $prep_statement);

			//delete voicemail options
				$sql = "delete from v_voicemail_options ";
				$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
				$sql .= "and voicemail_uuid = '".$this->voicemail_uuid."' ";
				$prep_statement = $this->db->prepare(check_sql($sql));
				$prep_statement->execute();
				unset($sql, $prep_statement);

			//delete voicemail
				$sql = "delete from v_voicemails ";
				$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
				$sql .= "and voicemail_uuid = '".$this->voicemail_uuid."' ";
				$prep_statement = $this->db->prepare(check_sql($sql));
				$prep_statement->execute();
				unset($sql, $prep_statement);
		}

		public function message_count() {
			$sql = "select count(*) as num_rows from v_voicemail_messages ";
			$sql .= "where domain_uuid = '$this->domain_uuid' ";
			$sql .= "and voicemail_uuid = '$this->voicemail_uuid' ";
			$prep_statement = $this->db->prepare($sql);
			if ($prep_statement) {
			$prep_statement->execute();
				$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
				if ($row['num_rows'] > 0) {
					$num_rows = $row['num_rows'];
				}
				else {
					$num_rows = '0';
				}
			}
			return $num_rows;
		}

		public function message_waiting() {
			//send the message waiting status
				$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
				if ($fp) {
					$switch_cmd .= "luarun app.lua voicemail mwi ".$this->voicemail_id."@".$_SESSION['domain_name'];
					$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
				}
		}

		public function message_delete() {
			//get the voicemail_id
				if (!isset($this->voicemail_id)) {
					$sql = "select voicemail_id from v_voicemails ";
					$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
					$sql .= "and voicemail_uuid = '".$this->voicemail_uuid."' ";
					$prep_statement = $this->db->prepare(check_sql($sql));
					$prep_statement->execute();
					$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
					foreach ($result as &$row) {
						$this->voicemail_id = $row["voicemail_id"];
					}
					unset ($prep_statement);
				}

			//delete the recording
				$file_path = $_SESSION['switch']['voicemail']['dir']."/default/".$_SESSION['domain_name']."/".$this->voicemail_id;
				if ($this->voicemail_message_uuid != '') {
					foreach (
						glob($file_path."/msg_".$this->voicemail_message_uuid.".*") as $file_name) { unlink($file_name);
					}
				}
				else {
					foreach (
						glob($file_path."/msg_*.*") as $file_name) { unlink($file_name); //remove all recordings
					}
				}

			//delete voicemail message(s)
				$sql = "delete from v_voicemail_messages ";
				$sql .= "where domain_uuid = '".$this->domain_uuid."' ";
				$sql .= "and voicemail_uuid = '".$this->voicemail_uuid."' ";
				if ($this->voicemail_message_uuid != '') {
					$sql .= "and voicemail_message_uuid = '".$this->voicemail_message_uuid."' ";
				}
				$prep_statement = $this->db->prepare(check_sql($sql));
				$prep_statement->execute();
				unset($sql);

			//check the message waiting status
				$this->message_waiting();
		}

		public function message_toggle() {
			//get the voicemail_id
				if (!isset($this->voicemail_id)) {
					$sql = "select voicemail_id from v_voicemails ";
					$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
					$sql .= "and voicemail_uuid = '".$this->voicemail_uuid."' ";
					$prep_statement = $this->db->prepare(check_sql($sql));
					$prep_statement->execute();
					$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
					foreach ($result as &$row) {
						$this->voicemail_id = $row["voicemail_id"];
					}
					unset ($prep_statement);
				}

			//get message status
				$sql = "select message_status from v_voicemail_messages ";
				$sql .= "where domain_uuid = '".$this->domain_uuid."' ";
				$sql .= "and voicemail_uuid = '".$this->voicemail_uuid."' ";
				$sql .= "and voicemail_message_uuid = '".$this->voicemail_message_uuid."' ";
				$prep_statement = $this->db->prepare(check_sql($sql));
				$prep_statement->execute();
				$row = $prep_statement->fetch(PDO::FETCH_NAMED);
				$new_status = ($row['message_status'] == 'saved') ? 'null' : "'saved'";
				unset($sql, $prep_statement, $row);

			//set message status
				$sql = "update v_voicemail_messages set ";
				$sql .= "message_status = ".$new_status." ";
				$sql .= "where domain_uuid = '".$this->domain_uuid."' ";
				$sql .= "and voicemail_uuid = '".$this->voicemail_uuid."' ";
				$sql .= "and voicemail_message_uuid = '".$this->voicemail_message_uuid."' ";
				$prep_statement = $this->db->prepare(check_sql($sql));
				$prep_statement->execute();
				unset($sql, $prep_statement);

			//check the message waiting status
				$this->message_waiting();
		}


		public function message_saved() {
			//set the voicemail status to saved
				$sql = "update v_voicemail_messages set ";
				$sql .= "message_status = 'saved' ";
				$sql .= "where domain_uuid = '".$this->domain_uuid."' ";
				$sql .= "and voicemail_uuid = '".$this->voicemail_uuid."' ";
				$sql .= "and voicemail_message_uuid = '".$this->voicemail_message_uuid."' ";
				$prep_statement = $this->db->prepare(check_sql($sql));
				$prep_statement->execute();
				unset($sql, $prep_statement);

			//check the message waiting status
				$this->message_waiting();
		}

		public function message_download() {

			//change the message status
				$this->message_saved();

			//clear the cache
				session_cache_limiter('public');

			//set source folder path
				$path = $_SESSION['switch']['voicemail']['dir'].'/default/'.$_SESSION['domain_name'].'/'.$this->voicemail_id;

			//prepare base64 content from db, if enabled
				if ($_SESSION['voicemail']['storage_type']['text'] == 'base64') {
					$sql = "select message_base64 from ";
					$sql .= "v_voicemail_messages as m, ";
					$sql .= "v_voicemails as v ";
					$sql .= "where ";
					$sql .= "m.voicemail_uuid = v.voicemail_uuid ";
					$sql .= "and v.voicemail_id = '".$this->voicemail_id."' ";
					$sql .= "and m.voicemail_uuid = '".$this->voicemail_uuid."' ";
					$sql .= "and m.domain_uuid = '".$this->domain_uuid."' ";
					$sql .= "and m.voicemail_message_uuid = '".$this->voicemail_message_uuid."' ";
					$prep_statement = $this->db->prepare(check_sql($sql));
					$prep_statement->execute();
					$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
					if (count($result) > 0) {
						foreach($result as &$row) {
							if ($row['message_base64'] != '') {
								$message_decoded = base64_decode($row['message_base64']);
								file_put_contents($path.'/msg_'.$this->voicemail_message_uuid.'.ext', $message_decoded);
								$finfo = finfo_open(FILEINFO_MIME_TYPE); //determine mime type (requires PHP >= 5.3.0, must be manually enabled on Windows)
								$file_mime = finfo_file($finfo, $path.'/msg_'.$this->voicemail_message_uuid.'.ext');
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
								rename($path.'/msg_'.$this->voicemail_message_uuid.'.ext', $path.'/msg_'.$this->voicemail_message_uuid.'.'.$file_ext);
							}
							break;
						}
					}
					unset ($sql, $prep_statement, $result, $message_decoded);
				}

			//prepare and stream the file
				if (file_exists($path.'/msg_'.$this->voicemail_message_uuid.'.wav')) {
					$file_path = $path.'/msg_'.$this->voicemail_message_uuid.'.wav';
				}
				if (file_exists($path.'/msg_'.$this->voicemail_message_uuid.'.mp3')) {
					$file_path = $path.'/msg_'.$this->voicemail_message_uuid.'.mp3';
				}
				if ($file_path != '') {
					$fd = fopen($file_path, "rb");
					if ($_GET['t'] == "bin") {
						header("Content-Type: application/force-download");
						header("Content-Type: application/octet-stream");
						header("Content-Type: application/download");
						header("Content-Description: File Transfer");
						$file_ext = substr($file_path, -3);
						if ($file_ext == "wav") {
							header('Content-Disposition: attachment; filename="msg_'.$this->voicemail_message_uuid.'.wav"');
						}
						if ($file_ext == "mp3") {
							header('Content-Disposition: attachment; filename="msg_'.$this->voicemail_message_uuid.'.mp3"');
						}
					}
					else {
						$file_ext = substr($file_path, -3);
						if ($file_ext == "wav") {
							header("Content-Type: audio/wav");
						}
						if ($file_ext == "mp3") {
							header("Content-Type: audio/mpeg");
						}
					}
					header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
					header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // date in the past
					header("Content-Length: " . filesize($file_path));
					ob_end_clean();
					fpassthru($fd);
				}

			//if base64, remove temp file
				if ($_SESSION['voicemail']['storage_type']['text'] == 'base64') {
					@unlink($path.'/msg_'.$this->voicemail_message_uuid.'.'.$file_ext);
				}

		} // download
	}

//example voicemail messages
	//require_once "app/voicemails/resources/classes/voicemail.php";
	//$voicemail = new voicemail;
	//$voicemail->db = $db;
	//$voicemail->domain_uuid = $_SESSION['domain_uuid'];
	//$voicemail->voicemail_uuid = $voicemail_uuid;
	//$voicemail->voicemail_id = $voicemail_id;
	//$voicemail->order_by = $order_by;
	//$voicemail->order = $order;
	//$result = $voicemail->messages();
	//$result_count = count($result);

/*
Array
(
    [user] => 1002
    [extension_uuid] => e163fc03-f180-459b-aa12-7ed87fcb6e2c
    [outbound_caller_id_name] => FusionPBX
    [outbound_caller_id_number] => 12084024632
)
Array
(
    [user] => 1020
    [extension_uuid] => ecfb23df-7c59-4286-891e-2abdc48856ac
    [outbound_caller_id_name] => Mark J Crane
    [outbound_caller_id_number] => 12084024632
)

foreach ($_SESSION['user']['extension'] as $value) {
	if (strlen($value['user']) > 0) {

	}
}
*/

?>