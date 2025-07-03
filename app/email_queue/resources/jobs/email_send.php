<?php

//check the permission
	if (defined('STDIN')) {
		//includes files
		require_once dirname(__DIR__, 4) . "/resources/require.php";
	}
	else {
		exit;
	}

//include files
	include_once "resources/phpmailer/class.phpmailer.php";
	include_once "resources/phpmailer/class.smtp.php";

//increase limits
	set_time_limit(0);
	//ini_set('max_execution_time',1800); //30 minutes
	ini_set('memory_limit', '512M');

//connect to the database
	$database = database::new();

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
		$debug = $_GET['debug'] ?? null;
		$sleep_seconds = $_GET['sleep'] ?? null;
	}
	else {
		//invalid uuid
		exit;
	}

//define the process id file
	$pid_file = '/var/run/fusionpbx/email_send.'.$email_queue_uuid.'.pid';
	//echo "pid_file: ".$pid_file."\n";

//function to check if the process exists
	function process_exists($file = '') {
		//check if the file exists return false if not found
		if (!file_exists($file)) {
			return false;
		}

		//check to see if the process id is valid
		$pid = file_get_contents($file);
		if (filter_var($pid, FILTER_VALIDATE_INT) === false) {
			return false;
		}

		//check if the process is running
		exec('ps -p '.$pid, $output);
		if (count($output) > 1) {
			return true;
		}
		else {
			return false;
		}
	}

//check to see if the process exists
	$pid_exists = process_exists($pid_file);

//prevent the process running more than once
	if ($pid_exists) {
		echo "Cannot lock pid file {$pid_file}\n";
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
		if (!empty($pid_file) && file_exists($pid_file)) {
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

//get the email details to send
	$sql = "select * from v_email_queue ";
	$sql .= "where email_queue_uuid = :email_queue_uuid ";
	$parameters['email_queue_uuid'] = $email_queue_uuid;
	$row = $database->select($sql, $parameters, 'row');
	if (is_array($row)) {
		$domain_uuid = $row["domain_uuid"];
		$email_date = $row["email_date"];
		$email_from = $row["email_from"];
		$email_to = $row["email_to"];
		$email_subject = $row["email_subject"];
		$email_body = $row["email_body"];
		$email_transcription = $row["email_transcription"];
		$email_status = $row["email_status"];
		$email_retry_count = $row["email_retry_count"];
		$email_uuid = $row["email_uuid"];
		//$email_action_before = $row["email_action_before"];
		$email_action_after = $row["email_action_after"];
	}
	unset($parameters);

//get the email queue settings
	$settings = new settings(["database" => $database,"domain_uuid" => $domain_uuid]);

//get the email settings
	$retry_limit = $settings->get('email_queue', 'retry_limit');
	$transcribe_enabled = $settings->get('transcribe', 'enabled', false);
	$save_response = $settings->get('email_queue', 'save_response', false);

//set defaults
	if (empty($email_retry_count)) {
		$email_retry_count = 0;
	}

//get the voicemail details
	$sql = "select * from v_voicemails ";
	$sql .= "where voicemail_uuid in ( ";
	$sql .= "	select voicemail_uuid from v_voicemail_messages	";
	$sql .= "	where voicemail_message_uuid = :voicemail_message_uuid ";
	$sql .= ") ";
	$parameters['voicemail_message_uuid'] = $email_uuid;
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
		if (gettype($voicemail_transcription_enabled) === 'string') {
			$voicemail_transcription_enabled = ($voicemail_transcription_enabled === 'true') ? true : false;
		}
	}
	unset($parameters);

//get the attachments and add to the email
	$sql = "select * from v_email_queue_attachments ";
	$sql .= "where email_queue_uuid = :email_queue_uuid ";
	$parameters['email_queue_uuid'] = $email_queue_uuid;
	$email_queue_attachments = $database->select($sql, $parameters, 'all');
	if (is_array($email_queue_attachments) && @sizeof($email_queue_attachments) != 0) {
		foreach($email_queue_attachments as $i => $field) {

			$email_queue_attachment_uuid = $field['email_queue_attachment_uuid'];
			$domain_uuid = $field['domain_uuid'];
			$email_attachment_type = $field['email_attachment_type'];
			$email_attachment_path = $field['email_attachment_path'];
			$email_attachment_name = $field['email_attachment_name'];
			$email_attachment_mime_type = $field['email_attachment_mime_type'];

			if (empty($email_attachment_mime_type)) {
				switch ($email_attachment_type) {
					case "wav":
						$email_attachment_mime_type = "audio/x-wav";
						break;
					case "mp3":
						$email_attachment_mime_type = "audio/x-mp3";
						break;
					case "pdf":
						$email_attachment_mime_type = "application/pdf";
						break;
					case "tif":
					case "tiff":
						$email_attachment_mime_type = "image/tiff";
						break;
					default:
						$email_attachment_mime_type = "binary/octet-stream";
						break;
				}
			}

			if ($transcribe_enabled && isset($voicemail_transcription_enabled) && $voicemail_transcription_enabled) {
				//debug message
				echo "transcribe enabled: true\n";

				//if email transcription has a value no need to transcribe again so run the transcription when the value is empty
				if (empty($email_transcription)) {
					//add the settings object
					$transcribe_engine = $settings->get('transcribe', 'engine', '');

					//add the transcribe object and get the languages arrays
					if (!empty($transcribe_engine) && class_exists('transcribe_' . $transcribe_engine)) {
						$transcribe = new transcribe($settings);

						//transcribe the voicemail recording
						$transcribe->audio_path = $email_attachment_path;
						$transcribe->audio_filename = $email_attachment_name;
						$transcribe->audio_mime_type = $email_attachment_mime_type;
						$transcribe->audio_string = (!empty($field['email_attachment_base64'])) ? base64_decode($field['email_attachment_base64']) : '';
						$transcribe_message = $transcribe->transcribe();
					}
				}
				else {
					$transcribe_message = $email_transcription;
				}

				echo "transcribe message: ".$transcribe_message."\n";

				//prepare the email body
				$email_body = str_replace('${message_text}', $transcribe_message, $email_body);
			}
			else {
				//prepare the email body
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
			$email_attachments[$i]['cid'] = $field['email_attachment_cid'];
			$email_attachments[$i]['mime_type'] = $email_attachment_mime_type;
			$email_attachments[$i]['name'] = $email_attachment_name;
			$email_attachments[$i]['path'] = $email_attachment_path;
			$email_attachments[$i]['base64'] = $field['email_attachment_base64'];
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
	if (isset($voicemail_transcription_enabled) && $voicemail_transcription_enabled && isset($transcribe_message)) {
		$sql = "update v_voicemail_messages ";
		$sql .= "set message_transcription = :message_transcription ";
		$sql .= "where voicemail_message_uuid = :voicemail_message_uuid; ";
		$parameters['voicemail_message_uuid'] = $email_uuid;
		$parameters['message_transcription'] = $transcribe_message;
		//echo $sql."\n";
		//print_r($parameters);
		$database->execute($sql, $parameters);
		unset($parameters);
	}

//add email settings
	$email_settings = '';
	$email_setting_array = $settings->get('email');
	ksort($email_setting_array);
	foreach ($email_setting_array as $name => $value) {
		if ($name == 'smtp_password') { $value = '[REDACTED]'; }
		if (is_array($value)) {
			foreach($value as $sub_value) {
				$email_settings .= $name.': '.$sub_value."\n";
			}
		}
		else {
			$email_settings .= $name.': '.$value."\n";
		}
	}

//parse email and name
	if (!empty($email_from)) {
		if (valid_email($email_from)) {
			$email_from_address = $email_from;
		}
		else {
			$lt_pos = strpos($email_from, '<');
			if ($lt_pos !== false) {
				$email_from_address = str_replace('>', '', substr($email_from, $lt_pos + 1));
				$email_from_name = trim(substr($email_from, 0, $lt_pos));
			}
		}
	}

//send the email
	$email = new email;
	$email->domain_uuid = $domain_uuid;
	$email->from_address = $email_from_address;
	if (!empty($email_from_name)) {
		$email->from_name = $email_from_name;
	}
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
		if (isset($transcribe_message)) {
			$sql .= "email_body = :email_body, ";
			$sql .= "email_transcription = :email_transcription, ";
			$parameters['email_body'] = $email_body;
			$parameters['email_transcription'] = $transcribe_message;
		}
		if ($save_response) {
			$sql .= "email_response = :email_response, ";
			$parameters['email_response'] = $email_settings."\n".$email_response;
		}
		$sql .= "update_date = now() ";
		$sql .= "where email_queue_uuid = :email_queue_uuid; ";
		$parameters['email_queue_uuid'] = $email_queue_uuid;
		$database->execute($sql, $parameters);
		unset($parameters);

		//delete the email after it is sent
		if ($email_action_after == 'delete') {
			//delay the delete by a few seconds
			sleep(3);

			//remove the email file after it has been sent
			if (is_array($email_queue_attachments) && @sizeof($email_queue_attachments) != 0) {
				foreach ($email_queue_attachments as $field) {
					$email_attachment_path = $field['email_attachment_path'];
					$email_attachment_name = $field['email_attachment_name'];
					$email_attachment_name_no_prefix = str_replace(['msg_','intro_'], '', pathinfo($email_attachment_name, PATHINFO_BASENAME));
					@unlink($email_attachment_path.'/'.$email_attachment_name);
					@unlink($email_attachment_path.'/intro_'.$email_attachment_name_no_prefix);
					@unlink($email_attachment_path.'/msg_'.$email_attachment_name_no_prefix);
					@unlink($email_attachment_path.'/intro_msg_'.$email_attachment_name_no_prefix);
				}
			}

			//delete the voicemail message from the database
			$sql = "delete from v_voicemail_messages ";
			$sql .= "where voicemail_message_uuid = :voicemail_message_uuid; ";
			$parameters['voicemail_message_uuid'] = $email_uuid;
			//echo $sql."\n";
			//print_r($parameters);
			$database->execute($sql, $parameters);
			unset($parameters);

			//get the domain_name
			$sql = "select domain_name from v_domains ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$parameters['domain_uuid'] = $domain_uuid;
			$domain_name = $database->select($sql, $parameters, 'column');

			//send the message waiting status
			$esl = event_socket::create();
			if ($esl->is_connected()) {
				//$switch_cmd .= "luarun app.lua voicemail mwi ".$voicemail_id."@".$domain_name;
				$switch_cmd .= "luarun app/voicemail/resources/scripts/mwi_notify.lua $voicemail_id $domain_name 0 0";
				$switch_result = event_socket::api($switch_cmd);
				echo $switch_cmd."\n";
			}
			else {
				echo "event socket connection failed\n";
			}
		}

		if ($settings->get('voicemail', 'storage_type') == 'base64') {
			//delay the delete by a few seconds
			sleep(3);

			//remove message files after email sent
			if (is_array($email_queue_attachments) && @sizeof($email_queue_attachments) != 0) {
				foreach ($email_queue_attachments as $field) {
					$email_attachment_path = $field['email_attachment_path'];
					$email_attachment_name = $field['email_attachment_name'];
					$email_attachment_name_no_prefix = str_replace(['msg_','intro_'], '', pathinfo($email_attachment_name, PATHINFO_BASENAME));
					@unlink($email_attachment_path.'/'.$email_attachment_name);
					@unlink($email_attachment_path.'/intro_'.$email_attachment_name_no_prefix);
					@unlink($email_attachment_path.'/msg_'.$email_attachment_name_no_prefix);
					@unlink($email_attachment_path.'/intro_msg_'.$email_attachment_name_no_prefix);
				}
			}
		}


		/*
		//build insert array
			$array['email_queue'][0]['email_queue_uuid'] = $email_queue_uuid;
			//$array['email_queue'][0]['sent_date'] = 'now()';
			$array['email_queue'][0]['email_status'] = 'sent';

		//grant temporary permissions
			$p = permissions::new();
			$p->add('email_queue_add', 'temp');
			$p->add('email_queue_update', 'temp');
		//execute insert
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
		$p = permissions::new();
		$p->add('email_queue_add', 'temp');

		//execute insert
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
					$p = permissions::new();
					$p->add('email_log_add', 'temp');
				//execute insert
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
	//$esl = fopen(sys_get_temp_dir()."/mailer-app.log", "a");

//prepare the output buffers
	//ob_end_clean();
	//ob_start();

//message divider for log file
	//echo "\n\n====================================================\n\n";

//get and save the output from the buffer
	//$content = ob_get_contents(); //get the output from the buffer
	//$content = str_replace("<br />", "", $content);

	//ob_end_clean(); //clean the buffer

	//fwrite($esl, $content);
	//fclose($esl);
