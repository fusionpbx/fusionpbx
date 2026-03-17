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
	 * called when the object is created
	 */
	public function __construct(settings $settings) {
		// Save the settings object
		$this->settings = $settings;

		// Set the database object from the settings object
		$this->database = $settings->database();

		// Set firewall path
		$this->firewall_path = trim(shell_exec('command -v iptables'));

		// Create a chain array
		$chains[] = 'sip-auth-ip';
		$chains[] = 'sip-auth-fail';
		foreach ($chains as $chain) {
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
		$command = $this->firewall_path.' -I '.$filter.' -s '.$ip_address.' -j DROP';
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
		// Invalid ip address
		if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
			return false;
		}

		// Unblock the address
		$command = $this->firewall_path.' -L '.$filter.' -n --line-numbers | grep "'.$ip_address.' " | cut -d " " -f1';
		$line_number = trim(shell_exec($command));
		echo "\n". $command . " line ".__line__."\n";
		if (is_numeric($line_number)) {
			//$result = shell_exec('iptables -D INPUT '.$line_number);
			$command = $this->firewall_path.' -D '.$filter.' '.$line_number;
			$result = shell_exec($command);
			if (!empty($result)) {
				return false;
			}
			echo "Unblock address ".$ip_address ." line ".$line_number." command ".$command." result ".$result."\n";
		}

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