<?php
/*
	Copyright (C) 2022 Mark J Crane <markjcrane@fusionpbx.com>

	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:
	1. Redistributions of source code must retain the above copyright notice,
	   this list of conditions and the following disclaimer.
	2. Redistributions in binary form must reproduce the above copyright
	   notice, this list of conditions and the following disclaimer in the
	   documentation and/or other materials provided with the distribution.

	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
	POSSIBILITY OF SUCH DAMAGE.
*/

//check the permission
	if (defined('STDIN')) {
		//set the include path
		$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
		set_include_path(parse_ini_file($conf[0])['document.root']);

		//includes files
		require_once "resources/require.php";
	}
	else {
		//only allow running this from command line
		exit;
	}

//increase limits
	set_time_limit(0);
	ini_set('max_execution_time', 0);
	ini_set('memory_limit', '256M');

//save the arguments to variables
	$script_name = $argv[0];
	if (!empty($argv[1])) {
		parse_str($argv[1], $_GET);
	}

//set the variables
	if (isset($_GET['hostname'])) {
		$hostname = urldecode($_GET['hostname']);
	}
	if (isset($_GET['debug'])) {
		if (is_numeric($_GET['debug'])) {
			$debug_level = $_GET['debug'];
		}
		$debug = true;
	}

//get the hostname
	if (!isset($hostname)) {
		$hostname = gethostname();
	}

//set the php operating system
	$php_os = strtolower(PHP_OS);

//define the firewall command
	if ($php_os == 'freebsd') {
		$firewall = 'pf';
	}
	if ($php_os == 'linux') {
		$firewall = 'iptables';
	}

//add the iptables chains
	if ($firewall == 'iptables') {
		//create a chain array
		$chains[] = 'sip-auth-ip';
		$chains[] = 'sip-auth-fail';

		//loop through the chains
		if (is_array($chains)) {
			foreach ($chains as $chain) {
				iptables_chain_add($chain);
			}
		}
	}

//test a specific address
	//$ip_address = '10.7.0.253';
	//$result = access_allowed($ip_address);

//get the settings
	//$setting_name = $_SESSION['category']['subcategory']['text'];

//set the event socket variables
	$event_socket_ip_address = $_SESSION['event_socket_ip_address'];
	$event_socket_port = $_SESSION['event_socket_port'];
	$event_socket_password = $_SESSION['event_socket_password'];

//end the session
	session_destroy();

//connect to event socket
	$socket = new event_socket;
	if (!$socket->connect($event_socket_ip_address, $event_socket_port, $event_socket_password)) {
		echo "Unable to connect to event socket\n";
	}

//preset values
	//$interval_seconds = 30;
	//$previous_time = time() - $interval_seconds;

//loop through the switch events
	$cmd = "event json ALL";
	$result = $socket->request($cmd);
	if ($debug) { print_r($result); }

	//filter for specific events
	$cmd = "filter Event-Name CUSTOM";
	$result = $socket->request($cmd);
	if ($debug) { print_r($result); }

	while (true) {

		//check pending unblock requests
		/*
		if ((time() - $previous_time) > $interval_seconds) {
			//debug info
			if ($debug) {
				echo "time difference: ". (time() - $previous_time)."\n";
			}

			//update the time
			$previous_time = time();
		}
		*/

		//reconnect to event socket
		if (!$socket->connected()) {
			//echo "Not connected to even socket\n";
			if ($socket->connect($event_socket_ip_address, $event_socket_port, $event_socket_password)) {
				$cmd = "event json ALL";
				$result = $socket->request($cmd);
				if ($debug) { print_r($result); }

				$cmd = "filter Event-Name CUSTOM";
				$result = $socket->request($cmd);
				if ($debug) { print_r($result); }
				echo "Re-connected to event socket\n";
			}
			else {
				//unable to connect to event socket
				echo "Unable to connect to event socket\n";

				//sleep and then attempt to reconnect
				sleep(1);
				continue;
			}
		}

		//read the socket
		$json_response = $socket->read_event();

		//decode the response
		if (isset($json_response) && $json_response != '') {
			$json_array = json_decode($json_response['$'], true);
			unset($json_response);
		}

		//debug info
		//if ($debug) { 
		//	print_r($json_array);
		//}

		//registration failed - block IP address unless they are registered, 
		if (is_array($json_array) && $json_array['Event-Subclass'] == 'sofia::register_failure') {
			//not registered so block the address
			if (!access_allowed($json_array['network-ip'])) {
				block($json_array['network-ip'], 'sip-auth-fail', $json_array);
			}
		}

		//sendevent CUSTOM event_guard:unblock
		if (is_array($json_array) && $json_array['Event-Subclass'] == 'event_guard:unblock') {
			//check the database for pending requests
			$sql = "select event_guard_log_uuid, log_date, filter, ip_address, extension, user_agent ";
			$sql .= "from v_event_guard_logs ";
			$sql .= "where log_status = 'pending' ";
			$sql .= "and hostname = :hostname ";
			//if ($debug) { echo $sql." ".$hostname."\n"; }
			$parameters['hostname'] = $hostname;
			$database = new database;
			$event_guard_logs = $database->select($sql, $parameters, 'all');
			unset($database);
			if (is_array($event_guard_logs)) {
				foreach($event_guard_logs as $row) {
					//unblock the ip address
					unblock($row['ip_address'], $row['filter']);

					//log the blocked ip address to the syslog
					openlog("fusionpbx", LOG_PID | LOG_PERROR);
					syslog(LOG_WARNING, "fusionpbx: unblocked: [ip_address: ".$row['ip_address'].", filter: ".$row['filter'].", to-user: ".$row['extension'].", to-host: ".$row['hostname'].", line: ".__line__."]");
					closelog();

					//debug info
					if ($debug) {
						echo "unblocked: [ip_address: ".$row['ip_address'].", filter: ".$row['filter'].", to-user: ".$row['extension'].", to-host: ".$row['hostname'].", line: ".__line__."]\n";
					}

					//log the blocked ip address to the database
					$array['event_guard_logs'][$x]['event_guard_log_uuid'] = $row['event_guard_log_uuid'];
					$array['event_guard_logs'][$x]['log_date'] = 'now()';
					$array['event_guard_logs'][$x]['log_status'] = 'unblocked';
					$x++;
				}
				if (is_array($array)) {
					$p = new permissions;
					$p->add('event_guard_log_edit', 'temp');
					$database = new database;
					$database->app_name = 'event guard';
					$database->app_uuid = 'c5b86612-1514-40cb-8e2c-3f01a8f6f637';
					$database->save($array);
					//$message = $database->message;
					$p->delete('event_guard_log_edit', 'temp');
					unset($database, $array);
				}
			}
		}

		//registration to the IP address
		if (is_array($json_array) && $json_array['Event-Subclass'] == 'sofia::pre_register') {
			if (isset($json_array['to-host'])) {
				$is_valid_ip = filter_var($json_array['to-host'], FILTER_VALIDATE_IP);
				if ($is_valid_ip) {
					//if not registered block the address
					if (!access_allowed($json_array['network-ip'])) {
						block($json_array['network-ip'], 'sip-auth-ip', $json_array);
					}

					//debug info
					if ($debug) {
						echo "network-ip ".$json_array['network-ip']."\n";
						echo "to-host ".$json_array['to-host']."\n";
						echo "\n";
					}
				}
			}
		}

		//debug information
		//if ($debug && ($json_array['Event-Subclass'] == 'sofia::register_failure' || $json_array['Event-Subclass'] == 'sofia::pre_register')) {

			//echo "\n";
			//print_r($json_array);

			//echo "event_name: ".$json_array['Event-Name']."\n";
			//echo "event_type: ".$json_array['event_type']."\n";
			//echo "event_subclass: ".$json_array['Event-Subclass']."\n";
			//echo "status: ".$json_array['status']."\n";
			//echo "network_ip: ".$json_array['network-ip']."\n";
			//echo "channel_state: ".$json_array['Channel-State']."\n";
			//echo "channel_call_state: ".$json_array['Channel-Call-State']."\n";
			//echo "call_direction: ".$json_array['Call-Direction']."\n";
			//echo "channel_call_uuid: ".$json_array['Channel-Call-UUID']."\n";
			//echo "answer_state: ".$json_array['Answer-State']."\n";
			//echo "hangup_cause: ".$json_array['Hangup-Cause']."\n";
			//echo "to-host: $json_array['to-host']\n";
			//echo "\n";
		//}

		//unset the array
		if (is_array($json_array)) {
			unset($json_array);
		}

		//debug info
		if ($debug && $debug_level == '2') {
			//current memory
			$memory_usage = memory_get_usage();

			//peak memory
			$memory_peak = memory_get_peak_usage();
			echo "\n";
			echo 'Current memory: ' . round($memory_usage / 1024) . " KB\n";
			echo 'Peak memory: ' . round($memory_peak / 1024) . " KB\n\n";
			echo "\n";
		}

	}

//run command and capture standard output
	function shell($command) {
		ob_start();
		$result = system($command);
		ob_get_clean();
		return $result;
	}

//block an ip address
	function block($ip_address, $filter, $event) {
		//set global variables
		global $firewall;

		//invalid ip address
		if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
			return false;
		}

		//run the block command for iptables
		if ($firewall == 'iptables') {
			//example: iptables -I INPUT -s 127.0.0.1 -j DROP
			$command = 'iptables -I '.$filter.' -s '.$ip_address.' -j DROP';
			$result = shell($command);
		}

		//run the block command for pf
		if ($firewall == 'pf') {
			//example: pfctl -t sip-auth-ip -T add 127.0.0.5/32
			$command = 'pfctl -t '.$filter.' -T add '.$ip_address.'/32';
			$result = shell($command);
		}

		//log the blocked ip address to the syslog
		openlog("fusionpbx", LOG_PID | LOG_PERROR);
		syslog(LOG_WARNING, "fusionpbx: blocked: [ip_address: ".$ip_address.", filter: ".$filter.", to-user: ".$event['to-user'].", to-host: ".$event['to-host'].", line: ".__line__."]");
		closelog();

		//log the blocked ip address to the database
		$array['event_guard_logs'][0]['event_guard_log_uuid'] = uuid();
		$array['event_guard_logs'][0]['hostname'] = gethostname();
		$array['event_guard_logs'][0]['log_date'] = 'now()';
		$array['event_guard_logs'][0]['filter'] = $filter;
		$array['event_guard_logs'][0]['ip_address'] = $ip_address;
		$array['event_guard_logs'][0]['extension'] = $event['to-user'].'@'.$event['to-host'];
		$array['event_guard_logs'][0]['user_agent'] = $event['user-agent'];
		$array['event_guard_logs'][0]['log_status'] = 'blocked';
		$p = new permissions;
		$p->add('event_guard_log_add', 'temp');
		$database = new database;
		$database->app_name = 'event guard';
		$database->app_uuid = 'c5b86612-1514-40cb-8e2c-3f01a8f6f637';
		$database->save($array);
		$p->delete('event_guard_log_add', 'temp');
		unset($database, $array);

		//send debug information to the console
		if ($debug) {
			echo "blocked address ".$ip_address .", line ".__line__."\n";
		}

		//unset the array
		unset($event);
	}

//unblock the ip address
	function unblock($ip_address, $filter) {
		//set global variables
		global $firewall;

		//invalid ip address
		if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
			return false;
		}

		//unblock the address
		if ($firewall == 'iptables') {
			$command = 'iptables -L '.$filter.' -n --line-numbers | grep "'.$ip_address.' " | cut -d " " -f1';
			$line_number = trim(shell($command));
			echo "\n". $command . " line ".__line__." result ".$result."\n";
			if (is_numeric($line_number)) {
				//$result = shell('iptables -D INPUT '.$line_number);
				$command = 'iptables -D '.$filter.' '.$line_number;
				$result = shell($command);
				echo "Unblock address ".$ip_address ." line ".$line_number." command ".$command." result ".$result."\n";
			}
		}

		//unblock the address
		if ($firewall == 'pf') {
			//example: pfctl -t sip-auth-ip -T delete 127.0.0.5/32
			$command = 'pfctl -t '.$filter.' -T delete '.$ip_address.'/32';
			$result = shell($command);
		}

		//send debug information to the console
		if ($debug) {
			echo "Unblock address ".$ip_address ."\n";
		}
	}

//is the ip address blocked
	function is_blocked($ip_address) {
		//set global variables
		global $firewall;

		//invalid ip address
		if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
			return false;
		}

		//determine whether to return true or false
		if ($firewall == 'iptables') {
			//check to see if the address is blocked
			$command = 'iptables -L -n --line-numbers | grep '.$ip_address;
			$result = shell($command);
			if (strlen($result) > 3) {
				return true;
			}
		}
		elseif ($firewall == 'pf') {
			//check to see if the address is blocked
			$command = 'pfctl -t ".$filter." -Ts | grep '.$ip_address;
			$result = shell($command);
			if (strlen($result) > 3) {
				return true;
			}
		}
		else {
			return false;
		}
	}

//determine if the IP address has been allowed by the access control list node cidr
	function access_allowed($ip_address) {
		//define global variables
		global $debug;

		//invalid ip address
		if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
			return false;
		}

		//check the cache to see if the address is allowed
		$cache = new cache;
		if ($cache->get("switch:allowed:".$ip_address) === 'true') {
			//debug info
			if ($debug) {
				echo "address: ".$ip_address." allowed by: cache\n";
			}

			//return boolean true
			return true;
		}

		//allow access for addresses with authentication status success
		if (user_log_allowed($ip_address)) {
			//save address to the cache as allowed
			$cache->set("switch:allowed:".$ip_address, 'true');

			//debug info
			if ($debug) {
				echo "address: ".$ip_address." allowed by: user logs\n";
			}

			//return boolean true
			return true;
		}

		//allow access for addresses that have been unblocked
		/*
		if (event_guard_log_allowed($ip_address)) {
			//save address to the cache as allowed
			$cache->set("switch:allowed:".$ip_address, 'true');

			//debug info
			if ($debug) {
				echo "address: ".$ip_address." allowed by: unblocked\n";
			}

			//return boolean true
			return true;
		}
		*/

		//allow access if the cidr address is allowed
		if (access_control_allowed($ip_address)) {
			//save address to the cache as allowed
			$cache->set("switch:allowed:".$ip_address, 'true');

			//debug info
			if ($debug) {
				echo "address: ".$ip_address." allowed by: access controls\n";
			}

			//return boolean true
			return true;
		}

		//auto allow if there is a registration from the same IP address
		if (is_registered($ip_address)) {
			//save address to the cache as allowed
			$cache->set("switch:allowed:".$ip_address, 'true');

			//debug info
			if ($debug) {
				echo "address: ".$ip_address." allowed by: registration\n";
			}

			//return boolean true
			return true;
		}

		//return
		return false;
	}

//is the ip address registered
	function is_registered($ip_address) {
		//invalid ip address
		if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
			return false;
		}

		$registered = false;
		$command = "fs_cli -x 'show registrations as json' ";
		$result = shell($command);
		$array = json_decode($result, true);
		if (is_array($array['rows'])) {
			foreach ($array['rows'] as $row) {
				if ($row['network_ip'] == $ip_address) {
					$registered = true;
				}
			}
		}

		//return registered boolean
		return $registered;
	}

//determine if the IP address has been allowed by the access control list node cidr
	function access_control_allowed($ip_address) {

		//invalid ip address
		if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
			return false;
		}

		//get the access control allowed nodes
		$sql = "select access_control_node_uuid, access_control_uuid, node_cidr, node_description ";
		$sql .= "from v_access_control_nodes ";
		$sql .= "where node_type = 'allow' ";
		$sql .= "and length(node_cidr) > 0 ";
		$parameters = null;
		$database = new database;
		$allowed_nodes = $database->select($sql, $parameters, 'all');
		unset($database);

		//default authorized to false
		$allowed = false;

		//use the ip address to get the authorized nodes
		if (is_array($allowed_nodes)) {
			foreach($allowed_nodes as $row) {
				if (check_cidr($row['node_cidr'], $ip_address)) {
					//debug info
					//if ($debug) {
					//	print_r($row);
					//	echo $ip_address."\n";
					//}

					//set the allowed to true
					$allowed = true;

					//exit the loop
					break;
				}
			}
		}

		//return
		return $allowed;
	}

//determine if the IP address has been allowed by a successful authentication
	function user_log_allowed($ip_address) {

		//invalid ip address
		if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
			return false;
		}

		//check to see if the address was authenticated successfully
		$sql = "select count(user_log_uuid) ";
		$sql .= "from v_user_logs ";
		$sql .= "where remote_address = :remote_address ";
		$sql .= "and result = 'success' ";
		$parameters['remote_address'] = $ip_address;  
		$database = new database;
		$user_log_count = $database->select($sql, $parameters, 'column');
		unset($database);

		//debug info
		if ($debug) {
			echo "address ".$ip_address." count ".$user_log_count."\n";
		}

		//default authorized to false
		$allowed = false;

		//use the ip address to get the authorized nodes
		if ($user_log_count > 0) {
			$allowed = true;
		}

		//return
		return $allowed;
	}

//determine if the IP address has been unblocked in the event guard log
	function event_guard_log_allowed($ip_address) {

		//invalid ip address
		if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
			return false;
		}

		//get the access control allowed nodes
		$sql = "select count(event_guard_log_uuid) ";
		$sql .= "from v_event_guard_logs ";
		$sql .= "where ip_address = :ip_address ";
		$sql .= "and log_status = 'unblocked' ";
		$parameters['ip_address'] = $ip_address;  
		$database = new database;
		$user_log_count = $database->select($sql, $parameters, 'column');
		unset($database);

		//debug info
		if ($debug) {
			echo "address ".$ip_address." count ".$user_log_count."\n";
		}

		//default authorized to false
		$allowed = false;

		//use the ip address to get the authorized nodes
		if ($user_log_count > 0) {
			$allowed = true;
		}

		//return
		return $allowed;
	}

//add IP table chains
	function iptables_chain_add($chain) {
		//if the chain exists return true
		if (iptables_chain_exists($chain)) {
			echo "IPtables ".$chain." chain already exists\n";
			return true;
		}

		//log info to the console
		echo "Add iptables ".$chain." chain\n";

		//add the chain
		system('iptables --new '.$chain);
		system('iptables -I INPUT -j '.$chain);

		//check if the chain exists
		if (iptables_chain_exists($chain)) {
			return true;
		}
		else {
			sleep(1);
			iptables_chain_add($chain);
		}
	}

//check if the iptables chain exists
	function iptables_chain_exists($chain) {
		$command = "iptables --list INPUT --numeric | grep ".$chain." | awk '{print \$1}' | sed ':a;N;\$!ba;s/\\n/,/g' ";
		//if ($debug) { echo $command."\n"; }
		$response = shell($command);
		if (in_array($chain, explode(",", $response))) {
			return true;
		}
		else {
			return false;
		}
	}

?>
