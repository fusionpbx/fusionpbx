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
		public $domain_uuid;
		public $domain_name;
		public $voicemail_uuid;
		public $voicemail_id;
		public $voicemail_message_uuid;
		public $order_by;
		public $order;
		public $app_uuid;

		public function __construct() {
			//set the application specific uuid
				$this->app_uuid = 'b523c2d2-64cd-46f1-9520-ca4b4098e044';

			//set the domain_uuid if not provided
				if (strlen($this->domain_uuid) == 0) {
					$this->domain_uuid = $_SESSION['domain_uuid'];
				}
		}

		public function __destruct() {
			foreach ($this as $key => $value) {
				unset($this->$key);
			}
		}
		
		public function get_voicemail_id() {

			//check if for valid input
				if (!is_uuid($this->voicemail_uuid) || !is_uuid($this->domain_uuid)) {
					return false;
				}

			//get the voicemail id if it isn't set already
				if (!isset($this->voicemail_id)) {
					$sql = "select voicemail_id from v_voicemails ";
					$sql .= "where domain_uuid = :domain_uuid ";
					$sql .= "and voicemail_uuid = :voicemail_uuid ";
					$parameters['domain_uuid'] = $this->domain_uuid;
					$parameters['voicemail_uuid'] = $this->voicemail_uuid;
					$database = new database;
					$voicemail_id = $database->select($sql, $parameters, 'column');
					if (is_numeric($voicemail_id)) {
						$this->voicemail_id = $voicemail_id;
					}
					unset($sql, $parameters, $voicemail_id);
				}
		}

		public function voicemails() {

			//check if for valid input
				if (!is_uuid($this->domain_uuid)) {
					return false;
				}

			//set the voicemail id and voicemail uuid arrays
				if (isset($_SESSION['user']['extension'])) {
					foreach ($_SESSION['user']['extension'] as $index => $row) {
						$voicemail_ids[$index]['voicemail_id'] = strlen($row['number_alias']) > 0 ? $row['number_alias'] : $row['user'];
					}
				}
				if (isset($_SESSION['user']['voicemail'])) {
					foreach ($_SESSION['user']['voicemail'] as $row) {
						if (strlen($row['voicemail_uuid']) > 0) {
							$voicemail_uuids[]['voicemail_uuid'] = $row['voicemail_uuid'];
						}
					}
				}

			//get the uuid and voicemail_id
				$sql = "select * from v_voicemails ";
				$sql .= "where domain_uuid = :domain_uuid ";
				if (is_uuid($this->voicemail_uuid)) {
					if (permission_exists('voicemail_delete')) {
						//view specific voicemail box usually reserved for an admin or superadmin
						$sql .= "and voicemail_uuid = :voicemail_uuid ";
						$parameters['voicemail_uuid'] = $this->voicemail_uuid;
					}
					else {
						//ensure that the requested voicemail box is assigned to this user
						$found = false;
						if (is_array($voicemail_uuids)) {
							foreach($voicemail_uuids as $row) {
								if ($voicemail_uuid == $row['voicemail_uuid']) {
									$sql .= "and voicemail_uuid = :voicemail_uuid ";
									$parameters['voicemail_uuid'] = $row['voicemail_uuid'];
									$found = true;
								}
							}
						}
						//id requested is not owned by the user return no results
						if (!$found) {
							$sql .= "and voicemail_uuid is null ";
						}
					}
				}
				else {
					if (is_array($voicemail_ids) && @sizeof($voicemail_ids) != 0) {
						//show only the assigned voicemail ids
						$x = 0;
						$sql .= "and ( ";
						foreach($voicemail_ids as $row) {
							$sql_where_or[] = "voicemail_id = :voicemail_id_".$x;
							$parameters['voicemail_id_'.$x] = $row['voicemail_id'];
							$x++;
						}
						$sql .= implode(' or ', $sql_where_or);
						$sql .= ") ";
						unset($sql_where_or);
					}
					else {
						//no assigned voicemail ids so return no results
						$sql .= "and voicemail_uuid is null ";
					}
				}
				$sql .= "order by voicemail_id asc ";
				$parameters['domain_uuid'] = $this->domain_uuid;
				$database = new database;
				$result = $database->select($sql, $parameters, 'all');
				unset($sql, $parameters);
				return $result;
		}

		public function messages() {
			//get the voicemails
				$voicemails = $this->voicemails();

			//add the voicemail messages to the array
				if (is_array($voicemails)) {
					foreach ($voicemails as &$row) {
						//get the voicemail messages
						$this->voicemail_uuid = $row['voicemail_uuid'];
						$this->voicemail_id = $row['voicemail_id'];
						$result = $this->voicemail_messages();
						$voicemail_count = count($result);
						$row['messages'] = $result;
					}
				}

			//return the array
				return $voicemails;
		}

		public function voicemail_messages() {

			//check if for valid input
				if (!is_numeric($this->voicemail_id) || !is_uuid($this->domain_uuid)) {
					return false;
				}

			//get the message from the database
				$sql = "select * from v_voicemail_messages as m, v_voicemails as v ";
				$sql .= "where m.domain_uuid = :domain_uuid ";
				$sql .= "and m.voicemail_uuid = v.voicemail_uuid ";
				if (is_array($this->voicemail_id) && @sizeof($this->voicemail_id) != 0) {
					$x = 0;
					$sql .= "and ( ";
					foreach ($this->voicemail_id as $row) {
						$sql_where_or[] = "v.voicemail_id = :voicemail_id_".$x;
						$parameters['voicemail_id_'.$x] = $row['voicemail_id'];
						$x++;
					}
					$sql .= implode(' or ', $sql_where_or);
					$sql .= ") ";
					unset($sql_where_or);
				}
				else {
					$sql .= "and v.voicemail_id = :voicemail_id ";
					$parameters['voicemail_id'] = $this->voicemail_id;
				}
				if (strlen($this->order_by) == 0) {
					$sql .= "order by v.voicemail_id, m.created_epoch desc ";
				}
				else {
					$sql .= "order by v.voicemail_id, m.".$this->order_by." ".$this->order." ";
				}
				$parameters['domain_uuid'] = $this->domain_uuid;
				$database = new database;
				$result = $database->select($sql, $parameters, 'all');
				unset($sql, $parameters);
			
			//update the array with additional information
				if (is_array($result)) {
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

			//get the voicemail id
				$this->get_voicemail_id();

			//check if for valid input
				if (!is_uuid($this->voicemail_uuid) || !is_uuid($this->domain_uuid)) {
					return false;
				}

			//delete voicemail messages
				$this->message_delete();

			//delete voicemail recordings folder (includes greetings)
				if (is_numeric($this->voicemail_id)) {
					$file_path = $_SESSION['switch']['voicemail']['dir']."/default/".$_SESSION['domain_name']."/".$this->voicemail_id;
					foreach (glob($file_path."/*.*") as $file_name) {
						unlink($file_name);
					}
					@rmdir($file_path);
				}

			//build voicemail destinations delete array
				$array['voicemail_destinations'][0]['domain_uuid'] = $this->domain_uuid;
				$array['voicemail_destinations'][0]['voicemail_uuid'] = $this->voicemail_uuid;

			//build voicemail greetings delete array
				if (is_numeric($this->voicemail_id)) {
					$array['voicemail_greetings'][0]['domain_uuid'] = $this->domain_uuid;
					$array['voicemail_greetings'][0]['voicemail_id'] = $this->voicemail_id;
				}

			//build voicemail options delete array
				$array['voicemail_options'][0]['domain_uuid'] = $this->domain_uuid;
				$array['voicemail_options'][0]['voicemail_uuid'] = $this->voicemail_uuid;

			//build voicemail delete array
				$array['voicemails'][0]['domain_uuid'] = $this->domain_uuid;
				$array['voicemails'][0]['voicemail_uuid'] = $this->voicemail_uuid;

			//grant temporary permissions
				$p = new permissions;
				$p->add('voicemail_destination_delete', 'temp');
				if (is_numeric($this->voicemail_id)) {
					$p->add('voicemail_greeting_delete', 'temp');
				}
				$p->add('voicemail_option_delete', 'temp');
				$p->add('voicemail_delete', 'temp');

			//execute delete
				$database = new database;
				$database->app_name = 'voicemails';
				$database->app_uuid = 'b523c2d2-64cd-46f1-9520-ca4b4098e044';
				$database->delete($array);
				unset($array);

			//revoke temporary permissions
				$p->delete('voicemail_destination_delete', 'temp');
				if (is_numeric($this->voicemail_id)) {
					$p->delete('voicemail_greeting_delete', 'temp');
				}
				$p->delete('voicemail_option_delete', 'temp');
				$p->delete('voicemail_delete', 'temp');

		}

		public function message_count() {

			//check if for valid input
				if (!is_uuid($this->voicemail_uuid) || !is_uuid($this->domain_uuid)) {
					return false;
				}
		
			//return the message count
				$sql = "select count(*) from v_voicemail_messages ";
				$sql .= "where domain_uuid = :domain_uuid ";
				$sql .= "and voicemail_uuid = :voicemail_uuid ";
				$parameters['domain_uuid'] = $this->domain_uuid;
				$parameters['voicemail_uuid'] = $this->voicemail_uuid;
				$database = new database;
				return $database->select($sql, $parameters, 'column');
				unset($sql, $parameters);

		}

		public function message_waiting() {
			//get the voicemail id
				$this->get_voicemail_id();

			//send the message waiting status
				$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
				if ($fp) {
					$switch_cmd .= "luarun app.lua voicemail mwi ".$this->voicemail_id."@".$_SESSION['domain_name'];
					$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
				}
		}

		public function message_delete() {

			//get the voicemail id
				$this->get_voicemail_id();

			//check if for valid input
				if (!is_numeric($this->voicemail_id)
					|| !is_uuid($this->voicemail_uuid)
					|| !is_uuid($this->domain_uuid)
					|| !is_uuid($this->voicemail_message_uuid)
					) {
					return false;
				}

			//delete the recording
				$file_path = $_SESSION['switch']['voicemail']['dir']."/default/".$_SESSION['domain_name']."/".$this->voicemail_id;
				if (is_uuid($this->voicemail_message_uuid)) {
					foreach (glob($file_path."/intro_".$this->voicemail_message_uuid.".*") as $file_name) {
						unlink($file_name);
					}
					foreach (glob($file_path."/msg_".$this->voicemail_message_uuid.".*") as $file_name) {
						unlink($file_name);
					}
				}
				else {
					foreach (glob($file_path."/msg_*.*") as $file_name) { 
						unlink($file_name); //remove all recordings
					}
				}

			//build delete array
				$array['voicemail_messages'][0]['domain_uuid'] = $this->domain_uuid;
				$array['voicemail_messages'][0]['voicemail_uuid'] = $this->voicemail_uuid;
				if (is_uuid($this->voicemail_message_uuid)) {
					$array['voicemail_messages'][0]['voicemail_message_uuid'] = $this->voicemail_message_uuid;
				}

			//grant temporary permissions
				$p = new permissions;
				$p->add('voicemail_message_delete', 'temp');

			//execute delete
				$database = new database;
				$database->app_name = 'voicemails';
				$database->app_uuid = 'b523c2d2-64cd-46f1-9520-ca4b4098e044';
				$database->delete($array);
				unset($array);

			//revoke temporary permissions
				$p->delete('voicemail_message_delete', 'temp');

			//check the message waiting status
				$this->message_waiting();
		}

		public function message_toggle() {

			//check if for valid input
				if (!is_uuid($this->voicemail_uuid)
					|| !is_uuid($this->domain_uuid)
					|| !is_uuid($this->voicemail_message_uuid)
					) {
					return false;
				}

			//get message status
				$sql = "select message_status from v_voicemail_messages ";
				$sql .= "where voicemail_message_uuid = :voicemail_message_uuid ";
				$parameters['voicemail_message_uuid'] = $this->voicemail_message_uuid;
				$database = new database;
				$new_status = $database->select($sql, $parameters, 'column') != 'saved' ? 'saved' : null;
				unset($sql, $parameters);

			//build message status update array
				$array['voicemail_messages'][0]['voicemail_message_uuid'] = $this->voicemail_message_uuid;
				$array['voicemail_messages'][0]['message_status'] = $new_status;

			//grant temporary permissions
				$p = new permissions;
				$p->add('voicemail_message_edit', 'temp');

			//execute update
				$database = new database;
				$database->app_name = 'voicemails';
				$database->app_uuid = 'b523c2d2-64cd-46f1-9520-ca4b4098e044';
				$database->save($array);
				unset($array);

			//revoke temporary permissions
				$p->delete('voicemail_message_edit', 'temp');

			//check the message waiting status
				$this->message_waiting();
		}

		public function message_saved() {

			//check if for valid input
				if (!is_uuid($this->voicemail_uuid)
					|| !is_uuid($this->domain_uuid)
					|| !is_uuid($this->voicemail_message_uuid)
					) {
					return false;
				}

			//build message status update array
				$array['voicemail_messages'][0]['voicemail_message_uuid'] = $this->voicemail_message_uuid;
				$array['voicemail_messages'][0]['message_status'] = 'saved';

			//grant temporary permissions
				$p = new permissions;
				$p->add('voicemail_message_edit', 'temp');

			//execute update
				$database = new database;
				$database->app_name = 'voicemails';
				$database->app_uuid = 'b523c2d2-64cd-46f1-9520-ca4b4098e044';
				$database->save($array);
				unset($array);

			//revoke temporary permissions
				$p->delete('voicemail_message_edit', 'temp');

			//check the message waiting status
				$this->message_waiting();
		}

		public function message_download() {

			//check if for valid input
				if (!is_numeric($this->voicemail_id)
					|| !is_uuid($this->voicemail_uuid)
					|| !is_uuid($this->domain_uuid)
					|| !is_uuid($this->voicemail_message_uuid)
					) {
					return false;
				}

			//change the message status
				$this->message_saved();

			//clear the cache
				session_cache_limiter('public');

			//set source folder path
				$path = $_SESSION['switch']['voicemail']['dir'].'/default/'.$_SESSION['domain_name'].'/'.$this->voicemail_id;

			//prepare base64 content from db, if enabled
				if ($_SESSION['voicemail']['storage_type']['text'] == 'base64') {
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
					$parameters['voicemail_id'] = $this->voicemail_id;
					$parameters['voicemail_uuid'] = $this->voicemail_uuid;
					$parameters['domain_uuid'] = $this->domain_uuid;
					$parameters['voicemail_message_uuid'] = $this->voicemail_message_uuid;
					$database = new database;
					$message_base64 = $database->select($sql, $parameters, 'column');
					if ($message_base64 != '') {
						$message_decoded = base64_decode($message_base64);
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
					unset($sql, $parameters, $message_base64, $message_decoded);
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

		}

	}

//example voicemail messages
	//require_once "app/voicemails/resources/classes/voicemail.php";
	//$voicemail = new voicemail;
	//$voicemail->db = $db;
	//$voicemail->voicemail_uuid = $voicemail_uuid;
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
    [outbound_caller_id_number] => 12089068227
)
Array
(
    [user] => 1020
    [extension_uuid] => ecfb23df-7c59-4286-891e-2abdc48856ac
    [outbound_caller_id_name] => Mark J Crane
    [outbound_caller_id_number] => 12089068227
)

foreach ($_SESSION['user']['extension'] as $value) {
	if (strlen($value['user']) > 0) {

	}
}
*/

?>