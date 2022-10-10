<?php

//check the permission
	if (defined('STDIN')) {
		//set the include path
		$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
		set_include_path(parse_ini_file($conf[0])['document.root']);

		//includes files
		require_once "resources/require.php";
		require_once "resources/functions.php";
	}
	else {
		exit;
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

//set the GET array
	if (!empty($argv[1])) {
		parse_str($argv[1], $_GET);
	}

//set the variables
	if (isset($_GET['hostname'])) {
		$hostname = urldecode($_GET['hostname']);
	}
	if (isset($_GET['debug'])) {
		$debug = $_GET['debug'];
	}
	if (isset($_GET['file'])) {
		$file = $_GET['file'];
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

//get the primary key
	if (is_uuid($_GET['fax_queue_uuid'])) {
		$fax_queue_uuid = $_GET['fax_queue_uuid'];
		$hostname = $_GET['hostname'];
		$sleep_seconds = $_GET['sleep'];
	}
	else {
		//invalid uuid
		exit;
	}

//define the process id file
	$pid_file = "/var/run/fusionpbx/fax_send".".".$fax_queue_uuid.".pid";
	//echo "pid_file: ".$pid_file."\n";

//function to check if the process exists
	function process_exists($file = false) {

		//set the default exists to false
		$exists = false;

		//check to see if the process is running
		if (file_exists($file)) {
			$pid = file_get_contents($file);
			if (posix_getsid($pid) === false) { 
				//process is not running
				$exists = false;
			}
			else {
				//process is running
				$exists = true;
			}
		}

		//return the result
		return $exists;
	}

//check to see if the process exists
	$pid_exists = process_exists($pid_file);

//prevent the process running more than once
	if ($pid_exists) {
		//echo "Cannot lock pid file {$pid_file}\n";
		exit;
	}

//make sure the /var/run/fusionpbx directory exists
	if (!file_exists('/var/run/fusionpbx')) {
		$result = mkdir('/var/run/fusionpbx', 0777, true);
		if (!$result) {
			die('Failed to create /var/run/fusionpbx');
		}
	}

//create the process id file if the process doesn't exist
	if (!$pid_exists) {
		//remove the old pid file
		if (file_exists($file)) {
			unlink($pid_file);
		}

		//show the details to the user
		//echo "The process id is ".getmypid()."\n";
		//echo "pid_file: ".$pid_file."\n";

		//save the pid file
		file_put_contents($pid_file, getmypid());
	}

//sleep used for debugging
	if (isset($sleep_seconds)) {
		sleep($sleep_seconds);
	}

//prepare to save the output
	if (isset($file)) {
		//create the debug log
		$fp = fopen(sys_get_temp_dir()."/fax_queue.log", "a");
		
		//prepare the output buffers
		ob_end_clean();
		ob_start();

		//message divider for log file
		echo "\n\n====================================================\n\n";
	}

//get the fax queue details to send
	$sql = "select q.*, d.domain_name ";
	$sql .= "from v_fax_queue as q, v_domains as d ";
	$sql .= "where fax_queue_uuid = :fax_queue_uuid ";
	$sql .= "and q.domain_uuid = d.domain_uuid ";
	$parameters['fax_queue_uuid'] = $fax_queue_uuid;
	$database = new database;
	$row = $database->select($sql, $parameters, 'row');
//view_array($row);
	if (is_array($row)) {
		$fax_queue_uuid = $row['fax_queue_uuid'];
		$domain_uuid = $row['domain_uuid'];
		$domain_name = $row['domain_name'];
		$fax_uuid = $row['fax_uuid'];
		$origination_uuid = $row['origination_uuid'];
		$fax_log_uuid = $row['fax_log_uuid'];
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
	$sql .= "and domain_setting_category in ('domain', 'fax', 'fax_queue') ";
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
	unset($result, $parameters);

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
	$email_from_address = (isset($_SESSION['fax']['smtp_from']['text'])) ? $_SESSION['fax']['smtp_from']['text'] : $_SESSION['email']['smtp_from']['text'];
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

//add debug info
	if (isset($debug)) {
		echo "fax_retry_count $fax_retry_count\n";
		echo "retry_limit $retry_limit\n";
		echo "fax_status $fax_status\n";
	}

//sending the fax
	if ($fax_status == 'waiting' || $fax_status == 'trying' || $fax_status == 'busy') {

		//create event socket handle
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
			if (!$fp) {
				echo "Could not connect to event socket.\n";
				exit;	
			}

		//check if the uuid exists if it does then end the script
			if (trim(event_socket_request($fp, "api uuid_exists ".$origination_uuid)) == 'true') {
				echo "FAX job in progress.\n";
				exit;
			}

		//check if the uuid exists if it does then end the script
			if (trim(event_socket_request($fp, "api uuid_exists ".$origination_uuid)) == 'true') {
				echo "FAX job in progress.\n";
				exit;
			}

		//fax options, first attempt use the fax variables from settings
			if ($fax_retry_count == 0) {
				$fax_options = '';
			}
			if ($fax_retry_count == 1) {
				$fax_options = '';
				foreach($_SESSION['fax']['variable'] as $variable) {
					$fax_options .= $variable.",";
				}
			}
			elseif ($fax_retry_count == 2) {
				$fax_options = "fax_use_ecm=false,fax_enable_t38=true,fax_enable_t38_request=true";
			}
			elseif ($fax_retry_count == 3) {
				$fax_options = "fax_use_ecm=true,fax_enable_t38=true,fax_enable_t38_request=true,fax_disable_v17=false";
			}
			elseif ($fax_retry_count == 4) {
				$fax_options = "fax_use_ecm=true,fax_enable_t38=false,fax_enable_t38_request=false,fax_disable_v17=false";
			}
			elseif ($fax_retry_count == 5) {
				$fax_options = "fax_use_ecm=true,fax_enable_t38=true,fax_enable_t38_request=true,fax_disable_v17=true";
			}
			elseif ($fax_retry_count == 6) {
				$fax_options = "fax_use_ecm=false,fax_enable_t38=false,fax_enable_t38_request=false,fax_disable_v17=false";
			}

		//define the fax file
			$common_variables = '';
			$common_variables = "accountcode='"                  . $fax_accountcode         . "',";
			$common_variables .= "sip_h_accountcode='"          . $fax_accountcode         . "',";
			$common_variables .= "domain_uuid="                  . $domain_uuid             . ",";
			$common_variables .= "domain_name="                  . $domain_name             . ",";
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
			}
			else {
				//send the external call
				$fax_uri = $route_array[0];
			}

		//set the fax file name without the extension
			$fax_instance_id = pathinfo($fax_file, PATHINFO_FILENAME);

		//set the origination uuid
			$origination_uuid = uuid();

		//build a list of fax variables
			$dial_string = $common_variables;
			$dial_string .= $fax_options.",";
			$dial_string .= "origination_uuid="    . $origination_uuid. ",";
			$dial_string .= "fax_uuid="            . $fax_uuid. ",";
			$dial_string .= "fax_queue_uuid="      . $fax_queue_uuid. ",";
			$dial_string .= "mailto_address='"     . $fax_email_address   . "',";
			$dial_string .= "mailfrom_address='"   . $email_from_address . "',";
			$dial_string .= "fax_uri="             . $fax_uri  . ",";
			$dial_string .= "fax_retry_attempts="  . $fax_retry_count  . ",";  
			$dial_string .= "fax_retry_limit="     . $retry_limit  . ",";
			//$dial_string .= "fax_retry_sleep=180"  . ",";
			$dial_string .= "fax_verbose=true"     . ",";
			//$dial_string .= "fax_use_ecm=off"      . ",";
			$dial_string .= "api_hangup_hook='lua app/fax/resources/scripts/hangup_tx.lua'";
			$fax_command  = "originate {" . $dial_string . "}" . $fax_uri." &txfax('".$fax_file."')";
			//echo $fax_command."\n";

		//connect to event socket and send the command
			if ($fax_status != 'failed' && file_exists($fax_file)) {
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

		//update the database to say status to trying and set the command
			$array['fax_queue'][0]['fax_queue_uuid'] = $fax_queue_uuid;
			$array['fax_queue'][0]['domain_uuid'] = $domain_uuid;
			$array['fax_queue'][0]['origination_uuid'] = $origination_uuid;
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
			unset($array);

		//remove temporary permissions
			$p->delete('fax_queue_edit', 'temp');

	}

//post process
	if (in_array($fax_status, array('sent', 'failed'))) {

		//send the email
			if (strlen($fax_email_address) > 0 && file_exists($fax_file)) {
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
				$fax_file_tif = path_join($fax_file_dirname, $fax_file_filename . '.' . $fax_file_extension);
				$fax_file_pdf = path_join($fax_file_dirname, $fax_file_filename . '.pdf');
				
				if (file_exists($fax_file_pdf)) {
					$fax_file_name = $fax_file_filename . '.pdf';
				}
				else {
					$fax_file_name = $fax_file_filename . '.' . $fax_file_extension;
				}

				//get fax log data for email variables
				if (isset($fax_email_address) && strlen($fax_email_address) > 0 && isset($fax_log_uuid)) {
					$sql = "select * ";
					$sql .= "from v_fax_logs ";
					$sql .= "where fax_log_uuid = :fax_log_uuid ";
					$parameters['fax_log_uuid'] = $fax_log_uuid;
					$database = new database;
					$row = $database->select($sql, $parameters, 'row');
					if (is_array($row)) {
						$fax_success = $row['fax_success'];
						$fax_result_code = $row['fax_result_code'];
						$fax_result_text = $row['fax_result_text'];
						$fax_ecm_used = $row['fax_ecm_used'];
						$fax_local_station_id = $row['fax_local_station_id'];
						$fax_document_transferred_pages = $row["fax_document_transferred_pages"];
						$fax_document_total_pages = $row["fax_document_total_pages"];
						$fax_image_resolution = $row["fax_image_resolution"];
						$fax_image_size = $row["fax_image_size"];
						$fax_bad_rows = $row["fax_bad_rows"];
						$fax_transfer_rate = $row["fax_transfer_rate"];
						$fax_epoch = $row["fax_epoch"];
						$fax_duration = $row["fax_duration"];
						$fax_duration_formatted = sprintf('%02dh %02dm %02ds', ($fax_duration/ 3600),($fax_duration/ 60 % 60), $fax_duration% 60);
					}
					unset($parameters);
				}

				//replace variables in email subject
				$email_subject = str_replace('${domain_name}', $domain_uuid, $email_subject);
				$email_subject = str_replace('${number_dialed}', $fax_number, $email_subject);
				$email_subject = str_replace('${fax_file_name}', $fax_file_name, $email_subject);
				$email_subject = str_replace('${fax_extension}', $fax_extension, $email_subject);
				$email_subject = str_replace('${fax_messages}', $fax_messages, $email_subject);
				$email_subject = str_replace('${fax_file_warning}', $fax_file_warning, $email_subject);
				$email_subject = str_replace('${fax_subject_tag}', $fax_email_inbound_subject_tag, $email_subject);
				
				$email_subject = str_replace('${fax_success}', $fax_success, $email_subject);
				$email_subject = str_replace('${fax_result_code}', $fax_result_code, $email_subject);
				$email_subject = str_replace('${fax_result_text}', $fax_result_text, $email_subject);
				$email_subject = str_replace('${fax_ecm_used}', $fax_ecm_used, $email_subject);
				$email_subject = str_replace('${fax_local_station_id}', $fax_local_station_id, $email_subject);
				$email_subject = str_replace('${fax_document_transferred_pages}', $fax_document_transferred_pages, $email_subject);
				$email_subject = str_replace('${fax_document_total_pages}', $fax_document_total_pages, $email_subject);
				$email_subject = str_replace('${fax_image_resolution}', $fax_image_resolution, $email_subject);
				$email_subject = str_replace('${fax_image_size}', $fax_image_size, $email_subject);
				$email_subject = str_replace('${fax_bad_rows}', $fax_bad_rows, $email_subject);
				$email_subject = str_replace('${fax_transfer_rate}', $fax_transfer_rate, $email_subject);
				$email_subject = str_replace('${fax_date}', date('Y-m-d H:i:s', $fax_epoch), $email_subject);
				$email_subject = str_replace('${fax_duration}', $fax_duration, $email_subject);
				$email_subject = str_replace('${fax_duration_formatted}', $fax_duration_formatted, $email_subject);
				
				//replace variables in email body
				$email_body = str_replace('${domain_name}', $domain_uuid, $email_body);
				$email_body = str_replace('${number_dialed}', $fax_number, $email_body);
				$email_body = str_replace('${fax_file_name}', $fax_file_name, $email_body);
				$email_body = str_replace('${fax_extension}', $fax_extension, $email_body);
				$email_body = str_replace('${fax_messages}', $fax_messages, $email_body);
				$email_body = str_replace('${fax_file_warning}', $fax_file_warning, $email_body);
				$email_body = str_replace('${fax_subject_tag}', $fax_email_inbound_subject_tag, $email_body);
				
				$email_body = str_replace('${fax_success}', $fax_success, $email_body);
				$email_body = str_replace('${fax_result_code}', $fax_result_code, $email_body);
				$email_body = str_replace('${fax_result_text}', $fax_result_text, $email_body);
				$email_body = str_replace('${fax_ecm_used}', $fax_ecm_used, $email_body);
				$email_body = str_replace('${fax_local_station_id}', $fax_local_station_id, $email_body);
				$email_body = str_replace('${fax_document_transferred_pages}', $fax_document_transferred_pages, $email_body);
				$email_body = str_replace('${fax_document_total_pages}', $fax_document_total_pages, $email_body);
				$email_body = str_replace('${fax_image_resolution}', $fax_image_resolution, $email_body);
				$email_body = str_replace('${fax_image_size}', $fax_image_size, $email_body);
				$email_body = str_replace('${fax_bad_rows}', $fax_bad_rows, $email_body);
				$email_body = str_replace('${fax_transfer_rate}', $fax_transfer_rate, $email_body);
				$email_body = str_replace('${fax_date}', date('Y-m-d H:i:s', $fax_epoch), $email_body);
				$email_body = str_replace('${fax_duration}', $fax_duration, $email_body);
				$email_body = str_replace('${fax_duration_formatted}', $fax_duration_formatted, $email_body);

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
					foreach($email_addresses as $email_address) {
							//send the email
							$email = new email;
							$email->domain_uuid = $domain_uuid;
							$email->recipients = $email_address;
							$email->subject = $email_subject;
							$email->body = $email_body;
							$email->from_address = $email_from_address;
							$email->from_name = $email_from_name;
							$email->attachments = $email_attachments;
							$email->debug_level = 3;
							$email_error = $mail->error;
							//view_array($email);
							$sent = $email->send();

							//debug info
							if (isset($debug)) {
								echo "template_subcategory: ".$template_subcategory."\n";
								echo "email_adress: ".$email_address."\n";
								echo "email_from: ".$email_from_name."\n";
								echo "email_from_name: ".$email_from_address."\n";
								echo "email_subject: ".$email_subject."\n";
								//echo "email_body: ".$email_body."\n";
								echo "email_error: ".$email_error."\n";
								echo "\n";
							}
					}
				}
			}

		//update the database to say status to trying and set the command
			$array['fax_queue'][0]['fax_queue_uuid'] = $fax_queue_uuid;
			$array['fax_queue'][0]['domain_uuid'] = $domain_uuid;
			$array['fax_queue'][0]['fax_status'] = $fax_status;
			$array['fax_queue'][0]['fax_notify_sent'] = true;
			$array['fax_queue'][0]['fax_notify_date'] = 'now()';

		//add temporary permissions
			$p = new permissions;
			$p->add('fax_queue_edit', 'temp');

		//save the data
			$database = new database;
			$database->app_name = 'fax queue';
			$database->app_uuid = '3656287f-4b22-4cf1-91f6-00386bf488f4';
			$database->save($array, false);
			unset($array);

		//remove temporary permissions
			$p->delete('fax_queue_edit', 'temp');

		//send the email
			//if ($sent) {
			//	echo "Mailer Error";
			//	$email_status=$mail;
			//}
			//else {
			//	echo "Message sent!";
			//	$email_status="ok";
			//}
	}

//remove the old pid file
	if (file_exists($pid_file)) {
		unlink($pid_file);
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

//get and save the output from the buffer
	if (isset($file)) {
		echo "\n";
		$content = ob_get_contents(); //get the output from the buffer
		$content = str_replace("<br />", "", $content);

		ob_end_clean(); //clean the buffer

		fwrite($fp, $content);
		fclose($fp);
	}

?>
