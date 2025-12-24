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

//includes files
require_once dirname(__DIR__, 2) . "/resources/require.php";
require_once "resources/check_auth.php";

//check permissions
if (!permission_exists('conference_interactive_view')) {
	echo "access denied";
	exit;
}

//show intended global variables
global $domain_uuid, $user_uuid, $settings, $database, $config;

//get the domain uuid
if (empty($domain_uuid)) {
	$domain_uuid = $_SESSION['domain_uuid'] ?? '';
}

//get the user uuid
if (empty($user_uuid)) {
	$user_uuid = $_SESSION['user_uuid'] ?? '';
}

//load the config
if (!($config instanceof config)) {
	$config = config::load();
}

//load the database
if (!($database instanceof database)) {
	$database = new database;
}

//load the settings
if (!($settings instanceof settings)) {
	$settings = new settings(['database' => $database, 'domain_uuid' => $domain_uuid, 'user_uuid' => $user_uuid]);
}

//add multi-lingual support
$language = new text;
$text = $language->get();

//get the http get or post and set it as php variables
if (!empty($_REQUEST["c"]) && is_numeric($_REQUEST["c"])) {
	$conference_id = $_REQUEST["c"];
}
elseif (!empty($_REQUEST["c"]) && is_uuid($_REQUEST["c"])) {
	$conference_id = $_REQUEST["c"];
}
else {
	//exit if the conference id is invalid
	exit;
}

//replace the space with underscore
$conference_name = $conference_id.'@'.$_SESSION['domain_name'];

//get and prepare the conference display name
$conference_display_name = str_replace("-", " ", $conference_id);
$conference_display_name = str_replace("_", " ", $conference_display_name);

//create token
$token = (new token())->create($_SERVER['PHP_SELF']);

// Pass the token to the subscriber class so that when this subscriber makes a websocket
// connection, the subscriber object can validate the information.
subscriber::save_token($token, ['active.conferences']);

//show the header
$document['title'] = $text['label-interactive'];
require_once dirname(__DIR__, 2) . "/resources/header.php";

//break the caching
$version = md5(file_get_contents(__DIR__ . '/resources/javascript/websocket_client.js'));

//build permissions object for client-side checks
$user_permissions = [
	'lock' => permission_exists('conference_interactive_lock'),
	'mute' => permission_exists('conference_interactive_mute'),
	'deaf' => permission_exists('conference_interactive_deaf'),
	'kick' => permission_exists('conference_interactive_kick'),
	'energy' => permission_exists('conference_interactive_energy'),
	'volume' => permission_exists('conference_interactive_volume'),
	'gain' => permission_exists('conference_interactive_gain'),
	'video' => permission_exists('conference_interactive_video'),
];

//get websocket settings from default settings
$ws_settings = [
	'reconnect_delay' => (int)$settings->get('active_conferences', 'reconnect_delay', 2000),
	'ping_interval' => (int)$settings->get('active_conferences', 'ping_interval', 30000),
	'auth_timeout' => (int)$settings->get('active_conferences', 'auth_timeout', 10000),
	'pong_timeout' => (int)$settings->get('active_conferences', 'pong_timeout', 10000),
	'refresh_interval' => (int)$settings->get('active_conferences', 'refresh_interval', 0),
	'max_reconnect_delay' => (int)$settings->get('active_conferences', 'max_reconnect_delay', 30000),
	'pong_timeout_max_retries' => (int)$settings->get('active_conferences', 'pong_timeout_max_retries', 3),
];

//get theme colors for status indicator
$status_colors = [
	'connected' => $settings->get('theme', 'active_conference_status_connected', '#28a745'),
	'warning' => $settings->get('theme', 'active_conference_status_warning', '#ffc107'),
	'disconnected' => $settings->get('theme', 'active_conference_status_disconnected', '#dc3545'),
	'connecting' => $settings->get('theme', 'active_conference_status_connecting', '#6c757d'),
];

//get status indicator mode and icons
$status_indicator_mode = $settings->get('active_conferences', 'status_indicator_mode', 'color');
$status_icons = [
	'connected' => $settings->get('active_conferences', 'status_icon_connected', 'fa-solid fa-plug-circle-check'),
	'warning' => $settings->get('active_conferences', 'status_icon_warning', 'fa-solid fa-plug-circle-exclamation'),
	'disconnected' => $settings->get('active_conferences', 'status_icon_disconnected', 'fa-solid fa-plug-circle-xmark'),
	'connecting' => $settings->get('active_conferences', 'status_icon_connecting', 'fa-solid fa-plug fa-fade'),
];

//get status tooltips from translations
$status_tooltips = [
	'connected' => $text['status-connected'],
	'warning' => $text['status-warning'],
	'disconnected' => $text['status-disconnected'],
	'connecting' => $text['status-connecting'],
];

?>

<script type="text/javascript">
//user permissions for client-side checks
const user_permissions = <?= json_encode($user_permissions) ?>;

//websocket configuration from server settings
const ws_config = <?= json_encode($ws_settings) ?>;

//status indicator colors from theme settings
const status_colors = <?= json_encode($status_colors) ?>;

//status indicator icons from settings
const status_icons = <?= json_encode($status_icons) ?>;

//status tooltips from translations
const status_tooltips = <?= json_encode($status_tooltips) ?>;

//status indicator mode: 'color' or 'icon'
const status_indicator_mode = <?= json_encode($status_indicator_mode) ?>;

//send action via WebSocket
function send_action(action, options = {}, skip_refresh = false) {
	if (!ws || !ws.ws || ws.ws.readyState !== WebSocket.OPEN) {
		console.error('WebSocket not connected');
		return Promise.reject('Not connected');
	}

	const payload = {
		action: action,
		conference_name: conference_name,
		domain_name: '<?= $_SESSION['domain_name'] ?>',
		...options
	};

	console.log('Sending action:', action, payload);

	return ws.request('active.conferences', 'action', payload)
		.then(response => {
			console.log('Action response:', response);
			const result = response.payload || response;
			if (!result.success) {
				console.error('Action failed:', result.message);
			}
			// Refresh data after action (unless skip_refresh is true)
			if (!skip_refresh) {
				load_conference_data();
			}
			return result;
		})
		.catch(err => {
			console.error('Action error:', err);
			throw err;
		});
}

//conference control functions
function conference_action(action, member_id, uuid, direction) {
	// Handle mute_all and unmute_all by iterating over members
	if (action === 'mute_all' || action === 'unmute_all') {
		return mute_all_members(action === 'mute_all');
	}

	return send_action(action, {
		member_id: member_id || '',
		uuid: uuid || '',
		direction: direction || ''
	});
}

//mute or unmute all non-moderator members by iterating over them
async function mute_all_members(mute) {
	const action = mute ? 'mute' : 'unmute';
	const rows = document.querySelectorAll('tr[data-member-id]');

	console.log(`${action}_all: Found ${rows.length} member rows`);

	const promises = [];

	for (const row of rows) {
		const member_id = row.getAttribute('data-member-id');
		const uuid = row.getAttribute('data-uuid');

		// Check if this is a moderator (has fa-user-tie icon)
		const is_moderator = row.querySelector('.fa-user-tie') !== null;

		if (is_moderator) {
			console.log(`Skipping moderator member ${member_id}`);
			continue;
		}

		console.log(`${action} member ${member_id} (uuid: ${uuid})`);

		// Send the action for this member (skip_refresh = true)
		promises.push(
			send_action(action, {
				member_id: member_id,
				uuid: uuid
			}, true).catch(err => {
				console.error(`Failed to ${action} member ${member_id}:`, err);
			})
		);
	}

	// Wait for all actions to complete
	await Promise.all(promises);

	// Refresh the display once at the end
	load_conference_data();
}

var record_count = 0;
</script>

<?php
$ws_client_file = __DIR__ . '/resources/javascript/websocket_client.js';
$ws_client_hash = file_exists($ws_client_file) ? md5_file($ws_client_file) : $version;
?>
<script src="resources/javascript/websocket_client.js?v=<?= $ws_client_hash ?>"></script>

<?php

//page header
echo "<div class='action_bar' id='action_bar'>\n";
echo "<div class='heading'><b>".$text['label-interactive']."</b>&nbsp;";
if ($status_indicator_mode === 'icon') {
	echo "<span id='connection_status' class='".$status_icons['connecting']."' style='color: ".$status_colors['connecting'].";' title='".$status_tooltips['connecting']."'></span>";
} else {
	echo "<div id='connection_status' class='count' style='display: inline-block; min-width: 12px; height: 12px; vertical-align: middle; background: ".$status_colors['connecting'].";' title='".$status_tooltips['connecting']."'></div>";
}
echo "</div>\n";
echo "<div class='actions'>\n";
echo "</div>\n";
echo "<div style='clear: both;'></div>\n";
echo "</div>\n";

echo $text['description-interactive']."\n";
echo "<br /><br />\n";

//show the content
echo "<div id='ajax_response'></div>\n";
echo "<br /><br />\n";

?>

<script>
const token = {
	name: '<?= $token['name'] ?>',
	hash: '<?= $token['hash'] ?>'
};

const conference_name = <?= json_encode($conference_name) ?>;
const conference_id = <?= json_encode($conference_id) ?>;

let ws = null;
let reconnect_attempts = 0;

// Track member timers - keyed by member_id
let member_timers = {};
let timer_interval = null;
let ping_interval_timer = null;
let last_pong_time = Date.now();
let ping_timeout = null;
let auth_timeout = null;
let refresh_interval_timer = null;
let pong_failure_count = 0;

function update_connection_status(state) {
	const el = document.getElementById('connection_status');
	const color = status_colors[state] || status_colors.connecting;
	const tooltip = status_tooltips[state] || status_tooltips.connecting;

	// Update tooltip for accessibility
	el.title = tooltip;

	if (status_indicator_mode === 'icon') {
		// Update icon class and color
		const icon = status_icons[state] || status_icons.connecting;
		el.className = icon;
		el.style.color = color;
	} else {
		// Update background color for color mode
		el.style.backgroundColor = color;
	}
}

function send_ping() {
	if (!ws || !ws.ws || ws.ws.readyState !== WebSocket.OPEN) {
		return;
	}

	console.log('Sending keepalive ping');

	// Set a timeout - if no pong received in time, handle retry or reload
	ping_timeout = setTimeout(() => {
		pong_failure_count++;
		console.warn('No pong response received - failure count:', pong_failure_count, 'of', ws_config.pong_timeout_max_retries);

		if (pong_failure_count >= ws_config.pong_timeout_max_retries) {
			console.error('Max pong failures reached - reloading page');
			update_connection_status('disconnected');
			window.location.reload();
		} else {
			// Show warning state - still trying
			update_connection_status('warning');
		}
	}, ws_config.pong_timeout);

	ws.request('active.conferences', 'ping', {})
		.then(response => {
			// Pong received - clear the timeout, reset failure count, update status
			if (ping_timeout) {
				clearTimeout(ping_timeout);
				ping_timeout = null;
			}
			pong_failure_count = 0;
			last_pong_time = Date.now();
			update_connection_status('connected');
			console.log('Pong received from service');
		})
		.catch(err => {
			console.error('Ping failed:', err);
		});
}

function format_time(seconds) {
	// Handle NaN, undefined, null, or negative values
	if (!Number.isFinite(seconds) || seconds < 0) {
		seconds = 0;
	}
	const hrs = Math.floor(seconds / 3600);
	const mins = Math.floor((seconds % 3600) / 60);
	const secs = Math.floor(seconds % 60);
	return String(hrs).padStart(2, '0') + ':' + String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
}

function initialize_timers() {
	// Clear existing timers
	member_timers = {};

	// Find all member rows and initialize their timers
	const rows = document.querySelectorAll('tr[data-member-id]');
	console.log('Initializing timers for', rows.length, 'members');
	rows.forEach(row => {
		const member_id = row.getAttribute('data-member-id');
		const uuid = row.getAttribute('data-uuid');
		const join_time = parseInt(row.getAttribute('data-join-time'), 10) || 0;
		const last_talking = parseInt(row.getAttribute('data-last-talking'), 10) || 0;

		console.log('Member:', member_id, 'join_time:', join_time, 'last_talking:', last_talking);

		member_timers[member_id] = {
			uuid: uuid,
			join_time: join_time,
			last_talking: last_talking,
			is_talking: false
		};
	});

	// Start the interval timer if not already running
	if (!timer_interval) {
		timer_interval = setInterval(update_timer_displays, 1000);
	}
}

function update_timer_displays() {
	const rows = document.querySelectorAll('tr[data-member-id]');
	rows.forEach(row => {
		const member_id = row.getAttribute('data-member-id');
		const timer = member_timers[member_id];

		if (timer) {
			// Increment join time
			timer.join_time++;

			// Increment quiet time only if not talking
			if (!timer.is_talking) {
				timer.last_talking++;
			}

			// Update the display
			const join_time_cell = row.querySelector('.join-time');
			const quiet_time_cell = row.querySelector('.quiet-time');

			if (join_time_cell) {
				join_time_cell.textContent = format_time(timer.join_time);
			}
			if (quiet_time_cell) {
				quiet_time_cell.textContent = format_time(timer.last_talking);
			}
		}
	});
}

function handle_talking_event(member_id, is_talking) {
	console.log('handle_talking_event called - member_id:', member_id, 'is_talking:', is_talking);
	console.log('Available timers:', Object.keys(member_timers));

	const timer = member_timers[member_id];
	if (timer) {
		timer.is_talking = is_talking;
		if (is_talking) {
			// Reset quiet time when they start talking
			timer.last_talking = 0;
			console.log('Reset quiet time to 0 for member:', member_id);
		}
	} else {
		console.warn('No timer found for member:', member_id);
	}

	// Update the talking icon
	const row = document.querySelector(`tr[data-member-id="${member_id}"]`);
	console.log('Found row for member:', member_id, row ? 'yes' : 'no');
	if (row) {
		const talking_icon = row.querySelector('.talking-icon');
		if (talking_icon) {
			talking_icon.style.visibility = is_talking ? 'visible' : 'hidden';
			console.log('Updated talking icon visibility to:', is_talking ? 'visible' : 'hidden');
		}
	}
}

function connect_websocket() {
	const ws_url = `wss://${window.location.hostname}/websockets/`;

	try {
		ws = new ws_client(ws_url, token);

		ws.on_event('authenticated', authenticated);

		// Handle authentication failure (session expired)
		ws.on_event('authentication_failed', function(event) {
			console.error('WebSocket authentication failed - session may have expired');
			update_connection_status('disconnected');
			window.location.href = '<?= PROJECT_PATH ?>/?path=' + encodeURIComponent(window.location.pathname);
		});

		ws.ws.addEventListener("open", () => {
			console.log('WebSocket connection opened');
			reconnect_attempts = 0;
			update_connection_status('connecting');

			// Set authentication timeout - if not authenticated in time, session may have expired
			auth_timeout = setTimeout(() => {
				console.error('Authentication timeout - session may have expired');
				update_connection_status('disconnected');
				window.location.href = '<?= PROJECT_PATH ?>/?path=' + encodeURIComponent(window.location.pathname);
			}, ws_config.auth_timeout);
		});

		ws.ws.addEventListener("close", (event) => {
			console.warn('WebSocket disconnected - code:', event.code, 'reason:', event.reason, 'wasClean:', event.wasClean);

			// Clear auth timeout if connection closes
			if (auth_timeout) {
				clearTimeout(auth_timeout);
				auth_timeout = null;
			}

			// Log the close code meaning
			const close_codes = {
				1000: 'Normal closure',
				1001: 'Going away (page navigation)',
				1002: 'Protocol error',
				1003: 'Unsupported data',
				1005: 'No status received',
				1006: 'Abnormal closure (no close frame)',
				1007: 'Invalid frame payload data',
				1008: 'Policy violation',
				1009: 'Message too big',
				1010: 'Mandatory extension missing',
				1011: 'Internal server error',
				1015: 'TLS handshake failure'
			};
			console.warn('Close code meaning:', close_codes[event.code] || 'Unknown');

			update_connection_status('disconnected');

			// Clear the ping interval and timeout
			if (ping_interval_timer) {
				clearInterval(ping_interval_timer);
				ping_interval_timer = null;
			}
			if (ping_timeout) {
				clearTimeout(ping_timeout);
				ping_timeout = null;
			}

			// Clear refresh interval if set
			if (refresh_interval_timer) {
				clearInterval(refresh_interval_timer);
				refresh_interval_timer = null;
			}

			// The token is consumed on first connection, so we must reload
			// the page to get a fresh token for reconnection
			setTimeout(() => {
				window.location.reload();
			}, ws_config.reconnect_delay);
		});

		ws.ws.addEventListener("error", (error) => {
			console.error('WebSocket error:', error);
		});

	} catch (error) {
		console.error('Failed to connect to WebSocket:', error);
		update_connection_status('disconnected');
	}
}

function authenticated(message) {
	console.log('WebSocket authenticated');
	pong_failure_count = 0;
	// Show warning until first pong confirms service is responding
	update_connection_status('warning');

	// Clear the authentication timeout since we're now authenticated
	if (auth_timeout) {
		clearTimeout(auth_timeout);
		auth_timeout = null;
	}

	// Send immediate ping to verify service is responding
	send_ping();

	// Start ping interval to keep connection alive
	if (ping_interval_timer) {
		clearInterval(ping_interval_timer);
	}
	ping_interval_timer = setInterval(() => {
		send_ping();
	}, ws_config.ping_interval);

	// Start optional refresh interval if configured
	if (ws_config.refresh_interval > 0) {
		if (refresh_interval_timer) {
			clearInterval(refresh_interval_timer);
		}
		refresh_interval_timer = setInterval(() => {
			load_conference_data();
		}, ws_config.refresh_interval);
	}

	// Register event handlers for conference events
	ws.on_event('*', handle_conference_event);

	// Subscribe to all events
	ws.subscribe('*');

	// Load initial conference data
	load_conference_data();
}

function handle_conference_event(event) {
	console.log('Conference event:', event);

	// Get the action from payload or event
	const payload = event.payload || event;
	const action = payload.action || event.action || event.event_name;
	// FreeSWITCH uses Member-ID which may come through as member_id or member-id
	const member_id = payload.member_id || payload['member-id'] || event.member_id || event['member-id'];

	console.log('Parsed action:', action, 'member_id:', member_id);

	// Handle talking events without full refresh
	// FreeSWITCH uses hyphenated actions: start-talking, stop-talking
	if ((action === 'start-talking' || action === 'start_talking') && member_id) {
		console.log('Start talking event for member:', member_id);
		handle_talking_event(member_id, true);
		return;
	}
	if ((action === 'stop-talking' || action === 'stop_talking') && member_id) {
		console.log('Stop talking event for member:', member_id);
		handle_talking_event(member_id, false);
		return;
	}

	// Refresh the conference data on other relevant events
	const refresh_events = [
		'add-member', 'del-member', 'mute-member', 'unmute-member',
		'deaf-member', 'undeaf-member', 'kick-member',
		'floor-change', 'lock', 'unlock', 'conference-create',
		'conference-destroy',
		// Also check underscore versions
		'add_member', 'del_member', 'mute_member', 'unmute_member',
		'deaf_member', 'undeaf_member', 'kick_member',
		'floor_change', 'conference_create', 'conference_destroy'
	];

	if (refresh_events.includes(action)) {
		load_conference_data();
	}
}

function load_conference_data() {
	// Use AJAX to get current conference state
	fetch('conference_interactive_inc.php?c=' + encodeURIComponent(conference_id))
		.then(response => {
			// Check if we were redirected to login page (session expired)
			if (response.redirected) {
				window.location.href = response.url;
				return null;
			}
			return response.text();
		})
		.then(html => {
			if (html === null) return;

			// Check if the response contains a login form (session expired)
			// Look for common indicators of the login page
			if (html.includes('id="login_form"') ||
				html.includes('name="username"') && html.includes('name="password"') ||
				html.includes('authentication_failed') ||
				html.includes('id="login"')) {
				// Session expired - redirect to login page
				console.log('Session expired - redirecting to login');
				window.location.href = '<?= PROJECT_PATH ?>/?path=' + encodeURIComponent(window.location.pathname);
				return;
			}

			// Check for access denied
			if (html.trim() === 'access denied') {
				console.log('Access denied - redirecting to login');
				window.location.href = '<?= PROJECT_PATH ?>/';
				return;
			}

			document.getElementById('ajax_response').innerHTML = html;
			// Re-initialize timers after loading new data
			initialize_timers();
		})
		.catch(err => console.error('Error loading conference data:', err));
}

// Start websocket connection
connect_websocket();

// Initial load
document.addEventListener('DOMContentLoaded', function() {
	load_conference_data();
});
</script>

<?php require_once "resources/footer.php"; ?>
