<?php
	$payment_plugin[$x]['name'] = 'Offline';						// Name of the plugin, it will apear in webpage
	$payment_plugin[$x]['icon'] = 'pay-button.jpg';					// Icon
	$payment_plugin[$x]['path'] = PROJECT_PATH . "/app/billing/resources/plugins/offline";	// Absolute path to directory
	$payment_plugin[$x]['testmode'] = true;						// Are you testing?  Offline doesn't really have a test mode
	$payment_plugin[$x]['default_charge'] = '10';					// 10 in any currency by default
	$payment_plugin[$x]['currency'][] = '*';					// All currencies
	$payment_plugin[$x]['message']['es'] = '<p>Tu pago lo puedes realizar por medio de cualquiera de las siguientes formas desde México:</p>
<ul>
	<li>Haciendo una transferencia bancaria al número CLABE 002 180 70032740993 9 cuenta registrada en Banamex,</li>
	<li>Depositando en cualquiera de las tienda OXXO al número de tarjeta 4766 8400 1073 9899,</li>
	<li>Haciendo un pago vía TRANSFER al número 312 595 7212.</li>
</ul>
<p>O vía Interac desde Canadá:</p>
<ul>
	<li>payments@to-call.me</li>
</ul>
<p>Recuerda enviarnos tu comprobante con el número de intención de pago único escrito en él.</p>';
	$payment_plugin[$x]['message']['en'] = '<p>You can send your payment to the next accounts from Mexico:</p>
<ul>
	<li>Making a deposit into CLABE number 002 180 70032740993 9 in Banamex,</li>
	<li>Making a deposit at OXXO to number 4766 8400 1073 9899,</li>
	<li>Sending a payment by TRANSFER to number 513 595 7212.</li>
</ul>
<p>Or by Interact from Canada:</p>
<ul>
	<li>payments@to-call.me</li>
</ul>
<p>Remember to send us your prof of payment with the payment id on it.</p>';
	$payment_plugin[$x]['percentage_comission'] = 0;
	$payment_plugin[$x]['fixed_comission'] = 0;
	$payment_plugin[$x]['fixed_comission_currency'] = 'USD';
