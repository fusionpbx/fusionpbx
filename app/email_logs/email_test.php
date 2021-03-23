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
*/

//includes
	require_once "root.php";
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

//send email

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

	$recipient = check_str($_POST['to']);

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

	$eml_body = "<b>Test Message</b><br /><br />\n";
	$eml_body .= "This message is a test of the SMTP settings configured within your PBX.<br />\n";
	$eml_body .= "If you received this message, your current SMTP settings are valid.<br /><br />\n";

	ob_start();
	$sent = !send_email($recipient, 'Test Message', $eml_body, $eml_error, null, null, 3, 3) ? false : true;
	$response = ob_get_clean();

	echo $response;

	echo "<br><br>\n";

	echo "<b>".$text['header-result']."</b>\n";
	echo "<br><br>\n";
	echo $sent ? "Message Sent Successfully<br>Receipient: <a href='mailto:".$recipient."'>".$recipient."</a>" : "Message Failed...<br>".$eml_error;


	echo "<br>\n";
	echo "<center>\n";
	echo "	<input type='button' class='btn' style='margin-top: 15px;' value='".$text['button-close']."' onclick=\"$('#test_result_layer').fadeOut(200);\">\n";
	echo "</center>\n";

?>