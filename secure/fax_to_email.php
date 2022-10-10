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
	James Rose <james.o.rose@gmail.com>
*/

$output_type = "file"; //file or console

if (defined('STDIN')) {
	//get the document root php file must be executed with the full path
		$document_root = str_replace("\\", "/", $_SERVER["PHP_SELF"]);
		$document_root = str_replace("\\", "/", $_SERVER["PHP_SELF"]);
		preg_match("/^(.*)\/secure\/.*$/", $document_root, $matches);
		$document_root = $matches[1];
	//set the include path
		set_include_path($document_root);
		$_SERVER["DOCUMENT_ROOT"] = $document_root;
		//echo "$document_root is document_root\n";
}

$IS_WINDOWS = stristr(PHP_OS, 'WIN') ? true : false;

if (!function_exists('exec_in_dir')) {
	function exec_in_dir($dir, $cmd, &$ok) {
		$args = func_get_args();
		$cwd = getcwd();
		chdir($dir);
		$output = array();
		$ret = 0;
		$result = exec($cmd, $output, $ret);
		if ($cwd)
			chdir($cwd);
		$ok = ($ret == 0);
		return implode("\n", $output);
	}
}

if (!function_exists('correct_path')) {
	function correct_path($p) {
		global $IS_WINDOWS;
		if ($IS_WINDOWS) {
			return str_replace('/', '\\', $p);
		}
		return $p;
	}
}

if (!function_exists('path_join')) {
	function path_join() {
		$args = func_get_args();
		$paths = array();
		foreach ($args as $arg) {
			$paths = array_merge($paths, (array)$arg);
		}

		$prefix = null;
		foreach($paths as &$path) {
			if ($prefix === null && strlen($path) > 0) {
				if (substr($path, 0, 1) == '/') $prefix = '/';
				else $prefix = '';
			}
			$path = trim( $path, '/' );
		}

		if ($prefix === null) {
			return '';
		}

		$paths = array_filter($paths);
		return $prefix . implode('/', $paths);
	}
}

if (!function_exists('tiff2pdf')) {
	function tiff2pdf($tiff_file_name) {
		//convert the tif to a pdf
		//Ubuntu: apt-get install libtiff-tools

		global $IS_WINDOWS;

		if (!file_exists($tiff_file_name)) {
			echo "tiff file does not exists";
			return false; // "tiff file does not exists";
		}

		$GS = $IS_WINDOWS ? 'gswin32c' : 'gs';
		$tiff_file = pathinfo($tiff_file_name);
		$dir_fax = $tiff_file['dirname'];
		$fax_file_name = $tiff_file['filename'];
		$pdf_file_name = path_join($dir_fax, $fax_file_name . '.pdf');

		if (file_exists($pdf_file_name)) {
			return $pdf_file_name;
		}

		$dir_fax_temp = $_SESSION['server']['temp']['dir'];
		if (!$dir_fax_temp) {
			$dir_fax_temp = path_join(dirname($dir_fax), 'temp');
		}

		if (!file_exists($dir_fax_temp)) {
			echo "can not create temporary directory";
			return false; //
		}

		$cmd  = "tiffinfo " . correct_path($tiff_file_name) . ' | grep "Resolution:"';
		$ok   = false;
		$resp = exec_in_dir($dir_fax, $cmd, $ok);
		if (!$ok) {
			echo "can not find fax resoulution";
			return false; // "can not find fax resoulution"
		}

		$ppi_w = 0;
		$ppi_h = 0;
		$tmp = array();
		if (preg_match('/Resolution.*?(\d+).*?(\d+)/', $resp, $tmp)) {
			$ppi_w = $tmp[1];
			$ppi_h = $tmp[2];
		}

		$cmd = "tiffinfo " . $tiff_file_name . ' | grep "Image Width:"';
		$resp = exec_in_dir($dir_fax, $cmd, $ok);
		if (!$ok) {
			echo "can not find fax size";
			return false; // "can not find fax size"
		}

		$pix_w = 0;
		$pix_h = 0;
		$tmp = array();
		if (preg_match('/Width.*?(\d+).*?Length.*?(\d+)/', $resp, $tmp)) {
			$pix_w = $tmp[1];
			$pix_h = $tmp[2];
		}

		$page_width  = $pix_w / $ppi_w;
		$page_height = $pix_h / $ppi_h;
		$page_size   = 'a4';

		if (($page_width > 8.4) && ($page_height > 13)) {
			$page_width  = 8.5;
			$page_height = 14;
			$page_size   = 'legal';
		}
		elseif (($page_width > 8.4) && ($page_height < 12)) {
			$page_width  = 8.5;
			$page_height = 11;
			$page_size   = 'letter';
		}
		elseif (($page_width < 8.4) && ($page_height > 11)) {
			$page_width  = 8.3;
			$page_height = 11.7;
			$page_size   = 'a4';
		}
		$page_width  = sprintf('%.4f', $page_width);
		$page_height = sprintf('%.4f', $page_height);

		$cmd = implode(' ', array('tiff2pdf', 
			'-o', correct_path($pdf_file_name),
			correct_path($tiff_file_name),
		));

		$resp = exec_in_dir($dir_fax, $cmd, $ok);

		if (!file_exists($pdf_file_name)) {
			echo "can not create pdf: $resp";
			return false;
		}

		return $pdf_file_name;
	}
}

if (!function_exists('fax_split_dtmf')) {
	function fax_split_dtmf(&$fax_number, &$fax_dtmf) {
		$tmp = array();
		$fax_dtmf = '';
		if (preg_match('/^\s*(.*?)\s*\((.*)\)\s*$/', $fax_number, $tmp)) {
			$fax_number = $tmp[1];
			$fax_dtmf = $tmp[2];
		}
	}
}

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	include "resources/classes/event_socket.php";
	include "resources/phpmailer/class.phpmailer.php";
	include "resources/phpmailer/class.smtp.php"; // optional, gets called from within class.phpmailer.php if not already loaded

//set php ini values
	ini_set(max_execution_time,900); //15 minutes
	ini_set('memory_limit', '96M');

//start the to cache the output
	if ($output_type == "file") {
		ob_end_clean();
		ob_start();
	}

//add a delimeter to the log
	echo "\n---------------------------------\n";

//get the parameters and save them as variables
	$php_version = substr(phpversion(), 0, 1);
	if ($php_version == '4') {
		$domain_name = $_REQUEST["domain"];
		$fax_email = $_REQUEST["email"];
		$fax_extension = $_REQUEST["extension"];
		$fax_file = $_REQUEST["name"];
		$fax_messages = $_REQUEST["messages"];
		$caller_id_name = $_REQUEST["caller_id_name"];
		$caller_id_number = $_REQUEST["caller_id_number"];
		$fax_relay = $_REQUEST["retry"];
		$mail_from_address = $_REQUEST["mailfrom_address"];
	}
	else {
		$tmp_array = explode("=", $_SERVER["argv"][1]);
		$fax_email = $tmp_array[1];
		unset($tmp_array);

		$tmp_array = explode("=", $_SERVER["argv"][2]);
		$fax_extension = $tmp_array[1];
		unset($tmp_array);

		$tmp_array = explode("=", $_SERVER["argv"][3]);
		$fax_file = $tmp_array[1];
		unset($tmp_array);

		$tmp_array = explode("=", $_SERVER["argv"][4]);
		$fax_messages = $tmp_array[1];
		unset($tmp_array);

		$tmp_array = explode("=", $_SERVER["argv"][5]);
		$domain_name = $tmp_array[1];
		unset($tmp_array);

		$tmp_array = explode("=", $_SERVER["argv"][6]);
		$caller_id_name = $tmp_array[1];
		unset($tmp_array);

		$tmp_array = explode("=", $_SERVER["argv"][7]);
		$caller_id_number = $tmp_array[1];
		unset($tmp_array);

		$tmp_array = explode("=", $_SERVER["argv"][8]);
		$fax_relay = $tmp_array[1];
		unset($tmp_array);

		$tmp_array = explode("=", $_SERVER["argv"][9]);
		$fax_prefix = $tmp_array[1];
		unset($tmp_array);

		$tmp_array = explode("=", $_SERVER["argv"][10]);
		$mail_from_address = $tmp_array[1];
		unset($tmp_array);

		//$tmp_array = explode("=", $_SERVER["argv"][10]);
		//$destination_number = $tmp_array[1];
		//unset($tmp_array);
	}

//get the fax file name (only) if a full path
	$fax_path = pathinfo($fax_file);
	$fax_file_only = $fax_path['basename'];
	$fax_file_name = $fax_path['filename'];
	$dir_fax = $fax_path['dirname'];

//get the domain_uuid from the database
	$sql = "select * from v_domains ";
	$sql .= "where domain_name = :domain_name ";
	$parameters['domain_name'] = $domain_name;
	$database = new database;
	$result = $database->select($sql, $parameters, 'all');
	if (is_array($result) && @sizeof($result) != 0) {
		foreach ($result as &$row) {
			//set the domain variables
				$domain_uuid = $row["domain_uuid"];
				$_SESSION["domain_uuid"] = $row["domain_uuid"];
				$_SESSION["domain_name"] = $domain_name;
			//set the setting arrays
				$domain = new domains();
				$domain->db = $db;
				$domain->set();
		}
	}
	unset($sql, $parameters, $result);

//prepare smtp server settings
	$email_from_address = $_SESSION['email']['smtp_from']['text'];
	$email_from_name = $_SESSION['email']['smtp_from_name']['text'];
	if (isset($_SESSION['fax']['smtp_from']['text']) && strlen($_SESSION['fax']['smtp_from']['text']) > 0) {
		$email_from_address = $_SESSION['fax']['smtp_from']['text'];
	}
	if (isset($_SESSION['fax']['smtp_from_name']['text']) && strlen($_SESSION['fax']['smtp_from_name']['text']) > 0) {
		$email_from_name = $_SESSION['fax']['smtp_from_name']['text'];
	}

//get the fax settings from the database
	$sql = "select * from v_fax ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and fax_extension = :fax_extension ";
	$parameters['domain_uuid'] = $domain_uuid;
	$parameters['fax_extension'] = $fax_extension;
	$database = new database;
	$row = $database->select($sql, $parameters, 'row');
	if (is_array($row) && @sizeof($row) != 0) {
		$fax_email = $row["fax_email"];
		$fax_uuid = $row["fax_uuid"];
		$fax_accountcode = $row["fax_accountcode"];
		$fax_prefix = $row["fax_prefix"];
		$fax_pin_number = $row["fax_pin_number"];
		$fax_caller_id_name = $row["fax_caller_id_name"];
		$fax_caller_id_number = $row["fax_caller_id_number"];
		$fax_forward_number = $row["fax_forward_number"];
		$fax_description = $row["fax_description"];
		$fax_email_inbound_subject_tag = $row['fax_email_inbound_subject_tag'];
		$mail_to_address = $fax_email;
	}
	unset($sql, $parameters, $row);

//set the fax directory
	if (!file_exists($dir_fax) || !file_exists(path_join($dir_fax, $fax_file_only))) {
		$dir_fax = $_SESSION['switch']['storage']['dir'].'/fax/'.$domain_name.'/'.$fax_extension.'/inbox';
		if (!file_exists($dir_fax) || !file_exists(path_join($dir_fax, $fax_file_only))) {
			$dir_fax = $_SESSION['switch']['storage']['dir'].'/fax/'.$fax_extension.'/inbox';
		}
	}
	$fax_file = path_join($dir_fax, $fax_file_only);

//used for debug
	echo "fax_prefix: $fax_prefix\n";
	echo "mail_to_adress: $mail_to_address\n";
	echo "fax_email: $fax_email\n";
	echo "fax_extension: $fax_extension\n";
	echo "fax_name: $fax_file_only\n";
	echo "dir_fax: $dir_fax\n";
	echo "full_path: $fax_file\n";

	$pdf_file = tiff2pdf($fax_file);
	echo "file: $pdf_file \n";
	if (!$pdf_file) {
		$fax_file_warning = 'warning: Fax image not available on server.';
	}
	else{
		$fax_file_warning = '';
	}

	echo "pdf file: $pdf_file\n";

//forward the fax
	if (file_exists($fax_file)) {
		if (strpos($fax_file_name,'#') !== false) {
			$tmp = explode("#",$fax_file_name);
			$fax_forward_number = $fax_prefix.$tmp[0];
		}

		if (isset($fax_forward_number) && strlen($fax_forward_number) > 0) {
			//show info
				echo "fax_forward_number: $fax_forward_number\n";

			//add fax to the fax queue or send it directly
			if ($_SESSION['fax_queue']['enabled']['boolean'] == 'true') {
				//build an array to add the fax to the queue
				$array['fax_queue'][0]['fax_queue_uuid'] = uuid();
				$array['fax_queue'][0]['domain_uuid'] = $domain_uuid;
				$array['fax_queue'][0]['fax_uuid'] = $fax_uuid;
				$array['fax_queue'][0]['fax_date'] = 'now()';
				$array['fax_queue'][0]['hostname'] = gethostname();
				$array['fax_queue'][0]['fax_caller_id_name'] = $fax_caller_id_name;
				$array['fax_queue'][0]['fax_caller_id_number'] = $fax_caller_id_number;
				$array['fax_queue'][0]['fax_number'] = $fax_forward_number;
				$array['fax_queue'][0]['fax_prefix'] = $fax_prefix;
				$array['fax_queue'][0]['fax_email_address'] = $mail_to_address;
				$array['fax_queue'][0]['fax_file'] = $fax_file;
				$array['fax_queue'][0]['fax_status'] = 'waiting';
				$array['fax_queue'][0]['fax_retry_count'] = 0;
				$array['fax_queue'][0]['fax_accountcode'] = $fax_accountcode;

				//add temporary permisison
				$p = new permissions;
				$p->add('fax_queue_add', 'temp');

				//save the data
				$database = new database;
				$database->app_name = 'fax queue';
				$database->app_uuid = '3656287f-4b22-4cf1-91f6-00386bf488f4';
				$database->save($array);

				//remove temporary permisison
				$p->delete('fax_queue_add', 'temp');
				
				//add message to show in the browser
				message::add($text['confirm-queued']);
			}
			else {
				fax_split_dtmf($fax_forward_number, $fax_dtmf);

				$fax_send_mode = $_SESSION['fax']['send_mode']['text'];
				if (strlen($fax_send_mode) == 0) {
					$fax_send_mode = 'direct';
				}

				$route_array = outbound_route_to_bridge($domain_uuid, $fax_forward_number);
				if (count($route_array) == 0) {
					//send the internal call to the registered extension
						$fax_uri = "user/".escapeshellarg($fax_forward_number)."@".escapeshellarg($domain_name);
						$fax_variables = "";
				}
				else {
					//send the external call
						$fax_uri = $route_array[0];
						$fax_variables = "";
						foreach($_SESSION['fax']['variable'] as $variable) {
							$fax_variables .= escapeshellarg($variable).",";
						}
				}

				//build the dial string
				$dial_string = "absolute_codec_string='PCMU,PCMA',";
				$dial_string .= "accountcode='"                  . escapeshellarg($fax_accountcode)         . "',";
				$dial_string .= "sip_h_X-accountcode='"          . escapeshellarg($fax_accountcode)         . "',";
				$dial_string .= "domain_uuid="                   . escapeshellarg($domain_uuid)             . ",";
				$dial_string .= "domain_name="                   . escapeshellarg($domain_name)             . ",";
				$dial_string .= "origination_caller_id_name='"   . escapeshellarg($fax_caller_id_name)      . "',";
				$dial_string .= "origination_caller_id_number='" . escapeshellarg($fax_caller_id_number)    . "',";
				$dial_string .= "fax_ident='"                    . escapeshellarg($fax_caller_id_number)    . "',";
				$dial_string .= "fax_header='"                   . escapeshellarg($fax_caller_id_name)      . "',";
				$dial_string .= "fax_file='"                     . escapeshellarg($fax_file)                . "',";

				if ($fax_send_mode != 'queue') {
					//add more ot the dial string
						$dial_string .= $fax_variables;
						$dial_string .= "mailto_address='"     . escapeshellarg($mail_to_address)   . "',";
						$dial_string .= "mailfrom_address='"   . escapeshellarg($mail_from_address) . "',";
						$dial_string .= "fax_uri="             . escapeshellarg($fax_uri)  . ",";
						$dial_string .= "fax_retry_attempts=1" . ",";
						$dial_string .= "fax_retry_limit=20"   . ",";
						$dial_string .= "fax_retry_sleep=180"  . ",";
						$dial_string .= "fax_verbose=true"     . ",";
						$dial_string .= "fax_use_ecm=off"      . ",";
						$dial_string .= "api_hangup_hook='lua fax_retry.lua'";
						$dial_string = "{" . $dial_string . "}" . escapeshellarg($fax_uri)." &txfax('".escapeshellarg($fax_file)."')";

					//get the event socket information
						$sql = "select * from v_settings ";
						$database = new database;
						$row = $database->select($sql, $parameters, 'row');
						if (is_array($row) && @sizeof($row) != 0) {
							$event_socket_ip_address = $row["event_socket_ip_address"];
							$event_socket_port = $row["event_socket_port"];
							$event_socket_password = $row["event_socket_password"];
						}
						unset($sql);

					//create the event socket connection
						$fp = event_socket_create($event_socket_ip_address, $event_socket_port, $event_socket_password);

					//send the command with event socket
						if ($fp) {
							//prepare the fax originate command
								$cmd = "api originate ".$dial_string;
							//send info to the log
								echo "fax forward\n";
								echo $cmd."\n";
							//send the command to event socket
								$response = event_socket_request($fp, $cmd);
								$response = str_replace("\n", "", $response);
							//send info to the log
								echo "response: ".$response."\n";
							//get the uuid
								$uuid = str_replace("+OK ", "", $response);
							//close event socket
								fclose($fp);
						}
				}
			}
		}
	}

//send the email
	if (strlen($fax_email) > 0 && file_exists($fax_file)) {

		//get the language code
			$language_code = $_SESSION['domain']['language']['code'];

		//get the template subcategory
			if ($fax_relay == 'true') {
				$template_subcategory = 'relay';
			}
			else {
				$template_subcategory = 'inbound';
			}

		//get the email template from the database
			if (isset($fax_email) && strlen($fax_email) > 0) {
				$sql = "select template_subject, template_body from v_email_templates ";
				$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
				$sql .= "and template_language = :template_language ";
				$sql .= "and template_category = :template_category ";
				$sql .= "and template_subcategory = :template_subcategory ";
				$sql .= "and template_type = :template_type ";
				$sql .= "and template_enabled = 'true' ";
				$parameters['domain_uuid'] = $domain_uuid;
				$parameters['template_language'] = $language_code;
				$parameters['template_category'] = 'fax';
				$parameters['template_subcategory'] = $template_subcategory;
				$parameters['template_type'] = 'html';
				$database = new database;
				$row = $database->select($sql, $parameters, 'row');
				if (is_array($row)) {
					$email_subject = $row['template_subject'];
					$email_body = $row['template_body'];
				}
				unset($sql, $parameters);
			}

		//replace variables in email subject
			$email_subject = str_replace('${domain_name}', $domain_name, $email_subject);
			$email_subject = str_replace('${fax_file_name}', $fax_file_name, $email_subject);
			$email_subject = str_replace('${fax_extension}', $fax_extension, $email_subject);
			$email_subject = str_replace('${fax_messages}', $fax_messages, $email_subject);
			$email_subject = str_replace('${fax_file_warning}', $fax_file_warning, $email_subject);
			$email_subject = str_replace('${fax_subject_tag}', $fax_email_inbound_subject_tag, $email_subject);

		//replace variables in email body
			$email_body = str_replace('${domain_name}', $domain_name, $email_body);
			$email_body = str_replace('${fax_file_name}', $fax_file_name, $email_body);
			$email_body = str_replace('${fax_extension}', $fax_extension, $email_body);
			$email_body = str_replace('${fax_messages}', $fax_messages, $email_body);
			$email_body = str_replace('${fax_file_warning}', $fax_file_warning, $email_body);
			$email_body = str_replace('${fax_subject_tag}', $fax_email_inbound_subject_tag, $email_body);

		//debug info
			//echo "<hr />\n";
			//echo "email_address ".$fax_email."<br />\n";
			//echo "email_subject ".$email_subject."<br />\n";
			//echo "email_body ".$email_body."<br />\n";
			//echo "<hr />\n";

		//send the email
			if (isset($fax_email) && strlen($fax_email) > 0) {
				//add the attachment
				if (strlen($fax_file_name) > 0) {
					$email_attachments[0]['type'] = 'file';
					if ($pdf_file && file_exists($pdf_file)) {
						$email_attachments[0]['name'] = $fax_file_name.'.pdf';
						$email_attachments[0]['value'] = $pdf_file;
					}
					else {
						$email_attachments[0]['name'] = $fax_file_name.'.tif';
						$email_attachments[0]['value'] = $fax_file;
					}
				}

				//$email_response = send_email($email_address, $email_subject, $email_body);
				$email = new email;
				$email->recipients = $fax_email;
				$email->subject = $email_subject;
				$email->body = $email_body;
				$email->from_address = $email_from_address;
				$email->from_name = $email_from_name;
				$email->attachments = $email_attachments;
				//$email->debug_level = 3;
				$response = $mail->error;
				$sent = $email->send();
			}

		//output to the log
			echo "email_from_address: ".$email_from_address."\n";
			echo "email_from_name: ".$email_from_address."\n";
			echo "email_subject: $email_subject\n";

		//send the email
			if ($sent) {
				echo "Mailer Error";
				$email_status='failed';
			}
			else {
				echo "Message sent!";
				$email_status='ok';
			}
	}

//when sending an email the following files are created:
	//     /usr/local/freeswitch/storage/fax
	//        emailed_faxes.log - this is a log of all the faxes we have successfully emailed.  (note that we need to work out how to rotate this log)
	//        failed_fax_emails.log - this is a log of all the faxes we have failed to email.  This log is in the form of instructions that we can re-execute in order to retry.
	//            Whenever this exists there should be an at job present to run it sometime in the next 3 minutes (check with atq).  If we succeed in sending the messages
	//            this file will be removed.
	//     /tmp
	//        fax_email_retry.sh - this is the renamed failed_fax_emails.log and is created only at the point in time that we are trying to re-send the emails.  Note however
	//            that this will continue to exist even if we succeed as we do not delete it when finished.
	//        failed_fax_emails.sh - this is created when we have a email we need to re-send.  At the time it is created, an at job is created to execute it in 3 minutes time,
	//            this allows us to try sending the email again at that time.  If the file exists but there is no at job this is because there are no longer any emails queued
	//            as we have successfully sent them all.
	if ($_SESSION['fax_queue']['enabled']['boolean'] != 'true' && strlen($fax_email) > 0 && file_exists($fax_file)) {
		if (stristr(PHP_OS, 'WIN')) {
			//not compatible with windows
		}
		else {
			$fax_to_email_queue_dir = $_SESSION['switch']['storage']['dir']."/fax";
			if ($email_status == 'ok') {
				//log the success
					$fp = fopen($fax_to_email_queue_dir."/emailed_faxes.log", "a");
					fwrite($fp, $fax_file_name." received on ".$fax_extension." emailed to ".$fax_email." ".$fax_messages."\n");
					fclose($fp);
			}
		}
	}

//open the file for writing
	if ($output_type == "file") {
		//open the file
			$fp = fopen($_SESSION['server']['temp']['dir']."/fax_to_email.log", "w");
		//get the output from the buffer
			$content = ob_get_contents();
		//clean the buffer
			ob_end_clean();
		//write the contents of the buffer
			fwrite($fp, $content);
			fclose($fp);
	}

?>
