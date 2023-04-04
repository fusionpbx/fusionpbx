<?php

//check the permission
	if (defined('STDIN')) {
		//set the include path
		$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
		set_include_path(parse_ini_file($conf[0])['document.root']);

		//includes files
		require_once "resources/require.php";
	}
	else {
		exit;
	}

//increase limits
	set_time_limit(0);
	//ini_set('max_execution_time',1800); //30 minutes
	//ini_set('memory_limit', '512M');

//save the arguments to variables
	$script_name = $argv[0];
	if (!empty($argv[1])) {
		parse_str($argv[1], $_GET);
	}

//get the primary key
	if (Is_array($_GET)) {
		$hostname = urldecode($_GET['hostname']);
		$debug = $_GET['debug'];
		$sleep_seconds = $_GET['sleep'];
	}
	else {
		//invalid uuid
		exit;
	}

//connect to event socket
	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);

//get the list
	$sql = "select domain_name, extension, user_context, do_not_disturb, description ";
	$sql .= "from v_extensions as e, v_domains as d ";
	$sql .= "where do_not_disturb = 'true' ";
	$sql .= "and e.domain_uuid = d.domain_uuid ";
	$sql .= "and enabled = 'true' ";
	$database = new database;
	$results = $database->select($sql, $parameters, 'all');
	unset($parameters);

//view_array($results);
	foreach($results as $row) {

		//build the event
		$cmd = "sendevent PRESENCE_IN\n";
		$cmd .= "proto: sip\n";
		$cmd .= "login: ".$row['extension']."@".$row['domain_name']."\n";
		$cmd .= "from: ".$row['extension']."@".$row['domain_name']."\n";
		$cmd .= "status: Active (1 waiting)\n";
		$cmd .= "rpid: unknown\n";
		$cmd .= "event_type: presence\n";
		$cmd .= "alt_event_type: dialog\n";
		$cmd .= "event_count: 1\n";
		$cmd .= "unique-id: ".uuid()."\n";
		$cmd .= "Presence-Call-Direction: outbound\n";
		$cmd .= "answer-state: confirmed\n";
		//$cmd .= "answer-state: early\n";
		//$cmd .= "answer-state: terminated\n";

		//send message to the console
		if (isset($debug)) {
			echo "\n";
			echo "[presence] dnd ".$row['extension']."@".$row['domain_name']."\n";
		}

		//send the event
		$result = event_socket_request($fp, $cmd);
		if (isset($debug)) {
			print_r($result, false);
		}

	}

/*
* * * * * cd /var/www/fusionpbx && php /var/www/fusionpbx/app/call_forward/resources/jobs/dnd.php
*/

?>
