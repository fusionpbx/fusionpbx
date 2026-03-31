<?php

/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2008-2025
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Tim Fry <tim@fusionpbx.com>
*/

/**
 * Operator Panel WebSocket Service
 *
 * Unified backend service for the operator panel.  Handles three real-time
 * data streams:
 *
 *   1. Calls / Extensions  — FreeSWITCH channel events (CHANNEL_*, CALL_UPDATE,
 *      valet_parking::info) forwarded as-received so the UI can update extension
 *      blocks in under one second without any page refresh or AJAX.
 *
 *   2. Conference rooms — conference::maintenance events forwarded the same way
 *      the active_conferences service does, reusing the same enrichment helpers.
 *
 *   3. Agent stats — polled from the FreeSWITCH call-center module on a
 *      configurable timer (default 10 s) and broadcast to all connected subscribers.
 *      The timer self-reschedules by returning the interval value from the callback,
 *      which is the pattern supported by {@see base_websocket_system_service::set_timer()}.
 *
 * ALL client-initiated mutations (hangup, transfer, eavesdrop, record, user status,
 * agent status) are handled through the 'action' WebSocket topic — no AJAX is used.
 *
 * @author Tim Fry <tim@fusionpbx.com>
 * @version 1.0.0
 */
class operator_panel_service extends base_websocket_system_service implements websocket_service_interface {

	/**
	 * FreeSWITCH event subscriptions for this service.
	 *
	 * Covers calls, conferences, call-center agent events, and the heartbeat
	 * used to keep the event-socket connection alive.
	 *
	 * @var array
	 */
	const switch_events = [
		// Call / channel events
		['Event-Name'     => 'CHANNEL_CREATE'],
		['Event-Name'     => 'CHANNEL_CALLSTATE'],
		['Event-Name'     => 'CALL_UPDATE'],
		['Event-Name'     => 'CHANNEL_DESTROY'],
		['Event-Name'     => 'CHANNEL_PARK'],
		['Event-Name'     => 'CHANNEL_UNPARK'],
		['Event-Subclass' => 'valet_parking::info'],
		// Conference events
		['API-Command'    => 'conference'],
		['Event-Subclass' => 'conference::maintenance'],
		// Call-center agent events
		['Event-Subclass' => 'callcenter::maintenance'],
		// Registration events
		['Event-Subclass' => 'sofia::register'],
		['Event-Subclass' => 'sofia::unregister'],
		// Keep-alive
		['Event-Name'     => 'HEARTBEAT'],
	];

	/**
	 * Call channel event keys forwarded to subscribers.
	 *
	 * @var array
	 */
	const call_event_keys = [
		'event_name',
		'unique_id',
		'caller_context',
		'channel_presence_id',
		'answer_state',
		'channel_call_state',
		'caller_channel_created_time',
		'channel_read_codec_name',
		'channel_write_codec_name',
		'channel_read_codec_rate',
		'channel_write_codec_rate',
		'caller_channel_name',
		'caller_caller_id_name',
		'caller_caller_id_number',
		'caller_destination_number',
		'secure',
		'application',
		'application_data',
		'variable_current_application',
		'call_direction',
		'variable_call_direction',
		'other_leg_unique_id',
		'other_leg_rdnis',
		'variable_bridge_uuid',
		// Valet parking
		'valet_extension',
		'action',
		'variable_referred_by_user',
		'variable_pre_transfer_caller_id_name',
		'variable_valet_parking_timeout',
	];

	/**
	 * Conference event keys forwarded to subscribers.
	 *
	 * @var array
	 */
	const conf_event_keys = [
		'event_name',
		'unique_id',
		'caller_context',
		'channel_presence_id',
		'answer_state',
		'channel_call_state',
		'caller_channel_created_time',
		'channel_read_codec_name',
		'channel_write_codec_name',
		'caller_caller_id_name',
		'caller_caller_id_number',
		'caller_destination_number',
		'conference_name',
		'conference_uuid',
		'conference_size',
		'conference_profile_name',
		'action',
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
		'caller_id_name',
		'caller_id_number',
		'api_command_argument',
	];

	/**
	 * Map of operator-panel action names to required permissions.
	 *
	 * Used by handle_action() to validate that the requesting subscriber
	 * has the necessary permission before executing the FreeSWITCH command.
	 *
	 * @var array
	 */
	const permission_map = [
		// Call actions
		'hangup'       => 'operator_panel_hangup',
		'hangup_caller' => 'operator_panel_hangup',
		'transfer'     => 'operator_panel_manage',
		'transfer_attended' => 'operator_panel_transfer_attended',
		'transfer_attended_complete' => 'operator_panel_transfer_attended',
		'transfer_attended_cancel' => 'operator_panel_transfer_attended',
		'eavesdrop'    => 'operator_panel_eavesdrop',
		'whisper'      => 'operator_panel_coach',
		'barge'        => 'operator_panel_coach',
		'record'       => 'operator_panel_record',
		'recording_state' => 'operator_panel_record',
		'registrations_state' => 'operator_panel_view',
		'originate'    => 'operator_panel_originate',
		'intercept'    => 'operator_panel_manage',
		// Conference member actions
		'mute'         => 'operator_panel_manage',
		'unmute'       => 'operator_panel_manage',
		'deaf'         => 'operator_panel_manage',
		'undeaf'       => 'operator_panel_manage',
		'energy'       => 'operator_panel_manage',
		'volume_in'    => 'operator_panel_manage',
		'volume_out'   => 'operator_panel_manage',
		'kick'         => 'operator_panel_hangup',
		// User presence status (own status only, enforced inside handler)
		'user_status'  => 'operator_panel_view',
		// Call-center agent status (supervisor action)
		'agent_status' => 'operator_panel_manage',
	];

	/** @var resource|null Raw TCP socket to FreeSWITCH event socket */
	protected $switch_socket;

	/** @var event_socket|null High-level wrapper around $switch_socket */
	protected $event_socket;

	/** @var array Conference name → display name lookup cache */
	protected $conference_name_cache = [];

	/** @var int Seconds between agent-stats broadcasts */
	protected $agent_stats_interval;

	/** @var string Debug permissions mode: 'off', 'bytes', or 'full' */
	protected $debug_show_permissions_mode;

	/** @var bool Whether to log raw switch events */
	protected $debug_show_switch_event;

	/**
	 * Service name used for WebSocket subscription routing.
	 *
	 * @return string
	 */
	public static function get_service_name(): string {
		return 'active.operator.panel';
	}

	/**
	 * Build a filter chain for the given subscriber.
	 *
	 * Domain isolation is applied to call events.  Conference event keys are
	 * narrowed to conf_event_keys.  Both filters share the same domain guard.
	 *
	 * @param subscriber $subscriber
	 *
	 * @return filter|null
	 */
	public static function create_filter_chain_for(subscriber $subscriber): ?filter {
		if ($subscriber->has_permission('operator_panel_view')) {
			// Accept both the domain-name context (used by sofia/SIP calls) and
			// 'default' (used by feature codes and outbound routes on most installs).
			return filter_chain::and_link([
				new caller_context_filter([$subscriber->get_domain_name(), 'default']),
			]);
		}
		return null;
	}

	/** @override */
	protected static function display_version(): void {
		echo "Operator Panel Service 1.0\n";
	}

	/**
	 * Called once by run() after connecting to the WebSocket server.
	 *
	 * Registers all topic handlers, loads settings, connects to FreeSWITCH, and
	 * starts the agent-stats broadcast timer.
	 *
	 * @return void
	 */
	protected function register_topics(): void {
		// Snapshot requests
		$this->on_topic('extensions_active',   [$this, 'request_extensions_active']);
		$this->on_topic('calls_active',        [$this, 'request_calls_active']);
		$this->on_topic('conferences_active',  [$this, 'request_conferences_active']);
		$this->on_topic('agents_active',       [$this, 'request_agents_active']);
		$this->on_topic('parked_active',       [$this, 'request_parked_active']);
		// Action handler (all mutations)
		$this->on_topic('action',              [$this, 'handle_action']);
		// Keep-alive
		$this->on_topic('ping',                [$this, 'handle_ping']);
		// Debug wildcard
		$this->on_topic('*',                   [$this, 'subscribe_all']);

		$this->reload_settings();
	}

	/**
	 * Re-read configuration and reconnect to FreeSWITCH if needed.
	 *
	 * @return void
	 */
	protected function reload_settings(): void {
		// Ensure reload is idempotent by removing stale listener/timers before re-registering.
		if (!empty($this->switch_socket)) {
			$this->remove_listener($this->switch_socket);
		}
		$this->clear_timers();

		parent::$config->read();

		$database   = database::new(['config' => parent::$config]);
		$settings   = new settings(['database' => $database]);

		$this->agent_stats_interval         = (int)$settings->get('operator_panel', 'agent_stats_interval', 10);
		$this->debug_show_permissions_mode  = $settings->get('operator_panel', 'debug_show_permissions_mode', 'off');
		$this->debug_show_switch_event      = $settings->get('operator_panel', 'debug_show_switch_event', false) === true;

		$this->connect_to_ws_server();

		if ($this->connect_to_switch_server()) {
			$this->register_event_socket_filters();
		} else {
			$this->warning('Failed to connect to switch server — real-time events will not be received');
		}

		if (!empty($this->switch_socket)) {
			$this->add_listener($this->switch_socket, [$this, 'handle_switch_events']);
		}

		// Start the self-rescheduling agent-stats broadcast timer
		if ($this->agent_stats_interval > 0) {
			$this->set_timer($this->agent_stats_interval, [$this, 'broadcast_agent_stats']);
		}
	}

	/**
	 * Connect to the FreeSWITCH event socket, blocking until success.
	 *
	 * @return bool
	 */
	protected function connect_to_switch_server(): bool {
		$host     = parent::$config->get('switch.event_socket.host', '127.0.0.1');
		$port     = (int)parent::$config->get('switch.event_socket.port', 8021);
		$password = parent::$config->get('switch.event_socket.password', 'ClueCon');

		try {
			while (true) {
				$this->switch_socket = stream_socket_client("tcp://$host:$port", $errno, $errstr, 5);
				if ($this->switch_socket) {
					$this->notice("Connected to switch server at $host:$port");
					break;
				}
				sleep(3);
			}
		} catch (\RuntimeException $re) {
			$this->warning('Unable to connect to event socket: ' . $re->getMessage());
			return false;
		}

		stream_set_blocking($this->switch_socket, true);
		$this->event_socket = new event_socket($this->switch_socket);
		$this->event_socket->connect(null, null, $password);
		stream_set_blocking($this->switch_socket, false);

		return $this->event_socket->is_connected();
	}

	/**
	 * Register FreeSWITCH event filters so only relevant events are streamed.
	 *
	 * @return void
	 */
	protected function register_event_socket_filters(): void {
		$this->event_socket->request('event plain all');

		foreach (self::switch_events as $events) {
			foreach ($events as $event_key => $event_name) {
				$this->debug("Requesting event filter [$event_key]=[$event_name]");
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
				$this->info("Filter registered: $response");
			}
		}
	}

	/**
	 * Log when a WebSocket client first makes a connection
	 *
	 * @override
	 */
	protected function on_ws_connected(): void {
		parent::on_ws_connected();
		if ($this->ws_client->is_connected()) {
			$this->info('Registered: ' . self::get_service_name());
		}
	}

	/**
	 * Show a notice when a WebSocket client successfully authenticates
	 *
	 * @override
	 */
	protected function on_ws_authenticated(websocket_message $websocket_message): void {
		$this->notice('WebSocket client authenticated successfully');
	}

	/**
	 * Called by the main run() loop when data arrives on the FreeSWITCH socket.
	 *
	 * @return void
	 */
	protected function handle_switch_events(): void {
		$event = $this->event_socket->read_event();

		if ($event === false || ($this->event_socket === null && $this->switch_socket !== null)) {
			$this->warning('Lost connection to switch server');
			$this->remove_listener($this->switch_socket);
			$this->switch_socket = null;
			$this->event_socket  = null;
			$this->reload_settings();
			return;
		}

		if ($this->debug_show_switch_event) {
			$this->debug('Switch event received');
		}

		// Build a filtered event_message using the call-event key list by default
		$call_filter  = filter_chain::and_link([new event_key_filter(self::call_event_keys)]);
		$event_message = event_message::create_from_switch_event($event, $call_filter);

		// Route by subclass for CUSTOM events (e.g. sofia::register),
		// otherwise fall back to Event-Name / API-Command.
		$event_name = strtolower((string)($event['Event-Name'] ?? $event_message->event_name ?? ''));
		$event_subclass = strtolower((string)($event['Event-Subclass'] ?? ''));
		$api_command = strtolower((string)($event['API-Command'] ?? ''));
		$topic = $event_subclass !== ''
			? $event_subclass
			: ($api_command !== '' ? $api_command : $event_name);
		$event_message->topic = $topic;
		if ($topic === 'sofia::register' || $topic === 'sofia::unregister' || $event_name === 'custom') {
			$this->debug('Registration trace [route]: '
				. 'event_name=' . $event_name
				. ' subclass=' . $event_subclass
				. ' api_command=' . $api_command
				. ' resolved_topic=' . $topic);
		}

		switch ($topic) {
			// Call / Channel events
			case 'channel_create':
			case 'channel_callstate':
			case 'call_update':
			case 'channel_destroy':
			case 'channel_park':
			case 'channel_unpark':
				$this->broadcast_call_event($event_message, $topic);
				break;

			case 'valet_parking::info':
				$this->broadcast_call_event($event_message, 'valet_info');
				break;

			// Conference events (re-filter with conf keys)
			case 'conference':
			case 'conference::maintenance':
				$conf_filter   = filter_chain::and_link([new event_key_filter(self::conf_event_keys)]);
				$conf_message  = event_message::create_from_switch_event($event, $conf_filter);
				$this->on_conference_maintenance($conf_message);
				break;

			// Call-center agent events
			case 'callcenter::maintenance':
				$this->on_callcenter_maintenance($event_message);
				break;

			// Registration events
			case 'sofia::register':
			case 'sofia::unregister':
				$reg_message = event_message::create_from_switch_event($event, null);
				$this->on_registration_event($reg_message, $topic);
				break;

			case 'heartbeat':
				$this->debug('HEARTBEAT');
				break;

			default:
				$this->debug("Unhandled switch event: $topic");
				break;
		}
	}

	/**
	 * Respond to an extensions_active snapshot request.
	 *
	 * Returns all enabled extensions for the subscriber's domain including
	 * their current SIP registration status.  Call state (idle/ringing/active/
	 * held) is intentionally omitted here because the JS client derives it
	 * incrementally from the CHANNEL_* events already flowing over the socket.
	 *
	 * @param websocket_message $message
	 *
	 * @return void
	 */
	protected function request_extensions_active(websocket_message $message): void {
		// Debug information for request handling
		$this->debug('extensions_active snapshot requested');
		$t0 = microtime(true);
		$this->debug('extensions_active trace [step1] begin');

		// Validate input and permissions
		$payload     = $message->payload();
		$domain_name = $payload['domain_name'] ?? '';

		$extensions = [];

		// Query all enabled extensions for this domain
		try {
			$t_db0 = microtime(true);
			$database = database::new(['config' => parent::$config]);

			$sql = "SELECT e.extension_uuid, e.extension, e.effective_caller_id_name, e.effective_caller_id_number, "
				 . "e.description, e.call_group, e.do_not_disturb, e.user_context, e.enabled, "
				 . "COALESCE(vm.voicemail_enabled, false)::text AS voicemail_enabled "
				 . "FROM v_extensions AS e "
				 . "LEFT JOIN v_domains AS d ON e.domain_uuid = d.domain_uuid "
				 . "LEFT JOIN v_voicemails AS vm ON vm.domain_uuid = e.domain_uuid "
				 . "AND vm.voicemail_id = e.extension "
				 . "WHERE d.domain_name = :domain_name AND e.enabled = 'true' "
				 . "ORDER BY e.extension::integer NULLS LAST, e.extension ASC";

			$extensions = $database->select($sql, [':domain_name' => $domain_name], 'all') ?? [];
			$this->debug('extensions_active trace [step2] extensions query done: rows=' . count($extensions)
				. ' elapsed_ms=' . round((microtime(true) - $t_db0) * 1000, 1));
		} catch (\Exception $e) {
			$this->error('Failed to query extensions: ' . $e->getMessage());
		}

		// Best-effort: attach user_uuid and user_status where schema supports extension-user mappings
		// If these tables/views are unavailable on an installation, keep extensions visible
		$user_status_map = [];
		try {
			$t_us0 = microtime(true);
			$sql = "SELECT e.extension, eu.user_uuid, COALESCE(u.user_status, '') AS user_status,"
				 . " ca.contact_attachment_uuid AS contact_image "
				 . "FROM v_extensions AS e "
				 . "LEFT JOIN v_domains AS d ON e.domain_uuid = d.domain_uuid "
				 . "LEFT JOIN ("
				 . "SELECT DISTINCT ON (extension_uuid) extension_uuid, user_uuid "
				 . "FROM v_extension_users ORDER BY extension_uuid"
				 . ") AS eu ON eu.extension_uuid = e.extension_uuid "
				 . "LEFT JOIN v_users AS u ON u.user_uuid = eu.user_uuid "
				 . "LEFT JOIN v_contact_attachments AS ca ON ca.contact_uuid = u.contact_uuid "
				 . "AND ca.attachment_primary = true "
				 . "AND ca.attachment_filename IS NOT NULL "
				 . "AND ca.attachment_content IS NOT NULL "
				 . "WHERE d.domain_name = :domain_name AND e.enabled = 'true'";

			$rows = $database->select($sql, [':domain_name' => $domain_name], 'all');

			// Check for unexpected query result formats and coerce to empty array if needed
			if (!is_array($rows)) {
				$this->debug('extensions_active trace [step3] user-status query returned non-array; coercing to empty array');
				$rows = [];
			}

			// Some extensions may not have a user mapping; skip those rather than dropping the entire list
			foreach ($rows as $row) {
				$ext_num = $row['extension'] ?? '';
				if ($ext_num === '') continue;
				$user_status_map[$ext_num] = [
					'user_uuid'     => $row['user_uuid'] ?? null,
					'user_status'   => $row['user_status'] ?? '',
					'contact_image' => $row['contact_image'] ?? null,
				];
			}

			// Debug logging for user status mapping
			$this->debug('extensions_active trace [step3] user-status query done: rows=' . count($rows)
				. ' mapped=' . count($user_status_map)
				. ' elapsed_ms=' . round((microtime(true) - $t_us0) * 1000, 1));
		} catch (\Exception $e) {
			$this->debug('Could not fetch extension user status mappings: ' . $e->getMessage());
		}

		// Fetch live registration status for all extensions in this domain
		$registered_map = [];
		try {
			$t_reg0 = microtime(true);
			$normalized_domain_name = preg_replace('/:\d+$/', '', (string) $domain_name);
			$this->debug('extensions_active trace [step4] fetching registrations via show registrations as json');
			$reg_json = trim(event_socket::api('show registrations as json'));
			$this->debug('extensions_active trace [step4] registrations api returned: bytes=' . strlen($reg_json)
				. ' elapsed_ms=' . round((microtime(true) - $t_reg0) * 1000, 1));
			$t_parse0 = microtime(true);
			$reg_data = json_decode($reg_json, true);
			if (is_array($reg_data) && !empty($reg_data['rows'])) {
				foreach ($reg_data['rows'] as $row) {
					$ext_num = trim((string) ($row['reg_user'] ?? ''));
					$reg_domain = preg_replace('/:\d+$/', '', trim((string) ($row['realm'] ?? '')));
					if (strpos($ext_num, '@') !== false) {
						[$ext_num, $parsed_domain] = array_pad(explode('@', $ext_num, 2), 2, '');
						$ext_num = trim((string) $ext_num);
						if ($reg_domain === '' && $parsed_domain !== '') {
							$reg_domain = preg_replace('/:\d+$/', '', trim((string) $parsed_domain));
						}
					}
					if ($ext_num !== '' && $reg_domain === $normalized_domain_name) {
						$registered_map[$ext_num] = ($registered_map[$ext_num] ?? 0) + 1;
					}
				}
			}
			$this->debug('extensions_active trace [step5] registrations parsed: rows=' . (is_array($reg_data['rows'] ?? null) ? count($reg_data['rows']) : 0)
				. ' matched_domain=' . count($registered_map)
				. ' parse_elapsed_ms=' . round((microtime(true) - $t_parse0) * 1000, 1));
		} catch (\Exception $e) {
			$this->debug('Could not fetch registrations: ' . $e->getMessage());
		}

		// Annotate each extension with its registration status
		foreach ($extensions as &$ext) {
			$ext_num = $ext['extension'] ?? '';
			$ext['registered']          = isset($registered_map[$ext_num]);
			$ext['registration_count']  = $registered_map[$ext_num] ?? 0;
			$ext['user_uuid']           = $user_status_map[$ext_num]['user_uuid'] ?? null;
			$ext['user_status']         = $user_status_map[$ext_num]['user_status'] ?? '';
			$ext['contact_image']       = $user_status_map[$ext_num]['contact_image'] ?? null;
		}
		unset($ext);
		$this->debug('extensions_active trace [step6] annotation complete: extensions=' . count($extensions));

		// Create the response message
		$response = new websocket_message();
		$response
			->payload($extensions)
			->service_name(self::get_service_name())
			->topic('extensions_active')
			->status_string('ok')
			->status_code(200)
			->request_id($message->request_id())		// Echo back the request_id so the client can correlate in the browser
			->resource_id($message->resource_id())		// Echo back the resource_id so the client can correlate in the browser
		;

		// Send the response back to the requesting subscriber
		websocket_client::send($this->ws_client->socket(), $response);

		// Debug logging for response payload and timing
		$this->debug('extensions_active trace [step7] response sent: rows=' . count($extensions)
			. ' total_elapsed_ms=' . round((microtime(true) - $t0) * 1000, 1));
	}

	/**
	 * Respond to a calls_active snapshot request.
	 *
	 * Returns all current channels from FreeSWITCH as a JSON array.
	 *
	 * @param websocket_message $message
	 *
	 * @return void
	 */
	protected function request_calls_active(websocket_message $message): void {
		$this->debug('calls_active snapshot requested');

		$payload  = $message->payload();
		$domain   = $payload['domain_name'] ?? '';

		$json_str = trim(event_socket::api('show channels as json'));
		$channels_raw = json_decode($json_str, true);
		if (is_array($channels_raw) && isset($channels_raw['rows']) && is_array($channels_raw['rows'])) {
			$rows = $channels_raw['rows'];
		} elseif (is_array($channels_raw)) {
			$rows = $channels_raw;
		} else {
			$rows = [];
		}

		// Map "show channels as json" short field names to the event-style
		// long names that the client JavaScript expects.  This mirrors the
		// mapping performed in active_calls_service::get_active_calls().
		$channels = [];
		foreach ($rows as $call) {
			$mapped = [];
			$mapped['event_name']                    = 'CHANNEL_CALLSTATE';
			$mapped['channel_call_state']            = $call['callstate'] ?? 'ACTIVE';
			// Derive answer_state from the actual callstate
			$cs = strtoupper($call['callstate'] ?? '');
			if ($cs === 'RINGING' || $cs === 'EARLY') {
				$mapped['answer_state'] = 'ringing';
			} elseif ($cs === 'HELD') {
				$mapped['answer_state'] = 'held';
			} else {
				$mapped['answer_state'] = 'answered';
			}
			$mapped['unique_id']                     = $call['uuid'] ?? '';
			$mapped['call_direction']                = $call['direction'] ?? '';
			$mapped['caller_channel_created_time']   = strval(intval($call['created_epoch'] ?? 0) * 1000000);
			$mapped['channel_read_codec_name']       = $call['read_codec'] ?? '';
			$mapped['channel_read_codec_rate']       = $call['read_rate'] ?? '';
			$mapped['channel_write_codec_name']      = $call['write_codec'] ?? '';
			$mapped['channel_write_codec_rate']      = $call['write_rate'] ?? '';
			$mapped['caller_channel_name']           = $call['name'] ?? '';
			$mapped['caller_context']                = $call['context'] ?? '';
			$mapped['channel_presence_id']           = $call['presence_id'] ?? '';
			$mapped['caller_caller_id_name']         = $call['initial_cid_name'] ?? ($call['cid_name'] ?? '');
			$mapped['caller_caller_id_number']       = $call['initial_cid_num'] ?? ($call['cid_num'] ?? '');
			$mapped['caller_destination_number']     = $call['initial_dest'] ?? ($call['dest'] ?? '');
			$mapped['application']                   = $call['application'] ?? '';
			$mapped['secure']                        = $call['secure'] ?? '';
			$channels[] = $mapped;
		}

		// Filter to requesting subscriber's domain when provided.
		// Accept both the FQDN context (sofia/SIP calls) and 'default'
		// (feature codes, outbound routes) so all calls are visible.
		if (!empty($domain) && !empty($channels)) {
			$channels = array_filter($channels, function ($ch) use ($domain) {
				$context  = $ch['caller_context'] ?? '';
				$presence = $ch['channel_presence_id'] ?? '';

				// Some channel snapshots omit context; do not drop those rows.
				if ($context === '') {
					return true;
				}

				if ($context === $domain || $context === 'default') {
					return true;
				}

				return strpos((string) $presence, '@' . $domain) !== false;
			});
			$channels = array_values($channels);
		}

		$response = new websocket_message();
		$response
			->payload($channels ?? [])
			->service_name(self::get_service_name())
			->topic('calls_active')
			->status_string('ok')
			->status_code(200)
			->request_id($message->request_id())
			->resource_id($message->resource_id())
		;

		websocket_client::send($this->ws_client->socket(), $response);
	}

	/**
	 * Respond to a parked_active snapshot request.
	 *
	 * Uses valet_info to enumerate current parked slots for the subscriber's domain
	 * and enriches each parked UUID with caller details.
	 *
	 * @param websocket_message $message
	 *
	 * @return void
	 */
	protected function request_parked_active(websocket_message $message): void {
		$this->debug('parked_active snapshot requested');

		$payload = $message->payload();
		$domain_name = $payload['domain_name'] ?? '';
		$parked = [];

		if ($domain_name !== '') {
			$valet_info = event_socket::api('valet_info park@' . $domain_name);
			if ($valet_info !== false && is_string($valet_info)) {
				$matches = [];
				preg_match_all('/<extension uuid="(.*?)">(.*?)<\/extension>/s', $valet_info, $matches, PREG_SET_ORDER);
				foreach ($matches as $row) {
					$uuid = $row[1] ?? '';
					$extension = trim((string)($row[2] ?? ''));
					if ($uuid === '' || $extension === '') continue;

					$caller_name = trim((string)event_socket::api('uuid_getvar ' . $uuid . ' caller_id_name'));
					if ($caller_name === '_undef_') $caller_name = '';
					$caller_number = trim((string)event_socket::api('uuid_getvar ' . $uuid . ' caller_id_number'));
					if ($caller_number === '_undef_') $caller_number = '';
					$parked_by = trim((string)event_socket::api('uuid_getvar ' . $uuid . ' referred_by_user'));
					if ($parked_by === '' || $parked_by === '_undef_') {
						$parked_by = trim((string)event_socket::api('uuid_getvar ' . $uuid . ' valet_parking_orbit_exten'));
					}
					if ($parked_by === '_undef_') $parked_by = '';
					$original_destination = trim((string)event_socket::api('uuid_getvar ' . $uuid . ' destination_number'));
					if ($original_destination === '_undef_') $original_destination = '';
					$created_epoch = trim((string)event_socket::api('uuid_getvar ' . $uuid . ' caller_channel_created_time'));
					if ($created_epoch === '' || $created_epoch === '_undef_') {
						// Fall back to start_uepoch (microseconds) or start_epoch (seconds)
						$created_epoch = trim((string)event_socket::api('uuid_getvar ' . $uuid . ' start_uepoch'));
						if ($created_epoch === '' || $created_epoch === '_undef_') {
							$created_epoch = trim((string)event_socket::api('uuid_getvar ' . $uuid . ' start_epoch'));
							if ($created_epoch === '_undef_') $created_epoch = '';
						}
					}

					$parked[] = [
						'unique_id' => $uuid,
						'event_name' => 'valet_parking::snapshot',
						'action' => 'hold',
						'valet_extension' => $extension,
						'caller_caller_id_name' => $caller_name,
						'caller_caller_id_number' => $caller_number,
						'caller_destination_number' => $original_destination,
						'variable_referred_by_user' => $parked_by,
						'caller_channel_created_time' => $created_epoch,
					];
				}
			}
		}

		$response = new websocket_message();
		$response
			->payload($parked)
			->service_name(self::get_service_name())
			->topic('parked_active')
			->status_string('ok')
			->status_code(200)
			->request_id($message->request_id())
			->resource_id($message->resource_id())
		;

		websocket_client::send($this->ws_client->socket(), $response);
	}

	/**
	 * Respond to a conferences_active snapshot request.
	 *
	 * @param websocket_message $message
	 *
	 * @return void
	 */
	protected function request_conferences_active(websocket_message $message): void {
		$this->debug('conferences_active snapshot requested');

		$payload     = $message->payload();
		$domain_name = $payload['domain_name'] ?? '';

		$json_str   = trim(event_socket::api('conference json_list'));
		$conferences = json_decode($json_str, true);

		if (is_array($conferences)) {
			foreach ($conferences as &$conf) {
				$conf_name = $conf['conference_name'] ?? '';
				if (!empty($conf_name)) {
					$conf['conference_display_name'] = $this->lookup_conference_display_name($conf_name);
				}
			}
			unset($conf);

			if (!empty($domain_name)) {
				$conferences = array_filter($conferences, function ($c) use ($domain_name) {
					return strpos($c['conference_name'] ?? '', '@' . $domain_name) !== false;
				});
				$conferences = array_values($conferences);
			}
		}

		$response = new websocket_message();
		$response
			->payload($conferences ?? [])
			->service_name(self::get_service_name())
			->topic('conferences_active')
			->status_string('ok')
			->status_code(200)
			->request_id($message->request_id())
			->resource_id($message->resource_id())
		;

		websocket_client::send($this->ws_client->socket(), $response);
	}

	/**
	 * Respond to an agents_active snapshot request.
	 *
	 * The payload is shaped by {@see operator_panel_agent_filter} according to
	 * the subscriber's role (supervisor vs. regular agent).
	 *
	 * @param websocket_message $message
	 *
	 * @return void
	 */
	protected function request_agents_active(websocket_message $message): void {
		$this->debug('agents_active snapshot requested');

		$payload     = $message->payload();
		$domain_name = $payload['domain_name'] ?? '';
		$permissions = $message->get_permissions();

		$agents = $this->get_all_agent_stats($domain_name);

		$is_supervisor = !empty($permissions['operator_panel_manage']);
		$agent_name    = $this->get_agent_name_for_permission($permissions, $domain_name);
		$filter        = new operator_panel_agent_filter($is_supervisor, $agent_name);
		$filtered      = $filter->filter($agents);

		$response = new websocket_message();
		$response
			->payload($filtered)
			->service_name(self::get_service_name())
			->topic('agents_active')
			->status_string('ok')
			->status_code(200)
			->request_id($message->request_id())
			->resource_id($message->resource_id())
		;

		websocket_client::send($this->ws_client->socket(), $response);
	}

	/**
	 * Handle all client-initiated mutations over WebSocket.
	 *
	 * Validates the required permission from {@see self::permission_map}, then
	 * executes the corresponding FreeSWITCH API command or database update.
	 *
	 * @param websocket_message $message
	 *
	 * @return void
	 */
	protected function handle_action(websocket_message $message): void {
		$payload     = $message->payload();
		$action      = $payload['action']      ?? '';
		$uuid        = $payload['uuid']        ?? '';
		$permissions = $message->get_permissions();

		$this->debug("Action request: $action");

		// Debug permissions logging
		if ($this->debug_show_permissions_mode === 'full') {
			$this->debug("Permission check — action: $action, required: " . (self::permission_map[$action] ?? 'unknown'));
			$this->debug("Permissions: " . json_encode($permissions));
		} elseif ($this->debug_show_permissions_mode === 'bytes') {
			$this->debug("Permissions: " . count($permissions) . " items, " . strlen(json_encode($permissions)) . " bytes");
		}

		// Validate action name
		if (!array_key_exists($action, self::permission_map)) {
			$this->send_action_response($message, false, 'Invalid action: ' . $action);
			return;
		}

		// Check permission
		$required = self::permission_map[$action];
		if (empty($permissions[$required])) {
			$this->warning("Permission denied: $required for action: $action");
			$this->send_action_response($message, false, 'Permission denied');
			return;
		}

		$result = $this->execute_action($action, $payload);
		$status_message = isset($result['message']) ? (string)$result['message'] : '';
		$success = (bool)($result['success'] ?? false);
		$extra_payload = $result;
		unset($extra_payload['success'], $extra_payload['message']);
		$this->send_action_response($message, $success, $status_message, $extra_payload);
	}

	/**
	 * Execute a validated action.
	 *
	 * @param string $action  Action name (e.g. 'hangup', 'transfer', 'eavesdrop', etc.)
	 * @param array  $payload Full request payload from the client.
	 *
	 * @return array  ['success' => bool, 'message' => string]
	 */
	private function execute_action(string $action, array $payload): array {
		$uuid = $payload['uuid'] ?? '';
		$destination = $payload['destination'] ?? '';
		$domain_name = $payload['domain_name'] ?? '';
		$context = $payload['context'] ?? ($domain_name !== '' ? $domain_name : 'default');
		$conference_name = html_entity_decode(urldecode($payload['conference_name'] ?? ''));
		$member_id = $payload['member_id'] ?? '';
		$direction = $payload['direction'] ?? '';

		// Debug action execution attempt with all relevant parameters
		$this->debug("Executing action: $action, uuid: $uuid, destination: $destination, context: $context, conference_name: $conference_name, member_id: $member_id, direction: $direction");

		try {
			switch ($action) {
				case 'hangup':
					if (empty($uuid)) {
						return ['success' => false, 'message' => 'UUID required'];
					}
					event_socket::api("uuid_kill $uuid");
					return ['success' => true, 'message' => 'Call terminated'];

				case 'hangup_caller':
					if (empty($uuid)) {
						return ['success' => false, 'message' => 'UUID required'];
					}
					// Find the A-leg (caller) from the B-leg UUID
					$a_leg = trim((string)event_socket::api("uuid_getvar $uuid other_leg_unique_id"));
					if (empty($a_leg) || stripos($a_leg, '-ERR') !== false || $a_leg === '_undef_') {
						$a_leg = trim((string)event_socket::api("uuid_getvar $uuid signal_bond"));
					}
					if (!empty($a_leg) && stripos($a_leg, '-ERR') === false && $a_leg !== '_undef_') {
						event_socket::api("uuid_kill $a_leg");
						$this->info("Hangup caller: killed A-leg $a_leg (B-leg was $uuid)");
						return ['success' => true, 'message' => 'Caller terminated'];
					}
					// Fallback: kill the provided UUID
					event_socket::api("uuid_kill $uuid");
					return ['success' => true, 'message' => 'Call terminated'];

				case 'transfer':
					if (empty($uuid) || empty($destination)) {
						return ['success' => false, 'message' => 'UUID and destination required'];
					}
					if (!preg_match('/^[0-9*#+]+$/', $destination)) {
						return ['success' => false, 'message' => 'Invalid destination'];
					}
					$bleg = !empty($payload['bleg']) ? '-bleg ' : '';
					event_socket::api("uuid_transfer $uuid {$bleg}$destination XML $context");
					return ['success' => true, 'message' => 'Call transferred'];

				case 'transfer_attended':
					if (empty($uuid) || empty($destination)) {
						return ['success' => false, 'message' => 'UUID and destination required'];
					}
					if (!preg_match('/^[0-9*#+]+$/', $destination)) {
						return ['success' => false, 'message' => 'Invalid destination'];
					}
					$xfer_domain = $domain_name !== '' ? $domain_name : $context;
					$reply = trim((string)event_socket::api("uuid_broadcast $uuid att_xfer::user/$destination@$xfer_domain both"));
					$this->debug("transfer_attended reply: $reply");
					if (stripos($reply, '-ERR') !== false) {
						return ['success' => false, 'message' => $reply];
					}
					return ['success' => true, 'message' => 'Attended transfer started'];
				case 'transfer_attended_complete':
					// Attended transfer — Step 2: Complete the transfer
					// Bridge the parked caller to the destination (other side of operator's current call).
					$parked_uuid = $payload['parked_uuid'] ?? '';
					$operator_uuid_val = $payload['operator_uuid'] ?? '';
					if (empty($parked_uuid) || empty($operator_uuid_val)) {
						return ['success' => false, 'message' => 'parked_uuid and operator_uuid required'];
					}

					// Find the destination's channel (other side of operator's consultation call)
					$dest_uuid = trim((string)event_socket::api("uuid_getvar $operator_uuid_val other_leg_unique_id"));
					if (empty($dest_uuid) || stripos($dest_uuid, '-ERR') !== false || $dest_uuid === '_undef_') {
						$dest_uuid = trim((string)event_socket::api("uuid_getvar $operator_uuid_val signal_bond"));
					}

					if (!empty($dest_uuid) && stripos($dest_uuid, '-ERR') === false && $dest_uuid !== '_undef_') {
						// Destination answered — bridge parked caller to destination
						event_socket::api("uuid_bridge $parked_uuid $dest_uuid");
						// Disconnect operator cleanly
						event_socket::api("uuid_kill $operator_uuid_val");
						$this->info("Attended transfer complete: bridged $parked_uuid to $dest_uuid");
						return ['success' => true, 'message' => 'Transfer completed'];
					}

					// Destination not yet answered — blind-transfer the parked caller instead
					$dest_ext = $payload['destination'] ?? '';
					if (!empty($dest_ext) && preg_match('/^[0-9*#+]+$/', $dest_ext)) {
						event_socket::api("uuid_kill $operator_uuid_val");
						$xfer_context = $payload['context'] ?? ($domain_name !== '' ? $domain_name : 'default');
						event_socket::api("uuid_transfer $parked_uuid $dest_ext XML $xfer_context");
						$this->info("Attended transfer complete (dest not answered): blind-transferred $parked_uuid to $dest_ext");
						return ['success' => true, 'message' => 'Transfer completed (destination still ringing)'];
					}

					return ['success' => false, 'message' => 'Cannot find destination channel'];

				case 'transfer_attended_cancel':
					// Attended transfer — Cancel: hang up consultation, reconnect caller to operator
					$parked_uuid = $payload['parked_uuid'] ?? '';
					$operator_uuid_val = $payload['operator_uuid'] ?? '';
					$source_ext = $payload['source_extension'] ?? '';
					if (empty($parked_uuid)) {
						return ['success' => false, 'message' => 'parked_uuid required'];
					}

					// Kill the operator's consultation call (also terminates the destination leg)
					if (!empty($operator_uuid_val)) {
						event_socket::api("uuid_kill $operator_uuid_val");
					}

					// Reconnect the parked caller back to the operator's extension
					if (!empty($source_ext) && !empty($domain_name)) {
						$originate_cmd = "bgapi originate user/{$source_ext}@{$domain_name} &bridge({$parked_uuid})";
						$reply = trim((string)event_socket::api($originate_cmd));
						if (stripos($reply, '-ERR') === false) {
							$this->info("Attended transfer cancelled: reconnecting $parked_uuid via $source_ext");
							return ['success' => true, 'message' => 'Transfer cancelled, reconnecting'];
						}
					}

					// Fallback: could not reconnect — just kill the parked call
					event_socket::api("uuid_kill $parked_uuid");
					$this->warning("Attended transfer cancelled: could not reconnect, killed parked call $parked_uuid");
					return ['success' => true, 'message' => 'Transfer cancelled'];

				case 'eavesdrop':
					if (empty($uuid) || empty($destination)) {
						return ['success' => false, 'message' => 'UUID and destination extension required'];
					}
					$dest_ext = $payload['destination_extension'] ?? $destination;
					if (!preg_match('/^[0-9*#+]+$/', $dest_ext)) {
						return ['success' => false, 'message' => 'Invalid destination extension'];
					}
					if (empty($domain_name)) {
						return ['success' => false, 'message' => 'domain_name required'];
					}

					$api_cmd = "bgapi originate {origination_caller_id_name=eavesdrop,origination_caller_id_number=$dest_ext}user/$dest_ext@$domain_name &eavesdrop($uuid)";
					$reply = trim((string)event_socket::api($api_cmd));
					$this->info("Eavesdrop request: uuid=$uuid destination=$dest_ext@$domain_name");
					$this->debug("Eavesdrop command reply: $reply");

					if (stripos($reply, '-ERR') !== false) {
						return ['success' => false, 'message' => $reply];
					}

					return ['success' => true, 'message' => 'Eavesdrop started'];

				case 'whisper':
				case 'barge':
					if (empty($uuid) || empty($destination)) {
						return ['success' => false, 'message' => 'UUID and destination extension required'];
					}
					$dest_ext = $payload['destination_extension'] ?? $destination;
					if (!preg_match('/^[0-9*#+]+$/', $dest_ext)) {
						return ['success' => false, 'message' => 'Invalid destination extension'];
					}
					if (empty($domain_name)) {
						return ['success' => false, 'message' => 'domain_name required'];
					}

					$mode_token = $action === 'whisper' ? 'whisper' : 'barge';
					$api_cmd = "bgapi originate {origination_caller_id_name=$mode_token,origination_caller_id_number=$dest_ext}user/$dest_ext@$domain_name &eavesdrop($uuid $mode_token)";
					$reply = trim((string)event_socket::api($api_cmd));
					$this->info(ucfirst($action) . " request: uuid=$uuid destination=$dest_ext@$domain_name");
					$this->debug(ucfirst($action) . " command reply: $reply");

					if (stripos($reply, '-ERR') !== false) {
						return ['success' => false, 'message' => $reply];
					}

					return ['success' => true, 'message' => ucfirst($action) . ' started'];

				case 'record':
					if (empty($uuid)) {
						return ['success' => false, 'message' => 'UUID required'];
					}
					$stop = !empty($payload['stop']) && ($payload['stop'] === true || $payload['stop'] === 'true' || $payload['stop'] === 1 || $payload['stop'] === '1');
					if ($stop) {
						$reply = trim((string) event_socket::api("uuid_record $uuid stop"));
						if (stripos($reply, '-ERR') !== false) {
							return ['success' => false, 'message' => $reply];
						}
						$this->info("Recording stopped: uuid=$uuid");
						return ['success' => true, 'message' => 'Recording stopped'];
					}
					$recordings_path = event_socket::api('global_getvar recordings_dir');
					$recordings_path = trim($recordings_path ?: '/var/lib/freeswitch/recordings');
					if (!empty($domain_name)) {
						$recordings_path .= '/' . $domain_name;
					}
					$recordings_path .= '/archive/' . date('Y') . '/' . date('M') . '/' . date('d');
					$file = $recordings_path . '/' . $uuid . '.wav';
					$reply = trim((string) event_socket::api("uuid_record $uuid start $file"));
					if (stripos($reply, '-ERR') !== false) {
						return ['success' => false, 'message' => $reply];
					}
					$this->info("Recording started: uuid=$uuid file=$file");
					return ['success' => true, 'message' => 'Recording started'];

				case 'recording_state':
					$uuids = $payload['uuids'] ?? [];
					if (!is_array($uuids)) {
						return ['success' => false, 'message' => 'uuids must be an array'];
					}

					$states = [];
					foreach ($uuids as $id) {
						$id = trim((string) $id);
						if ($id === '' || !preg_match('/^[a-f0-9-]{32,36}$/i', $id)) {
							continue;
						}

						$buglist = trim((string) event_socket::api("uuid_buglist $id"));
						$lower = strtolower($buglist);
						$is_recording = (
							strpos($lower, 'record') !== false
							&& strpos($lower, '-err') === false
							&& strpos($lower, 'no bugs') === false
						);
						$states[$id] = $is_recording;
					}

					return ['success' => true, 'message' => 'Recording state updated', 'states' => $states];

				case 'registrations_state':
					if (empty($domain_name)) {
						return ['success' => false, 'message' => 'domain_name required'];
					}
					$normalized_domain_name = preg_replace('/:\d+$/', '', (string) $domain_name);
					$states = [];
					$reg_json = trim((string) event_socket::api('show registrations as json'));
					$reg_data = json_decode($reg_json, true);
					if (is_array($reg_data) && !empty($reg_data['rows']) && is_array($reg_data['rows'])) {
						foreach ($reg_data['rows'] as $row) {
							$ext_num = trim((string) ($row['reg_user'] ?? ''));
							$reg_domain = preg_replace('/:\d+$/', '', trim((string) ($row['realm'] ?? '')));
							if (strpos($ext_num, '@') !== false) {
								[$ext_num, $parsed_domain] = array_pad(explode('@', $ext_num, 2), 2, '');
								$ext_num = trim((string) $ext_num);
								if ($reg_domain === '' && $parsed_domain !== '') {
									$reg_domain = preg_replace('/:\d+$/', '', trim((string) $parsed_domain));
								}
							}
							if ($ext_num === '' || $reg_domain !== $normalized_domain_name) continue;
							$states[$ext_num] = ($states[$ext_num] ?? 0) + 1;
						}
					}
					return ['success' => true, 'message' => 'Registrations state updated', 'states' => $states];

				case 'mute':
				case 'unmute':
					if (empty($conference_name) || strpos($conference_name, '@') === false) {
						return ['success' => false, 'message' => 'Invalid conference name'];
					}
					if (empty($member_id)) {
						return ['success' => false, 'message' => 'Member ID required'];
					}
					event_socket::api("conference '$conference_name' $action $member_id");
					if (!empty($uuid)) {
						event_socket::api("uuid_setvar $uuid hand_raised false");
					}
					return ['success' => true, 'message' => 'Conference member updated'];

				case 'deaf':
				case 'undeaf':
					if (empty($conference_name) || strpos($conference_name, '@') === false) {
						return ['success' => false, 'message' => 'Invalid conference name'];
					}
					if (empty($member_id)) {
						return ['success' => false, 'message' => 'Member ID required'];
					}
					event_socket::api("conference '$conference_name' $action $member_id");
					return ['success' => true, 'message' => 'Conference member updated'];

				case 'kick':
					if (empty($uuid)) {
						return ['success' => false, 'message' => 'UUID required'];
					}
					event_socket::api("uuid_kill $uuid");
					return ['success' => true, 'message' => 'Conference member removed'];

				case 'energy':
					if (empty($conference_name) || strpos($conference_name, '@') === false) {
						return ['success' => false, 'message' => 'Invalid conference name'];
					}
					if (empty($member_id) || empty($direction)) {
						return ['success' => false, 'message' => 'Member ID and direction required'];
					}
					$current = trim((string) event_socket::api("conference '$conference_name' energy $member_id"));
					if (preg_match('/=(\d+)/', $current, $matches)) {
						$value = (int) $matches[1];
						$value = ($direction === 'up') ? $value + 100 : $value - 100;
						event_socket::api("conference '$conference_name' energy $member_id $value");
					}
					return ['success' => true, 'message' => 'Energy updated'];

				case 'volume_in':
				case 'volume_out':
					if (empty($conference_name) || strpos($conference_name, '@') === false) {
						return ['success' => false, 'message' => 'Invalid conference name'];
					}
					if (empty($member_id) || empty($direction)) {
						return ['success' => false, 'message' => 'Member ID and direction required'];
					}
					$current = trim((string) event_socket::api("conference '$conference_name' $action $member_id"));
					if (preg_match('/=(-?\d+)/', $current, $matches)) {
						$value = (int) $matches[1];
						$value = ($direction === 'up') ? $value + 1 : $value - 1;
						event_socket::api("conference '$conference_name' $action $member_id $value");
					}
					return ['success' => true, 'message' => 'Volume updated'];

				case 'intercept':
					if (empty($uuid) || empty($destination)) {
						return ['success' => false, 'message' => 'UUID and destination extension required'];
					}
					$dest_ext = $payload['destination_extension'] ?? $destination;
					if (!preg_match('/^[0-9*#+]+$/', $dest_ext)) {
						return ['success' => false, 'message' => 'Invalid destination extension'];
					}
					if (empty($domain_name)) {
						return ['success' => false, 'message' => 'domain_name required'];
					}
					// Find the A-leg (caller) by querying the B-leg's other_leg_unique_id
					$a_leg = trim((string)event_socket::api("uuid_getvar $uuid other_leg_unique_id"));
					if (empty($a_leg) || stripos($a_leg, '-ERR') !== false || $a_leg === '_undef_') {
						$a_leg = trim((string)event_socket::api("uuid_getvar $uuid bridge_uuid"));
					}
					if (empty($a_leg) || stripos($a_leg, '-ERR') !== false || $a_leg === '_undef_') {
						$a_leg = trim((string)event_socket::api("uuid_getvar $uuid signal_bond"));
					}
					if (!empty($a_leg) && stripos($a_leg, '-ERR') === false && $a_leg !== '_undef_') {
						// Transfer the A-leg to the interceptor's extension
						$reply = trim((string)event_socket::api("uuid_transfer $a_leg $dest_ext XML $context"));
						$this->info("Intercept via A-leg transfer: a_leg=$a_leg dest=$dest_ext@$domain_name");
					} else {
						// Last resort: transfer the B-leg itself to the interceptor
						$reply = trim((string)event_socket::api("uuid_transfer $uuid $dest_ext XML $context"));
						$this->info("Intercept via B-leg transfer: uuid=$uuid dest=$dest_ext@$domain_name");
					}
					$this->debug("Intercept command reply: $reply");
					if (stripos($reply, '-ERR') !== false) {
						return ['success' => false, 'message' => $reply];
					}
					return ['success' => true, 'message' => 'Call intercepted'];

				case 'originate':
					$source      = $payload['source']      ?? '';
					$dest        = $payload['destination'] ?? '';
					if (empty($source) || empty($dest)) {
						return ['success' => false, 'message' => 'Source and destination required'];
					}
					// Sanitize source and destination: digits, *, #, + only
					if (!preg_match('/^[0-9*#+]+$/', $source) || !preg_match('/^[0-9*#+]+$/', $dest)) {
						return ['success' => false, 'message' => 'Invalid source or destination'];
					}
					// Prevent self-calls
					if ($source === $dest) {
						return ['success' => false, 'message' => 'Cannot call self'];
					}

					// Look up the source extension's user_context for correct dialplan routing
					$originate_context = $context;
					try {
						$database = database::new(['config' => parent::$config]);
						$rows = $database->select(
							"SELECT e.user_context FROM v_extensions AS e "
							. "LEFT JOIN v_domains AS d ON e.domain_uuid = d.domain_uuid "
							. "WHERE d.domain_name = :domain_name AND e.extension = :extension AND e.enabled = 'true' LIMIT 1",
							[':domain_name' => $domain_name, ':extension' => $source],
							'all'
						);
						if (!empty($rows[0]['user_context'])) {
							$originate_context = $rows[0]['user_context'];
						}
					} catch (\Exception $e) {
						$this->debug('Could not look up user_context for originate: ' . $e->getMessage());
					}

					// The destination gets routed through the extension's dialplan context
					$originate_cmd = "originate {sip_auto_answer=true,origination_caller_id_number=$source}user/$source@$domain_name $dest XML $originate_context";

					// Log the originate command attempt
					$this->debug("Originate: from=$source to=$dest domain=$domain_name context=$originate_context cmd=$originate_cmd");

					$fs_response = event_socket::api($originate_cmd);

					// Log the response from FreeSWITCH
					$this->debug("Originate response: " . json_encode($fs_response));

					if ($fs_response === false) {
						$this->error("Failed to send originate command to FreeSWITCH");
						return ['success' => false, 'message' => 'Failed to send originate command to FreeSWITCH'];
					}
					if (is_array($fs_response) && isset($fs_response['-ERR'])) {
						$this->error("Originate error: " . $fs_response['-ERR']);
						return ['success' => false, 'message' => 'FreeSWITCH error: ' . $fs_response['-ERR']];
					}

					return ['success' => true, 'message' => 'Call originated', 'fs_response' => $fs_response];

				case 'user_status':
					$status       = $payload['status']    ?? '';
					$user_uuid    = $payload['user_uuid'] ?? '';
					$allowed = ['Available', 'Available (On Demand)', 'On Break', 'Do Not Disturb', 'Logged Out'];
					if (!in_array($status, $allowed, true)) {
						return ['success' => false, 'message' => 'Invalid status value'];
					}
					if (empty($user_uuid)) {
						return ['success' => false, 'message' => 'user_uuid required'];
					}
					$database = database::new(['config' => parent::$config]);
					$database->execute(
						"UPDATE v_users SET user_status = :status WHERE user_uuid = :user_uuid",
						[':status' => $status, ':user_uuid' => $user_uuid]
					);
					return ['success' => true, 'message' => 'Status updated'];

				case 'agent_status':
					$agent_name  = $payload['agent_name'] ?? '';
					$status      = $payload['status']     ?? '';
					$allowed_cc  = ['Available', 'Available (On Demand)', 'On Break', 'Do Not Disturb', 'Logged Out'];
					if (empty($agent_name) || !in_array($status, $allowed_cc, true)) {
						return ['success' => false, 'message' => 'agent_name and valid status required'];
					}
					// Sanitize agent name
					if (!preg_match('/^[a-zA-Z0-9@._\-]+$/', $agent_name)) {
						return ['success' => false, 'message' => 'Invalid agent name'];
					}
					event_socket::api("callcenter_config agent set status $agent_name '$status'");
					// Also update the database record for persistence
					$database = database::new(['config' => parent::$config]);
					$database->execute(
						"UPDATE v_call_center_agents SET agent_status = :status WHERE agent_name = :agent_name",
						[':status' => $status, ':agent_name' => $agent_name]
					);
					return ['success' => true, 'message' => 'Agent status updated'];

				default:
					return ['success' => false, 'message' => 'Unknown action'];
			}
		} catch (\Exception $e) {
			$this->error('Action failed: ' . $e->getMessage());
			return ['success' => false, 'message' => $e->getMessage()];
		}
	}

	/**
	 * Respond to a ping with a pong.
	 *
	 * @param websocket_message $message
	 *
	 * @return void
	 */
	protected function handle_ping(websocket_message $message): void {
		$this->debug('Ping received — sending pong');

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

		websocket_client::send($this->ws_client->socket(), $response);
	}

	/**
	 * Wildcard subscription — acknowledges receipt for debugging purposes.
	 *
	 * @param websocket_message $message
	 *
	 * @return void
	 */
	protected function subscribe_all(websocket_message $message): void {
		$this->debug('Wildcard subscription requested');

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

		websocket_client::send($this->ws_client->socket(), $response);
	}

	/**
	 * Collect agent statistics from FreeSWITCH and broadcast to all subscribers.
	 *
	 * Called by the timer loop.  Returns the interval in seconds so that
	 * {@see base_websocket_system_service::run()} auto-reschedules the timer.
	 *
	 * @return int  Seconds until next broadcast (self::$agent_stats_interval).
	 */
	public function broadcast_agent_stats(): int {
		$this->debug('Broadcasting agent stats');

		// Retrieve all queues from the database (we need the domain for context)
		$agents = $this->get_all_agent_stats();

		if (empty($agents)) {
			return $this->agent_stats_interval;
		}

		$message = new websocket_message();
		$message
			->service_name(self::get_service_name())
			->topic('agent_stats')
			->payload($agents)
		;

		websocket_client::send($this->ws_client->socket(), $message);

		// Return the interval so the timer reschedules itself
		return $this->agent_stats_interval;
	}

	/**
	 * Retrieve live agent statistics for all call-center queues.
	 *
	 * Queries the FreeSWITCH callcenter_config command for each queue found
	 * in the database, then merges the live stats into a unified array.
	 *
	 * @param string $domain_name  Optional domain to scope the query.
	 *
	 * @return array  Array of agent stat rows.
	 */
	private function get_all_agent_stats(string $domain_name = ''): array {
		try {
			$database = database::new(['config' => parent::$config]);

			$sql = "SELECT q.queue_extension, q.queue_name, d.domain_name "
				 . "FROM v_call_center_queues AS q "
				 . "LEFT JOIN v_domains AS d ON q.domain_uuid = d.domain_uuid ";
			$params = [];
			if (!empty($domain_name)) {
				$sql .= "WHERE d.domain_name = :domain_name ";
				$params[':domain_name'] = $domain_name;
			}
			$queues = $database->select($sql, $params, 'all');
		} catch (\Exception $e) {
			$this->error('Failed to query queues: ' . $e->getMessage());
			return [];
		}

		if (empty($queues)) {
			return [];
		}

		$all_agents = [];
		foreach ($queues as $queue) {
			$ext    = $queue['queue_extension'] ?? '';
			$domain = $queue['domain_name']     ?? '';

			if (empty($ext) || empty($domain)) {
				continue;
			}

			$raw = event_socket::api("callcenter_config queue list agents $ext@$domain");
			if (empty($raw) || strpos($raw, '-ERR') === 0) {
				continue;
			}

			// Parse pipe-delimited output: name|status|state|uuid|contact|...
			foreach (explode("\n", trim($raw)) as $line) {
				$line = trim($line);
				if (empty($line) || strpos($line, 'name|') === 0) {
					continue; // skip header
				}
				$parts = explode('|', $line);
				if (count($parts) < 16) {
					continue;
				}
				$all_agents[] = [
					'agent_name'          => $parts[0],
					'status'              => $parts[1],
					'state'               => $parts[2],
					'uuid'                => $parts[3],
					'contact'             => $parts[4],
					'max_no_answer'       => $parts[5],
					'wrap_up_time'        => $parts[6],
					'reject_delay_time'   => $parts[7],
					'busy_delay_time'     => $parts[8],
					'last_bridge_start'   => $parts[9],
					'last_bridge_end'     => $parts[10],
					'last_status_change'  => $parts[11],
					'no_answer_count'     => $parts[12],
					'calls_answered'      => $parts[13],
					'talk_time'           => $parts[14],
					'ready_time'          => $parts[15],
					'queue_name'          => $queue['queue_name'] ?? '',
					'queue_extension'     => $ext,
					'domain_name'         => $domain,
				];
			}
		}

		return $all_agents;
	}

	/**
	 * Process a conference::maintenance event and broadcast to subscribers.
	 *
	 * @param event_message $event_message
	 *
	 * @return void
	 */
	private function on_conference_maintenance(event_message $event_message): void {
		$action = str_replace('-', '_', $event_message->action ?? '');
		$conference_name = $this->extract_conference_name($event_message);

		switch ($action) {
			case 'start_talking':
			case 'stop_talking':
			case 'mute_member':
			case 'unmute_member':
			case 'deaf_member':
			case 'undeaf_member':
			case 'floor_change':
			case 'conference_destroy':
			case 'lock':
			case 'unlock':
			case 'kick_member':
			case 'energy_level':
			case 'gain_level':
			case 'volume_level':
				$this->broadcast_conference_event($event_message, $action, $conference_name);
				break;

			case 'add_member':
				$enriched = $this->enrich_member_event($event_message, $conference_name);
				$this->broadcast_enriched_event($enriched, $action);
				break;

			case 'del_member':
				$enriched = $this->enrich_del_member_event($event_message, $conference_name);
				$this->broadcast_enriched_event($enriched, $action);
				break;

			case 'conference_create':
				$enriched = $this->enrich_conference_create_event($event_message, $conference_name);
				$this->broadcast_enriched_event($enriched, $action);
				break;

			default:
				$this->debug("Unknown conference action: $action");
				break;
		}
	}

	/**
	 * Trigger an immediate agent stats broadcast when a call-center maintenance
	 * event is received (e.g. agent status change, call answered).
	 *
	 * @param event_message $event_message
	 *
	 * @return void
	 */
	private function on_callcenter_maintenance(event_message $event_message): void {
		$this->debug('callcenter::maintenance — triggering immediate agent stats broadcast');
		$this->broadcast_agent_stats();
		// Note: the periodic timer continues independently; no reschedule needed here.
	}

	/**
	 * Handle a sofia::register or sofia::unregister event and broadcast
	 * a registration_change message so connected UIs can update the
	 * extension's registered status in real time.
	 *
	 * @param event_message $reg_message  Parsed event (unfiltered).
	 * @param string        $topic        'sofia::register' or 'sofia::unregister'.
	 *
	 * @return void
	 */
	protected function on_registration_event(event_message $reg_message, string $topic): void {
		$this->debug('Registration trace [raw]: ' . json_encode($reg_message->to_array()));

		$ext_num = '';
		foreach (['from_user', 'to_user', 'user', 'username', 'auth_username'] as $k) {
			$v = trim((string)($reg_message->{$k} ?? ''));
			if ($v !== '') { $ext_num = $v; break; }
		}

		$domain_name = '';
		foreach (['realm', 'from_host', 'to_host', 'presence_host', 'sip_host'] as $k) {
			$v = trim((string)($reg_message->{$k} ?? ''));
			if ($v !== '') { $domain_name = $v; break; }
		}

		// Fallback parse from SIP-style fields (e.g. sip:1001@example.com).
		if ($ext_num === '' || $domain_name === '') {
			foreach (['contact', 'from', 'to', 'sip_contact_uri', 'network_ip'] as $k) {
				$raw = trim((string)($reg_message->{$k} ?? ''));
				if ($raw === '') continue;

				if ($ext_num === '' && preg_match('/(?:sip:)?([^@;>\s]+)@/i', $raw, $m)) {
					$ext_num = trim((string)$m[1]);
				}
				if ($domain_name === '' && preg_match('/@([^;>\s:]+(?:\:[0-9]+)?)/', $raw, $m)) {
					$domain_name = trim((string)$m[1]);
				}
				if ($ext_num !== '' && $domain_name !== '') break;
			}
		}

		// Normalize ext and domain value forms.
		if (strpos($ext_num, '@') !== false) {
			$parts = explode('@', $ext_num, 2);
			$ext_num = trim((string)$parts[0]);
			if ($domain_name === '' && !empty($parts[1])) $domain_name = trim((string)$parts[1]);
		}
		$domain_name = preg_replace('/:\d+$/', '', $domain_name ?? '');

		$registered  = ($topic === 'sofia::register');
		$this->debug('Registration trace [parsed]: '
			. 'topic=' . $topic
			. ' ext=' . $ext_num
			. ' domain=' . $domain_name
			. ' registered=' . ($registered ? 'true' : 'false'));

		if (empty($ext_num) || empty($domain_name)) {
			$this->debug('Registration trace [drop]: missing ext/domain after parse');
			return;
		}

		$this->debug("Registration change: $ext_num@$domain_name registered=" . ($registered ? 'true' : 'false'));

		$message = new websocket_message();
		$message
			->service_name(self::get_service_name())
			->topic('registration_change')
			->status_string('ok')
			->status_code(200)
			->request_id('')
			->resource_id('')
			->payload([
				'extension'   => $ext_num,
				'domain_name' => $domain_name,
				'registered'  => $registered,
			])
		;
		$this->debug('Registration trace [broadcast]: ' . json_encode([
			'extension' => $ext_num,
			'domain_name' => $domain_name,
			'registered' => $registered,
			'service_name' => self::get_service_name(),
			'topic' => 'registration_change',
			'request_id' => $message->request_id(),
			'resource_id' => $message->resource_id(),
		]));
		websocket_client::send($this->ws_client->socket(), $message);
	}

	/**
	 * Broadcast a call or conference event to all subscribers.
	 *
	 * @param event_message $event_message
	 * @param string        $action  Topic name to use.
	 *
	 * @return void
	 */
	private function broadcast_call_event(event_message $event_message, string $action): void {
		$message = new websocket_message();
		$message
			->service_name(self::get_service_name())
			->topic($action)
			->payload($event_message->to_array())
		;
		websocket_client::send($this->ws_client->socket(), $message);
	}

	/**
	 * Broadcast an already-enriched event payload.
	 *
	 * @param array  $event_data
	 * @param string $action
	 *
	 * @return void
	 */
	private function broadcast_enriched_event(array $event_data, string $action): void {
		$message = new websocket_message();
		$message
			->service_name(self::get_service_name())
			->topic($action)
			->payload($event_data)
		;
		websocket_client::send($this->ws_client->socket(), $message);
	}

	/**
	 * Broadcast a conference event with a normalized conference identity.
	 *
	 * @param event_message $event_message
	 * @param string        $action
	 * @param string        $conference_name
	 *
	 * @return void
	 */
	private function broadcast_conference_event(event_message $event_message, string $action, string $conference_name): void {
		$event_data = $event_message->to_array();
		$event_data = $this->normalize_conference_payload($event_data, $conference_name);


		$message = new websocket_message();
		$message
			->service_name(self::get_service_name())
			->topic($action)
			->payload($event_data)
		;
		websocket_client::send($this->ws_client->socket(), $message);
	}

	/**
	 * Add a normalized conference identity and context to an event payload.
	 *
	 * @param array  $event_data
	 * @param string $conference_name
	 *
	 * @return array
	 */
	private function normalize_conference_payload(array $event_data, string $conference_name): array {
		$event_data['conference_name'] = $conference_name;
		$event_data['conference_display_name'] = $this->lookup_conference_display_name($conference_name);

		if (strpos($conference_name, '@') !== false) {
			$parts = explode('@', $conference_name, 2);
			$domain_name = $parts[1] ?? '';
			if ($domain_name !== '') {
				$event_data['domain_name'] = $domain_name;
				$event_data['caller_context'] = $domain_name;
			}
		}

		return $event_data;
	}

	/**
	 * Send an action response back to the requesting client.
	 *
	 * @param websocket_message $message
	 * @param bool              $success
	 * @param string            $status_message
	 *
	 * @param array             $extra_payload
	 *
	 * @return void
	 */
	private function send_action_response(websocket_message $message, bool $success, string $status_message, array $extra_payload = []): void {
		$payload = array_merge(['success' => $success, 'message' => $status_message], $extra_payload);

		$response = new websocket_message();
		$response
			->payload($payload)
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
	 * Enrich an add_member conference event with full member data from json_list.
	 *
	 * @param event_message $event_message
	 * @param string        $conference_name
	 *
	 * @return array
	 */
	private function enrich_member_event(event_message $event_message, string $conference_name): array {
		$event_data = $event_message->to_array();
		$member_id  = $event_data['member_id'] ?? '';
		$found      = false;

		if (!empty($conference_name)) {
			$json_str    = trim(event_socket::api("conference '$conference_name' json_list"));
			$conferences = json_decode($json_str, true);

			if (is_array($conferences) && !empty($conferences)) {
				$conference = $conferences[0];
				$members    = $conference['members'] ?? [];
				$event_data['member_count'] = $conference['member_count'] ?? count($members);

				foreach ($members as $member) {
					if ((string)($member['id'] ?? '') === (string)$member_id) {
						$event_data['member'] = $member;
						$found = true;
						break;
					}
				}
			}
		}

		if (!$found && !empty($member_id)) {
			$event_data['member'] = [
				'id'               => $member_id,
				'uuid'             => $event_data['unique_id'] ?? '',
				'caller_id_name'   => $event_data['caller_id_name'] ?? $event_data['caller_caller_id_name'] ?? '',
				'caller_id_number' => $event_data['caller_id_number'] ?? $event_data['caller_caller_id_number'] ?? '',
				'flags'            => [
					'can_hear'     => ($event_data['hear']           ?? 'true')  === 'true',
					'can_speak'    => ($event_data['speak']          ?? 'true')  === 'true',
					'talking'      => ($event_data['talking']        ?? 'false') === 'true',
					'has_video'    => ($event_data['video']          ?? 'false') === 'true',
					'has_floor'    => ($event_data['floor']          ?? 'false') === 'true',
					'is_moderator' => ($event_data['member_type']    ?? '')       === 'moderator',
				],
			];
		}

		return $this->normalize_conference_payload($event_data, $conference_name);
	}

	/**
	 * Enrich a del_member conference event with updated member count.
	 *
	 * @param event_message $event_message
	 * @param string        $conference_name
	 *
	 * @return array
	 */
	private function enrich_del_member_event(event_message $event_message, string $conference_name): array {
		$event_data   = $event_message->to_array();
		$member_count = isset($event_data['conference_size']) ? (int)$event_data['conference_size'] : null;

		if ($member_count === null && !empty($conference_name)) {
			$json_str    = trim(event_socket::api("conference '$conference_name' json_list"));
			$conferences = json_decode($json_str, true);
			$member_count = (is_array($conferences) && !empty($conferences))
				? ($conferences[0]['member_count'] ?? 0)
				: 0;
		}

		$event_data['member_count'] = $member_count ?? 0;
		return $this->normalize_conference_payload($event_data, $conference_name);
	}

	/**
	 * Enrich a conference_create event with name and display name.
	 *
	 * @param event_message $event_message
	 * @param string        $conference_name
	 *
	 * @return array
	 */
	private function enrich_conference_create_event(event_message $event_message, string $conference_name): array {
		$event_data                            = $event_message->to_array();
		$event_data['member_count']            = 0;
		return $this->normalize_conference_payload($event_data, $conference_name);
	}

	/**
	 * Extract the conference identifier (name@domain) from an event message,
	 * trying several common header locations in priority order.
	 *
	 * @param event_message $event_message
	 *
	 * @return string
	 */
	private function extract_conference_name(event_message $event_message): string {
		$name = $event_message->conference_name ?? '';
		if (!empty($name)) return $name;

		$presence = $event_message->channel_presence_id ?? '';
		if (!empty($presence) && strpos($presence, '@') !== false) return $presence;

		$channel = $event_message->caller_channel_name ?? '';
		if (!empty($channel) && preg_match('/conference\+([^\/]+)/', $channel, $m)) return $m[1];

		$dest    = $event_message->caller_destination_number ?? '';
		$context = $event_message->caller_context ?? '';
		if (!empty($dest) && !empty($context)) return "$dest@$context";

		return '';
	}

	/**
	 * Lookup a human-readable conference display name from cache or database.
	 *
	 * @param string $conference_identifier  Full name such as "uuid@domain" or "3001@domain".
	 *
	 * @return string
	 */
	private function lookup_conference_display_name(string $conference_identifier): string {
		if (empty($conference_identifier)) return '';

		$parts = explode('@', $conference_identifier);
		$key   = $parts[0] ?? '';
		$domain_name = $parts[1] ?? '';

		if (empty($key)) return $conference_identifier;

		if (isset($this->conference_name_cache[$key])) {
			return $this->conference_name_cache[$key];
		}

		try {
			$database = database::new(['config' => parent::$config]);

			if ($this->is_uuid($key)) {
				$row = $database->select(
					"SELECT conference_room_name FROM v_conference_rooms WHERE conference_room_uuid = :uuid",
					[':uuid' => $key],
					'row'
				);
				if (!empty($row['conference_room_name'])) {
					$this->conference_name_cache[$key] = $row['conference_room_name'];
					return $row['conference_room_name'];
				}
			}

			if (is_numeric($key)) {
				$sql    = "SELECT c.conference_name FROM v_conferences AS c "
						. "LEFT JOIN v_domains AS d ON c.domain_uuid = d.domain_uuid "
						. "WHERE c.conference_extension = :ext ";
				$params = [':ext' => $key];
				if (!empty($domain_name)) {
					$sql .= "AND d.domain_name = :domain ";
					$params[':domain'] = $domain_name;
				}
				$row = $database->select($sql, $params, 'row');
				if (!empty($row['conference_name'])) {
					$this->conference_name_cache[$key] = $row['conference_name'];
					return $row['conference_name'];
				}
			}
		} catch (\Exception $e) {
			$this->debug('DB error in lookup_conference_display_name: ' . $e->getMessage());
		}

		return $key;
	}

	/**
	 * Return true if the given string looks like a UUID.
	 *
	 * @param string $string
	 *
	 * @return bool
	 */
	private function is_uuid(string $string): bool {
		return (bool)preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $string);
	}

	/**
	 * Look up the call-center agent name for the connected user.
	 *
	 * Compares the domain permissions context against v_call_center_agents.
	 * Returns empty string when the user is not a registered agent.
	 *
	 * @param array  $permissions  Subscriber permission map.
	 * @param string $domain_name  Domain to scope the lookup.
	 *
	 * @return string
	 */
	private function get_agent_name_for_permission(array $permissions, string $domain_name): string {
		// user_uuid should be in the permission metadata attached to the subscriber
		$user_uuid = $permissions['_user_uuid'] ?? '';
		if (empty($user_uuid) || empty($domain_name)) return '';

		try {
			$database = database::new(['config' => parent::$config]);
			$row = $database->select(
				"SELECT a.agent_name FROM v_call_center_agents AS a "
				. "LEFT JOIN v_domains AS d ON a.domain_uuid = d.domain_uuid "
				. "WHERE a.user_uuid = :user_uuid AND d.domain_name = :domain_name",
				[':user_uuid' => $user_uuid, ':domain_name' => $domain_name],
				'row'
			);
			return $row['agent_name'] ?? '';
		} catch (\Exception $e) {
			$this->debug('DB error in get_agent_name_for_permission: ' . $e->getMessage());
			return '';
		}
	}
}
