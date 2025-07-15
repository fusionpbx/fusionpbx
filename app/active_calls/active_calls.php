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
if (permission_exists('call_active_view')) {
	//access granted
} else {
	echo "access denied";
	exit;
}

//set a default value
$debug = false;

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

if (!($database instanceof database)) {
	$database = database::new();
}

if (!($settings instanceof settings)) {
	$settings = new settings(['database' => $database, 'domain_uuid' => $domain_uuid, 'user_uuid' => $user_uuid]);
}

//ensure we have the proper icons to avoid PHP warnings
$theme_button_icon_all = $settings->get('theme', 'button_icon_all');
$theme_button_icon_back = $settings->get('theme', 'button_icon_back');
$theme_button_icon_broom = $settings->get('theme', 'button_icon_broom');

//add multi-lingual support
$language = new text;
$text = $language->get();

$token = (new token())->create($_SERVER['PHP_SELF']);

//show the header
$document['title'] = $text['title'];
require_once dirname(__DIR__, 2) . "/resources/header.php";

//add the style
echo "<style>\n";
echo "	/* Small screens: Hide columns with class 'hide-small' */\n";
echo "	@media (max-width: 600px) {\n";
echo "		.hide-small {\n";
echo "			display: none;\n";
echo "		}\n";
echo "	}\n";
echo "\n";
echo "	/* Medium screens: Hide columns with class 'hide-medium' */\n";
echo	"@media (max-width: 1023px) and (min-width: 601px) {\n";
echo "		.hide-medium {\n";
echo "			display: none;\n";
echo "		}\n";
echo "	}\n";
echo "\n";
echo "	th {\n";
echo "		white-space: nowrap; /* Prevents text from wrapping */\n";
echo "		overflow: hidden;    /* Hides any content that overflows the element's box */\n";
echo "		text-overflow: ellipsis; /* Shows an ellipsis (...) for clipped text */\n";
echo "	}\n";
echo "</style>\n";

//	if (permission_exists('call_active_details')) {
if ($debug) {
	echo "<div id='overlay' class='hidden'>\n";
	echo "  <div id='overlay-content'>\n";
	echo "  </div>\n";
	echo "</div>\n";
}
echo "<div class='action_bar' id='action_bar'>\n";
if (permission_exists('call_active_all')) {
	echo "	<div class='heading'><b>" . $text['title'] . "</b><div id='calls_active_count' class='count' style='background: red;'>0</div></div>";
} else {
	echo "	<div class='heading'><b>" . $text['title'] . "</b><div id='calls_active_count' class='count' style='background: red;'>0</div></div>";
}
echo "	<div class='actions'>\n";
if (permission_exists('call_active_all')) {
	// Show All button
	echo button::create([
		'id' => 'btn_show_all',
		'type' => 'button',
		'label' => $text['button-show_all'],
		'icon' => $theme_button_icon_all,
	]);
	// Hide the back button initially
	echo button::create([
		'id' => 'btn_back',
		'label' => $text['button-back'],
		'icon' => $theme_button_icon_back,
		'style' => 'display: none;',
	]);
}

if (!$settings->get('active_calls', 'remove_completed_calls', true)) {
	// Clear rows (development)
	echo button::create([
		'id' => 'btn_clear',
		'label' => $text['button-clear'] ?? 'clear',
		'icon' => $theme_button_icon_broom,
		'style' => 'display: inline-block;',
		'onclick' => 'clear_rows()'
	]);
}

if (permission_exists('call_active_hangup')) {
	// Hangup selected calls
	echo button::create([
		'id' => 'btn_hangup_all',
		'type' => 'button',
		'label' => $text['label-hangup'],
		'icon' => 'phone-slash',
		'onclick' => "if (confirm('" . $text['confirm-hangup'] . "')) { "
		. "hangup_selected();"
		. "} else { "
		. "this.blur(); "
		. "return false; "
		. "}",
	]) . "\n";
}
echo "	</div>\n";
echo "	<div style='clear: both;'></div>\n";
echo "</div>\n";
echo $text['description'] . "\n";
echo "<br /><br />\n";
echo "	<div class='card'>\n";
echo "		<div class='table_wrapper'>\n";
echo "			<table id='calls_active'>\n";
echo "				<thead>\n";
echo "					<tr class='list-header'>\n";
if (permission_exists('call_active_hangup')) {
	echo "					<th class='checkbox'>\n";
	echo "						<input type='checkbox' id='checkbox_all' name='checkbox_all'>\n";
	echo "					</th>\n";
}
if (permission_exists('call_active_direction')) {
	echo "						<th class='hide-small'>" . $text['label-direction'] . "</th>\n";
}
if (permission_exists('call_active_profile')) {
	echo "						<th class='hide-small'>" . $text['label-profile'] . "</th>\n";
}
echo "						<th>" . $text['label-duration'] . "</th>\n";
echo "						<th id='th_domain' style='width: 185px; display: none;'>" . $text['label-domain'] . "</th>\n";
echo "						<th class='hide-small'>" . $text['label-cid-name'] . "</th>\n";
echo "						<th>" . $text['label-cid-number'] . "</th>\n";
echo "						<th>" . $text['label-destination'] . "</th>\n";
if (permission_exists('call_active_application')) {
	echo "						<th class='hide-small hide-medium'>" . $text['label-app'] . "</th>\n";
}
if (permission_exists('call_active_codec')) {
	echo "						<th class='hide-small hide-medium'>" . $text['label-codec'] . "</th>\n";
}
if (permission_exists('call_active_secure')) {
	echo "						<th class='hide-small hide-medium'>" . $text['label-secure'] . "</th>\n";
}
if (permission_exists('call_active_eavesdrop') || permission_exists('call_active_hangup')) {
	echo "						<th style='width: 216px;'>&nbsp;</th>\n";
}
echo "					</tr>\n";
echo "				</thead>\n";
echo "				<tbody id='calls_active_body'>\n";
echo "				</tbody>\n";
echo "			</table>\n";
// After the table, put a generic hangup and eavesdrop button that we can clone
if (permission_exists('call_active_hangup')) {
	echo button::create([
		'id' => 'btn_hangup',
		'type' => 'button',
		'style' => 'display: none;',
		'label' => $text['label-hangup'],
		'onclick' => "if (confirm('" . $text['confirm-hangup'] . "')) { "
			. "hangup_selected(this);"
			. "} else { "
			. "this.blur(); "
			. "return false; "
			. "}",
		'icon' => 'phone-slash',
	]) . "\n";
}
echo "		</div>\n";
echo "	</div>\n";
if (permission_exists('call_active_eavesdrop')) {
	echo button::create([
		'id' => 'btn_eavesdrop'
		, 'type' => 'button'
		, 'label' => $text['label-eavesdrop']
		, 'icon' => 'headphones'
		, 'collapse' => 'hide-lg-dn'
		, 'style' => 'display: none;'
	]);
}

echo "  <input id='current_context' type='hidden' name='current_context' value='" . $_SESSION['domain_name'] . "'>\n";

echo "	<input id='token' type='hidden' name='" . $token['name'] . "' value='" . $token['hash'] . "'>\n";

//
// Pass the token array, websocket services to subscribe to, and time limit to
// the subscriber class so that when this subscriber makes a websocket
// connection, the subscriber object can validate the information.
//
subscriber::save_token($token, ['active.calls']);

//break the caching
$version = md5(file_get_contents(__DIR__, '/resources/javascript/websocket_client.js'));
echo "<script src='resources/javascript/websocket_client.js?v=$version'></script>\n";
$version = md5(file_get_contents(__DIR__, '/resources/javascript/arrow.js'));
echo "<script src='resources/javascript/arrows.js?v=$version'></script>\n";
?>
<script>
	const timers = [];
	const callsMap = new Map();

	var showAll = false;
	const websockets_domain_name = '<?= $_SESSION['domain_name'] ?>';

	// push PHP values into JS
	const authToken = {
		name: "<?= $token['name'] ?>",
		hash: "<?= $token['hash'] ?>"
	};

	// show the user extensions for eavesdrop
<?php
	$user['extensions'] = [];
	// translate the current users assigned extensions
	if (!empty($_SESSION['user']['extension'])) {
		echo "const extension = {\n";
		foreach ($_SESSION['user']['extension'] as $user) {
			echo "		extension_uuid: '" . $user['extension_uuid'] . "',\n";
			echo "		extension: '" . $user['user'] . "',\n";
			if (strlen($user['number_alias']) > 0) {
				$user_contact = $user['number_alias'];
			} else {
				$user_contact = $user['user'];
			}
			echo "		extension_destination: '$user_contact',\n";
			$user['extensions'][$user['extension_uuid']] = $user_contact;
		}
		echo "	last_entry_so_no_comma: '-100'";
		echo "	};\n";
	}
?>

	const colors = {
		RINGING: 'blue',
		CONNECTED: '<?php echo $settings->get('theme', 'heading_count_background_color', '#28a745'); ?>',
		HANGUP: 'red',
		INACTIVE: 'black'
	}

	const truncate_application_data_length = <?php echo $settings->get('active_calls', 'truncate_application_data_length', 80); ?>;
	const truncate_application_data = truncate_application_data_length > 0;

	const overlay = document.getElementById('overlay');

	// pre-cache arrows
	const arrows = {
		inbound: {
			red: create_arrow('inbound', colors.HANGUP),
			green: create_arrow('inbound', colors.CONNECTED),
			blue: create_arrow('inbound', colors.RINGING),
			black: create_arrow('inbound', colors.INACTIVE)
		},
		outbound: {
			red: create_arrow('outbound', colors.HANGUP),
			green: create_arrow('outbound', colors.CONNECTED),
			blue: create_arrow('outbound', colors.RINGING),
			black: create_arrow('outbound', colors.INACTIVE)
		},
		local: {
			red: create_arrow('local', colors.HANGUP),
			green: create_arrow('local', colors.CONNECTED),
			blue: create_arrow('local', colors.RINGING),
			black: create_arrow('local', colors.INACTIVE)
		},
		voicemail: {
			red: create_arrow('voicemail', colors.HANGUP),
			green: create_arrow('voicemail', colors.CONNECTED),
			blue: create_arrow('voicemail', colors.RINGING),
			black: create_arrow('voicemail', colors.INACTIVE)
		},
		missed: {
			red: create_arrow('missed', colors.HANGUP),
			green: create_arrow('missed', colors.CONNECTED),
			blue: create_arrow('missed', colors.RINGING),
			black: create_arrow('missed', colors.INACTIVE)
		}
	}

	let client = null;
	let reconnectAttempts = 0;

	function connectWebsocket() {
		const maxReconnectDelay = 30000; // 30 seconds
		const baseReconnectDelay = 1000; // 1 second

		client = new ws_client(`wss://${window.location.hostname}/websockets/`, authToken);

		if (!client) {
			console.error('Unable to connect to web socket server');
		}

		// CONNECTED
		client.ws.addEventListener("open", async () => {
			try {
				console.log('Connected');
				console.log('Requesting authentication');
				await client.request('authentication');
				reconnectAttempts = 0;
				const status = document.getElementById('calls_active_count');
				status.style.backgroundColor = colors.INACTIVE;
				bindEventHandlers(client);
				console.log('Sent request for calls in progress');
				client.request('active.calls', 'in.progress');
				status.style.backgroundColor = colors.CONNECTED;
			} catch (err) {
				console.error("WS setup failed: ", err);
				return;
			}
		});

		// DISCONNECTED
		client.ws.addEventListener("close", async () => {
			const status = document.getElementById('calls_active_count');
			status.style.background = '#cc0033';
			console.warn("Websocket Disconnected");

			// reconnect to web socket server
			reconnectAttempts++;

			// delay timer to reload page
			const auto_reload_seconds = <?php echo $settings->get('active_calls', 'auto_reload_seconds', 0); ?>;
			if (auto_reload_seconds > 0) {
				console.log(`Reloading in ${auto_reload_seconds}s...`);

				// after waiting, reconnect
				setTimeout(() => {window.location.reload()}, auto_reload_seconds * 1000);
			}
		})

		// wire up “select all” checkbox
		document.getElementById("checkbox_all").addEventListener("change", e => {
			document.querySelectorAll("#calls_active_body input[type=checkbox]").forEach(cb => cb.checked = e.target.checked);
		});

<?php if (permission_exists('call_active_all')): ?>
		// Show all listener
		const btn_show_all = document.getElementById('btn_show_all');
		btn_show_all.addEventListener('click', e => {
			showAll = true;
			const domain_header = document.getElementById('th_domain');
			const tbody = document.getElementById("calls_active_body");
			//hide the show_all button
			btn_show_all.style.display = 'none';
			//show back button
			btn_back.style.display = 'inline-block';
			domain_header.style.display = 'table-cell';
			for (var i = 0; i < tbody.rows.length; i++) {
				const uuid = tbody.rows[i].id;
				const caller_context = document.getElementById(`caller_context_${uuid}`);
				caller_context.style.display = domain_header.style.display;
				if (showAll) {
					tbody.rows[i].style.display = 'table-row';
				} else {
					tbody.rows[i].style.display = 'none';
				}
			}
			updateCount();
		});
		const btn_back = document.getElementById('btn_back');
		btn_back.addEventListener('click', async (e) => {
			// Get the state from server
			// TODO: Implement permission retrieval from the server and compare to current caller context
			//showAll = await getShowAllState();
			showAll = false;
			const domain_header = document.getElementById('th_domain');
			const tbody = document.getElementById("calls_active_body");
			btn_show_all.style.display = 'inline-block';
			btn_back.style.display = 'none';
			domain_header.style.display = 'none';
			for (var i = 0; i < tbody.rows.length; i++) {
				const uuid = tbody.rows[i].id;
				const caller_context = document.getElementById(`caller_context_${uuid}`);
				caller_context.style.display = domain_header.style.display;
				if (showAll) {
					tbody.rows[i].style.display = 'table-row';
				} else {
					if (caller_context.textContent !== '<?= $_SESSION['domain_name'] ?>') {
						tbody.rows[i].style.display = 'none';
					}
				}
			}
			updateCount();
		});
<?php endif; ?>
	}

	/////////////////////
	// Event Functions //
	/////////////////////
	function bindEventHandlers(client) {
		client.onEvent("CHANNEL_CALLSTATE", channel_callstate_event);
		client.onEvent("CHANNEL_EXECUTE", channel_execute_event);
<?php if (permission_exists('call_active_application')): ?>
		client.onEvent("PLAYBACK_START", playback_start_event);
		client.onEvent("PLAYBACK_STOP", playback_stop_event);
		client.onEvent("CHANNEL_APPLICATION", channel_application_event);
<?php endif; ?>
		client.onEvent("valet_parking::info", valet_parking_info_event);
		client.onEvent("HEARTBEAT", heartbeat_event);
//		client.onEvent("CHANNEL_STATE", channel_state_event);	//Too many events
	}

	// Ringing, Answer, Hangup
	function channel_callstate_event(call) {
		const state = call.answer_state;
		//update color
		const uuid = call.unique_id;
		//console.log(call.event_name, call.unique_id, state, call);
		let row = document.getElementById(uuid) || null;
		//create a row for the call
		if (row === null) {
			new_call(call);
		}
		const other_leg_rdnis = call.other_leg_rdnis ?? '';
		const other_leg_unique_id = call.other_leg_unique_id ?? '';
		switch (state) {
			case 'ringing':
				update_call(call);
				replace_arrow_color(uuid, colors.RINGING);
				//enforce a local arrow for eavesdrop
				if (call.caller_caller_id_name === '<?= $text['label-eavesdrop'] ?>') {
					replace_arrow_icon(uuid, 'local');
					row.dataset.forced_direction = 'local';
				}
				//calls with an rdnis means that it came from an outside source
				if (other_leg_rdnis !== '') {
					replace_arrow_icon(uuid, 'inbound');
				} else {
					if (other_leg_unique_id !== '') {
						const matched_call = document.getElementById(other_leg_unique_id);
						if (matched_call.dataset.forced_direction) {
							replace_arrow_icon(uuid, matched_call.dataset.forced_direction);
						}
					} else {
						replace_arrow_icon(uuid, call.call_direction);
					}
				}
				break;
			case 'answered':
				update_call(call);
				//console.log('ANSWERED', call);
				if (row !== null) {
					replace_arrow_color(uuid, colors.CONNECTED);
				}
				break;
			case 'hangup':
				if (row !== null)
					replace_arrow_color(uuid, colors.HANGUP);
				hangup_call(call);
				break;
		}
	}

	function channel_execute_event(call) {
		//console.log(call.event_name, call.unique_id, call);

		// Set some values that we will use
		const uuid = call.unique_id;
		const row = document.getElementById(uuid);
		if (!row) { return; }
		const direction = call.variable_call_direction ?? '';

		// use application field to help determine arrows
		<?php if (permission_exists('call_active_application')): ?>
		const application = call.application ?? null;
		if (application !== null) {
			const application_data = call.application_data ?? application;

			//detect voicemail calls
			if (application_data === 'app.lua voicemail') {
				replace_arrow_icon(uuid, 'voicemail');
				row.dataset.forced_direction = 'voicemail';
			}

			//detect outbound calls
			if (application_data === 'call_direction=outbound') {
				replace_arrow_icon(uuid, 'outbound');
				row.dataset.forced_direction = 'outbound';
			}

			//detect public calls coming to this domain when not showing all calls
			if (!showAll && call.variable_domain_uuid === '<?= $_SESSION['domain_uuid'] ?>' && call.answer_state === 'ringing') {
				//console.log('public call', uuid, call);
				row.style.display = 'table-row';
				//if direction is not set then set it as inbound
				if (direction === '') {
					replace_arrow_icon(uuid, 'inbound');
					row.dataset.forced_direction = 'inbound';
				}
			}

			//detect inbound calls
			if (application_data === 'call_direction=inbound') {
				replace_arrow_icon(uuid, 'inbound');
				row.dataset.forced_direction = 'inbound';
			}

			//detect local calls
			const variable_user_exists = call.variable_user_exists ?? null;
			const variable_from_user_exists = call.variable_from_user_exists ?? null;
			if (application_data === 'call_direction=local') {
				replace_arrow_icon(uuid, 'local');
				row.dataset.forced_direction = 'local';
			}
			if (variable_user_exists === 'true' && variable_from_user_exists === 'true') {
				replace_arrow_icon(uuid, 'local');
				row.dataset.forced_direction = 'local';
			}
			console.log('application', uuid, application_data);
			update_call_element(`application_${uuid}`, application_data);
		}
		<?php endif; ?>
	}

<?php if (permission_exists('call_active_application')): ?>
	// react to capture the playback to update application
	function playback_start_event(call) {
		//console.log(call.event_name, call.unique_id, call);
		const tbody = document.getElementById("calls_active_body")
		if (callsMap.has(call.unique_id)) {
			const uuid = call.unique_id;
			const file = call.playback_file_path;
			const file_basename = basename(file);
			const play_string = `play:${file_basename}`;

			//update application cell
			update_call_element(`application_${uuid}`, play_string);

		}
	}

	function playback_stop_event(call) {
		//console.log(call.event_name, call.unique_id, call);
		const tbody = document.getElementById("calls_active_body")
		if (callsMap.has(call.unique_id)) {
			const uuid = call.unique_id;

			//update application cell
			update_call_element(`application_${uuid}`, 'play:stopped');
		}
	}

	//update the application cell
	function channel_application_event(call) {
		//console.log(call.event_name, call.unique_id, call);
		const tbody = document.getElementById("calls_active_body");
		if (!callsMap.has(call.unique_id)) {
			update_call_element(`application_${uuid}`, call.application_name);
		}
	}
<?php endif; ?>

	// CALL PARK
	// This is possible because we have promoted the Event-Subclass name
	// when the Event-Class is 'CUSTOM'
	// This can be disabled with the -b command line option
	function valet_parking_info_event(call) {
		const state = call.answer_state;
		//console.log(call.event_name, call.unique_id, state, call);
		if (call.action === 'hold') {
			//get the call park info
			const uuid = call.unique_id;
			const application = call.variable_current_application;
			const valet_extension = call.valet_extension;
			const parked_by = call.variable_referred_by_user;
			const origin_name = call.variable_pre_transfer_caller_id_name;
			const parking_timeout = call.variable_valet_parking_timeout;
			update_call_element(`caller_id_number_${uuid}`, `${parked_by} -> ${valet_extension}`);
			update_call_element(`caller_id_name_${uuid}`, origin_name);
			//remove the call arrow
			const span = document.getElementById(`arrow_${uuid}`);
			if (!span) { return; }
			span.removeChild(span.firstChild);
		}
		if (call.topic === 'exit') {
			//do something
		}
	}

	function heartbeat_event(call) {
		console.log(call.event_name);
	}

	function channel_state_event(call) {
		//console.log(call.event_name, call.channel_state, call.unique_id, call);
	}

	//////////////////////
	// Helper functions //
	//////////////////////

	function replace_arrow_color(uuid, color) {
		const row = document.getElementById(uuid);
		if (!row) { return; }

		//get the table cell
		const span = document.getElementById(`arrow_${uuid}`) ?? null;
		if (!span) { return; }
		const icon = span.dataset.icon ?? 'local';

		//nothing to do
		if (color === span.dataset.color) {
			return;
		}

		span.dataset.icon = icon;
		span.dataset.color = color;

		//copy the cached arrow
		const cached_arrow = arrows[icon][color];
		const arrow = cached_arrow.cloneNode(true);

		//check for exiting arrow and add or replace
		const span_arrow = span.firstChild ?? null;
		if (span_arrow !== null) {
			span.replaceChild(arrow, span_arrow);
		} else {
			span.appendChild(arrow);
		}
	}

	function replace_arrow_icon(uuid, icon) {

		const row = document.getElementById(uuid);
		if (!row) {	return; }

		//get the table cell
		const span = document.getElementById(`arrow_${uuid}`) ?? null;
		if (!span) { return; }
		const color = span.dataset.color ?? colors.RINGING;


		if (span.dataset.icon === null) {
			throw Exception('icon empty');
		}

		//nothing to do
		if (icon === span.dataset.icon) {
			return;
		}

		span.dataset.icon = icon;
		span.dataset.color = color;

		//copy the cached arrow
		const cached_arrow = arrows[icon][color];
		const arrow = cached_arrow.cloneNode(true);

		const span_arrow = span.firstChild ?? null;
		if (span_arrow !== null) {
			span.replaceChild(arrow, span_arrow);
		} else {
			span.appendChild(create_arrow(icon, color));
		}
	}

	function new_call(call) {
		//console.log(call);
		const tbody = document.getElementById("calls_active_body");
		if (!callsMap.has(call.unique_id)) {
			// create the row
			const uuid = call.unique_id;

			//set the profile
<?php if (permission_exists('call_active_profile')): ?>
			const profile = call?.caller_channel_name.split('/')[1] ?? '';
<?php endif; ?>

<?php if (permission_exists('call_active_codec')): ?>
			//set the codec
			const read_codec = call.channel_read_codec_name ?? '';
			const read_rate = call.channel_read_codec_rate ?? '';
			const write_codec = call.channel_write_codec_name ?? '';
			const write_rate = call.channel_write_codec_rate ?? '';
			const codec = `${read_codec}:${read_rate} / ${write_codec}:${write_rate}`
<?php endif; ?>

			//create or get the row
			let row = document.getElementById(uuid);
			if (!row) {
				row = document.createElement("tr");
			}
			row.id = uuid;
			row.className = 'list-row';
			row.dataset.color = colors.RINGING;

			// start string block
			row.innerHTML = `<?php
			if (permission_exists('call_active_hangup')) {
				echo '<td id="checkbox_${uuid}" class="checkbox">'.PHP_EOL;
				echo '	<input type="checkbox" data-uuid="${uuid}">'.PHP_EOL;
				echo '</td>'.PHP_EOL;
			}
			if (permission_exists('call_active_direction')) {
				echo '<td id="direction_${uuid}" class="hide-small"><span id="arrow_${uuid}"></span></td>'.PHP_EOL;
			}
			if (permission_exists('call_active_profile')) {
				echo '<td id="profile_${uuid}" class="hide-small">${profile}</td>'.PHP_EOL;
			}
			echo '<td id="duration_${uuid}"></td>'.PHP_EOL;
			if (permission_exists('call_active_all')) {
				echo '<td id="caller_context_${uuid}" style="display: none;">${call.caller_context}</td>'.PHP_EOL;
			}
			echo '<td id="caller_id_name_${uuid}" class="hide-small">${call.caller_caller_id_name}</td>'.PHP_EOL;
			echo '<td id="caller_id_number_${uuid}">${call.caller_caller_id_number}</td>'.PHP_EOL;
			echo '<td id="destination_${uuid}">${call.caller_destination_number}</td>'.PHP_EOL;
			if (permission_exists('call_active_application')) {
				echo '<td id="application_${uuid}" class="hide-small hide-medium">${call.caller_destination_number}</td>'.PHP_EOL;
			}
			if (permission_exists('call_active_codec')) {
				echo '<td id="codec_${uuid}" class="hide-small hide-medium">${codec}</td>'.PHP_EOL;
			}
			if (permission_exists('call_active_secure')) {
				echo '<td id="secure_${uuid}" class="hide-small hide-medium">&nbsp;</td>'.PHP_EOL;
			}
			if (permission_exists('call_active_hangup') || permission_exists('call_active_eavesdrop')) {
				echo '<td id="commands_${uuid}" class="button right">'.PHP_EOL;
					echo '<span>'.PHP_EOL;
					if (permission_exists('call_active_hangup')) {
						echo '<span id="span_hangup_${uuid}"></span>'.PHP_EOL;
					}
					if (permission_exists('call_active_eavesdrop')) {
						echo '<span id="span_eavesdrop_${uuid}"></span>'.PHP_EOL;
					}
					echo '</span>'.PHP_EOL;
				echo '</td>'.PHP_EOL;
			}
?>`;
//end string block
			if (websockets_domain_name === call.caller_context || showAll) {
				row.style.display = 'table-row';
			} else {
				row.style.display = 'none';
			}

			// add the row to the table
			tbody.appendChild(row);

			console.log('NEW ROW ADDED', row.id);

			// Hide/show domain column
			const domain = document.getElementById('th_domain');
			document.getElementById(`caller_context_${call.unique_id}`).style.display = domain.style.display;

			// start the timer
			start_duration_timer(call.unique_id, call.caller_channel_created_time);

			// add the uuid to the map
			callsMap.set(call.unique_id, row);

<?php /* add hangup button */ if (permission_exists('call_active_hangup')): ?>
				const hangup = document.getElementById('btn_hangup').cloneNode(true);
				const span_hangup = document.getElementById(`span_hangup_${uuid}`);
				hangup.id = `btn_hangup_${uuid}`;
				hangup.name = `btn_hangup_${uuid}`;
				hangup.style.display = 'inline-block';
				hangup.dataset.row_id = uuid;
				span_hangup.appendChild(hangup);
<?php endif; ?>

<?php /* add eavesdrop button */ if (permission_exists('call_active_eavesdrop') && !empty($user['extensions'])): ?>
				// Don't add an eavesdrop button for an eavesdrop call
				if (call.caller_caller_id_name !== '<?= $text['label-eavesdrop'] ?>') {
					const eavesdrop = document.getElementById('btn_eavesdrop').cloneNode(true);
					const span_eavesdrop = document.getElementById(`span_eavesdrop_${call.unique_id}`);
					eavesdrop.id = `btn_eavesdrop_${call.unique_id}`;
					eavesdrop.name = `btn_eavesdrop_${call.unique_id}`;
					eavesdrop.style.display = 'inline-block';
					eavesdrop.addEventListener('click', async e => {
						//send command to server to eavesdrop on call
						//console.log('eavesdrop:', call);
						client.request('active.calls', 'eavesdrop', {
							unique_id: call.unique_id, //$channnel_uuid
							origination_caller_id_name: '<?= $text['label-eavesdrop'] ?>', //origination_caller_id_name=
							origination_caller_contact: extension.extension_destination, //extension_destination
							caller_caller_id_number: call.caller_caller_id_number, //origination_caller_id_number=
							caller_destination_number: call.caller_destination_number	//$destination
						})
					});
					span_eavesdrop.appendChild(eavesdrop);
				}
<?php endif; ?>
		}
		updateCount();
	}

	function toggleOverlay(visible) {
		overlay.classList.toggle('hidden', !visible);
	}

	function update_call(call) {

		const tbody = document.getElementById("calls_active_body")
		if (callsMap.has(call.unique_id)) {

			//set values
			const uuid = call.unique_id;
			const row = document.getElementById(uuid);
			<?php if (permission_exists('call_active_profile')): ?>
				const caller_channel_name = call?.caller_channel_name.split('/')[1] ?? '';
			<?php endif; ?>
			<?php if (permission_exists('call_active_all')): ?>
				const caller_context = call.caller_context ?? '';
			<?php endif; ?>
			const caller_caller_id_name = call.caller_caller_id_name ?? '';
			const caller_caller_id_number = call.caller_caller_id_number ?? '';
			const caller_destination_number = call.caller_destination_number ?? '';
			<?php if (permission_exists('call_active_application')): ?>
				const application_name = call.application_name ?? '';
			<?php endif; ?>
			<?php if (permission_exists('call_active_codec')): ?>
				const read_codec_name = call.channel_read_codec_name ?? '';
				const read_codec_rate = call.channel_read_codec_rate ?? '';
				const write_codec_name = call.channel_write_codec_name ?? '';
				const write_codec_rate = call.channel_write_codec_rate ?? '';
				const codec = `${read_codec_name}:${read_codec_rate} / ${write_codec_name}:${write_codec_rate}`
			<?php endif; ?>
			<?php if (permission_exists('call_active_secure')): ?>
				const secure = call.secure ?? '';
			<?php endif; ?>

			//update table cells
			<?php if (permission_exists('call_active_profile')): ?>
				update_call_element(`profile_${uuid}`, caller_channel_name);
			<?php endif; ?>
			<?php if (permission_exists('call_active_all')): ?>
				update_call_element(`caller_context_${uuid}`, caller_context);
				//check if the context changes to this domain
				if (caller_context === '<?= $_SESSION['domain_name'] ?>') {
					row.style.display = 'table-row';
				}
			<?php endif; ?>
				update_call_element(`caller_id_name_${uuid}`, caller_caller_id_name);
				update_call_element(`caller_id_number_${uuid}`, caller_caller_id_number);
				update_call_element(`destination_${uuid}`, caller_destination_number);
			<?php if (permission_exists('call_active_application')): ?>
				update_call_element(`application_${uuid}`, application_name);
			<?php endif; ?>
			<?php if (permission_exists('call_active_codec')): ?>
				update_call_element(`codec_${uuid}`, codec);
			<?php endif; ?>
			<?php if (permission_exists('call_active_secure')): ?>
				update_call_element(`secure_${uuid}`, secure);
			<?php endif; ?>
		}

	}

	function hangup_call(call) {
		const row = callsMap.get(call.unique_id);
		if (row) {
			const uuid = call.unique_id;
			remove_button_by_id(`span_hangup_${uuid}`);
			remove_button_by_id(`span_eavesdrop_${uuid}`);
			<?php if (permission_exists('call_active_codec')): ?>
				const codec = document.getElementById(`codec_${uuid}`) ?? null;
				if (codec.textContent === ': / :') {
					replace_arrow_icon(uuid, 'missed');
				}
			<?php endif; ?>
			if (<?php /* DEBUGGING OPTION */ echo $settings->get('active_calls','remove_completed_calls', true) ? 'true': 'false'; ?>) {
				row.remove();
			}
			callsMap.delete(uuid);
			stop_duration_timer_and_update_call_count(uuid);
			updateCount();
		}
	}

	// Hangup the checked calls
	function hangup_selected(button) {

		if (button) {
			client.request('active.calls', 'hangup', {unique_id: button.dataset.row_id});
			return;
		}

		const checked = document.querySelectorAll('#calls_active_body input[type="checkbox"]:checked');

		if (checked.length === 0) {
			alert('No calls selected.');
			return;
		}

		checked.forEach(function (checkbox) {
			const row = checkbox.closest('tr');
			if (row) client.request('active.calls', 'hangup', {unique_id: row.id});
		});

		const checkbox_all = document.getElementById('checkbox_all');
		if (checkbox_all) checkbox_all.checked = false;
	}

	function remove_button_by_id(button_id) {
		const button = document.getElementById(button_id);
		if (button)
			button.remove();
	}

	function clear_rows() {
		const tbody = document.getElementById('calls_active_body');
		tbody.innerHTML = "";
	}

	// Only updates the field when the data is not empty and has changed
	function update_call_element(element_name, value) {
		if (typeof value === 'undefined')
			return;
		// Check if we need to truncate the application data length
		if (truncate_application_data && element_name.startsWith('application_') && value.length > truncate_application_data_length) {
			value = value.substring(0, truncate_application_data_length);
		}
		const element = document.getElementById(element_name);
		if (element !== null && value !== null && value.length > 0 && element.textContent !== value) {
			element.textContent = value;
		}
	}

	function basename(path) {
		return path.replace(/^.*[\\\/]/, '')
	}

	function updateCount() {
		const calls_active_count = document.getElementById('calls_active_count');

		let visibleCount = 0;
		callsMap.forEach((row) => {
			if (row.style.display !== 'none') {
				visibleCount++;
			}
		});

		const totalCount = callsMap.size;
		calls_active_count.textContent = `${visibleCount}`;
	}

	function start_duration_timer(uuid, start_time) {
		const td = document.getElementById(`duration_${uuid}`)

		// Render function closes over startMs
		function render() {
			//calculate already elapsed time
			const start = new Date(start_time / 1000);
			const now = new Date();
			const elapsed = Math.floor(now.getTime() - start.getTime());

			//format time
			const hh = Math.floor(elapsed / (1000 * 3600)).toString();
			const mm = Math.floor((elapsed % (1000 * 3600)) / (1000 * 60)).toString().padStart(2, "0");
			const ss = Math.floor((elapsed % (1000 * 60)) / 1000).toString().padStart(2, "0"); // Convert remaining milliseconds to seconds

			td.textContent = `${hh}:${mm}:${ss}`;
		}

		render();
		const timerId = setInterval(render, 1000);

		timers[uuid] = timerId

		// Return stop function
		return () => clearInterval(timerId);
	}

	function stop_duration_timer_and_update_call_count(uuid) {
		//clear the timer on the row
		const timer_id = timers[uuid]
		if (timer_id) {
			clearInterval(timer_id)
			delete timers[uuid]
		}
		//update the row count aka call count after row is removed
		updateCount();
	}

	//////////////////////////
	// Start the connection //
	//////////////////////////
	connectWebsocket();
</script>

<?php require_once "resources/footer.php"; ?>
