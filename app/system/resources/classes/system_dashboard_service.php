<?php

class system_dashboard_service extends base_websocket_system_service {

	const PERMISSIONS = [
		'system_view_cpu',
		'system_view_backup',
		'system_view_database',
		'system_view_hdd',
		'system_view_info',
		'system_view_memcache',
		'system_view_ram',
		'system_view_support',
		'system_view_network',
	];

	const CPU_STATUS_TOPIC = 'cpu_status';
	const NETWORK_STATUS_TOPIC = "network_status";

	/**
	 *
	 * @var system_information $system_information
	 */
	protected static $system_information;

	/**
	 * Settings object
	 * @var settings
	 */
	private $settings;

	/**
	 * Integer representing the number of seconds to broadcast the CPU usage
	 * @var int
	 */
	private $cpu_status_refresh_interval;

	private $network_status_refresh_interval;
	private $network_interface;

	protected function reload_settings(): void {
		static::set_system_information();

		// re-read the config file to get any possible changes
		parent::$config->read();

		// re-connect to the websocket server if required
		$this->connect_to_ws_server();

		// Connect to the database
		$database = new database(['config' => parent::$config]);

		// get the settings using global defaults
		$this->settings = new settings(['database' => $database]);

		// get the cpu interval
		$this->cpu_status_refresh_interval = $this->settings->get('dashboard', 'cpu_status_refresh_interval', 3);

		// get the network interval
		$this->network_status_refresh_interval = $this->settings->get('dashboard', 'network_status_refresh_interval', 3);

		// get the network card to watch
		$this->network_interface = $this->settings->get('system', 'network_interface', 'eno1');
	}

	/**
	 * @override base_websocket_system_service
	 * @return void
	 */
	protected function on_timer(): void {
		// Send the CPU status
		$this->on_cpu_status();

		// Send the network average
		$this->on_network_status();

		// Reset the timer
		$this->set_timer($this->cpu_status_refresh_interval);
	}

	/**
	 * Executes once
	 * @return void
	 */
	protected function register_topics(): void {

		// get the settings from the global defaults
		$this->reload_settings();

		// Create a system information object that can tell us the cpu regardless of OS
		self::$system_information = system_information::new();

		// Register the call back to respond to cpu_status requests
		$this->on_topic(self::CPU_STATUS_TOPIC, [$this, 'on_cpu_status']);

		// Register the call back to respond to network_status requests
		$this->on_topic(self::NETWORK_STATUS_TOPIC, [$this, 'on_cpu_status']);

		// Set a timer
		$this->set_timer($this->cpu_status_refresh_interval);

		// Notify the user of the interval
		$this->info("Broadcasting CPU Status every {$this->cpu_status_refresh_interval}s");
		$this->info("Broadcasting Network Status every {$this->network_status_refresh_interval}s");
	}

	public function on_network_status($message = null): void {
		// Get RX (receive) and TX (transmit) bps
		$network_rates = self::$system_information->get_network_speed($this->network_interface);

		// Prepare a response
		$response = new websocket_message();
		$response
			->payload([self::NETWORK_STATUS_TOPIC => $network_rates])
			->service_name(self::get_service_name())
			->topic(self::NETWORK_STATUS_TOPIC)
		;
		if ($message !== null && $message instanceof websocket_message) {
			$this->debug("Responding to message request id: ".$message->id());
			$response->id($message->id());
		}

		// Log for debugging
		$this->debug(sprintf(
			"Broadcasting Network interface %s of RX %d bps, TX %d bps",
			$this->network_interface,
			$network_rates['rx_bps'],
			$network_rates['tx_bps']
		));

		$show_disconnect_message = true;
		try {
			// Send the broadcast
			$this->respond($response);
		} catch (\socket_disconnected_exception $sde) {
			// wait until we connect again
			while (!$this->connect_to_ws_server()) {
				if ($show_disconnect_message) {
					$this->warn("Websocket server disconnected");
					$show_disconnect_message = false;
				}
				sleep(1);
			}
			$this->warn("Websocket server connected");
		}
	}

	public function on_network_interface_select($message = null): void {
		if ($message !== null && $message instanceof websocket_message) {
			$payload = $message->payload();
			if (!empty($payload['network_interface'])) {
				$this->network_interface = ['network_interface'];
			}
		}
	}

	public function on_cpu_status($message = null): void {
		// Get total and per-core CPU usage
		$cpu_percent_total = self::$system_information->get_cpu_percent();
		$cpu_percent_per_core = self::$system_information->get_cpu_percent_per_core();

		// Prepare response
		$response = new websocket_message();
		$response
			->payload([
				self::CPU_STATUS_TOPIC => [
					'total' => $cpu_percent_total,
					'per_core' => array_values($cpu_percent_per_core)
				]
			])
			->service_name(self::get_service_name())
			->topic(self::CPU_STATUS_TOPIC);

		// Include message ID if responding to a request
		if ($message !== null && $message instanceof websocket_message) {
			$response->id($message->id());
		}

		// Log for debugging
		$this->debug(sprintf(
			"Broadcasting CPU total %.2f%% with %d cores",
			$cpu_percent_total,
			count($cpu_percent_per_core)
		));

		$show_disconnect_message = true;
		try {
			// Send the broadcast
			$this->respond($response);
		} catch (\socket_disconnected_exception $sde) {
			// wait until we connect again
			while (!$this->connect_to_ws_server()) {
				if ($show_disconnect_message) {
					$this->warn("Websocket server disconnected");
					$show_disconnect_message = false;
				}
				sleep(1);
			}
			$this->warn("Websocket server connected");
		}
	}



	public static function get_service_name(): string {
		return "dashboard.system.information";
	}

	public static function create_filter_chain_for(subscriber $subscriber): ?filter {
		// Get the subscriber permissions
		$permissions = $subscriber->get_permissions();

		// Create a filter for broadcaster => permission
		$permission_filter = new permission_filter([self::get_service_name() => 'system_view_cpu', self::get_service_name() => 'system_view_network']);

		// Match them to create a filter
		foreach (self::PERMISSIONS as $permission) {
			if (in_array($permission, $permissions)) {
				$permission_filter->add_permission($permission);
			}
		}

		$filter = filter_chain::and_link([$permission_filter]);

		// Return the filter with user permissions to ensure they can't receive information they shouldn't
		return $filter;
	}

	public static function set_system_information(): void {
		self::$system_information = system_information::new();
	}
}
