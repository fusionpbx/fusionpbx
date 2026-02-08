<?php

/**
 * Handles WebSocket connections for the central system.
 *
 * This service builds on the shared functionality provided by {@see base_websocket_system_service}
 * to manage WebSocket sessions in a uniform way across the application.  It exposes a
 * single entry point for the central system to create, update, and terminate connections.
 *
 * In addition to the standard channel sockets, the service implements *event sockets*.
 * These sockets are specifically wired to the switch‑event pipeline, allowing the
 * service to push real‑time event payloads (e.g. switch state changes, alerts) back
 * to the client without requiring a separate WebSocket implementation.
 *
 * @author Tim Fry <tim@fusionpbx.com>
 * @version 1.0.0
 */
class active_conferences_service extends base_websocket_system_service implements websocket_service_interface {

	/**
	 * Direct mapping of switch events using Key => Value pair
	 *
	 * This is used to only subscribe to specific events from the switch
	 * that are relevant to active conferences.
	 * @var array
	 */
	const switch_events = [
		['API-Command' => 'conference'],
		['Event-Name' => 'HEARTBEAT'],
		['Event-Subclass' => 'conference::maintenance'],
	];

	/**
	 * Keys to include in the switch event payload sent to clients
	 *
	 * This is used to filter the switch event data sent to clients
	 * to only include relevant information about the conference events.
	 * @var array
	 */
	const event_keys = [
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
		// Conference specific - FreeSWITCH headers
		'conference_name',	// Name of the conference including domain (from Conference-Name header)
		'conference_uuid',	// UUID of the conference (from Conference-Unique-ID header)
		'conference_size',	// Number of members (from Conference-Size header)
		'conference_profile_name',	// Profile name (from Conference-Profile-Name header)
		'action',	// start-talking, stop-talking, add-member, del-member, etc.
		'floor',
		'video',
		'hear',
		'see',
		'speak',
		'talking',
		'mute_detect',
		'hold',
		'member_id',
		'member_type',
		'member_ghost',
		'energy_level',
		'current_energy',
		'new_id',
		'api_command_argument',
		// Member caller ID from conference events
		'caller_id_name',	// Member's caller ID name
		'caller_id_number',	// Member's caller ID number
	];

	/**
	 * Map of conference actions to required permissions
	 *
	 * This is used to check if a user has the necessary permissions
	 * to perform a specific action on a conference.
	 * @var array
	 */
	const permission_map = [
		'lock'       => 'conference_interactive_lock',
		'unlock'     => 'conference_interactive_lock',
		'mute'       => 'conference_interactive_mute',
		'unmute'     => 'conference_interactive_mute',
		'mute_all'   => 'conference_interactive_mute',
		'unmute_all' => 'conference_interactive_mute',
		'deaf'       => 'conference_interactive_deaf',
		'undeaf'     => 'conference_interactive_deaf',
		'kick'       => 'conference_interactive_kick',
		'kick_all'   => 'conference_interactive_kick',
		'energy'     => 'conference_interactive_energy',
		'volume_in'  => 'conference_interactive_volume',
		'volume_out' => 'conference_interactive_gain',
	];

	/**
	 * Event filter used to filter conference events
	 *
	 * @var mixed
	 */
	protected $event_filter;

	/**
	 * @var mixed $switch_socket The socket connection to the FreeSWITCH server
	 *                           Used for communicating with the switch to manage
	 *                           active conference sessions
	 */
	protected $switch_socket;

	/**
	 * @var mixed $event_socket The event socket connection used to receive events
	 *                          from the FreeSWITCH server
	 * @access protected
	 */
	protected $event_socket;

	/**
	 * Debug show permissions mode setting
	 * Values: 'bytes' (minimal), 'full' (detailed), or 'off' (disabled)
	 *
	 * @var string
	 */
	protected $debug_show_permissions_mode;

	/**
	 * Debug show switch event setting
	 * When true, switch events are logged to debug output
	 *
	 * @var bool
	 */
	protected $debug_show_switch_event;

	/**
	 * Cache for conference UUID to name mapping
	 * Key: conference_uuid, Value: conference_name (e.g., "room@domain.com")
	 *
	 * @var array
	 */
	protected $conference_name_cache;

	/**
	 * Builds a filter for the subscriber
	 *
	 * @param subscriber $subscriber
	 *
	 * @return filter|null
	 */
	public static function create_filter_chain_for(subscriber $subscriber): ?filter {
		// Domain filtering for conferences
		if ($subscriber->has_permission('conference_active_view')) {
			return filter_chain::and_link([
				new caller_context_filter([$subscriber->get_domain_name()]),
			]);
		}

		// No special filtering for conferences, they are domain-specific by design
		return filter_chain::or_link(self::event_keys);
	}

	/**
	 * Returns the service name for this service that is used when the web browser clients subscriber
	 * to this service for updates
	 *
	 * @return string
	 */
	public static function get_service_name(): string {
		return "active.conferences";
	}

	/**
	 * Returns a string used to execute a conference command
	 *
	 * @param string $uuid The UUID of the conference (optional)
	 * @param string $domain_name The domain name of the conference (optional)
	 *
	 * @return string
	 * @access public
	 */
	public static function get_conference_command(string $uuid = '', string $domain_name = ''): string {
		if (!empty($uuid) && !empty($domain_name)) {
			$name = "$uuid@$domain_name";
		} else {
			$name = "";
		}
		return "api conference " . ($name ? $name . " " : "") . "json_list";
	}

	/**
	 * Reloads the settings for the service so the service does not have to be restarted
	 *
	 * @return void
	 */
	protected function reload_settings(): void {
		// Re-read the config file to get any possible changes
		parent::$config->read();

		// Load default settings from database
		$database = database::new(['config' => parent::$config]);
		$settings = new settings(['database' => $database]);
		$this->debug_show_permissions_mode = $settings->get('active_conferences', 'debug_show_permissions_mode', 'off');
		$this->debug_show_switch_event = $settings->get('active_conferences', 'debug_show_switch_event', false) === true;
		$this->debug("Loaded debug_show_permissions_mode: " . $this->debug_show_permissions_mode);
		$this->debug("Loaded debug_show_switch_event: " . ($this->debug_show_switch_event ? 'true' : 'false'));

		// Re-connect to the websocket server
		$this->connect_to_ws_server();

		// Re-connect to the switch server
		if ($this->connect_to_switch_server()) {
			$this->register_event_socket_filters();
		}

		// Add the switch event socket to the base websocket listener
		$this->add_listener($this->switch_socket, [$this, 'handle_switch_events']);
	}

	/**
	 * Handles incoming FreeSWITCH events from the event socket
	 *
	 * @return void
	 */
	protected function handle_switch_events(): void {
		$event = $this->event_socket->read_event();
		$event_message = event_message::create_from_switch_event($event, $this->event_filter);

		// Set the event message topic as the event name
		$topic = $event_message->topic = $event_message->event_name;

		switch ($topic) {
			case 'conference':
			case 'conference::maintenance':
				$this->on_conference_maintenance($event_message);
				break;
			case 'heartbeat':
				$this->on_heartbeat($event_message);
				break;
			default:
				break;

		}
		return;
	}

	/**
	 * Called when a HEARTBEAT event is received from the switch
	 *
	 * @param event_message $event_message The event message object
	 *
	 * @return void
	 */
	protected function on_heartbeat($event_message): void {
		$this->debug('HEARTBEAT');
	}

	/**
	 * Called when the websocket connection is established
	 *
	 * @return void
	 */
	protected function on_ws_connected(): void {
		// Call the parent on connected function
		parent::on_ws_connected();

		// Show the registered service name
		if ($this->ws_client->is_connected()) {
			$this->info('Registered: ' . $this->get_service_name());
		}
	}

	/**
	 * Registers the switch events needed for active conferences
	 *
	 * @return void
	 */
	protected function register_event_socket_filters(): void {
		$this->event_socket->request('event plain all');

		//
		// CUSTOM and API are required to handle events such as:
		//   - 'conference::maintenance'
		//   - 'SMS::SEND_MESSAGE'
		//   - 'cache::flush'
		//   - 'sofia::register'
		//
		//	$event_filter = [
		//		'CUSTOM',		// Event-Name is swapped with Event-Subclass
		//		'API',			// Event-Name is swapped with API-Command
		//	];
		// Merge API and CUSTOM with the events listening
		//	$events = array_merge(ws_active_conference_service::switch_events, $event_filter);
		// Add filters for active conference events only
		foreach (self::switch_events as $events) {
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
				$this->info("Response: " . $response);
			}
		}

		// Create the filter to remove extra array entries in the event
		// because we don't need for this event on the switch.
		// This allows us to less data on websockets when an event occurs.
		$this->event_filter = filter_chain::and_link([
			new event_key_filter(self::event_keys)
		]);

		return;
	}

	/**
	 * Establishes a connection to the switch server.
	 *
	 * @return bool Returns true if the connection was successfully established, false otherwise.
	 */
	protected function connect_to_switch_server(): bool {
		// Get configuration data from the config.conf file
		$host = parent::$config->get('switch.event_socket.host', '127.0.0.1');
		$port = intval(parent::$config->get('switch.event_socket.port', 8021));
		$password = parent::$config->get('switch.event_socket.password', 'ClueCon');

		// Create a new switch server connection object
		try {
			$this->switch_socket = stream_socket_client("tcp://$host:$port", $errno, $errstr, 5);
		} catch (\RuntimeException $re) {
			$this->warning('Unable to connect to event socket');
		}

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
	 * Displays the version of the active conferences service in the console
	 *
	 * @return void
	 * @override base_websocket_system_service
	 */
	protected static function display_version(): void {
		echo "Active Conferences Service 1.0\n";
	}

	/**
	 * Handles FreeSWITCH events for active conferences.
	 *
	 * This method processes incoming switch events related to conference
	 * activity and performs the necessary actions based on event types.
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function register_topics(): void {
		$this->on_topic('in_progress', [$this, 'request_in_progress']);
		$this->on_topic('room', [$this, 'subscribe_room']);
		$this->on_topic('ping', [$this, 'handle_ping']);
		$this->on_topic('action', [$this, 'handle_action']);
		$this->on_topic('*', [$this, 'subscribe_all']);

		$this->reload_settings();
	}

	/**
	 * Handle ping requests to keep the connection alive
	 *
	 * @param websocket_message $message
	 *
	 * @return void
	 */
	protected function handle_ping(websocket_message $message): void {
		$this->debug('Ping received from client. Sending pong response.');

		// Create a pong response
		$response = new websocket_message();
		$response
			->payload(['pong' => time()])
			->service_name(self::get_service_name())
			->topic('pong')
			->status_string('ok')
			->status_code(200)
			->request_id($message->request_id())
			->resource_id($message->resource_id())
		;

		// Send the response back to the client
		websocket_client::send($this->ws_client->socket(), $response);
	}

	/**
	 * Handle conference action requests from clients
	 *
	 * Actions: lock, unlock, mute, unmute, deaf, undeaf, kick, kick_all,
	 *          mute_all, unmute_all, energy, volume_in, volume_out
	 *
	 * @param websocket_message $message
	 *
	 * @return void
	 */
	protected function handle_action(websocket_message $message): void {
		$payload = $message->payload();
		$action = $payload['action'] ?? '';
		$conference_name = $payload['conference_name'] ?? '';
		$member_id = $payload['member_id'] ?? '';
		$uuid = $payload['uuid'] ?? '';
		$direction = $payload['direction'] ?? '';
		$domain_name = $payload['domain_name'] ?? '';

		// Decode any URL or HTML entity encoding
		$conference_name = html_entity_decode(urldecode($conference_name));

		$this->debug("Action request: $action for conference: $conference_name member: $member_id");

		// Get permissions from the message (attached by websocket_service)
		$permissions = $message->get_permissions();

		// Debug permissions based on setting (loaded in reload_settings)
		if ($this->debug_show_permissions_mode === 'full') {
			$this->debug("Permission check - Action: $action, Required: " . (self::permission_map[$action] ?? 'unknown'));
			$this->debug("User permissions: " . json_encode($permissions));
		} elseif ($this->debug_show_permissions_mode === 'bytes') {
			$perm_count = count($permissions);
			$perm_bytes = strlen(json_encode($permissions));
			$this->debug("Permissions: $perm_count items, $perm_bytes bytes");
		}

		// Validate action
		if (!isset(self::permission_map[$action])) {
			$this->send_action_response($message, false, 'Invalid action: ' . $action);
			return;
		}

		// Check permission
		$required_permission = self::permission_map[$action];
		if (!isset($permissions[$required_permission])) {
			if ($this->debug_show_permissions_mode === 'full') {
				$this->debug("Permission denied - Required: $required_permission, Has: " . implode(', ', array_keys($permissions)));
			}
			$this->warning("Permission denied: $required_permission for action: $action");
			$this->send_action_response($message, false, 'Permission denied');
			return;
		}

		if ($this->debug_show_permissions_mode === 'full') {
			$this->debug("Permission granted: $required_permission for action: $action");
		}

		// Validate conference name (must include a domain - basic validation)
		if (empty($conference_name) || strpos($conference_name, '@') === false) {
			$this->warning("Invalid conference name: $conference_name");
			$this->send_action_response($message, false, 'Invalid conference name');
			return;
		}

		// Execute the action
		$result = $this->execute_conference_action($action, $conference_name, $member_id, $uuid, $direction);

		$this->send_action_response($message, $result['success'], $result['message']);
	}

	/**
	 * Execute a conference action via event socket
	 *
	 * @param string $action          The action to execute
	 * @param string $conference_name The conference name
	 * @param string $member_id       The member ID (optional)
	 * @param string $uuid            The call UUID (optional)
	 * @param string $direction       Direction for energy/volume (up/down)
	 *
	 * @return array ['success' => bool, 'message' => string]
	 */
	private function execute_conference_action(string $action, string $conference_name, string $member_id, string $uuid, string $direction): array {
		$this->debug("Executing action: $action on $conference_name");

		try {
			switch ($action) {
				case 'lock':
				case 'unlock':
					$cmd = "conference $conference_name $action";
					event_socket::api($cmd);
					break;

				case 'mute':
				case 'unmute':
					if (empty($member_id)) {
						return ['success' => false, 'message' => 'Member ID required'];
					}
					$cmd = "conference $conference_name $action $member_id";
					event_socket::api($cmd);
					// Clear hand raised flag on mute/unmute
					if (!empty($uuid)) {
						event_socket::api("uuid_setvar $uuid hand_raised false");
					}
					break;

				case 'mute_all':
					$cmd = "conference $conference_name mute non_moderator";
					$this->debug("Executing command: $cmd");
					$result = event_socket::api($cmd);
					$this->debug("Command result: " . print_r($result, true));
					break;

				case 'unmute_all':
					$cmd = "conference $conference_name unmute non_moderator";
					$this->debug("Executing command: $cmd");
					$result = event_socket::api($cmd);
					$this->debug("Command result: " . print_r($result, true));
					break;

				case 'deaf':
				case 'undeaf':
					if (empty($member_id)) {
						return ['success' => false, 'message' => 'Member ID required'];
					}
					$cmd = "conference $conference_name $action $member_id";
					event_socket::api($cmd);
					break;

				case 'kick':
					if (empty($uuid)) {
						return ['success' => false, 'message' => 'UUID required'];
					}
					event_socket::api("uuid_kill $uuid");
					break;

				case 'kick_all':
					$this->kick_all_members($conference_name);
					break;

				case 'energy':
					if (empty($member_id) || empty($direction)) {
						return ['success' => false, 'message' => 'Member ID and direction required'];
					}
					$current = event_socket::api("conference $conference_name energy $member_id");
					$current = trim($current);
					if (preg_match('/=(\d+)/', $current, $matches)) {
						$value = (int)$matches[1];
						$value = ($direction === 'up') ? $value + 100 : $value - 100;
						event_socket::api("conference $conference_name energy $member_id $value");
					}
					break;

				case 'volume_in':
					if (empty($member_id) || empty($direction)) {
						return ['success' => false, 'message' => 'Member ID and direction required'];
					}
					$current = event_socket::api("conference $conference_name volume_in $member_id");
					$current = trim($current);
					if (preg_match('/=(-?\d+)/', $current, $matches)) {
						$value = (int)$matches[1];
						$value = ($direction === 'up') ? $value + 1 : $value - 1;
						event_socket::api("conference $conference_name volume_in $member_id $value");
					}
					break;

				case 'volume_out':
					if (empty($member_id) || empty($direction)) {
						return ['success' => false, 'message' => 'Member ID and direction required'];
					}
					$current = event_socket::api("conference $conference_name volume_out $member_id");
					$current = trim($current);
					if (preg_match('/=(-?\d+)/', $current, $matches)) {
						$value = (int)$matches[1];
						$value = ($direction === 'up') ? $value + 1 : $value - 1;
						event_socket::api("conference $conference_name volume_out $member_id $value");
					}
					break;

				default:
					return ['success' => false, 'message' => 'Unknown action'];
			}

			return ['success' => true, 'message' => 'Action executed'];

		} catch (\Exception $e) {
			$this->error("Action failed: " . $e->getMessage());
			return ['success' => false, 'message' => $e->getMessage()];
		}
	}

	/**
	 * Kick all members from a conference
	 *
	 * @param string $conference_name
	 *
	 * @return void
	 */
	private function kick_all_members(string $conference_name): void {
		// Get conference member list
		$json_str = event_socket::api("conference '$conference_name' json_list");
		$conferences = json_decode($json_str, true);

		if (!is_array($conferences) || empty($conferences)) {
			return;
		}

		$conference = $conferences[0];
		$members = $conference['members'] ?? [];

		$first = true;
		foreach ($members as $member) {
			$member_uuid = $member['uuid'] ?? '';
			if (!empty($member_uuid)) {
				event_socket::api("uuid_kill $member_uuid");
				if ($first) {
					usleep(500000); // 0.5 seconds for first member
					$first = false;
				} else {
					usleep(10000); // 0.01 seconds for others
				}
			}
		}
	}

	/**
	 * Send action response back to client
	 *
	 * @param websocket_message $message Original message
	 * @param bool $success Whether action succeeded
	 * @param string $status_message Status message
	 *
	 * @return void
	 */
	private function send_action_response(websocket_message $message, bool $success, string $status_message): void {
		$response = new websocket_message();
		$response
			->payload(['success' => $success, 'message' => $status_message])
			->service_name(self::get_service_name())
			->topic('action_response')
			->status_string($success ? 'ok' : 'error')
			->status_code($success ? 200 : 400)
			->request_id($message->request_id())
			->resource_id($message->resource_id())
		;

		websocket_client::send($this->ws_client->socket(), $response);
	}

	/**
	 * Subscribe to all events (wildcard) - useful for debugging
	 *
	 * @param websocket_message $message
	 *
	 * @return void
	 */
	protected function subscribe_all(websocket_message $message): void {
		$this->debug('Wildcard subscription requested - subscribing to all events');

		// Forward to websocket server to register this subscriber for all events from this service
		$response = new websocket_message();
		$response
			->payload(['subscribed' => '*'])
			->service_name(self::get_service_name())
			->topic('*')
			->status_string('ok')
			->status_code(200)
			->request_id($message->request_id())
			->resource_id($message->resource_id())
		;

		// Send the response back to the client
		websocket_client::send($this->ws_client->socket(), $response);
	}

	/**
	 * Subscribe to room events (placeholder for specific room filtering)
	 *
	 * @param websocket_message $message
	 * @return void
	 */
	protected function subscribe_room(websocket_message $message): void {
		$this->debug('Room subscription requested');
		
		$response = new websocket_message();
		$response
			->payload(['subscribed' => 'room'])
			->service_name(self::get_service_name())
			->topic('room')
			->status_string('ok')
			->status_code(200)
			->request_id($message->request_id())
			->resource_id($message->resource_id())
		;

		websocket_client::send($this->ws_client->socket(), $response);
	}

	/**
	 * Handles requests for conferences in progress
	 *
	 * @param websocket_message $message The incoming websocket message
	 *
	 * @return void
	 */
	protected function request_in_progress(websocket_message $message): void {
		$this->debug('Conferences in progress requested by websocket client');

		// Get required parameters from the message payload
		$payload = $message->payload();
		$domain_name = $payload['domain_name'] ?? '';
		$uuid = $payload['uuid'] ?? '';

		$this->debug("in_progress request - domain_name: $domain_name, uuid: $uuid");

		// Get the list of active conferences
		// Note: get_conference_command returns "api conference ... json_list"
		$command = self::get_conference_command($uuid, $domain_name);

		// Remove "api " prefix if present to use with event_socket::api
		if (substr($command, 0, 4) === 'api ') {
			$command = substr($command, 4);
		}

		// Use a dedicated event socket for API command so we don't get any the wrong events
		$json_str = trim(event_socket::api($command));
		$conferences = json_decode($json_str, true);

		// Enrich conferences with display names from database
		if (is_array($conferences)) {
			foreach ($conferences as &$conference) {
				$conf_name = $conference['conference_name'] ?? '';
				if (!empty($conf_name)) {
					$conference['conference_display_name'] = $this->lookup_conference_display_name($conf_name);
				}
			}
			unset($conference); // Break reference
		}

		// If a specific UUID was requested but no active conference found,
		// check if the conference exists in the database (Conference Center room or simple Conference)
		if (!empty($uuid) && (empty($conferences) || !is_array($conferences))) {
			$conference_info = $this->lookup_conference_info($uuid, $domain_name);
			if ($conference_info !== null) {
				// Conference exists in database but has no active members
				// Return an empty conference structure so the UI can show the room as valid but empty
				$conferences = [[
					'conference_name' => $conference_info['conference_name'],
					'conference_display_name' => $conference_info['display_name'],
					'conference_uuid' => $uuid,
					'member_count' => 0,
					'members' => [],
					'locked' => false,
					'recording' => false,
					'exists_in_database' => true,
				]];
				$this->debug("Conference exists in database but has no active members: " . $conference_info['display_name']);
			} else {
				// Conference does not exist in database
				$conferences = [[
					'conference_name' => $uuid . '@' . $domain_name,
					'conference_uuid' => $uuid,
					'member_count' => 0,
					'members' => [],
					'exists_in_database' => false,
					'error' => 'not_found',
				]];
				$this->debug("Conference not found in database: $uuid");
			}
		}

		// Create a response message
		$response = new websocket_message();
		$response
			->payload($conferences)
			->service_name(self::get_service_name())
			->topic('in_progress')
			->request_id($message->request_id())
			->resource_id($message->resource_id())
		;

		// Send the response back to the client
		websocket_client::send($this->ws_client->socket(), $response);
	}

	/**
	 * Handles the conference maintenance event
	 *
	 * This method is triggered when a conference maintenance event occurs.
	 * It processes the event message and performs necessary maintenance operations
	 * for the active conference.
	 *
	 * @param event_message $event_message The event message object containing conference maintenance data
	 * @return void
	 */
	private function on_conference_maintenance(event_message $event_message): void {
		// Show switch event if debug setting is enabled
		if ($this->debug_show_switch_event) {
			$this->debug('Processing switch event conference::maintenance');
			$this->debug('Event message: ' . $event_message);
		}

		$action = $event_message->action ?? '';

		// Replace - with _ for action names
		$action = str_replace('-', '_', $action);

		// Extract conference name from event using multiple fallback methods
		$conference_name = $this->extract_conference_name($event_message);

		switch ($action) {
			case 'start_talking':
			case 'stop_talking':
				$this->debug("$action event");
				// Talking events only need member_id - no need to fetch full data
				$this->broadcast_event($event_message, $action);
				break;

			case 'add_member':
				$this->debug('add_member event');
				// Fetch complete member data for the added member
				$enriched_data = $this->enrich_member_event($event_message, $conference_name);
				$this->broadcast_enriched_event($enriched_data, $action, $conference_name);
				break;

			case 'del_member':
				$this->debug('del_member event');
				// For del_member, we need member_id and updated member_count
				$enriched_data = $this->enrich_del_member_event($event_message, $conference_name);
				$this->broadcast_enriched_event($enriched_data, $action, $conference_name);
				break;

			case 'mute_member':
			case 'unmute_member':
			case 'deaf_member':
			case 'undeaf_member':
			case 'floor_change':
				$this->debug("$action event");
				// These events just need member_id and the new state
				$this->broadcast_event($event_message, $action);
				break;

			case 'conference_create':
				$this->debug('conference_create event');
				// Include conference info for new conference
				$enriched_data = $this->enrich_conference_create_event($event_message, $conference_name);
				$this->broadcast_enriched_event($enriched_data, $action, $conference_name);
				break;

			case 'conference_destroy':
				$this->debug('conference_destroy event');
				// Just broadcast the conference name
				$this->broadcast_event($event_message, $action);
				break;

			case 'lock':
			case 'unlock':
				$this->debug("$action event");
				$this->broadcast_event($event_message, $action);
				break;

			case 'kick_member':
			case 'play_file':
			case 'play_file_done':
			case 'gain_level':
			case 'volume_level':
			case 'play_file_member_done':
			case 'energy_level':
			case 'execute_app':
				$this->debug("$action event");
				$this->broadcast_event($event_message, $action);
				break;

			default:
				$this->debug("Unknown conference event: $event_message");
				break;
		}
	}

	/**
	 * Extract conference identifier from event message (UUID@domain or extension@domain)
	 * This is the identifier FreeSWITCH uses for API commands.
	 *
	 * @param event_message $event_message The event message
	 * @return string The conference identifier or empty string if not found
	 */
	private function extract_conference_name(event_message $event_message): string {
		// Try direct conference_name field (from Conference-Name header)
		$conference_name = $event_message->conference_name ?? '';
		if (!empty($conference_name)) {
			$this->debug("Conference identifier from conference_name: $conference_name");
			return $conference_name;
		}

		// Try channel_presence_id (e.g., "3001@domain.com")
		$presence_id = $event_message->channel_presence_id ?? '';
		if (!empty($presence_id) && strpos($presence_id, '@') !== false) {
			$this->debug("Conference identifier from channel_presence_id: $presence_id");
			return $presence_id;
		}

		// Try to extract from caller_channel_name (e.g., "sofia/internal/conference+3001@domain.com")
		$channel_name = $event_message->caller_channel_name ?? '';
		if (!empty($channel_name) && preg_match('/conference\+([^\/]+)/', $channel_name, $matches)) {
			$this->debug("Conference identifier from caller_channel_name: " . $matches[1]);
			return $matches[1];
		}

		// Try caller_destination_number with caller_context for domain
		$dest_num = $event_message->caller_destination_number ?? '';
		$context = $event_message->caller_context ?? '';
		if (!empty($dest_num) && !empty($context)) {
			$conference_name = $dest_num . '@' . $context;
			$this->debug("Conference identifier from destination+context: $conference_name");
			return $conference_name;
		}

		$this->debug("Could not extract conference identifier from event");
		return '';
	}

	/**
	 * Cache a conference key to display name mapping
	 * Key can be UUID (for Conference Center) or extension (for simple Conference)
	 *
	 * @param string $conference_key The conference UUID or extension
	 * @param string $display_name The human-readable display name
	 * @return void
	 */
	private function cache_conference_name(string $conference_key, string $display_name): void {
		if (!empty($conference_key) && !empty($display_name)) {
			$this->conference_name_cache[$conference_key] = $display_name;
			$this->debug("Cached conference display name: $conference_key => $display_name");
		}
	}

	/**
	 * Lookup human-readable conference display name from cache or database
	 * 
	 * Conference Center rooms use UUID as identifier and have conference_room_name
	 * Simple Conferences use extension as identifier and have conference_name
	 *
	 * @param string $conference_identifier The full conference name from FreeSWITCH (e.g., "uuid@domain" or "3001@domain")
	 * @return string The human-readable display name or the identifier if not found
	 */
	private function lookup_conference_display_name(string $conference_identifier): string {
		if (empty($conference_identifier)) {
			return '';
		}

		// Parse the identifier to get the key and domain
		$parts = explode('@', $conference_identifier);
		$conference_key = $parts[0] ?? '';
		$domain_name = $parts[1] ?? '';

		if (empty($conference_key)) {
			return $conference_identifier;
		}

		// Check cache first
		if (isset($this->conference_name_cache[$conference_key])) {
			$this->debug("Conference display name cache hit for: $conference_key");
			return $this->conference_name_cache[$conference_key];
		}

		$this->debug("Conference display name cache miss for: $conference_key - querying database");

		try {
			$database = database::new(['config' => parent::$config]);

			// Determine type by checking if key is UUID or numeric extension
			if ($this->is_uuid($conference_key)) {
				// Conference Center room - lookup by UUID
				$sql = "SELECT cr.conference_room_name ";
				$sql .= "FROM v_conference_rooms AS cr ";
				$sql .= "WHERE cr.conference_room_uuid = :conference_room_uuid ";
				$parameters['conference_room_uuid'] = $conference_key;
				$row = $database->select($sql, $parameters, 'row');

				if (!empty($row['conference_room_name'])) {
					$display_name = $row['conference_room_name'];
					$this->cache_conference_name($conference_key, $display_name);
					$this->debug("Found Conference Center room name: $display_name");
					return $display_name;
				}
				unset($parameters);
			}
			
			if (is_numeric($conference_key)) {
				// Simple Conference - lookup by extension
				$sql = "SELECT c.conference_name ";
				$sql .= "FROM v_conferences AS c ";
				$sql .= "LEFT JOIN v_domains AS d ON c.domain_uuid = d.domain_uuid ";
				$sql .= "WHERE c.conference_extension = :conference_extension ";
				if (!empty($domain_name)) {
					$sql .= "AND d.domain_name = :domain_name ";
					$parameters['domain_name'] = $domain_name;
				}
				$parameters['conference_extension'] = $conference_key;
				$row = $database->select($sql, $parameters, 'row');

				if (!empty($row['conference_name'])) {
					$display_name = $row['conference_name'];
					$this->cache_conference_name($conference_key, $display_name);
					$this->debug("Found simple Conference name: $display_name");
					return $display_name;
				}
			}
		} catch (Exception $e) {
			$this->debug("Database error looking up conference display name: " . $e->getMessage());
		}

		// Fallback to the key itself (extension number or UUID)
		$this->debug("No display name found, using identifier: $conference_key");
		return $conference_key;
	}

	/**
	 * Check if a string is a valid UUID
	 *
	 * @param string $string The string to check
	 * @return bool True if valid UUID format
	 */
	private function is_uuid(string $string): bool {
		return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $string) === 1;
	}

	/**
	 * Lookup conference information from database by UUID or extension
	 * Returns conference name and display name if found, null if not found
	 *
	 * @param string $identifier The conference UUID or extension
	 * @param string $domain_name The domain name for context
	 * @return array|null Array with 'conference_name' and 'display_name' keys, or null if not found
	 */
	private function lookup_conference_info(string $identifier, string $domain_name = ''): ?array {
		if (empty($identifier)) {
			return null;
		}

		try {
			$database = database::new(['config' => parent::$config]);

			// Check if identifier is a UUID (Conference Center room)
			if ($this->is_uuid($identifier)) {
				$sql = "SELECT cr.conference_room_uuid, cr.conference_room_name, d.domain_name ";
				$sql .= "FROM v_conference_rooms AS cr ";
				$sql .= "LEFT JOIN v_domains AS d ON cr.domain_uuid = d.domain_uuid ";
				$sql .= "WHERE cr.conference_room_uuid = :conference_room_uuid ";
				$parameters['conference_room_uuid'] = $identifier;
				$row = $database->select($sql, $parameters, 'row');

				if (!empty($row['conference_room_uuid'])) {
					$conf_domain = $row['domain_name'] ?? $domain_name;
					$conference_name = $identifier . '@' . $conf_domain;
					$display_name = $row['conference_room_name'] ?? $identifier;
					$this->cache_conference_name($identifier, $display_name);
					$this->debug("Found Conference Center room in database: $display_name");
					return [
						'conference_name' => $conference_name,
						'display_name' => $display_name,
						'type' => 'conference_center',
					];
				}
				unset($parameters);
			}

			// Check if identifier is numeric (simple Conference extension)
			if (is_numeric($identifier)) {
				$sql = "SELECT c.conference_uuid, c.conference_name, c.conference_extension, d.domain_name ";
				$sql .= "FROM v_conferences AS c ";
				$sql .= "LEFT JOIN v_domains AS d ON c.domain_uuid = d.domain_uuid ";
				$sql .= "WHERE c.conference_extension = :conference_extension ";
				if (!empty($domain_name)) {
					$sql .= "AND d.domain_name = :domain_name ";
					$parameters['domain_name'] = $domain_name;
				}
				$parameters['conference_extension'] = $identifier;
				$row = $database->select($sql, $parameters, 'row');

				if (!empty($row['conference_extension'])) {
					$conf_domain = $row['domain_name'] ?? $domain_name;
					$conference_name = $identifier . '@' . $conf_domain;
					$display_name = $row['conference_name'] ?? $identifier;
					$this->cache_conference_name($identifier, $display_name);
					$this->debug("Found simple Conference in database: $display_name");
					return [
						'conference_name' => $conference_name,
						'display_name' => $display_name,
						'type' => 'conference',
					];
				}
			}
		} catch (Exception $e) {
			$this->debug("Database error looking up conference info: " . $e->getMessage());
		}

		$this->debug("Conference not found in database: $identifier");
		return null;
	}

	/**
	 * Enrich add_member event with complete member data from FreeSWITCH
	 *
	 * @param event_message $event_message The original event
	 * @param string $conference_name The conference name
	 * @return array Enriched event data with full member details
	 */
	private function enrich_member_event(event_message $event_message, string $conference_name): array {
		$event_data = $event_message->to_array();
		$member_id = $event_data['member_id'] ?? '';
		$member_found = false;

		$this->debug("enrich_member_event - conference_name: $conference_name, member_id: $member_id");

		// Get conference data to find the member
		if (!empty($conference_name)) {
			$api_cmd = "conference '$conference_name' json_list";
			$this->debug("Calling API: $api_cmd");
			$json_str = trim(event_socket::api($api_cmd));
			$this->debug("API response length: " . strlen($json_str));
			$conferences = json_decode($json_str, true);

			if (is_array($conferences) && !empty($conferences)) {
				$conference = $conferences[0];
				$members = $conference['members'] ?? [];
				$event_data['member_count'] = $conference['member_count'] ?? count($members);
				$this->debug("Conference found with " . count($members) . " members");

				// Find the specific member
				foreach ($members as $member) {
					if ((string)($member['id'] ?? '') === (string)$member_id) {
						$event_data['member'] = $member;
						$member_found = true;
						$this->debug("Found member $member_id in conference data: " . json_encode($member));
						break;
					}
				}
				
				if (!$member_found) {
					$this->debug("Member $member_id not found in members list. Available IDs: " . implode(', ', array_column($members, 'id')));
				}
			} else {
				$this->debug("json_list returned no conferences or invalid JSON");
			}
		}

		// Fallback: Build member object from event data if not found via json_list
		if (!$member_found && !empty($member_id)) {
			$this->debug("Building member from event data as fallback");
			$event_data['member'] = [
				'id' => $member_id,
				'uuid' => $event_data['unique_id'] ?? '',
				'caller_id_name' => $event_data['caller_id_name'] ?? $event_data['caller_caller_id_name'] ?? '',
				'caller_id_number' => $event_data['caller_id_number'] ?? $event_data['caller_caller_id_number'] ?? '',
				'join_time' => 0,
				'last_talking' => 0,
				'flags' => [
					'can_hear' => ($event_data['hear'] ?? 'true') === 'true',
					'can_speak' => ($event_data['speak'] ?? 'true') === 'true',
					'talking' => ($event_data['talking'] ?? 'false') === 'true',
					'has_video' => ($event_data['video'] ?? 'false') === 'true',
					'has_floor' => ($event_data['floor'] ?? 'false') === 'true',
					'is_moderator' => ($event_data['member_type'] ?? '') === 'moderator',
				],
			];
		}

		$event_data['conference_name'] = $conference_name;
		$event_data['conference_display_name'] = $this->lookup_conference_display_name($conference_name);
		return $event_data;
	}

	/**
	 * Enrich del_member event with updated member count
	 *
	 * @param event_message $event_message The original event
	 * @param string $conference_name The conference name
	 * @return array Enriched event data
	 */
	private function enrich_del_member_event(event_message $event_message, string $conference_name): array {
		$event_data = $event_message->to_array();

		// Try to get member count from the event first (conference_size header)
		$member_count = isset($event_data['conference_size']) ? (int)$event_data['conference_size'] : null;

		// Get updated conference data for member count if not in event
		if ($member_count === null && !empty($conference_name)) {
			$json_str = trim(event_socket::api("conference '$conference_name' json_list"));
			$conferences = json_decode($json_str, true);

			if (is_array($conferences) && !empty($conferences)) {
				$conference = $conferences[0];
				$member_count = $conference['member_count'] ?? 0;
			} else {
				// Conference may have been destroyed
				$member_count = 0;
			}
		}

		$event_data['member_count'] = $member_count ?? 0;
		$event_data['conference_name'] = $conference_name;
		$event_data['conference_display_name'] = $this->lookup_conference_display_name($conference_name);
		return $event_data;
	}

	/**
	 * Enrich conference_create event with conference details
	 *
	 * @param event_message $event_message The original event
	 * @param string $conference_name The conference name
	 * @return array Enriched event data
	 */
	private function enrich_conference_create_event(event_message $event_message, string $conference_name): array {
		$event_data = $event_message->to_array();
		$event_data['conference_name'] = $conference_name;
		$event_data['conference_display_name'] = $this->lookup_conference_display_name($conference_name);
		$event_data['member_count'] = 0;

		// Extract domain from conference name
		if (strpos($conference_name, '@') !== false) {
			$parts = explode('@', $conference_name);
			$event_data['domain_name'] = $parts[1] ?? '';
		}

		return $event_data;
	}

	/**
	 * Broadcast an enriched event with additional data
	 *
	 * @param array $event_data The enriched event data
	 * @param string $action The action/topic name
	 * @param string $conference_name The conference name
	 * @return void
	 */
	private function broadcast_enriched_event(array $event_data, string $action, string $conference_name): void {
		$this->debug("Broadcasting enriched event - action: $action, conference: $conference_name");
		$this->debug("Payload: " . json_encode($event_data));
		
		$message = new websocket_message();
		$message
			->service_name(self::get_service_name())
			->topic($action)
			->payload($event_data)
		;

		websocket_client::send($this->ws_client->socket(), $message);
		$this->debug("Event sent to websocket server");
	}

	/**
	 * Broadcast an event to all subscribed clients
	 *
	 * @param event_message $event_message The event data to broadcast
	 * @param string $action The action/topic name for the event
	 *
	 * @return void
	 */
	private function broadcast_event(event_message $event_message, string $action): void {
		// Create a websocket message with the service_name so the websocket server
		// knows which subscribers to broadcast to
		$message = new websocket_message();
		$message
			->service_name(self::get_service_name())
			->topic($action)
			->payload($event_message->to_array())
		;

		websocket_client::send($this->ws_client->socket(), $message);
	}
}
