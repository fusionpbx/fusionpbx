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
	Portions created by the Initial Developer are Copyright (C) 2008-2022
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//set the include path
	if (defined('STDIN')) {	
		$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
		set_include_path(parse_ini_file($conf[0])['document.root']);
	}
	else {
		exit;
	}

//include files
	require_once "resources/require.php";

//define a function to remove html tags
	if (!function_exists('remove_tags')) {
		function remove_tags($string) {
			//remove HTML tags
			$string = preg_replace ('/<[^>]*>/', ' ', $string);

			//remove control characters
			$string = str_replace("\r", '', $string);    // --- replace with empty space
			$string = str_replace("\n", ' ', $string);   // --- replace with space
			$string = str_replace("\t", ' ', $string);   // --- replace with space

			//remove multiple spaces
			$string = trim(preg_replace('/ {2,}/', ' ', $string));
			return $string;
		}
	}

//set init settings
	ini_set('max_execution_time',1800); //30 minutes
	ini_set('memory_limit', '512M');

//listen for standard input
	if ($msg == '') {
		$fd = fopen("php://stdin", "r");
		$msg = file_get_contents ("php://stdin");
		fclose($fd);
	}

//save output to
	$fp = fopen(sys_get_temp_dir()."/mailer-app.log", "a");

//prepare the output buffers
	ob_end_clean();
	ob_start();

//message divider for log file
	echo "\n\n======================================================================================================================================================================================\n\n";

//testing show the raw email
	//echo "Message: \n".$msg."\n";

//includes
	//require_once('resources/pop3/mime_parser.php');
	//require_once('resources/pop3/rfc822_addresses.php');
	//if (file_exists($_SERVER["PROJECT_ROOT"]."/app/emails/email_transcription.php")) {
	//	require_once($_SERVER["PROJECT_ROOT"]."/app/emails/email_transcription.php");
	//}

//parse the email
	$email = new email;
	$email->parse($msg);
	$email->debug_level = 0;
	$email->method = 'direct';
	$headers = $email->headers;
	$sent = $email->send();

//debug information
	/*
	echo "<pre>\n";
	echo "recipients ".print_r($email->recipients)."\n";
	echo "recipients ".$email->recipients."\n";
	echo "subject ".$email->subject."\n";
	echo "body ".$email->body."\n";
	echo "from_address ".$email->from_address."\n";
	echo "from_name ".$email->from_name."\n";
	echo "headers ".print_r($email->headers)."\n";
	//echo "attachments ".print_r($email->attachments)."\n";
	echo "</pre>\n";
	exit;
	*/

//send the email
	if (!$sent) {
		echo "Mailer Error: ".$email_error."\n\n";

		$call_uuid = $headers["X-FusionPBX-Call-UUID"];
		if ($resend == true) {
			echo "Retained in v_email_logs \n";
		}
		else {
			//log the message in database for review
			if (!isset($email_log_uuid)) {
				//build insert array
					$email_log_uuid = uuid();
					$array['email_logs'][0]['email_log_uuid'] = $email_log_uuid;
					if (is_uuid($call_uuid)) {
						$array['email_logs'][0]['call_uuid'] = $call_uuid;
					}
					if (isset($headers["X-FusionPBX-Domain-UUID"])) {
						$array['email_logs'][0]['domain_uuid'] = $headers["X-FusionPBX-Domain-UUID"];
					}
					$array['email_logs'][0]['sent_date'] = 'now()';
					if (isset($headers["X-FusionPBX-Email-Type"])) {
						$array['email_logs'][0]['type'] = $headers["X-FusionPBX-Email-Type"];
					}
					$array['email_logs'][0]['status'] = 'failed';
					$array['email_logs'][0]['email'] = str_replace("'", "''", $msg);

				//grant temporary permissions
					$p = new permissions;
					$p->add('email_log_add', 'temp');

				//execute insert
					$database = new database;
					$database->app_name = 'v_mailto';
					$database->app_uuid = 'ba41954e-9d21-4b10-bbc2-fa5ceabeb184';
					$database->save($array);
					unset($array);

				//revoke temporary permissions
					$p->delete('email_log_add', 'temp');
			}

			echo "Retained in v_email_logs as email_log_uuid = ".$email_log_uuid."\n";
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

/*
//save in /tmp as eml file
	$fp = fopen(sys_get_temp_dir()."/email.eml", "w");
	ob_end_clean();
	ob_start();

	$sql = "select email from v_email_logs where email_log_uuid = :email_log_uuid ";
	$parameters['email_log_uuid'] = $email_log_uuid;
	$database = new database;
	$email = $database->select($sql, $parameters, 'column');
	echo $email;
	unset($sql, $parameters, $email);

	$content = ob_get_contents(); //get the output from the buffer
	$content = str_replace("<br />", "", $content);

	ob_end_clean(); //clean the buffer

	fwrite($fp, $content);
	fclose($fp);
*/

?>
