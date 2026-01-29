<?php

 /**
 * event_guard_pf class
 *
 */
class event_guard_pf implements event_guard_interface {

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
		$this->firewall_path = trim(shell_exec('command -v pfctl'));
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

		// Run the block command for pf
		// Example: pfctl -t sip-auth-ip -T add 127.0.0.5
		$command = $this->firewall_path.' -t '.$filter.' -T add '.$ip_address;
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
		// Example: pfctl -t sip-auth-ip -T delete 127.0.0.5
		$command = $this->firewall_path.' -t '.$filter.' -T delete '.$ip_address;
		$result = shell_exec($command);
		if (!empty($result)) {
			return false;
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
		$command = $this->firewall_path.' -t ".$filter." -Ts | grep '.$ip_address;
		$result = shell_exec($command);
		if (!empty($result) && strlen($result) > 3) {
			return true;
		}

		// Return the result
		return false;
	}
}