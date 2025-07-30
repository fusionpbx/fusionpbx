<?php

//check the permission
	if (defined('STDIN')) {
		//includes files
		require_once  dirname(__DIR__, 4) . "/resources/require.php";
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

//shutdown call back function
	function shutdown() {
		//when the fax status is still sending
		//then set the fax status to trying
		$sql = "update v_fax_queue ";
		$sql .= "set fax_status = 'trying' ";
		$sql .= "where fax_queue_uuid = :fax_queue_uuid ";
		$sql .= "and fax_status = 'sending' ";
		$parameters['fax_queue_uuid'] = $fax_queue_uuid;
		$database->execute($sql, $parameters);
		unset($sql);
	}
	register_shutdown_function('shutdown');

//define the process id file
	$pid_file = '/var/run/fusionpbx/fax_send.'.$fax_queue_uuid.'.pid';
	//echo "pid_file: ".$pid_file."\n";

//function to check if the process exists
	function process_exists($file = '') {
		//check if the file exists return false if not found
		if (!file_exists($file)) {
			return false;
		}

		//check to see if the process id is valid
		$pid = file_get_contents($file);
		if (filter_var($pid, FILTER_VALIDATE_INT) === false) {
			return false;
		}

		//check if the process is running
		exec('ps -p '.$pid, $output);
		if (count($output) > 1) {
			return true;
		}
		else {
			return false;
		}
	}

//remove single quote
	function escape_quote($value) {
		if (!empty($value)) {
			return str_replace("'", "", $value);
		}
		else {
			return false;
		}
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

//get the fax queue details to send
	$sql = "select q.*, d.domain_name, f.fax_toll_allow ";
	$sql .= "from v_fax_queue as q, v_domains as d, v_fax as f ";
	$sql .= "where fax_queue_uuid = :fax_queue_uuid ";
	$sql .= "and q.domain_uuid = d.domain_uuid and f.fax_uuid = q.fax_uuid";
	$parameters['fax_queue_uuid'] = $fax_queue_uuid;
	$row = $database->select($sql, $parameters, 'row');
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
		$fax_recipient = $row["fax_recipient"];
		$fax_prefix = $row["fax_prefix"];
		$fax_number = $row["fax_number"];
		$fax_email_address = $row["fax_email_address"];
		$fax_file = $row["fax_file"];
		$fax_status = $row["fax_status"];
		$fax_retry_count = $row["fax_retry_count"];
		$fax_accountcode = $row["fax_accountcode"];
		$fax_command = $row["fax_command"];
		$fax_toll_allow = $row["fax_toll_allow"];
	}
	unset($parameters);

//get the settings object
	$settings = new settings(['database' => $database, 'domain_uuid' => $domain_uuid]);

//prepare the smtp from and from name variables
	$email_from = $settings->get('fax','smtp_from');
	$email_from_name = $settings->get('fax','smtp_from_name');
	if (empty($email_from)) {
		$email_from = $settings->get('email','smtp_from');
	}
	if (empty($email_from_name)) {
		$email_from_name = $settings->get('email','smtp_from_name');
	}

//prepare the variables to send the fax
	$retry_limit = $settings->get('fax_queue','retry_limit');

//prepare the fax retry count
	if (!isset($fax_retry_count)) {
		$fax_retry_count = 0;
	}
	else {
		$fax_retry_count = $fax_retry_count + 1;
	}

//determine if the retry count exceed the limit
	if ($fax_status != 'sent' && $fax_retry_count > $retry_limit) {
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

		//get the provider_prefix from the domain_variables dialplan
			$sql = "select dialplan_detail_data from v_dialplan_details ";
			$sql .= "where dialplan_uuid in ( ";
			$sql .= "	select dialplan_uuid from v_dialplans ";
			$sql .= "	where dialplan_name = 'domain-variables' ";
			$sql .= "	and domain_uuid = :domain_uuid ";
			$sql .= ")	";
			$sql .= "and dialplan_detail_data like 'provider_prefix%' ";
			$sql .= "and dialplan_detail_enabled = 'true' ";
			$parameters['domain_uuid'] = $domain_uuid;
			$row = $database->select($sql, $parameters, 'row');
			$dialplan_detail_data = $row["dialplan_detail_data"];
			unset($sql, $parameters, $row);
			if (!empty($dialplan_detail_data)) {
				$provider_prefix = explode('=', $dialplan_detail_data)[1];
			}

		//create event socket handle
			$esl = event_socket::create();
			if (!$esl->is_connected()) {
				echo "Could not connect to event socket.\n";
				exit;
			}

		//fax options, first attempt use the fax variables from settings
			if ($fax_retry_count == 0) {
				//use default settings or domain settings (defaults to t38)
				$fax_options = '';
				foreach($settings->get('fax','variable') as $variable) {
					$fax_options .= $variable.",";
				}
			}
			elseif ($fax_retry_count == 1) {
				//send without t38 faster
				$fax_options = "fax_use_ecm=true,fax_enable_t38=false,fax_enable_t38_request=false,fax_disable_v17=false";
			}
			elseif ($fax_retry_count == 2) {
				//use t38 passthru
				$fax_options = "fax_use_ecm=false,fax_enable_t38=true,t38_passthru=true";
			}
			elseif ($fax_retry_count == 3) {
				//send without t38 slower
				$fax_options = "fax_use_ecm=true,fax_enable_t38=false,fax_enable_t38_request=false,fax_disable_v17=true";
			}
			elseif ($fax_retry_count == 4) {
				//send with t38 no error correction
				$fax_options = "fax_use_ecm=false,fax_enable_t38=true,fax_enable_t38_request=true";
			}
			elseif ($fax_retry_count == 5) {
				//send with t38 with error correction faster
				$fax_options = "fax_use_ecm=true,fax_enable_t38=true,fax_enable_t38_request=true,fax_disable_v17=false";
			}
			elseif ($fax_retry_count == 6) {
				//send with t38 with error correction slower
				$fax_options = "fax_use_ecm=true,fax_enable_t38=true,fax_enable_t38_request=true,fax_disable_v17=true";
			}
			else {
				//try the user definable method again
				$fax_options = '';
				foreach($settings->get('fax','variable') as $variable) {
					$fax_options .= $variable.",";
				}
			}

		//extract fax_dtmf from the fax number
			fax_split_dtmf($fax_number, $fax_dtmf);

		//get the fax toll allow
			if (!empty($fax_toll_allow)) {
				$channel_variables["toll_allow"] = $fax_toll_allow;
			}

		//check to see if the destination number is local
			$local_destination = false;
			if ($settings->get('fax_queue','prefer_local', false)) {
				$sql = "select count(destination_uuid) ";
				$sql .= "from v_destinations ";
				$sql .= "where (";
				$sql .= " destination_number = :destination_number ";
				$sql .= " or concat(destination_prefix, destination_number) = :destination_number ";
				$sql .= " or concat(destination_trunk_prefix, destination_number) = :destination_number ";
				$sql .= " or concat(destination_area_code, destination_number) = :destination_number ";
				$sql .= " or concat(destination_prefix, destination_area_code, destination_number) = :destination_number ";
				$sql .= ") ";
				$parameters['destination_number'] = $fax_number;
				$destination_count = $database->select($sql, $parameters, 'column');
				if ($destination_count > 0) {
					$local_destination = true;
					$route_array[] = 'loopback/'.$fax_number.'/public';
				}
			}

		//define the fax file
			$common_variables = '';
			$common_variables = "accountcode='"                  . escape_quote($fax_accountcode) . "',";
			$common_variables .= "sip_h_accountcode='"           . escape_quote($fax_accountcode) . "',";
			$common_variables .= "domain_uuid="                  . $domain_uuid . ",";
			$common_variables .= "domain_name="                  . $domain_name . ",";
			$common_variables .= "origination_caller_id_name='"  . escape_quote($fax_caller_id_name) . "',";
			$common_variables .= "origination_caller_id_number=" . $fax_caller_id_number . ",";
			$common_variables .= "fax_ident='"                   . escape_quote($fax_caller_id_number) . "',";
			$common_variables .= "fax_header='"                  . escape_quote($fax_caller_id_name) . "',";
			$common_variables .= "fax_file='"                    . escape_quote($fax_file) . "',";
			$common_variables .= "hangup_after_bridge=true,";
			$common_variables .= "continue_on_fail=true,";

		//add the fax destination number variables
			if ($local_destination) {
				$common_variables .= "sip_to_user=".$fax_number.",";
				$common_variables .= "sip_req_user=".$fax_number.",";
			}


		//prepare the fax command
			$channel_variables["toll_allow"] = !empty($fax_toll_allow) ? $fax_toll_allow : null;
			$route_array = outbound_route_to_bridge($domain_uuid, $fax_prefix . $fax_number, $channel_variables);

			if (empty($route_array)) {
				//send the internal call to the registered extension
				if (count($route_array) == 0) {
					//check for valid extension
					$sql = "select count(extension_uuid) ";
					$sql .= "from v_extensions ";
					$sql .= "where extension = :fax_number ";
					$sql .= "and domain_uuid = :domain_uuid ";
					$parameters['domain_uuid'] = $domain_uuid;
					$parameters['fax_number'] = $fax_number;
					$extension_count = $database->select($sql, $parameters, 'column');
					if ($extension_count > 0) {
						//send the internal call to the registered extension
						$fax_uri = "user/".$fax_number."@".$domain_name;
					}
					else {
						$fax_uri = '';
						$fax_status = 'failed';
					}
				}
			}
			else {
				foreach($route_array as $key => $bridge) {
					//add the bridge to the fax_uri, after first iteration add the delimiter
					if ($key == 0) {
						$fax_uri = $bridge;
					}
					else {
						$fax_uri .= '|'.$bridge;
					}

					//add the provider_prefix
					if (!empty($provider_prefix)) {
						$fax_uri = preg_replace('/\${provider_prefix}/', $provider_prefix, $fax_uri);
					}

					//remove switch ${variables} from the bridge statement
					$fax_uri = preg_replace('/\${[^}]+}/', '', $fax_uri);
				}
			}

		//set the origination uuid
			$origination_uuid = uuid();

		//build a list of fax variables
			$dial_string = $common_variables;
			$dial_string .= $fax_options.",";
			$dial_string .= "origination_uuid="    . $origination_uuid . ",";
			$dial_string .= "fax_uuid="            . $fax_uuid . ",";
			$dial_string .= "fax_queue_uuid="      . $fax_queue_uuid . ",";
			$dial_string .= "mailto_address='"     . $fax_email_address . "',";
			$dial_string .= "mailfrom_address='"   . $email_from . "',";
			$dial_string .= "fax_retry_attempts="  . $fax_retry_count . ",";
			$dial_string .= "fax_retry_limit="     . $retry_limit . ",";
			$dial_string .= "fax_recipient='"      . escape_quote($fax_recipient) . "',";
			//$dial_string .= "fax_retry_sleep=180,";
			$dial_string .= "fax_verbose=true,";
			//$dial_string .= "fax_use_ecm=off,";
			$dial_string .= "absolute_codec_string=PCMU,PCMA,";
			$dial_string .= "api_hangup_hook='lua app/fax/resources/scripts/hangup_tx.lua'";

		//connect to event socket and send the command
			if ($fax_status != 'failed' && file_exists($fax_file)) {
				//send the fax and try another route if the fax fails
				$fax_command  = "originate {" . $dial_string . ",fax_uri=".$fax_uri."}" . $fax_uri." &txfax('".$fax_file."')";
				$fax_response = event_socket::api($fax_command);
				$response = str_replace("\n", "", $fax_response);
				$response = trim(str_replace("+OK", "", $response));
				if (is_uuid($response)) {
					//originate command accepted
					$uuid = $response;
					echo "uuid: ".$uuid."\n";
				}
				else {
					//originate command failed (-ERR INVALID_GATEWAY or other errors)
					echo "response: ".$response."\n";
				}

				//set the fax file name without the extension
				$fax_instance_id = pathinfo($fax_file, PATHINFO_FILENAME);

				//set the fax status
				if (empty($fax_status)) {
					$fax_status = 'sending';
				}

				//update the database to say status to trying and set the command
				$array['fax_queue'][0]['fax_queue_uuid'] = $fax_queue_uuid;
				$array['fax_queue'][0]['domain_uuid'] = $domain_uuid;
				$array['fax_queue'][0]['origination_uuid'] = $origination_uuid;
				$array['fax_queue'][0]['fax_status'] = $fax_status;
				$array['fax_queue'][0]['fax_retry_count'] = $fax_retry_count;
				$array['fax_queue'][0]['fax_retry_date'] = 'now()';
				$array['fax_queue'][0]['fax_command'] = $fax_command;
				$array['fax_queue'][0]['fax_response'] = $fax_response;

				//add temporary permissions
				$p = permissions::new();
				$p->add('fax_queue_edit', 'temp');

				//save the data
				$database->app_name = 'fax queue';
				$database->app_uuid = '3656287f-4b22-4cf1-91f6-00386bf488f4';
				$database->save($array, false);
				unset($array);

				//remove temporary permissions
				$p->delete('fax_queue_edit', 'temp');
			}
			else {
				echo "fax file missing: ".$fax_file."\n";
				$fax_status = 'failed';
			}

	}

//post process
	if (in_array($fax_status, array('sent', 'failed'))) {

		//send the email
			if (!empty($fax_email_address) && file_exists($fax_file)) {
				//get the language code
				$language_code = $settings->get('domain','language');

				//get the template subcategory
				if (isset($fax_relay) && $fax_relay == 'true') {
					$template_subcategory = 'relay';
				}
				else {
					$template_subcategory = 'inbound';
				}

				//get the email template from the database
				if (isset($fax_email_address) && !empty($fax_email_address)) {
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
				if (isset($fax_email_address) && !empty($fax_email_address) && isset($fax_log_uuid)) {
					$sql = "select * ";
					$sql .= "from v_fax_logs ";
					$sql .= "where fax_log_uuid = :fax_log_uuid ";
					$parameters['fax_log_uuid'] = $fax_log_uuid;
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
				if (isset($fax_email_address) && !empty($fax_email_address)) {
					//add the attachment
					if (!empty($fax_file_name)) {
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
							$email->from_address = $email_from;
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
								echo "email_from_name: ".$email_from."\n";
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
			$p = permissions::new();
			$p->add('fax_queue_edit', 'temp');

		//save the data
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

//wait for a few seconds
	sleep(1);

//remove the old pid file
	if (file_exists($pid_file)) {
		unlink($pid_file);
	}

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

?>
