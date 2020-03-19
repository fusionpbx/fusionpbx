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
	Portions created by the Initial Developer are Copyright (C) 2008-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//set the include path
	if (defined('STDIN')) {
		$document_root = str_replace("\\", "/", $_SERVER["PHP_SELF"]);
		preg_match("/^(.*)\/secure\/.*$/", $document_root, $matches);
		$document_root = $matches[1];
		set_include_path($document_root);
		$_SERVER["DOCUMENT_ROOT"] = $document_root;
	}

//includes
	if (!defined('STDIN')) { include_once "root.php"; }
	require_once "resources/require.php";

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

//set init settings
	ini_set('max_execution_time',1800); //30 minutes
	ini_set('memory_limit', '512M');

//listen for standard input
	if ($msg == '') {
		$fd = fopen("php://stdin", "r");
		$msg = file_get_contents ("php://stdin");
		fclose($fd);
	}

//save output to
	$fp = fopen(sys_get_temp_dir()."/mailer-app.log", "a");

//prepare the output buffers
	ob_end_clean();
	ob_start();

//message divider for log file
	echo "\n\n======================================================================================================================================================================================\n\n";

//testing show the raw email
	//echo "Message: \n".$msg."\n";

//includes
	require_once('resources/pop3/mime_parser.php');
	require_once('resources/pop3/rfc822_addresses.php');
	if (file_exists($_SERVER["PROJECT_ROOT"]."/app/emails/email_transcription.php")) {
		require_once($_SERVER["PROJECT_ROOT"]."/app/emails/email_transcription.php");
	}

//parse the email message
	$mime = new mime_parser_class;
	$mime->decode_bodies = 1;
	$parameters = array(
		//'File'=>$message_file,

		// Read a message from a string instead of a file
		'Data' => $msg,

		// Save the message body parts to a directory
		// 'SaveBody' => '/tmp',

		// Do not retrieve or save message body parts
		//   'SkipBody' => 1,
	);
	$success = $mime->Decode($parameters, $decoded);
	unset($parameters);

	if (!$success) {
		echo "MIME message decoding error: ".HtmlSpecialChars($mime->error)."\n";
	}
	else {
		//get the headers
			//print_r($decoded[0]);
			$headers = json_decode($decoded[0]["Headers"]["x-headers:"], true);
			$subject = $decoded[0]["Headers"]["subject:"];
			$from = $decoded[0]["Headers"]["from:"];
			$reply_to = $decoded[0]["Headers"]["reply-to:"];
			$to = $decoded[0]["Headers"]["to:"];
			$date = $decoded[0]["Headers"]["date:"];

		//get the body
			$body = ''; //$parts_array["Parts"][0]["Headers"]["content-type:"];

		//get the body
			$body = '';
			$content_type = $decoded[0]['Headers']['content-type:'];
			if (substr($content_type, 0, 15) == "multipart/mixed" || substr($content_type, 0, 21) == "multipart/alternative") {
				foreach ($decoded[0]["Parts"] as $row) {
					$body_content_type = $row["Headers"]["content-type:"];
					if (substr($body_content_type, 0, 9) == "text/html") { $body = $row["Body"]; }
					if (substr($body_content_type, 0, 10) == "text/plain") { $body_plain = $row["Body"];  $body = $body_plain; }
				}
			}
			else {
				$content_type_array = explode(";", $content_type);
				$body = $decoded[0]["Body"];
				//if ($content_type_array[0] == "text/html" || $content_type_array[0] == "text/plain") {
				//	$body = $row["Body"];
				//}
			}
	}

//prepare smtp server settings
	// load default smtp settings
	if ($_SESSION['email']['smtp_hostname']['text'] != '') { 
		$smtp['hostname'] = $_SESSION['email']['smtp_hostname']['text'];
	}
	$smtp['host'] 		= (strlen($_SESSION['email']['smtp_host']['text'])?$_SESSION['email']['smtp_host']['text']:'127.0.0.1');
	if (isset($_SESSION['email']['smtp_port'])) {
		$smtp['port'] = (int) $_SESSION['email']['smtp_port']['numeric'];
	}
	else {
		$smtp['port'] = 0;
	}
	$smtp['secure'] 	= $_SESSION['email']['smtp_secure']['text'];
	$smtp['auth'] 		= $_SESSION['email']['smtp_auth']['text'];
	$smtp['username'] 	= $_SESSION['email']['smtp_username']['text'];
	$smtp['password'] 	= $_SESSION['email']['smtp_password']['text'];
	$smtp['from'] 		= $_SESSION['email']['smtp_from']['text'];
	$smtp['from_name'] 	= $_SESSION['email']['smtp_from_name']['text'];

	if (isset($_SESSION['voicemail']['smtp_from']) && strlen($_SESSION['voicemail']['smtp_from']['text']) > 0) {
		$smtp['from'] = $_SESSION['voicemail']['smtp_from']['text'];
	}
	if (isset($_SESSION['voicemail']['smtp_from_name']) && strlen($_SESSION['voicemail']['smtp_from_name']['text']) > 0) {
		$smtp['from_name'] = $_SESSION['voicemail']['smtp_from_name']['text'];
	}

	// overwrite with domain-specific smtp server settings, if any
	if (is_uuid($headers["X-FusionPBX-Domain-UUID"])) {
		$sql = "select domain_setting_subcategory, domain_setting_value ";
		$sql .= "from v_domain_settings ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and (domain_setting_category = 'email' or domain_setting_category = 'voicemail') ";
		$sql .= "and domain_setting_enabled = 'true' ";
		$parameters['domain_uuid'] = $headers["X-FusionPBX-Domain-UUID"];
		$database = new database;
		$result = $database->select($sql, $parameters, 'all');
		if (is_array($result) && @sizeof($result) != 0) {
			foreach ($result as $row) {
				if ($row['domain_setting_value'] != '') {
					$smtp[str_replace('smtp_','',$row["domain_setting_subcategory"])] = $row['domain_setting_value'];
				}
			}
		}
		unset($sql, $parameters, $result, $row);
	}
	// value adjustments
	$smtp['auth'] 		= ($smtp['auth'] == "true") ? true : false;
	$smtp['password'] 	= ($smtp['password'] != '') ? $smtp['password'] : null;
	$smtp['secure'] 	= ($smtp['secure'] != "none") ? $smtp['secure'] : null;
	$smtp['username'] 	= ($smtp['username'] != '') ? $smtp['username'] : null;

//send the email
	include_once "resources/phpmailer/class.phpmailer.php";
	include_once "resources/phpmailer/class.smtp.php";
	$mail = new PHPMailer();
	if (isset($_SESSION['email']['method'])) {
		switch ($_SESSION['email']['method']['text']) {
			case 'sendmail': $mail->IsSendmail(); break;
			case 'qmail': $mail->IsQmail(); break;
			case 'mail': $mail->IsMail(); break;
			default: $mail->IsSMTP(); break;
		}
	}
	else {
		$mail->IsSMTP();
	}

// optional bypass TLS certificate check e.g. for self-signed certificates
	if (isset($_SESSION['email']['smtp_validate_certificate'])) {
	    if ($_SESSION['email']['smtp_validate_certificate']['boolean'] == "false") {

		    // this is needed to work around TLS certificate problems
		    $mail->SMTPOptions = array(
			    'ssl' => array(
			    'verify_peer' => false,
			    'verify_peer_name' => false,
			    'allow_self_signed' => true
			    )
		    );
	    }
	}

	$mail->SMTPAuth = $smtp['auth'];
	if (isset($smtp['hostname'])) { 
		$mail->Hostname = $smtp['hostname'];
	}
	$mail->Host = $smtp['host'];
	if ($smtp['port']!=0) $mail->Port=$smtp['port'];
	if ($smtp['secure'] != '') {
		$mail->SMTPSecure = $smtp['secure'];
	}
	if ($smtp['auth']) {
		$mail->Username = $smtp['username'];
		$mail->Password = $smtp['password'];
	}
	$mail->SMTPDebug  = 2;

//send context to the temp log
	if (sizeof($headers)>0) {
		foreach ($headers as $header => $value) {
			echo $header.": ".$value."\n";
		}
	}
	echo "Subject: ".$subject."\n";
	echo "From: ".$from."\n";
	echo "Reply-to: ".$reply_to."\n";
	echo "To: ".$to."\n";
	echo "Date: ".$date."\n";
	//echo "Body: ".$body."\n";

//add to, from, fromname, custom headers and subject to the email
	$mail->From = $smtp['from'] ;
	$mail->FromName = $smtp['from_name'];
	if (sizeof($headers)>0) {
		foreach ($headers as $header => $value) {
			$mail->addCustomHeader($header.": ".$value);
		}
	}
	$mail->Subject = $subject;

	$to = trim($to, "<> ");
	$to = str_replace(";", ",", $to);
	$to_array = explode(",", $to);
	if (count($to_array) == 0) {
		$mail->AddAddress($to);
	}
	else {
		foreach ($to_array as $to_row) {
			if (strlen($to_row) > 0) {
				echo "Add Address: $to_row\n";
				$mail->AddAddress(trim($to_row));
			}
		}
	}

//get the attachments and add to the email
	if ($success) {
		foreach ($decoded[0]["Parts"] as &$parts_array) {
			$content_type = $parts_array["Parts"][0]["Headers"]["content-type:"];
				//image/tiff;name="testfax.tif"
				//text/plain; charset=ISO-8859-1; format=flowed
			$content_transfer_encoding = $parts_array["Parts"][0]["Headers"]["content-transfer-encoding:"];
				//base64
				//7bit
			$content_disposition = $parts_array["Parts"][0]["Headers"]["content-disposition"];
				//inline;filename="testfax.tif"
			$file = $parts_array["FileName"];
				//testfax.tif
			$filedisposition = $parts_array["FileDisposition"];
				//inline
			$bodypart = $parts_array["BodyPart"];
			$bodylength = $parts_array["BodyLength"];
			if (strlen($file) > 0) {
				//get the file information
					$file_ext = pathinfo($file, PATHINFO_EXTENSION);
					$file_name = substr($file, 0, (strlen($file) - strlen($file_ext))-1 );
					$encoding = "base64"; //base64_decode

					switch ($file_ext){
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

				//add an attachment
					$mail->AddStringAttachment($parts_array["Body"],$file,$encoding,$mime_type);
					if (function_exists('get_transcription')) {
						$attachments_array = $mail->GetAttachments();
						$transcription = get_transcription($attachments_array[0]);
						echo "Transcription: " . $transcription;
					}
					else {
						$transcription = '';
					}
			}
		}
	}

//add the body to the email
	$body_plain = remove_tags($body);
	//echo "body_plain = $body_plain\n";
	if ((substr($body, 0, 5) == "<html") || (substr($body, 0, 9) == "<!doctype")) {
		$mail->ContentType = "text/html";
		$mail->Body = $body."<br><br>".nl2br($transcription);
		$mail->AltBody = $body_plain."\n\n$transcription";
		$mail->isHTML(true);
	}
	else {
		// $mail->Body = ($body != '') ? $body : $body_plain;
		$mail->Body = $body_plain."\n\n$transcription";
		$mail->AltBody = $body_plain."\n\n$transcription";
		$mail->isHTML(false);
	}
	$mail->CharSet = "utf-8";

//send the email
	if (!$mail->Send()) {
		$mailer_error = $mail->ErrorInfo;
		echo "Mailer Error: ".$mailer_error."\n\n";

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

	}
	else {
		echo "Message sent!";
	}

//get and save the output from the buffer
	$content = ob_get_contents(); //get the output from the buffer
	$content = str_replace("<br />", "", $content);

	ob_end_clean(); //clean the buffer

	fwrite($fp, $content);
	fclose($fp);

/*
//save in /tmp as eml file
	$fp = fopen(sys_get_temp_dir()."/email.eml", "w");
	ob_end_clean();
	ob_start();

	$sql = "select email from v_email_logs where email_log_uuid = :email_log_uuid ";
	$parameters['email_log_uuid'] = $email_log_uuid;
	$database = new database;
	$email = $database->select($sql, $parameters, 'column');
	echo $email;
	unset($sql, $parameters, $email);

	$content = ob_get_contents(); //get the output from the buffer
	$content = str_replace("<br />", "", $content);

	ob_end_clean(); //clean the buffer

	fwrite($fp, $content);
	fclose($fp);
*/

?>