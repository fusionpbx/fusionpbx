<?php
/*-
 * Copyright (c) 2008-2022 Mark J Crane <markjcrane@fusionpbx.com>
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

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!permission_exists('email_log_view')) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//validate the token
	$token = new token;
	if (!$token->validate('/app/email_logs/email_logs.php')) {
		//message::add($text['message-invalid_token'],'negative');
		echo "<script>display_message('".$text['message-invalid_token']."', 'negative');</script>";
		echo "<center>\n";
		echo 	$text['message-invalid_token'];
		echo "	<br><br>\n";
		echo "	<input type='button' class='btn' style='margin-top: 15px;' value='".$text['button-close']."' onclick=\"$('#test_result_layer').fadeOut(200);\">\n";
		echo "</center>\n";
		exit;
	}


//show the content
	echo "<b>".$text['header-settings']."</b>\n";
	echo "<br><br>\n";
	ksort($_SESSION['email']);
	foreach ($_SESSION['email'] as $name => $setting) {
		foreach ($setting as $type => $value) {
			if ($type == 'uuid') { $uuid = $value; continue; }
			if ($name == 'smtp_password') { $value = '[REDACTED]'; }
			if (permission_exists('default_setting_edit')) {
				echo "<a href='../../core/default_settings/default_setting_edit.php?id=".$uuid."' target='_blank'>".$name.'</a>: '.$value."<br>\n";
			}
			else {
				echo $name.': '.$value."<br>\n";
			}
		}
	}
	echo "<br><br>\n";

	echo "<b>".$text['header-connection']."</b>\n";
	echo "<br><br>\n";

//prepare the email
	$email_recipient = check_str($_POST['to']);

	$email_body = "<b>Test Message</b><br /><br />\n";
	$email_body .= "This message is a test of the SMTP settings configured within your PBX.<br />\n";
	$email_body .= "If you received this message, your current SMTP settings are valid.<br /><br />\n";

	//$email_attachments[0]['type'] = 'file';
	//$email_attachments[0]['name'] = 'logo.png';
	//$email_attachments[0]['value'] = $_SERVER["PROJECT_ROOT"]."/themes/default/images/logo.png";

	$email_from_address = $_SESSION['email']['smtp_from']['text'];
	$email_from_name = $_SESSION['email']['smtp_from_name']['text'];

//send email
	//ob_start();
	//$sent = !send_email($email_recipient, 'Test Message', $email_body, $email_error, null, null, 3, 3, $email_attachments) ? false : true;
	//$email_response = ob_get_clean();

//send email
	$email = new email;
	$email->recipients = $email_recipient;
	$email->subject = 'Test Message';
	$email->body = $email_body;
	$email->from_address = $email_from_address;
	$email->from_name = $email_from_name;
	$email->attachments = $email_attachments;
	$email->debug_level = 3;
	$email->method = 'direct';
	$sent = $email->send();
	//$email_error = $email->email_error;

//show additional information
	echo "<br><br>\n";

	echo "<b>".$text['header-result']."</b>\n";
	echo "<br><br>\n";
	echo $sent ? "Message Sent Successfully<br>Receipient: <a href='mailto:".$email_recipient."'>".$email_recipient."</a>" : "Message Failed...<br>".$email_error;

	echo "<br>\n";
	echo "<center>\n";
	echo "	<input type='button' class='btn' style='margin-top: 15px;' value='".$text['button-close']."' onclick=\"$('#test_result_layer').fadeOut(200);\">\n";
	echo "</center>\n";

?>
