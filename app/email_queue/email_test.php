<?php
/*-
 * Copyright (c) 2008-2023 Mark J Crane <markjcrane@fusionpbx.com>
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED.  IN NO EVENT SHALL THE AUTHOR OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS
 * OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
 * OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF
 * SUCH DAMAGE.
 */

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!permission_exists('email_queue_view')) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//prepare the email
	$email_recipient = !empty($_POST['to']) && valid_email($_POST['to']) ? strtolower($_POST['to']) : null;

	$email_body = "<b>Test Message</b><br /><br />\n";
	$email_body .= "This message is a test of the SMTP settings configured within your PBX.<br />\n";
	$email_body .= "If you received this message, your current SMTP settings are valid.<br /><br />\n";

	$email_from_address = $_SESSION['email']['smtp_from']['text'];
	$email_from_name = $_SESSION['email']['smtp_from_name']['text'];

//send email
	$sent = 0;
	$email = new email;
	$email->recipients = $email_recipient;
	$email->subject = 'Test Message';
	$email->body = $email_body;
	$email->from_address = $email_from_address;
	$email->from_name = $email_from_name;
	$email->attachments = $email_attachments ?? null;
	$email->debug_level = 3;
	$email->method = 'direct';
	ob_start();
	$sent = $email->send();
	$send_response = ob_get_contents();
	ob_end_clean();
	$end_response = $email->response;

//format response
	$email_response = array_merge(explode("\n", str_replace('<br>', '', $end_response)), explode("<br>\n", $send_response));
	if (!empty($email_response) && is_array($email_response) && @sizeof($email_response) != 0) {
		foreach ($email_response as $x => $line) {
			if (empty(trim($line))) { unset($email_response[$x]); }
		}
	}

//show the content
	echo "<input type='button' class='btn' style='float: right;' value='".$text['button-close']."' onclick=\"$('#test_result_layer').fadeOut(200);\">\n";
	echo "<b>".$text['header-email_test']."</b>\n";
	echo "<br><br>\n";

	echo $text['description-email_test']."\n";
	echo "<br><br><br>\n";

	echo "<b>".$text['header-settings']."</b>\n";
	echo "<br><br>\n";
	ksort($_SESSION['email']);
	echo "<table>\n";
	foreach ($_SESSION['email'] as $name => $setting) {
		foreach ($setting as $type => $value) {
			echo "<tr>\n";
			if ($type == 'uuid') { $uuid = $value; continue; }
			if ($name == 'smtp_password') { $value = str_repeat('*', strlen($value)); }
			if (permission_exists('default_setting_edit')) {
				echo "<td style='padding-right: 30px;'><a href='../../core/default_settings/default_setting_edit.php?id=".$uuid."' target='_blank'>".$name."</a></td>\n";
				echo "<td style='padding-right: 30px;'>".$value."</td>\n";
			}
			else {
				echo "<td style='padding-right: 30px;'>".$name."</td>\n";
				echo "<td style='padding-right: 30px;'>".$value."</td>\n";
			}
			echo "<tr>\n";
		}
	}
	echo "</table>\n";
	echo "<br><br>\n";

	echo "<b>".$text['header-connection']."</b>\n";
	echo "<br><br>\n";

	echo "<div style='width: 100%; max-height: 250px; overflow: auto; border: 1px solid ".($_SESSION['theme']['table_row_border_color']['text'] ?? '#c5d1e5')."; padding: 12px 15px; background-color: ".($_SESSION['theme']['table_row_background_color_light']['text'] ?? '#fff')."; font-family: monospace; font-size: 85%;'>\n";

	if (!empty($email_response) && is_array($email_response) && @sizeof($email_response) != 0) {
		echo implode("<br>\n<hr style='margin: 3px 0;'>\n", $email_response);
	}
	echo "</div>\n";
	echo "<br><br>\n";

	echo "<b>".$text['header-result']."</b>\n";
	echo "<br><br>\n";
	echo $sent ? "Message Sent Successfully<br>Receipient: <a href='mailto:".$email_recipient."'>".$email_recipient."</a>" : "Message Failed";

	echo "<br><br>\n";
	echo "<center>\n";
	echo "	<input type='button' class='btn' style='margin-top: 15px;' value='".$text['button-close']."' onclick=\"$('#test_result_layer').fadeOut(200);\">\n";
	echo "</center>\n";

?>