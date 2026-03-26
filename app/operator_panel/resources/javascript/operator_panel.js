/**
 * Operator Panel — JavaScript controller
 *
 * Three-tab, fully WebSocket-driven panel: Calls | Conferences | Agents.
 * NO Ajax is used anywhere. All data arrives as WebSocket events, and all
 * operator actions (hangup, transfer, eavesdrop, record, user status, agent
 * status) are sent as WebSocket action requests.
 *
 * Depends on:
 *   websocket_client.js  — ws_client class
 *   ws_config            — PHP-injected WS settings
 *   status_colors        — PHP-injected theme colours
 *   status_icons         — PHP-injected FA icon classes
 *   status_tooltips      — PHP-injected translated strings
 *   status_show_icon     — PHP-injected boolean for icon visibility
 *   permissions          — PHP-injected permission booleans
 *   text                 — PHP-injected translated strings
 *   domain_name          — PHP-injected session domain
 *   user_uuid            — PHP-injected session user UUID
 *   token                — PHP-injected WS auth token {name, hash}
 *   user_statuses        — PHP-injected array of allowed status strings
 */

'use strict';

/** @type {ws_client|null} */
let ws = null;

let reconnect_attempts = 0;
let ping_interval_timer = null;
let ping_timeout = null;
let auth_timeout = null;
let refresh_interval_timer = null;
let pong_failure_count = 0;
let duration_tick_timer = null;
let extensions_reconcile_timer = null;
let recording_state_timer = null;
let registrations_state_timer = null;

/**
 * Live call map: uuid → call object.
 * Maintained incrementally from channel events.
 * @type {Map<string, object>}
 */
const calls_map = new Map();

/**
 * Live conference map: conference_name → conference object.
 * @type {Map<string, object>}
 */
const conferences_map = new Map();

/**
 * Extension directory: extension_number → extension object (from DB snapshot).
 * Registration status is included.  Call state is derived dynamically from calls_map.
 * @type {Map<string, object>}
 */
const extensions_map = new Map();

/**
 * Current agent stats list (last broadcast).
 * @type {Array<object>}
 */
let agents_list = [];

/** Debounce timer for extensions re-render triggered by channel events. */
let extensions_render_debounce = null;

/** UUID of the call being dragged from the Calls tab onto an extension block. */
let dragged_call_uuid = null;

/** Source extension number for a dragged call (when available). */
let dragged_call_source_extension = null;

/** Extension number being dragged (for origination). */
let dragged_extension = null;

/** UUID of the call being dragged from eavesdrop icon onto an extension block. */
let dragged_eavesdrop_uuid = null;

/** Calls flagged as actively recording in UI state. */
const recording_call_uuids = new Set();

/** Active group filters (set of lowercase group keys; empty = show all). */
const active_group_filters = new Set();

/** Whether edit mode (drag to reorder cards) is active. */
let edit_mode_active = false;

/** SortableJS instance for edit mode. */
let sortable_instance = null;

/** Saved card order (array of group keys). Persisted to localStorage. */
let saved_card_order = null;
let all_group_keys_for_filters = [];
let tabs_initialized = false;

function is_lop_debug_enabled() {
	if (typeof window !== 'undefined' && window.OP_DEBUG === true) return true;
	try {
		if (typeof localStorage !== 'undefined' && localStorage.getItem('op_debug') === '1') return true;
	} catch (err) {
		// Ignore storage access errors.
	}
	return false;
}

function is_lop_reg_trace_enabled() {
	if (typeof window !== 'undefined' && window.OP_REG_TRACE_ENABLED === true) return true;
	try {
		if (typeof localStorage !== 'undefined' && localStorage.getItem('op_reg_trace') === '1') return true;
	} catch (err) {
		// Ignore storage access errors.
	}
	return false;
}

function lop_debug(label, data) {
	const is_reg_trace = typeof label === 'string' && label.indexOf('[OP_REG_TRACE]') !== -1;
	if (!is_lop_debug_enabled() && !(is_reg_trace && is_lop_reg_trace_enabled())) {
		return;
	}
	const now = new Date();
	const ts = now.toISOString().replace('T', ' ').replace('Z', '');
	if (typeof data === 'undefined') {
		console.debug(`[${ts}] ${label}`);
	} else {
		console.debug(`[${ts}] ${label}`, data);
	}
}

function activate_tab(button) {
	if (!button) return;

	// Prefer Bootstrap Tab API when available.
	if (typeof bootstrap !== 'undefined' && bootstrap.Tab) {
		try {
			bootstrap.Tab.getOrCreateInstance(button).show();
			return;
		} catch (err) {
			console.warn('[OP] Bootstrap tab show failed, using fallback:', err);
		}
	}

	// Fallback tab activation when Bootstrap JS API is unavailable.
	const target_selector = button.getAttribute('data-bs-target');
	if (!target_selector) return;
	const target = document.querySelector(target_selector);
	if (!target) return;

	document.querySelectorAll('#lop_tabs .nav-link').forEach(link => {
		link.classList.remove('active');
		link.setAttribute('aria-selected', 'false');
	});
	document.querySelectorAll('#lop_tab_content .tab-pane').forEach(pane => {
		pane.classList.remove('show', 'active');
	});

	button.classList.add('active');
	button.setAttribute('aria-selected', 'true');
	target.classList.add('show', 'active');
}

function init_tab_navigation() {
	if (tabs_initialized) return;

	const buttons = document.querySelectorAll('#lop_tabs .nav-link[data-bs-target]');
	if (!buttons.length) return;

	buttons.forEach(btn => {
		btn.addEventListener('click', function (event) {
			event.preventDefault();
			activate_tab(btn);
		});
	});

	tabs_initialized = true;
}

function normalize_group_key(raw_group) {
	const key = ((raw_group || '') + '').trim().toLowerCase();
	return key || '';
}

function get_extension_group_key(ext_number) {
	const ext = extensions_map.get((ext_number || '').toString());
	if (!ext) return '';
	return normalize_group_key(ext.call_group || '');
}

function get_call_group_key(call) {
	const presence = ((call.channel_presence_id || '').split('@')[0] || '').trim();
	const dest     = ((call.caller_destination_number || '') + '').trim();
	const cid      = ((call.caller_caller_id_number || call.caller_id_number || '') + '').trim();

	const candidates = [presence, dest, cid].filter(Boolean);
	for (const ext of candidates) {
		const key = get_extension_group_key(ext);
		if (key !== '' || extensions_map.has(ext)) return key;
	}
	return '';
}

/**
 * Resolve the opposite-party extension/number for a call leg.
 * Prefers direct per-leg fields; if those are ambiguous (self/self),
 * falls back to scanning sibling rows in calls_map for the same extension.
 */
function resolve_peer_number_for_leg(ch, ext_number, direction_raw) {
	const ext = ((ext_number || '') + '').trim();
	const cid = (((ch || {}).caller_caller_id_number || (ch || {}).caller_id_number || '') + '').trim();
	const dest = (((ch || {}).caller_destination_number || '') + '').trim();

	// Direct per-leg interpretation.
	if (ext) {
		if (dest === ext && cid && cid !== ext) return cid;
		if (cid === ext && dest && dest !== ext) return dest;
	}

	// Direction-guided fallback when one side is known and non-self.
	if ((direction_raw || '') === 'inbound') {
		if (cid && cid !== ext) return cid;
		if (dest && dest !== ext) return dest;
	}
	if ((direction_raw || '') === 'outbound') {
		if (dest && dest !== ext) return dest;
		if (cid && cid !== ext) return cid;
	}

	// Cross-leg fallback: find another row that references this extension
	// and exposes a non-self opposite party.
	if (ext) {
		for (const other of calls_map.values()) {
			if (other === ch) continue;
			const ocid = ((other.caller_caller_id_number || other.caller_id_number || '') + '').trim();
			const odest = ((other.caller_destination_number || '') + '').trim();
			if (ocid === ext && odest && odest !== ext) return odest;
			if (odest === ext && ocid && ocid !== ext) return ocid;
		}
	}

	// Last resort: return whichever side is not self, then any known side.
	if (dest && dest !== ext) return dest;
	if (cid && cid !== ext) return cid;
	return dest || cid || ext;
}

function set_filter_bar_visibility(show) {
	['extensions_filter_bar', 'calls_filter_bar', 'conferences_filter_bar', 'agents_filter_bar'].forEach(id => {
		const bar = document.getElementById(id);
		if (bar) bar.style.display = show ? '' : 'none';
	});
}

/**
 * Connect (or reconnect) to the WebSocket server.
 * Called once on page load from index.php.
 */
function connect_websocket() {
	const ws_url = `wss://${window.location.host}/websockets/`;

	try {
		ws = new ws_client(ws_url, token);

		// Authentication
		ws.on_event('authenticated',       on_authenticated);
		ws.on_event('authentication_failed', on_authentication_failed);

		ws.ws.addEventListener('open', () => {
			console.log('[OP] WebSocket open');
			reconnect_attempts = 0;
			update_connection_status('connecting');

			auth_timeout = setTimeout(() => {
				console.error('[OP] Authentication timeout');
				update_connection_status('disconnected');
				redirect_to_login();
			}, ws_config.auth_timeout);
		});

		ws.ws.addEventListener('close', (ev) => {
			console.warn('[OP] WebSocket closed, code:', ev.code);
			_clear_timers();
			update_connection_status('disconnected');

			const delay = Math.min(
				ws_config.reconnect_delay * Math.pow(2, reconnect_attempts),
				ws_config.max_reconnect_delay
			);
			reconnect_attempts++;
			console.log(`[OP] Reconnecting in ${delay}ms (attempt ${reconnect_attempts})`);
			setTimeout(connect_websocket, delay);
		});

		ws.ws.addEventListener('error', (err) => {
			console.error('[OP] WebSocket error:', err);
		});

	} catch (err) {
		console.error('[OP] Failed to create WebSocket:', err);
		update_connection_status('disconnected');
	}
}

function on_authentication_failed() {
	console.error('[OP] Authentication failed');
	update_connection_status('disconnected');
	redirect_to_login();
}

/**
 * Called once authentication succeeds.
 * Subscribes to the service topics and requests initial snapshots.
 */
function on_authenticated() {
	init_tab_navigation();

	console.log('[OP] Authenticated');
	pong_failure_count = 0;
	update_connection_status('warning');

	if (auth_timeout) { clearTimeout(auth_timeout); auth_timeout = null; }

	// Start keep-alive
	send_ping();
	if (ping_interval_timer) clearInterval(ping_interval_timer);
	ping_interval_timer = setInterval(send_ping, ws_config.ping_interval);

	// Start 1-second duration ticker
	if (duration_tick_timer) clearInterval(duration_tick_timer);
	duration_tick_timer = setInterval(tick_durations, 1000);

	// Star-code/auto-recording does not always produce consumable events,
	// so poll recording state by UUID.
	if (recording_state_timer) clearInterval(recording_state_timer);
	recording_state_timer = setInterval(sync_recording_state, 2000);

	// Reconcile registration flags in case registration_change events are missed.
	if (registrations_state_timer) clearInterval(registrations_state_timer);
	if (typeof registrations_reconcile_enabled !== 'undefined' && registrations_reconcile_enabled) {
		registrations_state_timer = setInterval(sync_registrations_state, 3000);
		sync_registrations_state();
	} else {
		lop_debug('[OP][reg][reconcile] disabled by setting registrations_reconcile_enabled');
	}

	// Register incremental event handlers before subscribing so we do not
	// miss the first pushed event that can arrive immediately after subscribe.
	// Call events
	const call_topics = [
		'channel_create', 'channel_callstate', 'call_update',
		'channel_destroy', 'channel_park', 'channel_unpark', 'valet_info',
	];
	call_topics.forEach(t => ws.on_event(t, on_call_event));
	ws.on_event('calls_active', on_calls_snapshot_event);

	// Conference events
	const conf_topics = [
		'conference_create', 'conference_destroy',
		'add_member', 'del_member',
		'start_talking', 'stop_talking',
		'mute_member', 'unmute_member',
		'deaf_member', 'undeaf_member',
		'floor_change', 'lock', 'unlock',
		'kick_member', 'energy_level', 'gain_level', 'volume_level',
	];
	conf_topics.forEach(t => ws.on_event(t, on_conference_event));

	// Agent stats broadcast
	ws.on_event('agent_stats',   on_agent_stats);

	// Registration events (extension register / unregister)
	ws.on_event('registration_change', on_registration_change);

	// Action responses
	ws.on_event('action_response', on_action_response);

	// Pong handler: resolves keep-alive pings that arrive as server-pushed events
	// (e.g. after reconnect when the pending request map was cleared)
	ws.on_event('pong', on_pong);

	// Fire subscribe first, then immediately request initial snapshots.
	// Do not wait on subscribe promise resolution because some deployments
	// do not send an explicit subscribe response, which would block loading.
	ws.subscribe('active.operator.panel').catch((err) => {
		console.error('[OP] Failed to subscribe to service:', err);
	});

	load_extensions_snapshot();
	load_calls_snapshot();
	load_conferences_snapshot();
	load_agents_snapshot();
	sync_recording_state();

	// One-time reconciliation shortly after auth to capture any early
	// registration changes that may race initial subscription startup.
	if (extensions_reconcile_timer) clearTimeout(extensions_reconcile_timer);
	extensions_reconcile_timer = setTimeout(() => {
		load_extensions_snapshot();
		extensions_reconcile_timer = null;
	}, 500);
}

/** Tear down all recurring timers. */
function _clear_timers() {
	if (auth_timeout)           { clearTimeout(auth_timeout);            auth_timeout           = null; }
	if (ping_timeout)           { clearTimeout(ping_timeout);            ping_timeout           = null; }
	if (ping_interval_timer)    { clearInterval(ping_interval_timer);    ping_interval_timer    = null; }
	if (refresh_interval_timer) { clearInterval(refresh_interval_timer); refresh_interval_timer = null; }
	if (duration_tick_timer)    { clearInterval(duration_tick_timer);    duration_tick_timer    = null; }
	if (extensions_reconcile_timer) { clearTimeout(extensions_reconcile_timer); extensions_reconcile_timer = null; }
	if (recording_state_timer)  { clearInterval(recording_state_timer);  recording_state_timer  = null; }
	if (registrations_state_timer) { clearInterval(registrations_state_timer); registrations_state_timer = null; }
}

/** Redirect to the FusionPBX login page. */
function redirect_to_login() {
	const base = (typeof PROJECT_PATH !== 'undefined') ? PROJECT_PATH : '';
	window.location.href = base + '/?path=' + encodeURIComponent(window.location.pathname);
}


// Keep-alive ping / pong
function send_ping() {
	if (!ws || !ws.ws || ws.ws.readyState !== WebSocket.OPEN) return;

	const had_failures = pong_failure_count > 0;

	ping_timeout = setTimeout(() => {
		pong_failure_count++;
		if (pong_failure_count >= ws_config.pong_timeout_max_retries) {
			update_connection_status('disconnected');
			window.location.reload();
		} else {
			update_connection_status('warning');
		}
	}, ws_config.pong_timeout);

	ws.request('active.operator.panel', 'ping', {})
		.then(() => {
			if (ping_timeout) { clearTimeout(ping_timeout); ping_timeout = null; }
			pong_failure_count = 0;
			update_connection_status('connected');
			if (had_failures) {
				// Refresh all four snapshots after a reconnect
				load_extensions_snapshot();
				load_calls_snapshot();
				load_conferences_snapshot();
				load_agents_snapshot();
			}
		})
		.catch(console.error);
}

/**
 * Handle a pong that arrives as a server-pushed event rather than as a
 * response to a pending request (e.g. after reconnect clears _pending).
 */
function on_pong() {
	if (ping_timeout) { clearTimeout(ping_timeout); ping_timeout = null; }
	pong_failure_count = 0;
	update_connection_status('connected');
}

function update_connection_status(state) {
	const el = document.getElementById('connection_status');
	if (!el) return;

	const color   = (status_colors   && status_colors[state])   || '#6c757d';
	const tooltip = (status_tooltips && status_tooltips[state]) || state;

	el.title = tooltip;
	el.style.backgroundColor = color;
	el.style.color = '#fff';

	const icon_el = document.getElementById('connection_status_icon');
	if (icon_el) {
		const icon = (status_icons && status_icons[state]) || '';
		icon_el.className = icon;
		icon_el.style.marginRight = '5px';
	}

	const text_el = document.getElementById('connection_status_text');
	if (text_el) {
		text_el.textContent = tooltip;
	}
}

function esc(text) {
	if (text === null || text === undefined) return '';
	return text.toString()
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;')
		.replace(/'/g, '&#039;');
}

function jsq(value) {
	return JSON.stringify(value === null || value === undefined ? '' : String(value));
}

function get_conference_action_icon(name, fallback) {
	if (conference_action_icons && conference_action_icons[name]) {
		return conference_action_icons[name];
	}
	return fallback;
}

/** Format a Unix microsecond timestamp as elapsed time hh:mm:ss */
function format_elapsed(us_timestamp) {
	if (!us_timestamp || us_timestamp === '0') return '--:--:--';
	const start = Math.floor(Number(us_timestamp) / 1000000);
	const now   = Math.floor(Date.now() / 1000);
	let sec     = Math.max(0, now - start);
	const h = Math.floor(sec / 3600); sec -= h * 3600;
	const m = Math.floor(sec / 60);   sec -= m * 60;
	return [h, m, sec].map(n => String(n).padStart(2, '0')).join(':');
}

/** Update all visible duration elements every second. */
function tick_durations() {
	document.querySelectorAll('[data-created]').forEach(el => {
		const ts = el.getAttribute('data-created');
		if (ts && ts !== '0') {
			el.textContent = format_elapsed(ts);
		}
	});
}

/** Format a Unix-second timestamp as HH:MM */
function format_time(unix_seconds) {
	if (!unix_seconds || unix_seconds === '0') return '--:--';
	const d = new Date(Number(unix_seconds) * 1000);
	return d.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
}

/** Normalize the call UUID field across snapshot and event payload variants. */
function get_call_uuid(ch) {
	if (!ch || typeof ch !== 'object') return '';
	return ch.unique_id || ch.uuid || ch.channel_uuid || '';
}

/**
 * Request a full calls snapshot from the service.
 * Snapshot response arrives as a topic='calls_active' event.
 */
function load_calls_snapshot() {
	if (!ws || ws.ws.readyState !== WebSocket.OPEN) return;
	ws.request('active.operator.panel', 'calls_active', {domain_name: domain_name})
		.then(response => {
			apply_calls_snapshot(response.payload || []);
		})
		.catch(console.error);
}

/** Apply a full calls snapshot array/object to calls_map and refresh UI. */
function apply_calls_snapshot(payload) {
	const channels = Array.isArray(payload)
		? payload
		: ((payload && Array.isArray(payload.rows)) ? payload.rows : []);

	calls_map.clear();
	channels.forEach(ch => {
		const uuid = get_call_uuid(ch);
		if (uuid) calls_map.set(uuid, ch);
	});

	render_calls_tab();
	// Re-render extensions so their active/idle state reflects calls snapshot.
	schedule_extensions_render();
}

/** Handle server-pushed full calls snapshot event (topic='calls_active'). */
function on_calls_snapshot_event(event) {
	const payload = event.payload || event.data || event;
	apply_calls_snapshot(payload);
}

/**
 * Handle incremental call / channel events from FreeSWITCH.
 * @param {object} event
 */
function on_call_event(event) {
	const name = (event.topic || event.event_name || '').toLowerCase();
	const uuid  = get_call_uuid(event);

	if (!uuid) return;

	switch (name) {
		case 'channel_create':
		case 'channel_callstate':
		case 'call_update':
		case 'channel_park':
		case 'channel_unpark':
		case 'valet_info':
			// Merge/upsert into the map
			calls_map.set(uuid, Object.assign(calls_map.get(uuid) || {}, event));
			break;

		case 'channel_destroy':
			recording_call_uuids.delete(uuid);
			calls_map.delete(uuid);
			break;

		default:
			calls_map.set(uuid, Object.assign(calls_map.get(uuid) || {}, event));
			break;
	}

	schedule_extensions_render();
	render_calls_tab();
}

function call_is_recording(ch, uuid) {
	if (ch && (ch.is_recording === true || ch.is_recording === 'true' || ch.is_recording === 1 || ch.is_recording === '1')) return true;
	const app = ((ch && (ch.variable_current_application || ch.application)) || '').toString().toLowerCase();
	const app_data = ((ch && ch.application_data) || '').toString().toLowerCase();
	if (app.indexOf('record') !== -1 || app_data.indexOf('record') !== -1) return true;
	if (uuid && recording_call_uuids.has(uuid)) return true;
	return false;
}

function sync_recording_state() {
	if (!ws || ws.ws.readyState !== WebSocket.OPEN) return;
	const uuids = Array.from(calls_map.keys());
	if (!uuids.length) return;

	send_action('recording_state', { uuids })
		.then((response) => {
			const payload = response && response.payload ? response.payload : response;
			const states = payload && payload.states ? payload.states : null;
			if (!states || typeof states !== 'object') return;

			// If either leg reports recording, treat all linked UUIDs as recording.
			const effective_recording = new Set();
			Object.entries(states).forEach(([id, is_rec]) => {
				if (!is_rec) return;
				get_conversation_call_uuids(id).forEach(linked_id => effective_recording.add(linked_id));
				effective_recording.add(id);
			});

			let changed = false;
			uuids.forEach((id) => {
				const ch = calls_map.get(id);
				if (!ch) return;
				const next = effective_recording.has(id);
				if (!!ch.is_recording !== next) {
					ch.is_recording = next;
					changed = true;
				}
				if (next) recording_call_uuids.add(id);
				else recording_call_uuids.delete(id);
			});

			if (changed) {
				render_calls_tab();
				schedule_extensions_render();
			}
		})
		.catch(() => {
			// Silent polling failure handling.
		});
}

function sync_registrations_state() {
	if (!ws || ws.ws.readyState !== WebSocket.OPEN) return;
	if (extensions_map.size === 0) return;

	send_action('registrations_state', {})
		.then((response) => {
			const payload = response && response.payload ? response.payload : response;
			const states = payload && payload.states ? payload.states : null;
			if (!states || typeof states !== 'object') {
				lop_debug('[OP][reg][reconcile][drop] no states payload', { payload_keys: payload ? Object.keys(payload) : [] });
				return;
			}
			lop_debug('[OP][reg][reconcile][states]', {
				state_count: Object.keys(states).length,
				has_555: Object.prototype.hasOwnProperty.call(states, '555'),
				has_102: Object.prototype.hasOwnProperty.call(states, '102'),
			});

			let changed = false;
			extensions_map.forEach((ext, ext_num) => {
				const count = Number(states[ext_num] || 0);
				const next_registered = count > 0;
				const prev_registered = !!ext.registered;
				const prev_count = Number(ext.registration_count || 0);
				if (prev_registered !== next_registered || prev_count !== count) {
					ext.registered = next_registered;
					ext.registration_count = count;
					changed = true;
					lop_debug('[OP][reg][reconcile]', { extension: ext_num, prev_registered, next_registered, prev_count, count });
				}
			});

			if (changed) {
				schedule_extensions_render();
			}
		})
		.catch(() => {
			// silent polling failure
		});
}

function get_linked_call_uuids(uuid) {
	const linked = new Set();
	if (!uuid) return linked;
	linked.add(uuid);

	const ch = calls_map.get(uuid);
	if (ch) {
		const direct = [ch.other_leg_unique_id, ch.variable_bridge_uuid, ch.bridge_uuid]
			.map(v => ((v || '') + '').trim())
			.filter(Boolean);
		direct.forEach(v => linked.add(v));
	}

	// Reverse lookup for legs that reference this UUID.
	for (const [other_uuid, other_ch] of calls_map.entries()) {
		if (other_uuid === uuid) continue;
		const refs = [other_ch.other_leg_unique_id, other_ch.variable_bridge_uuid, other_ch.bridge_uuid]
			.map(v => ((v || '') + '').trim());
		if (refs.includes(uuid)) linked.add(other_uuid);
	}

	return linked;
}

function get_conversation_key(ch) {
	if (!ch) return '';
	const ext = (((ch.channel_presence_id || '').split('@')[0]) || '').trim();
	const cid = ((ch.caller_caller_id_number || ch.caller_id_number || '') + '').trim();
	const dest = ((ch.caller_destination_number || '') + '').trim();
	let direction_raw = (ch.call_direction || ch.variable_call_direction || '').toString().toLowerCase();
	if (ext && cid && dest) {
		if (ext === cid && ext !== dest) direction_raw = 'outbound';
		else if (ext === dest && ext !== cid) direction_raw = 'inbound';
	}
	const peer = resolve_peer_number_for_leg(ch, ext, direction_raw);
	if (!ext || !peer || ext === peer) return '';
	return [ext, peer].sort().join('|');
}

function get_conversation_call_uuids(uuid) {
	const all = new Set(get_linked_call_uuids(uuid));
	const seed = calls_map.get(uuid);
	if (!seed) return all;
	const key = get_conversation_key(seed);
	if (!key) return all;
	for (const [id, ch] of calls_map.entries()) {
		if (get_conversation_key(ch) === key) all.add(id);
	}
	return all;
}

/**
 * Render the Calls tab from the in-memory calls_map.
 */
function render_calls_tab() {
	const container = document.getElementById('calls_container');
	if (!container) return;

	const calls = Array.from(calls_map.values());

	// Update badge
	const badge = document.getElementById('calls_count');
	if (badge) badge.textContent = calls.length;

	if (calls.length === 0) {
		container.innerHTML = `<p class="text-muted">${esc(text['label-no_calls_active'] || 'No active calls.')}</p>`;
		return;
	}

	let html = "<div class='card'>\n";
	html += "<table class='list'>\n";
	html += "<tr class='list-header'>\n";
	html += `<th class="mono" style="width:28px"></th>\n`;
	html += `<th>${esc(text['label-caller_id'] || 'Caller ID')}</th>\n`;
	html += `<th>${esc(text['label-destination'] || 'Destination')}</th>\n`;
	html += `<th>${esc(text['label-state'] || 'State')}</th>\n`;
	html += `<th>${esc(text['label-duration'] || 'Duration')}</th>\n`;
	html += `<th class="right">${esc(text['label-actions'] || 'Actions')}</th>\n`;
	html += "</tr>\n";

	calls.forEach(ch => {
		const uuid_raw      = get_call_uuid(ch);
		if (!uuid_raw) return;
		const uuid          = esc(uuid_raw);
		const group_key     = esc(get_call_group_key(ch));
		const ext_number    = ((ch.channel_presence_id || '').split('@')[0] || '').trim();
		const raw_cid_name  = (ch.caller_caller_id_name   || ch.caller_id_name   || '').toString();
		const raw_cid_num   = (ch.caller_caller_id_number || ch.caller_id_number || '').toString().trim();
		const raw_dest_num  = (ch.caller_destination_number || '').toString().trim();

		// Derive per-leg direction so B-leg rows do not incorrectly show outbound.
		let direction_raw = (ch.call_direction || ch.variable_call_direction || '').toString().toLowerCase();
		if (ext_number && raw_cid_num && raw_dest_num) {
			if (ext_number === raw_cid_num && ext_number !== raw_dest_num) {
				direction_raw = 'outbound';
			}
			else if (ext_number === raw_dest_num && ext_number !== raw_cid_num) {
				direction_raw = 'inbound';
			}
		}

		const peer_number   = resolve_peer_number_for_leg(ch, ext_number, direction_raw);
		let caller_number_raw = (ext_number || raw_cid_num || '').toString().trim();

		// If this leg is ambiguous (same number on both CID and destination),
		// show the inferred peer as the leg owner for inbound perspective.
		if (direction_raw === 'inbound' && raw_cid_num && raw_dest_num && raw_cid_num === raw_dest_num && peer_number && raw_cid_num !== peer_number) {
			caller_number_raw = peer_number;
		}
		if (caller_number_raw === peer_number) {
			if (raw_dest_num && raw_dest_num !== peer_number) caller_number_raw = raw_dest_num;
			else if (raw_cid_num && raw_cid_num !== peer_number) caller_number_raw = raw_cid_num;
		}

		const cid_name      = esc(raw_cid_name);
		const cid_number    = esc(caller_number_raw);
		const dest          = esc(peer_number || raw_dest_num || ext_number);
		const state         = esc(ch.channel_call_state       || ch.answer_state    || '');
		const direction     = esc(direction_raw);
		const direction_icon = direction_raw === 'inbound'
			? '../operator_panel/resources/images/inbound.png'
			: (direction_raw === 'outbound' ? '../operator_panel/resources/images/outbound.png' : '');
		const created_ts    = ch.caller_channel_created_time  || '0';
		const elapsed       = esc(format_elapsed(created_ts));
		const is_recording  = call_is_recording(ch, uuid_raw);
		const record_icon   = is_recording
			? '../operator_panel/resources/images/recording.png'
			: '../operator_panel/resources/images/record.png';

		html += `<tr class="list-row" id="call_row_${uuid}" draggable="true" data-uuid="${uuid}" data-group-key="${group_key}" ondragstart="on_drag_call('${uuid}', event)" ondragend="on_drag_end(); document.querySelectorAll('.op-ext-block').forEach(b=>b.classList.remove('op-ext-drop-over'))">\n`;
		const show_cid_name = raw_cid_name && raw_cid_name.toLowerCase() !== 'outbound call' && raw_cid_name.toLowerCase() !== 'inbound call';
		html += `  <td class="mono">${direction_icon ? `<img src="${direction_icon}" width="12" height="12" alt="${direction}" title="${direction}" style="vertical-align:middle;">` : ''}</td>\n`;
		html += `  <td>${show_cid_name ? `${cid_name}<br><small>${cid_number}</small>` : cid_number}</td>\n`;
		html += `  <td>${dest}</td>\n`;
		html += `  <td>${state}</td>\n`;
		html += `  <td class="mono" data-created="${created_ts}">${elapsed}</td>\n`;
		html += `  <td class="right">\n`;

		if (permissions.operator_panel_hangup) {
			html += `    <a class="btn-action" href="javascript:void(0)" title="${esc(text['button-hangup'] || 'Hangup')}" onclick="action_hangup('${uuid}')">`
				+ `<img class="op-ext-action-icon" src="../operator_panel/resources/images/kill.png" alt="${esc(text['button-hangup'] || 'Hangup')}"></a> `;
		}
		if (permissions.operator_panel_eavesdrop) {
			html += `    <a class="btn-action" href="javascript:void(0)" title="${esc(text['button-eavesdrop'] || 'Eavesdrop')}" onclick="action_eavesdrop('${uuid}')">`
				+ `<img class="op-ext-action-icon" src="../operator_panel/resources/images/eavesdrop.png" alt="${esc(text['button-eavesdrop'] || 'Eavesdrop')}"></a> `;
		}
		if (permissions.operator_panel_coach) {
			html += `    <a class="btn-action" href="javascript:void(0)" title="${esc(text['button-whisper'] || 'Whisper')}" onclick="action_whisper('${uuid}')">`
				+ `<img class="op-ext-action-icon" src="../operator_panel/resources/images/whisper.svg" alt="${esc(text['button-whisper'] || 'Whisper')}"></a> `;
			html += `    <a class="btn-action" href="javascript:void(0)" title="${esc(text['button-barge'] || 'Barge')}" onclick="action_barge('${uuid}')">`
				+ `<img class="op-ext-action-icon" src="../operator_panel/resources/images/barge.svg" alt="${esc(text['button-barge'] || 'Barge')}"></a> `;
		}
		if (permissions.operator_panel_record) {
			html += `    <a class="btn-action" href="javascript:void(0)" title="${esc(text['button-record'] || 'Record')}" onclick="action_record('${uuid}')">`
				+ `<img class="op-ext-action-icon" src="${record_icon}" alt="${esc(text['button-record'] || 'Record')}"></a> `;
		}

		html += "  </td>\n";
		html += "</tr>\n";
	});

	html += "</table>\n</div>\n";
	container.innerHTML = html;
	apply_calls_filters();
}

/**
 * Request a full conferences snapshot.
 */
function load_conferences_snapshot() {
	if (!ws || ws.ws.readyState !== WebSocket.OPEN) return;
	ws.request('active.operator.panel', 'conferences_active', {domain_name: domain_name})
		.then(response => {
			const conferences = response.payload || [];
			conferences_map.clear();
			conferences.forEach(conf => {
				if (conf.conference_name) conferences_map.set(conf.conference_name, conf);
			});
			render_conferences_tab();
		})
		.catch(console.error);
}

/**
 * Handle incremental conference events.
 * @param {object} event
 */
function on_conference_event(event) {
	const action         = (event.topic || event.event_name || '').toLowerCase();
	const conference_name = event.conference_name || event.channel_presence_id || '';

	if (!conference_name) return;

	switch (action) {
		case 'conference_create': {
			conferences_map.set(conference_name, {
				conference_name:         conference_name,
				conference_display_name: event.conference_display_name || '',
				member_count:            0,
				members:                 [],
			});
			break;
		}

		case 'conference_destroy': {
			conferences_map.delete(conference_name);
			break;
		}

		case 'add_member': {
			const conf = conferences_map.get(conference_name) || {
				conference_name,
				conference_display_name: event.conference_display_name || '',
				member_count:            0,
				members:                 [],
			};
			// Upsert member
			const member  = event.member || { id: event.member_id, uuid: event.unique_id,
				caller_id_name: event.caller_id_name || event.caller_caller_id_name || '',
				caller_id_number: event.caller_id_number || event.caller_caller_id_number || '',
				flags: {
					can_hear:     (event.hear     || 'true')  === 'true',
					can_speak:    (event.speak    || 'true')  === 'true',
					talking:      (event.talking  || 'false') === 'true',
					has_video:    (event.video    || 'false') === 'true',
					has_floor:    (event.floor    || 'false') === 'true',
					is_moderator: (event.member_type || '') === 'moderator',
				}
			};
			const members = conf.members || [];
			const idx = members.findIndex(m => String(m.id) === String(member.id));
			if (idx >= 0) members[idx] = member; else members.push(member);
			conf.members      = members;
			conf.member_count = event.member_count || members.length;
			conferences_map.set(conference_name, conf);
			break;
		}

		case 'del_member': {
			const conf    = conferences_map.get(conference_name);
			if (!conf) break;
			const members = (conf.members || []).filter(m => String(m.id) !== String(event.member_id));
			conf.members      = members;
			conf.member_count = event.member_count !== undefined ? event.member_count : members.length;
			conferences_map.set(conference_name, conf);
			break;
		}

		case 'start_talking':
		case 'stop_talking':
		case 'mute_member':
		case 'unmute_member':
		case 'deaf_member':
		case 'undeaf_member':
		case 'floor_change': {
			const conf = conferences_map.get(conference_name);
			if (!conf) break;
			const members = conf.members || [];
			const member  = members.find(m => String(m.id) === String(event.member_id));
			if (member && member.flags) {
				if (action === 'start_talking')  member.flags.talking  = true;
				if (action === 'stop_talking')   member.flags.talking  = false;
				if (action === 'mute_member')    member.flags.can_speak = false;
				if (action === 'unmute_member')  member.flags.can_speak = true;
				if (action === 'deaf_member')    member.flags.can_hear  = false;
				if (action === 'undeaf_member')  member.flags.can_hear  = true;
				if (action === 'floor_change')   { members.forEach(m => { if (m.flags) m.flags.has_floor = false; }); member.flags.has_floor = true; }
			}
			conferences_map.set(conference_name, conf);
			break;
		}

		default:
			break;
	}

	render_conferences_tab();
}

/**
 * Render the Conferences tab from the in-memory conferences_map.
 */
function render_conferences_tab() {
	const container = document.getElementById('conferences_container');
	if (!container) return;

	const conferences = Array.from(conferences_map.values());

	const badge = document.getElementById('conferences_count');
	if (badge) badge.textContent = conferences.length;

	if (conferences.length === 0) {
		container.innerHTML = `<p class="text-muted">${esc(text['label-no_conferences_active'] || 'No active conferences.')}</p>`;
		return;
	}

	let html = '';

	conferences.forEach(conf => {
		const name    = conf.conference_name || '';
		const display = conf.conference_display_name || name.split('@')[0] || name;
		const count   = conf.member_count || (conf.members || []).length;
		const members = conf.members || [];
		const conf_group_keys = new Set();
		members.forEach(m => {
			const n = ((m.caller_id_number || '') + '').trim();
			if (!n) return;
			conf_group_keys.add(get_extension_group_key(n));
		});
		if (conf_group_keys.size === 0) conf_group_keys.add('');
		const conf_group_attr = esc(Array.from(conf_group_keys).join(','));

		html += `<div class="card mb-3" data-group-keys="${conf_group_attr}">\n`;
		html += `  <div class="card-header d-flex justify-content-between align-items-center">\n`;
		html += `    <strong>${esc(display)}</strong>\n`;
		html += `    <span class="badge bg-secondary">${count} ${esc(text['label-members'] || 'members')}</span>\n`;
		html += `  </div>\n`;

		if (members.length > 0) {
			html += `  <div class="card-body p-0"><table class="list mb-0">\n`;
			html += `  <tr class="list-header">`;
			html += `    <th>${esc(text['label-caller_id'] || 'Caller ID')}</th>`;
			html += `    <th>${esc(text['label-member_id'] || 'ID')}</th>`;
			html += `    <th>${esc(text['label-flags'] || 'Flags')}</th>`;
			if (permissions.operator_panel_hangup || permissions.operator_panel_manage) {
				html += `    <th class="right">${esc(text['label-actions'] || 'Actions')}</th>`;
			}
			html += `  </tr>\n`;

			members.forEach(m => {
				const conf_name_js = jsq(name);
				const member_id_js = jsq(String(m.id || ''));
				const uuid_js = jsq(m.uuid || '');
				const mid     = esc(String(m.id || ''));
				const muuid   = esc(m.uuid || '');
				const cid     = esc(m.caller_id_name || '');
				const cid_num = esc(m.caller_id_number || '');
				const flags   = m.flags || {};
				let flags_html = '';

				if (flags.talking) {
					flags_html += `      <span class="badge bg-info" title="${esc(text['label-talking'] || 'Talking')}" style="margin:0 3px;">${esc(text['label-talking'] || 'Talking')}</span>`;
				}
				if (flags.can_speak === false) {
					flags_html += `      <span class="badge bg-warning text-dark" title="${esc(text['label-muted'] || 'Muted')}" style="margin:0 3px;">${esc(text['label-muted'] || 'Muted')}</span>`;
				}
				if (flags.can_hear === false) {
					flags_html += `      <span class="badge bg-warning text-dark" title="${esc(text['label-deaf'] || 'Deaf')}" style="margin:0 3px;">${esc(text['label-deaf'] || 'Deaf')}</span>`;
				}
				if (flags.has_floor) {
					flags_html += `      <span class="badge bg-warning text-dark" title="${esc(text['label-floor'] || 'Floor')}" style="margin:0 3px;">${esc(text['label-floor'] || 'Floor')}</span>`;
				}
				if (flags.is_moderator) {
					flags_html += `      <span class="badge bg-primary" title="${esc(text['label-moderator'] || 'Moderator')}" style="margin:0 3px;">${esc(text['label-moderator'] || 'Moderator')}</span>`;
				}

				html += `  <tr class="list-row" id="conf_member_${muuid}">\n`;
				html += `    <td>${cid ? `${cid}<br><small>${cid_num}</small>` : cid_num}</td>\n`;
				html += `    <td>${mid}</td>\n`;
				html += `    <td style="white-space:nowrap;">`;
				html += flags_html;
				html += `    </td>\n`;

				if (permissions.operator_panel_hangup || permissions.operator_panel_manage) {
					html += `    <td class="right">\n`;
					if (permissions.operator_panel_hangup) {
						html += `      <button type="button" class="btn btn-default btn-xs" title="${esc(text['button-hangup'] || 'Hangup')}" onclick='action_conference_member("kick", ${conf_name_js}, ${member_id_js}, ${uuid_js})'><span class="${esc(get_conference_action_icon('kick', 'fas fa-ban'))}" aria-hidden="true"></span></button> `;
					}
					if (permissions.operator_panel_manage) {
						const mute_action = flags.can_speak === false ? 'unmute' : 'mute';
						const mute_label = flags.can_speak === false ? (text['button-unmute'] || 'Unmute') : (text['button-mute'] || 'Mute');
						const deaf_action = flags.can_hear === false ? 'undeaf' : 'deaf';
						const deaf_label = flags.can_hear === false ? (text['button-undeaf'] || 'Undeaf') : (text['button-deaf'] || 'Deaf');
						html += `      <button type="button" class="btn btn-default btn-xs" title="${esc(mute_label)}" onclick='action_conference_member(${jsq(mute_action)}, ${conf_name_js}, ${member_id_js}, ${uuid_js})'><span class="${esc(get_conference_action_icon(mute_action, mute_action === 'mute' ? 'fas fa-microphone-slash' : 'fas fa-microphone'))}" aria-hidden="true"></span></button> `;
						html += `      <button type="button" class="btn btn-default btn-xs" title="${esc(deaf_label)}" onclick='action_conference_member(${jsq(deaf_action)}, ${conf_name_js}, ${member_id_js}, ${uuid_js})'><span class="${esc(get_conference_action_icon(deaf_action, deaf_action === 'deaf' ? 'fas fa-deaf' : 'fas fa-headphones'))}" aria-hidden="true"></span></button> `;
						html += `      <button type="button" class="btn btn-default btn-xs" title="${esc(text['label-energy'] || 'Energy')}` + ` +" onclick='action_conference_member("energy", ${conf_name_js}, ${member_id_js}, ${uuid_js}, "up")'><span class="${esc(get_conference_action_icon('energy_up', 'fas fa-plus'))}" aria-hidden="true"></span></button> `;
						html += `      <button type="button" class="btn btn-default btn-xs" title="${esc(text['label-energy'] || 'Energy')}` + ` -" onclick='action_conference_member("energy", ${conf_name_js}, ${member_id_js}, ${uuid_js}, "down")'><span class="${esc(get_conference_action_icon('energy_down', 'fas fa-minus'))}" aria-hidden="true"></span></button> `;
						html += `      <button type="button" class="btn btn-default btn-xs" title="${esc(text['label-volume'] || 'Volume')}` + ` -" onclick='action_conference_member("volume_in", ${conf_name_js}, ${member_id_js}, ${uuid_js}, "down")'><span class="${esc(get_conference_action_icon('volume_down', 'fas fa-volume-down'))}" aria-hidden="true"></span></button> `;
						html += `      <button type="button" class="btn btn-default btn-xs" title="${esc(text['label-volume'] || 'Volume')}` + ` +" onclick='action_conference_member("volume_in", ${conf_name_js}, ${member_id_js}, ${uuid_js}, "up")'><span class="${esc(get_conference_action_icon('volume_up', 'fas fa-volume-up'))}" aria-hidden="true"></span></button> `;
						html += `      <button type="button" class="btn btn-default btn-xs" title="${esc(text['label-gain'] || 'Gain')}` + ` -" onclick='action_conference_member("volume_out", ${conf_name_js}, ${member_id_js}, ${uuid_js}, "down")'><span class="${esc(get_conference_action_icon('gain_down', 'fas fa-sort-amount-down'))}" aria-hidden="true"></span></button> `;
						html += `      <button type="button" class="btn btn-default btn-xs" title="${esc(text['label-gain'] || 'Gain')}` + ` +" onclick='action_conference_member("volume_out", ${conf_name_js}, ${member_id_js}, ${uuid_js}, "up")'><span class="${esc(get_conference_action_icon('gain_up', 'fas fa-sort-amount-up'))}" aria-hidden="true"></span></button> `;
					}
					html += `    </td>\n`;
				}

				html += `  </tr>\n`;
			});

			html += `  </table></div>\n`;
		}

		html += `</div>\n`;
	});

	container.innerHTML = html;
	apply_conferences_filters();
}

/**
 * Request a full agents snapshot.
 */
function load_agents_snapshot() {
	if (!ws || ws.ws.readyState !== WebSocket.OPEN) return;
	ws.request('active.operator.panel', 'agents_active', {domain_name: domain_name})
		.then(response => {
			agents_list = response.payload || [];
			render_agents_tab();
		})
		.catch(console.error);
}

/**
 * Handle the server-pushed agent stats broadcast (timer-driven every N seconds).
 * @param {object} event
 */
function on_agent_stats(event) {
	agents_list = event.payload || event.data || [];
	render_agents_tab();
}

/**
 * Status → Bootstrap badge colour helper.
 * @param {string} status
 * @returns {string}
 */
function agent_status_class(status) {
	switch ((status || '').toLowerCase()) {
		case 'available':               return 'bg-success';
		case 'available (on demand)':   return 'bg-info';
		case 'on break':                return 'bg-warning text-dark';
		case 'do not disturb':          return 'bg-secondary';
		case 'logged out':              return 'bg-dark';
		default:                        return 'bg-secondary';
	}
}

/**
 * Render the Agents tab from the in-memory agents_list.
 */
function render_agents_tab() {
	const container = document.getElementById('agents_container');
	if (!container) return;

	const badge = document.getElementById('agents_count');
	if (badge) badge.textContent = agents_list.length;

	if (agents_list.length === 0) {
		container.innerHTML = `<p class="text-muted">${esc(text['label-no_agents'] || 'No agents.')}</p>`;
		return;
	}

	let html = "<div class='card'>\n";
	html += "<table class='list'>\n";
	html += "<tr class='list-header'>\n";
	html += `  <th>${esc(text['label-agent'] || 'Agent')}</th>\n`;
	html += `  <th>${esc(text['label-queue'] || 'Queue')}</th>\n`;
	html += `  <th>${esc(text['label-status'] || 'Status')}</th>\n`;
	html += `  <th>${esc(text['label-state'] || 'State')}</th>\n`;
	html += `  <th>${esc(text['label-calls_answered'] || 'Answered')}</th>\n`;
	html += `  <th>${esc(text['label-talk_time'] || 'Talk Time')}</th>\n`;
	html += `  <th>${esc(text['label-last_call'] || 'Last Call')}</th>\n`;
	if (permissions.operator_panel_manage) {
		html += `  <th class="right">${esc(text['label-actions'] || 'Actions')}</th>\n`;
	}
	html += "</tr>\n";

	agents_list.forEach(agent => {
		const name      = esc(agent.agent_name     || '');
		const queue     = esc(agent.queue_name     || agent.queue_extension || '');
		const group_key = esc(get_extension_group_key(agent.queue_extension || ''));
		const status    = agent.status             || '';
		const state     = esc(agent.state          || '');
		const answered  = esc(agent.calls_answered || '0');
		const talk_time = esc(agent.talk_time      || '0');
		const last_call = format_time(agent.last_bridge_start || 0);
		const status_cls = agent_status_class(status);

		html += `<tr class="list-row" id="agent_row_${name}" data-group-key="${group_key}">\n`;
		html += `  <td>${name}</td>\n`;
		html += `  <td>${queue}</td>\n`;
		html += `  <td><span class="badge ${status_cls}">${esc(status)}</span></td>\n`;
		html += `  <td>${state}</td>\n`;
		html += `  <td class="center">${answered}</td>\n`;
		html += `  <td class="mono">${talk_time}</td>\n`;
		html += `  <td>${esc(last_call)}</td>\n`;

		if (permissions.operator_panel_manage) {
			html += `  <td class="right">\n`;
			// Status change select
			html += `    <select class="element select-sm" onchange="action_agent_status('${name.replace(/'/g, "\\'")}', this.value)">\n`;
			const statuses = ['Available', 'Available (On Demand)', 'On Break', 'Do Not Disturb', 'Logged Out'];
			statuses.forEach(s => {
				const selected = s === status ? ' selected' : '';
				html += `      <option value="${esc(s)}"${selected}>${esc(s)}</option>\n`;
			});
			html += `    </select>\n`;
			html += `  </td>\n`;
		}

		html += `</tr>\n`;
	});

	html += "</table>\n</div>\n";
	container.innerHTML = html;
	apply_agents_filters();
}

/**
 * Send a generic action to the service.
 * @param {string} action
 * @param {object} extra   Additional payload fields.
 * @returns {Promise}
 */
function send_action(action, extra) {
	if (!ws || ws.ws.readyState !== WebSocket.OPEN) {
		console.error('[OP] Cannot send action: WebSocket not connected');
		return Promise.reject(new Error('Not connected'));
	}
	return ws.request('active.operator.panel', 'action', Object.assign({ action, domain_name }, extra));
}

/** Handle action responses from the server. */
function on_action_response(event) {
	const payload = event.payload || event;
	if (!payload.success) {
		console.warn('[OP] Action failed:', payload.message);
		show_toast(payload.message || 'Action failed', 'danger');
	}
}

function action_hangup(uuid) {
	if (!confirm(text['label-confirm_hangup'] || 'Hang up this call?')) return;
	send_action('hangup', { uuid }).catch(console.error);
}

/** Open the transfer modal for the given UUID. */
function open_transfer_modal(uuid) {
	const uuid_field = document.getElementById('transfer_uuid');
	const dest_field = document.getElementById('transfer_destination');
	if (!uuid_field || !dest_field) return;

	uuid_field.value = uuid;
	dest_field.value = '';

	const modal_el = document.getElementById('transfer_modal');
	if (!modal_el) return;

	const modal = bootstrap.Modal.getOrCreateInstance(modal_el);
	modal.show();
	setTimeout(() => dest_field.focus(), 350);
}

/** Called by the Transfer button inside the modal. */
function confirm_transfer() {
	const uuid        = (document.getElementById('transfer_uuid')        || {}).value || '';
	const destination = (document.getElementById('transfer_destination') || {}).value || '';

	if (!uuid || !destination) {
		show_toast(text['label-destination_required'] || 'Please enter a destination.', 'warning');
		return;
	}

	// Close modal
	const modal_el = document.getElementById('transfer_modal');
	if (modal_el) bootstrap.Modal.getInstance(modal_el)?.hide();

	send_action('transfer', { uuid, destination, context: domain_name }).catch(console.error);
}

function action_eavesdrop(uuid) {
	if (!uuid) return;

	// If user has exactly one extension, use it directly (no prompt).
	if (Array.isArray(user_own_extensions) && user_own_extensions.length === 1) {
		send_action('eavesdrop', { uuid, destination: user_own_extensions[0], destination_extension: user_own_extensions[0] })
			.then(() => show_toast(text['button-eavesdrop'] || 'Eavesdrop started', 'success'))
			.catch((err) => {
				console.error(err);
				show_toast((err && err.message) || 'Eavesdrop failed', 'danger');
			});
		return;
	}

	const ext = prompt(text['label-your_extension'] || 'Your extension to receive the call:');
	if (!ext) return;
	send_action('eavesdrop', { uuid, destination: ext, destination_extension: ext })
		.then(() => show_toast(text['button-eavesdrop'] || 'Eavesdrop started', 'success'))
		.catch((err) => {
			console.error(err);
			show_toast((err && err.message) || 'Eavesdrop failed', 'danger');
		});
}

function action_whisper(uuid) {
	action_monitor_mode('whisper', uuid, text['button-whisper'] || 'Whisper started');
}

function action_barge(uuid) {
	action_monitor_mode('barge', uuid, text['button-barge'] || 'Barge started');
}

function action_monitor_mode(mode, uuid, success_message) {
	if (!uuid) return;

	if (Array.isArray(user_own_extensions) && user_own_extensions.length === 1) {
		const ext = user_own_extensions[0];
		send_action(mode, { uuid, destination: ext, destination_extension: ext })
			.then(() => show_toast(success_message, 'success'))
			.catch((err) => {
				console.error(err);
				show_toast((err && err.message) || (mode + ' failed'), 'danger');
			});
		return;
	}

	const ext = prompt(text['label-your_extension'] || 'Your extension to receive the call:');
	if (!ext) return;

	send_action(mode, { uuid, destination: ext, destination_extension: ext })
		.then(() => show_toast(success_message, 'success'))
		.catch((err) => {
			console.error(err);
			show_toast((err && err.message) || (mode + ' failed'), 'danger');
		});
}

/** Called when dragging the eavesdrop icon onto an extension block. */
function on_eavesdrop_dragstart(uuid, event) {
	event.stopPropagation();
	dragged_eavesdrop_uuid = uuid;
	dragged_call_uuid = null;
	dragged_extension = null;
	event.dataTransfer.setData('text/plain', uuid);
	event.dataTransfer.setData('application/x-op-eavesdrop-uuid', uuid);
	event.dataTransfer.effectAllowed = 'copy';
	set_drag_visual_state(true);
}

function action_record(uuid) {
	const ch = calls_map.get(uuid);
	const stopping = call_is_recording(ch, uuid);
	send_action('record', { uuid, stop: stopping })
		.then(() => {
			const related = get_conversation_call_uuids(uuid);
			related.forEach((id) => {
				if (stopping) recording_call_uuids.delete(id);
				else recording_call_uuids.add(id);
				const leg = calls_map.get(id);
				if (leg) leg.is_recording = !stopping;
			});
			render_calls_tab();
			schedule_extensions_render();
		})
		.catch(console.error);
}

/**
 * Send user status change through WebSocket.
 * Triggered by the status dropdown in the action bar.
 * @param {string} status
 */
function send_user_status(status) {
	send_action('user_status', { status, user_uuid })
		.then(() => {
			// Update local extensions_map so the UI reflects the new status immediately
			if (Array.isArray(user_own_extensions)) {
				user_own_extensions.forEach(ext_num => {
					const ext = extensions_map.get(ext_num);
					if (ext) {
						ext.user_status = status;
						ext.do_not_disturb = (status === 'Do Not Disturb') ? 'true' : 'false';
					}
				});
			}
			render_extensions_tab();
		})
		.catch(console.error);
}

function action_agent_status(agent_name, status) {
	send_action('agent_status', { agent_name, status }).catch(console.error);
}

function action_conference_member(action, conference_name, member_id, uuid, direction) {
	const payload = { conference_name, member_id, uuid };
	if (direction) {
		payload.direction = direction;
	}
	send_action(action, payload)
		.then(() => {
			if (action === 'kick') {
				show_toast(text['button-hangup'] || 'Member removed', 'success');
				return;
			}
			show_toast(text['label-actions'] || 'Action executed', 'success');
		})
		.catch(console.error);
}

/**
 * Show a brief Bootstrap toast notification.
 * @param {string} message
 * @param {string} [variant='info']  Bootstrap color variant.
 */
function show_toast(message, variant) {
	variant = variant || 'info';
	let container = document.getElementById('lop_toast_container');
	if (!container) {
		container = document.createElement('div');
		container.id             = 'lop_toast_container';
		container.className      = 'toast-container position-fixed bottom-0 end-0 p-3';
		container.style.zIndex   = '9999';
		document.body.appendChild(container);
	}

	const toast_el = document.createElement('div');
	toast_el.className               = `toast align-items-center text-bg-${variant} border-0`;
	toast_el.setAttribute('role',    'alert');
	toast_el.setAttribute('aria-live', 'assertive');
	toast_el.innerHTML = `
		<div class="d-flex">
			<div class="toast-body">${esc(message)}</div>
			<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
		</div>`;

	container.appendChild(toast_el);
	const toast = new bootstrap.Toast(toast_el, { delay: 4000 });
	toast.show();
	toast_el.addEventListener('hidden.bs.toast', () => toast_el.remove());
}

/**
 * Request a full extensions snapshot from the service.
 */
function load_extensions_snapshot() {
	if (!ws || ws.ws.readyState !== WebSocket.OPEN) return;
	ws.request('active.operator.panel', 'extensions_active', {domain_name: domain_name})
		.then(response => {
			extensions_map.clear();
			(response.payload || []).forEach(ext => {
				if (ext.extension) extensions_map.set(ext.extension, ext);
			});
			render_extensions_tab();
			sync_status_buttons();
		})
		.catch(console.error);
}

/**
 * Highlight the status button that matches the logged-in user's current
 * status (read from extensions_map for the user's own extension).
 */
function sync_status_buttons() {
	if (!Array.isArray(user_own_extensions) || user_own_extensions.length === 0) return;
	const ext = extensions_map.get(user_own_extensions[0]);
	if (!ext) return;
	let current = (ext.user_status || '').trim();
	// If user has no explicit status but is registered, treat as Available
	if (!current && ext.user_uuid && ext.registered === true) {
		current = 'Available';
	}
	if (!current) return;
	document.querySelectorAll('.op-status-btn').forEach(b => {
		if (b.getAttribute('data-status') === current) {
			b.classList.add('active');
		} else {
			b.classList.remove('active');
		}
	});
}

/**
 * Derive call state for a given extension number by scanning calls_map.
 * @param {string} ext_number
 * @returns {{ state: string, call_uuid: string|null, call_info: object|null }}
 */
function get_extension_call_state(ext_number) {
	const candidates = [];

	for (const [uuid, ch] of calls_map) {
		const presence = (ch.channel_presence_id || '').split('@')[0];
		const dest     = ch.caller_destination_number || '';
		const cid_num  = ch.caller_caller_id_number   || '';
		const context  = ch.caller_context            || '';

		const matches = (presence === ext_number)
			|| (dest === ext_number)
			|| (cid_num === ext_number)
			|| ((dest === ext_number || cid_num === ext_number) && (context === domain_name || context === 'default'));

		if (!matches) continue;

		const cs = (ch.channel_call_state || '').toUpperCase();
		const as = (ch.answer_state       || '').toUpperCase();

		let state = 'active';
		if (cs === 'HELD' || as === 'HELD') {
			state = 'held';
		} else if (cs.indexOf('RING') !== -1 || as.indexOf('RING') !== -1 || as === 'EARLY') {
			// EARLY and RING* states map to offering/ringing from the panel perspective.
			state = 'ringing';
		}

		// Pick the most relevant leg for this extension first.
		let leg_score = 0;
		if (presence === ext_number) leg_score = 4;
		else if (dest === ext_number) leg_score = 3;
		else if (cid_num === ext_number) leg_score = 2;
		else leg_score = 1;

		const state_score = (state === 'ringing') ? 3 : (state === 'active' ? 2 : (state === 'held' ? 1 : 0));

		candidates.push({ uuid, ch, state, leg_score, state_score });
	}

	if (candidates.length === 0) {
		return { state: 'idle', call_uuid: null, call_info: null };
	}

	candidates.sort((a, b) => {
		if (b.leg_score !== a.leg_score) return b.leg_score - a.leg_score;
		return b.state_score - a.state_score;
	});

	const best = candidates[0];
	return { state: best.state, call_uuid: best.uuid, call_info: best.ch };
}

/**
 * Render one extension block as an HTML string.
 * @param {object}  ext
 * @param {boolean} is_mine  True when this extension belongs to the logged-in user.
 * @returns {string}
 */
function render_ext_block(ext, is_mine) {
	const num     = ext.extension || '';
	const raw_name = ext.effective_caller_id_name || ext.description || '';
	const show_name = raw_name && raw_name !== num;
	const dnd     = (ext.do_not_disturb || '') === 'true';
	const reg     = ext.registered === true;
	const voicemail_enabled = (ext.voicemail_enabled || '') === 'true';
	const { state, call_uuid, call_info } = get_extension_call_state(num);

	const user_status_raw = (ext.user_status || '').trim();

	let css_state;
	if (!reg) {
		css_state = 'op-ext-unregistered';
	} else if (state === 'ringing') {
		css_state = 'op-ext-ringing';
	} else if (state === 'active') {
		css_state = 'op-ext-active';
	} else if (state === 'held') {
		css_state = 'op-ext-held';
	} else if (dnd || user_status_raw === 'Do Not Disturb') {
		css_state = 'op-ext-dnd';
	} else if (user_status_raw === 'On Break') {
		css_state = 'op-ext-on-break';
	} else if (user_status_raw === 'Available' || user_status_raw === 'Available (On Demand)') {
		css_state = 'op-ext-available';
	} else if (user_status_raw === 'Logged Out') {
		css_state = 'op-ext-logged-out';
	} else if (ext.user_uuid && user_status_raw === '') {
		// Has a user but no explicit status set — treat as Available when registered
		css_state = 'op-ext-available';
	} else {
		// Registered with no user attached or unknown status — blue
		css_state = 'op-ext-registered';
	}

	// Icon color per state
	const icon_colors = {
		'op-ext-available': '#1e7e34',
		'op-ext-on-break': '#8a6508',
		'op-ext-dnd': '#a71d2a',
		'op-ext-registered': '#2b6cb0',
		'op-ext-logged-out': '#1e7e34',
		'op-ext-unregistered': '#6c757d',
		'op-ext-ringing': '#0e6882',
		'op-ext-active': '#2a7a2b',
		'op-ext-held': '#1a6c7a'
	};
	const icon_color = icon_colors[css_state] || '#7a8499';

	// Only show a state label when something notable is happening
	let state_label = '';
	if (dnd && reg) {
		state_label = text['label-do_not_disturb'] || 'Do Not Disturb';
	} else if (reg && state !== 'idle') {
		switch (state) {
			case 'ringing': state_label = text['label-ringing'] || 'Ringing\u2026'; break;
			case 'active':  state_label = text['label-on_call'] || 'On Call';       break;
			case 'held':    state_label = text['label-on_hold'] || 'On Hold';       break;
		}
		if (call_info) {
			const call_dest = ((call_info.caller_destination_number || '') + '').trim();
			const call_cid  = ((call_info.caller_caller_id_number || call_info.caller_id_number || '') + '').trim();
			const call_name = ((call_info.caller_caller_id_name || call_info.caller_id_name || '') + '').trim();

			// Show the opposite party relative to this extension to avoid
			// misleading CID values when both legs are internal extensions.
			let peer = '';
			if (call_dest === num && call_cid && call_cid !== num) {
				peer = call_cid;
			} else if (call_cid === num && call_dest && call_dest !== num) {
				peer = call_dest;
			} else if (call_cid && call_cid !== num) {
				peer = call_cid;
			} else if (call_dest && call_dest !== num) {
				peer = call_dest;
			} else {
				peer = call_name || call_cid || call_dest || '';
			}

			if (peer) state_label += ': ' + esc(peer);
		}
	}

	const mine_cls   = is_mine ? ' op-ext-mine'   : '';
	const data_uuid  = call_uuid ? ` data-call-uuid="${esc(call_uuid)}"` : '';
	// Always allow extension-to-extension drag originate; backend routing handles
	// availability, call forwarding, follow_me, and voicemail decisions.
	const can_receive_originate = true;
	const can_manual_originate = reg && state === 'idle';
	const has_live_call = reg && state !== 'idle' && !!call_info;
	const call_uuid_js = (call_uuid || '').replace(/'/g, "\\'");
	const call_dest = ((call_info || {}).caller_destination_number || '').trim();
	const call_cid  = ((call_info || {}).caller_caller_id_number || '').trim();
	let direction_raw = '';
	if (call_dest === num && call_cid !== num) {
		direction_raw = 'inbound';
	} else if (call_cid === num && call_dest !== num) {
		direction_raw = 'outbound';
	} else {
		direction_raw = (((call_info || {}).call_direction || (call_info || {}).variable_call_direction || '') + '').toLowerCase();
	}
	const direction_icon = direction_raw === 'inbound'
		? '../operator_panel/resources/images/inbound.png'
		: (direction_raw === 'outbound' ? '../operator_panel/resources/images/outbound.png' : '');
	const is_recording = call_is_recording(call_info, call_uuid);
	const record_icon = is_recording
		? '../operator_panel/resources/images/recording.png'
		: '../operator_panel/resources/images/record.png';
	const duration_raw = has_live_call ? format_elapsed((call_info || {}).caller_channel_created_time || '0') : '';
	const user_status = (ext.user_status || '').trim();
	let status_icon = 'status_logged_out';
	let status_hover = text['label-status_logged_out_or_unknown'] || text['label-status_logged_out'] || 'Logged Out';
	switch (user_status) {
		case 'Available':
			status_icon = 'status_available';
			status_hover = text['label-status_available'] || 'Available';
			break;
		case 'Available (On Demand)':
			status_icon = 'status_available_on_demand';
			status_hover = text['label-status_available_on_demand'] || 'Available (On Demand)';
			break;
		case 'On Break':
			status_icon = 'status_on_break';
			status_hover = text['label-status_on_break'] || 'On Break';
			break;
		case 'Do Not Disturb':
			status_icon = 'status_do_not_disturb';
			status_hover = text['label-status_do_not_disturb'] || 'Do Not Disturb';
			break;
		case 'Logged Out':
			// Use green icon when still registered to indicate the phone is online
			if (reg) {
				status_icon = 'status_available';
			} else {
				status_icon = 'status_logged_out';
			}
			status_hover = text['label-status_logged_out_or_unknown'] || text['label-status_logged_out'] || 'Logged Out';
			break;
		default:
			if (reg && ext.user_uuid) {
				status_icon = 'status_available';
				status_hover = text['label-status_available'] || 'Available';
			} else if (reg) {
				status_icon = 'status_available';
				status_hover = text['label-status_registered'] || 'Registered';
			} else {
				status_icon = 'status_logged_out';
				status_hover = text['label-status_logged_out_or_unknown'] || text['label-status_logged_out'] || 'Logged Out';
			}
	}
	if (!reg) {
		status_icon = 'status_logged_out';
		status_hover = text['label-status_logged_out_or_unknown'] || text['label-status_logged_out'] || 'Logged Out';
	}
	if (dnd) {
		status_icon = 'status_do_not_disturb';
		status_hover = text['label-status_do_not_disturb'] || 'Do Not Disturb';
	}

	// Allow dragging from idle extensions (originate) and live-call extensions (transfer).
	const is_draggable = reg && (state === 'idle' || has_live_call);
	const drag_attrs   = is_draggable
		? ` draggable="true" ondragstart="on_ext_dragstart('${esc(num)}', event)" ondragend="on_drag_end()"`
		: '';
	const dialpad_html = can_manual_originate
		? `<div class="op-ext-dial-wrap">` +
			`<button type="button" class="op-ext-dial-toggle" title="${esc(text['label-dial'] || 'Dial')}" onclick="toggle_ext_dialpad('${esc(num)}', event)">` +
			`<img src="../operator_panel/resources/images/keypad_call.png" width="12" height="12" alt="${esc(text['label-dial'] || 'Dial')}">` +
			`</button>` +
			`<input type="text" class="op-ext-dial-input d-none" placeholder="${esc(text['label-destination'] || 'Destination')}" ` +
			`onkeydown="if (event.key === 'Enter') submit_ext_dial('${esc(num)}', event)" onblur="toggle_ext_dialpad('${esc(num)}', event)" onclick="event.stopPropagation()">` +
			`</div>`
		: '';
	const live_call_meta_html = has_live_call
		? `<div class="op-ext-call-meta">` +
			(direction_icon ? `<img class="op-ext-call-direction" src="${direction_icon}" alt="${esc(text['label-call_direction'] || 'Direction')}" title="${esc(text['label-call_direction'] || 'Direction')}">` : '') +
			`<span class="op-ext-call-duration" data-created="${(call_info || {}).caller_channel_created_time || '0'}">${esc(duration_raw)}</span>` +
			`</div>`
		: '';
	const live_actions_html = has_live_call
		? `<div class="op-ext-call-actions">` +
			(permissions.operator_panel_record
				? `<img class="op-ext-action-icon" src="${record_icon}" alt="${esc(text['button-record'] || 'Record')}" title="${esc(text['button-record'] || 'Record')}" onclick="action_record('${call_uuid_js}')">`
				: '') +
			(permissions.operator_panel_eavesdrop
				? `<img class="op-ext-action-icon" src="../operator_panel/resources/images/eavesdrop.png" alt="${esc(text['button-eavesdrop'] || 'Eavesdrop')}" title="${esc(text['button-eavesdrop'] || 'Eavesdrop')}" draggable="true" ondragstart="on_eavesdrop_dragstart('${call_uuid_js}', event)" ondragend="on_drag_end()" onclick="action_eavesdrop('${call_uuid_js}')">`
				: '') +
			(permissions.operator_panel_coach
				? `<img class="op-ext-action-icon" src="../operator_panel/resources/images/whisper.svg" alt="${esc(text['button-whisper'] || 'Whisper')}" title="${esc(text['button-whisper'] || 'Whisper')}" onclick="action_whisper('${call_uuid_js}')">`
				: '') +
			(permissions.operator_panel_coach
				? `<img class="op-ext-action-icon" src="../operator_panel/resources/images/barge.svg" alt="${esc(text['button-barge'] || 'Barge')}" title="${esc(text['button-barge'] || 'Barge')}" onclick="action_barge('${call_uuid_js}')">`
				: '') +
			(permissions.operator_panel_hangup
				? `<img class="op-ext-action-icon" src="../operator_panel/resources/images/kill.png" alt="${esc(text['button-hangup'] || 'Hangup')}" title="${esc(text['button-hangup'] || 'Hangup')}" onclick="action_hangup('${call_uuid_js}')">`
				: '') +
			`</div>`
		: '';

	return `<div class="op-ext-block ${css_state}${mine_cls}" id="ext_block_${esc(num)}"` +
		` data-extension="${esc(num)}"${data_uuid}` +
		` data-can-receive-originate="${can_receive_originate ? 'true' : 'false'}"` +
		drag_attrs +
		` ondragover="on_ext_dragover(event)"` +
		` ondragleave="event.currentTarget.classList.remove('op-ext-drop-over')"` +
		` ondrop="on_ext_drop('${esc(num)}', event)">` +
		`<div class="op-ext-icon" title="${esc(status_hover)}"><img class="op-ext-status-icon" src="../operator_panel/resources/images/${status_icon}.png" width="28" height="28" alt="${esc(status_hover)}"></div>` +
		`<div class="op-ext-info${has_live_call ? ' op-has-live-call' : ''}">` +
		`<div class="op-ext-number">${esc(num)}</div>` +
		dialpad_html +
		(show_name ? `<div class="op-ext-name" title="${esc(raw_name)}">${esc(raw_name)}</div>` : '') +
		(state_label ? `<div class="op-ext-state-info">${state_label}</div>` : '') +
		live_call_meta_html +
		live_actions_html +
		`</div>` +
		`</div>`;
}

/**
 * Convert a string to Title Case.
 */
function to_title_case(str) {
	if (!str) return '';
	return str.replace(/\S+/g, function(word) {
		return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
	});
}

/**
 * Render a group card (dashboard-style widget card) containing extension blocks.
 * @param {string} group_key  Lowercase group key for data attribute
 * @param {string} title      The card title (call group name in Title Case)
 * @param {Array}  exts       Array of extension objects
 * @param {boolean} is_mine   Whether these are the user's own extensions
 * @returns {string}
 */
function render_group_card(group_key, title, exts, is_mine) {
	const hidden = active_group_filters.size > 0 && !active_group_filters.has(group_key) ? ' op-hidden' : '';
	// Card label position from default settings (global for all cards).
	const valid_positions = ['top', 'left', 'right', 'bottom', 'hidden'];
	let position = (typeof card_label_position === 'string' ? card_label_position.toLowerCase() : 'left');
	if (!valid_positions.includes(position)) position = 'left';

	let html = `<div class="op-group-card${hidden}" data-group-key="${esc(group_key)}"`;
	html += ` data-position="${esc(position)}"`;
	// Tooltip: show group name on hover using title attribute
	html += ` title="${esc(title)}">`;

	// For "My Extensions" card, hide the header text but keep grey shading
	const is_my_card = is_mine || group_key === '__my__';
	if (is_my_card) {
		html += `<div class="op-group-card-header op-hidden-text"></div>`;
	} else {
		html += `<div class="op-group-card-header">${esc(title)}</div>`;
	}

	html += `<div class="op-group-card-body">`;
	html += '<div class="op-ext-grid">';
	exts.forEach(ext => { html += render_ext_block(ext, is_mine); });
	html += '</div>';
	html += '</div></div>';
	return html;
}

/**
 * Build the group filter buttons in the filter bar.
 * @param {Array} group_keys  Sorted array of {key, display} objects
 */
function build_group_filter_buttons(group_keys) {
	all_group_keys_for_filters = group_keys.slice();

	const targets = [
		document.getElementById('group_filter_buttons'),
		document.getElementById('group_filter_buttons_calls'),
		document.getElementById('group_filter_buttons_conferences'),
		document.getElementById('group_filter_buttons_agents'),
	].filter(Boolean);
	if (targets.length === 0) return;

	let html = `<button type="button" class="op-group-filter-btn active" data-group-key="__all__" onclick="toggle_group_filter(this)">${esc(text['button-all'] || 'All')}</button>`;
	group_keys.forEach(g => {
		const is_active = active_group_filters.has(g.key) ? ' active' : '';
		html += `<button type="button" class="op-group-filter-btn${is_active}" data-group-key="${esc(g.key)}" onclick="toggle_group_filter(this)">${esc(g.display).toUpperCase()}</button>`;
	});
	targets.forEach(container => {
		container.innerHTML = html;
	});
}

/**
 * Toggle a group filter button on/off.
 */
function toggle_group_filter(btn) {
	const key = btn.getAttribute('data-group-key');

	if (key === '__all__') {
		// "All" button: clear all filters (show everything)
		active_group_filters.clear();
		document.querySelectorAll('.op-group-filter-btn').forEach(b => {
			b.classList.toggle('active', b.getAttribute('data-group-key') === '__all__');
		});
	} else {
		// Toggle individual group
		if (active_group_filters.has(key)) {
			active_group_filters.delete(key);
		} else {
			active_group_filters.add(key);
		}
		btn.classList.toggle('active');
		// Update "All" button state
		const all_btn = document.querySelector('.op-group-filter-btn[data-group-key="__all__"]');
		if (all_btn) all_btn.classList.toggle('active', active_group_filters.size === 0);
	}

	apply_extension_filters();
	apply_calls_filters();
	apply_conferences_filters();
	apply_agents_filters();
}

function matches_group_filter(group_key) {
	return active_group_filters.size === 0 || active_group_filters.has(group_key || '');
}

function apply_calls_filters() {
	const container = document.getElementById('calls_container');
	if (!container) return;
	const table = container.querySelector('table.list');
	if (!table) return;

	const filter_text = (((document.getElementById('calls_text_filter') || {}).value) || '').trim().toLowerCase();
	let visible_count = 0;
	table.querySelectorAll('tr.list-row').forEach(row => {
		const group_key = row.getAttribute('data-group-key') || '';
		const group_ok = matches_group_filter(group_key);
		const text_ok = !filter_text || (row.textContent || '').toLowerCase().indexOf(filter_text) !== -1;
		const show = group_ok && text_ok;
		row.style.display = show ? '' : 'none';
		if (show) visible_count++;
	});

	let empty = container.querySelector('.op-empty-filter-result');
	if (visible_count === 0) {
		if (!empty) {
			empty = document.createElement('p');
			empty.className = 'text-muted op-empty-filter-result';
			empty.textContent = text['label-no_calls_active'] || 'No active calls.';
			container.appendChild(empty);
		}
	} else if (empty) {
		empty.remove();
	}
}

function apply_conferences_filters() {
	const container = document.getElementById('conferences_container');
	if (!container) return;
	const filter_text = (((document.getElementById('conferences_text_filter') || {}).value) || '').trim().toLowerCase();
	let visible_count = 0;

	container.querySelectorAll('.card.mb-3').forEach(card => {
		const group_keys_raw = card.getAttribute('data-group-keys') || '';
		const group_keys = group_keys_raw ? group_keys_raw.split(',') : [''];
		const group_ok = active_group_filters.size === 0 || group_keys.some(k => active_group_filters.has(k || ''));
		const text_ok = !filter_text || (card.textContent || '').toLowerCase().indexOf(filter_text) !== -1;
		const show = group_ok && text_ok;
		card.style.display = show ? '' : 'none';
		if (show) visible_count++;
	});

	let empty = container.querySelector('.op-empty-filter-result');
	if (visible_count === 0 && container.querySelector('.card.mb-3')) {
		if (!empty) {
			empty = document.createElement('p');
			empty.className = 'text-muted op-empty-filter-result';
			empty.textContent = text['label-no_conferences_active'] || 'No active conferences.';
			container.appendChild(empty);
		}
	} else if (empty) {
		empty.remove();
	}
}

function apply_agents_filters() {
	const container = document.getElementById('agents_container');
	if (!container) return;
	const table = container.querySelector('table.list');
	if (!table) return;

	const filter_text = (((document.getElementById('agents_text_filter') || {}).value) || '').trim().toLowerCase();
	let visible_count = 0;
	table.querySelectorAll('tr.list-row').forEach(row => {
		const group_key = row.getAttribute('data-group-key') || '';
		const group_ok = matches_group_filter(group_key);
		const text_ok = !filter_text || (row.textContent || '').toLowerCase().indexOf(filter_text) !== -1;
		const show = group_ok && text_ok;
		row.style.display = show ? '' : 'none';
		if (show) visible_count++;
	});

	let empty = container.querySelector('.op-empty-filter-result');
	if (visible_count === 0) {
		if (!empty) {
			empty = document.createElement('p');
			empty.className = 'text-muted op-empty-filter-result';
			empty.textContent = text['label-no_agents'] || 'No agents.';
			container.appendChild(empty);
		}
	} else if (empty) {
		empty.remove();
	}
}

/**
 * Apply group filter and text filter to show/hide cards and extension blocks.
 */
function apply_extension_filters() {
	const text_val = (document.getElementById('extensions_text_filter') || {}).value || '';
	const filter_text = text_val.trim().toLowerCase();

	document.querySelectorAll('.op-group-card').forEach(card => {
		const key = card.getAttribute('data-group-key') || '';
		// Group filter
		const group_visible = active_group_filters.size === 0 || active_group_filters.has(key);
		card.classList.toggle('op-hidden', !group_visible);

		if (group_visible && filter_text) {
			// Text filter within visible cards
			let any_visible = false;
			card.querySelectorAll('.op-ext-block').forEach(block => {
				const ext_num  = (block.getAttribute('data-extension') || '').toLowerCase();
				const ext_name = (block.querySelector('.op-ext-name') || {}).textContent || '';
				const matches  = ext_num.indexOf(filter_text) !== -1 || ext_name.toLowerCase().indexOf(filter_text) !== -1;
				block.style.display = matches ? '' : 'none';
				if (matches) any_visible = true;
			});
			card.classList.toggle('op-hidden', !any_visible);
		} else if (group_visible) {
			card.querySelectorAll('.op-ext-block').forEach(block => {
				block.style.display = '';
			});
		}
	});
}

/**
 * Toggle edit mode for rearranging group cards via drag-and-drop.
 */
function toggle_edit_mode() {
	edit_mode_active = !edit_mode_active;
	const btn = document.getElementById('edit_mode_btn');
	const container = document.getElementById('extensions_container');
	if (btn) btn.classList.toggle('active', edit_mode_active);
	if (!container) return;

	if (edit_mode_active) {
		container.classList.add('op-edit-mode');
		if (typeof Sortable !== 'undefined') {
			sortable_instance = Sortable.create(container, {
				animation: 150,
				draggable: '.op-group-card',
				handle: '.op-group-card-header',
				ghostClass: 'sortable-ghost',
				filter: '.op-ext-block, .op-ext-action-icon',
				preventOnFilter: false,
				onEnd: function() {
					save_card_order();
				}
			});
		}
	} else {
		container.classList.remove('op-edit-mode');
		if (sortable_instance) {
			sortable_instance.destroy();
			sortable_instance = null;
		}
	}
}

/**
 * Save card order to localStorage.
 */
function save_card_order() {
	const container = document.getElementById('extensions_container');
	if (!container) return;
	const order = [];
	container.querySelectorAll('.op-group-card').forEach(card => {
		order.push(card.getAttribute('data-group-key'));
	});
	saved_card_order = order;
	try {
		localStorage.setItem('op_card_order_' + domain_name, JSON.stringify(order));
	} catch(e) { /* ignore */ }
}

/**
 * Load saved card order from localStorage.
 */
function load_card_order() {
	try {
		const raw = localStorage.getItem('op_card_order_' + domain_name);
		if (raw) saved_card_order = JSON.parse(raw);
	} catch(e) { /* ignore */ }
}

/**
 * Select a user status button and send it.
 */
function select_user_status(btn) {
	const status = btn.getAttribute('data-status');
	document.querySelectorAll('.op-status-btn').forEach(b => b.classList.remove('active'));
	btn.classList.add('active');
	send_user_status(status);
}

/**
 * Render the Extensions tab from extensions_map, with the logged-in user's
 * extensions shown first, then other extensions grouped by call_group in cards.
 */
function render_extensions_tab() {
	const container = document.getElementById('extensions_container');
	const my_container = document.getElementById('my_extensions_container');
	if (!container) return;

	const all    = Array.from(extensions_map.values());
	const own    = all.filter(e => user_own_extensions.includes(e.extension));
	const others = all.filter(e => !user_own_extensions.includes(e.extension));

	const badge = document.getElementById('extensions_count');
	if (badge) badge.textContent = all.length;

	if (all.length === 0) {
		if (my_container) my_container.innerHTML = '';
		container.innerHTML = `<p class="text-muted">${esc(text['label-no_extensions'] || 'No extensions found.')}</p>`;
		set_filter_bar_visibility(false);
		return;
	}

	// Group remaining extensions by call_group (case-insensitive)
	const groups = new Map();
	others.forEach(ext => {
		const raw_group = (ext.call_group || '').trim();
		const key = raw_group.toLowerCase() || '';
		if (!groups.has(key)) {
			groups.set(key, { display: raw_group ? to_title_case(raw_group) : '', exts: [] });
		}
		groups.get(key).exts.push(ext);
	});

	// Sort groups: named groups alphabetically, ungrouped last
	let sorted_keys = Array.from(groups.keys()).sort((a, b) => {
		if (a === '' && b !== '') return 1;
		if (a !== '' && b === '') return -1;
		return a.localeCompare(b);
	});

	// Build the list of all group keys for filters (including "my_extensions")
	const filter_keys = [];
	if (own.length > 0) {
		filter_keys.push({ key: '__my__', display: text['label-my_extensions'] || 'My Extensions' });
	}
	sorted_keys.forEach(key => {
		const g = groups.get(key);
		filter_keys.push({ key, display: g.display });
	});

	// Build filter buttons
	build_group_filter_buttons(filter_keys);

	// Show the filter bar now that we have data
	set_filter_bar_visibility(true);

	// Apply saved card order if available (only for other groups, not __my__)
	if (!saved_card_order) load_card_order();
	if (saved_card_order && saved_card_order.length > 0) {
		const all_keys_set = new Set(sorted_keys);
		const ordered = [];
		saved_card_order.forEach(k => {
			if (k === '__my__') return; // skip — My Extensions is always in its own container
			if (all_keys_set.has(k)) {
				ordered.push(k);
				all_keys_set.delete(k);
			}
		});
		// Append any new groups not in saved order
		all_keys_set.forEach(k => ordered.push(k));
		sorted_keys = ordered;
	}

	// Render My Extensions into its own container
	if (my_container) {
		if (own.length > 0) {
			my_container.innerHTML = render_group_card('__my__', text['label-my_extensions'] || 'My Extensions', own, true);
		} else {
			my_container.innerHTML = '';
		}
	}

	// Render other groups into the main container
	let html = '';
	const was_edit = container.classList.contains('op-edit-mode');

	if (saved_card_order && saved_card_order.length > 0) {
		// Render in saved order (excluding __my__ which is separate)
		const ordered_render = saved_card_order.filter(k => k !== '__my__' && groups.has(k));
		// Add any new keys not in saved order
		const rendered_set = new Set(ordered_render);
		sorted_keys.forEach(k => { if (!rendered_set.has(k)) ordered_render.push(k); });

		ordered_render.forEach(k => {
			if (groups.has(k)) {
				const g = groups.get(k);
				html += render_group_card(k, g.display, g.exts, false);
			}
		});
	} else {
		// Default order: sorted groups
		sorted_keys.forEach(key => {
			const group = groups.get(key);
			html += render_group_card(key, group.display, group.exts, false);
		});
	}

	container.innerHTML = html;
	if (was_edit) container.classList.add('op-edit-mode');

	// Re-apply filters
	apply_extension_filters();

	// Re-init sortable if edit mode was active
	if (edit_mode_active && typeof Sortable !== 'undefined') {
		if (sortable_instance) sortable_instance.destroy();
		sortable_instance = Sortable.create(container, {
			animation: 150,
			draggable: '.op-group-card',
			handle: '.op-group-card-header',
			ghostClass: 'sortable-ghost',
			filter: '.op-ext-block, .op-ext-action-icon',
			preventOnFilter: false,
			onEnd: function() { save_card_order(); }
		});
	}
}

/**
 * Debounce the extensions re-render so rapid call-events don't thrash the DOM.
 */
function schedule_extensions_render() {
	if (extensions_render_debounce) clearTimeout(extensions_render_debounce);
	extensions_render_debounce = setTimeout(render_extensions_tab, 120);
}

/**
 * Called on dragstart of a call row; stores the dragged UUID.
 * @param {string} uuid
 * @param {DragEvent} event
 */
function on_drag_call(uuid, event) {
	dragged_call_uuid = uuid;
	dragged_call_source_extension = null;
	dragged_extension = null; // Not dragging an extension
	dragged_eavesdrop_uuid = null;
	event.dataTransfer.setData('text/plain', uuid);
	event.dataTransfer.effectAllowed = 'move';
	set_drag_visual_state(true);
}

/**
 * Called when dragging an idle extension (for call origination).
 * @param {string} ext_number
 * @param {DragEvent} event
 */
function on_ext_dragstart(ext_number, event) {
	const call_uuid = (event.currentTarget && event.currentTarget.dataset)
		? (event.currentTarget.dataset.callUuid || '')
		: '';

	// If this extension currently has a live call, drag-and-drop performs transfer.
	if (call_uuid) {
		dragged_call_uuid = call_uuid;
		dragged_call_source_extension = ext_number;
		dragged_extension = null;
		event.dataTransfer.setData('text/plain', call_uuid);
		event.dataTransfer.effectAllowed = 'move';
	} else {
		dragged_extension = ext_number;
		dragged_call_uuid = null; // Not dragging a call
		dragged_call_source_extension = null;
		event.dataTransfer.setData('text/plain', ext_number);
		event.dataTransfer.effectAllowed = 'copy';
	}

	dragged_eavesdrop_uuid = null;
	set_drag_visual_state(true);
}

function on_drag_end() {
	set_drag_visual_state(false);
}

function set_drag_visual_state(is_dragging) {
	if (!document || !document.body) return;
	document.body.classList.toggle('op-dragging', !!is_dragging);
}

/** Toggle inline dialpad input for an extension block. */
function toggle_ext_dialpad(ext_number, event) {
	if (event) {
		event.preventDefault();
		event.stopPropagation();
	}

	const row = document.getElementById(`ext_block_${ext_number}`);
	if (!row) return;

	const input = row.querySelector('.op-ext-dial-input');
	if (!input) return;

	const visible = !input.classList.contains('d-none');
	if (visible) {
		input.classList.add('d-none');
		input.value = '';
		return;
	}

	input.classList.remove('d-none');
	setTimeout(() => input.focus(), 10);
}

/** Submit manual originate from extension to destination. */
function submit_ext_dial(ext_number, event) {
	if (event) {
		event.preventDefault();
		event.stopPropagation();
	}

	const row = document.getElementById(`ext_block_${ext_number}`);
	if (!row) return;

	const input = row.querySelector('.op-ext-dial-input');
	if (!input) return;

	const destination = (input.value || '').trim();
	if (!destination) {
		show_toast(text['label-destination_required'] || 'Please enter a destination.', 'warning');
		input.focus();
		return;
	}

	send_action('originate', { source: ext_number, destination })
		.then(() => {
			input.value = '';
			input.classList.add('d-none');
		})
		.catch(console.error);
}

/**
 * Allow dropping onto an extension block and highlight it.
 * @param {DragEvent} event
 */
function on_ext_dragover(event) {
	const can_receive_originate = event.currentTarget.dataset.canReceiveOriginate === 'true';
	const can_drop = dragged_call_uuid || dragged_eavesdrop_uuid || (dragged_extension && can_receive_originate);
	if (!can_drop) return;

	event.preventDefault();
	// Determine drop effect based on what's being dragged
	event.dataTransfer.dropEffect = dragged_call_uuid ? 'move' : 'copy';
	event.currentTarget.classList.add('op-ext-drop-over');
}

/**
 * Handle the drop: transfer the dragged call to the extension, or originate a new call.
 * @param {string}    ext_number
 * @param {DragEvent} event
 */
function on_ext_drop(ext_number, event) {
	event.preventDefault();
	event.currentTarget.classList.remove('op-ext-drop-over');
	set_drag_visual_state(false);

	// Determine what was dropped and perform the appropriate action
	if (dragged_call_uuid) {
		// Transfer an existing call to the destination extension
		const uuid = dragged_call_uuid;
		const source_ext = dragged_call_source_extension;
		dragged_call_uuid = null;
		dragged_call_source_extension = null;
		dragged_eavesdrop_uuid = null;
		dragged_extension = null;
		if (!uuid || !ext_number) return;
		if (source_ext && source_ext === ext_number) return;
		// When dragged from an extension block the UUID is the extension's own
		// leg; use -bleg so FreeSWITCH transfers the *other* leg (the caller).
		const payload = { uuid, destination: ext_number, context: domain_name };
		if (source_ext) payload.bleg = true;
		send_action('transfer', payload)
			.catch(console.error);
	} else if (dragged_eavesdrop_uuid) {
		// Eavesdrop an existing call using dropped extension as destination
		const uuid = dragged_eavesdrop_uuid;
		dragged_eavesdrop_uuid = null;
		dragged_call_uuid = null;
		dragged_call_source_extension = null;
		dragged_extension = null;
		if (!uuid || !ext_number) return;
		send_action('eavesdrop', { uuid, destination: ext_number, destination_extension: ext_number })
			.then(() => show_toast(text['button-eavesdrop'] || 'Eavesdrop started', 'success'))
			.catch((err) => {
				console.error(err);
				show_toast((err && err.message) || 'Eavesdrop failed', 'danger');
			});
	} else if (dragged_extension) {
		// Originate a new call from dragged_extension to ext_number
		const from_ext = dragged_extension;
		dragged_extension = null;
		dragged_eavesdrop_uuid = null;
		dragged_call_source_extension = null;
		const can_receive_originate = event.currentTarget.dataset.canReceiveOriginate === 'true';
		if (!from_ext || !ext_number || from_ext === ext_number) return; // Ignore self-drop
		if (!can_receive_originate) {
			show_toast(text['label-extension_unavailable'] || 'Destination extension is unavailable.', 'warning');
			return;
		}
		send_action('originate', { source: from_ext, destination: ext_number })
			.catch(console.error);
	}
}

/**
 * Handle a registration_change event pushed by the service when an
 * extension registers or unregisters with FreeSWITCH.
 * Updates extensions_map in place and re-renders; if the extension is
 * unknown (brand-new registration), reloads the full snapshot.
 * @param {object} event
 */
function on_registration_change(event) {
	lop_debug('[OP_REG_TRACE] [OP][reg][recv] registration_change event:', event);
	let ext_num          = event.extension   || (event.payload && event.payload.extension)   || '';
	let evt_domain       = event.domain_name || (event.payload && event.payload.domain_name) || '';
	const raw_reg        = event.registered ?? (event.payload && event.payload.registered);
	const registered     = raw_reg === true || raw_reg === 'true';
	const ws_state = (ws && ws.ws) ? ws.ws.readyState : -1;

	lop_debug('[OP][reg][step1] raw extraction', {
		extension_raw: ext_num,
		event_domain_raw: evt_domain,
		raw_registered: raw_reg,
		ws_ready_state: ws_state,
	});

	ext_num = ((ext_num || '') + '').trim();
	evt_domain = ((evt_domain || '') + '').trim().replace(/:\d+$/, '');
	if (ext_num.indexOf('@') !== -1) {
		const parts = ext_num.split('@');
		ext_num = (parts[0] || '').trim();
		if (!evt_domain && parts[1]) evt_domain = parts[1].trim().replace(/:\d+$/, '');
	}
	lop_debug('[OP][reg][normalized]', {
		extension: ext_num,
		event_domain: evt_domain,
		session_domain: domain_name,
		registered: registered,
		raw_registered: raw_reg,
	});
	lop_debug('[OP][reg][step2] map pre-check', {
		map_size: extensions_map.size,
		has_extension: extensions_map.has(ext_num),
	});

	if (!ext_num) {
		lop_debug('[OP][reg][drop] empty extension after normalization');
		return;
	}

	// Ignore registration events for other domains
	if (evt_domain && evt_domain !== domain_name) {
		lop_debug('[OP][reg][drop] domain mismatch', { evt_domain, domain_name, extension: ext_num });
		return;
	}
	lop_debug('[OP][reg][step3] domain accepted', {
		extension: ext_num,
		event_domain: evt_domain || domain_name,
	});

	const ext = extensions_map.get(ext_num);
	if (ext) {
		lop_debug('[OP][reg][update] found extension in map', { extension: ext_num, registered });
		lop_debug('[OP][reg][step4] before update', {
			extension: ext_num,
			previous_registered: ext.registered,
			previous_registration_count: ext.registration_count,
		});
		ext.registered         = registered;
		ext.registration_count = registered ? Math.max(1, ext.registration_count || 0) : 0;
		lop_debug('[OP][reg][step5] after update', {
			extension: ext_num,
			new_registered: ext.registered,
			new_registration_count: ext.registration_count,
		});
		schedule_extensions_render();
		lop_debug('[OP][reg][step6] render scheduled', { extension: ext_num });
	} else if (registered && extensions_map.size > 0) {
		lop_debug('[OP][reg][miss] extension not found in map, requesting snapshot', {
			extension: ext_num,
			map_size: extensions_map.size,
		});
		// Extension is new and map is loaded — reload the full snapshot
		load_extensions_snapshot();
		lop_debug('[OP][reg][step4b] snapshot requested due to map miss', { extension: ext_num });
		setTimeout(() => {
			const ext_after = extensions_map.get(ext_num);
			lop_debug('[OP][reg][step5b] snapshot verify', {
				extension: ext_num,
				found_after_snapshot: !!ext_after,
				registered_after_snapshot: ext_after ? ext_after.registered : null,
				map_size_after_snapshot: extensions_map.size,
			});
		}, 700);
	} else {
		lop_debug('[OP][reg][noop] extension missing and/or unregister event', {
			extension: ext_num,
			registered,
			map_size: extensions_map.size,
		});
	}

	setTimeout(() => {
		const ext_final = extensions_map.get(ext_num);
		lop_debug('[OP][reg][final] post-handler state', {
			extension: ext_num,
			in_map: !!ext_final,
			registered: ext_final ? ext_final.registered : null,
			registration_count: ext_final ? ext_final.registration_count : null,
		});
	}, 250);
	// If the map is empty the initial snapshot is still loading;
	// it will include the current registration state when it arrives.
}
