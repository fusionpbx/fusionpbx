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

	private $timers;

	/**
	 * Array of topics and their callbacks
	 * @var array
	 */
	protected $topics;

	/**
	 * Array of listeners
	 * Listener is an array of socket and callback used to listen for events on the socket. When a listener is added,
	 * the socket is added to the array of listeners. When the socket is closed, the listener is removed from the
	 * array of listeners. When an event is received on the respective socket, the provided callback is called.
	 * @var array
	 */
	protected $listeners;

	protected static function display_version(): void {
		echo "System Dashboard Service 1.0\n";
	}

	/**
	 * Set a timer to trigger the defined function every $seconds. To stop the timer, set the value to null
	 * @param int $seconds
	 * @return void
	 * @see on_timer
	 */
	protected function set_timer(int $seconds, callable $callable): void {
		$this->timers[] = ['expire_time' => time() + $seconds, 'callable' => $callable];
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

	/**
	 * Add a socket listener
	 *
	 * @param $socket
	 * @param callable $callback
	 * @return void
	 */
	protected function add_listener($socket, callable $callback): void {
		$this->listeners[] = [$socket, $callback];
	}

	public function run(): int {
		// set the timers property as an array
		$this->timers = [];

		// Set the listeners property as an array
		$this->listeners = [];

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
			// Get the array of sockets to read from
			$listeners = array_column($this->listeners, 0);

			// reconnect to websocket server
			if ($this->ws_client === null || !$this->ws_client->is_connected()) {
				// reconnect failed
				if (!$this->connect_to_ws_server()) {
					if (!$suppress_ws_message) $this->error("Unable to connect to websocket server.");
					$suppress_ws_message = true;
				}
			}

			if ($this->ws_client !== null && $this->ws_client->is_connected()) {
				// Combine the websocket client and the listeners into a single array
				$read = array_merge($listeners, [$this->ws_client->socket()]);
				// Reset the suppress message flag
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
						// Other listeners
						foreach ($this->listeners as $listener) {
							if ($resource === $listener[0]) {
								// Call the callback function provided by the add_listener function
								call_user_func($listener[1]);
								continue;
							}
						}
					}
				}
			}

			// Timers can be set by child classes
			if (!empty($this->timers)) {
				// Check all timers
				foreach($this->timers as $key => $array) {
					// Check if the timer should be run
					if (time() >= $array['expire_time']) {
						// Get the callback function
						$callable = $array['callable'];
						// Call the callback and see if it returns a value for the next timer
						$next_timer = call_user_func($callable);
						if (is_numeric($next_timer)) {
							// Set the timer again when requested by called function returning a value
							$this->set_timer($next_timer, $callable);
						}
						// Remove the expired timer from tracking list
						unset($this->timers[$key]);
					}
				}
			}
		}
		return 0;
	}

	/**
	 * Connects to the web socket server using a websocket_client object
	 * @return bool True if connected and False if not able to connect
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
	 * @param string $topic
	 * @param callable $callable
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
