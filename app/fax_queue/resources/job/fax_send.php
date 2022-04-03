<?php

//check the permission
	if (defined('STDIN')) {
		$document_root = str_replace("\\", "/", $_SERVER["PHP_SELF"]);
		preg_match("/^(.*)\/app\/.*$/", $document_root, $matches);
		$document_root = $matches[1];
		set_include_path($document_root);
		$_SERVER["DOCUMENT_ROOT"] = $document_root;
		require_once "resources/require.php";
		require_once "resources/functions.php";
	}
	else {
		exit;
		include "root.php";
		require_once "resources/require.php";
		require_once "resources/pdo.php";
	}

//increase limits
	set_time_limit(0);
	//ini_set('max_execution_time',1800); //30 minutes
	ini_set('memory_limit', '512M');

//save the arguments to variables
	$script_name = $argv[0];
	if (!empty($argv[1])) {
		parse_str($argv[1], $_GET);
	}
	//print_r($_GET);

//set the variables
	if (isset($_GET['hostname'])) {
		$hostname = urldecode($_GET['hostname']);
	}
	if (isset($_GET['debug'])) {
		$debug = $_GET['debug'];
	}

//extract dtmf from the fax number
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

//set the GET array
	if (!empty($argv[1])) {
		parse_str($argv[1], $_GET);
	}

//get the primary key
	$fax_queue_uuid = $_GET['fax_queue_uuid'];
	$hostname = $_GET['hostname'];

//get the fax queue details to send
	$sql = "select q.*, d.domain_name ";
	$sql .= "from v_fax_queue as q, v_domains as d ";
	$sql .= "where fax_queue_uuid = :fax_queue_uuid ";
	$sql .= "and q.domain_uuid = d.domain_uuid ";
	$parameters['fax_queue_uuid'] = $fax_queue_uuid;
	$database = new database;
	$row = $database->select($sql, $parameters, 'row');
	if (is_array($row)) {
		$fax_queue_uuid = $row['fax_queue_uuid'];
		$domain_uuid = $row['domain_uuid'];
		$domain_name = $row['domain_name'];
		$fax_uuid = $row['fax_uuid'];
		$hostname = $row["hostname"];
		$fax_date = $row["fax_date"];
		$fax_caller_id_name = $row["fax_caller_id_name"];
		$fax_caller_id_number = $row["fax_caller_id_number"];
		$fax_prefix = $row["fax_prefix"];
		$fax_number = $row["fax_number"];
		$fax_email_address = $row["fax_email_address"];
		$fax_file = $row["fax_file"];
		$fax_status = $row["fax_status"];
		$fax_retry_count = $row["fax_retry_count"];
		$fax_accountcode = $row["fax_accountcode"];
		$fax_command = $row["fax_command"];
	}
	unset($parameters);

//get the default settings
	$sql = "select default_setting_uuid, default_setting_name, default_setting_category, default_setting_subcategory, default_setting_value ";
	$sql .= "from v_default_settings ";
	$sql .= "where default_setting_category in ('domain', 'fax', 'fax_queue') ";
	$sql .= "and default_setting_enabled = 'true' ";
	$parameters = null;
	$database = new database;
	$result = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);
	if (is_array($result) && sizeof($result) != 0) {
		foreach ($result as $row) {
			$name = $row['default_setting_name'];
			$category = $row['default_setting_category'];
			$subcategory = $row['default_setting_subcategory'];
			if ($subcategory != '') {
				if ($name == "array") {
					$_SESSION[$category][] = $row['default_setting_value'];
				}
				else {
					$_SESSION[$category][$name] = $row['default_setting_value'];
				}
			}
			else {
				if ($name == "array") {
					$_SESSION[$category][$subcategory][] = $row['default_setting_value'];
				}
				else {
					$_SESSION[$category][$subcategory]['uuid'] = $row['default_setting_uuid'];
					$_SESSION[$category][$subcategory][$name] = $row['default_setting_value'];
				}
			}
		}
	}
	unset($result, $row);

//get the domain settings
	$sql = "select domain_setting_uuid, domain_setting_name, domain_setting_category, domain_setting_subcategory, domain_setting_value ";
	$sql .= "from v_domain_settings ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and domain_setting_category in ('domain', 'fax', fax_queue') ";
	$sql .= "and domain_setting_enabled = 'true' ";
	$parameters['domain_uuid'] = $domain_uuid;
	$database = new database;
	$result = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);
	if (is_array($result) && sizeof($result) != 0) {
		
		foreach ($result as $row) {
			$name = $row['domain_setting_name'];
			$category = $row['domain_setting_category'];
			$subcategory = $row['domain_setting_subcategory'];
			if ($subcategory != '') {
				if ($name == "array") {
					$_SESSION[$category][] = $row['domain_setting_value'];
				}
				else {
					$_SESSION[$category][$name] = $row['domain_setting_value'];
				}
			}
			else {
				if ($name == "array") {
					$_SESSION[$category][$subcategory][] = $row['domain_setting_value'];
				}
				else {
					$_SESSION[$category][$subcategory]['uuid'] = $row['domain_setting_uuid'];
					$_SESSION[$category][$subcategory][$name] = $row['domain_setting_value'];
				}
			}
		}
	}
	unset($result, $row);

//prepare the smtp from and from name variables
	$email_from = $_SESSION['email']['smtp_from']['text'];
	$email_from_name = $_SESSION['email']['smtp_from_name']['text'];
	if (isset($_SESSION['fax']['smtp_from']['text']) && strlen($_SESSION['fax']['smtp_from']['text']) > 0) {
		$email_from = $_SESSION['fax']['smtp_from']['text'];
	}
	if (isset($_SESSION['fax']['smtp_from_name']['text']) && strlen($_SESSION['fax']['smtp_from_name']['text']) > 0) {
		$email_from_name = $_SESSION['fax']['smtp_from_name']['text'];
	}

//prepare the variables to send the fax
	$mail_from_address = (isset($_SESSION['fax']['smtp_from']['text'])) ? $_SESSION['fax']['smtp_from']['text'] : $_SESSION['email']['smtp_from']['text'];
	$retry_limit = $_SESSION['fax_queue']['retry_limit']['numeric'];
	//$retry_interval = $_SESSION['fax_queue']['retry_interval']['numeric'];

//prepare the fax retry count
	if (strlen($fax_retry_count) == 0) {
		$fax_retry_count = 0;
	}
	elseif ($fax_status != 'busy') {
		$fax_retry_count = $fax_retry_count + 1;
	}

//determine if the retry count exceed the limit
	if ($fax_status != 'sent' && $fax_status != 'busy' && $fax_retry_count > $retry_limit) {
		$fax_status = 'failed';
	}

//attempt sending the fax
	if ($fax_status == 'waiting' || $fax_status == 'trying' || $fax_status == 'busy') {

		//fax options
			if ($fax_retry_count == 0) {
				$fax_options = "fax_use_ecm=false,fax_enable_t38=true,fax_enable_t38_request=true,fax_disable_v17=default";
			}
			elseif ($fax_retry_count == 1) {
				$fax_options = "fax_use_ecm=true,fax_enable_t38=true,fax_enable_t38_request=true,fax_disable_v17=false";
			}
			elseif ($fax_retry_count == 2) {
				$fax_options = "fax_use_ecm=true,fax_enable_t38=false,fax_enable_t38_request=false,fax_disable_v17=false";
			}
			elseif ($fax_retry_count == 3) {
				$fax_options = "fax_use_ecm=true,fax_enable_t38=true,fax_enable_t38_request=true,fax_disable_v17=true";
			}
			elseif ($fax_retry_count == 4) {
				$fax_options = "fax_use_ecm=false,fax_enable_t38=false,fax_enable_t38_request=false,fax_disable_v17=false";
			}

		//define the fax file
			$common_variables  = "for_fax=1,";
			$common_variables .= "accountcode='"                  . $fax_accountcode         . "',";
			$common_variables .= "sip_h_X-accountcode='"          . $fax_accountcode         . "',";
			$common_variables .= "domain_uuid="                   . $domain_uuid             . "',";
			$common_variables .= "domain_name="                   . $domain_name             . "',";
			$common_variables .= "origination_caller_id_name='"   . $fax_caller_id_name      . "',";
			$common_variables .= "origination_caller_id_number='" . $fax_caller_id_number    . "',";
			$common_variables .= "fax_ident='"                    . $fax_caller_id_number    . "',";
			$common_variables .= "fax_header='"                   . $fax_caller_id_name      . "',";
			$common_variables .= "fax_file='"                     . $fax_file                . "',";

		//extract fax_dtmf from the fax number
			fax_split_dtmf($fax_number, $fax_dtmf);

		//prepare the fax command
			if (strlen($fax_toll_allow) > 0) {
				$channel_variables["toll_allow"] = $fax_toll_allow;
			}
			$route_array = outbound_route_to_bridge($domain_uuid, $fax_prefix . $fax_number, $channel_variables);
			if (count($route_array) == 0) {
				//send the internal call to the registered extension
				$fax_uri = "user/".$fax_number."@".$domain_name;
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

		//set the fax file name without the extension
			$fax_instance_id = pathinfo($fax_file, PATHINFO_FILENAME);

		//build a list of fax variables
			$dial_string = $common_variables;
			$dial_string .= $fax_variables;
			$dial_string .= $fax_options.",";
			$dial_string .= "fax_uuid="            . $fax_uuid. ",";
			$dial_string .= "fax_queue_uuid="      . $fax_queue_uuid. ",";
			$dial_string .= "mailto_address='"     . $fax_email_address   . "',";
			$dial_string .= "mailfrom_address='"   . $mail_from_address . "',";
			$dial_string .= "fax_uri="             . $fax_uri  . ",";
			$dial_string .= "fax_retry_attempts=1" . ",";
			$dial_string .= "fax_retry_limit=1"    . ",";
			//$dial_string .= "fax_retry_sleep=180"  . ",";
			$dial_string .= "fax_verbose=true"     . ",";
			//$dial_string .= "fax_use_ecm=off"      . ",";
			$dial_string .= "api_hangup_hook='lua app/fax/resources/scripts/hangup_tx.lua'";
			$fax_command  = "originate {" . $dial_string . "}" . $fax_uri." &txfax('".$fax_file."')";
			//echo $fax_command."\n";

		//connect to event socket and send the command
			if ($fax_status != 'failed' && file_exists($fax_file)) {
				$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
				if ($fp) {
					$response = event_socket_request($fp, "api " . $fax_command);
					//$response = event_socket_request($fp, $fax_command);
					$response = str_replace("\n", "", $response);
					$uuid = str_replace("+OK ", "", $response);
					echo "uuid ".$uuid."\n";
				}
				fclose($fp);
			}
			else {
				echo "fax file missing: ".$fax_file."\n";
			}

		//set the fax status
			$fax_status = 'trying';
	}

//update the database to say status to trying and set the command
	$array['fax_queue'][0]['fax_queue_uuid'] = $fax_queue_uuid;
	$array['fax_queue'][0]['domain_uuid'] = $domain_uuid;
	$array['fax_queue'][0]['fax_status'] = $fax_status;
	$array['fax_queue'][0]['fax_retry_count'] = $fax_retry_count;
	$array['fax_queue'][0]['fax_retry_date'] = 'now()';
	$array['fax_queue'][0]['fax_command'] = $fax_command;

//add temporary permissions
	$p = new permissions;
	$p->add('fax_queue_edit', 'temp');

//save the data
	$database = new database;
	$database->app_name = 'fax queue';
	$database->app_uuid = '3656287f-4b22-4cf1-91f6-00386bf488f4';
	$database->save($array, false);

//remove temporary permissions
	$p->delete('fax_queue_edit', 'temp');

//send the email
	if (in_array($fax_status, array('sent', 'failed')) && strlen($fax_email_address) > 0 && file_exists($fax_file)) {

		//get the language code
			$language_code = $_SESSION['domain']['language']['code'];

		//get the template subcategory
			if (isset($fax_relay) && $fax_relay == 'true') {
				$template_subcategory = 'relay';
			}
			else {
				$template_subcategory = 'inbound';
			}

		//get the email template from the database
			if (isset($fax_email_address) && strlen($fax_email_address) > 0) {
				$sql = "select template_subcategory, template_subject, template_body from v_email_templates ";
				$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
				$sql .= "and template_language = :template_language ";
				$sql .= "and template_category = :template_category ";
				$sql .= "and template_type = :template_type ";
				$sql .= "and template_enabled = 'true' ";
				$parameters['domain_uuid'] = $domain_uuid;
				$parameters['template_language'] = $language_code;
				$parameters['template_category'] = 'fax';
				$parameters['template_type'] = 'html';
				$database = new database;
				$fax_templates = $database->select($sql, $parameters, 'all');
				unset($sql, $parameters);
			}

		//determine the template category: fail_busy, fail_default, fail_invalid, inbound, relay, success_default
			switch ($fax_status) {
				case 'sent':
					$template_subcategory = 'success_default';
					break;
				case 'failed':
					$template_subcategory = 'fail_default';
					break;
				case 'busy':
					$template_subcategory = 'fail_busy';
					break;
			}

		//determine the email template to use
			if (is_array($fax_templates)) {
				foreach($fax_templates as $row) {
					if ($row['template_subcategory'] == $template_subcategory) {
						$email_subject = $row['template_subject'];
						$email_body = $row['template_body'];
					}
				}
			}

		//get the fax file name (only) if a full path
			$path_info = pathinfo($fax_file);
			$fax_file_dirname = $path_info['dirname'];
			$fax_file_basename = $path_info['basename'];
			$fax_file_filename = $path_info['filename'];
			$fax_file_extension = $path_info['extension'];

		//set the fax file pdf and tif files
			$fax_file_tif = path_join($fax_file_dirname, $fax_file_filename . $fax_file_extension);
			$fax_file_pdf = path_join($fax_file_dirname, $fax_file_filename . 'pdf');
			if (file_exists(path_join($fax_file_dirname, $fax_file_filename . 'pdf'))) {
				$fax_file_name = $fax_file_filename . '.pdf';
			}
			else {
				$fax_file_name = $fax_file_filename . '.' . $fax_file_extension;
			}

		//replace variables in email subject
			$email_subject = str_replace('${domain_name}', $domain_uuid, $email_subject);
			$email_subject = str_replace('${number_dialed}', $fax_number, $email_subject);
			$email_subject = str_replace('${fax_file_name}', $fax_file_name, $email_subject);
			$email_subject = str_replace('${fax_extension}', $fax_extension, $email_subject);
			$email_subject = str_replace('${fax_messages}', $fax_messages, $email_subject);
			$email_subject = str_replace('${fax_file_warning}', $fax_file_warning, $email_subject);
			$email_subject = str_replace('${fax_subject_tag}', $fax_email_inbound_subject_tag, $email_subject);

		//replace variables in email body
			$email_body = str_replace('${domain_name}', $domain_uuid, $email_body);
			$email_body = str_replace('${number_dialed}', $fax_number, $email_body);
			$email_body = str_replace('${fax_file_name}', $fax_file_name, $email_body);
			$email_body = str_replace('${fax_extension}', $fax_extension, $email_body);
			$email_body = str_replace('${fax_messages}', $fax_messages, $email_body);
			$email_body = str_replace('${fax_file_warning}', $fax_file_warning, $email_body);
			$email_body = str_replace('${fax_subject_tag}', $fax_email_inbound_subject_tag, $email_body);

		//send the email
			if (isset($fax_email_address) && strlen($fax_email_address) > 0) {
				//add the attachment
				if (strlen($fax_file_name) > 0) {
					$email_attachments[0]['type'] = 'file';
					$email_attachments[0]['name'] = $fax_file_name;
					$email_attachments[0]['value'] = path_join($fax_file_dirname, '.', $fax_file_name);
				}

				$fax_email_address = str_replace(",", ";", $fax_email_address);
				$email_addresses = explode(";", $fax_email_address);
				foreach($email_addresses as $email_adress) {
						//send the email
						$email_response = !send_email($email_adress, $email_subject, $email_body, $email_error, $email_from_address, $email_from_name, 3, null, $email_attachments) ? false : true;

						//debug info
						if (isset($_GET['debug'])) {
							echo "template_subcategory: ".$template_subcategory."\n";
							echo "email_adress: ".$email_adress."\n";
							echo "email_from: ".$email_from_name."\n";
							echo "email_from_name: ".$email_from_address."\n";
							echo "email_subject: ".$email_subject."\n";
							//echo "email_body: ".$email_body."\n";
							echo "\n";
						}
				}

			}

		//send the email
			//if ($email_response) {
			//	echo "Mailer Error";
			//	$email_status=$mail;
			//}
			//else {
			//	echo "Message sent!";
			//	$email_status="ok";
			//}
	}

//wait for a few seconds
	//sleep(1);

//move the generated tif (and pdf) files to the sent directory
	//if (file_exists($dir_fax_temp.'/'.$fax_instance_id.".tif")) {
	//	copy($dir_fax_temp.'/'.$fax_instance_id.".tif", $dir_fax_sent.'/'.$fax_instance_id.".tif");
	//}
	//if (file_exists($dir_fax_temp.'/'.$fax_instance_id.".pdf")) {
	//	copy($dir_fax_temp.'/'.$fax_instance_id.".pdf ", $dir_fax_sent.'/'.$fax_instance_id.".pdf");
	//}

//send context to the temp log
	//echo "Subject: ".$email_subject."\n";
	//echo "From: ".$email_from."\n";
	//echo "Reply-to: ".$email_from."\n";
	//echo "To: ".$email_to."\n";
	//echo "Date: ".$email_date."\n";
	//echo "Transcript: ".$array['message']."\n";
	//echo "Body: ".$email_body."\n";

//save output to
	//$fp = fopen(sys_get_temp_dir()."/mailer-app.log", "a");

//prepare the output buffers
	//ob_end_clean();
	//ob_start();

//message divider for log file
	//echo "\n\n====================================================\n\n";

//get and save the output from the buffer
	//$content = ob_get_contents(); //get the output from the buffer
	//$content = str_replace("<br />", "", $content);

	//ob_end_clean(); //clean the buffer

	//fwrite($fp, $content);
	//fclose($fp);


?>
