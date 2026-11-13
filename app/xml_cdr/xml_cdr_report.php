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
	Portions created by the Initial Developer are Copyright (C) 2008-2026
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";

//detect if running from CLI or web browser
	$is_cli = php_sapi_name() === 'cli';

//only allow command line access
	if (!$is_cli) {
		echo "access denied";
		exit;
	}

//check permissions/authentication
	if ($is_cli) {
		//show the help if requested
		if (isset($argv) && is_array($argv)) {
			foreach ($argv as $arg) {
				if ($arg == '--help' || $arg == '-h') {
					echo "Usage: php xml_cdr_report.php --to=<email> --domain_name=<name> [options]\n";
					echo "\n";
					echo "Send the call detail records for a domain and date as a CSV email attachment.\n";
					echo "\n";
					echo "Options:\n";
					echo "  --to=<email>          -t=<email>     Required: email address to send the report to\n";
					echo "  --domain_name=<name>  -n=<name>      Required: domain to build the report for (e.g. example.com)\n";
					echo "  --date=<date>         -d=<date>      Optional: report date, use 'yesterday', 'today' or a specific date YYYY-MM-DD (default: yesterday)\n";
					echo "  --timezone=<tz>       -z=<tz>        Optional: time zone for the report (default: domain time zone)\n";
					echo "  --debug=<level>       -d=<level>     Optional: email debug level, use true for verbose output\n";
					echo "  --help                -h             Show this help and exit\n";
					echo "\n";
					echo "Examples:\n";
					echo "  php xml_cdr_report.php --to=reports@example.com --domain_name=example.com\n";
					echo "  php xml_cdr_report.php --to=reports@example.com --domain_name=example.com --date=yesterday\n";
					exit(0);
				}
			}
		}
	}

//set the time zone
	$time_zone = $settings->get('domain', 'time_zone', date_default_timezone_get());
	date_default_timezone_set($time_zone);

//set the default report variables
	$email_recipients = [];
	$domain_name = '';
	$report_date = 'yesterday'; //use 'yesterday', 'today' or a specific date (YYYY-MM-DD)
	$debug = '';

//set the time format options: 12h, 24h
	if ($settings->get('domain', 'time_format') == '24h') {
		$time_format = 'HH24:MI';
	}
	else {
		$time_format = 'HH12:MI am';
	}

//parse the CLI arguments
	$cli_args = [];
	if ($is_cli && isset($argv) && is_array($argv)) {
		foreach ($argv as $arg) {
			if (strpos($arg, '--') === 0 && strpos($arg, '=') !== false) {
				list($key, $value) = explode('=', $arg, 2);
				$cli_args[substr($key, 2)] = $value;
			}
			//also support short options: -t, -n, -d, -z, -g
			elseif (preg_match('/^-(.)=(.+)$/', $arg, $matches)) {
				$short_to_long = [
					't' => 'to',
					'n' => 'domain_name',
					'd' => 'date',
					'z' => 'timezone',
					'd' => 'debug'
				];
				if (isset($short_to_long[$matches[1]])) {
					$cli_args[$short_to_long[$matches[1]]] = $matches[2];
				}
			}
		}
	}

//override the report variables with the CLI arguments
	if (isset($cli_args['to']) && valid_email($cli_args['to'])) {
		$email_recipients = array($cli_args['to']);
	}
	if (isset($cli_args['domain_name']) && !empty($cli_args['domain_name'])) {
		$domain_name = $cli_args['domain_name'];
	}
	if (isset($cli_args['date']) && !empty($cli_args['date'])) {
		$report_date = $cli_args['date'];
	}
	if (isset($cli_args['timezone']) && !empty($cli_args['timezone'])) {
		$time_zone = $cli_args['timezone'];
		date_default_timezone_set($time_zone);
	}
	if (isset($cli_args['debug']) && !empty($cli_args['debug'])) {
		$debug = $cli_args['debug'];
	}

//set the report variables from the request in web mode
	if (!$is_cli) {
		if (!empty($_REQUEST['to']) && valid_email($_REQUEST['to'])) {
			$email_recipients = array($_REQUEST['to']);
		}
		if (!empty($_REQUEST['domain_name'])) {
			$domain_name = $_REQUEST['domain_name'];
		}
		if (!empty($_REQUEST['date'])) {
			$report_date = $_REQUEST['date'];
		}
		if (!empty($_REQUEST['timezone'])) {
			$time_zone = $_REQUEST['timezone'];
			date_default_timezone_set($time_zone);
		}
		if (!empty($_REQUEST['debug'])) {
			$debug = $_REQUEST['debug'];
		}
	}

//a recipient email address is required
	if (empty($email_recipients)) {
		if ($is_cli) {
			echo "Error: recipient email address is required.\n";
			echo "Usage: php xml_cdr_report.php --to=<email> --domain_name=<name> [options], use --help for more information.\n";
		}
		else {
			echo "Error: recipient email address is required";
		}
		exit(1);
	}

//the domain name is required
	if (empty($domain_name)) {
		if ($is_cli) {
			echo "Error: domain name is required.\n";
			echo "Usage: php xml_cdr_report.php --to=<email> --domain_name=<name> [options], use --help for more information.\n";
		}
		else {
			echo "Error: domain name is required";
		}
		exit(1);
	}

//get the domain uuid from the domain name
	$sql = "select domain_uuid from v_domains \n";
	$sql .= "where domain_name = :domain_name \n";
	$parameters['domain_name'] = $domain_name;
	$domain_uuid = $database->select($sql, $parameters, 'column');
	unset($sql, $parameters);
	if (empty($domain_uuid)) {
		echo "Error: domain '$domain_name' not found\n";
		exit(1);
	}

//validate the report date, allow 'yesterday', 'today' or a specific date (YYYY-MM-DD)
	if (!in_array(strtolower($report_date), array('yesterday', 'today'))
	&& !(preg_match('/^\d{4}-\d{2}-\d{2}$/', $report_date)
	&& date('Y-m-d', strtotime($report_date)) === $report_date)) {
		echo "Error: invalid report date '$report_date'. Use 'yesterday', 'today' or a specific date (YYYY-MM-DD).\n";
		exit(1);
	}

//calculate the time range for the report
	$start_epoch = strtotime($report_date);
	$end_epoch = strtotime($report_date . ' +1 day');
	if (!$start_epoch || !$end_epoch) {
		echo "Error: invalid report date '$report_date'. Use 'yesterday', 'today' or a specific date (YYYY-MM-DD).\n";
		exit(1);
	}

//retrieve the call detail records for the report date
	$sql = "select \n";
	$sql .= "xml_cdr_uuid, \n";
	$sql .= "sip_call_id, \n";
	$sql .= "start_stamp, \n";
	$sql .= "end_stamp, \n";
	$sql .= "to_char(timezone(:time_zone, start_stamp), 'DD Mon YYYY') as start_date_formatted, \n";
	$sql .= "to_char(timezone(:time_zone, start_stamp), '".$time_format."') as start_time_formatted, \n";
	$sql .= "start_epoch, \n";
	$sql .= "hangup_cause, \n";
	$sql .= "billsec as duration, \n";
	$sql .= "billmsec, \n";
	$sql .= "missed_call, \n";
	$sql .= "record_path, \n";
	$sql .= "record_name, \n";
	$sql .= "bridge_uuid, \n";
	$sql .= "direction, \n";
	$sql .= "billsec, \n";
	$sql .= "caller_id_name, \n";
	$sql .= "caller_id_number, \n";
	$sql .= "caller_destination, \n";
	$sql .= "source_number, \n";
	$sql .= "destination_number, \n";
	$sql .= "leg, \n";
	$sql .= "read_codec, \n";
	$sql .= "write_codec, \n";
	$sql .= "cc_side, \n";
	$sql .= "answer_stamp, \n";
	$sql .= "status, \n";
	$sql .= "sip_hangup_disposition, \n";
	$sql .= "call_disposition, \n";
	$sql .= "waitsec as wait, \n";
	$sql .= "(answer_epoch - start_epoch) as tta \n";
	$sql .= "from v_xml_cdr \n";
	$sql .= "where domain_uuid = :domain_uuid \n";
	$sql .= "and start_epoch >= :start_epoch \n";
	$sql .= "and start_epoch < :end_epoch \n";
	$sql .= "order by \n";
	$sql .= "start_epoch asc \n";
	$parameters['time_zone'] = $time_zone;
	$parameters['domain_uuid'] = $domain_uuid;
	$parameters['start_epoch'] = $start_epoch;
	$parameters['end_epoch'] = $end_epoch;
	$cdr_rows = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);
	if ($cdr_rows === false) {
		echo "Error: " . $database->message['message'] . "\n";
		exit(1);
	}
	$record_count = is_array($cdr_rows) ? count($cdr_rows) : 0;

//write the csv file
	$csv_path = sys_get_temp_dir() . "/daily-call-report.csv";
	$csv_handle = fopen($csv_path, 'w');
	if (!$csv_handle) {
		echo "Error: unable to write to $csv_path\n";
		exit(1);
	}

	//write the csv header
	fputcsv($csv_handle, array(
		'sip_call_id', 'start_stamp', 'end_stamp', 'start_date_formatted', 'start_time_formatted',
		'start_epoch', 'hangup_cause', 'duration', 'billmsec', 'missed_call',
		'record_path', 'record_name', 'xml_cdr_uuid', 'bridge_uuid', 'direction',
		'billsec', 'caller_id_name', 'caller_id_number', 'caller_destination',
		'source_number', 'destination_number', 'leg', 'read_codec', 'write_codec',
		'cc_side', 'answer_stamp', 'status', 'sip_hangup_disposition', 'call_disposition',
		'wait', 'tta'
	));

	//write the csv rows
	if (is_array($cdr_rows) && @sizeof($cdr_rows) != 0) {
		foreach ($cdr_rows as $cdr_row) {
			//convert the postgres boolean to a readable value
			$missed_call = empty($cdr_row['missed_call']) ? 'false' : 'true';
			fputcsv($csv_handle, array(
				$cdr_row['sip_call_id'],
				$cdr_row['start_stamp'],
				$cdr_row['end_stamp'],
				$cdr_row['start_date_formatted'],
				$cdr_row['start_time_formatted'],
				$cdr_row['start_epoch'],
				$cdr_row['hangup_cause'],
				$cdr_row['duration'],
				$cdr_row['billmsec'],
				$missed_call,
				$cdr_row['record_path'],
				$cdr_row['record_name'],
				$cdr_row['xml_cdr_uuid'],
				$cdr_row['bridge_uuid'],
				$cdr_row['direction'],
				$cdr_row['billsec'],
				$cdr_row['caller_id_name'],
				$cdr_row['caller_id_number'],
				$cdr_row['caller_destination'],
				$cdr_row['source_number'],
				$cdr_row['destination_number'],
				$cdr_row['leg'],
				$cdr_row['read_codec'],
				$cdr_row['write_codec'],
				$cdr_row['cc_side'],
				$cdr_row['answer_stamp'],
				$cdr_row['status'],
				$cdr_row['sip_hangup_disposition'],
				$cdr_row['call_disposition'],
				$cdr_row['wait'],
				$cdr_row['tta']
			));
		}
	}
	fclose($csv_handle);
	unset($cdr_rows);
	echo "xml_cdr_report: wrote $record_count record(s) for $report_date ($time_zone, $domain_name) to $csv_path\n";

//set the email variables
	$email_array = $email_recipients;
	$email_subject = 'Daily Call Detail Report';
	$email_body = "Your daily call detail report is attached.";
	$smtp_from = $settings->get('email', 'smtp_from');
	$smtp_from_name = $settings->get('email', 'smtp_from_name');

	//set the email attachment
	$email_attachments = array();
	$email_attachments[] = array(
		'name' => basename($csv_path),
		'path' => sys_get_temp_dir(),
		'mime_type' => 'text/csv',
		'base64' => ''
	);

//create the email object
	$email = new email;

//send email
	foreach ($email_array as $email_address) {
		$email->recipients = $email_address;
		$email->subject = $email_subject;
		$email->body = $email_body;
		$email->from_address = $smtp_from;
		$email->from_name = $smtp_from_name;
		if (isset($email_attachments)) {
			$email->attachments = $email_attachments;
		}
		$email->debug_level = 3;
		$email_response = $email->send();
		if (!empty($debug) && $debug == 'true') {
			echo $email_response;
		}
	}

//log the results
	$success = isset($email_response) && $email_response !== false;
	if ($success) {
		echo "xml_cdr_report: Email " . $email_subject . " Sent to " . implode(', ', $email_array) . "\n";
		exit(0);
	}
	else {
		echo "xml_cdr_report: Email send failed\n";
		exit(1);
	}
