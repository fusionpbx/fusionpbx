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
		$document_root = str_replace("\\", "/", $_SERVER["PHP_SELF"]);
		preg_match("/^(.*)\/app\/.*$/", $document_root, $matches);
		$document_root = $matches[1];
		set_include_path($document_root);
		$_SERVER["DOCUMENT_ROOT"] = $document_root;
		require_once "resources/require.php";
	}
	else {
		//only allow running this from command line
		exit;
		include "root.php";
		require_once "resources/require.php";
		require_once "resources/pdo.php";
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
				$command = "iptables --list INPUT | grep ".$chain." | awk '{print \$1}' | sed ':a;N;\$!ba;s/\\n/,/g' ";
				//if ($debug) { echo $command."\n"; }
				$response = shell($command);
				if (!in_array($chain, explode(",", $response))) {
					echo "Add iptables ".$chain." chain\n";
					system('iptables --new '.$chain);
					system('iptables -I INPUT -j '.$chain);
					echo "\n";
				}
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

//loop through the switch events
	$cmd = "event json ALL";
	$result = $socket->request($cmd);
	while (true) {

		//reconnect to event socket
		if (!$socket) {
			echo "Not connected to even socket\n";
			if ($socket->connect($event_socket_ip_address, $event_socket_port, $event_socket_password)) {
				echo "Re-connected to event socket\n";
			}	
			else {
				echo "Unable to connect to event socket\n";
				break;
			}
		}

		//read the socket
		$response = $socket->read_event();

		//decode the response
		if (isset($response) && $response != '') {
			$array = json_decode($response['$'], true);
			unset($response);
		}

		//registration failed - block IP address unless they are registered, 
		if (is_array($array) && $array['Event-Subclass'] == 'sofia::register_failure') {
			//not registered so block the address
			if (!access_allowed($array['network-ip'])) {
				block($array['network-ip'], 'sip-auth-fail', $array);
			}
		}

		//registration to the IP address
		if (is_array($array) && $array['Event-Subclass'] == 'sofia::pre_register') {
			if (isset($array['to-host'])) {
				$is_valid_ip = filter_var($array['to-host'], FILTER_VALIDATE_IP);
				if ($is_valid_ip) {
					//if not registered block the address
					if (!access_allowed($array['network-ip'])) {
						block($array['network-ip'], 'sip-auth-ip', $array);
					}

					//debug info
					if ($debug) {
						echo "network-ip ".$array['network-ip']."\n";
						echo "to-host ".$array['to-host']."\n";
						echo "\n";
					}
				}
			}
		}

		//unset the array
		if (is_array($array)) {
			unset($array);
		}

		//debug information
		if ($debug && ($array['Event-Subclass'] == 'sofia::register_failure' || $array['Event-Subclass'] == 'sofia::pre_register')) {

			echo "\n";
			print_r($array);

			//echo "event_name: ".$array['Event-Name']."\n";
			//echo "event_type: ".$array['event_type']."\n";
			//echo "event_subclass: ".$array['Event-Subclass']."\n";
			//echo "status: ".$array['status']."\n";
			//echo "network_ip: ".$array['network-ip']."\n";
			//echo "channel_state: ".$array['Channel-State']."\n";
			//echo "channel_call_state: ".$array['Channel-Call-State']."\n";
			//echo "call_direction: ".$array['Call-Direction']."\n";
			//echo "channel_call_uuid: ".$array['Channel-Call-UUID']."\n";
			//echo "answer_state: ".$array['Answer-State']."\n";
			//echo "hangup_cause: ".$array['Hangup-Cause']."\n";
			//echo "to-host: $array['to-host']\n";
			//echo "\n";
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
	function unblock($ip_address, $chain) {
		//set global variables
		global $firewall;

		//invalid ip address
		if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
			return false;
		}

		//unblock the address
		if ($firewall == 'iptables') {
			$command = 'iptables -L -n --line-numbers | grep '.$ip_address;
			$result = shell($command);
			echo "\n". $command . " line ".__line__." result ".$result."\n";
			if (strlen($result) > 3) {
				$array = explode(' ', trim($result));
				$line_number = trim($array[0]);

				//$result = shell('iptables -D INPUT '.$line_number);
				$command = 'iptables -D '.$chain.' '.$line_number;
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
				echo "allowed by: cache\n";
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
				echo "allowed by: registration\n";
			}

			//return boolean true
			return true;
		}

		//allow access if the cidr address is allowed
		if (access_control_allowed($ip_address)) {
			//save address to the cache as allowed
			$cache->set("switch:allowed:".$ip_address, 'true');

			//debug info
			if ($debug) {
				echo "allowed by: access controls\n";
			}

			//return boolean true
			return true;
		}

		//return
		return false;
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
					if ($debug) {
						print_r($row);
						echo $ip_address."\n";
					}

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

?>
