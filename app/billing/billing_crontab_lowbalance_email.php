<?php
/*
        Billing for FusionPBX

        Contributor(s):
        Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

	include 'root.php';
	require_once 'resources/require.php';
	$call_type = 1;

include_once 'resources/phpmailer/class.phpmailer.php';
include_once 'resources/phpmailer/class.smtp.php';
require_once 'app/billing/resources/functions/currency.php';
require_once 'app/billing/resources/functions/version.php';

$debug = (strtolower($_SESSION['billing']['debug']['boolean']) == "true")?true:false;

$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$today = getdate();
//$sql_billing = "SELECT * FROM v_billings WHERE billing_uuid='459b772a-7a0d-4af1-b90d-237b106461b6'";
$sql_billing = "SELECT * FROM v_billings WHERE credit_type='prepaid' AND ( (currency='USD' AND balance < 0.4) OR (currency='MXN' AND balance < 5.37) OR (currency='CAD' AND balance < 0.44) OR (currency='ARS' AND balance < 2))";
if ($debug){
        echo $sql_billing; echo '<br />';
}
$result_billing = $db->query($sql_billing)->fetchAll(PDO::FETCH_NAMED);
foreach($result_billing as &$billing){
	$billing_referred_depth = $billing['referred_depth'];
	$billing_referred_percentage = $billing['referred_percentage'];
	$billing_uuid = $billing['billing_uuid'];
	$billing_currency = $billing['currency'];
	$billing_contact_uuid_to = $billing['contact_uuid_to'];
	$billing_balance = $billing['balance'];

	$smtp['host']           = $_SESSION['email']['smtp_host']['var'];
	$smtp['secure']         = $_SESSION['email']['smtp_secure']['var'];
	$smtp['auth']           = $_SESSION['email']['smtp_auth']['var'];
	$smtp['username']       = $_SESSION['email']['smtp_username']['var'];
	$smtp['password']       = $_SESSION['email']['smtp_password']['var'];
	$smtp['from']           = $_SESSION['email']['smtp_from']['var'];
	$smtp['from_name']      = $_SESSION['email']['smtp_from_name']['var'];
	$smtp['auth']           = ($smtp['auth'] == 'true') ? $smtp['auth'] : 'false';
	$smtp['password']       = ($smtp['password'] != '') ? $smtp['password'] : null;
	$smtp['secure']         = ($smtp['secure'] != 'none') ? $smtp['secure'] : null;
	$smtp['username']       = ($smtp['username'] != '') ? $smtp['username'] : null;
	$mail = new PHPMailer();
	$mail->IsSMTP();
	$mail->SMTPAuth = $smtp['auth'];
	$mail->Host = $smtp['host'];

	if ($smtp['secure'] != '') {
		$mail->SMTPSecure = $smtp['secure'];
	}

	if ($smtp['auth'] == 'true') {
		$mail->Username = $smtp['username'];
		$mail->Password = $smtp['password'];
	}

	if ($debug){ $mail->SMTPDebug  = 2; };
	$mail->From = $smtp['from'] ;
	$mail->FromName = $smtp['from_name'];
	$mail->addCustomHeader('X-FusionPBX-Billing:'.$billing_uuid);
	$mail->Subject = 'Low credit notification';

	//$sql_to = "SELECT contact_name_given, contact_name_family, contact_email FROM v_contacts WHERE contact_uuid='$billing_contact_uuid_to'";
	$sql_to = 'jpcatino@cistech.com.ar';
	if ($debug){
		echo "sql_to: $sql_to\n";
	};
	//$prep_to = $db->prepare($sql_to);
	//$prep_to->execute();
	//$result_to = $prep_to->fetch(PDO::FETCH_ASSOC);
	//$to_email = $result_to['contact_email'];
	$to_email = $sql_to;
	$to_contact_name_given = $result_to['contact_name_given'];
	$to_contact_name_family = $result_to['contact_name_family'];

	$body = '<p>(an English message will follow)</p><hr />';
	$body .= "<p>Estimado(a) $to_contact_name_given $to_contact_name_family,</p>\n";
	$body .= '<p>Este mensaje es producido por nuestro monitoreo.</p>';
	$body .= "<p>Actualmente usted tiene un saldo de $billing_balance $billing_currency; para evitar la interrupci&oacute;n de su servicio telef&oacute;nico le sugerimos abonar saldo.</p>";
	$body .= '<p>Usted puede a&ntilde;adir cr&eacute;dito siguiendo los siguientes pasos:</p>';
	$body .= "<ol><li>Entre a nuestra consola de auto-administraci&oacute;n ubicada en <a href='http://pbx.to-call.me'>http://pbx.to-call.me</a>, utilice las credenciales que creo cuando se inscribi&oacute; al servicio,</li>";
	$body .= '<li>Vaya al menu App->Billing y seleccione el bot&oacute;n "Add money", y</li>';
	$body .= '<li>Seleccione cualquiera de los m&eacute;todos de pago disponibles.</li></ol>';
	$body .= '<p>Gracias</p>';
	$body .= '<hr />';
	$body .= "<p>Dear $to_contact_name_given $to_contact_name_family,</p>\n";
	$body .= '<p>This message is sent by our monitoring.</p>';
	$body .= "<p>Currently you have a balance of $billing_balance $billing_currency; to void service interruption we suggest you to add credit.</p>";
	$body .= '<p>You can add credit by following next steps:</p>';
	$body .= "<ol><li>Log into our self-management console at <a href='http://pbx.to-call.me'>http://pbx.to-call.me</a>, use your login details you created when you singed up to our service,</li>";
	$body .= '<li>Go to App->Billing menu and select "Add money" button, and</li>';
	$body .= '<li>Select any of the payment methods available.</li></ol>';
	$body .= '<p>Thank you</p>';

	$mail->ContentType = 'text/html';
	$mail->Body = $body;
	$mail->AddAddress($to_email);
	$mail->addBCC('dlucio@okay.com.mx');
	$mail->Send();
}
