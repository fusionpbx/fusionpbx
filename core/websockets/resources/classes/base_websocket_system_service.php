<?php

/**
 * Description of base_websocket_system_service 
 *
 * @author Tim Fry <tim@fusionpbx.com>
 */
abstract class base_websocket_system_service extends service implements websocket_service_interface {

	private static $websocket_port = null;
	private static $websocket_host = null;

	/**
	 * Sets a time to fire the on_timer function
	 * @var int|null
	 */
	protected $timer_expire_time = null;

	/**
	 * Websocket client
	 * @var websocket_client $ws_client
	 */
	protected $ws_client;

	//abstract protected function reload_settings(): void;

	protected static function display_version(): void {
		echo "System Dashboard Service 1.0\n";
	}

	/**
	 * Set a timer to trigger the on_timer function every $seconds. To stop the timer, set the value to null
	 * @param int $seconds
	 * @return void
	 * @see on_timer
	 */
	protected function set_timer(?int $seconds): void {
		if ($seconds !== null) $this->timer_expire_time = time() + $seconds;
		else $this->timer_expire_time = null;
	}

	/**
	 * When the set_timer is used to set a timer, this function will run. Override
	 * the function in the child class.
	 * @return void
	 * @see set_timer
	 */
	protected function on_timer(): void {
		return;
	}

	protected static function set_command_options() {
		parent::append_command_option(
			command_option::new()
				->description('Set the Port to connect to the websockets service')
				->short_option('w')
				->short_description('-w <port>')
				->long_option('websockets-port')
				->long_description('--websockets-port <port>')
				->callback('set_websockets_port')
		);
		parent::append_command_option(
			command_option::new()
				->description('Set the IP address for the websocket')
				->short_option('k')
				->short_description('-k <ip_addr>')
				->long_option('websockets-address')
				->long_description('--websockets-address <ip_addr>')
				->callback('set_websockets_host_address')
		);
	}

	protected static function set_websockets_port($port): void {
		self::$websocket_port = $port;
	}

	protected static function set_websockets_host_address($host): void {
		self::$websocket_host = $host;
	}

	public function run(): int {
		// re-read the config file to get any possible changes
		parent::$config->read();

		// re-connect to the websocket server
		$this->connect_to_ws_server();

		// Notify connected web server socket when we close
		register_shutdown_function(function ($ws_client) {
			if ($ws_client !== null)
				$ws_client->disconnect();
		}, $this->ws_client);

		$this->register_topics();

		// Register the authenticate request
		$this->on_topic('authenticate', [$this, 'on_authenticate']);

		// Track the WebSocket Server Error Message so it doesn't flood the system logs
		$suppress_ws_message = false;

		while ($this->running) {
			$read = [];
			// reconnect to websocket server
			if ($this->ws_client === null || !$this->ws_client->is_connected()) {
				// reconnect failed
				if (!$this->connect_to_ws_server()) {
					if (!$suppress_ws_message) $this->error("Unable to connect to websocket server.");
					$suppress_ws_message = true;
				}
			}

			if ($this->ws_client !== null && $this->ws_client->is_connected()) {
				$read[] = $this->ws_client->socket();
				$suppress_ws_message = false;
			}

			// Check if we have sockets to read
			if (!empty($read)) {
				$write = $except = [];
				// Wait for an event and timeout at 1/3 of a second so we can re-check all connections
				if (false === stream_select($read, $write, $except, 0, 333333)) {
					// severe error encountered so exit
					$this->running = false;
					// Exit with non-zero exit code
					return 1;
				}
				// stream_select will update $read so re-check it
				if (!empty($read)) {
					$this->debug("Received event");
					// Iterate over each socket event
					foreach ($read as $resource) {
						// Web socket event
						if ($resource === $this->ws_client->socket()) {
							$this->handle_websocket_event($this->ws_client);
							continue;
						}
					}
				}
			}

			// Timers can be set by child classes
			if ($this->timer_expire_time !== null && time() >= $this->timer_expire_time) {
				$this->on_timer();
			}
		}
		return 0;
	}

	protected function debug(string $message) {
		self::log($message, LOG_DEBUG);
	}

	protected function warn(string $message) {
		self::log($message, LOG_WARNING);
	}

	protected function error(string $message) {
		self::log($message, LOG_ERR);
	}

	protected function info(string $message) {
		self::log($message, LOG_INFO);
	}

	/**
	 * Connects to the web socket server using a websocket_client object
	 * @return bool
	 */
	protected function connect_to_ws_server(): bool {
		if ($this->ws_client !== null && $this->ws_client->is_connected()) return true;

		$host = self::$websocket_host ?? self::$config->get('websocket.host', '127.0.0.1');
		$port = self::$websocket_port ?? self::$config->get('websocket.port', 8080);
		try {
			// Create a websocket client
			$this->ws_client = new websocket_client("ws://$host:$port");

			// Block stream for handshake and authentication
			$this->ws_client->set_blocking(true);

			// Connect to web socket server
			$this->ws_client->connect();

			// Disable the stream blocking
			$this->ws_client->set_blocking(false);

			$this->debug(self::class . " RESOURCE ID: " . $this->ws_client->socket());
		} catch (\RuntimeException $re) {
			//unable to connect
			return false;
		}
		return true;
	}

	/**
	 * Handles the message from the web socket client and triggers the appropriate requested topic event
	 * @param resource $ws_client
	 * @return void
	 */
	private function handle_websocket_event() {
		// Read the JSON string
		$json_string = $this->ws_client->read();

		// Nothing to do
		if ($json_string === null) {
			$this->warn('Message received from Websocket is empty');
			return;
		}

		$this->debug("Received message on websocket: $json_string (" . strlen($json_string) . " bytes)");

		// Get the web socket message as an object
		$message = websocket_message::create_from_json_message($json_string);

		// Nothing to do
		if (empty($message->topic())) {
			$this->error("Message received does not have topic");
			return;
		}

		// Call the registered topic event
		$this->trigger_topic($message->topic, $message, $this->ws_client);
	}

	/**
	 * Call each of the registered events for the websocket topic that has arrived
	 * @param string $topic
	 * @param websocket_message $websocket_message
	 */
	private function trigger_topic(string $topic, websocket_message $websocket_message) {
		if (empty($topic) || empty($websocket_message)) {
			return;
		}

		if (!empty($this->topics[$topic])) {
			foreach ($this->topics[$topic] as $callback) {
				call_user_func($callback, $websocket_message);
			}
		}
	}

	protected function on_authenticate(websocket_message $websocket_message) {
		$this->info("Authenticating with websocket server");
		// Create a service token
		[$token_name, $token_hash] = websocket_client::create_service_token(active_calls_service::get_service_name(), static::class);

		// Request authentication as a service
		$this->ws_client->authenticate($token_name, $token_hash);
	}

	/**
	 * Allows the service to register a callback so when the topic arrives the callable is called
	 * @param type $topic
	 * @param type $callable
	 */
	protected function on_topic($topic, $callable) {
		if (!isset($this->topics[$topic])) {
			$this->topics[$topic] = [];
		}
		$this->topics[$topic][] = $callable;
	}

	protected function respond(websocket_message $websocket_message): void {
		websocket_client::send($this->ws_client->socket(), $websocket_message);
	}

	abstract protected function register_topics(): void;
}
