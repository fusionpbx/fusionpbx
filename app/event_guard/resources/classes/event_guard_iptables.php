<?php

 /**
 * event_guard_iptables class
 *
 */
class event_guard_iptables implements event_guard_interface {

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
	 * firewall_path string
	 * @var string
	 */
	private $firewall_path;

	/**
	 * chains array
	 * @var array
	 */
	private $chains;

	/**
	 * called when the object is created
	 */
	public function __construct(settings $settings) {
		// Save the settings object
		$this->settings = $settings;

		// Set the database object from the settings object
		$this->database = $settings->database();

		// Set firewall path
		$this->firewall_path = trim(shell_exec('command -v iptables'));

		// Set grep path
		$this->grep_path = trim(shell_exec('command -v grep'));

		// Create a chain array
		$this->chains[] = 'sip-auth-ip';
		$this->chains[] = 'sip-auth-fail';

		// Add the chains to active iptables
		foreach ($this->chains as $chain) {
			shell_exec($this->firewall_path.' --new ' . $chain . ' >/dev/null 2>&1 &');
			shell_exec($this->firewall_path.' -I INPUT -j '.$chain . ' >/dev/null 2>&1 &');
		}
	}

	/**
	 * Execute a block command for iptables
	 *
	 * @param string $ip_address The IP address to block
	 * @param string $filter     The filter name for nftables, iptables or pf
	 * @param array  $event      The event data containing 'to-user' and 'to-host'
	 *
	 * @return boolean True if the block command was executed successfully, false otherwise
	 */
	public function block_add(string $ip_address, string $filter) : bool {

		// Invalid ip address
		if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
			return false;
		}

		// Run the block command for iptables
		// Example: iptables -I INPUT -s 127.0.0.1 -j DROP
		$command = $this->firewall_path . ' -I ' . escapeshellarg($filter) . ' -s ' . $ip_address . ' -j DROP';
		$result = shell_exec($command);
		if (!empty($result)) {
			return false;
		}

		// Return success
		return true;
	}

	/**
	 * Unblock a specified IP address from a firewall.
	 *
	 * @param string $ip_address The IP address to unblock.
	 * @param string $filter     The filter name used in the firewall configuration.
	 *
	 * @return bool True if the IP address was successfully unblocked, false otherwise.
	 */
	public function block_delete(string $ip_address, string $filter) : bool {
		// Invalid IP address
		if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
			return false;
		}

		// Remove from all chains or a specific one
		if ($filter == 'all') {
			$chains = $this->chains;
		}
		else {
			$chains[] = $filter;
		}

		//remove the IP address from each chain
		foreach($chains as $chain) {
			$i = 0;
			while (true) {
				// Remove the blocked IP address
				$command = $this->firewall_path . ' -D ' . escapeshellarg($chain) . ' -s ' . $ip_address . ' -j DROP';
				$descriptors = [
					0 => ['pipe', 'r'],  // stdin
					1 => ['pipe', 'w'],  // stdout
					2 => ['pipe', 'w'],  // stderr
				];
				$process = proc_open($command, $descriptors, $pipes);
				if (is_resource($process)) {
					$stdout = stream_get_contents($pipes[1]);
					$stderr = stream_get_contents($pipes[2]);
					$exit_code = proc_close($process);

					if ($exit_code !== 0 && strpos($stderr, "Bad rule") !== false) {
						echo "exiting the loop\n";
						break;
					}
				}

				//added as a failsafe
				if ($i > 1000) {
					break;
				}

				//increment the iterator
				$i++;
			}
		}
		
		// Send information to the user
		echo "Unblock address " . $ip_address . " line " . $line_number . " command " . $command . " result " . $result . "\n";

		// Return success
		return true;
	}

	/**
	 * Check if an IP address is blocked in the configured firewall.
	 *
	 * @param string $ip_address The IP address to check
	 *
	 * @return bool True if the address is blocked, False otherwise
	 */
	public function block_exists(string $ip_address, string $filter) : bool {
		// Invalid IP address
		if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
			return false;
		}

		// Determine whether to return true or false
		// Check to see if the address is blocked
		$command = $this->firewall_path.' -L -n --line-numbers | grep '.$ip_address;
		$result = shell_exec($command);
		if (!empty($result) && strlen($result) > 3) {
			return true;
		}

		// Return the result
		return false;
	}
}
