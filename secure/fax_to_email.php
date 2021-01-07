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
	Portions created by the Initial Developer are Copyright (C) 2008-2018
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
	function exec_in_dir($dir, $cmd, &$ok){
		$args = func_get_args();
		$cwd = getcwd();
		chdir($dir);
		$output = array();
		$ret = 0;
		$result = exec($cmd, $output, $ret);
		if ($cwd)
			chdir($cwd);
		$ok = ($ret == 0);
		return join($output, "\n");
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

		if ($prefix === null){
			return '';
		}

		$paths = array_filter($paths);
		return $prefix . join('/', $paths);
	}
}

if (!function_exists('tiff2pdf')) {
	function tiff2pdf($tiff_file_name){
		//convert the tif to a pdf
		//Ubuntu: apt-get install libtiff-tools

		global $IS_WINDOWS;

		if (!file_exists($tiff_file_name)){
			echo "tiff file does not exists";
			return false; // "tiff file does not exists";
		}

		$GS = $IS_WINDOWS ? 'gswin32c' : 'gs';
		$tiff_file = pathinfo($tiff_file_name);
		$dir_fax = $tiff_file['dirname'];
		$fax_file_name = $tiff_file['filename'];
		$pdf_file_name = path_join( $dir_fax, $fax_file_name . '.pdf' );

		if (file_exists($pdf_file_name))
			return $pdf_file_name;

		$dir_fax_temp = $_SESSION['server']['temp']['dir'];
		if (!$dir_fax_temp){
			$dir_fax_temp = path_join(dirname($dir_fax), 'temp');
		}

		if (!file_exists($dir_fax_temp)){
			echo "can not create temporary directory";
			return false; //
		}

		$cmd  = "tiffinfo " . correct_path($tiff_file_name) . ' | grep "Resolution:"';
		$ok   = false;
		$resp = exec_in_dir($dir_fax, $cmd, $ok);
		if (!$ok){
			echo "can not find fax resoulution";
			return false; // "can not find fax resoulution"
		}

		$ppi_w = 0;
		$ppi_h = 0;
		$tmp = array();
		if (preg_match('/Resolution.*?(\d+).*?(\d+)/', $resp, $tmp)){
			$ppi_w = $tmp[1];
			$ppi_h = $tmp[2];
		}

		$cmd = "tiffinfo " . $tiff_file_name . ' | grep "Image Width:"';
		$resp = exec_in_dir($dir_fax, $cmd, $ok);
		if (!$ok){
			echo "can not find fax size";
			return false; // "can not find fax size"
		}

		$pix_w = 0;
		$pix_h = 0;
		$tmp = array();
		if (preg_match('/Width.*?(\d+).*?Length.*?(\d+)/', $resp, $tmp)){
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

		$cmd = join(array('tiff2pdf', 
			'-o', correct_path($pdf_file_name),
			correct_path($tiff_file_name),
		), ' ');

		$resp = exec_in_dir($dir_fax, $cmd, $ok);

		if (!file_exists($pdf_file_name)){
			echo "can not create pdf: $resp";
			return false;
		}

		return $pdf_file_name;
	}
}

if (!function_exists('fax_enqueue')) {
	function fax_enqueue($fax_uuid, $fax_file, $wav_file, $reply_address, $fax_uri, $fax_dtmf, $dial_string) {
		global $db_type;

		$fax_task_uuid = uuid();
		$dial_string .= "fax_task_uuid='".$fax_task_uuid."',";
		$description = ''; //! @todo add description
		if ($db_type == "pgsql") {
			$date_utc_now_sql  = "NOW()";
		}
		if ($db_type == "mysql") {
			$date_utc_now_sql  = "UTC_TIMESTAMP()";
		}
		if ($db_type == "sqlite") {
			$date_utc_now_sql  = "datetime('now')";
		}

		$sql = "insert into v_fax_tasks";
		$sql .= "( ";
		$sql .= "fax_task_uuid, ";
		$sql .= "fax_uuid, ";
		$sql .= "task_next_time, ";
		$sql .= "task_lock_time, ";
		$sql .= "task_fax_file, ";
		$sql .= "task_wav_file, ";
		$sql .= "task_uri, ";
		$sql .= "task_dial_string, ";
		$sql .= "task_dtmf, ";
		$sql .= "task_interrupted, ";
		$sql .= "task_status, ";
		$sql .= "task_no_answer_counter, ";
		$sql .= "task_no_answer_retry_counter,";
		$sql .= "task_retry_counter, ";
		$sql .= "task_reply_address, ";
		$sql .= "task_description ";
		$sql .= ") ";
		$sql .= "values ( ";
		$sql .= ":fax_task_uuid, ";
		$sql .= ":fax_uuid, ";
		$sql .= $date_utc_now_sql.", ";
		$sql .= "null, ";
		$sql .= ":fax_file, ";
		$sql .= ":wav_file, ";
		$sql .= ":fax_uri, ";
		$sql .= ":dial_string, ";
		$sql .= ":fax_dtmf, ";
		$sql .= "'false', ";
		$sql .= "0, ";
		$sql .= "0, ";
		$sql .= "0, ";
		$sql .= "0, ";
		$sql .= ":reply_address, ";
		$sql .= ":description ";
		$sql .= ") ";
		$parameters['fax_task_uuid'] = $fax_task_uuid;
		$parameters['fax_uuid'] = $fax_uuid;
		$parameters['fax_file'] = $fax_file;
		$parameters['wav_file'] = $wav_file;
		$parameters['fax_uri'] = $fax_uri;
		$parameters['dial_string'] = $dial_string;
		$parameters['fax_dtmf'] = $fax_dtmf;
		$parameters['reply_address'] = $reply_address;
		$parameters['description'] = $description;
		$database = new database;
		$database->execute($sql, $parameters);
		$response = $database->message();
		if ($response['message'] == 'OK' && $response['code'] == '200') {
			return 'Success';
		}
		else{
			//! @todo log error
			view_array($response);
			return 'Failed';
		}
		unset($sql, $parameters, $response);
	}
}

if (!function_exists('fax_split_dtmf')) {
	function fax_split_dtmf(&$fax_number, &$fax_dtmf){
		$tmp = array();
		$fax_dtmf = '';
		if (preg_match('/^\s*(.*?)\s*\((.*)\)\s*$/', $fax_number, $tmp)){
			$fax_number = $tmp[1];
			$fax_dtmf = $tmp[2];
		}
	}
}

//includes
	if (!defined('STDIN')) { include "root.php"; }
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
		$mailfrom_address = $_REQUEST["mailfrom_address"];
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
		$mailfrom_address = $tmp_array[1];
		unset($tmp_array);

		//$tmp_array = explode("=", $_SERVER["argv"][10]);
		//$destination_number = $tmp_array[1];
		//unset($tmp_array);
	}
	$mailto_address = $fax_email;

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
	// load default smtp settings
	$smtp['method'] = $_SESSION['email']['smtp_method']['text'];
	$smtp['host'] = (strlen($_SESSION['email']['smtp_host']['text'])?$_SESSION['email']['smtp_host']['text']:'127.0.0.1');
	if (isset($_SESSION['email']['smtp_port'])) {
		$smtp['port'] = (int)$_SESSION['email']['smtp_port']['numeric'];
	}
	else {
		$smtp['port'] = 0;
	}

	$smtp['secure'] = $_SESSION['email']['smtp_secure']['text'];
	$smtp['auth'] = $_SESSION['email']['smtp_auth']['text'];
	$smtp['username'] = $_SESSION['email']['smtp_username']['text'];
	$smtp['password'] = $_SESSION['email']['smtp_password']['text'];
	$smtp['from'] = $_SESSION['email']['smtp_from']['text'];
	$smtp['from_name'] = $_SESSION['email']['smtp_from_name']['text'];

	if (isset($_SESSION['fax']['smtp_from']['text']) && strlen($_SESSION['fax']['smtp_from']['text']) > 0) {
		$smtp['from'] = $_SESSION['fax']['smtp_from']['text'];
	}
	if (isset($_SESSION['fax']['smtp_from_name']['text']) && strlen($_SESSION['fax']['smtp_from_name']['text']) > 0) {
		$smtp['from_name'] = $_SESSION['fax']['smtp_from_name']['text'];
	}

	// overwrite with domain-specific smtp server settings, if any
	if (is_uuid($domain_uuid)) {
		$sql = "select ";
		$sql .= "domain_setting_subcategory, ";
		$sql .= "domain_setting_value ";
		$sql .= "from v_domain_settings ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and ( ";
		$sql .= "domain_setting_category = 'email' ";
		$sql .= "or domain_setting_category = 'fax' ";
		$sql .= ") ";
		$sql .= "and domain_setting_name = 'text' ";
		$sql .= "and domain_setting_enabled = 'true' ";
		$parameters['domain_name'] = $domain_name;
		$database = new database;
		$result = $database->select($sql, $parameters, 'all');
		if (is_array($result) && @sizeof($result) != 0) {
			foreach ($result as $row) {
				if ($row['domain_setting_value'] != '') {
					$smtp[str_replace('smtp_','',$row["domain_setting_subcategory"])] = $row['domain_setting_value'];
				}
			}
		}
		unset($sql, $parameters, $result, $row);
	}

	// value adjustments
	$smtp['method'] = ($smtp['method'] == '') ? 'smtp' : $smtp['method'];
	$smtp['auth'] = ($smtp['auth'] == "true") ? true : false;
	$smtp['password'] = ($smtp['password'] != '') ? $smtp['password'] : null;
	$smtp['secure'] = ($smtp['secure'] != "none") ? $smtp['secure'] : null;
	$smtp['username'] = ($smtp['username'] != '') ? $smtp['username'] : null;

//get the fax details from the database
	$sql = "select * from v_fax ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and fax_extension = :fax_extension ";
	$parameters['domain_uuid'] = $_SESSION["domain_uuid"];
	$parameters['fax_extension'] = $fax_extension;
	$database = new database;
	$row = $database->select($sql, $parameters, 'row');
	if (is_array($row) && @sizeof($row) != 0) {
		//$fax_email = $row["fax_email"];
		$fax_uuid = $row["fax_uuid"];
		$fax_accountcode = $row["fax_accountcode"];
		$fax_prefix = $row["fax_prefix"];
		$fax_pin_number = $row["fax_pin_number"];
		$fax_caller_id_name = $row["fax_caller_id_name"];
		$fax_caller_id_number = $row["fax_caller_id_number"];
		$fax_forward_number = $row["fax_forward_number"];
		$fax_description = $row["fax_description"];
		$fax_email_inbound_subject_tag = $row['fax_email_inbound_subject_tag'];
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
	echo "mailto_adress: $mailto_address\n";
	echo "fax_email: $fax_email\n";
	echo "fax_extension: $fax_extension\n";
	echo "fax_name: $fax_file_only\n";
	echo "dir_fax: $dir_fax\n";
	echo "full_path: $fax_file\n";

	$pdf_file = tiff2pdf($fax_file);
	echo "file: $pdf_file \n";
	if (!$pdf_file){
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

		echo "fax_forward_number: $fax_forward_number\n";
		if (strlen($fax_forward_number) > 0) {
			fax_split_dtmf($fax_forward_number, $fax_dtmf);

			$fax_send_mode = $_SESSION['fax']['send_mode']['text'];
			if (strlen($fax_send_mode) == 0){
				$fax_send_mode = 'direct';
			}

			$route_array = outbound_route_to_bridge($_SESSION['domain_uuid'], $fax_forward_number);
			if (count($route_array) == 0) {
				//send the internal call to the registered extension
					$fax_uri = "user/".$fax_forward_number."@".$domain_name;
					$fax_variables = "";
			}
			else {
				//send the external call
					$fax_uri = $route_array[0];
					$fax_variables = "";
					foreach($_SESSION['fax']['variable'] as $variable) {
						$fax_variables .= $variable.",";
					}
			}

			$dial_string = "absolute_codec_string='PCMU,PCMA',";
			$dial_string .= "accountcode='"                  . $fax_accountcode         . "',";
			$dial_string .= "sip_h_X-accountcode='"          . $fax_accountcode         . "',";
			$dial_string .= "domain_uuid="                   . $_SESSION["domain_uuid"] . ",";
			$dial_string .= "domain_name="                   . $_SESSION["domain_name"] . ",";
			$dial_string .= "origination_caller_id_name='"   . $fax_caller_id_name      . "',";
			$dial_string .= "origination_caller_id_number='" . $fax_caller_id_number    . "',";
			$dial_string .= "fax_ident='"                    . $fax_caller_id_number    . "',";
			$dial_string .= "fax_header='"                   . $fax_caller_id_name      . "',";
			$dial_string .= "fax_file='"                     . $fax_file                . "',";

			if ($fax_send_mode != 'queue') {
				$dial_string .= $fax_variables;
				$dial_string .= "mailto_address='"     . $mailto_address   . "',";
				$dial_string .= "mailfrom_address='"   . $mailfrom_address . "',";
				$dial_string .= "fax_uri=" . $fax_uri  . ",";
				$dial_string .= "fax_retry_attempts=1" . ",";
				$dial_string .= "fax_retry_limit=20"   . ",";
				$dial_string .= "fax_retry_sleep=180"  . ",";
				$dial_string .= "fax_verbose=true"     . ",";
				$dial_string .= "fax_use_ecm=off"      . ",";
				$dial_string .= "api_hangup_hook='lua fax_retry.lua'";
				$dial_string = "{" . $dial_string . "}" . $fax_uri." &txfax('".$fax_file."')";

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
			else{
				$wav_file = '';
				$response = fax_enqueue($fax_uuid, $fax_file, $wav_file, $mailto_address, $fax_uri, $fax_dtmf, $dial_string);
			}
		}
	}

//send the email
	if (strlen($fax_email) > 0 && file_exists($fax_file)) {
		//prepare the message
			$tmp_subject = (($fax_email_inbound_subject_tag != '') ? "[".$fax_email_inbound_subject_tag."]" : "Fax Received").": ".$fax_file_name;

			$tmp_text_html = "<br><strong>Fax Received</strong><br><br>";
			$tmp_text_html .= "Name: ".$fax_file_name."<br>";
			$tmp_text_html .= "Extension: ".$fax_extension."<br>";
			$tmp_text_html .= "Messages: ".$fax_messages."<br>";
			$tmp_text_html .= $fax_file_warning."<br>";
			if ($fax_relay == 'yes') {
				$tmp_subject = "Fax Received for Relay: ".$fax_file_name;
				$tmp_text_html .= "<br>This message arrived successfully from your fax machine, and has been queued for outbound fax delivery. You will be notified later as to the success or failure of this fax.<br>";
			}
			$tmp_text_plain = strip_tags(str_replace("<br>", "\n", $tmp_text_html));

		//prepare the mail object
			$mail = new PHPMailer();
			if (isset($smtp['method'])) {
				switch($smtp['method']) {
					case 'sendmail': $mail->IsSendmail(); break;
					case 'qmail': $mail->IsQmail(); break;
					case 'mail': $mail->IsMail(); break;
					default: $mail->IsSMTP(); break;
				}
			}
			else {
				$mail->IsSMTP(); // set mailer to use SMTP
			}

		//optionally skip certificate validation
			if (isset($_SESSION['email']['smtp_validate_certificate'])) {
				if ($_SESSION['email']['smtp_validate_certificate']['boolean'] == "false") {

					// this works around TLS certificate problems e.g. self-signed certificates
					$mail->SMTPOptions = array(
						'ssl' => array(
						'verify_peer' => false,
						'verify_peer_name' => false,
						'allow_self_signed' => true
						)
					);
				}
			}

			if ($smtp['auth'] == "true") {
				$mail->SMTPAuth = $smtp['auth']; // turn on/off SMTP authentication
			}
			$mail->Host = $smtp['host'];
			if (strlen($smtp['port']) > 0) {
				$mail->Port = $smtp['port'];
			}
			if (strlen($smtp['secure']) > 0 && $smtp['secure'] != 'none') {
				$mail->SMTPSecure = $smtp['secure'];
			}
			if ($smtp['username'] != '') {
				$mail->Username = $smtp['username'];
				$mail->Password = $smtp['password'];
			}
			$mail->SMTPDebug  = 2;
			$mail->From = $smtp['from'];
			$mail->FromName = $smtp['from_name'];
			$mail->Subject = $tmp_subject;
			$mail->AltBody = $tmp_text_plain;
			$mail->MsgHTML($tmp_text_html);

			$tmp_to = $fax_email;
			$tmp_to = str_replace(";", ",", $tmp_to);
			$tmp_to_array = explode(",", $tmp_to);
			foreach($tmp_to_array as $tmp_to_row) {
				if (strlen($tmp_to_row) > 0) {
					echo "tmp_to_row: $tmp_to_row\n";
					$mail->AddAddress(trim($tmp_to_row));
				}
			}

		//output to the log
			echo "smtp_host: ".$smtp['host']."\n";
			echo "smtp_from: ".$smtp['from']."\n";
			echo "smtp_from_name: ".$smtp['from_name']."\n";
			echo "tmp_subject: $tmp_subject\n";

		//add the attachments
			if (strlen($fax_file_name) > 0) {
				if ($pdf_file && file_exists($pdf_file)) {
					$mail->AddAttachment($pdf_file); // pdf attachment
				}
				else {
					$mail->AddAttachment($fax_file); // tif attachment
				}
				//$filename='fax.tif'; $encoding = "base64"; $type = "image/tif";
				//$mail->AddStringAttachment(base64_decode($strfax),$filename,$encoding,$type);
			}

		//send the email
			if (!$mail->Send()) {
				echo "Mailer Error: " . $mail->ErrorInfo;
				$email_status=$mail;
			}
			else {
				echo "Message sent!";
				$email_status="ok";
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
	if (strlen($fax_email) > 0 && file_exists($fax_file)) {
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
			else {
				//create an instruction log to email messages once the connection to the mail server has been restored
					$fp = fopen($fax_to_email_queue_dir."/failed_fax_emails.log", "a");
					fwrite($fp, PHP_BINDIR."/php ".$_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/secure/fax_to_email.php email='".$fax_email."' extension=".$fax_extension." name='".$fax_file."' messages='".$fax_messages."' domain=".$domain_name." caller_id_name='".$caller_id_name."' caller_id_number=".$caller_id_number." retry=true\n");
					fclose($fp);
				//create a script to do the delayed mailing
					$fp = fopen($_SESSION['server']['temp']['dir']."/failed_fax_emails.sh", "w");
					fwrite($fp, "rm ".$_SESSION['server']['temp']['dir']."/fax_email_retry.sh\n");
					fwrite($fp, "mv ".$fax_to_email_queue_dir."/failed_fax_emails.log ".$_SESSION['server']['temp']['dir']."/fax_email_retry.sh\n");
					fwrite($fp, "chmod 777 ".$_SESSION['server']['temp']['dir']."/fax_email_retry.sh\n");
					fwrite($fp, $_SESSION['server']['temp']['dir']."/fax_email_retry.sh\n");
					fclose($fp);
					$tmp_response = exec("chmod 777 ".$_SESSION['server']['temp']['dir']."/failed_fax_emails.sh");
				//note we use batch in order to execute when system load is low.  Alternatively this could be replaced with AT.
					$tmp_response = exec("at -f ".$_SESSION['server']['temp']['dir']."/failed_fax_emails.sh now + 3 minutes");
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