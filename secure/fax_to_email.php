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
	Portions created by the Initial Developer are Copyright (C) 2008-2012
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

//includes
	if (!defined('STDIN')) { include "root.php"; }
	require_once "resources/require.php";
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
		$fax_name = $_REQUEST["name"];
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
		$fax_name = $tmp_array[1];
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
	echo "mailto_adress is ".$mailto_address."\n";
	echo "fax_email is ".$fax_email."\n";


//used for debug
	echo "fax_email $fax_email\n";
	echo "fax_extension $fax_extension\n";
	echo "fax_name $fax_name\n";
	echo "cd $dir_fax; /usr/bin/tiff2png ".$dir_fax.'/'.$fax_name.".png\n";

//get the fax details from the database
	$sql = "select * from v_domains ";
	$sql .= "where domain_name = '".$domain_name."' ";
	$prep_statement = $db->prepare($sql);
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
	foreach ($result as &$row) {
		$_SESSION["domain_uuid"] = $row["domain_uuid"];
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
			$fax_pin_number = $row["fax_pin_number"];
			$fax_caller_id_name = $row["fax_caller_id_name"];
			$fax_caller_id_number = $row["fax_caller_id_number"];
			$fax_forward_number = $row["fax_forward_number"];
			//$fax_user_list = $row["fax_user_list"];
			$fax_description = $row["fax_description"];
	}
	unset ($prep_statement);

//set the fax directory
	$dir_fax = $_SESSION['switch']['storage']['dir'].'/fax/'.$domain_name.'/'.$fax_extension.'/inbox';
	echo "dir_fax is $dir_fax\n";
	if (!file_exists($dir_fax)) {
		$dir_fax = $_SESSION['switch']['storage']['dir'].'/fax/'.$fax_extension.'/inbox';
	}

//convert the tif to a pdf
	//Ubuntu: apt-get install libtiff-tools
	$fax_file_warning = "";
	if (file_exists($dir_fax.'/'.$fax_name.".tif")) {
		if (!file_exists($dir_fax.'/'.$fax_name.".pdf")) {
			$tmp_tiff2pdf = exec("which tiff2pdf");
			if (strlen($tmp_tiff2pdf) == 0) {$tmp_tiff2pdf = "/usr/bin/tiff2pdf"; }
			if (strlen($tmp_tiff2pdf) > 0) {
				$cmd = "cd ".$dir_fax."; ".$tmp_tiff2pdf." -f -o ".$fax_name.".pdf ".$dir_fax.'/'.$fax_name.".tif";
				echo $cmd."\n";
				exec($cmd);
			}
		}
	}
	else {
		$fax_file_warning = " Fax image not available on server.";
		echo "$fax_file_warning\n";
	}

//forward the fax
	if (strpos($fax_name,'#') !== false) {
		$tmp = explode("#",$fax_name);
		$fax_forward_number = $tmp[0];
	}

	echo "fax_forward_number is $fax_forward_number\n";
	if (strlen($fax_forward_number) > 0) {
		if (file_exists($dir_fax."/".$fax_name.".tif")) {
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
						$route_array = outbound_route_to_bridge($_SESSION['domain_uuid'], $fax_forward_number);
						$fax_file = $dir_fax."/".$fax_name.".tif";
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
						$cmd = "api originate {mailto_address='".$mailto_address."',mailfrom_address='".$mailfrom_address."',origination_caller_id_name='".$fax_caller_id_name."',origination_caller_id_number=".$fax_caller_id_number.",fax_uri=".$fax_uri.",fax_file='".$fax_file."',fax_retry_attempts=1,fax_retry_limit=20,fax_retry_sleep=180,fax_verbose=true,fax_use_ecm=off,".$t38.",api_hangup_hook='lua fax_retry.lua'}".$fax_uri." &txfax('".$fax_file."')";
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

//send the email
	if (strlen($fax_email) > 0 && file_exists($dir_fax."/".$fax_name.".tif")) {
		//prepare the message
			$tmp_subject = "Fax Received: ".$fax_name;
			$tmp_text_plain  = "\nFax Received:\n";
			$tmp_text_plain .= "Name: ".$fax_name."\n";
			$tmp_text_plain .= "Extension: ".$fax_extension."\n";
			$tmp_text_plain .= "Messages: ".$fax_messages."\n";
			$tmp_text_plain .= $fax_file_warning."\n";
			if ($fax_relay == 'yes') {
				$tmp_subject = "Fax Received for Relay: ".$fax_name;
				//$tmp_text_plain .= "This message arrived earlier and has been queued until now due to email server issues.\n";
				$tmp_text_plain .= "\nThis message arrived successfully from your fax machine, and has been queued for outbound fax delivery. You will be notified later as to the success or failure of this fax.\n";
			}
			$tmp_text_html = $tmp_text_plain;

		//prepare the mail object
			$mail = new PHPMailer();
			$mail->IsSMTP(); // set mailer to use SMTP
			if ($_SESSION['email']['smtp_auth']['var'] == "true") {
				$mail->SMTPAuth = $_SESSION['email']['smtp_auth']['var']; // turn on/off SMTP authentication
			}
			$mail->Host = $_SESSION['email']['smtp_host']['var'];
			if ($_SESSION['email']['smtp_secure']['var'] == "none") {
				$_SESSION['email']['smtp_secure']['var'] = '';
			}
			if (strlen($_SESSION['email']['smtp_secure']['var']) > 0) {
				$mail->SMTPSecure = $_SESSION['email']['smtp_secure']['var'];
			}
			if ($_SESSION['email']['smtp_username']['var']) {
				$mail->Username = $_SESSION['email']['smtp_username']['var'];
				$mail->Password = $_SESSION['email']['smtp_password']['var'];
			}
			$mail->SMTPDebug  = 2;
			$mail->From       = $_SESSION['email']['smtp_from']['var'];
			$mail->FromName   = $_SESSION['email']['smtp_from_name']['var'];
			$mail->Subject    = $tmp_subject;
			$mail->AltBody    = $tmp_text_plain;
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
			if (strlen($fax_name) > 0) {
				if (file_exists($dir_fax.'/'.$fax_name.".pdf")) {
					$mail->AddAttachment($dir_fax.'/'.$fax_name.'.pdf'); // pdf attachment
				}
				else {
					$mail->AddAttachment($dir_fax.'/'.$fax_name.'.tif'); // tif attachment
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
	if (strlen($fax_email) > 0 && file_exists($dir_fax."/".$fax_name.".tif")) {
		if (stristr(PHP_OS, 'WIN')) {
			//not compatible with windows
		}
		else {
			$fax_to_email_queue_dir = $_SESSION['switch']['storage']['dir']."/fax";
			if ($email_status == 'ok') {
				// log the success
					$fp = fopen($fax_to_email_queue_dir."/emailed_faxes.log", "a");
					fwrite($fp, $fax_name." received on ".$fax_extension." emailed to ".$fax_email." ".$fax_messages."\n");
					fclose($fp);
			} else {
				// create an instruction log to email messages once the connection to the mail server has been restored
					$fp = fopen($fax_to_email_queue_dir."/failed_fax_emails.log", "a");
					fwrite($fp, PHP_BINDIR."/php ".$_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/secure/fax_to_email.php email=$fax_email extension=$fax_extension name=$fax_name messages='$fax_messages' retry=yes\n");
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
