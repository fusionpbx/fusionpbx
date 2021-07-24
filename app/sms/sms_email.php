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
	Jonathan Black <jonathan@diamondvoice.net>

*/

//Regex from https://emailregex.com
function validateEMAIL($EMAIL) {
    $v = '/^(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){255,})(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){65,}@)(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22))(?:\\.(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-+[a-z0-9]+)*\\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-+[a-z0-9]+)*)|(?:\\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\\]))$/iD';

    return (bool)preg_match($v, $EMAIL);
}

function send_sms_to_email($from, $to, $body, $media = null) {
	global $db, $debug, $domain_uuid, $domain_name, $carrier;
	if ($debug) {
		error_log('Media: ' .  print_r($media, true));
	}
	//$domain_name = string.match(to,'%@+(.+)');
	if (preg_match('/@+(.+)/',$to,$matches)) {
		$domain_name = $matches[1];
	}
	//get email address from db
	// Check for email address in sms_destinations table
	$sql = "select domain_name, ";
	$sql .= "email, ";
	$sql .= "v_sms_destinations.domain_uuid as domain_uuid, ";
	$sql .= "carrier ";
	$sql .= "from v_sms_destinations, ";
	$sql .= "v_domains ";
	$sql .= "where v_sms_destinations.domain_uuid = v_domains.domain_uuid";
	$sql .= " and destination like :to";
//	$sql .= " and chatplan_detail_data <> ''"; //uncomment to disable email-only

	if ($debug) {
		error_log("SQL: " . print_r($sql,true));
	}

	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->bindValue(':to', "%{$to}%");
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);

	if (count($result) > 0) {
		foreach ($result as &$row) {
			$domain_name = $row["domain_name"];
			$email_to = $row["email"];
			$domain_uuid = $row["domain_uuid"];
			$carrier = $row["carrier"];
			break; //limit to 1 row
		}
	}

	//error_log('to: ' .  $to);
	//error_log($email_to);

	if (empty($email_to)) {
			error_log("[sms] email address is empty, cannot send sms to email.");
			return false;
	}
	else {

		//set email values
		$email_subject = 'Text Message from: ' . $from;
		$semi_rand = md5(time());
		$mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";

		if (!empty($_SESSION['email']['smtp_from']['text']) and validateEMAIL($_SESSION['email']['smtp_from']['text'])) {
			$headers = "From: " . $_SESSION['email']['smtp_from']['text'] . "\n";
		}
		else {
			$headers = "From: noreply@example.com\n";
		}
		if ($debug) {
			error_log("Email Sender: " . $headers);
		}
		$headers .= "MIME-Version: 1.0\n" . "Content-Type: multipart/mixed; " . "boundary=\"{$mime_boundary}\"";
		$body = urldecode($body);
		$body = preg_replace('([\n])', '<br>', $body); // fix newlines
		$email_txt = 'To: ' . $to . '<br>Msg: ' . $body;

		$email_message = "" .	$email_txt . "";

		if ($carrier == "telnyx") {
			if (gettype($media)=="array") {
				$email_txt = 'To: ' . $to . '<br>Msg: ' . $body . '<br>MMS Message received, see attachment';

				$email_message = "" .	$email_txt . "";
				//process MMS attachment
				foreach ($media as $attachment) {
					$url = $attachment->url;
					$start = strrpos($url, '/') == -1 ? strrpos($url, '//') : strrpos($url, '/')+1;
					$fileatt_name = substr($url, $start, strlen($url)); // Filename that will be used for the file as the attachment
					if (!empty($_SESSION['sms']['mms_attachment_temp_path']['text'])) {
						$fileatt = $_SESSION['sms']['mms_attachment_temp_path']['text'];
						if (substr($fileatt, -1) != '/') {
							$fileatt .= '/';
						}
						$fileatt .= $fileatt_name;
					}
					else {
						$fileatt = '/var/www/fusionpbx/app/sms/tmp/' . $fileatt_name; // Path to the file
					}

					// download attachment
					file_put_contents($fileatt, fopen($url, 'r'));

					//$fileatt_type = "application/octet-stream"; // File Type
					$fileatt_type = $attachment->content_type; // File Type

					//$start = strrpos($attachment, '/') == -1 ? strrpos($attachment, '//') : strrpos($attachment, '/')+1;

					$file = fopen($fileatt,'rb');
					$attdata = fread($file,filesize($fileatt));
					fclose($file);


					$attdata = chunk_split(base64_encode($attdata));

					$email_message .= "--{$mime_boundary}\n" . "Content-Type: {$fileatt_type};\n" .
						" name = \"{$fileatt_name}\"\n" . "Content-Disposition: inline;\n" . " filename = \"{$fileatt_name}\"\n" .
						"Content-Transfer-Encoding:base64\n\n" . $attdata . "\n\n" . "--{$mime_boundary}--\n";
					error_log("email_message: " . $email_message);
					unlink($fileatt); // delete a file after attachment sent.
				}
			}
		}
		else {
			$email_message .= "";
		}
		if ($debug) {
			error_log("headers: " . $headers);
		}
		//send email
		$ok = send_email($email_to, $email_subject, $email_message);//, $headers);

		if ($ok) {
			error_log("[sms] Email Sent Successfully.");
			return true;
		} else {
			error_log("[sms] Email could not be sent.");
			return false;
		}
	}
}

?>
