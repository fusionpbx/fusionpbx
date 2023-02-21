<?php

//check the permission
	if (defined('STDIN')) {
		//set the include path
		$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
		set_include_path(parse_ini_file($conf[0])['document.root']);
	}
	else {
		exit;
	}

//include files
	require_once "resources/require.php";
	include "resources/classes/permissions.php";
	require $_SERVER['DOCUMENT_ROOT']."/app/email_queue/resources/functions/transcribe.php";

//increase limits
	set_time_limit(0);
	//ini_set('max_execution_time',1800); //30 minutes
	ini_set('memory_limit', '512M');

//save the arguments to variables
	$script_name = $argv[0];
	if (!empty($argv[1])) {
		parse_str($argv[1], $_GET);
	}
	//print_r($_GET);

//get the primary key
	if (is_uuid($_GET['email_queue_uuid'])) {
		$email_queue_uuid = $_GET['email_queue_uuid'];
		$hostname = urldecode($_GET['hostname']);
		$debug = $_GET['debug'];
		$sleep_seconds = $_GET['sleep'];
	}
	else {
		//invalid uuid
		exit;
	}

//define the process id file
	$pid_file = "/var/run/fusionpbx/email_send".".".$email_queue_uuid.".pid";
	//echo "pid_file: ".$pid_file."\n";

//function to check if the process exists
	function process_exists($file = false) {

		//set the default exists to false
		$exists = false;

		//check to see if the process is running
		if (file_exists($file)) {
			$pid = file_get_contents($file);
			if (posix_getsid($pid) === false) { 
				//process is not running
				$exists = false;
			}
			else {
				//process is running
				$exists = true;
			}
		}

		//return the result
		return $exists;
	}

//check to see if the process exists
	$pid_exists = process_exists($pid_file);

//prevent the process running more than once
	if ($pid_exists) {
		//echo "Cannot lock pid file {$pid_file}\n";
		exit;
	}

//make sure the /var/run/fusionpbx directory exists
	if (!file_exists('/var/run/fusionpbx')) {
		$result = mkdir('/var/run/fusionpbx', 0777, true);
		if (!$result) {
			die('Failed to create /var/run/fusionpbx');
		}
	}

//create the process id file if the process doesn't exist
	if (!$pid_exists) {
		//remove the old pid file
		if (file_exists($file)) {
			unlink($pid_file);
		}

		//show the details to the user
		//echo "The process id is ".getmypid()."\n";
		//echo "pid_file: ".$pid_file."\n";

		//save the pid file
		file_put_contents($pid_file, getmypid());
	}

//sleep used for debugging
	if (isset($sleep_seconds)) {
		sleep($sleep_seconds);
	}

//define a function to remove html tags
	if (!function_exists('remove_tags')) {
		function remove_tags($string) {
			//remove HTML tags
			$string = preg_replace ('/<[^>]*>/', ' ', $string);

			//remove control characters
			$string = str_replace("\r", '', $string);    // --- replace with empty space
			$string = str_replace("\n", ' ', $string);   // --- replace with space
			$string = str_replace("\t", ' ', $string);   // --- replace with space

			//remove multiple spaces
			$string = trim(preg_replace('/ {2,}/', ' ', $string));
			return $string;
		}
	}

//includes
	include_once "resources/phpmailer/class.phpmailer.php";
	include_once "resources/phpmailer/class.smtp.php";

//get the email details to send
	$sql = "select * from v_email_queue ";
	$sql .= "where email_queue_uuid = :email_queue_uuid ";
	$parameters['email_queue_uuid'] = $email_queue_uuid;
	$database = new database;
	$row = $database->select($sql, $parameters, 'row');
	if (is_array($row)) {
		$domain_uuid = $row["domain_uuid"];
		$email_date = $row["email_date"];
		$email_from = $row["email_from"];
		$email_to = $row["email_to"];
		$email_subject = $row["email_subject"];
		$email_body = $row["email_body"];
		$email_status = $row["email_status"];
		$email_retry_count = $row["email_retry_count"];
		$email_uuid = $row["email_uuid"];
		//$email_action_before = $row["email_action_before"];
		$email_action_after = $row["email_action_after"];
	}
	unset($parameters);

//get the call center settings
	$retry_limit = $_SESSION['email_queue']['retry_limit']['numeric'];
	//$retry_interval = $_SESSION['email_queue']['retry_interval']['numeric'];

//set defaults
	if (strlen($email_retry_count) == 0) {
		$email_retry_count = 0;
	}

//get the voicemail details
	$sql = "select * from v_voicemails ";
	$sql .= "where voicemail_uuid in ( ";
	$sql .= "	select voicemail_uuid from v_voicemail_messages	";
	$sql .= "	where voicemail_message_uuid = :voicemail_message_uuid ";
	$sql .= ") ";
	$parameters['voicemail_message_uuid'] = $email_uuid;
	$database = new database;
	$row = $database->select($sql, $parameters, 'row');
	if (is_array($row)) {
		//$domain_uuid = $row["domain_uuid"];
		//$voicemail_uuid = $row["voicemail_uuid"];
		$voicemail_id = $row["voicemail_id"];
		//$voicemail_password = $row["voicemail_password"];
		//$greeting_id = $row["greeting_id"];
		//$voicemail_alternate_greet_id = $row["voicemail_alternate_greet_id"];
		//$voicemail_mail_to = $row["voicemail_mail_to"];
		//$voicemail_sms_to  = $row["voicemail_sms_to "];
		$voicemail_transcription_enabled = $row["voicemail_transcription_enabled"];
		//$voicemail_attach_file = $row["voicemail_attach_file"];
		//$voicemail_file = $row["voicemail_file"];
		//$voicemail_local_after_email = $row["voicemail_local_after_email"];
		//$voicemail_enabled = $row["voicemail_enabled"];
		//$voicemail_description = $row["voicemail_description"];
		//$voicemail_name_base64 = $row["voicemail_name_base64"];
		//$voicemail_tutorial = $row["voicemail_tutorial"];
		echo "transcribe enabled: ".$voicemail_transcription_enabled."\n";
	}
	unset($parameters);

//get the attachments and add to the email
	$sql = "select * from v_email_queue_attachments ";
	$sql .= "where email_queue_uuid = :email_queue_uuid ";
	$parameters['email_queue_uuid'] = $email_queue_uuid;
	$database = new database;
	$email_queue_attachments = $database->select($sql, $parameters, 'all');
	if (is_array($email_queue_attachments) && @sizeof($email_queue_attachments) != 0) {
		foreach($email_queue_attachments as $field) {

			$email_queue_attachment_uuid = $field['email_queue_attachment_uuid'];
			$domain_uuid = $field['domain_uuid'];
			$email_attachment_type = $field['email_attachment_type'];
			$email_attachment_path = $field['email_attachment_path'];
			$email_attachment_name = $field['email_attachment_name'];
			//$email_attachment_base64= $field['email_attachment_base64'];

			switch ($email_attachment_type) {
				case "wav":
					$mime_type = "audio/x-wav";
					break;
				case "mp3":
					$mime_type = "audio/x-mp3";
					break;
				case "pdf":
					$mime_type = "application/pdf";
					break;
				case "tif":
					$mime_type = "image/tiff";
					break;
				case "tiff":
					$mime_type = "image/tiff";
					break;
				default:
					$mime_type = "binary/octet-stream";
					break;
			}

			if (isset($voicemail_transcription_enabled) && $voicemail_transcription_enabled == 'true') {
				//transcribe the attachment
				if ($email_attachment_type == 'wav' || $email_attachment_type == 'mp3') {
					$field = transcribe($email_attachment_path, $email_attachment_name, $email_attachment_type);
					echo "transcribe path: ".$email_attachment_path."\n";
					echo "transcribe name: ".$email_attachment_name."\n";
					echo "transcribe type: ".$email_attachment_type."\n";
					echo "transcribe command: ".$field['command']."\n";
					echo "transcribe message: ".$field['message']."\n";
					$transcribe_message = $field['message'];
				}

				//echo "email_body before: ".$email_body."\n";
				$email_body = str_replace('${message_text}', $transcribe_message, $email_body);
				//echo "email_body after: ".$email_body."\n";
				//unset($field);
			}
			else {
				$email_body = str_replace('${message_text}', '', $email_body);
			}

			//base64 encode the file
			//$file_contents = base64_encode(file_get_contents($email_attachment_path.'/'.$email_attachment_name));

			//add an attachment
			//public addAttachment ( string $path, string $name = '', string $encoding = 'base64', string $type = '', string $disposition = 'attachment' ) : boolean
			//$mail->AddAttachment($email_attachment_path.'/'.$email_attachment_name, $email_attachment_name, 'base64', 'attachment');

			//add email attachments as a string for the send_email function
			//$email_attachments[0]['type'] = 'string';
			//$email_attachments[0]['name'] = $email_attachment_path.'/'.$email_attachment_name;
			//$email_attachments[0]['value'] = base64_encode(file_get_contents($email_attachment_path.'/'.$email_attachment_name));

			//add email attachment as a file for the send_email function
			$email_attachments[0]['type'] = 'file';
			$email_attachments[0]['name'] = $email_attachment_name;
			$email_attachments[0]['value'] = $email_attachment_path.'/'.$email_attachment_name;
		}
	}
	unset($parameters);

//send context to the temp log
	echo "Subject: ".$email_subject."\n";
	echo "From: ".$email_from."\n";
	echo "Reply-to: ".$email_from."\n";
	echo "To: ".$email_to."\n";
	echo "Date: ".$email_date."\n";
	//echo "Transcript: ".$array['message']."\n";
	//echo "Body: ".$email_body."\n";

//update the message transcription
	if (isset($voicemail_transcription_enabled) && $voicemail_transcription_enabled == 'true' && isset($transcribe_message)) {
		$sql = "update v_voicemail_messages ";
		$sql .= "set message_transcription = :message_transcription ";
		$sql .= "where voicemail_message_uuid = :voicemail_message_uuid; ";
		$parameters['voicemail_message_uuid'] = $email_uuid;
		$parameters['message_transcription'] = $transcribe_message;
		//echo $sql."\n";
		//print_r($parameters);
		$database = new database;
		$database->execute($sql, $parameters);
		unset($parameters);
	}

//add email settings
	ksort($_SESSION['email']);
	foreach ($_SESSION['email'] as $name => $setting) {
		foreach ($setting as $type => $value) {
			if ($type == 'uuid') { $uuid = $value; continue; }
			if ($name == 'smtp_password') { $value = '[REDACTED]'; }
			$email_settings .= $name.': '.$value."\n";
		}
	}

//send the email
	$email = new email;
	$email->domain_uuid = $domain_uuid;
	$email->from_address = $email_from_address;
	$email->from_name = $email_from_name;
	$email->recipients = $email_to;
	$email->subject = $email_subject;
	$email->body = $email_body;
	$email->attachments = $email_attachments;
	$email->debug_level = 3;
	$email->method = 'direct';
	$email_status = $email->send();
	$email_error = $email->error;
	$email_response = $email->response;

//send the email
	if ($email_status) {

		//set the email status to sent
		$sql = "update v_email_queue ";
		$sql .= "set email_status = 'sent', ";
		//$sql .= "set email_status = 'waiting' "; //debug
		if (isset($transcribe_message)) {
			$sql .= "email_transcription = :email_transcription, ";
		}
		$sql .= "email_response = :email_response, ";
		$sql .= "update_date = now() ";
		$sql .= "where email_queue_uuid = :email_queue_uuid; ";
		$parameters['email_queue_uuid'] = $email_queue_uuid;
		$parameters['email_response'] = $email_settings."\n".$email_response;
		if (isset($transcribe_message)) {
			$parameters['email_transcription'] = $transcribe_message;
		}
		//echo $sql."\n";
		//print_r($parameters);
		$database = new database;
		$database->execute($sql, $parameters);
		unset($parameters);

		//delete the email after it is sent
		if ($email_action_after == 'delete') {
			//delay the delete by a few seconds
			sleep(3);

			//remove the email file after it has been sent
			if (is_array($email_queue_attachments) && @sizeof($email_queue_attachments) != 0) {
				foreach($email_queue_attachments as $field) {
					$email_attachment_path = $field['email_attachment_path'];
					$email_attachment_name = $field['email_attachment_name'];
					if (file_exists($email_attachment_path.'/'.$email_attachment_name)) {
						unlink($email_attachment_path.'/'.$email_attachment_name);
					}
				}
			}

			//delete the voicemail message from the database
			$sql = "delete from v_voicemail_messages ";
			$sql .= "where voicemail_message_uuid = :voicemail_message_uuid; ";
			$parameters['voicemail_message_uuid'] = $email_uuid;
			//echo $sql."\n";
			//print_r($parameters);
			$database = new database;
			$database->execute($sql, $parameters);
			unset($parameters);

			//get the domain_name
			$sql = "select domain_name from v_domains ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$parameters['domain_uuid'] = $domain_uuid;
			$database = new database;
			$domain_name = $database->select($sql, $parameters, 'column');

			//send the message waiting status
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
			if ($fp) {
				//$switch_cmd .= "luarun app.lua voicemail mwi ".$voicemail_id."@".$domain_name;
				$switch_cmd .= "luarun app/voicemail/resources/scripts/mwi_notify.lua ".$voicemail_id." ".$domain_name." 0 0";
				$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
				echo $switch_cmd."\n";
			}
			else {
				echo "event socket connection failed\n";
			}
		}

		/*
		//build insert array
			$array['email_queue'][0]['email_queue_uuid'] = $email_queue_uuid;
			//$array['email_queue'][0]['sent_date'] = 'now()';
			$array['email_queue'][0]['email_status'] = 'sent';

		//grant temporary permissions
			$p = new permissions;
			$p->add('email_queue_add', 'temp');
			$p->add('email_queue_update', 'temp');
		//execute insert
			$database = new database;
			$database->app_name = 'email_queue';
			$database->app_uuid = '5befdf60-a242-445f-91b3-2e9ee3e0ddf7';
			print_r($array);
			$message = $database->save($array);
			print_r($message);
			unset($array);
		//revoke temporary permissions
			$p->delete('email_queue_add', 'temp');
			$p->delete('email_queue_update', 'temp');
		*/

		//send a message to the console
			echo "Message sent!\n";

	}
	else {

		$mailer_error = $mail->ErrorInfo;
		echo "Mailer Error: ".$mailer_error."\n\n";

		/*
		//build insert array
		$email_log_uuid = uuid();
		$array['email_queue'][0]['email_queue_uuid'] = $email_queue_uuid;
		$array['email_queue'][0]['email_status'] = 'failed';

		//grant temporary permissions
		$p = new permissions;
		$p->add('email_queue_add', 'temp');

		//execute insert
		$database = new database;
		$database->app_name = 'email_queue';
		$database->app_uuid = 'ba41954e-9d21-4b10-bbc2-fa5ceabeb184';
		$database->save($array);
		unset($array);

		//revoke temporary permissions
		$p->delete('email_queue_add', 'temp');
		*/

		//set the email retry count
		$email_retry_count++;

		//set the email status to failed
		$sql = "update v_email_queue ";
		if ($email_retry_count >= $retry_limit) {
			$sql .= "set email_status = 'failed', ";
		}
		else {
			$sql .= "set email_status = 'trying', ";
		}
		$sql .= "email_response = :email_response, ";
		$sql .= "email_retry_count = :email_retry_count, ";
		$sql .= "update_date = now() ";
		$sql .= "where email_queue_uuid = :email_queue_uuid; ";
		$parameters['email_queue_uuid'] = $email_queue_uuid;
		$parameters['email_response'] = $email_settings."\n".$email_response;
		$parameters['email_retry_count'] = $email_retry_count;
		$database = new database;
		$database->execute($sql, $parameters);
		unset($parameters);

		/*
		$call_uuid = $headers["X-FusionPBX-Call-UUID"];
		if ($resend == true) {
			echo "Retained in v_email_logs \n";
		}
		else {
			// log/store message in database for review
			if (!isset($email_log_uuid)) {
				//build insert array
					$email_log_uuid = uuid();
					$array['email_logs'][0]['email_log_uuid'] = $email_log_uuid;
					if (is_uuid($call_uuid)) {
						$array['email_logs'][0]['call_uuid'] = $call_uuid;
					}
					$array['email_logs'][0]['domain_uuid'] = $headers["X-FusionPBX-Domain-UUID"];
					$array['email_logs'][0]['sent_date'] = 'now()';
					$array['email_logs'][0]['type'] = $headers["X-FusionPBX-Email-Type"];
					$array['email_logs'][0]['status'] = 'failed';
					$array['email_logs'][0]['email'] = str_replace("'", "''", $msg);
				//grant temporary permissions
					$p = new permissions;
					$p->add('email_log_add', 'temp');
				//execute insert
					$database = new database;
					$database->app_name = 'v_mailto';
					$database->app_uuid = 'ba41954e-9d21-4b10-bbc2-fa5ceabeb184';
					$database->save($array);
					unset($array);
				//revoke temporary permissions
					$p->delete('email_log_add', 'temp');
			}

			echo "Retained in v_email_logs as email_log_uuid = ".$email_log_uuid."\n";
		}
		*/

	}

//remove the old pid file
	if (file_exists($pid_file)) {
		unlink($pid_file);
	}

//unset the php mail object
	unset($mail);

//save output to
	//$fp = fopen(sys_get_temp_dir()."/mailer-app.log", "a");

//prepare the output buffers
	//ob_end_clean();
	//ob_start();

//message divider for log file
	//echo "\n\n====================================================\n\n";

//get and save the output from the buffer
	//$content = ob_get_contents(); //get the output from the buffer
	//$content = str_replace("<br />", "", $content);

	//ob_end_clean(); //clean the buffer

	//fwrite($fp, $content);
	//fclose($fp);

?>
