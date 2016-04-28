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
	if (!defined('STDIN')) { include "root.php"; }
	require_once "resources/require.php";

//define a function to remove html tags
	function rip_tags($string) {
		// ----- remove HTML TAGs -----
		$string = preg_replace ('/<[^>]*>/', ' ', $string);

		// ----- remove control characters -----

		$string = str_replace("\r", '', $string);    // --- replace with empty space
		$string = str_replace("\n", ' ', $string);   // --- replace with space
		$string = str_replace("\t", ' ', $string);   // --- replace with space

		// ----- remove multiple spaces -----
		$string = trim(preg_replace('/ {2,}/', ' ', $string));
		return $string;
	}

//set init settings
	ini_set('max_execution_time',1800); //30 minutes
	ini_set('memory_limit', '128M');

//listen for standard input
	if ($msg == '') {
		$fd = fopen("php://stdin", "r");
		$msg = file_get_contents ("php://stdin");
		fclose($fd);
	}

//save output to
	$fp = fopen(sys_get_temp_dir()."/mailer-app.log", "w");

//prepare the output buffers
	ob_end_clean();
	ob_start();

//testing show the raw email
	//echo "Message: \n".$msg."\n";

//includes
	require('resources/pop3/mime_parser.php');
	require('resources/pop3/rfc822_addresses.php');

//parse the email message
	$mime=new mime_parser_class;
	$mime->decode_bodies = 1;
	$parameters=array(
		//'File'=>$message_file,

		// Read a message from a string instead of a file
		'Data'=>$msg,

		// Save the message body parts to a directory
		// 'SaveBody'=>'/tmp',

		// Do not retrieve or save message body parts
		//   'SkipBody'=>1,
	);
	$success=$mime->Decode($parameters, $decoded);

	if(!$success) {
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
				foreach($decoded[0]["Parts"] as $row) {
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
	$smtp['host'] 		= (strlen($_SESSION['email']['smtp_host']['var'])?$_SESSION['email']['smtp_host']['var']:'127.0.0.1');
	if (isset($_SESSION['email']['smtp_port'])) {
		$smtp['port'] = (int)$_SESSION['email']['smtp_port']['numeric'];
	} else {
		$smtp['port'] = 0;
	}
	$smtp['secure'] 	= $_SESSION['email']['smtp_secure']['var'];
	$smtp['auth'] 		= $_SESSION['email']['smtp_auth']['var'];
	$smtp['username'] 	= $_SESSION['email']['smtp_username']['var'];
	$smtp['password'] 	= $_SESSION['email']['smtp_password']['var'];
	$smtp['from'] 		= (strlen($_SESSION['email']['smtp_from']['var'])?$_SESSION['email']['smtp_from']['var']:'fusionpbx@example.com');
	$smtp['from_name'] 	= (strlen($_SESSION['email']['smtp_from_name']['var'])?$_SESSION['email']['smtp_from_name']['var']:'FusionPBX Voicemail');

	// overwrite with domain-specific smtp server settings, if any
	if ($headers["X-FusionPBX-Domain-UUID"] != '') {
		$sql = "select domain_setting_subcategory, domain_setting_value ";
		$sql .= "from v_domain_settings ";
		$sql .= "where domain_uuid = '".$headers["X-FusionPBX-Domain-UUID"]."' ";
		$sql .= "and domain_setting_category = 'email' ";
		$sql .= "and domain_setting_name = 'var' ";
		$sql .= "and domain_setting_enabled = 'true' ";
		$prep_statement = $db->prepare($sql);
		if ($prep_statement) {
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			foreach ($result as $row) {
				if ($row['domain_setting_value'] != '') {
					$smtp[str_replace('smtp_','',$row["domain_setting_subcategory"])] = $row['domain_setting_value'];
				}
			}
		}
		unset($sql, $prep_statement);
	}

	// value adjustments
	$smtp['auth'] 		= ($smtp['auth'] == "true") ? true : false;
	$smtp['password'] 	= ($smtp['password'] != '') ? $smtp['password'] : null;
	$smtp['secure'] 	= ($smtp['secure'] != "none") ? $smtp['secure'] : null;
	$smtp['username'] 	= ($smtp['username'] != '') ? $smtp['username'] : null;

//send the email
	include "resources/phpmailer/class.phpmailer.php";
	include "resources/phpmailer/class.smtp.php";
	$mail = new PHPMailer();
	if (isset($_SESSION['email']['method'])) {
		switch($_SESSION['email']['method']['text']) {
			case 'sendmail': $mail->IsSendmail(); break;
			case 'qmail': $mail->IsQmail(); break;
			case 'mail': $mail->IsMail(); break;
			default: $mail->IsSMTP(); break;
		}
	} else $mail->IsSMTP();
	$mail->SMTPAuth = $smtp['auth'];
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
		foreach($to_array as $to_row) {
			if (strlen($to_row) > 0) {
				echo "Add Address: $to_row\n";
				$mail->AddAddress(trim($to_row));
			}
		}
	}

//get the attachments and add to the email
	if($success) {
		foreach ($decoded[0][Parts] as &$parts_array) {
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

					switch($file_ext){
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
			}
		}
	}

//add the body to the email
	$body_plain = rip_tags($body);
	//echo "body_plain = $body_plain\n";
	if ((substr($body, 0, 5) == "<html") ||  (substr($body, 0, 9) == "<!doctype")) {
		$mail->ContentType = "text/html";
		$mail->Body = $body;
		$mail->AltBody = $body_plain;
	}
	else {
		// $mail->Body = ($body != '') ? $body : $body_plain;
		$mail->Body = $body_plain;
		$mail->AltBody = $body_plain;
	}

//send the email
	if(!$mail->Send()) {
		$mailer_error = $mail->ErrorInfo;
		echo "Mailer Error: ".$mailer_error."\n\n";

		$call_uuid = $headers["X-FusionPBX-Call-UUID"];
		if ($resend == true) {
			echo "Retained in v_emails \n";
		} else {
			// log/store message in database for review
			$email_uuid = uuid();
			$sql = "insert into v_emails ( ";
			$sql .= "email_uuid, ";
			if ($call_uuid) {
				$sql .= "call_uuid, ";
			}
			$sql .= "domain_uuid, ";
			$sql .= "sent_date, ";
			$sql .= "type, ";
			$sql .= "status, ";
			$sql .= "email ";
			$sql .= ") values ( ";
			$sql .= "'".$email_uuid."', ";
			if ($call_uuid) {
				$sql .= "'".$call_uuid."', ";
			}
			$sql .= "'".$headers["X-FusionPBX-Domain-UUID"]."', ";
			$sql .= "now(),";
			$sql .= "'".$headers["X-FusionPBX-Email-Type"]."', ";
			$sql .= "'failed', ";
			$sql .= "'".str_replace("'", "''", $msg)."' ";
			$sql .= ") ";
			$db->exec(check_sql($sql));
			unset($sql);

			echo "Retained in v_emails as email_uuid = ".$email_uuid."\n";
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


/********************************************************************************************

// save in /tmp as eml file

$fp = fopen(sys_get_temp_dir()."/email.eml", "w");

ob_end_clean();
ob_start();

$sql = "select email from v_emails where email_uuid = '".$email_uuid."'";
$prep_statement = $db->prepare($sql);
if ($prep_statement) {
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		echo $row["email"];
		break;
	}
}
unset($sql, $prep_statement, $result);

$content = ob_get_contents(); //get the output from the buffer
$content = str_replace("<br />", "", $content);

ob_end_clean(); //clean the buffer

fwrite($fp, $content);
fclose($fp);

*/
?>