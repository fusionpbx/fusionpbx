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
let parked_state_timer = null;

/**
 * Live call map: uuid → call object.
 * Maintained incrementally from channel events.
 * @type {Map<string, object>}
 */
const calls_map = new Map();

/**
 * Parked call map: uuid -> normalized parked call object.
 * @type {Map<string, object>}
 */
const parked_calls_map = new Map();

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

/** UUID of the parked call being dragged onto an extension block. */
let dragged_parked_uuid = null;

/** UUIDs recently removed from parked state; suppressed from snapshots briefly. */
const parked_suppress_map = new Map();

/** Persistent store of the first valid parked_since_us seen per UUID.
 *  Survives parked_calls_map.clear() so duration never resets on snapshot refresh. */
const parked_since_known = new Map();

/** Current user's status; this is the only status source for My Extensions cards. */
let current_user_status = user_status.trim();

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
const calls_layout_slot_keys = ['left', 'right_top', 'right_bottom'];
const calls_layout_view_keys = ['all', 'incoming', 'outgoing'];
let calls_layout_order = null;
let dragged_calls_panel_view = '';
let _op_calls_doc_drag_handler = null;
let _op_calls_slot_rects = [];  /* [{el, rect}] cached on dragstart */
let _op_calls_highlighted_slot = null;
let _op_calls_render_deferred = false;  /* true when render_calls_tab was skipped during drag */

function clear_calls_panel_slot_over() {
	document.querySelectorAll('.op-calls-slot-over').forEach(el => el.classList.remove('op-calls-slot-over'));
	_op_calls_highlighted_slot = null;
}

/**
 * Find which .op-calls-slot the cursor (clientX/Y) is over using cached bounding rects.
 * This avoids all e.target / pointer-events issues.
 */
function _op_calls_slot_from_point(x, y) {
	for (let i = 0; i < _op_calls_slot_rects.length; i++) {
		const r = _op_calls_slot_rects[i].rect;
		if (x >= r.left && x <= r.right && y >= r.top && y <= r.bottom) {
			return _op_calls_slot_rects[i];
		}
	}
	return null;
}

/**
 * Document-level dragover handler attached only while a Calls panel header is being dragged.
 * Uses bounding-rect hit testing to reliably find the destination slot.
 */
function _op_calls_panel_doc_drag(e) {
	if (!dragged_calls_panel_view) return;
	const hit = _op_calls_slot_from_point(e.clientX, e.clientY);
	const slot = hit ? hit.el : null;
	/* Allow drop when cursor is over any slot */
	if (slot) e.preventDefault();
	/* Update highlighted slot only when it changes */
	if (slot !== _op_calls_highlighted_slot) {
		if (_op_calls_highlighted_slot) _op_calls_highlighted_slot.classList.remove('op-calls-slot-over');
		if (slot) slot.classList.add('op-calls-slot-over');
		_op_calls_highlighted_slot = slot;
	}
}
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

function get_extension_display_name(ext_number) {
	const ext = extensions_map.get((ext_number || '').toString());
	if (!ext) return '';
	return ((ext.effective_caller_id_name || ext.description || '') + '').trim();
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
	['extensions_filter_bar', 'calls_filter_bar', 'parked_filter_bar', 'conferences_filter_bar', 'agents_filter_bar'].forEach(id => {
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

	// Periodically refresh parked calls snapshot from FreeSWITCH valet_info.
	if (parked_state_timer) clearInterval(parked_state_timer);
	parked_state_timer = setInterval(load_parked_snapshot, 8000);

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
	ws.on_event('parked_active', on_parked_snapshot_event);

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
	load_parked_snapshot();
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
	if (parked_state_timer)         { clearInterval(parked_state_timer);         parked_state_timer         = null; }
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

/** Normalize epoch-like timestamps to Unix microseconds as a numeric string. */
function normalize_epoch_us(raw_value) {
	if (raw_value === null || raw_value === undefined) return '0';
	const value = String(raw_value).trim();
	if (!value) return '0';

	// Fast path: pure integer string (seconds/ms/us/ns).
	if (/^\d+$/.test(value)) {
		if (value.length <= 10) return `${value}000000`; // seconds -> us
		if (value.length <= 13) return `${value}000`; // ms -> us
		if (value.length <= 16) return value; // already us
		if (value.length <= 19) return String(Math.floor(Number(value) / 1000)); // ns -> us
		return value.slice(0, 16);
	}

	// Common case from some APIs: decimal seconds (e.g. 1711864980.123456).
	if (/^\d+\.\d+$/.test(value)) {
		const float_val = Number(value);
		if (Number.isFinite(float_val) && float_val > 0) {
			return String(Math.floor(float_val * 1000000));
		}
	}

	// Fallback: pull a 10-19 digit run from mixed strings.
	const match = value.match(/(\d{10,19})/);
	if (match && match[1]) {
		return normalize_epoch_us(match[1]);
	}

	return '0';
}

/** Format a Unix microsecond timestamp as elapsed time hh:mm:ss */
function format_elapsed(us_timestamp) {
	const normalized_us = normalize_epoch_us(us_timestamp);
	if (!normalized_us || normalized_us === '0') return '--:--:--';
	const start = Math.floor(Number(normalized_us) / 1000000);
	if (!Number.isFinite(start) || start <= 0) return '--:--:--';
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
		if (normalize_epoch_us(ts) !== '0') {
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

function is_parked_call(ch) {
	if (!ch || typeof ch !== 'object') return false;
	const callstate = ((ch.channel_call_state || ch.answer_state || '') + '').toLowerCase();
	const app = ((ch.application || ch.variable_current_application || '') + '').toLowerCase();
	const event_name = ((ch.event_name || '') + '').toLowerCase();
	const action = ((ch.action || '') + '').toLowerCase();
	const topic = ((ch.topic || '') + '').toLowerCase();
	return callstate === 'park'
		|| app.indexOf('park') !== -1
		|| event_name === 'channel_park'
		|| action === 'hold'
		|| (topic === 'valet_info' && !!((ch.valet_extension || '') + '').trim());
}

function get_parked_since_us(ch) {
	const candidates = [
		ch.parked_epoch_us,
		ch.parked_epoch,
		ch.variable_parked_epoch,
		ch.variable_park_epoch,
		ch.caller_channel_created_time,
		ch.variable_start_uepoch,
		ch.start_uepoch,
		ch.variable_start_epoch,
		ch.start_epoch,
		ch.event_date_timestamp,
	];
	for (const value of candidates) {
		const normalized = normalize_epoch_us(value);
		if (normalized !== '0') return normalized;
	}
	return '0';
}

function get_parking_lot(ch) {
	const candidates = [
		ch.valet_extension,
		ch.variable_valet_extension,
		ch.variable_parking_lot,
		ch.parking_lot,
		ch.parked_location,
		ch.variable_parked_location,
		ch.variable_parking_slot,
		ch.variable_park_slot,
		ch.caller_destination_number,
	];
	for (const value of candidates) {
		const cleaned = ((value || '') + '').trim();
		if (cleaned) return cleaned;
	}
	return '';
}

function get_parked_by(ch) {
	const candidates = [
		ch.parked_by,
		ch.variable_parked_by,
		ch.variable_referred_by_user,
		ch.variable_last_sent_callee_id_number,
		ch.channel_presence_id ? ch.channel_presence_id.split('@')[0] : '',
		ch.presence_id ? ch.presence_id.split('@')[0] : '',
		ch.caller_caller_id_number,
		ch.caller_id_number,
	];
	for (const value of candidates) {
		const cleaned = ((value || '') + '').trim();
		if (cleaned && cleaned !== '_undef_') return cleaned;
	}
	return '';
}

function get_original_destination(ch) {
	const candidates = [
		ch.original_destination_number,
		ch.variable_last_dialed_extension,
		ch.variable_dialed_extension,
		ch.caller_destination_number,
		ch.destination_number,
	];
	for (const value of candidates) {
		const cleaned = ((value || '') + '').trim();
		if (cleaned) return cleaned;
	}
	return '';
}

function normalize_parked_call(ch) {
	const uuid = get_call_uuid(ch);
	if (!uuid) return null;
	const caller_id_name = ((ch.variable_pre_transfer_caller_id_name || ch.caller_caller_id_name || ch.caller_id_name || '') + '').trim();
	const caller_id_number = ((ch.caller_caller_id_number || ch.caller_id_number || '') + '').trim();
	const parking_lot = get_parking_lot(ch);
	const parked_by = get_parked_by(ch);
	const original_destination = get_original_destination(ch);
	const parked_since_us = get_parked_since_us(ch);
	const group_key = get_call_group_key(ch);

	return {
		uuid,
		caller_id_name,
		caller_id_number,
		parking_lot,
		parked_by,
		original_destination,
		parked_since_us,
		group_key,
	};
}

function update_parked_badges() {
	const badge = document.getElementById('parked_count');
	if (badge) badge.textContent = parked_calls_map.size;
}

function upsert_parked_call(ch) {
	const normalized = normalize_parked_call(ch);
	if (!normalized) return;
	// Skip if this UUID was recently unparked (race window suppression)
	const suppress_until = parked_suppress_map.get(normalized.uuid);
	if (suppress_until) {
		if (Date.now() < suppress_until) return;
		parked_suppress_map.delete(normalized.uuid);
	}
	const current = parked_calls_map.get(normalized.uuid) || {};

	// Keep the first valid parked_since_us we ever saw for this UUID.
	// parked_since_known persists across parked_calls_map.clear() so snapshot
	// refreshes and rebuild_parked_calls_map cannot wipe the live duration.
	if (normalized.parked_since_us && normalized.parked_since_us !== '0') {
		parked_since_known.set(normalized.uuid, normalized.parked_since_us);
	} else if (parked_since_known.has(normalized.uuid)) {
		normalized.parked_since_us = parked_since_known.get(normalized.uuid);
	}

	parked_calls_map.set(normalized.uuid, Object.assign(current, normalized));
}

function remove_parked_call_by_uuid(uuid) {
	if (!uuid) return;
	parked_calls_map.delete(uuid);
	parked_since_known.delete(uuid);
	// Suppress this UUID from being re-added by snapshots for a short window
	parked_suppress_map.set(uuid, Date.now() + 6000);
}

function rebuild_parked_calls_map() {
	parked_calls_map.clear();
	for (const ch of calls_map.values()) {
		if (is_parked_call(ch)) upsert_parked_call(ch);
	}
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
	rebuild_parked_calls_map();

	render_calls_tab();
	render_parked_side_card();
	render_parked_tab();
	// Re-render extensions so their active/idle state reflects calls snapshot.
	schedule_extensions_render();
}

/** Handle server-pushed full calls snapshot event (topic='calls_active'). */
function on_calls_snapshot_event(event) {
	const payload = event.payload || event.data || event;
	apply_calls_snapshot(payload);
}

function load_parked_snapshot() {
	if (!ws || ws.ws.readyState !== WebSocket.OPEN) return;
	ws.request('active.operator.panel', 'parked_active', {domain_name: domain_name})
		.then(response => {
			apply_parked_snapshot(response.payload || []);
		})
		.catch(console.error);
}

function apply_parked_snapshot(payload) {
	const rows = Array.isArray(payload)
		? payload
		: ((payload && Array.isArray(payload.rows)) ? payload.rows : []);

	parked_calls_map.clear();
	rows.forEach(ch => {
		upsert_parked_call(ch);
	});
	render_parked_side_card();
	render_parked_tab();
}

function on_parked_snapshot_event(event) {
	const payload = event.payload || event.data || event;
	apply_parked_snapshot(payload);
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

	if (name === 'channel_unpark' || name === 'channel_destroy') {
		remove_parked_call_by_uuid(uuid);
	} else if (name === 'valet_info') {
		const action = ((event.action || '') + '').toLowerCase();
		if (action === 'hold') {
			const call = calls_map.get(uuid);
			if (call) upsert_parked_call(call);
		} else {
			// action=bridge or other means unparked
			remove_parked_call_by_uuid(uuid);
		}
	} else {
		const call = calls_map.get(uuid);
		if (call && (name === 'channel_park' || is_parked_call(call))) {
			upsert_parked_call(call);
		}
	}

	schedule_extensions_render();
	render_calls_tab();
	render_parked_side_card();
	render_parked_tab();
}

function get_sorted_parked_calls() {
	return Array.from(parked_calls_map.values()).sort((a, b) => {
		const a_ts = Number(a.parked_since_us || 0);
		const b_ts = Number(b.parked_since_us || 0);
		return a_ts - b_ts;
	});
}

function render_parked_side_card() {
	const container = document.getElementById('parked_side_container');
	if (!container) return;

	const parked_calls = get_sorted_parked_calls();
	const title = esc(text['label-parked_calls'] || 'Parked');
	const no_parked = esc(text['label-no_parked_calls'] || 'No parked calls');

	let html = `<div class="op-parked-card" ondragover="on_parked_dragover(event)" ondragleave="on_parked_dragleave(event)" ondrop="on_parked_drop(event)">`;
html += `<div class="op-parked-header">${title}</div>`;

	if (!parked_calls.length) {
		html += `<div class="op-parked-empty">${no_parked}</div>`;
		html += `</div>`;
		container.innerHTML = html;
		update_parked_badges();
		return;
	}

	html += `<div class="op-parked-list">`;
	parked_calls.forEach(p => {
		const uuid = esc(p.uuid);
		const caller = esc((p.caller_id_name || '').trim() || (p.caller_id_number || 'Unknown'));
		const lot = esc(p.parking_lot || '-');
		const dest = esc(p.original_destination || p.caller_id_number || '-');
		const parked_by = esc(p.parked_by || '-');
		const parked_since = esc(p.parked_since_us || '0');
		html += `<div class="op-parked-item" draggable="true" data-uuid="${uuid}" data-group-key="${esc(p.group_key || '')}"`;
		html += ` ondragstart="on_drag_parked('${uuid}', event)" ondragend="on_drag_end()">`;
		html += `<span class="op-parked-duration mono" data-created="${parked_since}">${esc(format_elapsed(parked_since))}</span>`;
		html += `<div class="op-parked-main">${lot}</div>`;
		html += `<div class="op-parked-sub">${caller}</div>`;
		html += `<div class="op-parked-sub">${esc(text['label-on_call'] || 'On Call')}: ${dest}</div>`;
		html += `<div class="op-parked-sub">${esc(text['label-parked_by'] || 'Parked By')}: ${parked_by}</div>`;
		html += `</div>`;
	});
	html += `</div></div>`;

	container.innerHTML = html;
	update_parked_badges();
}

function render_parked_tab() {
	const container = document.getElementById('parked_container');
	if (!container) return;

	const parked_calls = get_sorted_parked_calls();
	update_parked_badges();

	if (!parked_calls.length) {
		container.innerHTML = `<p class="text-muted">${esc(text['label-no_parked_calls'] || 'No parked calls')}</p>`;
		apply_parked_filters();
		return;
	}

	let html = "<div class='card op-parked-drop-zone' ondragover=\"on_parked_dragover(event)\" ondragleave=\"on_parked_dragleave(event)\" ondrop=\"on_parked_drop(event)\">\n";
	html += "<table class='list'>\n";
	html += "<tr class='list-header'>\n";
	html += `<th>${esc(text['label-caller_id'] || 'Caller ID')}</th>\n`;
	html += `<th>${esc(text['label-parking_lot'] || 'Parking Lot')}</th>\n`;
	html += `<th>${esc(text['label-duration'] || 'Duration')}</th>\n`;
	html += `<th>${esc(text['label-parked_by'] || 'Parked By')}</th>\n`;
	html += `<th>${esc(text['label-original_destination'] || 'Original Destination')}</th>\n`;
	html += "</tr>\n";

	parked_calls.forEach(p => {
		const uuid = esc(p.uuid);
		const caller_name = esc(p.caller_id_name || '');
		const caller_num = esc(p.caller_id_number || '-');
		const lot = esc(p.parking_lot || '-');
		const parked_by = esc(p.parked_by || '-');
		const dest = esc(p.original_destination || '-');
		const group_key = esc(p.group_key || '');
		html += `<tr class="list-row" draggable="true" data-uuid="${uuid}" data-group-key="${group_key}" ondragstart="on_drag_parked('${uuid}', event)" ondragend="on_drag_end(); document.querySelectorAll('.op-ext-block').forEach(b=>b.classList.remove('op-ext-drop-over'))">\n`;
		html += `  <td>${caller_name ? `${caller_name}<br><small>${caller_num}</small>` : caller_num}</td>\n`;
		html += `  <td>${lot}</td>\n`;
		html += `  <td class="mono" data-created="${esc(p.parked_since_us || '0')}">${esc(format_elapsed(p.parked_since_us || '0'))}</td>\n`;
		html += `  <td>${parked_by}</td>\n`;
		html += `  <td>${dest}</td>\n`;
		html += "</tr>\n";
	});

	html += "</table>\n</div>\n";
	container.innerHTML = html;
	apply_parked_filters();
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

function normalize_calls_layout_order(raw_order) {
	const normalized = [];
	if (Array.isArray(raw_order)) {
		raw_order.forEach(value => {
			const view_key = ((value || '') + '').trim().toLowerCase();
			if (calls_layout_view_keys.includes(view_key) && !normalized.includes(view_key)) {
				normalized.push(view_key);
			}
		});
	}
	calls_layout_view_keys.forEach(view_key => {
		if (!normalized.includes(view_key)) normalized.push(view_key);
	});
	return normalized.slice(0, calls_layout_slot_keys.length);
}

function load_calls_layout_order() {
	try {
		const raw = localStorage.getItem('op_calls_layout_' + domain_name);
		calls_layout_order = raw
			? normalize_calls_layout_order(JSON.parse(raw))
			: normalize_calls_layout_order(null);
	} catch (err) {
		calls_layout_order = normalize_calls_layout_order(null);
	}
}

function save_calls_layout_order() {
	if (!Array.isArray(calls_layout_order)) return;
	try {
		localStorage.setItem('op_calls_layout_' + domain_name, JSON.stringify(calls_layout_order));
	} catch (err) {
		// Ignore storage access errors.
	}
}

function get_calls_layout_view_for_slot(slot_key) {
	if (!Array.isArray(calls_layout_order)) load_calls_layout_order();
	const slot_index = calls_layout_slot_keys.indexOf(((slot_key || '') + '').toLowerCase());
	if (slot_index === -1) return calls_layout_view_keys[0];
	return calls_layout_order[slot_index] || calls_layout_view_keys[slot_index] || calls_layout_view_keys[0];
}

function move_calls_panel_view_to_slot(view_key, slot_key) {
	if (!Array.isArray(calls_layout_order)) load_calls_layout_order();

	const view = ((view_key || '') + '').trim().toLowerCase();
	const slot = ((slot_key || '') + '').trim().toLowerCase();
	const from_index = calls_layout_order.indexOf(view);
	const to_index = calls_layout_slot_keys.indexOf(slot);

	if (!calls_layout_view_keys.includes(view) || from_index === -1 || to_index === -1 || from_index === to_index) {
		return false;
	}

	const displaced = calls_layout_order[to_index];
	calls_layout_order[to_index] = view;
	calls_layout_order[from_index] = displaced;
	save_calls_layout_order();
	return true;
}

/**
 * Render the Calls tab from the in-memory calls_map.
 */
function render_calls_tab() {
	/* While a Calls panel header is being dragged, defer rendering so the DOM
	   isn't replaced mid-drag (which would orphan cached slot elements and wipe
	   the destination-slot highlight class). The deferred render fires in
	   on_calls_panel_drag_end(). */
	if (dragged_calls_panel_view) {
		_op_calls_render_deferred = true;
		return;
	}
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

	const columns_html =
		`<tr class="list-header">\n`
		+ `<th class="mono" style="width:28px"></th>\n`
		+ `<th>${esc(text['label-extension'] || 'Extension')}</th>\n`
		+ `<th>${esc(text['label-name'] || 'Name')}</th>\n`
		+ `<th>${esc(text['label-destination'] || 'Destination')}</th>\n`
		+ `<th>${esc(text['label-state'] || 'State')}</th>\n`
		+ `<th>${esc(text['label-duration'] || 'Duration')}</th>\n`
		+ `<th class="right">${esc(text['label-actions'] || 'Actions')}</th>\n`
		+ `</tr>\n`;

	function build_call_row(ch) {
		const uuid_raw = get_call_uuid(ch);
		if (!uuid_raw) return null;

		const uuid = esc(uuid_raw);
		const group_key = esc(get_call_group_key(ch));
		const ext_number = ((ch.channel_presence_id || '').split('@')[0] || '').trim();
		const raw_cid_name = (ch.caller_caller_id_name || ch.caller_id_name || '').toString();
		const raw_cid_num = (ch.caller_caller_id_number || ch.caller_id_number || '').toString().trim();
		const raw_dest_num = (ch.caller_destination_number || '').toString().trim();

		let direction_raw = (ch.call_direction || ch.variable_call_direction || '').toString().toLowerCase();
		if (ext_number && raw_cid_num && raw_dest_num) {
			if (ext_number === raw_cid_num && ext_number !== raw_dest_num) {
				direction_raw = 'outbound';
			}
			else if (ext_number === raw_dest_num && ext_number !== raw_cid_num) {
				direction_raw = 'inbound';
			}
		}

		const peer_number = resolve_peer_number_for_leg(ch, ext_number, direction_raw);
		let caller_number_raw = (ext_number || raw_cid_num || '').toString().trim();

		if (direction_raw === 'inbound' && raw_cid_num && raw_dest_num && raw_cid_num === raw_dest_num && peer_number && raw_cid_num !== peer_number) {
			caller_number_raw = peer_number;
		}
		if (caller_number_raw === peer_number) {
			if (raw_dest_num && raw_dest_num !== peer_number) caller_number_raw = raw_dest_num;
			else if (raw_cid_num && raw_cid_num !== peer_number) caller_number_raw = raw_cid_num;
		}

		const extension_number_raw = (ext_number || caller_number_raw || '').toString().trim();
		let extension_name_raw = '';
		if (extension_number_raw) {
			extension_name_raw = get_extension_display_name(extension_number_raw);
			if (!extension_name_raw && extension_number_raw === raw_cid_num) {
				extension_name_raw = raw_cid_name;
			}
		}
		if (extension_name_raw && extension_name_raw === extension_number_raw) {
			extension_name_raw = '';
		}
		if (extension_name_raw) {
			const n = extension_name_raw.toLowerCase();
			if (n === 'outbound call' || n === 'inbound call') extension_name_raw = '';
		}

		const extension_number = esc(extension_number_raw);
		const extension_name = esc(extension_name_raw);
		const dest = esc(peer_number || raw_dest_num || ext_number);
		const state = esc(ch.channel_call_state || ch.answer_state || '');
		const direction = esc(direction_raw);
		const direction_icon = direction_raw === 'inbound'
			? '../operator_panel/resources/images/inbound.png'
			: (direction_raw === 'outbound' ? '../operator_panel/resources/images/outbound.png' : '');
		const created_ts = ch.caller_channel_created_time || '0';
		const elapsed = esc(format_elapsed(created_ts));
		const is_recording = call_is_recording(ch, uuid_raw);
		const record_icon = is_recording
			? '../operator_panel/resources/images/recording.png'
			: '../operator_panel/resources/images/record.png';

		let row_html = `<tr class="list-row" draggable="true" data-uuid="${uuid}" data-group-key="${group_key}" ondragstart="on_drag_call('${uuid}', event)" ondragend="on_drag_end(); document.querySelectorAll('.op-ext-block').forEach(b=>b.classList.remove('op-ext-drop-over'))" oncontextmenu="on_call_contextmenu(event, '${uuid}')">\n`;
		row_html += `  <td class="mono">${direction_icon ? `<img src="${direction_icon}" width="12" height="12" alt="${direction}" title="${direction}" style="vertical-align:middle;">` : ''}</td>\n`;
		row_html += `  <td>${extension_number}</td>\n`;
		row_html += `  <td>${extension_name}</td>\n`;
		row_html += `  <td>${dest}</td>\n`;
		row_html += `  <td>${state}</td>\n`;
		row_html += `  <td class="mono" data-created="${created_ts}">${elapsed}</td>\n`;
		row_html += `  <td class="right">\n`;

		if (permissions.operator_panel_hangup) {
			row_html += `    <a class="btn-action" href="javascript:void(0)" title="${esc(text['button-hangup'] || 'Hangup')}" onclick="action_hangup('${uuid}')">`
				+ `<img class="op-ext-action-icon" src="../operator_panel/resources/images/kill.png" alt="${esc(text['button-hangup'] || 'Hangup')}"></a> `;
		}
		if (permissions.operator_panel_eavesdrop) {
			row_html += `    <a class="btn-action" href="javascript:void(0)" title="${esc(text['button-eavesdrop'] || 'Eavesdrop')}" onclick="action_eavesdrop('${uuid}')">`
				+ `<img class="op-ext-action-icon" src="../operator_panel/resources/images/eavesdrop.png" alt="${esc(text['button-eavesdrop'] || 'Eavesdrop')}"></a> `;
		}
		if (permissions.operator_panel_coach) {
			row_html += `    <a class="btn-action" href="javascript:void(0)" title="${esc(text['button-whisper'] || 'Whisper')}" onclick="action_whisper('${uuid}')">`
				+ `<img class="op-ext-action-icon" src="../operator_panel/resources/images/whisper.svg" alt="${esc(text['button-whisper'] || 'Whisper')}"></a> `;
			row_html += `    <a class="btn-action" href="javascript:void(0)" title="${esc(text['button-barge'] || 'Barge')}" onclick="action_barge('${uuid}')">`
				+ `<img class="op-ext-action-icon" src="../operator_panel/resources/images/barge.svg" alt="${esc(text['button-barge'] || 'Barge')}"></a> `;
		}
		if (permissions.operator_panel_record) {
			row_html += `    <a class="btn-action" href="javascript:void(0)" title="${esc(text['button-record'] || 'Record')}" onclick="action_record('${uuid}')">`
				+ `<img class="op-ext-action-icon" src="${record_icon}" alt="${esc(text['button-record'] || 'Record')}"></a> `;
		}

		row_html += "  </td>\n";
		row_html += "</tr>\n";

		return {
			row_html,
			direction: direction_raw,
		};
	}

	function render_calls_section(view_key, title, rows_html, empty_label, extra_class) {
		return `<section class="card op-calls-card ${extra_class || ''}" data-calls-view="${esc(view_key)}">`
			+ `<div class="op-calls-card-title" draggable="true" ondragstart="on_calls_panel_drag_start(event, '${view_key}')" ondragend="on_calls_panel_drag_end(event)" title="${esc(text['label-drag_to_rearrange'] || 'Drag to rearrange panel')}">${esc(title)}<span class="op-calls-drag-hint">DRAG</span></div>`
			+ `<div class="op-calls-table-wrap">`
			+ `<table class="list">`
			+ columns_html
			+ `<tbody>${rows_html.join('')}</tbody>`
			+ `</table>`
			+ `</div>`
			+ `<p class="text-muted op-calls-empty"${rows_html.length ? ' style="display:none;"' : ''}>${esc(empty_label)}</p>`
			+ `</section>`;
	}

	const all_rows = [];
	const incoming_rows = [];
	const outgoing_rows = [];

	calls.forEach(ch => {
		const rendered = build_call_row(ch);
		if (!rendered) return;

		all_rows.push(rendered.row_html);
		if (rendered.direction === 'inbound') {
			incoming_rows.push(rendered.row_html);
		}
		else if (rendered.direction === 'outbound') {
			outgoing_rows.push(rendered.row_html);
		}
	});

	if (all_rows.length === 0) {
		container.innerHTML = `<p class="text-muted">${esc(text['label-no_calls_active'] || 'No active calls.')}</p>`;
		return;
	}
	if (!Array.isArray(calls_layout_order)) load_calls_layout_order();

	const sections_by_view = {
		all: render_calls_section('all', text['tab-calls'] || text['label-tab_calls'] || 'Calls', all_rows, text['label-no_calls_active'] || 'No active calls.', 'op-calls-card-all'),
		incoming: render_calls_section('incoming', 'Incoming Calls', incoming_rows, 'No incoming calls.', 'op-calls-card-incoming'),
		outgoing: render_calls_section('outgoing', 'Outgoing Calls', outgoing_rows, 'No outgoing calls.', 'op-calls-card-outgoing'),
	};

	const left_view = get_calls_layout_view_for_slot('left');
	const right_top_view = get_calls_layout_view_for_slot('right_top');
	const right_bottom_view = get_calls_layout_view_for_slot('right_bottom');

	function render_calls_slot(slot_key, section_html, extra_class) {
		const classes = `op-calls-pane op-calls-slot ${extra_class || ''}`.trim();
		return `<div class="${classes}" data-slot-key="${esc(slot_key)}" ondrop="on_calls_panel_drop(event, '${slot_key}')">${section_html}</div>`;
	}

	let html = `<div class="op-calls-layout">`;
	html += render_calls_slot('left', sections_by_view[left_view], 'op-calls-pane-left');
	html += `<div class="op-calls-pane op-calls-pane-right">`;
	html += render_calls_slot('right_top', sections_by_view[right_top_view], 'op-calls-pane-half');
	html += render_calls_slot('right_bottom', sections_by_view[right_bottom_view], 'op-calls-pane-half');
	html += `</div>`;
	html += `</div>`;

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

// ─── Transfer mode ────────────────────────────────────────────────────────────

/** Current transfer mode: 'blind' or 'attended'. Persisted per page session. */
let transfer_mode = 'blind';

/** Resolve transfer mode from the toggle button state. */
function get_transfer_mode_from_button() {
	const btn = document.getElementById('btn_transfer_mode_toggle');
	if (!btn) return transfer_mode;
	const mode = (btn.dataset && btn.dataset.transferMode) ? btn.dataset.transferMode : '';
	if (mode === 'blind' || mode === 'attended') return mode;
	return transfer_mode;
}

/** Resolve transfer action name from current transfer mode and permissions. */
function get_transfer_action_from_button() {
	const mode = get_transfer_mode_from_button();
	if (mode === 'attended' && permissions.operator_panel_transfer_attended) {
		return 'transfer_attended';
	}
	return 'transfer';
}

/** Toggle between blind and attended transfer modes and update the button. */
function toggle_transfer_mode() {
	transfer_mode = (transfer_mode === 'blind') ? 'attended' : 'blind';
	const btn = document.getElementById('btn_transfer_mode_toggle');
	if (!btn) return;
	if (transfer_mode === 'attended') {
		btn.dataset.transferMode = 'attended';
		btn.textContent = text['label-attended_transfer'] || 'Conference';
		btn.title       = text['label-attended_transfer_title'] || 'Attended transfer: destination is called first; connected to caller when answered';
		btn.style.background   = '#198754';
		btn.style.borderColor  = '#198754';
		btn.style.color        = '#fff';
	} else {
		btn.dataset.transferMode = 'blind';
		btn.textContent = text['label-blind_transfer'] || 'Blind';
		btn.title       = text['label-blind_transfer_title'] || 'Blind transfer: immediately connect the call to the destination';
		btn.style.background  = '';
		btn.style.borderColor = '';
		btn.style.color       = '';
	}
}

document.addEventListener('DOMContentLoaded', function () {
	const btn = document.getElementById('btn_transfer_mode_toggle');
	if (!btn) return;
	if (!btn.dataset.transferMode) {
		btn.dataset.transferMode = 'blind';
	}
});

// ─── Context menu ─────────────────────────────────────────────────────────────

/** Hide the right-click context menu. */
function hide_context_menu() {
	const m = document.getElementById('op_context_menu');
	if (m) m.style.display = 'none';
}

/**
 * Build and display a right-click context menu at the cursor.
 * @param {MouseEvent} event
 * @param {Array}      items  Objects: {label, icon_class, fn, danger} | {separator:true} | {header:string}
 */
function show_context_menu(event, items) {
	event.preventDefault();
	event.stopPropagation();

	const menu = document.getElementById('op_context_menu');
	if (!menu || !items.length) return;

	menu.innerHTML = '';
	items.forEach(function (item) {
		if (item.separator) {
			const sep = document.createElement('div');
			sep.className = 'op-ctx-separator';
			menu.appendChild(sep);
			return;
		}
		if (item.header) {
			const hdr = document.createElement('div');
			hdr.className = 'op-ctx-header';
			hdr.textContent = item.header;
			menu.appendChild(hdr);
			return;
		}
		const btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'op-ctx-item' + (item.danger ? ' op-ctx-danger' : '');
		if (item.icon_class) {
			const ico = document.createElement('i');
			ico.className = item.icon_class + ' op-ctx-icon';
			btn.appendChild(ico);
		}
		const span = document.createElement('span');
		span.textContent = item.label;
		btn.appendChild(span);
		btn.addEventListener('click', function () {
			hide_context_menu();
			if (typeof item.fn === 'function') item.fn();
		});
		menu.appendChild(btn);
	});

	// Estimate height before measuring and position to avoid off-screen overflow
	menu.style.left    = '-9999px';
	menu.style.top     = '-9999px';
	menu.style.display = 'block';
	const mw = menu.offsetWidth;
	const mh = menu.offsetHeight;
	const px = Math.min(event.clientX + window.scrollX, window.innerWidth  + window.scrollX - mw - 4);
	const py = Math.min(event.clientY + window.scrollY, window.innerHeight + window.scrollY - mh - 4);
	menu.style.left = px + 'px';
	menu.style.top  = py + 'px';
}

// Close context menu on outside click or Escape
document.addEventListener('click', hide_context_menu, true);
document.addEventListener('keydown', function (e) {
	if (e.key === 'Escape') hide_context_menu();
});

/**
 * Right-click handler for extension blocks.
 * @param {MouseEvent} event
 * @param {string}     ext_num
 */
function on_ext_contextmenu(event, ext_num) {
	const block = document.getElementById('ext_block_' + ext_num);
	const uuid  = block ? (block.getAttribute('data-call-uuid') || '') : '';
	const is_mine  = !!(block && block.classList.contains('op-ext-mine'));
	const { state, call_info } = get_extension_call_state(ext_num);
	const has_call  = !!uuid;
	const ext_data = extensions_map.get(ext_num) || {};
	const voicemail_enabled = (ext_data.voicemail_enabled || '') === 'true';

	// Derive call direction for the ringing extension to suppress Reject on outbound calls.
	const call_dest = ((call_info || {}).caller_destination_number || '').trim();
	const call_cid  = ((call_info || {}).caller_caller_id_number  || '').trim();
	let direction_raw = '';
	if (call_dest === ext_num && call_cid !== ext_num) direction_raw = 'inbound';
	else if (call_cid === ext_num && call_dest !== ext_num) direction_raw = 'outbound';
	else direction_raw = (((call_info || {}).call_direction || (call_info || {}).variable_call_direction || '') + '').toLowerCase();
	const is_outbound = direction_raw === 'outbound';

	const items = [];
	items.push({ header: ext_num });

	if (!has_call) {
		if (is_mine) {
			// Own idle extension: open dialpad to originate from this extension
			if (permissions.operator_panel_originate) {
				items.push({ label: text['label-dial_number'] || 'Dial a Number', icon_class: 'fa-solid fa-phone',
					fn: function () { toggle_ext_dialpad(ext_num, null); }
				});
			}
		} else {
			// Other idle extension: originate from one of my extensions TO this extension
			if (permissions.operator_panel_originate) {
				items.push({ label: text['button-call'] || 'Call', icon_class: 'fa-solid fa-phone',
					fn: function () { action_call_extension(ext_num); }
				});
				if (voicemail_enabled) {
					items.push({ label: text['button-call_voicemail'] || 'Call Voicemail', icon_class: 'fa-solid fa-voicemail',
						fn: function () { action_call_voicemail(ext_num); }
					});
				}
			}
		}
	} else if (state === 'ringing') {
		if (is_mine) {
			if (permissions.operator_panel_originate) {
				items.push({ label: text['label-dial_number'] || 'Dial a Number', icon_class: 'fa-solid fa-phone',
					fn: function () { toggle_ext_dialpad(ext_num, null); } });
			}
			if (permissions.operator_panel_hangup) {
				if (permissions.operator_panel_originate) items.push({ separator: true });
				if (!is_outbound) {
					items.push({ label: text['button-reject'] || 'Reject', icon_class: 'fa-solid fa-phone-slash',
						fn: function () { action_reject(uuid); } });
				}
				items.push({ label: text['button-hangup_caller'] || 'Hangup Caller', icon_class: 'fa-solid fa-xmark',
					fn: function () { action_hangup_caller(uuid); }, danger: true });
			}
		} else {
			if (permissions.operator_panel_manage) {
				items.push({ label: text['button-intercept'] || 'Intercept', icon_class: 'fa-solid fa-phone-volume',
					fn: function () { action_intercept_icon(uuid, ext_num); } });
			}
			if (permissions.operator_panel_hangup) {
				items.push({ label: text['button-hangup_caller'] || 'Hangup Caller', icon_class: 'fa-solid fa-xmark',
					fn: function () { action_hangup_caller(uuid); }, danger: true });
			}
		}
	} else if (has_call) {
		// active / held
		if (is_mine && permissions.operator_panel_originate) {
			items.push({ label: text['label-dial_number'] || 'Dial a Number', icon_class: 'fa-solid fa-phone',
				fn: function () { toggle_ext_dialpad(ext_num, null); } });
			items.push({ separator: true });
		}
		if (permissions.operator_panel_manage) {
			items.push({ label: text['label-transfer'] || 'Transfer', icon_class: 'fa-solid fa-arrow-right-from-bracket',
				fn: function () { open_transfer_modal(uuid, ext_num); } });
		}
		if (permissions.operator_panel_eavesdrop) {
			items.push({ label: text['button-eavesdrop'] || 'Eavesdrop', icon_class: 'fa-solid fa-ear-listen',
				fn: function () { action_eavesdrop(uuid); } });
		}
		if (permissions.operator_panel_coach) {
			items.push({ label: text['button-whisper'] || 'Whisper', icon_class: 'fa-solid fa-comment-dots',
				fn: function () { action_whisper(uuid); } });
			items.push({ label: text['button-barge'] || 'Barge', icon_class: 'fa-solid fa-volume-high',
				fn: function () { action_barge(uuid); } });
		}
		if (permissions.operator_panel_record) {
			items.push({ label: text['button-record'] || 'Record', icon_class: 'fa-solid fa-circle-dot',
				fn: function () { action_record(uuid); } });
		}
		if (permissions.operator_panel_hangup) {
			if (items.length > 1) items.push({ separator: true });
			items.push({ label: text['label-hangup'] || 'Hangup', icon_class: 'fa-solid fa-phone-slash',
				fn: function () { action_hangup(uuid); }, danger: true });
		}
	}

	if (items.length <= 1) return; // nothing beyond the header
	show_context_menu(event, items);
}

/**
 * Infer the local extension for a call UUID (used for leg-aware actions).
 * Prefers channel presence and then CID/destination fields when they map to known extensions.
 * @param {string} uuid
 * @returns {string}
 */
function get_call_source_extension(uuid) {
	if (!uuid) return '';
	const ch = calls_map.get(uuid);
	if (!ch) return '';

	const presence = (((ch.channel_presence_id || '').split('@')[0]) || '').trim();
	if (presence && extensions_map.has(presence)) return presence;

	const dest = ((ch.caller_destination_number || '') + '').trim();
	if (dest && extensions_map.has(dest)) return dest;

	const cid = ((ch.caller_caller_id_number || ch.caller_id_number || '') + '').trim();
	if (cid && extensions_map.has(cid)) return cid;

	return '';
}

/**
 * Derive normalized panel call state for a single call row.
 * @param {object|null} ch
 * @returns {string}
 */
function get_call_row_state(ch) {
	if (!ch) return 'unknown';
	const cs = ((ch.channel_call_state || '') + '').toUpperCase();
	const as = ((ch.answer_state || '') + '').toUpperCase();
	if (cs === 'HELD' || as === 'HELD') return 'held';
	if (cs.indexOf('RING') !== -1 || as.indexOf('RING') !== -1 || as === 'EARLY') return 'ringing';
	return 'active';
}

/**
 * Right-click handler for call rows in the Calls tab.
 * @param {MouseEvent} event
 * @param {string}     uuid
 */
function on_call_contextmenu(event, uuid) {
	if (!uuid) return;
	const items = [];
	const call_info = calls_map.get(uuid) || null;
	const source_ext = get_call_source_extension(uuid);
	const is_mine = !!(source_ext && Array.isArray(user_own_extensions) && user_own_extensions.includes(source_ext));
	const state = get_call_row_state(call_info);

	if (state === 'ringing' && source_ext) {
		const call_dest = ((call_info || {}).caller_destination_number || '').trim();
		const call_cid  = ((call_info || {}).caller_caller_id_number || (call_info || {}).caller_id_number || '').trim();
		let direction_raw = '';
		if (call_dest === source_ext && call_cid !== source_ext) direction_raw = 'inbound';
		else if (call_cid === source_ext && call_dest !== source_ext) direction_raw = 'outbound';
		else direction_raw = (((call_info || {}).call_direction || (call_info || {}).variable_call_direction || '') + '').toLowerCase();
		const is_outbound = direction_raw === 'outbound';

		if (is_mine) {
			if (permissions.operator_panel_hangup) {
				if (!is_outbound) {
					items.push({ label: text['button-reject'] || 'Reject', icon_class: 'fa-solid fa-phone-slash',
						fn: function () { action_reject(uuid); } });
				}
				items.push({ label: text['button-hangup_caller'] || 'Hangup Caller', icon_class: 'fa-solid fa-xmark',
					fn: function () { action_hangup_caller(uuid); }, danger: true });
			}
		} else {
			if (permissions.operator_panel_manage) {
				items.push({ label: text['button-intercept'] || 'Intercept', icon_class: 'fa-solid fa-phone-volume',
					fn: function () { action_intercept_icon(uuid, source_ext); } });
			}
			if (permissions.operator_panel_hangup) {
				items.push({ label: text['button-hangup_caller'] || 'Hangup Caller', icon_class: 'fa-solid fa-xmark',
					fn: function () { action_hangup_caller(uuid); }, danger: true });
			}
		}
	}

	if (!items.length) {
		if (permissions.operator_panel_manage) {
			items.push({ label: text['label-transfer'] || 'Transfer', icon_class: 'fa-solid fa-arrow-right-from-bracket',
				fn: function () { open_transfer_modal(uuid, source_ext); } });
		}
		if (permissions.operator_panel_eavesdrop) {
			items.push({ label: text['button-eavesdrop'] || 'Eavesdrop', icon_class: 'fa-solid fa-ear-listen',
				fn: function () { action_eavesdrop(uuid); } });
		}
		if (permissions.operator_panel_coach) {
			items.push({ label: text['button-whisper'] || 'Whisper', icon_class: 'fa-solid fa-comment-dots',
				fn: function () { action_whisper(uuid); } });
			items.push({ label: text['button-barge'] || 'Barge', icon_class: 'fa-solid fa-volume-high',
				fn: function () { action_barge(uuid); } });
		}
		if (permissions.operator_panel_record) {
			items.push({ label: text['button-record'] || 'Record', icon_class: 'fa-solid fa-circle-dot',
				fn: function () { action_record(uuid); } });
		}
		if (permissions.operator_panel_hangup) {
			if (items.length) items.push({ separator: true });
			items.push({ label: text['label-hangup'] || 'Hangup', icon_class: 'fa-solid fa-phone-slash',
				fn: function () { action_hangup(uuid); }, danger: true });
		}
	}

	if (!items.length) return;
	show_context_menu(event, items);
}

/** Reject a ringing call on user's own extension (kills B-leg, phone stops ringing). */
function action_reject(uuid) {
	if (!uuid) return;
	send_action('hangup', { uuid }).catch(console.error);
}

/** Hangup the caller (A-leg) of a ringing call. */
function action_hangup_caller(uuid) {
	if (!uuid) return;
	if (!confirm(text['label-confirm_hangup_caller'] || 'Hang up the caller?')) return;
	send_action('hangup_caller', { uuid }).catch(console.error);
}

/** Intercept a ringing call from the icon (uses the ringing action modal). */
/**
 * Originate a call from one of the user's own extensions to the given extension number.
 */
function action_call_extension(ext_num) {
	if (!Array.isArray(user_own_extensions) || user_own_extensions.length === 0) return;
	const from_ext = user_own_extensions.length === 1
		? user_own_extensions[0]
		: prompt(text['label-your_extension'] || 'Your extension:');
	if (!from_ext) return;
	send_action('originate', { source: from_ext, destination: ext_num }).catch(console.error);
}

function action_call_voicemail(ext_num) {
	if (!Array.isArray(user_own_extensions) || user_own_extensions.length === 0) return;
	const from_ext = user_own_extensions.length === 1
		? user_own_extensions[0]
		: prompt(text['label-your_extension'] || 'Your extension:');
	if (!from_ext) return;
	send_action('originate', { source: from_ext, destination: '*99' + ext_num }).catch(console.error);
}

function action_intercept_icon(uuid, target_ext) {
	if (!uuid) return;
	const from_ext = (Array.isArray(user_own_extensions) && user_own_extensions.length === 1)
		? user_own_extensions[0]
		: null;
	if (from_ext) {
		show_ringing_action_modal(uuid, from_ext, target_ext);
	} else if (Array.isArray(user_own_extensions) && user_own_extensions.length > 1) {
		const ext = prompt(text['label-your_extension'] || 'Your extension to receive the call:');
		if (!ext) return;
		show_ringing_action_modal(uuid, ext, target_ext);
	}
}

/** Open the transfer dialog for the given UUID. */
function open_transfer_modal(uuid, source_ext) {
	const uuid_field = document.getElementById('transfer_uuid');
	const dest_field = document.getElementById('transfer_destination');
	const src_field  = document.getElementById('transfer_source_extension');
	if (!uuid_field || !dest_field) return;

	uuid_field.value = uuid;
	dest_field.value = '';
	if (src_field) src_field.value = source_ext || '';

	const dlg = document.getElementById('transfer_dialog');
	if (!dlg) return;

	dlg.showModal();
	setTimeout(() => dest_field.focus(), 100);
}

/** Called by the Transfer button inside the dialog. */
function confirm_transfer() {
	const uuid        = (document.getElementById('transfer_uuid')        || {}).value || '';
	const destination = (document.getElementById('transfer_destination') || {}).value || '';
	const source_ext  = (document.getElementById('transfer_source_extension') || {}).value || '';

	if (!uuid || !destination) {
		show_toast(text['label-destination_required'] || 'Please enter a destination.', 'warning');
		return;
	}

	const dlg = document.getElementById('transfer_dialog');
	if (dlg) dlg.close();

	const selected_mode = get_transfer_mode_from_button();
	transfer_mode = selected_mode; // keep runtime state aligned with UI state
	const action = get_transfer_action_from_button();

	const payload = {
		uuid,
		destination,
		context: domain_name,
		source_extension: source_ext,
	};
	if (source_ext) payload.bleg = true;

	console.debug('[OP] confirm_transfer', { action, transfer_mode: selected_mode, payload });
	send_action(action, payload).catch(console.error);
}

// ─── Attended transfer consultation bar ───────────────────────────────────────

/** State for an in-progress attended transfer. */
let attended_transfer = null; // { parked_uuid, operator_uuid, destination, source_ext }

/**
 * Show the floating attended-transfer bar so the operator can complete or cancel.
 */
function show_attended_transfer_bar(parked_uuid, operator_uuid, destination, source_ext) {
	attended_transfer = { parked_uuid, operator_uuid, destination, source_ext };
	const bar = document.getElementById('attended_transfer_bar');
	if (!bar) return;
	const label = bar.querySelector('.op-att-label');
	if (label) {
		label.textContent = (text['label-consulting_with'] || 'Consulting with {dest}...').replace('{dest}', destination);
	}
	bar.style.display = 'flex';
}

/** Hide the attended-transfer bar. */
function hide_attended_transfer_bar() {
	attended_transfer = null;
	const bar = document.getElementById('attended_transfer_bar');
	if (bar) bar.style.display = 'none';
}

/** Complete the attended transfer: bridge parked caller to destination. */
function complete_attended_transfer() {
	if (!attended_transfer) return;
	const { parked_uuid, operator_uuid, destination, source_ext } = attended_transfer;
	hide_attended_transfer_bar();
	send_action('transfer_attended_complete', {
		parked_uuid,
		operator_uuid,
		destination,
		context: domain_name,
	}).then(function() {
		show_toast(text['message-transfer_completed'] || 'Transfer completed', 'success');
	}).catch(console.error);
}

/** Cancel the attended transfer: hang up consultation, reconnect caller to operator. */
function cancel_attended_transfer() {
	if (!attended_transfer) return;
	const { parked_uuid, operator_uuid, source_ext } = attended_transfer;
	hide_attended_transfer_bar();
	send_action('transfer_attended_cancel', {
		parked_uuid,
		operator_uuid,
		source_extension: source_ext,
	}).then(function() {
		show_toast(text['message-transfer_cancelled'] || 'Transfer cancelled', 'success');
	}).catch(console.error);
}

/**
 * Show a modal with Intercept / Call / Eavesdrop choices when dropping
 * an extension onto a ringing extension.
 */
function show_ringing_action_modal(target_uuid, from_ext, target_ext) {
	const dlg = document.getElementById('ringing_action_dialog');
	if (!dlg) return;

	// Description text
	const desc_el = document.getElementById('ringing_action_description');
	if (desc_el) {
		desc_el.textContent = (text['label-ringing_action_desc'] || 'Extension {target} is ringing. What would you like to do from {source}?')
			.replace('{target}', target_ext)
			.replace('{source}', from_ext);
	}

	function cleanup() {
		dlg.close();
		btn_intercept.removeEventListener('click', on_intercept);
		btn_call.removeEventListener('click', on_call);
		btn_eavesdrop.removeEventListener('click', on_eavesdrop);
		dlg.removeEventListener('close', on_close);
	}

	const btn_intercept = document.getElementById('ringing_action_intercept');
	const btn_call      = document.getElementById('ringing_action_call');
	const btn_eavesdrop = document.getElementById('ringing_action_eavesdrop');

	function on_intercept() {
		cleanup();
		const call = calls_map.get(target_uuid);
		let a_leg = call && (call.other_leg_unique_id || call.variable_bridge_uuid || '');
		// Reverse lookup: find the A-leg in calls_map whose other_leg points to target
		if (!a_leg) {
			for (const [uuid, ch] of calls_map) {
				if (uuid !== target_uuid && (ch.other_leg_unique_id === target_uuid || ch.variable_bridge_uuid === target_uuid)) {
					a_leg = uuid;
					break;
				}
			}
		}
		if (a_leg) {
			send_action('transfer', { uuid: a_leg, destination: from_ext, context: domain_name })
				.then(() => show_toast(text['label-call_intercepted'] || 'Call intercepted', 'success'))
				.catch((err) => {
					console.error(err);
					show_toast((err && err.message) || 'Intercept failed', 'danger');
				});
		} else {
			// Server-side fallback: let PHP find the A-leg
			send_action('intercept', { uuid: target_uuid, destination: from_ext, destination_extension: from_ext })
				.then(() => show_toast(text['label-call_intercepted'] || 'Call intercepted', 'success'))
				.catch((err) => {
					console.error(err);
					show_toast((err && err.message) || 'Intercept failed', 'danger');
				});
		}
	}

	function on_call() {
		cleanup();
		send_action('originate', { source: from_ext, destination: target_ext })
			.catch(console.error);
	}

	function on_eavesdrop() {
		cleanup();
		send_action('eavesdrop', { uuid: target_uuid, destination: from_ext, destination_extension: from_ext })
			.then(() => show_toast(text['button-eavesdrop'] || 'Eavesdrop started', 'success'))
			.catch((err) => {
				console.error(err);
				show_toast((err && err.message) || 'Eavesdrop failed', 'danger');
			});
	}

	function on_close() {
		btn_intercept.removeEventListener('click', on_intercept);
		btn_call.removeEventListener('click', on_call);
		btn_eavesdrop.removeEventListener('click', on_eavesdrop);
		dlg.removeEventListener('close', on_close);
	}

	btn_intercept.addEventListener('click', on_intercept);
	btn_call.addEventListener('click', on_call);
	btn_eavesdrop.addEventListener('click', on_eavesdrop);
	dlg.addEventListener('close', on_close);

	dlg.showModal();
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
			current_user_status = (status || '').trim();
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
	const current = current_user_status;
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
	const is_own_extension = !!is_mine || (Array.isArray(user_own_extensions) && user_own_extensions.includes(num));
	const voicemail_enabled = (ext.voicemail_enabled || '') === 'true';
	const { state, call_uuid, call_info } = get_extension_call_state(num);

	const user_status_raw = is_own_extension
		? current_user_status
		: (ext.user_status || '').trim();

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
	const user_status = user_status_raw;
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
			if (reg && (ext.user_uuid || is_own_extension)) {
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
	const is_ringing = state === 'ringing';
	let live_actions_html = '';
	if (has_live_call && is_ringing && is_mine) {
		// Ringing on user's own extension: Reject (inbound only) + Hangup (A-leg)
		const show_reject = direction_raw !== 'outbound';
		live_actions_html = `<div class="op-ext-call-actions">` +
			(permissions.operator_panel_hangup && show_reject
				? `<img class="op-ext-action-icon" src="../operator_panel/resources/images/reject.svg" alt="${esc(text['button-reject'] || 'Reject')}" title="${esc(text['button-reject'] || 'Reject')}" onclick="action_reject('${call_uuid_js}')">`
				: '') +
			(permissions.operator_panel_hangup
				? `<img class="op-ext-action-icon" src="../operator_panel/resources/images/kill.png" alt="${esc(text['button-hangup'] || 'Hangup')}" title="${esc(text['button-hangup_caller'] || 'Hangup Caller')}" onclick="action_hangup_caller('${call_uuid_js}')">`
				: '') +
			`</div>`;
	} else if (has_live_call && is_ringing && !is_mine) {
		// Ringing on another user's extension: Intercept icon
		live_actions_html = `<div class="op-ext-call-actions">` +
			(permissions.operator_panel_manage
				? `<img class="op-ext-action-icon" src="../operator_panel/resources/images/intercept.svg" alt="${esc(text['button-intercept'] || 'Intercept')}" title="${esc(text['button-intercept'] || 'Intercept')}" onclick="action_intercept_icon('${call_uuid_js}', '${esc(num)}')">`
				: '') +
			(permissions.operator_panel_hangup
				? `<img class="op-ext-action-icon" src="../operator_panel/resources/images/kill.png" alt="${esc(text['button-hangup'] || 'Hangup')}" title="${esc(text['button-hangup_caller'] || 'Hangup Caller')}" onclick="action_hangup_caller('${call_uuid_js}')">`
				: '') +
			`</div>`;
	} else if (has_live_call) {
		// Active/held call: normal action icons
		live_actions_html = `<div class="op-ext-call-actions">` +
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
			`</div>`;
	}

	return `<div class="op-ext-block ${css_state}${mine_cls}" id="ext_block_${esc(num)}"` +
		` data-extension="${esc(num)}"${data_uuid}` +
		` data-can-receive-originate="${can_receive_originate ? 'true' : 'false'}"` +
		drag_attrs +
		` ondragover="on_ext_dragover(event)"` +
		` ondragleave="event.currentTarget.classList.remove('op-ext-drop-over')"` +
		` ondrop="on_ext_drop('${esc(num)}', event)"` +
		` oncontextmenu="on_ext_contextmenu(event, '${esc(num)}')">` +
		(ext.contact_image
			? `<div class="op-ext-icon" title="${esc(status_hover)}"><div class="op-ext-contact-photo" style="background-image: url('/core/contacts/contact_attachment.php?id=${esc(ext.contact_image)}&action=download&sid=${esc(contact_image_sid)}')"></div></div>`
			: `<div class="op-ext-icon" title="${esc(status_hover)}"><img class="op-ext-status-icon" src="../operator_panel/resources/images/${status_icon}.png" width="28" height="28" alt="${esc(status_hover)}"></div>`) +
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
		document.getElementById('group_filter_buttons_parked'),
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
	apply_parked_filters();
	apply_conferences_filters();
	apply_agents_filters();
}

function matches_group_filter(group_key) {
	return active_group_filters.size === 0 || active_group_filters.has(group_key || '');
}

function apply_calls_filters() {
	const container = document.getElementById('calls_container');
	if (!container) return;
	const rows = container.querySelectorAll('tr.list-row');
	if (!rows.length) return;

	const filter_text = (((document.getElementById('calls_text_filter') || {}).value) || '').trim().toLowerCase();
	let visible_total = 0;
	rows.forEach(row => {
		const group_key = row.getAttribute('data-group-key') || '';
		const group_ok = matches_group_filter(group_key);
		const text_ok = !filter_text || (row.textContent || '').toLowerCase().indexOf(filter_text) !== -1;
		const show = group_ok && text_ok;
		row.style.display = show ? '' : 'none';
		if (show) visible_total++;
	});

	container.querySelectorAll('.op-calls-card').forEach(card => {
		const card_rows = card.querySelectorAll('tbody tr.list-row');
		let card_visible = 0;
		card_rows.forEach(row => {
			if (row.style.display !== 'none') card_visible++;
		});
		const card_empty = card.querySelector('.op-calls-empty');
		if (card_empty) {
			card_empty.style.display = card_visible === 0 ? '' : 'none';
		}
	});

	let empty = container.querySelector('.op-empty-filter-result');
	if (visible_total === 0) {
		if (!empty) {
			empty = document.createElement('p');
			empty.className = 'text-muted op-empty-filter-result';
			empty.textContent = text['label-no_calls_active'] || 'No active calls.';
			container.appendChild(empty);
		}
	}
	else if (empty) {
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

function apply_parked_filters() {
	const container = document.getElementById('parked_container');
	if (!container) return;
	const table = container.querySelector('table.list');
	if (!table) return;

	const filter_text = (((document.getElementById('parked_text_filter') || {}).value) || '').trim().toLowerCase();
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
			empty.textContent = text['label-no_parked_calls'] || 'No parked calls';
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
		render_parked_side_card();
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
	render_parked_side_card();

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

function on_calls_panel_drag_start(event, view_key) {
	dragged_calls_panel_view = ((view_key || '') + '').trim().toLowerCase();
	if (!calls_layout_view_keys.includes(dragged_calls_panel_view)) {
		dragged_calls_panel_view = '';
		return;
	}

	if (event && event.dataTransfer) {
		event.dataTransfer.effectAllowed = 'move';
		event.dataTransfer.setData('text/plain', dragged_calls_panel_view);
	}

	const card = event && event.target ? event.target.closest('.op-calls-card') : null;
	if (card) card.classList.add('op-calls-card-dragging');
	const layout = document.querySelector('.op-calls-layout');
	if (layout) layout.classList.add('op-calls-layout-dragging');

	/* Cache bounding rects of all slot elements for hit-testing during drag.
	 * This avoids relying on e.target / pointer-events which are unreliable during drag. */
	_op_calls_slot_rects = [];
	document.querySelectorAll('.op-calls-slot').forEach(function(el) {
		_op_calls_slot_rects.push({ el: el, rect: el.getBoundingClientRect() });
	});

	/* Attach a single document-level dragover handler for destination-slot highlighting. */
	if (_op_calls_doc_drag_handler) {
		document.removeEventListener('dragover', _op_calls_doc_drag_handler);
	}
	_op_calls_doc_drag_handler = _op_calls_panel_doc_drag;
	document.addEventListener('dragover', _op_calls_doc_drag_handler);
}

/* on_calls_panel_drag_enter / drag_over / drag_leave are no longer wired to slot elements.
 * Highlighting is handled entirely by the document-level _op_calls_panel_doc_drag listener
 * attached in on_calls_panel_drag_start and removed in on_calls_panel_drag_end.
 * These stubs are kept so any legacy references do not throw. */
function on_calls_panel_drag_enter(event) {}
function on_calls_panel_drag_over(event) {}
function on_calls_panel_drag_leave(event) {}

function on_calls_panel_drop(event, slot_key) {
	event.preventDefault();

	if (!dragged_calls_panel_view) { clear_calls_panel_slot_over(); return; }
	/* Resolve drop slot using bounding-rect hit test, fall back to inline slot_key */
	const hit = _op_calls_slot_from_point(event.clientX, event.clientY);
	const resolved_slot_key = (hit && hit.el && hit.el.dataset && hit.el.dataset.slotKey)
		? hit.el.dataset.slotKey
		: slot_key;
	const moved = move_calls_panel_view_to_slot(dragged_calls_panel_view, resolved_slot_key);
	on_calls_panel_drag_end(event);
	if (moved) render_calls_tab();
}

function on_calls_panel_drag_end(event) {
	const card = event && event.target ? event.target.closest('.op-calls-card') : null;
	if (card) card.classList.remove('op-calls-card-dragging');
	clear_calls_panel_slot_over();
	document.querySelectorAll('.op-calls-card-dragging').forEach(el => el.classList.remove('op-calls-card-dragging'));
	const layout = document.querySelector('.op-calls-layout');
	if (layout) layout.classList.remove('op-calls-layout-dragging');
	dragged_calls_panel_view = '';
	/* Remove the document-level highlight listener added in on_calls_panel_drag_start */
	if (_op_calls_doc_drag_handler) {
		document.removeEventListener('dragover', _op_calls_doc_drag_handler);
		_op_calls_doc_drag_handler = null;
	}
	_op_calls_slot_rects = [];
	_op_calls_highlighted_slot = null;
	/* Fire any render that was deferred while we were dragging */
	if (_op_calls_render_deferred) {
		_op_calls_render_deferred = false;
		render_calls_tab();
	}
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
	dragged_parked_uuid = null;
	event.dataTransfer.setData('text/plain', uuid);
	event.dataTransfer.effectAllowed = 'move';
	set_drag_visual_state(true);
}

function can_drop_into_parked_box() {
	return !!(dragged_call_uuid || dragged_eavesdrop_uuid === null && dragged_extension === null && dragged_parked_uuid === null);
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
	dragged_parked_uuid = null;
	set_drag_visual_state(true);
}

/**
 * Called on dragstart of a parked call row; stores the parked UUID.
 * @param {string} uuid
 * @param {DragEvent} event
 */
function on_drag_parked(uuid, event) {
	dragged_parked_uuid = uuid;
	dragged_call_uuid = null;
	dragged_call_source_extension = null;
	dragged_extension = null;
	dragged_eavesdrop_uuid = null;
	event.dataTransfer.setData('text/plain', uuid);
	event.dataTransfer.effectAllowed = 'move';
	set_drag_visual_state(true);
}

function on_drag_end() {
	dragged_parked_uuid = null;
	document.querySelectorAll('.op-parked-drop-over').forEach(el => el.classList.remove('op-parked-drop-over'));
	set_drag_visual_state(false);
}

function on_parked_dragover(event) {
	const can_drop = !!dragged_call_uuid;
	if (!can_drop) return;
	event.preventDefault();
	event.dataTransfer.dropEffect = 'move';
	const target = event.currentTarget;
	if (target) target.classList.add('op-parked-drop-over');
}

function on_parked_dragleave(event) {
	const target = event.currentTarget;
	if (target) target.classList.remove('op-parked-drop-over');
}

function on_parked_drop(event) {
	event.preventDefault();
	const target = event.currentTarget;
	if (target) target.classList.remove('op-parked-drop-over');
	set_drag_visual_state(false);

	if (!dragged_call_uuid || !park_destination) return;

	const uuid = dragged_call_uuid;
	const source_ext = dragged_call_source_extension;
	dragged_call_uuid = null;
	dragged_call_source_extension = null;
	dragged_eavesdrop_uuid = null;
	dragged_extension = null;
	dragged_parked_uuid = null;

	const payload = { uuid, destination: park_destination, context: domain_name };
	// Determine whether to use -bleg based on call type.
	// For internal ext-to-ext calls: transfer the dragged extension's own
	// channel into the parking lot (no -bleg), so the dragged ext is parked
	// and the other internal extension is freed.
	// For inbound external calls: use -bleg to park the external caller
	// and free the local extension.
	if (source_ext) {
		const call = calls_map.get(uuid);
		const caller_num = (call && (call.caller_caller_id_number || call.caller_id_number || '')).toString().trim();
		const is_internal = caller_num !== '' && extensions_map.has(caller_num);
		if (!is_internal) {
			payload.bleg = true;
		}
	}
	send_action('transfer', payload)
		.then(() => {
			// Multiple retries: FreeSWITCH may need time to complete the park
			setTimeout(load_parked_snapshot, 600);
			setTimeout(load_parked_snapshot, 1500);
			setTimeout(load_parked_snapshot, 3000);
		})
		.catch(console.error);
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
	const can_drop = dragged_call_uuid || dragged_parked_uuid || dragged_eavesdrop_uuid || (dragged_extension && can_receive_originate);
	if (!can_drop) return;

	event.preventDefault();
	// Determine drop effect based on what's being dragged
	event.dataTransfer.dropEffect = (dragged_call_uuid || dragged_parked_uuid) ? 'move' : 'copy';
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

	// When an extension block is dropped onto a ringing target, show the
	// action chooser modal regardless of whether the source ext has a call.
	const target_is_ringing = event.currentTarget.classList.contains('op-ext-ringing');
	const target_call_uuid  = event.currentTarget.dataset.callUuid || '';
	const from_ext_block    = dragged_extension || (dragged_call_uuid && dragged_call_source_extension) || '';

	if (target_is_ringing && target_call_uuid && from_ext_block) {
		const from_ext = dragged_extension || dragged_call_source_extension;
		dragged_call_uuid = null;
		dragged_call_source_extension = null;
		dragged_extension = null;
		dragged_eavesdrop_uuid = null;
		dragged_parked_uuid = null;
		if (from_ext && from_ext !== ext_number) {
			show_ringing_action_modal(target_call_uuid, from_ext, ext_number);
			return;
		}
	}

	// Determine what was dropped and perform the appropriate action
	if (dragged_call_uuid) {
		// Transfer an existing call to the destination extension
		const uuid = dragged_call_uuid;
		const source_ext = dragged_call_source_extension;
		dragged_call_uuid = null;
		dragged_call_source_extension = null;
		dragged_eavesdrop_uuid = null;
		dragged_parked_uuid = null;
		dragged_extension = null;
		if (!uuid || !ext_number) return;
		if (source_ext && source_ext === ext_number) return;
		// When dragged from an extension block the UUID is the extension's own
		// leg; use -bleg so FreeSWITCH transfers the *other* leg (the caller).
		const payload = { uuid, destination: ext_number, context: domain_name };
		if (source_ext) payload.bleg = true;
		const action = get_transfer_action_from_button();
		send_action(action, payload)
			.catch(console.error);
	} else if (dragged_parked_uuid) {
		const uuid = dragged_parked_uuid;
		dragged_parked_uuid = null;
		dragged_call_uuid = null;
		dragged_call_source_extension = null;
		dragged_eavesdrop_uuid = null;
		dragged_extension = null;
		if (!uuid || !ext_number) return;
		// Remove from parked map immediately for responsive UI
		remove_parked_call_by_uuid(uuid);
		render_parked_side_card();
		render_parked_tab();
		send_action('transfer', { uuid, destination: ext_number, context: domain_name })
			.then(() => {
				setTimeout(load_parked_snapshot, 600);
				setTimeout(load_parked_snapshot, 1500);
			})
			.catch(console.error);
	} else if (dragged_eavesdrop_uuid) {
		// Eavesdrop an existing call using dropped extension as destination
		const uuid = dragged_eavesdrop_uuid;
		dragged_eavesdrop_uuid = null;
		dragged_call_uuid = null;
		dragged_call_source_extension = null;
		dragged_parked_uuid = null;
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
		dragged_parked_uuid = null;
		if (!from_ext || !ext_number || from_ext === ext_number) return; // Ignore self-drop

		const can_receive_originate = event.currentTarget.dataset.canReceiveOriginate === 'true';
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
