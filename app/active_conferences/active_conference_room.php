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

global $domain_uuid, $user_uuid, $settings, $database, $config;

if (empty($domain_uuid)) {
$domain_uuid = $_SESSION['domain_uuid'] ?? '';
}

if (empty($user_uuid)) {
$user_uuid = $_SESSION['user_uuid'] ?? '';
}

if (!($config instanceof config)) {
$config = config::load();
}

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

$token = (new token())->create($_SERVER['PHP_SELF']);

// Pass the token to the subscriber class so that when this subscriber makes a websocket
// connection, the subscriber object can validate the information.
subscriber::save_token($token, ['active.conferences']);

//show the header
$document['title'] = $text['label-interactive'];
require_once dirname(__DIR__, 2) . "/resources/header.php";

//break the caching
$version = md5(file_get_contents(__DIR__ . '/resources/javascript/websocket_client.js'));

?>

<script type="text/javascript">
// Action commands use AJAX
function send_cmd(url) {
if (window.XMLHttpRequest) {
xmlhttp = new XMLHttpRequest();
}
else {
xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
}
xmlhttp.open("GET", url, true);
xmlhttp.onreadystatechange = function() {
if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
document.getElementById('cmd_reponse').innerHTML = xmlhttp.responseText;
}
};
xmlhttp.send(null);
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
echo "<div class='heading'><b>".$text['label-interactive']."</b>&nbsp;<span id='connection_status' style='font-size: 12px; color: #999;'>(Connecting...)</span></div>\n";
echo "<div class='actions'>\n";
echo "</div>\n";
echo "<div style='clear: both;'></div>\n";
echo "</div>\n";

echo $text['description-interactive']."\n";
echo "<br /><br />\n";

//show the content
echo "<div id='ajax_reponse'></div>\n";
echo "<br /><br />\n";

?>

<script>
const token = {
	name: '<?= $token['name'] ?>',
	hash: '<?= $token['hash'] ?>'
};

const conferenceName = "<?= escape($conference_name) ?>";
const conferenceId = "<?= escape($conference_id) ?>";

let ws = null;
let reconnectAttempts = 0;
const maxReconnectDelay = 30000;
const baseReconnectDelay = 1000;

// Track member timers - keyed by member_id
let memberTimers = {};
let timerInterval = null;
let pingInterval = null;
let lastPongTime = Date.now();
let pingTimeout = null;

function updateConnectionStatus(status, connected) {
	const el = document.getElementById('connection_status');
	el.innerHTML = '(' + status + ')';
	el.style.color = connected ? '#28a745' : '#999';
}

function formatTime(seconds) {
	const hrs = Math.floor(seconds / 3600);
	const mins = Math.floor((seconds % 3600) / 60);
	const secs = Math.floor(seconds % 60);
	return String(hrs).padStart(2, '0') + ':' + String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
}

function initializeTimers() {
	// Clear existing timers
	memberTimers = {};
	
	// Find all member rows and initialize their timers
	const rows = document.querySelectorAll('tr[data-member-id]');
	console.log('Initializing timers for', rows.length, 'members');
	rows.forEach(row => {
		const memberId = row.getAttribute('data-member-id');
		const uuid = row.getAttribute('data-uuid');
		const joinTime = parseInt(row.getAttribute('data-join-time') || '0');
		const lastTalking = parseInt(row.getAttribute('data-last-talking') || '0');
		
		console.log('Member:', memberId, 'joinTime:', joinTime, 'lastTalking:', lastTalking);
		
		memberTimers[memberId] = {
			uuid: uuid,
			joinTime: joinTime,
			lastTalking: lastTalking,
			isTalking: false
		};
	});
	
	// Start the interval timer if not already running
	if (!timerInterval) {
		timerInterval = setInterval(updateTimerDisplays, 1000);
	}
}

function updateTimerDisplays() {
	const rows = document.querySelectorAll('tr[data-member-id]');
	rows.forEach(row => {
		const memberId = row.getAttribute('data-member-id');
		const timer = memberTimers[memberId];
		
		if (timer) {
			// Increment join time
			timer.joinTime++;
			
			// Increment quiet time only if not talking
			if (!timer.isTalking) {
				timer.lastTalking++;
			}
			
			// Update the display
			const joinTimeCell = row.querySelector('.join-time');
			const quietTimeCell = row.querySelector('.quiet-time');
			
			if (joinTimeCell) {
				joinTimeCell.textContent = formatTime(timer.joinTime);
			}
			if (quietTimeCell) {
				quietTimeCell.textContent = formatTime(timer.lastTalking);
			}
		}
	});
}

function handleTalkingEvent(memberId, isTalking) {
	console.log('handleTalkingEvent called - memberId:', memberId, 'isTalking:', isTalking);
	console.log('Available timers:', Object.keys(memberTimers));
	
	const timer = memberTimers[memberId];
	if (timer) {
		timer.isTalking = isTalking;
		if (isTalking) {
			// Reset quiet time when they start talking
			timer.lastTalking = 0;
			console.log('Reset quiet time to 0 for member:', memberId);
		}
	} else {
		console.warn('No timer found for member:', memberId);
	}
	
	// Update the talking icon
	const row = document.querySelector(`tr[data-member-id="${memberId}"]`);
	console.log('Found row for member:', memberId, row ? 'yes' : 'no');
	if (row) {
		const talkingIcon = row.querySelector('.talking-icon');
		if (talkingIcon) {
			talkingIcon.style.visibility = isTalking ? 'visible' : 'hidden';
			console.log('Updated talking icon visibility to:', isTalking ? 'visible' : 'hidden');
		}
	}
}

function connectWebSocket() {
	const wsUrl = `wss://${window.location.hostname}/websockets/`;

	try {
		ws = new ws_client(wsUrl, token);

		ws.onEvent('authenticated', authenticated);

		ws.ws.addEventListener("open", () => {
			console.log('WebSocket connection opened');
			reconnectAttempts = 0;
			updateConnectionStatus('Connecting...', false);
		});

		ws.ws.addEventListener("close", (event) => {
			console.warn('WebSocket disconnected - code:', event.code, 'reason:', event.reason, 'wasClean:', event.wasClean);
			
			// Log the close code meaning
			const closeCodes = {
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
			console.warn('Close code meaning:', closeCodes[event.code] || 'Unknown');
			
			updateConnectionStatus('Disconnected - Reloading...', false);

			// Clear the ping interval and timeout
			if (pingInterval) {
				clearInterval(pingInterval);
				pingInterval = null;
			}
			if (pingTimeout) {
				clearTimeout(pingTimeout);
				pingTimeout = null;
			}

			// The token is consumed on first connection, so we must reload
			// the page to get a fresh token for reconnection
			setTimeout(() => {
				window.location.reload();
			}, 2000);
		});

		ws.ws.addEventListener("error", (error) => {
			console.error('WebSocket error:', error);
		});

	} catch (error) {
		console.error('Failed to connect to WebSocket:', error);
		updateConnectionStatus('Connection Failed', false);
	}
}

function authenticated(message) {
	console.log('WebSocket authenticated');
	updateConnectionStatus('Connected', true);

	// Start ping interval to keep connection alive (every 30 seconds)
	if (pingInterval) {
		clearInterval(pingInterval);
	}
	pingInterval = setInterval(() => {
		if (ws && ws.ws && ws.ws.readyState === WebSocket.OPEN) {
			// Send a ping request to keep the connection alive
			console.log('Sending keepalive ping');
			
			// Set a timeout - if no pong received within 10 seconds, reload
			pingTimeout = setTimeout(() => {
				console.error('No pong response received - service may be down, reloading...');
				updateConnectionStatus('Service not responding - Reloading...', false);
				window.location.reload();
			}, 10000);
			
			ws.request('active.conferences', 'ping', {})
				.then(response => {
					// Pong received - clear the timeout and update last pong time
					if (pingTimeout) {
						clearTimeout(pingTimeout);
						pingTimeout = null;
					}
					lastPongTime = Date.now();
					console.log('Pong received from service');
				})
				.catch(err => {
					console.error('Ping failed:', err);
				});
		}
	}, 30000);

	// Register event handlers for conference events
	ws.onEvent('*', handleConferenceEvent);

	// Subscribe to all events
	ws.subscribe('*');

	// Load initial conference data
	loadConferenceData();
}

function handleConferenceEvent(event) {
	console.log('Conference event:', event);

	// Get the action from payload or event
	const payload = event.payload || event;
	const action = payload.action || event.action || event.event_name;
	// FreeSWITCH uses Member-ID which may come through as member_id or member-id
	const memberId = payload.member_id || payload['member-id'] || event.member_id || event['member-id'];

	console.log('Parsed action:', action, 'memberId:', memberId);

	// Handle talking events without full refresh
	// FreeSWITCH uses hyphenated actions: start-talking, stop-talking
	if ((action === 'start-talking' || action === 'start_talking') && memberId) {
		console.log('Start talking event for member:', memberId);
		handleTalkingEvent(memberId, true);
		return;
	}
	if ((action === 'stop-talking' || action === 'stop_talking') && memberId) {
		console.log('Stop talking event for member:', memberId);
		handleTalkingEvent(memberId, false);
		return;
	}

	// Refresh the conference data on other relevant events
	const refreshEvents = [
		'add-member', 'del-member', 'mute-member', 'unmute-member',
		'deaf-member', 'undeaf-member', 'kick-member',
		'floor-change', 'lock', 'unlock', 'conference-create',
		'conference-destroy',
		// Also check underscore versions
		'add_member', 'del_member', 'mute_member', 'unmute_member',
		'deaf_member', 'undeaf_member', 'kick_member',
		'floor_change', 'conference_create', 'conference_destroy'
	];

	if (refreshEvents.includes(action)) {
		loadConferenceData();
	}
}

function loadConferenceData() {
	// Use AJAX to get current conference state
	fetch('conference_interactive_inc.php?c=' + encodeURIComponent(conferenceId))
		.then(response => response.text())
		.then(html => {
			document.getElementById('ajax_reponse').innerHTML = html;
			// Re-initialize timers after loading new data
			initializeTimers();
		})
		.catch(err => console.error('Error loading conference data:', err));
}

// Start websocket connection
connectWebSocket();

// Initial load
document.addEventListener('DOMContentLoaded', function() {
	loadConferenceData();
});
</script>

<?php require_once "resources/footer.php"; ?>
