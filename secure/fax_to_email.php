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
	Portions created by the Initial Developer are Copyright (C) 2008-2015
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/

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

if (stristr(PHP_OS, 'WIN')) { $IS_WINDOWS = true; } else { $IS_WINDOWS = false; }

if(!function_exists('exec_in_dir')) {
	function exec_in_dir($dir, $cmd, &$ok){
		$args = func_get_args();
		$cwd = getcwd();
		chdir($dir);
		$output = array();
		$ret = 0;
		$result = exec($cmd, $output, $ret);
		if($cwd)
			chdir($cwd);
		$ok = ($ret == 0);
		return join($output, "\n");
	}
}

if(!function_exists('correct_path')) {
	function correct_path($p) {
		global $IS_WINDOWS;
		if ($IS_WINDOWS) {
			return str_replace('/', '\\', $p);
		}
		return $p;
	}
}

if(!function_exists('path_join')) {
	function path_join() {
		$args = func_get_args();
		$paths = array();
		foreach ($args as $arg) {
			$paths = array_merge($paths, (array)$arg);
		}

		$prefix = null;
		foreach($paths as &$path) {
			if($prefix === null && strlen($path) > 0) {
				if(substr($path, 0, 1) == '/') $prefix = '/';
				else $prefix = '';
			}
			$path = trim( $path, '/' );
		}

		if($prefix === null){
			return '';
		}

		$paths = array_filter($paths);

		return $prefix . join('/', $paths);
	}
}

if(!function_exists('tiff2pdf')) {
	function tiff2pdf($tiff_file_name){
		//convert the tif to a pdf
		//Ubuntu: apt-get install libtiff-tools

		global $IS_WINDOWS;

		if(!file_exists($tiff_file_name)){
			echo "tiff file does not exists";
			return false; // "tiff file does not exists";
		}

		$GS = $IS_WINDOWS ? 'gswin32c' : 'gs';
		$tiff_file = pathinfo($tiff_file_name);
		$dir_fax = $tiff_file['dirname'];
		$fax_file_name = $tiff_file['filename'];
		$pdf_file_name = path_join( $dir_fax, $fax_file_name . '.pdf' );

		if(file_exists($pdf_file_name))
			return $pdf_file_name;

		$dir_fax_temp = $_SESSION['server']['temp']['dir'];
		if(!$dir_fax_temp){
			$dir_fax_temp = path_join(dirname($dir_fax), 'temp');
		}

		if(!file_exists($dir_fax_temp)){
			echo"can not create temporary directory";
			return false; //
		}

		$cmd  = "tiffinfo " . correct_path($tiff_file_name) . ' | grep "Resolution:"';
		$ok   = false;
		$resp = exec_in_dir($dir_fax, $cmd, $ok);
		if(!$ok){
			echo"can not find fax resoulution";
			return false; // "can not find fax resoulution"
		}

		$ppi_w = 0;
		$ppi_h = 0;
		$tmp = array();
		if(preg_match('/Resolution.*?(\d+).*?(\d+)/', $resp, $tmp)){
			$ppi_w = $tmp[1];
			$ppi_h = $tmp[2];
		}

		$cmd = "tiffinfo " . $tiff_file_name . ' | grep "Image Width:"';
		$resp = exec_in_dir($dir_fax, $cmd, $ok);
		if(!$ok){
			echo"can not find fax size";
			return false; // "can not find fax size"
		}

		$pix_w = 0;
		$pix_h = 0;
		$tmp = array();
		if(preg_match('/Width.*?(\d+).*?Length.*?(\d+)/', $resp, $tmp)){
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
			'-i -u i',
			'-p', $page_size,
			'-w', $page_width,
			'-l', $page_height,
			'-f',
			'-o', correct_path(path_join($dir_fax_temp, $fax_file_name . '.pdf')),
			correct_path($tiff_file_name),
		), ' ');

		$resp = exec_in_dir($dir_fax, $cmd, $ok);

		if(!file_exists(path_join($dir_fax_temp, $fax_file_name . '.pdf'))){
			echo "can not create temporary pdf: $resp";
			return false;
		}

		$cmd = join(array($GS,
			'-q -sDEVICE=tiffg3',
			'-r' . $ppi_w . 'x' . $ppi_h,
			'-g' . $pix_w . 'x' . $pix_h,
			'-dNOPAUSE',
			'-sOutputFile=' . $fax_file_name . '_temp.tif',
			'--',
			$fax_file_name . '.pdf',
			'-c quit',
		), ' ');

		$resp = exec_in_dir($dir_fax_temp, $cmd, $ok);

		unlink(path_join($dir_fax_temp, $fax_file_name . '.pdf'));

		if(!file_exists(path_join($dir_fax_temp, $fax_file_name . '_temp.tif'))){
			echo "can not temporary tiff: $resp";
			return false;
		}

		$cmd = join(array('tiff2pdf',
			'-i -u i',
			'-p', $page_size,
			'-w', $page_width,
			'-l', $page_height,
			'-f',
			'-o', correct_path($pdf_file_name),
			correct_path(path_join($dir_fax_temp, $fax_file_name . '_temp.tif')),
		), ' ');

		$resp = exec_in_dir($dir_fax, $cmd, $ok);

		unlink(path_join($dir_fax_temp, $fax_file_name . '_temp.tif'));

		if(!file_exists($pdf_file_name)){
			echo "can not create pdf: $resp";
			return false;
		}

		return $pdf_file_name;
	}
}

if(!function_exists('fax_enqueue')) {
	function fax_enqueue($fax_uuid, $fax_file, $wav_file, $reply_address, $fax_uri, $fax_dtmf, $dial_string){
		global $db, $db_type;

		$fax_task_uuid = uuid();
		$dial_string .= "fax_task_uuid='" . $fax_task_uuid . "',";
		$description = ''; //! @todo add description
		if ($db_type == "pgsql") {
			$date_utc_now_sql  = "NOW() at time zone 'utc'";
		}
		if ($db_type == "mysql") {
			$date_utc_now_sql  = "UTC_TIMESTAMP()";
		}
		if ($db_type == "sqlite") {
			$date_utc_now_sql  = "datetime('now')";
		}
		$sql = <<<HERE
INSERT INTO v_fax_tasks( fax_task_uuid, fax_uuid,
	task_next_time, task_lock_time,
	task_fax_file, task_wav_file, task_uri, task_dial_string, task_dtmf,
	task_interrupted, task_status, task_no_answer_counter, task_no_answer_retry_counter, task_retry_counter,
	task_reply_address, task_description)
VALUES (?, ?,
	$date_utc_now_sql, NULL,
	?, ?, ?, ?, ?,
	'false', 0, 0, 0, 0,
	?, ?);
HERE;
		$stmt = $db->prepare($sql);
		$i = 0;
		$stmt->bindValue(++$i, $fax_task_uuid);
		$stmt->bindValue(++$i, $fax_uuid);
		$stmt->bindValue(++$i, $fax_file);
		$stmt->bindValue(++$i, $wav_file);
		$stmt->bindValue(++$i, $fax_uri);
		$stmt->bindValue(++$i, $dial_string);
		$stmt->bindValue(++$i, $fax_dtmf);
		$stmt->bindValue(++$i, $reply_address);
		$stmt->bindValue(++$i, $description);
		if ($stmt->execute()) {
			$response = 'Enqueued';
		}
		else{
			//! @todo log error
			$response = 'Fail enqueue';
			var_dump($db->errorInfo());
		}
		unset($stmt);
		return $response;
	}
}

if(!function_exists('fax_split_dtmf')) {
	function fax_split_dtmf(&$fax_number, &$fax_dtmf){
		$tmp = array();
		$fax_dtmf = '';
		if(preg_match('/^\s*(.*?)\s*\((.*)\)\s*$/', $fax_number, $tmp)){
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
	ob_end_clean();
	ob_start();

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
		$mailfrom_address = $tmp_array[1];
		unset($tmp_array);

		//$tmp_array = explode("=", $_SERVER["argv"][10]);
		//$destination_number = $tmp_array[1];
		//unset($tmp_array);
	}

	$mailto_address = $fax_email;

//get the fax file name (only) if a full path
	$fax_path      = pathinfo($fax_file);
	$fax_file_only = $fax_path['basename'];
	$fax_file_name = $fax_path['filename'];
	$dir_fax       = $fax_path['dirname'];

//get the domain_uuid from the database
	$sql = "select * from v_domains ";
	$sql .= "where domain_name = '".$domain_name."' ";
	$prep_statement = $db->prepare($sql);
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
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
	unset ($prep_statement);

//get the fax details from the database
	$sql = "select * from v_fax ";
	$sql .= "where domain_uuid = '".$_SESSION["domain_uuid"]."' ";
	$sql .= "and fax_extension = '$fax_extension' ";
	$prep_statement = $db->prepare($sql);
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
	foreach ($result as &$row) {
		//set database fields as variables
			//$fax_email = $row["fax_email"];
			$fax_uuid = $row["fax_uuid"];
			$fax_accountcode = $row["fax_accountcode"];
			$fax_pin_number = $row["fax_pin_number"];
			$fax_caller_id_name = $row["fax_caller_id_name"];
			$fax_caller_id_number = $row["fax_caller_id_number"];
			$fax_forward_number = $row["fax_forward_number"];
			$fax_description = $row["fax_description"];
			$fax_email_inbound_subject_tag = $row['fax_email_inbound_subject_tag'];
	}
	unset ($prep_statement);

//set the fax directory
	if (!file_exists($dir_fax) || !file_exists(path_join($dir_fax, $fax_file_only))) {
		$dir_fax = $_SESSION['switch']['storage']['dir'].'/fax/'.$domain_name.'/'.$fax_extension.'/inbox';
		if (!file_exists($dir_fax) || !file_exists(path_join($dir_fax, $fax_file_only))) {
			$dir_fax = $_SESSION['switch']['storage']['dir'].'/fax/'.$fax_extension.'/inbox';
		}
	}

	$fax_file = path_join($dir_fax, $fax_file_only);

//used for debug
	echo "mailto_adress is $mailto_address\n";
	echo "fax_email is $fax_email\n";
	echo "fax_extension is $fax_extension\n";
	echo "fax_name is $fax_file_only\n";
	echo "dir_fax is $dir_fax\n";
	echo "full_path is $fax_file\n";

	$pdf_file = tiff2pdf($fax_file);
	if(!$pdf_file){
		$fax_file_warning = ' Fax image not available on server.';
	}
	else{
		$fax_file_warning = '';
	}

//used for debug
	echo "pdf file is $pdf_file\n";

//forward the fax
	if(file_exists($fax_file)) {
		if (strpos($fax_file_name,'#') !== false) {
			$tmp = explode("#",$fax_file_name);
			$fax_forward_number = $tmp[0];
		}

		echo "fax_forward_number is $fax_forward_number\n";
		if (strlen($fax_forward_number) > 0) {
			fax_split_dtmf($fax_forward_number, $fax_dtmf);

			$fax_send_mode = $_SESSION['fax']['send_mode']['text'];
			if(strlen($fax_send_mode) == 0){
				$fax_send_mode = 'direct';
			}

			$route_array = outbound_route_to_bridge($_SESSION['domain_uuid'], $fax_forward_number);
			if (count($route_array) == 0) {
				//send the internal call to the registered extension
					$fax_uri = "user/".$fax_forward_number."@".$domain_name;
					$t38 = "";
			}
			else {
				//send the external call
					$fax_uri = $route_array[0];
					$t38 = "fax_enable_t38=true,fax_enable_t38_request=true";
			}

			$common_dial_string  = "absolute_codec_string='PCMU,PCMA',";
			$common_dial_string .= "accountcode='"                  . $fax_accountcode         . "',";
			$common_dial_string .= "sip_h_X-accountcode='"          . $fax_accountcode         . "',";
			$common_dial_string .= "domain_uuid="                   . $_SESSION["domain_uuid"] . ",";
			$common_dial_string .= "domain_name="                   . $_SESSION["domain_name"] . ",";
			$common_dial_string .= "origination_caller_id_name='"   . $fax_caller_id_name      . "',";
			$common_dial_string .= "origination_caller_id_number='" . $fax_caller_id_number    . "',";
			$common_dial_string .= "fax_ident='"                    . $fax_caller_id_number    . "',";
			$common_dial_string .= "fax_header='"                   . $fax_caller_id_name      . "',";
			$common_dial_string .= "fax_file='"                     . $fax_file                . "',";

			if ($fax_send_mode != 'queue') {
				$dial_string .= $t38;
				$dial_string .= "mailto_address='"     . $mailto_address   . "',";
				$dial_string .= "mailfrom_address='"   . $mailfrom_address . "',";
				$dial_string .= "fax_uri=" . $fax_uri  . ",";
				$dial_string .= "fax_retry_attempts=1" . ",";
				$dial_string .= "fax_retry_limit=20"   . ",";
				$dial_string .= "fax_retry_sleep=180"  . ",";
				$dial_string .= "fax_verbose=true"     . ",";
				$dial_string .= "fax_use_ecm=off"      . ",";
				$dial_string .= "api_hangup_hook='lua fax_retry.lua'";
				$dial_string  = "{" . $dial_string . "}" . $fax_uri." &txfax('".$fax_file."')";

				//get the event socket information
					$sql = "select * from v_settings ";
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
					$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
					foreach ($result as &$row) {
						$event_socket_ip_address = $row["event_socket_ip_address"];
						$event_socket_port = $row["event_socket_port"];
						$event_socket_password = $row["event_socket_password"];
						break;
					}

				//create the event socket connection
					$fp = event_socket_create($event_socket_ip_address, $event_socket_port, $event_socket_password);

				//send the command with event socket
					if ($fp) {
						//prepare the fax originate command
							$cmd = "api originate " . $dial_string;
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
			$mail->IsSMTP(); // set mailer to use SMTP

			if ($_SESSION['email']['smtp_auth']['var'] == "true") {
				$mail->SMTPAuth = $_SESSION['email']['smtp_auth']['var']; // turn on/off SMTP authentication
			}
			$mail->Host = $_SESSION['email']['smtp_host']['var'];
			if (strlen($_SESSION['email']['smtp_secure']['var']) > 0 && $_SESSION['email']['smtp_secure']['var'] != 'none') {
				$mail->SMTPSecure = $_SESSION['email']['smtp_secure']['var'];
			}
			if ($_SESSION['email']['smtp_username']['var'] != '') {
				$mail->Username = $_SESSION['email']['smtp_username']['var'];
				$mail->Password = $_SESSION['email']['smtp_password']['var'];
			}
			$mail->SMTPDebug  = 2;
			$mail->From = $_SESSION['email']['smtp_from']['var'];
			$mail->FromName = $_SESSION['email']['smtp_from_name']['var'];
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
			echo "smtp_from: ".$_SESSION['email']['smtp_from']['var']."\n";
			echo "smtp_from_name: ".$_SESSION['email']['smtp_from_name']['var']."\n";
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
			if(!$mail->Send()) {
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
				// log the success
					$fp = fopen($fax_to_email_queue_dir."/emailed_faxes.log", "a");
					fwrite($fp, $fax_file_name." received on ".$fax_extension." emailed to ".$fax_email." ".$fax_messages."\n");
					fclose($fp);
			} else {
				// create an instruction log to email messages once the connection to the mail server has been restored
					$fp = fopen($fax_to_email_queue_dir."/failed_fax_emails.log", "a");
					fwrite($fp, PHP_BINDIR."/php ".$_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/secure/fax_to_email.php email='".$fax_email."' extension=".$fax_extension." name='".$fax_file."' messages='".$fax_messages."' domain=".$domain_name." caller_id_name='".$caller_id_name."' caller_id_number=".$caller_id_number." retry=true\n");
					fclose($fp);
				// create a script to do the delayed mailing
					$fp = fopen($_SESSION['server']['temp']['dir']."/failed_fax_emails.sh", "w");
					fwrite($fp, "rm ".$_SESSION['server']['temp']['dir']."/fax_email_retry.sh\n");
					fwrite($fp, "mv ".$fax_to_email_queue_dir."/failed_fax_emails.log ".$_SESSION['server']['temp']['dir']."/fax_email_retry.sh\n");
					fwrite($fp, "chmod 777 ".$_SESSION['server']['temp']['dir']."/fax_email_retry.sh\n");
					fwrite($fp, $_SESSION['server']['temp']['dir']."/fax_email_retry.sh\n");
					fclose($fp);
					$tmp_response = exec("chmod 777 ".$_SESSION['server']['temp']['dir']."/failed_fax_emails.sh");
				// note we use batch in order to execute when system load is low.  Alternatively this could be replaced with AT.
					$tmp_response = exec("at -f ".$_SESSION['server']['temp']['dir']."/failed_fax_emails.sh now + 3 minutes");
			}
		}
	}

//open the file for writing
	$fp = fopen($_SESSION['server']['temp']['dir']."/fax_to_email.log", "w");
//get the output from the buffer
	$content = ob_get_contents();
//clean the buffer
	ob_end_clean();
//write the contents of the buffer
	fwrite($fp, $content);
	fclose($fp);

?>