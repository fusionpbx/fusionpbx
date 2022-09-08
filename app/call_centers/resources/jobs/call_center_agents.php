<?php

//check the permission
	if (defined('STDIN')) {
		$document_root = str_replace("\\", "/", $_SERVER["PHP_SELF"]);
		preg_match("/^(.*)\/app\/.*$/", $document_root, $matches);
		if (isset($matches[1])) {
			$document_root = $matches[1];
			set_include_path($document_root);
			$_SERVER["DOCUMENT_ROOT"] = $document_root;
		}
		require_once "resources/require.php";
	}
	else {
		exit;
	}

//increase limits
	set_time_limit(300);
	ini_set('max_execution_time',300); //5 minutes
	ini_set('memory_limit', '256M');

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

//get the agent list from event socket
	$switch_cmd = 'callcenter_config agent list';
	$event_socket_str = trim(event_socket_request($fp, 'api '.$switch_cmd));
	$agent_list = csv_to_named_array($event_socket_str, '|');

//get the agents from the database
	$sql = "select a.*, d.domain_name \n";
	$sql .= "from v_call_center_agents as a, v_domains as d \n";
	$sql .= "where a.domain_uuid = d.domain_uuid \n";
	$sql .= "order by agent_name asc \n";
	//echo $sql;
	$database = new database;
	$agents = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//view_array($results);
	foreach($agents as $row) {

		//update the agent status
		if (is_array($agent_list)) {
			foreach ($agent_list as $r) {
				if ($r['name'] == $row['call_center_agent_uuid']) {
					$agent_status = $r['status'];
				}
			}
		}

		//answer_state options: confirmed, early, and terminated
		if ($agent_status == 'Available') {
			$answer_state = 'confirmed';
		}
		else {
			$answer_state = 'terminated';
		}

		//build the event
		if ($fp) {
			$event = "sendevent PRESENCE_IN\n";
			$event .= "proto: agent\n";
			$event .= "from: ".$row['agent_name']."@".$row['domain_name']."\n";
			$event .= "login: ".$row['agent_name']."@".$row['domain_name']."\n";
			$event .= "status: Active (1 waiting)\n";
			$event .= "rpid: unknown\n";
			$event .= "event_type: presence\n";
			$event .= "alt_event_type: dialog\n";
			$event .= "event_count: 1\n";
			$event .= "unique-id: ".uuid()."\n";
			$event .= "Presence-Call-Direction: outbound\n";
			$event .= "answer-state: ".$answer_state."\n";
		}

		//send message to the console
		if (isset($debug)) {
			echo "\n";
			echo "[presence][call_center] agent+".$row['agent_name']."@".$row['domain_name']." agent_status ".$agent_status." answer_state ".$answer_state."\n";
		}

		//send the event
		$result = event_socket_request($fp, $event);
		if (isset($debug)) {
			print_r($result, false);
		}

	}

	//close event socket connection
	fclose($fp);

/*
* * * * * cd /var/www/fusionpbx && php /var/www/fusionpbx/app/call_centers/resources/jobs/call_center_agents.php
*/

?>
