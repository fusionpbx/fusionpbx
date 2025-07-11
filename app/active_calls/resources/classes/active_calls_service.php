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
 * Description of active_calls_service
 *
 * @author Tim Fry <tim@fusionpbx.com>
 */
class active_calls_service extends service implements websocket_service_interface {

	const SWITCH_EVENTS = [
		['Event-Name' => 'CHANNEL_CREATE'],
		['Event-Name' => 'CHANNEL_CALLSTATE'],
		['Event-Name' => 'CALL_UPDATE'],
		['Event-Name' => 'PLAYBACK_START'],
		['Event-Name' => 'PLAYBACK_STOP'],
		['Event-Name' => 'CHANNEL_DESTROY'],
		['Event-Name' => 'CHANNEL_PARK'],
		['Event-Name' => 'CHANNEL_UNPARK'],
		['Event-Name' => 'CHANNEL_EXECUTE'],
		['Event-Name' => 'HEARTBEAT'], // Ensures that the switch is still responding
		['Event-Subclass' => 'valet_parking::info'],
	];

	const EVENT_KEYS = [
		// Event name: CHANNEL_EXECUTE, CHANNEL_DESTROY, NEW_CALL...
		'event_name',
		// Unique Call Identifier to determine new/existing calls
		'unique_id',
		// Domain
		'caller_context',
		'channel_presence_id',
		// Ringing, Hangup, Answered
		'answer_state',
		'channel_call_state',
		// Time stamp
		'caller_channel_created_time',
		// Codecs
		'channel_read_codec_name',
		'channel_write_codec_name',
		'channel_read_codec_rate',
		'channel_write_codec_rate',
		'caller_channel_name',
		// Caller/Callee ID
		'caller_caller_id_name',
		'caller_caller_id_number',
		'caller_destination_number',
		// Encrypted
		'secure',
		// Application
		'application',
		'application_data',
		'variable_current_application',
		'playback_file_path',
		// Valet parking info
		'valet_extension',
		'action',
		'variable_referred_by_user',
		'variable_pre_transfer_caller_id_name',
		'variable_valet_parking_timeout',
		// Direction
		'call_direction',
		'variable_call_direction',
		'other_leg_rdnis',
		'other_leg_unique_id',
		'content_type',
	];

	//
	// Maps the event key to the permission name
	//
	const PERMISSION_MAP = [
		'channel_read_codec_name'   => 'call_active_codec',
		'channel_read_codec_rate'   => 'call_active_codec',
		'channel_write_codec_name'  => 'call_active_codec',
		'channel_write_codec_rate'  => 'call_active_codec',
		'caller_channel_name'       => 'call_active_profile',
		'secure'                    => 'call_active_secure',
		'application'               => 'call_active_application',
		'playback_file_path'        => 'call_active_application',
		'variable_current_application'=> 'call_active_application',
		'channel_presence_id'		=> 'call_active_view',
		'caller_context'			=> 'call_active_domain',
	];

	/**
	 * Switch Event Socket
	 * @var event_socket
	 */
	private $event_socket;

	/**
	 * Web Socket Client
	 * @var websocket_client
	 */
	private $ws_client;

	/**
	 * Resource for the Switch Event Socket used to control blocking
	 * @var resource
	 */
	private $switch_socket;
	private $topics;

	/**
	 * Event Filter
	 * @var filter
	 */
	private $event_filter;

	private static $switch_port = null;
	private static $switch_host = null;
	private static $switch_password = null;

	private static $websocket_port = null;
	private static $websocket_host = null;

	/**
	 * Checks if an event exists in the SWITCH_EVENTS.
	 * @param string $event_name The value to search for.
	 * @return bool True if the value is found, false otherwise.
	 */
	public static function event_exists(string $event_name): bool {
		if (!empty($event_name)) {
			foreach (active_calls_service::SWITCH_EVENTS as $events) {
				if (in_array($event_name, $events)) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Checks if an event exists in the SWITCH_EVENTS ignoring case.
	 * This check is slower then the event_exists function. Whenever possible, it is recommended to use that function instead.
	 * @param string $event_name
	 * @return bool
	 */
	public static function event_exists_ignore_case(string $event_name): bool {
		foreach (self::SWITCH_EVENTS as $events) {
			foreach ($events as $value) {
				if (strtolower($value) === strtolower($event_name)) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Builds a filter for the subscriber
	 * @param subscriber $subscriber
	 * @return filter
	 */
	public static function create_filter_chain_for(subscriber $subscriber): filter {
		// Do not filter domain
		if ($subscriber->has_permission('call_active_all') || $subscriber->is_service()) {
			return filter_chain::and_link([
				new event_filter(self::SWITCH_EVENTS),
				new permission_filter(self::PERMISSION_MAP, $subscriber->get_permissions()),
				new event_key_filter(self::EVENT_KEYS),
			]);
		}

		// Filter on single domain name
		if ($subscriber->has_permission('call_active_domain')) {
			return filter_chain::and_link([
				new event_filter(self::SWITCH_EVENTS),
				new permission_filter(self::PERMISSION_MAP, $subscriber->get_permissions()),
				new event_key_filter(self::EVENT_KEYS),
				new caller_context_filter([$subscriber->get_domain_name()]),
			]);
		}

		// Filter on extensions
		return filter_chain::and_link([
			new event_filter(self::SWITCH_EVENTS),
			new permission_filter(self::PERMISSION_MAP, $subscriber->get_permissions()),
			new event_key_filter(self::EVENT_KEYS),
			new extension_filter($subscriber->get_user_setting('extension', [])),
		]);
	}

	/**
	 * Returns the service name for this service that is used when the web browser clients subscriber
	 * to this service for updates
	 * @return string
	 */
	public static function get_service_name(): string {
		return "active.calls";
	}

	/**
	 * Returns a string used to execute a hangup command
	 * @param string $uuid
	 * @return string
	 */
	public static function get_hangup_command(string $uuid): string {
		return "bgapi uuid_kill $uuid";
	}

	/**
	 * Reloads the settings for the service so the service does not have to be restarted
	 * @return void
	 */
	protected function reload_settings(): void {
		// re-read the config file to get any possible changes
		parent::$config->read();

		// use the connection information in the config file

		// re-connect to the event socket
		if ($this->connect_to_event_socket()) {
			$this->register_event_socket_filters();
		}
		// re-connect to the websocket server
		$this->connect_to_ws_server();
	}

	/**
	 * Displays the version of the active calls service in the console
	 * @return void
	 */
	protected static function display_version(): void {
		echo "Active Calls Service 1.0\n";
	}

	/**
	 * Sets the command line options
	 */
	protected static function set_command_options() {
		parent::append_command_option(
			command_option::new()
				->description('Set the Port to connect to the switch')
				->short_option('p')
				->short_description('-p <port>')
				->long_option('switch-port')
				->long_description('--switch-port <port>')
				->callback('set_switch_port')
		);
		parent::append_command_option(
			command_option::new()
				->description('Set the IP address for the switch')
				->short_option('i')
				->short_description('-i <ip_addr>')
				->long_option('switch-ip')
				->long_description('--switch-ip <ip_addr>')
				->callback('set_switch_host_address')
		);
		parent::append_command_option(
			command_option::new()
				->description('Set the password to be used with switch')
				->short_option('o')
				->short_description('-o <password>')
				->long_option('switch-password')
				->long_description('--switch-password <password>')
				->callback('set_switch_password')
		);
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

	protected static function set_switch_host_address($host): void {
		self::$switch_host = $host;
	}

	protected static function set_switch_port($port): void {
		self::$switch_port = $port;
	}

	protected static function set_switch_password($password): void {
		self::$switch_password = $password;
	}

	/**
	 * Main entry point
	 * @return int Non-zero exit indicates an error has occurred
	 */
	public function run(): int {

		// Notify connected web server socket when we close
		register_shutdown_function(function ($ws_client) {
			if ($ws_client !== null)
				$ws_client->disconnect();
		}, $this->ws_client);

		// Create an active call filter using filter objects to create an 'OR' chain
		$this->event_filter = filter_chain::or_link([new event_key_filter(active_calls_service::EVENT_KEYS)]);

		// Register callbacks for the topics
		$this->on_topic('in.progress',  [$this, 'on_in_progress'] );
		$this->on_topic('hangup',       [$this, 'on_hangup']      );
		$this->on_topic('eavesdrop',    [$this, 'on_eavesdrop']   );
		$this->on_topic('authenticate', [$this, 'on_authenticate']);

		$this->info("Starting " . self::class . " service");
		// Suppress the WebSocket Server Error Message so it doesn't flood the system logs
		$suppress_ws_message = false;
		// Suppress the Event Socket Error Message so it doesn't flood the system logs
		$suppress_es_message = false;
		while ($this->running) {
			$read = [];
			// reconnect to event_socket
			if ($this->event_socket === null || !$this->event_socket->is_connected()) {
				if (!$this->connect_to_event_socket()) {
					if (!$suppress_es_message) $this->error("Unable to connect to switch event server");
					$suppress_es_message = true;
				} else {
					$this->register_event_socket_filters();
				}
			}

			// reconnect to websocket server
			if ($this->ws_client === null || !$this->ws_client->is_connected()) {
				//$this->warn("Web socket disconnected");
				if (!$this->connect_to_ws_server()) {
					if (!$suppress_ws_message) $this->error("Unable to connect to websocket server.");
					$suppress_ws_message = true;
				}
			}

			// The switch _socket_ is used to read the 'data ready' on the stream
			if ($this->event_socket !== null && $this->event_socket->is_connected()) {
				$read[] = $this->switch_socket;
				$suppress_es_message = false;
			}

			if ($this->ws_client !== null && $this->ws_client->is_connected()) {
				$read[] = $this->ws_client->socket();
				$suppress_ws_message = false;
			}

			if (!empty($read)) {
				$write = $except = [];
				// Wait for an event and timeout at 1/3 of a second so we can re-check all connections
				if (false === stream_select($read, $write, $except, 0, 333333)) {
					// severe error encountered so exit
					$this->running = false;
					// Exit with non-zero exit code
					return 1;
				}

				if (!empty($read)) {
					$this->debug("Received event");
					// Iterate over each socket event
					foreach ($read as $resource) {
						// Switch event
						if ($resource === $this->switch_socket) {
							$this->handle_switch_event();
							// No need to process more in the loop
							continue;
						}

						// Web socket event
						if ($resource === $this->ws_client->socket()) {
							$this->handle_websocket_event($this->ws_client);
							continue;
						}
					}
				}
			}
		}

		// Normal termination
		return 0;
	}

	private function debug(string $message) {
		self::log($message, LOG_DEBUG);
	}

	private function warn(string $message) {
		self::log($message, LOG_WARNING);
	}

	private function error(string $message) {
		self::log($message, LOG_ERR);
	}

	private function info(string $message) {
		self::log($message, LOG_INFO);
	}

	private function on_authenticate(websocket_message $websocket_message) {
		$this->info("Authenticating with websocket server");
		// Create a service token
		[$token_name, $token_hash] = websocket_client::create_service_token(active_calls_service::get_service_name(), static::class);

		// Request authentication as a service
		$this->ws_client->authenticate($token_name, $token_hash);
	}

	private function on_in_progress(websocket_message $websocket_message) {
		// Check permission
		if (!$websocket_message->has_permission('call_active_view')) {
			$this->warn("Permission 'call_active_show' not found in subscriber request");
			websocket_client::send($this->ws_client->socket(), websocket_message::request_forbidden($websocket_message->request_id, SERVICE_NAME, $websocket_message->topic));
		}

		// Set up the response array
		$response = [];
		$response['service_name'] = SERVICE_NAME;
		// Attach the original request ID and subscriber ID given from websocket server so it can route it back
		$response['request_id'] = $websocket_message->request_id;
		$response['resource_id'] = $websocket_message->resource_id;
		$response['status_string'] = 'ok';
		$response['status_code'] = 200;

		// Get the active calls from the helper function
		$calls = $this->get_active_calls($this->event_socket, $this->ws_client);
		$count = count($calls);
		$this->debug("Sending calls in progress ($count)");

		// Use the subscribers permissions to filter out the event keys not permitted
		$filter = filter_chain::or_link([new permission_filter(self::PERMISSION_MAP, $websocket_message->permissions())]);

		/** @var event_message $event */
		foreach ($calls as $event) {
			// Remove keys that are not permitted by filter
			$event->apply_filter($filter);
			$response['payload'] = $event;
			$response['topic'] = $event->name;
			$websocket_response = new websocket_message($response);
			websocket_client::send($this->ws_client->socket(), $websocket_response);
		}
	}

	private function on_hangup(websocket_message $websocket_message) {
		// Check permission
		if (!$websocket_message->has_permission('call_active_hangup')) {
			$this->warn("Permission 'call_active_hangup' not found in subscriber request");
			websocket_client::send($this->ws_client->socket(), websocket_message::request_forbidden($websocket_message->request_id, SERVICE_NAME, $websocket_message->topic));
		}

		// Get the payload
		$payload = $websocket_message->payload();

		// Get the request ID so we can route it back
		$request_id = $websocket_message->request_id() ?? '';

		// Get the UUID from the payload
		$uuid = $payload['unique_id'] ?? '';

		// Respond with bad command
		if (empty($uuid)) {
			websocket_client::send(websocket_message::request_is_bad($request_id, SERVICE_NAME, 'hangup'));
		}

		$host = self::$switch_host ?? parent::$config->get('switch.event_socket.host', '127.0.0.1');
		$port = self::$switch_port ?? parent::$config->get('switch.event_socket.port', 8021);
		$password = self::$switch_password ?? parent::$config->get('switch.event_socket.password', 'ClueCon');

		//
		// We use a new socket connection to get the response because the switch
		// can be firing events while we are processing so we need only this
		// request answered
		//
		$event_socket = new event_socket();
		$event_socket->connect($host, $port, $password);

		// Make sure we are connected
		if (!$event_socket->is_connected()) {
			$this->warn("Unable to connect to event socket");
			return;
		}

		// Send the command on a new channel that does not have events
		$reply = trim($event_socket->request("api uuid_kill $uuid"));

		// Close the connection
		$event_socket->close();

		// Set up the response array
		$response = [];
		$response['service_name'] = SERVICE_NAME;
		$response['topic'] = $websocket_message->topic;
		$response['request_id'] = $websocket_message->request_id;

		$response['status_message'] = 'success';
		$response['status_code'] = 200;

		// Set the response payload to the reply received from the switch
		$response['payload'] = $reply;

		// Notify websocket server of the result
		websocket_client::send($this->ws_client->socket(), new websocket_message($response));
	}

	private function on_eavesdrop(websocket_message $websocket_message) {
		// Check permission
		if (!$websocket_message->has_permission('call_active_eavesdrop')) {
			$this->warn("Permission 'call_active_eavesdrop' not found in subscriber request");
			websocket_client::send($this->ws_client->socket(), websocket_message::request_forbidden($websocket_message->request_id, SERVICE_NAME, $websocket_message->topic));
		}

		// Make sure we are connected
		if (!$this->event_socket->is_connected()) {
			$this->warn("Failed to hangup call because event socket no longer connected");
			return;
		}

		// Set up the response array
		$response = [];
		$response['service_name'] = SERVICE_NAME;
		$response['topic'] = $websocket_message->topic;
		$response['request_id'] = $websocket_message->request_id;

		// Get the payload and domain from message
		$payload = $websocket_message->payload();
		$domain_name = $websocket_message->domain_name() ?? '';

		// Get the eavesdrop information from the payload to send to the switch
		$uuid = $payload['unique_id'] ?? '';
		$origination_caller_id_name = $payload['origination_caller_id_name'] ?? '';
		$caller_caller_id_number = $payload['caller_caller_id_number'] ?? '';
		$origination_caller_contact = $payload['origination_caller_contact'] ?? '';

		$response['status_message'] = 'success';
		$response['status_code'] = 200;

		$api_cmd = "bgapi originate {origination_caller_id_name=$origination_caller_id_name,origination_caller_id_number=$caller_caller_id_number}user/$origination_caller_contact@$domain_name &eavesdrop($uuid)";

		// Log the eavesdrop
		$this->info("Eavesdrop on $uuid by $origination_caller_contact@$domain_name");

		//
		// Send to the switch and ignore the result
		// Ignoring the switch information is important because on a busy system there will be more
		// events so the response is not necessarily correct
		//
		$this->event_socket->request($api_cmd);

		// Execute eavesdrop command
		$response['status_message'] = 'success';
		$response['status_code'] = 200;

		// Notify websocket server of the result
		websocket_client::send($this->ws_client->socket(), new websocket_message($response));
	}

	/**
	 * Connects to the web socket server using a websocket_client object
	 * @return bool
	 */
	private function connect_to_ws_server(): bool {
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
	 * Connects to the switch event socket
	 * @return bool
	 */
	private function connect_to_event_socket(): bool {

		// check if we have defined it already
		if (!isset($this->switch_socket)) {
			//default to false for the while loop below
			$this->switch_socket = false;
		}

		// When no command line option is used to set the switch host, port, or password, get it from
		// the config file. If it is not in the config file, then set a default value
		$host = self::$switch_host ?? parent::$config->get('switch.event_socket.host', '127.0.0.1');
		$port = self::$switch_port ?? parent::$config->get('switch.event_socket.port', 8021);
		$password = self::$switch_password ?? parent::$config->get('switch.event_socket.password', 'ClueCon');

		try {
			//set up the socket away from the event_socket object so we have control over blocking
			$this->switch_socket = stream_socket_client("tcp://$host:$port", $errno, $errstr, 5);
		} catch (\RuntimeException $re) {
			$this->warn('Unable to connect to event socket');
		}

		// If we didn't connect then return back false
		if (!$this->switch_socket) {
			return false;
		}

		// Block (wait) for responses so we can authenticate
		stream_set_blocking($this->switch_socket, true);

		// Create the event_socket object using the connected socket
		$this->event_socket = new event_socket($this->switch_socket);

		// The host and port are already provided when we connect the socket so just provide password
		$this->event_socket->connect(null, null, $password);

		// No longer need to wait for events
		stream_set_blocking($this->switch_socket, false);

		return $this->event_socket->is_connected();
	}

	/**
	 * Registers the switch events needed for active calls
	 */
	private function register_event_socket_filters() {
		$this->event_socket->request('event plain all');

		//
		// CUSTOM and API are required to handle events such as:
		//   - 'valet_parking::info'
		//   - 'SMS::SEND_MESSAGE'
		//   - 'cache::flush'
		//   - 'sofia::register'
		//
		//	$event_filter = [
		//		'CUSTOM',		// Event-Name is swapped with Event-Subclass
		//		'API',			// Event-Name is swapped with API-Command
		//	];
		// Merge API and CUSTOM with the events listening
		//	$events = array_merge(ws_active_calls::SWITCH_EVENTS, $event_filter);
		// Add filters for active calls only
		foreach (active_calls_service::SWITCH_EVENTS as $events) {
			foreach ($events as $event_key => $event_name) {
				$this->debug("Requesting event filter for [$event_key]=[$event_name]");
				$response = $this->event_socket->request("filter $event_key $event_name");
				while (!is_array($response)) {
					$response = $this->event_socket->read_event();
				}
				if (is_array($response)) {
					while (($response = array_pop($response)) !== "+OK filter added. [$event_key]=[$event_name]") {
						$response = $this->event_socket->read_event();
						usleep(1000);
					}
				}
				$this->debug("Response: " . $response);
			}
		}
	}

	private function get_active_calls(): array {
		$calls = [];

		$host = self::$switch_host ?? parent::$config->get('switch.event_socket.host', '127.0.0.1');
		$port = self::$switch_port ?? parent::$config->get('switch.event_socket.port', 8021);
		$password = self::$switch_password ?? parent::$config->get('switch.event_socket.password', 'ClueCon');

		//
		// We use a new socket connection to get the response because the switch
		// can be firing events while we are processing so we need only this
		// request answered
		//
		$event_socket = new event_socket();
		$event_socket->connect($host, $port, $password);

		// Make sure we are connected
		if (!$event_socket->is_connected()) {
			return $calls;
		}

		// Send the command on a new channel
		$json = trim($event_socket->request('api show channels as json'));
		$event_socket->close();

		$json_array = json_decode($json, true);
		if (empty($json_array["rows"])) {
			return $calls;
		}

		// Map the rows returned to the active call format
		foreach ($json_array["rows"] as $call) {
			$message = new event_message($call, $this->event_filter);
			$this->debug("MESSAGE: $message");
			// adjust basic info to match an event setting the callstate to ringing
			// so that a row can be created for it
			$message->event_name = 'CHANNEL_CALLSTATE';
			$message->answer_state = 'ringing';
			$message->channel_call_state = 'RINGING';
			$message->unique_id = $call['uuid'];
			$message->call_direction = $call['direction'];

			//set the codecs
			$message->caller_channel_created_time = intval($call['created_epoch']) * 1000000;
			$message->channel_read_codec_name = $call['read_codec'];
			$message->channel_read_codec_rate = $call['read_rate'];
			$message->channel_write_codec_name = $call['write_codec'];
			$message->channel_write_codec_rate = $call['write_rate'];

			//get the profile name
			$message->caller_channel_name = $call['name'];

			//domain or context
			$message->caller_context = $call['context'];
			$message->caller_caller_id_name = $call['initial_cid_name'];
			$message->caller_caller_id_number = $call['initial_cid_num'];
			$message->caller_destination_number = $call['initial_dest'];
			$message->application = $call['application'] ?? '';
			$message->secure = $call['secure'] ?? '';

			if (true) {
				$this->debug("-------- ACTIVE CALL ----------");
				$this->debug($message);
				$this->debug("In Progress: '$message->name', $message->unique_id");
				$this->debug("-------------------------------");
			}
			$calls[] = $message;
		}

		return $calls;
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

	/**
	 * Allows the service to register a callback so when the topic arrives the callable is called
	 * @param type $topic
	 * @param type $callable
	 */
	public function on_topic($topic, $callable) {
		if (!isset($this->topics[$topic])) {
			$this->topics[$topic] = [];
		}
		$this->topics[$topic][] = $callable;
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

		$this->debug("Received message on websocket: (" . strlen($json_string) . " bytes)");

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
	 * Handles a switch event by reading the event and then dispatching to the web socket server
	 */
	private function handle_switch_event() {

		$raw_event = $this->event_socket->read_event();

		//$this->debug("=====================================");
		//$this->debug("RAW EVENT: " . ($raw_event['$'] ?? ''));
		//$this->debug("=====================================");

		// get the switch message event object
		$event = event_message::create_from_switch_event($raw_event, $this->event_filter);

		// Log the event
		$this->debug("EVENT: '" . $event->name . "'");

		if (!$this->ws_client->is_connected()) {
			$this->debug('Not connected to websocket host. Dropping Event');
			return;
		}

		// Ensure it is an event that we are looking for
		if (active_calls_service::event_exists($event->name)) {
			// Create a message to send on websocket
			$message = new websocket_message();

			// Set the service name so subscribers can filter
			$message->service(SERVICE_NAME);

			// Set the topic to the event name
			$message->topic = $event->name;

			// The event is the payload
			$message->payload($event->to_array());

			// Notify system log of the message and event name
			$this->debug("Sending Event: '$event->event_name'");

			//send event to the web socket routing service
			websocket_client::send($this->ws_client->socket(), $message);
		}
	}

	/**
	 * Gets the array of enabled domains using the UUID as the array key and the domain name as the array value
	 * @return array
	 */
	private static function get_domain_names(database $database): array {
		return array_column($database->execute("select domain_name, domain_uuid from v_domains where domain_enabled='true'") ?: [], 'domain_name', 'domain_uuid');
	}

	/**
	 * Queries the database to return the domain name for the given uuid
	 * @param string $domain_uuid
	 * @return string
	 */
	private static function get_domain_name_by_uuid(database $database, string $domain_uuid): string {
		return $database->execute("select domain_name from v_domains where domain_enabled='true' and domain_uuid = :domain_uuid limit 1", ['domain_uuid' => $domain_uuid], 'column') ?: '';
	}

	/**
	 * Queries the database to return a single domain uuid for the given name. If more then one match is possible use the get_domain_names function.
	 * @param string $domain_name
	 * @return string
	 */
	private static function get_domain_uuid_by_name(database $database, string $domain_name): string {
		return $database->execute("select domain_uuid from v_domains where domain_enabled='true' and domain_name = :domain_name limit 1", ['domain_name' => $domain_name], 'column') ?: '';
	}
}
