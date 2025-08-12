<?php

/*
 * FusionPBX
 * Version: MPL 1.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is FusionPBX
 *
 * The Initial Developer of the Original Code is
 * Mark J Crane <markjcrane@fusionpbx.com>
 * Portions created by the Initial Developer are Copyright (C) 2008-2025
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 * Mark J Crane <markjcrane@fusionpbx.com>
 * Tim Fry <tim@fusionpbx.com>
 */

/**
 * Description of websocket_service
 *
 * @author Tim Fry <tim@fusionpbx.com>
 */
class websocket_service extends service {

	/**
	 * Address to bind to. (Default 8080)
	 * @var string
	 */
	protected $ip;

	/**
	 * Port to bind to. (Default 0.0.0.0 - all PHP detected IP addresses of the system)
	 * @var int
	 */
	protected $port;

	/**
	 * Resource or stream of the server socket binding
	 * @var resource|stream
	 */
	protected $server_socket;

	/**
	 * List of connected client sockets
	 * @var array
	 */
	protected $clients;

	/**
	 * Used to track on_message events
	 * @var array
	 */
	protected $message_callbacks;

	/**
	 * Used to track on_connect events
	 * @var array
	 */
	protected $connect_callbacks;

	/**
	 * Used to track on_disconnect events
	 * @var array
	 */
	protected $disconnect_callbacks;

	/**
	 * Used to track switch listeners or other socket connection types
	 * @var array
	 */
	protected $listeners;
	public static $logger;

	/**
	 * Subscriber Objects
	 * @var subscriber
	 */
	protected $subscribers;

	/**
	 * Array of registered services
	 * @var array
	 */
	private $services;

	public function is_debug_enabled(): bool {
		return parent::$log_level === LOG_DEBUG;
	}

	/**
	 * Reload settings
	 * @return void
	 * @throws \RuntimeException
	 * @access protected
	 */
	protected function reload_settings(): void {
		// Initialize tracking arrays
		$this->listeners = [];
		$this->clients = [];
		$this->message_callbacks = [];
		$this->connect_callbacks = [];
		$this->disconnect_callbacks = [];
		$this->subscribers = [];

		$settings = new settings(['database' => database::new(['config' => config::load()])]);

		$ip = $settings->get('websocket_server', 'bind_ip_address', '127.0.0.1');
		if ($ip === null) {
			throw new \RuntimeException("ERROR: Bind IP address not specified");
		}

		// Save the setting
		$this->ip = $ip;

		$port = intval($settings->get('websocket_server', 'bind_port', 8080));
		if (empty($port)) {
			throw new \RuntimeException("ERROR: Port address not specified");
		}

		// Save the setting
		$this->port = $port;
	}

	/**
	 * Display the version on the console
	 * @return void
	 * @access protected
	 */
	protected static function display_version(): void {
		echo "Web Socket Service Version 1.00\n";
	}

	/**
	 * Set extra command options from the command line
	 * @access protected
	 */
	protected static function set_command_options() {
		//TODO: ip address
		//TODO: port
	}

	/**
	 * Trigger disconnect callbacks
	 */
	protected function update_connected_clients() {
		$disconnected_clients = [];
		foreach ($this->clients as $index => $resource) {
			if (!is_resource($resource) || feof($resource)) {
				// Ensure resource is free
				unset($this->clients[$index]);
				$disconnected_clients[] = $resource;
			}
		}

		if (!empty($disconnected_clients)) {
			foreach ($disconnected_clients as $dis_con) {
				$this->trigger_disconnect($dis_con);
			}
		}
	}

	private function get_subscriber_from_socket_id($socket): ?subscriber {
		$subscriber = null;
		// Get the subscriber based on their socket ID
		foreach ($this->subscribers as $s) {
			if ($s->equals($socket)) {
				$subscriber = $s;
				break;
			}
		}
		return $subscriber;
	}

	private function authenticate_subscriber(subscriber $subscriber, websocket_message $message) {
		$this->info("Authenticating client: $subscriber->id");

		// Already authenticated
		if ($subscriber->is_authenticated()) {
			return true;
		}

		// Authenticate their token
		if ($subscriber->authenticate_token($message->token)) {
			$subscriber->send(websocket_message::request_authenticated($message->request_id, $message->service));
			// Check for service authenticated
			if ($subscriber->is_service()) {
				$this->info("Service $subscriber->id authenticated");
				$this->services[$subscriber->service_name()] = $subscriber;
			} else {
				// Subscriber authenticated
				$this->info("Client $subscriber->id authenticated");
				$subscriptions = $subscriber->subscribed_to();
				foreach ($subscriptions as $subscribed_to) {
					if (isset($this->services[$subscribed_to])) {
						$subscriber_service = $this->services[$subscribed_to];
						$class_name = $subscriber_service->service_class();
						// Make sure we can call the 'create_filter_chain_for' method
						if (is_a($class_name, 'websocket_service_interface', true)) {
							// Call the service class method to validate the subscriber
							$filter = $class_name::create_filter_chain_for($subscriber);
							if ($filter !== null) {
								// Log the filter has been set for the subscriber
								$this->info("Set filter for " . $subscriber->id());
								$subscriber->set_filter($filter);
							}
						}
						$this->info("Set permissions for $subscriber->id for service " . $subscriber_service->service_name());
					}
				}
			}
		} else {
			$subscriber->send(websocket_message::request_unauthorized($message->request_id, $message->service));
			// Disconnect them
			$this->handle_disconnect($subscriber->socket_id());
		}
		return;
	}

	private function broadcast_service_message(subscriber $broadcaster, ?websocket_message $message = null) {

		$this->debug("Processing Broadcast");

		// Ensure we have something to do
		if ($message === null) {
			$this->warn("Unable to broadcast empty message");
			return;
		}

		$subscribers = array_filter($this->subscribers, function ($subscriber) use ($broadcaster) {
			return $subscriber->not_equals($broadcaster);
		});

		if (empty($subscribers)) {
			$this->debug("No subscribers to broadcast message to");
			return;
		}

		// Ensure the service is not responding to a specific request
		$request_id = $message->request_id;
		if (empty($request_id)) {

			// Get the service name from the message
			$service_name = $message->service_name;

			// Filter subscribers to only the ones subscribed to the service name
			$send_to = $this->filter_subscribers($subscribers, $message, $service_name);

			// Send the message to the filtered subscribers
			foreach ($send_to as $subscriber) {
				try {
					// Notify of the message we are broadcasting
					$this->debug("Broadcasting message '" . $message->payload['event_name'] . "' for service '" . $message->service_name . "' to subscriber $subscriber->id");
					$subscriber->send_message($message);
				} catch (subscriber_token_expired_exception $ste) {
					$this->info("Subscriber $ste->id token expired");
					// Subscriber token has expired so disconnect them
					$this->handle_disconnect($subscriber->socket_id());
				}
			}
		}
		// Route a specific request from a service back to a subscriber
		else {
			// Get the subscriber object hash
			$object_id = $message->resource_id;
			if (isset($this->subscribers[$object_id])) {
				$subscriber = $this->subscribers[$object_id];
				// Remove the resource_id from the message
				$message->resource_id('');
				// TODO: Fix removal of request_id
				$message->request_id('');
				// Return the requested results back to the subscriber
				$subscriber->send_message($message);
			}
		}
		return;
	}

	/**
	 * Filters subscribers based on the service name given
	 * @param array $subscribers
	 * @param websocket_message $message
	 * @param string $service_name
	 * @return array List of subscriber objects or an empty array if there are no subscribers to that service name
	 */
	private function filter_subscribers(array $subscribers, websocket_message $message, string $service_name): array {
		$filtered = [];

		foreach ($subscribers as $subscriber) {
			$caller_context = strtolower($message->caller_context ?? '');
			if (!empty($caller_context) && $subscriber->has_subscribed_to($service_name) && ($subscriber->show_all || $caller_context === $subscriber->domain_name || $caller_context === 'public' || $caller_context === 'default'
					)
			) {
				$filtered[] = $subscriber;
			} else {
				if ($subscriber->has_subscribed_to($service_name))
					$filtered[] = $subscriber;
			}
		}

		return $filtered;
	}

	/**
	 * Create a subscriber for each connection
	 * @param resource $socket
	 * @return void
	 */
	private function handle_connect($socket) {
		// We catch only the socket disconnection exception as there is a general try/catch already
		try {
			$subscriber = new subscriber($socket, [websocket_service::class, 'send']);
			$this->subscribers[$subscriber->id] = $subscriber;
			$subscriber->send(websocket_message::connected());
		} catch (\socket_disconnected_exception $sde) {
			$this->warning("Client $sde->id disconnected during connection");
			// remove the connected client
			$this->handle_disconnect($sde->id);
		}
		return;
	}

	/**
	 * Web socket client disconnected from the server or this service has requested a disconnect from the subscriber
	 * @param subscriber|resource|int|string $object_or_resource_or_id
	 */
	private function handle_disconnect($object_or_resource_or_id) {
		//
		// Notify user
		//
		$this->info("Disconnecting subscriber: '$object_or_resource_or_id'");

		//
		// Search for the socket using the equals method in subscriber
		//
		$subscriber = null;

		/* PHP 8 syntax: $subscriber = array_find($this->subscribers, fn ($subscriber) => $subscriber->equals($socket_id)); */

		// Find the subscriber in our array
		foreach ($this->subscribers as $s) {
			if ($s->equals($object_or_resource_or_id)) {
				$subscriber = $s;
			}
		}

		// We have found our subscriber to be disconnected
		if ($subscriber !== null) {
			// If they are still connected then disconnect them with the proper disconnect
			if ($subscriber->is_connected()) {
				$subscriber->disconnect();
			}

			// remove from the subscribers list
			unset($this->subscribers[$subscriber->id]);

			// remove from services
			unset($this->services[$subscriber->service_name()]);

			// notify user
			$this->info("Disconnected subscriber: '$subscriber->id'");
		}

		// show the list for debugging
		$this->debug("Current Subscribers: " . implode(', ', array_keys($this->subscribers)));
	}

	/**
	 * When a message event occurs, send to all the subscribers
	 * @param resource $socket
	 * @param mixed $data
	 */
	private function handle_message($socket, $data) {
		$subscriber = $this->get_subscriber_from_socket_id($socket);

		// Ensure we have someone to talk to
		if ($subscriber === null)
			return;

		$this->debug("Received message from " . $subscriber->id);

		// Convert the message from json string to a message array
		$json_array = json_decode($data, true);

		if (is_array($json_array))
			try {

				// Check for an authenticating subscriber
				if ($json_array['service'] === 'authentication') {
					$this->authenticate_subscriber($subscriber, new websocket_message($json_array));
					return;
				}

				// Create a websocket_message object using the json data sent
				$message = websocket_message::create_from_json_message($json_array);

				if ($message === null) {
					$this->warn("Message is empty");
					return;
				}

				// Reject subscribers that do not have not validated
				if (!$subscriber->is_authenticated()) {
					$subscriber->send(websocket_message::request_authentication($message->request_id()));
					return;
				}

				// If the message comes from a service, broadcast it to all subscribers subscribed to that service
				if ($subscriber->is_service()) {
					$this->debug("Message is from service");
					$this->broadcast_service_message($subscriber, $message);
					return;
				}

				// Message is from the client so check the service_name that needs to get the message
				if (!empty($message->service_name())) {
					$this->debug("Message is from subscriber");
					$this->handle_client_message($subscriber, $message);
				} else {
					// Message does not have a service name
					$this->warning("The message does not have a service name. All messages must have a service name to direct their query to.");
					$subscriber->send(websocket_message::request_is_bad($message->id, 'INVALID', $message->topic));
				}
			} catch (socket_disconnected_exception $sde) {
				$this->handle_disconnect($sde->id);
			}
	}

	private function handle_client_message(subscriber $subscriber, websocket_message $message) {
		//find the service with that name
		foreach ($this->subscribers as $service) {
			//when we find the service send the request
			if ($service->service_equals($message->service_name())) {
				//notify we found the service
				$this->debug("Routing message to service '" . $message->service_name() . "' for topic '" . $message->topic() . "'");

				//attach the current subscriber permissions so the service can verify
				$message->permissions($subscriber->get_permissions());

				//attach the domain name
				$message->domain_name($subscriber->get_domain_name());

				//attach the client id so we can track the request
				$message->resource_id = $subscriber->id;

				//send the modified web socket message to the service
				$service->send((string) $message);

				//continue searching for service providers
				continue;
			}
		}
	}

	/**
	 * Runs the web socket server binding to the ip and port set in default settings
	 * The run method will stop if the SIG_TERM or SIG_HUP signal is processed in the parent
	 * @return int
	 * @throws \RuntimeException
	 * @throws socket_exception
	 */
	public function run(): int {
		// Reload all settings and initialize object properties
		$this->reload_settings();

		$this->server_socket = stream_socket_server("tcp://{$this->ip}:{$this->port}", $errno, $errstr);
		if (!$this->server_socket) {
			throw new \RuntimeException("Cannot bind socket ({$errno}): {$errstr}");
		}
		stream_set_blocking($this->server_socket, false);

		//
		// Register handlers
		// The handlers can be registered outside this class because they are standard callbacks
		//
		$this->on_connect([self::class, 'handle_connect']);
		$this->on_disconnect([self::class, 'handle_disconnect']);
		$this->on_message([self::class, 'handle_message']);

		$stream_select_tries = 0;

		while ($this->running) {

			//
			// Merge all sockets to a single array
			//
			$read = array_merge([$this->server_socket], $this->clients);
			$write = $except = [];

			//$this->debug("Waiting on event. Connected Clients: (".count($this->clients).")", LOG_DEBUG);
			//
			// Wait for activity on the sockets and timeout about 3 times per second
			//
			$result = stream_select($read, $write, $except, 0, 333333);
			if ($result === false) {
				// Check for error status 3 times in a row
				if (++$stream_select_tries > 3) {
					throw new \RuntimeException("Error occured reading socket");
				}
				// There was likely a disconnect during the wait state
				$this->update_connected_clients();
				continue;
			}

			// Reset stream_select counter
			$stream_select_tries = 0;

			if ($result === 0) {
				// Timeout no activity
				continue;
			}

			//
			// Handle a socket activity
			//
			foreach ($read as $client_socket) {
				// new connection
				if ($client_socket === $this->server_socket) {
					$conn = @stream_socket_accept($this->server_socket, 0);
					if ($conn) {
						try {
							// complete handshake on blocking socket
							stream_set_blocking($conn, true);
							$this->handshake($conn);
							// switch to non-blocking for further reads
							stream_set_blocking($conn, false);
							// add them to the websocket list
							$this->clients[] = $conn;
							// notify websocket on_connect listeners
							$this->trigger_connect($conn);
						} catch (invalid_handshake_exception $ex) {
							$resource = $ex->getResourceId();
							$this->warning('Invalid handshake from resource ' . $resource);
							$this->disconnect_client($resource);
							$this->warning('Disconnected resource ' . $resource);
						}
						continue;
					}
				}

				// Process web socket client communication
				$message = $this->receive_frame($client_socket);
				if ($message === '') {
					$this->debug("Empty message");
					continue;
				}

				// Check for control frame
				if (strlen($message) === 2) {
					$value = bin2hex($message);
					if ($value === '03e9') {
						$this->disconnect_client($client_socket);
						continue;
					}
					$this->err("UNKNOWN CONTROL FRAME: '$value'");
				}

				try {
					$this->trigger_message($client_socket, $message);
				} catch (subscriber_exception $se) {
					//
					// Here we are catching any type of subscriber exception and displaying the error in the log.
					// This will disconnect the subscriber as we no longer know the state of the object.
					//

					//
					// Get the error details
					//
					$subscriber_id = $se->getSubscriberId();
					$message = $se->getMessage();
					$code = $se->getCode();
					$file = $se->getFile();
					$line = $se->getLine();

					//
					// Dump the details in the log
					//
					$this->err("ERROR FROM $subscriber_id: $message ($code) IN FILE $file (Line: $line)");
					$this->err($se->getTraceAsString());
					//
					// Disconnect the subscriber
					//
					$subscriber = $this->subscribers[$subscriber_id] ?? null;
					if ($subscriber !== null) $this->disconnect_client($subscriber->socket());
				}
			}
		}
	}

	/**
	 * Overrides the parent class to shutdown all sockets
	 * @override service
	 */
	public function __destruct() {
		//disconnect all clients
		foreach ($this->clients as $socket) {
			$this->disconnect_client($socket);
		}
		//finish destruct using the parent
		parent::__destruct();
	}

	public function get_open_sockets(): array {
		return $this->clients;
	}

	/**
	 * Returns true if there are connected web socket clients.
	 * @return bool
	 */
	public function has_clients(): bool {
		return !empty($this->clients);
	}

	/**
	 * When a web socket message is received the $on_message_callback function is called.
	 * Multiple on_message functions can be specified.
	 * @param callable $on_message_callback
	 * @throws InvalidArgumentException
	 */
	public function on_message(callable $on_message_callback) {
		if (!is_callable($on_message_callback)) {
			throw new \InvalidArgumentException('The callable on_message_callback must be a valid callable function');
		}
		$this->message_callbacks[] = $on_message_callback;
	}

	/**
	 * Calls all the on_message functions
	 * @param resource $resource
	 * @param string $message
	 * @return void
	 * @access protected
	 */
	protected function trigger_message($resource, string $message) {
		foreach ($this->message_callbacks as $callback) {
			call_user_func($callback, $resource, $message);
			return;
		}
	}

	/**
	 * When a web socket handshake has completed, the $on_connect_callback function is called.
	 * Multiple on_connect functions can be specified.
	 * @param callable $on_connect_callback
	 * @throws InvalidArgumentException
	 */
	public function on_connect(callable $on_connect_callback) {
		if (!is_callable($on_connect_callback)) {
			throw new \InvalidArgumentException('The callable on_connect_callback must be a valid callable function');
		}
		$this->connect_callbacks[] = $on_connect_callback;
	}

	/**
	 * Calls all the on_connect functions
	 * @param resource $resource
	 * @access protected
	 */
	protected function trigger_connect($resource) {
		foreach ($this->connect_callbacks as $callback) {
			call_user_func($callback, $resource);
		}
	}

	/**
	 * When a web socket has disconnected, the $on_disconnect_callback function is called.
	 * Multiple functions can be specified with subsequent calls
	 * @param string|callable $on_disconnect_callback
	 * @throws InvalidArgumentException
	 */
	public function on_disconnect($on_disconnect_callback) {
		if (!is_callable($on_disconnect_callback)) {
			throw new \InvalidArgumentException('The callable on_disconnect_callback must be a valid callable function');
		}
		$this->disconnect_callbacks[] = $on_disconnect_callback;
	}

	/**
	 * Calls all the on_disconnect_callback functions
	 * @param resource $socket
	 * @access protected
	 */
	protected function trigger_disconnect($socket) {
		foreach ($this->disconnect_callbacks as $callback) {
			call_user_func($callback, $socket);
		}
	}

	/**
	 * Returns the socket used in the server connection
	 * @return resource
	 */
	public function get_socket() {
		return $this->server_socket;
	}

	/**
	 * Remove a client socket on disconnect.
	 * @param resource $resource Resource for the socket connection
	 * @return bool Returns true on client disconnect and false when the client is not found in the tracking array
	 * @access protected
	 */
	protected function disconnect_client($resource): bool {
		// Close the socket
		if (is_resource($resource)) {
			@fwrite($resource, chr(0x88) . chr(0x00)); // 0x88 = close frame, no reason
			@fclose($resource);
		}

		//$this->debug("OLD Client List: " . var_dump($this->clients, true));

		// Clean out the array
		$clients = array_filter($this->clients, function ($resource) {
			return is_resource($resource) && !feof($resource);
		});

		//$this->debug("NEW Client List: " . var_dump($clients, true));

		// Compare to the original array
		$diff = array_diff($this->clients, $clients);

		//$this->debug("DIFF Client List: " . var_dump($diff, true));

		// Replace the old list with only the connected ones
		$this->clients = $clients;

		// Trigger the disconnect for each closed socket
		foreach ($diff as $socket) {
			// We must check before closing the socket that it is a resource or a fatal error will occur
			if (is_resource($socket)) {
				@fwrite($resource, "\x88\x00"); // 0x88 = close frame, no payload
				@fclose($socket);
			}
			// Trigger the disconnect so any hooks can clean up their lists
			$this->trigger_disconnect($socket);
		}
		return true;
	}

	/**
	 * Performs web socket handshake on new connection.
	 * @access protected
	 */
	protected function handshake($resource): void {
		// ensure blocking to read full header
		stream_set_blocking($resource, true);
		$request_header = '';
		while (($line = fgets($resource)) !== false) {
			$request_header .= $line;
			if (rtrim($line) === '') {
				break;
			}
		}
		if (!preg_match("/Sec-WebSocket-Key: (.*)\r\n/i", $request_header, $matches)) {
			throw new invalid_handshake_exception($resource, "Invalid WebSocket handshake");
		}
		$key = trim($matches[1]);
		$accept_key = base64_encode(
				sha1($key . "258EAFA5-E914-47DA-95CA-C5AB0DC85B11", true)
		);
		$response_header = "HTTP/1.1 101 Switching Protocols\r\n"
				. "Upgrade: websocket\r\n"
				. "Connection: Upgrade\r\n"
				. "Sec-WebSocket-Accept: {$accept_key}\r\n\r\n";
		fwrite($resource, $response_header);
	}

	/**
	 * Read specific number of bytes from a websocket
	 * @param resource $socket
	 * @param int $length
	 * @return string
	 */
	private function read_bytes($socket, int $length): string {
		$data = '';
		while (strlen($data) < $length && is_resource($socket)) {
			$chunk = fread($socket, $length - strlen($data));
			if ($chunk === false || $chunk === '' || !is_resource($socket)) {
				//$this->disconnect_client($socket);
				return '';
			}
			$data .= $chunk;
		}
		return $data;
	}

	/**
	 * Reads a websocket data frame and converts it to a regular string
	 * @param resource $socket
	 * @return string
	 */
	private function receive_frame($socket): string {
		// Read first two header bytes
		$hdr = $this->read_bytes($socket, 2);
		// Ensure we have the correct number of bytes
		if (strlen($hdr) !== 2) {
			$this->warning('Header is empty!');
			$this->update_connected_clients();
			return '';
		}
		$bytes = unpack('Cfirst/Csecond', $hdr);
		$fin = ($bytes['first'] >> 7) & 0x1;
		$opcode = $bytes['first'] & 0x0F;
		$masked = ($bytes['second'] >> 7) & 0x1;
		$length = $bytes['second'] & 0x7F;

		// Determine actual payload length
		if ($length === 126) {
			$ext = $this->read_bytes($socket, 2);
			// Ensure we have the correct number of bytes
			if (strlen($ext) < 2)
				return '';
			$length = unpack('n', $ext)[1];
		} elseif ($length === 127) {
			$ext = $this->read_bytes($socket, 8);
			// Ensure we have the correct number of bytes
			if (strlen($ext) < 8)
				return '';
			// unpack 64-bit BE; PHP 7.0+: use J, else fallback
			$arr = unpack('J', $ext);
			$length = $arr[1];
		}

		// Read mask key if client→server frame
		$maskKey = $masked ? $this->read_bytes($socket, 4) : '';

		// Read payload data
		$data = $this->read_bytes($socket, $length);

		if (empty($data)) {
			$this->warning("Received empty frame (ID# $socket)");
			return '';
		}

		// Unmask if needed
		if ($masked) {
			// Ensure we have the correct number of bytes
			if (strlen($maskKey) < 4)
				return '';
			$unmasked = '';
			for ($i = 0; $i < $length; $i++) {
				$unmasked .= $data[$i] ^ $maskKey[$i % 4];
			}
			$data = $unmasked;
		}

		// Return completed data frame
		return $data;
	}

	private function debug(string $message) {
		self::log($message, LOG_DEBUG);
	}

	private function warning(string $message) {
		self::log($message, LOG_WARNING);
	}

	private function err(string $message) {
		self::log($message, LOG_ERR);
	}

	private function info(string $message) {
		self::log($message, LOG_INFO);
	}

	/**
	 * Send text frame to client. If the socket connection is not a valid resource, the send
	 * method will fail silently and return false.
	 * @param resource $resource The socket or resource id to communicate on.
	 * @param string|null $payload The string to wrap in a web socket frame to send to the clients
	 * @return bool
	 */
	public static function send($resource, ?string $payload): bool {
		if (!is_resource($resource)) {
			self::log("Cannot send: invalid resource", LOG_ERR);
			return false;
		}

		if ($payload === null) {
			@fwrite($resource, "\x88\x00"); // 0x88 = close frame, no payload
			return true;
		}

		$payload_length = strlen($payload);
		$frame_header = "\x81"; // FIN = 1, text frame
		// Create frame header
		if ($payload_length <= 125) {
			$frame_header .= chr($payload_length);
		} elseif ($payload_length <= 65535) {
			$frame_header .= chr(126) . pack('n', $payload_length);
		} else {
			$frame_header .= chr(127) . pack('J', $payload_length); // PHP 7.1+ supports 'J' for 64-bit unsigned
		}

		$frame = $frame_header . $payload;

		// Attempt to write full frame
		$written = @fwrite($resource, $frame);
		if ($written === false) {
			self::log("fwrite() failed for socket " . (int) $resource, LOG_ERR);
			throw new socket_disconnected_exception($resource);
		}

		if ($written < strlen($frame)) {
			self::log("Partial frame sent: {$written}/" . strlen($frame) . " bytes", LOG_WARNING);
			return false;
		}

		return true;
	}

	/**
	 * Get the IP and port of the connected remote system.
	 * @param resource $resource The resource or stream of the connection
	 * @return array An associative array of remote_ip and remote_port
	 */
	public static function get_remote_info($resource): array {
		[$remote_ip, $remote_port] = explode(':', stream_socket_get_name($resource, true), 2);
		return ['remote_ip' => $remote_ip, 'remote_port' => $remote_port];
	}
}
