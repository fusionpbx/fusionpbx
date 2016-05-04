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
	Copyright (C) 2008-2015
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('email_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get email
	$email_uuid = check_str($_REQUEST["id"]);

	$msg_found = false;

	if ($email_uuid != '') {
		$sql = "select * from v_emails ";
		$sql .= "where email_uuid = '".$email_uuid."' ";
		$sql .= "and domain_uuid = '".$domain_uuid."' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		$result_count = count($result);
		unset ($prep_statement, $sql);

		if ($result_count > 0) {
			foreach($result as $row) {
				$sent = $row['sent_date'];
				$type = $row['type'];
				$status = $row['status'];
				$email = $row['email'];
				$msg_found = true;
				break;
			}
		}
	}

	if (!$msg_found) {
		$_SESSION["message"] = $text['message-invalid_email'];
		header("Location: emails.php");
		exit;
	}

//includes
	require('resources/pop3/mime_parser.php');
	require('resources/pop3/rfc822_addresses.php');

//parse the email message
	$mime = new mime_parser_class;
	$mime->decode_bodies = 1;
	$parameters = array('Data' => $email);
	$success = $mime->Decode($parameters, $decoded);

	if ($success) {
		//get the headers
			$headers = json_decode($decoded[0]["Headers"]["x-headers:"], true);
			$subject = $decoded[0]["Headers"]["subject:"];
			$from = $decoded[0]["Headers"]["from:"];
			$reply_to = $decoded[0]["Headers"]["reply-to:"];
			$to = $decoded[0]["Headers"]["to:"];
			$subject = $decoded[0]["Headers"]["subject:"];

			if (substr_count($subject, '=?utf-8?B?') > 0) {
				$subject = str_replace('=?utf-8?B?', '', $subject);
				$subject = str_replace('?=', '', $subject);
				$subject = base64_decode($subject);
			}

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
			}

		//get the attachments (if any)
			foreach ($decoded[0]['Parts'] as &$parts_array) {
				$content_type = $parts_array["Parts"][0]["Headers"]["content-type:"]; //audio/wav; name="msg_b64f97e0-8570-11e4-8400-35da04cdaa74.wav"
				$content_transfer_encoding = $parts_array["Parts"][0]["Headers"]["content-transfer-encoding:"]; //base64
				$content_disposition = $parts_array["Parts"][0]["Headers"]["content-disposition"]; //attachment; filename="msg_b64f97e0-8570-11e4-8400-35da04cdaa74.wav"
				$file_name = $parts_array["FileName"];
				$file_size = $parts_array["BodyLength"];
			}
	}
	else {
		$_SESSION["message"] = $text['message-decoding_error'].(($mime->error != '') ? ': '.htmlspecialchars($mime->error) : null);
		header("Location: emails.php");
		exit;
	}

//show the header
	$document['title'] = $text['title-view_email'];
	require_once "resources/header.php";

//show content
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>";
	echo "		<td valign='top' align='left' nowrap>";
	echo "			<b>".$text['header-view_email']."</b>\n";
	echo "		</td>";
	echo "		<td valign='top' align='right' nowrap>";
	echo "			<input type='button' class='btn' alt='".$text['button-back']."' onclick=\"document.location.href='emails.php';\" value='".$text['button-back']."'>";
	if (permission_exists('email_download')) {
		echo "		<input type='button' class='btn' alt='".$text['button-download']."' onclick=\"document.location.href='emails.php?id=".$email_uuid."&a=download';\" value='".$text['button-download']."'>";
	}
	if (permission_exists('email_resend')) {
		echo "		<input type='button' class='btn' alt='".$text['button-resend']."' onclick=\"document.location.href='emails.php?id=".$email_uuid."&a=resend';\" value='".$text['button-resend']."'>";
	}
	echo "		</td>";
	echo "	</tr>";
	echo "</table>";
	echo "<br>\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncell' valign='top' align='left' nowrap>".$text['label-sent']."</td>\n";
	echo "<td width='70%' class='vtable' align='left'>".$sent."</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>".$text['label-type']."</td>\n";
	echo "<td class='vtable' align='left'>".$text['label-type_'.$type]."</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>".$text['label-status']."</td>\n";
	echo "<td class='vtable' align='left'>".$text['label-status_'.$status]."</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>".$text['label-from']."</td>\n";
	echo "<td class='vtable' align='left'>".$from."</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>".$text['label-to']."</td>\n";
	echo "<td class='vtable' align='left'>".$to."</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>".$text['label-subject']."</td>\n";
	echo "<td class='vtable' align='left'>".$subject."</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>".$text['label-message']."</td>\n";
	echo "<td class='vtable' align='left'>";
	echo "	<iframe id='msg_display' width='100%' height='250' scrolling='auto' cellspacing='0' style='border: 1px solid #c5d1e5; overflow: scroll;'></iframe>\n";
	echo "	<textarea id='msg' width='1' height='1' style='width: 1px; height: 1px; display: none;'>".$body."</textarea>\n";
	echo "	<script>";
	echo "		var iframe = document.getElementById('msg_display');";
	echo "		iframe.contentDocument.write(document.getElementById('msg').value);";
	echo "		iframe.contentDocument.close();";
	echo "	</script>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>".$text['label-attachment']."</td>\n";
	echo "<td class='vtable' align='left'>".$file_name." (".round($file_size/1024,2)." KB)</td>\n";
	echo "</tr>\n";

	echo "</table>\n";
	echo "<br><br>";

//include the footer
	require_once "resources/footer.php";
?>