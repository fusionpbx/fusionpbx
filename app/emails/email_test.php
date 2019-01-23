<?php
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";

if (!permission_exists('email_view')) {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

if (valid_email($_POST['to'])) {

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
	$sent = !send_email($recipient, 'Test Message', $eml_body, $eml_error) ? false : true;
	$response = ob_get_clean();

	echo $response;

	echo "<br><br>\n";

	echo "<b>".$text['header-result']."</b>\n";
	echo "<br><br>\n";
	echo $sent ? "Message Sent Successfully<br>Receipient: <a href='mailto:".$recipient."'>".$recipient."</a>" : "Message Failed...<br>".$eml_error;

}
else {

	echo "Error: Invalid Recipient Address";

}

echo "<br>\n";
echo "<center>\n";
echo "	<input type='button' class='btn' style='margin-top: 15px;' value='".$text['button-close']."' onclick=\"$('#test_result_layer').fadeOut(200);\">\n";
echo "</center>\n";

?>