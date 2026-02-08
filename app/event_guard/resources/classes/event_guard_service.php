<?php

/**
 * Description goes here for event_guard service
 */
class event_guard_service extends service {

	/**
	 * database object
	 * @var database
	 */
	private $database;

	/**
	 * settings object
	 * @var settings
	 */
	private $settings;

	/**
	 * hostname variable
	 * @var string
	 */
	private $hostname;

	/**
	 * firewall object
	 * @var event_guard_interface
	 */
	private $firewall;

	/**
	 * socket object
	 * @var event_socket
	 */
	private $socket;

	/**
	 * Reloads settings from database, config file and websocket server.
	 *
	 * @return void
	 */
	protected function reload_settings(): void {
		// Re-read the config file to get any possible changes
		parent::$config->read();

		// Connect to the database
		$this->database = new database(['config' => parent::$config]);

		// Get the settings using global defaults
		$this->settings = new settings(['database' => $this->database]);

		// Set the php operating system
		$php_os = strtolower(PHP_OS);

		// Set the firewall name
		if ($php_os == 'freebsd') {
			$firewall_name = $this->settings->get('system','firewall_name', 'pf');
		}
		if ($php_os == 'linux') {
			$firewall_name = $this->settings->get('system','firewall_name', 'iptables');
		}
		if (empty($firewall_name)) {
			throw new Exception("No firewall name specified in settings");
		}

		// Get the settings using global defaults
		$class_name = 'event_guard_'.$firewall_name;
		$this->firewall = new $class_name($this->settings);
		if (!($this->firewall instanceof event_guard_interface)) {
			throw new Exception("Must be an event_guard_interface firewall");
		}

		// Get the hostname
		$this->hostname = gethostname();

		// Connect to event socket
		$this->socket = new event_socket;
		if ($this->socket->connect()) {
			// Loop through the switch events
			$cmd = "event json ALL";
			$result = $this->socket->request($cmd);
			$this->debug('subscribe to ALL events '. print_r($result, true));

			// Filter for specific events
			$cmd = "filter Event-Name CUSTOM";
			$result = $this->socket->request($cmd);
			$this->debug('subscribe to CUSTOM events '. print_r($result, true));
		}
		else {
			$this->warning('Unable to connect to event socket');
		}
	}

	public function run(): int {
		// Reload the settings
		$this->reload_settings();

		// Service work is handled here
		while ($this->running) {

			// Initialize the array for switch events
			$json_array = [];

			// Make sure the database connection is available
			while (!$this->database->is_connected()) {
				// Connect to the database
				$this->database->connect();

				// Reload settings after connection to the database
				$this->settings = new settings(['database' => $this->database]);

				// Sleep for a moment
				sleep(1);
			}

			// Reconnect to event socket
			if (!$this->socket->connected()) {
				$this->warning('Not connected to even socket');
				if ($this->socket->connect()) {
					// Define the events
					$switch_events = [
						['Event-Subclass' => 'sofia::pre_register'],
						['Event-Subclass' => 'sofia::register_failure'],
						['Event-Subclass' => 'event_guard:unblock']
					];

					// Add the event filters
					$cmd = "event json ALL";
					$result = $this->socket->request($cmd);
					$this->info('subscribe to ALL events '. print_r($result, true));
					foreach($switch_events as $event_key => $event_value) {
						$cmd = "filter ".$event_key." ".$event_value;
						$result = $this->socket->request($cmd);
						$this->info('subscribe to CUSTOM events '. print_r($result, true));
					}
					$this->info('Re-connected to event socket');
				}
				else {
					// Unable to connect to event socket
					$this->warning('Unable to connect to event socket');

					// Sleep and then attempt to reconnect
					sleep(1);
					continue;
				}
			}

			// Read the socket
			$json_response = $this->socket->read_event();

			// Decode the response
			if (isset($json_response) && $json_response != '') {
				$json_array = json_decode($json_response['$'], true);
				unset($json_response);
			}

			// Debug the event array
			$this->debug('Event array '. print_r($json_array, true));

			// Registration failed - block IP address unless they are registered
			if (is_array($json_array) && $json_array['Event-Subclass'] == 'sofia::register_failure') {
				//not registered so block the address
				if (!$this->allow_access($json_array['network-ip'])) {
					$this->block_add($json_array['network-ip'], 'sip-auth-fail', $json_array);
				}
			}

			// Sendevent CUSTOM event_guard:unblock
			if (is_array($json_array) && $json_array['Event-Subclass'] == 'event_guard:unblock') {
				//check the database for pending requests
				$sql = "select event_guard_log_uuid, log_date, filter, ip_address, extension, user_agent ";
				$sql .= "from v_event_guard_logs ";
				$sql .= "where log_status = 'pending' ";
				$sql .= "and hostname = :hostname ";
				//$this->debug($sql." ".$this->hostname);
				$parameters['hostname'] = $this->hostname;
				$event_guard_logs = $this->database->select($sql, $parameters, 'all');
				if (is_array($event_guard_logs)) {
					$x = 0;
					foreach($event_guard_logs as $row) {
						//unblock the ip address
						$this->block_delete($row['ip_address'], $row['filter']);

						//debug info
						$this->info("unblocked: [ip_address: ".$row['ip_address'].", filter: ".$row['filter'].", to-user: ".$row['extension'].", to-host: ".$row['hostname'].", line: ".__line__);

						//log the blocked ip address to the database
						$array['event_guard_logs'][$x]['event_guard_log_uuid'] = $row['event_guard_log_uuid'];
						$array['event_guard_logs'][$x]['log_date'] = 'now()';
						$array['event_guard_logs'][$x]['log_status'] = 'unblocked';
						$x++;
					}
					if (is_array($array)) {
						$p = permissions::new();
						$p->add('event_guard_log_edit', 'temp');
						$this->database->save($array, false);
						$p->delete('event_guard_log_edit', 'temp');
						unset($array);
					}
				}
			}

			// Registration to the IP address
			if (is_array($json_array) && $json_array['Event-Subclass'] == 'sofia::pre_register') {
				if (isset($json_array['to-host'])) {
					$is_valid_ip = filter_var($json_array['to-host'], FILTER_VALIDATE_IP);
					if ($is_valid_ip) {
						//if not registered block the address
						if (!$this->allow_access($json_array['network-ip'])) {
							$this->block_add($json_array['network-ip'], 'sip-auth-ip', $json_array);
						}

						//debug info
						$this->debug("network-ip ".$json_array['network-ip'].", to-host ".$json_array['to-host']);
					}
				}
			}

			// Debug information
			//if (($json_array['Event-Subclass'] == 'sofia::register_failure' || $json_array['Event-Subclass'] == 'sofia::pre_register')) {
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

			// Sleep for 10 ms
			//usleep(10000);
		}
		return 0;
	}

	protected static function display_version(): void {
		echo "1.1\n";
	}

	protected static function set_command_options() {

	}

	/**
	 * Execute a block command for nftables, iptables or pf based on the firewall type.
	 *
	 * @param string $ip_address The IP address to block
	 * @param string $filter     The filter name for nftables, iptables or pf
	 * @param array  $event      The event data containing 'to-user' and 'to-host'
	 *
	 * @return boolean True if the block command was executed successfully, false otherwise
	 */
	public function block_add(string $ip_address, string $filter, array $event) : bool {
		//invalid ip address
		if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
			return false;
		}

		//block the IP address
		$result = $this->firewall->block_add($ip_address, $filter);
		if ($result) {
			//log the blocked ip address to the log
			$this->warning("blocked: [ip_address: ".$ip_address.", filter: ".$filter.", to-user: ".$event['to-user'].", to-host: ".$event['to-host'].", line: ".__line__."]");

			//log the blocked ip address to the database
			$array = [];
			$array['event_guard_logs'][0]['event_guard_log_uuid'] = uuid();
			$array['event_guard_logs'][0]['hostname'] = gethostname();
			$array['event_guard_logs'][0]['log_date'] = 'now()';
			$array['event_guard_logs'][0]['filter'] = $filter;
			$array['event_guard_logs'][0]['ip_address'] = $ip_address;
			$array['event_guard_logs'][0]['extension'] = $event['to-user'].'@'.$event['to-host'];
			$array['event_guard_logs'][0]['user_agent'] = $event['user-agent'];
			$array['event_guard_logs'][0]['log_status'] = 'blocked';
			$p = permissions::new();
			$p->add('event_guard_log_add', 'temp');
			$this->database->save($array, false);
			$p->delete('event_guard_log_add', 'temp');

			//send debug information to the console
			$this->info("blocked address " . $ip_address . ", line " . __line__);
		}

		//return the result
		return $result;
	}

	public function block_delete(string $ip_address, string $filter) : bool {
		//invalid ip address
		if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
			return false;
		}

		//unblock the IP address
		$result = $this->firewall->block_delete($ip_address, $filter);
		if ($result) {
			//send debug information to the console
			$this->info("Unblock address " . $ip_address . ", line " . __line__);
		}

		//return the result
		return $result;
	}

	public function block_exists(string $ip_address, string $filter) : bool {
		//invalid ip address
		if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
			return false;
		}

		//check if the address is blocked
		$result = $this->firewall->block_exists($ip_address, $filter);

		//send debug information to the console
		$this->debug("Address Exists " . $ip_address . ", line " . __line__);

		//return the result
		return $result;
	}

	/**
	 * Determine if access is allowed for a given IP address.
	 *
	 * This method checks the IP address is inside the cache, user logs, event guard logs, access controls,
	 * and registration to determine if access should be allowed. If the IP address is found
	 * in the access control list, user logs with result success, or valid registrations
	 * is found then the address is automatically allowed.
	 *
	 * @param string $ip_address The IP address to check for access.
	 *
	 * @return boolean True if access is allowed, false otherwise.
	 */
	private function allow_access($ip_address) {

		//invalid ip address
		if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
			return false;
		}

		//check the cache to see if the address is allowed
		$cache = new cache;
		if ($cache->get("switch:allowed:".$ip_address) === 'true') {
			//debug info
			$this->debug("address: ".$ip_address." allowed by: cache");

			//return boolean true
			return true;
		}

		//allow access for addresses with authentication status success
		if ($this->allow_user_log_success($ip_address)) {
			//save address to the cache as allowed
			$cache->set("switch:allowed:".$ip_address, 'true');

			//debug info
			$this->debug("address: ".$ip_address." allowed by: user logs");

			//return boolean true
			return true;
		}

		//allow access for addresses that have been unblocked
		/*
		if (event_guard_log_allowed($ip_address)) {
			//save address to the cache as allowed
			$cache->set("switch:allowed:".$ip_address, 'true');

			//debug info
			$this->debug("address: ".$ip_address." allowed by: unblocked");

			//return boolean true
			return true;
		}
		*/

		//allow access if the cidr address is allowed
		if ($this->allow_access_control($ip_address)) {
			//save address to the cache as allowed
			$cache->set("switch:allowed:".$ip_address, 'true');

			//debug info
			$this->debug("address: ".$ip_address." allowed by: access controls");

			//return boolean true
			return true;
		}

		//allow if there is a registration from the same IP address
		if ($this->allow_registered($ip_address)) {
			//save address to the cache as allowed
			$cache->set("switch:allowed:".$ip_address, 'true');

			//debug info
			$this->debug("address: ".$ip_address." allowed by: registration");

			//return boolean true
			return true;
		}

		//return
		return false;
	}

	/**
	 * Checks if the given IP address is authorized by any access control node.
	 *
	 * @param string $ip_address The IP address to check for authorization.
	 *
	 * @return bool True if the IP address is authorized, false otherwise.
	 */
	private function allow_access_control($ip_address) {

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
		$allowed_nodes = $this->database->select($sql, $parameters, 'all');

		//default authorized to false
		$allowed = false;

		//use the ip address to get the authorized nodes
		if (is_array($allowed_nodes)) {
			foreach($allowed_nodes as $row) {
				if (check_cidr($row['node_cidr'], $ip_address)) {
					//debug info
					//	print_r($row);
					//	$this->debug("Authorized: ".$ip_address);

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

	/**
	 * Determines if a user's IP address is allowed based on their login history.
	 *
	 * @param string $ip_address The IP address to check for access.
	 *
	 * @return bool True if the IP address is allowed, false otherwise.
	 */
	private function allow_user_log_success($ip_address) {

		//invalid ip address
		if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
			return false;
		}

		//check to see if the address was authenticated successfully
		$sql = "select count(user_log_uuid) ";
		$sql .= "from v_user_logs ";
		$sql .= "where remote_address = :remote_address ";
		$sql .= "and result = 'success' ";
		$sql .= "and timestamp > NOW() - INTERVAL '8 days' ";
		$parameters['remote_address'] = $ip_address;
		$user_log_count = $this->database->select($sql, $parameters, 'column');

		//debug info
		$this->debug("address ".$ip_address." count ".$user_log_count);

		//default authorized to false
		$allowed = false;

		//use the ip address to get the authorized nodes
		if (!empty($user_log_count) && $user_log_count > 0) {
			$allowed = true;
		}

		//return
		return $allowed;
	}

	/**
	 * Checks if the given IP address is registered on the network.
	 *
	 * @param string $ip_address The IP address to check for registration.
	 *
	 * @return bool True if the IP address is registered, false otherwise.
	 */
	private function allow_registered($ip_address) {
		//invalid ip address
		if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
			return false;
		}

		$registered = false;
		$command = "fs_cli -x 'show registrations as json' ";
		$result = shell_exec($command);
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
}
