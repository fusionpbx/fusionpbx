<?php

//check the permission
	if (defined('STDIN')) {
		$document_root = str_replace("\\", "/", $_SERVER["PHP_SELF"]);
		preg_match("/^(.*)\/app\/.*$/", $document_root, $matches);
		$document_root = $matches[1];
		set_include_path($document_root);
		$_SERVER["DOCUMENT_ROOT"] = $document_root;
		require_once "resources/require.php";
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

//get the email details to send
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

//get some more info to send the fax
	$mail_from_address = (isset($_SESSION['fax']['smtp_from']['text'])) ? $_SESSION['fax']['smtp_from']['text'] : $_SESSION['email']['smtp_from']['text'];

//get the call center settings
	$retry_limit = $_SESSION['fax_queue']['retry_limit']['numeric'];
	//$retry_interval = $_SESSION['fax_queue']['retry_interval']['numeric'];

//prepare the fax retry count
	if (strlen($fax_retry_count) == 0) {
		$fax_retry_count = 0;
	}
	else {
		$fax_retry_count = $fax_retry_count + 1;
	}

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
	$fax_instance_uuid = pathinfo($fax_file, PATHINFO_FILENAME);

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
	$dial_string .= "api_hangup_hook='lua app/fax/resources/scripts/hangup_rx.lua'";
	$fax_command  = "originate {" . $dial_string . "}" . $fax_uri." &txfax('".$fax_file."')";
	//echo $fax_command."\n";

//connect to event socket and send the command
	if (file_exists($fax_file)) {
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

//update the database to say status to trying and set the command
	$array['fax_queue'][0]['fax_queue_uuid'] = $fax_queue_uuid;
	$array['fax_queue'][0]['domain_uuid'] = $domain_uuid;
	if ($fax_retry_count >= $retry_limit) {
		$array['fax_queue'][0]['fax_status'] = 'failed';
	}
	else {
		$array['fax_queue'][0]['fax_status'] = 'trying';
	}
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

//wait for a few seconds
	//sleep(1);

//move the generated tif (and pdf) files to the sent directory
	//if (file_exists($dir_fax_temp.'/'.$fax_instance_uuid.".tif")) {
	//	copy($dir_fax_temp.'/'.$fax_instance_uuid.".tif", $dir_fax_sent.'/'.$fax_instance_uuid.".tif");
	//}
//	if (file_exists($dir_fax_temp.'/'.$fax_instance_uuid.".pdf")) {
//		copy($dir_fax_temp.'/'.$fax_instance_uuid.".pdf ", $dir_fax_sent.'/'.$fax_instance_uuid.".pdf");
//	}

//send context to the temp log
	//echo "Subject: ".$email_subject."\n";
	//echo "From: ".$email_from."\n";
	//echo "Reply-to: ".$email_from."\n";
	//echo "To: ".$email_to."\n";
	//echo "Date: ".$email_date."\n";
	//echo "Transcript: ".$array['message']."\n";
	//echo "Body: ".$email_body."\n";

//send email
	//ob_start();
	//$sent = !send_email($email_to, $email_subject, $email_body, $email_error, null, null, 3, 3, $email_attachments) ? false : true;
	//$response = ob_get_clean();
	//echo $response;

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
