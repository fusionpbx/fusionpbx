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
	Portions created by the Initial Developer are Copyright (C) 2008-2012
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
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
	require_once "includes/require.php";

//set init settings
	ini_set('max_execution_time',1800); //30 minutes
	ini_set('memory_limit', '128M');

//listen for standard input
	$fd = fopen("php://stdin", "r");
	$msg = file_get_contents ("php://stdin");
	fclose($fd);

//save output to 
	$fp = fopen(sys_get_temp_dir()."/mailer-app.log", "w");

//prepare the output buffers
	ob_end_clean();
	ob_start();

//testing show the raw email
	//echo "Message: \n".$msg."\n";

//includes
	require('includes/pop3/mime_parser.php');
	require('includes/pop3/rfc822_addresses.php');

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
			$subject = $decoded[0]["Headers"]["subject:"];
			$from = $decoded[0]["Headers"]["from:"];
			$reply_to = $decoded[0]["Headers"]["reply-to:"];
			$to = $decoded[0]["Headers"]["to:"];
			$date = $decoded[0]["Headers"]["date:"];

		//get the body
			$body = ''; //$parts_array["Parts"][0]["Headers"]["content-type:"];

		//get the body
			$body = '';
			foreach($decoded[0]["Parts"] as $row) {
				$content_type = $row['Headers']['content-type:'];
				if (substr($content_type, 0, 21) == "multipart/alternative") {
					$content_type = $row["Parts"][0]["Headers"]["content-type:"];
					if (substr($content_type, 0, 9) == "text/html") { $body = $row["Parts"][0]["Body"]; }
					if (substr($content_type, 0, 10) == "text/plain") { $body_plain = $row["Parts"][0]["Body"]; }
					$content_type = $row["Parts"][1]["Headers"]["content-type:"];
					if (substr($content_type, 0, 9) == "text/html") { $body = $row["Parts"][1]["Body"]; }
					if (substr($content_type, 0, 10) == "text/plain") { $body_plain = $row["Parts"][1]["Body"]; }
				}
				else {
					$content_type_array = explode(";", $content_type);
					if ($content_type_array[0] == "text/plain") {
						$body = $row["Body"];
					}
				}
			}
	}

//send the email
	include "resources/phpmailer/class.phpmailer.php";
	include "resources/phpmailer/class.smtp.php"; // optional, gets called from within class.phpmailer.php if not already loaded
	$mail = new PHPMailer();

	$mail->IsSMTP(); // set mailer to use SMTP
	if ($_SESSION['email']['smtp_auth']['var'] == "true") {
		$mail->SMTPAuth = $_SESSION['email']['smtp_auth']['var']; // turn on/off SMTP authentication
	}
	$mail->Host   = $_SESSION['email']['smtp_host']['var'];
	if ($_SESSION['email']['smtp_secure']['var'] == "none") {
		$_SESSION['email']['smtp_secure']['var'] = '';
	}
	if (strlen($_SESSION['email']['smtp_secure']['var']) > 0) {
		$mail->SMTPSecure = $_SESSION['email']['smtp_secure']['var'];
	}
	if ($_SESSION['email']['smtp_username']['var']) {
		$mail->Username = $_SESSION['email']['smtp_username']['var'];
		$mail->Password = $_SESSION['email']['smtp_password']['var'];
	}
	$mail->SMTPDebug  = 2;

//send context to the temp log
	echo "Subject: ".$subject."\n";
	echo "From: ".$from."\n";
	echo "Reply-to: ".$reply_to."\n";
	echo "To: ".$to."\n";
	echo "Date: ".$date."\n";
	//echo "Body: ".$body."\n";

//add to, from, fromname, and subject to the email
	$mail->From       = $_SESSION['email']['smtp_from']['var'] ;
	$mail->FromName   = $_SESSION['email']['smtp_from_name']['var'];
	$mail->Subject    = $subject;

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
	$mail->AltBody    = $body_plain;   // optional, comment out and test
	$mail->MsgHTML($body);

//send the email
	if(!$mail->Send()) {
		echo "Mailer Error: " . $mail->ErrorInfo;
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

?>