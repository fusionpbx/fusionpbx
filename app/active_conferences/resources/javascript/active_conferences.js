/**
 * Active Conferences JS
 * Handles WebSocket communication and rendering for Active Conferences List and Room views.
 */

// Global state variables
let ws = null;
let reconnect_attempts = 0;
let ping_interval_timer = null;
let last_pong_time = Date.now();
let ping_timeout = null;
let auth_timeout = null;
let refresh_interval_timer = null;
let pong_failure_count = 0;

// Room specific state
let member_timers = {};
let timer_interval = null;

/**
 * Connect to the WebSocket server
 */
function connect_websocket() {
	const ws_url = `wss://${window.location.hostname}/websockets/`;

	try {
		ws = new ws_client(ws_url, token);

		ws.on_event('authenticated', on_authenticated);

		// Handle authentication failure
		ws.on_event('authentication_failed', function(event) {
			console.error('WebSocket authentication failed - session may have expired');
			update_connection_status('disconnected');
			// Use global PROJECT_PATH if available, or just reload/redirect
			const project_path = (typeof PROJECT_PATH !== 'undefined') ? PROJECT_PATH : '';
			window.location.href = project_path + '/?path=' + encodeURIComponent(window.location.pathname);
		});

		ws.ws.addEventListener("open", () => {
			console.log('WebSocket connection opened');
			reconnect_attempts = 0;
			update_connection_status('connecting');

			auth_timeout = setTimeout(() => {
				console.error('Authentication timeout - session may have expired');
				update_connection_status('disconnected');
				const project_path = (typeof PROJECT_PATH !== 'undefined') ? PROJECT_PATH : '';
				window.location.href = project_path + '/?path=' + encodeURIComponent(window.location.pathname);
			}, ws_config.auth_timeout);
		});

		ws.ws.addEventListener("close", (event) => {
			console.warn('WebSocket disconnected - code:', event.code);
			if (auth_timeout) { clearTimeout(auth_timeout); auth_timeout = null; }
			update_connection_status('disconnected');

			if (ping_interval_timer) { clearInterval(ping_interval_timer); ping_interval_timer = null; }
			if (refresh_interval_timer) { clearInterval(refresh_interval_timer); refresh_interval_timer = null; }
			if (ping_timeout) { clearTimeout(ping_timeout); ping_timeout = null; }

			setTimeout(() => { window.location.reload(); }, ws_config.reconnect_delay);
		});

		ws.ws.addEventListener("error", (error) => {
			console.error('WebSocket error:', error);
		});

	} catch (error) {
		console.error('Failed to connect to WebSocket:', error);
		update_connection_status('disconnected');
	}
}

/**
 * Handle successful authentication
 */
function on_authenticated(message) {
	console.log('WebSocket authenticated');
	pong_failure_count = 0;
	update_connection_status('warning');
	if (auth_timeout) { clearTimeout(auth_timeout); auth_timeout = null; }

	send_ping();
	if (ping_interval_timer) clearInterval(ping_interval_timer);
	ping_interval_timer = setInterval(send_ping, ws_config.ping_interval);

	// No polling - websockets only for real-time updates
	// refresh_interval is not used; we rely on incremental websocket events

	ws.subscribe('active.conferences');

	// Register event handlers
	ws.on_event('*', handle_websocket_event);

	// Initial load - one-time fetch on connection
	refresh_data();
}

/**
 * Universal refresh function that delegates to specific load functions
 */
function refresh_data() {
    if (typeof load_conference_room_data === 'function' && document.getElementById('conference_container')) {
        load_conference_room_data();
    } else if (typeof load_conference_list === 'function' && document.getElementById('conferences_container')) {
        load_conference_list();
    }
}

/**
 * Universal event handler that delegates
 */
function handle_websocket_event(event) {
    console.log('handle_websocket_event - received event:', JSON.stringify(event));
    console.log('handle_websocket_event - conference_container exists:', !!document.getElementById('conference_container'));
    console.log('handle_websocket_event - conferences_container exists:', !!document.getElementById('conferences_container'));

    if (typeof handle_room_event === 'function' && document.getElementById('conference_container')) {
        console.log('Delegating to handle_room_event');
        handle_room_event(event);
    } else if (typeof handle_list_event === 'function' && document.getElementById('conferences_container')) {
        console.log('Delegating to handle_list_event');
        handle_list_event(event);
    } else {
        console.log('No handler matched - no container found');
    }
}

function update_connection_status(state) {
	const el = document.getElementById('connection_status');
	if (!el) return;

	const color = status_colors[state] || status_colors.connecting;
	const tooltip = status_tooltips[state] || status_tooltips.connecting;

	el.title = tooltip;

	if (status_indicator_mode === 'icon') {
		const icon = status_icons[state] || status_icons.connecting;
		el.className = icon;
		el.style.color = color;
	} else {
		el.style.backgroundColor = color;
	}
}

function send_ping() {
	if (!ws || !ws.ws || ws.ws.readyState !== WebSocket.OPEN) return;

	// Track if we had failures before this ping (indicates potential disconnect/reconnect)
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

	ws.request('active.conferences', 'ping', {})
		.then(() => {
			if (ping_timeout) { clearTimeout(ping_timeout); ping_timeout = null; }
			pong_failure_count = 0;
			last_pong_time = Date.now();
			update_connection_status('connected');

			// If we had failures before, refresh data to sync state after reconnection
			if (had_failures) {
				console.log('Pong received after failures - refreshing data');
				refresh_data();
			}
		})
		.catch(console.error);
}

function escapeHtml(text) {
	if (text === null || text === undefined) return '';
	return text.toString()
		.replace(/&/g, "&amp;")
		.replace(/</g, "&lt;")
		.replace(/>/g, "&gt;")
		.replace(/"/g, "&quot;")
		.replace(/'/g, "&#039;");
}

/* ===========================
 * Active Conferences List Logic
 * =========================== */

function load_conference_list() {
	if (!ws || !ws.ws || ws.ws.readyState !== WebSocket.OPEN) return;

	// domain_name global must be defined in PHP
	ws.request('active.conferences', 'in_progress', {domain_name: domain_name})
		.then(response => {
			render_conference_list(response.payload || response);
		})
		.catch(console.error);
}

function render_conference_list(conferences) {
	const container = document.getElementById('conferences_container');
    if (!container) return;

	if (!conferences) conferences = [];

	const filtered_conferences = conferences.filter(conf => {
		return conf.conference_name.includes('@' + domain_name);
	});

	let html = "<div class='card'>\n";
	html += "<table class='list'>\n";
	html += "<tr class='list-header'>\n";
	html += "	<th>" + text['label-name'] + "</th>\n";
	html += "	<th>" + text['label-extension'] + "</th>\n";
	html += "	<th>" + text['label-participant-pin'] + "</th>\n";
	html += "	<th class='center'>" + text['label-member-count'] + "</th>\n";
	if (permissions && permissions.conference_interactive_view) {
		html += "	<td class='action-button'>&nbsp;</td>\n";
	}
	html += "</tr>\n";

	if (filtered_conferences.length > 0) {
		filtered_conferences.forEach(row => {
			const full_name = row.conference_name;
			// Use conference_display_name from database if available, otherwise parse from conference_name
			let display_name = row.conference_display_name || '';
			if (!display_name) {
				display_name = full_name;
				if (full_name.includes('@')) {
					display_name = full_name.split('@')[0];
				}
				display_name = display_name.replace(/-/g, ' ').replace(/_/g, ' ');
			}

			// Get extension from conference_name (UUID for Conference Center, extension for simple Conference)
			let extension = full_name;
			if (full_name.includes('@')) {
				extension = full_name.split('@')[0];
			}

			const member_count = row.member_count;
			const list_row_url = 'active_conference_room.php?c=' + encodeURIComponent(extension);

			html += "<tr class='list-row' href='" + list_row_url + "' data-conference-name='" + escapeHtml(full_name) + "'>\n";
			html += "	<td>";
			if (permissions && permissions.conference_interactive_view) {
				html += "	<a href='" + list_row_url + "'>" + escapeHtml(display_name) + "</a>";
			} else {
				html += escapeHtml(display_name);
			}
			html += "	</td>\n";
			html += "	<td>" + escapeHtml(extension) + "</td>\n";
			html += "	<td></td>\n";
			html += "	<td class='center'>" + member_count + "</td>\n";

			if (permissions && permissions.conference_interactive_view && permissions.list_row_edit_button) {
				html += "	<td class='action-button'>";
				html += "		<a href='" + list_row_url + "' title='" + text['button-view'] + "'>";
				html += "			<span class='" + button_icon_view + "'></span>";
				html += "		</a>";
				html += "	</td>\n";
			}
			html += "</tr>\n";
		});
	}
	html += "</table>\n";
	html += "</div>\n";

	container.innerHTML = html;

	const rows = container.querySelectorAll('.list-row');
	rows.forEach(row => {
		row.addEventListener('click', function(e) {
			if (e.target.closest('a') || e.target.closest('.action-button')) return;
			const url = this.getAttribute('href');
			if (url) window.location = url;
		});
	});
}

function handle_list_event(event) {
	const payload = event.payload || event;
	const action = payload.action || event.action || event.event_name;
	const evt_domain_name = payload.domain_name || event.domain_name;
	const conference_name = payload.conference_name || '';

	// Filter by domain if provided
	if (evt_domain_name && evt_domain_name !== domain_name) {
		// Also check if conference_name contains our domain
		if (conference_name && !conference_name.includes('@' + domain_name)) {
			return;
		}
	}

	// Handle events incrementally without full refresh
	switch (action) {
		case 'add-member':
		case 'add_member':
			handle_list_add_member(payload);
			break;
		case 'del-member':
		case 'del_member':
			handle_list_del_member(payload);
			break;
		case 'conference-create':
		case 'conference_create':
			handle_list_conference_create(payload);
			break;
		case 'conference-destroy':
		case 'conference_destroy':
			handle_list_conference_destroy(payload);
			break;
		default:
			// Unknown event, ignore
			break;
	}

	// Update count display
	update_conference_list_count();
}

/**
 * Handle add_member event incrementally - update member count
 */
function handle_list_add_member(payload) {
	const conference_name = payload.conference_name || '';
	const conference_display_name = payload.conference_display_name || '';
	const member_count = payload.member_count;

	if (!conference_name) return;

	// Find existing row by data-conference-name attribute or href
	const rows = document.querySelectorAll('.list-row');
	let found = false;
	rows.forEach(row => {
		const row_conf_name = row.getAttribute('data-conference-name');
		if (row_conf_name === conference_name) {
			found = true;
			if (member_count !== undefined) {
				const cells = row.querySelectorAll('td');
				// Member count is in the 4th column (index 3)
				if (cells.length > 3) {
					cells[3].textContent = member_count;
				}
			}
		}
	});

	// If not found and it's for our domain, it might be a new conference - add row
	if (!found && conference_name.includes('@' + domain_name)) {
		add_conference_row(conference_name, member_count || 1, conference_display_name);
	}
}

/**
 * Handle del_member event incrementally - update member count
 */
function handle_list_del_member(payload) {
	const conference_name = payload.conference_name || '';
	const member_count = payload.member_count;

	if (!conference_name) return;

	// Find row by data-conference-name attribute
	const rows = document.querySelectorAll('.list-row');
	rows.forEach(row => {
		const row_conf_name = row.getAttribute('data-conference-name');
		if (row_conf_name === conference_name) {
			if (member_count !== undefined) {
				const cells = row.querySelectorAll('td');
				if (cells.length > 3) {
					cells[3].textContent = member_count;
				}
			}
			// If member count is 0, conference will be destroyed separately
		}
	});
}

/**
 * Handle conference_create event - add new row to list
 */
function handle_list_conference_create(payload) {
	const conference_name = payload.conference_name || '';
	const conference_display_name = payload.conference_display_name || '';

	if (!conference_name || !conference_name.includes('@' + domain_name)) return;

	// Check if row already exists by data-conference-name attribute
	const existing_rows = document.querySelectorAll('.list-row');
	let exists = false;
	existing_rows.forEach(row => {
		const row_conf_name = row.getAttribute('data-conference-name');
		if (row_conf_name === conference_name) {
			exists = true;
		}
	});

	if (!exists) {
		add_conference_row(conference_name, 0, conference_display_name);
	}
}

function update_conference_list_count() {
	const container = document.getElementById('conferences_container');
	if (!container) return;
	const count_el = document.getElementById('conference_count');
	if (!count_el) return;
	const rows = container.querySelectorAll('.list-row');
	count_el.textContent = rows.length;
}

/**
 * Handle conference_destroy event - remove row from list
 */
function handle_list_conference_destroy(payload) {
	const conference_name = payload.conference_name || '';

	if (!conference_name) return;

	// Find and remove row by data-conference-name attribute
	const rows = document.querySelectorAll('.list-row');
	rows.forEach(row => {
		const row_conf_name = row.getAttribute('data-conference-name');
		if (row_conf_name === conference_name) {
			row.remove();
		}
	});
}

/**
 * Add a new conference row to the list
 * @param {string} conference_name - The conference identifier (UUID@domain or extension@domain)
 * @param {number} member_count - Number of members in the conference
 * @param {string} conference_display_name - Human-readable name from database (optional)
 */
function add_conference_row(conference_name, member_count, conference_display_name) {
	const table = document.querySelector('.list');
	if (!table) return;

	const full_name = conference_name;

	// Use provided display name or parse from conference_name
	let display_name = conference_display_name || '';
	if (!display_name) {
		display_name = full_name;
		if (full_name.includes('@')) {
			display_name = full_name.split('@')[0];
		}
		display_name = display_name.replace(/-/g, ' ').replace(/_/g, ' ');
	}

	// Get extension/UUID for URL
	let extension = full_name;
	if (full_name.includes('@')) {
		extension = full_name.split('@')[0];
	}

	const list_row_url = 'active_conference_room.php?c=' + encodeURIComponent(extension);

	const row = document.createElement('tr');
	row.className = 'list-row';
	row.setAttribute('href', list_row_url);
	row.setAttribute('data-conference-name', full_name);

	let html = '';
	html += "<td>";
	if (permissions && permissions.conference_interactive_view) {
		html += "<a href='" + list_row_url + "'>" + escapeHtml(display_name) + "</a>";
	} else {
		html += escapeHtml(display_name);
	}
	html += "</td>";
	html += "<td>" + escapeHtml(extension) + "</td>";
	html += "<td></td>";
	html += "<td class='center'>" + (member_count || 0) + "</td>";

	if (permissions && permissions.conference_interactive_view && permissions.list_row_edit_button) {
		html += "<td class='action-button'>";
		html += "<a href='" + list_row_url + "' title='" + text['button-view'] + "'>";
		html += "<span class='" + button_icon_view + "'></span>";
		html += "</a>";
		html += "</td>";
	}

	row.innerHTML = html;
	row.addEventListener('click', function(e) {
		if (e.target.closest('a') || e.target.closest('.action-button')) return;
		const url = this.getAttribute('href');
		if (url) window.location = url;
	});

	table.appendChild(row);
}

/* ===========================
 * Active Conference Room Logic
 * =========================== */

function handle_room_event(event) {
	const payload = event.payload || event;
	const action = payload.action || event.action || event.event_name;
	const member_id = payload.member_id || payload['member-id'] || event.member_id || event['member-id'];
	const event_conference_name = payload.conference_name || '';

	console.log('handle_room_event - action:', action, 'event_conf:', event_conference_name, 'page_conf:', conference_name, 'page_id:', conference_id);

	// Only handle events for this conference room
	if (event_conference_name && typeof conference_name !== 'undefined') {
		// Check if event conference matches page conference (either direction)
		const matches = event_conference_name.includes(conference_id) ||
		                event_conference_name.includes(conference_name) ||
		                conference_name.includes(event_conference_name.split('@')[0]);
		if (!matches) {
			console.log('Event filtered out - not for this conference');
			return;
		}
	}

	console.log('Processing event:', action);

	// Handle events incrementally without full refresh
	switch (action) {
		case 'start-talking':
		case 'start_talking':
			if (member_id) handle_talking_event(member_id, true);
			break;
		case 'stop-talking':
		case 'stop_talking':
			if (member_id) handle_talking_event(member_id, false);
			break;
		case 'add-member':
		case 'add_member':
			handle_room_add_member(payload);
			break;
		case 'del-member':
		case 'del_member':
			handle_room_del_member(payload);
			break;
		case 'mute-member':
		case 'mute_member':
			handle_room_mute_member(member_id, true);
			break;
		case 'unmute-member':
		case 'unmute_member':
			handle_room_mute_member(member_id, false);
			break;
		case 'deaf-member':
		case 'deaf_member':
			handle_room_deaf_member(member_id, true);
			break;
		case 'undeaf-member':
		case 'undeaf_member':
			handle_room_deaf_member(member_id, false);
			break;
		case 'kick-member':
		case 'kick_member':
			handle_room_del_member(payload);
			break;
		case 'floor-change':
		case 'floor_change':
			handle_room_floor_change(payload);
			break;
		case 'lock':
			handle_room_lock(true);
			break;
		case 'unlock':
			handle_room_lock(false);
			break;
		case 'conference-destroy':
		case 'conference_destroy':
			// Conference ended - for valid conference rooms (UUID-based), keep the table structure
			// Just remove all member rows but keep the table headers
			handle_room_conference_destroy();
			break;
		default:
			// Unknown event, ignore
			break;
	}
}

/**
 * Handle add_member event - add new member row to the room table
 */
function handle_room_add_member(payload) {
	console.log('handle_room_add_member called with payload:', payload);
	const member = payload.member;
	const member_count = payload.member_count;

	console.log('Member data:', member, 'Member count:', member_count);

	// Check if the table exists - if not, this might be the first member
	// joining an empty conference, so we need to reload the full room view
	const container = document.getElementById('conference_container');
	const table = container ? container.querySelector('table.list') : null;
	console.log('Container found:', !!container, 'Table found:', !!table);

	if (!table) {
		// No table exists - reload conference room data to show the full view
		console.log('No table found - reloading room view');
		load_conference_room_data();
		return;
	}

	// Update member count display
	update_member_count(member_count);

	if (!member) {
		console.log('No member object in payload - cannot add row');
		return;
	}

	// Check if member already exists
	const existing_row = table.querySelector(`tr[data-member-id="${member.id}"]`);
	if (existing_row) {
		console.log('Member already exists in table');
		return;
	}

	console.log('Creating row for member:', member.id, member);
	const row = create_member_row(member);
	console.log('Row created:', row);

	// Append to tbody if exists, otherwise to table
	const tbody = table.querySelector('tbody') || table;
	tbody.appendChild(row);
	console.log('Row appended to table');

	// Initialize timer for new member
	if (typeof member_timers !== 'undefined' && member_timers) {
		member_timers[member.id] = {
			uuid: member.uuid,
			join_time: member.join_time || 0,
			last_talking: member.last_talking || 0,
			is_talking: false
		};
	}
}

/**
 * Handle del_member event - remove member row from the room table
 */
function handle_room_del_member(payload) {
	console.log('handle_room_del_member called with payload:', payload);
	const member_id = payload.member_id || payload['member-id'];
	const member_count = payload.member_count;

	console.log('Removing member_id:', member_id, 'new count:', member_count);

	// Update member count display
	if (member_count !== undefined) {
		update_member_count(member_count);
	}

	if (!member_id) {
		console.log('No member_id in payload');
		return;
	}

	// Remove member row
	const row = document.querySelector(`tr[data-member-id="${member_id}"]`);
	console.log('Found row to remove:', !!row);
	if (row) {
		row.remove();
		console.log('Row removed');
	}

	// Clean up timer
	if (typeof member_timers !== 'undefined' && member_timers && member_timers[member_id]) {
		delete member_timers[member_id];
	}
}

/**
 * Handle conference_destroy event - remove all members but keep the table structure
 * This keeps the empty conference room visible since the room UUID is still valid
 */
function handle_room_conference_destroy() {
	const container = document.getElementById('conference_container');
	if (!container) return;

	const table = container.querySelector('table.list');
	if (table) {
		// Remove all member rows (those with data-member-id attribute)
		const member_rows = table.querySelectorAll('tr[data-member-id]');
		member_rows.forEach(row => row.remove());

		// Update member count to 0
		update_member_count(0);

		// Clear all member timers
		if (typeof member_timers !== 'undefined' && member_timers) {
			for (const key in member_timers) {
				delete member_timers[key];
			}
		}
	}
	// If no table exists, the conference was never active - do nothing
	// The page already shows the empty conference state
}

/**
 * Handle mute/unmute member event
 */
function handle_room_mute_member(member_id, is_muted) {
	if (!member_id) return;

	const row = document.querySelector(`tr[data-member-id="${member_id}"]`);
	if (!row) return;

	// Find the capabilities cell and update microphone icon
	const cells = row.querySelectorAll('td');
	cells.forEach(cell => {
		const mic_icon = cell.querySelector('.fa-microphone, .fa-microphone-slash');
		if (mic_icon) {
			if (is_muted) {
				mic_icon.classList.remove('fa-microphone');
				mic_icon.classList.add('fa-microphone-slash');
				mic_icon.title = text['label-muted'] || 'Muted';
			} else {
				mic_icon.classList.remove('fa-microphone-slash');
				mic_icon.classList.add('fa-microphone');
				mic_icon.title = text['label-speak'] || 'Can speak';
			}
		}
	});

	// Update mute/unmute button
	const mute_btn = row.querySelector('[onclick*="mute"]');
	if (mute_btn && user_permissions.mute) {
		if (is_muted) {
			mute_btn.title = text['label-unmute'] || 'Unmute';
			mute_btn.setAttribute('onclick', mute_btn.getAttribute('onclick').replace("'mute'", "'unmute'"));
			const icon = mute_btn.querySelector('span');
			if (icon) {
				icon.classList.remove('fa-microphone-slash');
				icon.classList.add('fa-microphone');
			}
		} else {
			mute_btn.title = text['label-mute'] || 'Mute';
			mute_btn.setAttribute('onclick', mute_btn.getAttribute('onclick').replace("'unmute'", "'mute'"));
			const icon = mute_btn.querySelector('span');
			if (icon) {
				icon.classList.remove('fa-microphone');
				icon.classList.add('fa-microphone-slash');
			}
		}
	}
}

/**
 * Handle deaf/undeaf member event
 */
function handle_room_deaf_member(member_id, is_deaf) {
	if (!member_id) return;

	const row = document.querySelector(`tr[data-member-id="${member_id}"]`);
	if (!row) return;

	// Find the capabilities cell and update headphones icon
	const cells = row.querySelectorAll('td');
	cells.forEach(cell => {
		const hear_icon = cell.querySelector('.fa-headphones, .fa-deaf');
		if (hear_icon) {
			if (is_deaf) {
				hear_icon.classList.remove('fa-headphones');
				hear_icon.classList.add('fa-deaf');
				hear_icon.title = text['label-deaf'] || 'Deaf';
			} else {
				hear_icon.classList.remove('fa-deaf');
				hear_icon.classList.add('fa-headphones');
				hear_icon.title = text['label-hear'] || 'Can hear';
			}
		}
	});

	// Update deaf/undeaf button
	const deaf_btn = row.querySelector('[onclick*="deaf"]');
	if (deaf_btn && user_permissions.deaf) {
		if (is_deaf) {
			deaf_btn.title = text['label-undeaf'] || 'Undeaf';
			deaf_btn.setAttribute('onclick', deaf_btn.getAttribute('onclick').replace("'deaf'", "'undeaf'"));
			const icon = deaf_btn.querySelector('span');
			if (icon) {
				icon.classList.remove('fa-deaf');
				icon.classList.add('fa-headphones');
			}
		} else {
			deaf_btn.title = text['label-deaf'] || 'Deaf';
			deaf_btn.setAttribute('onclick', deaf_btn.getAttribute('onclick').replace("'undeaf'", "'deaf'"));
			const icon = deaf_btn.querySelector('span');
			if (icon) {
				icon.classList.remove('fa-headphones');
				icon.classList.add('fa-deaf');
			}
		}
	}
}

/**
 * Handle floor change event
 */
function handle_room_floor_change(payload) {
	const new_floor_member_id = payload.member_id || payload['member-id'];

	// Update all rows to remove floor indicator
	const rows = document.querySelectorAll('tr[data-member-id]');
	rows.forEach(row => {
		const floor_cells = row.querySelectorAll('td');
		// Floor is typically shown in one of the cells
		floor_cells.forEach(cell => {
			if (cell.textContent === text['label-yes'] || cell.textContent === 'Yes') {
				const member_id = row.getAttribute('data-member-id');
				if (member_id !== String(new_floor_member_id)) {
					cell.textContent = text['label-no'] || 'No';
				}
			}
		});
	});

	// Set floor for new member
	if (new_floor_member_id) {
		const new_floor_row = document.querySelector(`tr[data-member-id="${new_floor_member_id}"]`);
		if (new_floor_row) {
			// Floor column is typically the 6th column (index 5) in hide-sm-dn class
			const floor_cell = new_floor_row.querySelectorAll('td.hide-sm-dn')[1]; // Second hide-sm-dn cell
			if (floor_cell) {
				floor_cell.textContent = text['label-yes'] || 'Yes';
			}
		}
	}
}

/**
 * Handle lock/unlock event
 */
function handle_room_lock(is_locked) {
	// Find and update the lock/unlock button
	const lock_btns = document.querySelectorAll('[onclick*="lock"]');
	lock_btns.forEach(btn => {
		if (btn.getAttribute('onclick').includes("'lock'") || btn.getAttribute('onclick').includes("'unlock'")) {
			if (is_locked) {
				btn.setAttribute('onclick', "conference_action('unlock');");
				btn.title = text['label-unlock'] || 'Unlock';
				const icon = btn.querySelector('.fas');
				if (icon) {
					icon.classList.remove('fa-lock');
					icon.classList.add('fa-unlock');
				}
				const label = btn.querySelector('.hidden-xs');
				if (label) {
					label.textContent = text['label-unlock'] || 'Unlock';
				}
			} else {
				btn.setAttribute('onclick', "conference_action('lock');");
				btn.title = text['label-lock'] || 'Lock';
				const icon = btn.querySelector('.fas');
				if (icon) {
					icon.classList.remove('fa-unlock');
					icon.classList.add('fa-lock');
				}
				const label = btn.querySelector('.hidden-xs');
				if (label) {
					label.textContent = text['label-lock'] || 'Lock';
				}
			}
		}
	});
}

/**
 * Update member count display
 */
function update_member_count(count) {
	const container = document.getElementById('conference_container');
	if (!container) return;

	const strong_el = container.querySelector('strong');
	if (strong_el && strong_el.textContent.includes(text['label-members'])) {
		strong_el.textContent = text['label-members'] + ': ' + (count || 0);
	}
}

/**
 * Create a member row element for the table
 */
function create_member_row(member) {
	const id = member.id;
	const uuid = member.uuid;
	const name = decodeURIComponent(member.caller_id_name || '');
	const num = member.caller_id_number || '';

	const flags = member.flags || {};
	const can_hear = flags.can_hear !== false;
	const can_speak = flags.can_speak !== false;
	const talking = flags.talking === true;
	const has_video = flags.has_video === true;
	const has_floor = flags.has_floor === true;
	const is_moderator = flags.is_moderator === true;
	const hand_raised = false;

	const join_time = member.join_time || 0;
	const last_talking = member.last_talking || 0;

	const format_time_val = (val) => {
		const sec = parseInt(val, 10) || 0;
		const h = Math.floor(sec / 3600);
		const m = Math.floor((sec % 3600) / 60);
		const s = sec % 60;
		return [h,m,s].map(v => v < 10 ? "0" + v : v).join(":");
	};

	const join_formatted = format_time_val(join_time);
	const quiet_formatted = format_time_val(last_talking);

	let row_onclick = "";
	let row_title = "";
	let action_mute = "mute";

	if (user_permissions.mute) {
		action_mute = can_speak ? 'mute' : 'unmute';
		row_onclick = `onclick="conference_action('${action_mute}', '${id}', '${uuid}');"`;
		row_title = `title="${(text['message-click_to_' + action_mute] || action_mute)}"`;
	}

	const row = document.createElement('tr');
	row.className = 'list-row';
	row.setAttribute('data-member-id', id);
	row.setAttribute('data-uuid', uuid);
	row.setAttribute('data-join-time', join_time);
	row.setAttribute('data-last-talking', last_talking);

	let html = '';
	html += `<td ${row_onclick} ${row_title}>`;
	if (is_moderator) {
		html += `<i class='fas fa-user-tie fa-fw' title='${text['label-moderator']}'></i>`;
	} else {
		html += `<i class='fas fa-user fa-fw' title='${text['label-participant']}'></i>`;
	}
	html += "</td>";

	const talking_vis = talking ? 'visible' : 'hidden';
	const talking_icon = `<span class='talking-icon far fa-comment' style='font-size: 14px; margin: -2px 10px -2px 15px; visibility: ${talking_vis};' align='absmiddle' title='${text['label-talking']}'></span>`;
	html += `<td ${row_onclick} ${row_title} class='no-wrap'>${escapeHtml(name)}${talking_icon}</td>`;

	html += `<td ${row_onclick} ${row_title}>${escapeHtml(num)}</td>`;
	html += `<td ${row_onclick} ${row_title} class='hide-sm-dn join-time'>${join_formatted}</td>`;
	html += `<td ${row_onclick} ${row_title} class='hide-xs quiet-time'>${quiet_formatted}</td>`;
	html += `<td ${row_onclick} ${row_title} class='hide-sm-dn'>${has_floor ? text['label-yes'] : text['label-no']}</td>`;
	html += `<td ${row_onclick} ${row_title} class='hide-sm-dn'>${hand_raised ? text['label-yes'] : text['label-no']}</td>`;

	html += `<td ${row_onclick} ${row_title} class='center'>`;
	html += can_speak ? `<i class='fas fa-microphone fa-fw' title='${text['label-speak']}'></i>` : `<i class='fas fa-microphone-slash fa-fw' title='${text['label-speak']}'></i>`;
	html += can_hear ? `<i class='fas fa-headphones fa-fw' title='${text['label-hear']}' style='margin-left: 10px;'></i>` : `<i class='fas fa-deaf fa-fw' title='${text['label-hear']}' style='margin-left: 10px;'></i>`;
	if (user_permissions.video && has_video) {
		html += `<i class='fas fa-video fa-fw' title='${text['label-video']}' style='margin-left: 10px;'></i>`;
	}
	html += "</td>";

	if (user_permissions.energy) {
		html += "<td class='button center'>";
		html += `<button type='button' class='btn btn-default btn-xs' title='${text['label-energy']}' onclick="event.stopPropagation(); conference_action('energy', '${id}', '', 'up');"><span class='fas fa-plus'></span></button> `;
		html += `<button type='button' class='btn btn-default btn-xs' title='${text['label-energy']}' onclick="event.stopPropagation(); conference_action('energy', '${id}', '', 'down');"><span class='fas fa-minus'></span></button>`;
		html += "</td>";
	}

	if (user_permissions.volume) {
		html += "<td class='button center'>";
		html += `<button type='button' class='btn btn-default btn-xs' title='${text['label-volume']}' onclick="event.stopPropagation(); conference_action('volume_in', '${id}', '', 'down');"><span class='fas fa-volume-down'></span></button> `;
		html += `<button type='button' class='btn btn-default btn-xs' title='${text['label-volume']}' onclick="event.stopPropagation(); conference_action('volume_in', '${id}', '', 'up');"><span class='fas fa-volume-up'></span></button>`;
		html += "</td>";
	}

	if (user_permissions.gain) {
		html += "<td class='button center'>";
		html += `<button type='button' class='btn btn-default btn-xs' title='${text['label-gain']}' onclick="event.stopPropagation(); conference_action('volume_out', '${id}', '', 'down');"><span class='fas fa-sort-amount-down'></span></button> `;
		html += `<button type='button' class='btn btn-default btn-xs' title='${text['label-gain']}' onclick="event.stopPropagation(); conference_action('volume_out', '${id}', '', 'up');"><span class='fas fa-sort-amount-up'></span></button>`;
		html += "</td>";
	}

	html += "<td class='button right' style='padding-right: 0;'>";
	if (user_permissions.mute) {
		if (action_mute == 'mute') {
			html += `<button type='button' class='btn btn-default btn-xs' title='${text['label-mute']}' onclick="event.stopPropagation(); conference_action('mute', '${id}', '${uuid}');"><span class='fas fa-microphone-slash'></span></button> `;
		} else {
			html += `<button type='button' class='btn btn-default btn-xs' title='${text['label-unmute']}' onclick="event.stopPropagation(); conference_action('unmute', '${id}', '${uuid}');"><span class='fas fa-microphone'></span></button> `;
		}
	}

	if (user_permissions.deaf) {
		if (can_hear) {
			html += `<button type='button' class='btn btn-default btn-xs' title='${text['label-deaf']}' onclick="event.stopPropagation(); conference_action('deaf', '${id}');"><span class='fas fa-deaf'></span></button> `;
		} else {
			html += `<button type='button' class='btn btn-default btn-xs' title='${text['label-undeaf']}' onclick="event.stopPropagation(); conference_action('undeaf', '${id}');"><span class='fas fa-headphones'></span></button> `;
		}
	}

	if (user_permissions.kick) {
		html += `<button type='button' class='btn btn-default btn-xs' title='${text['label-kick']}' onclick="event.stopPropagation(); conference_action('kick', '${id}', '${uuid}');"><span class='fas fa-ban'></span></button>`;
	}
	html += "</td>";

	row.innerHTML = html;
	return row;
}

function load_conference_room_data() {
	console.log('load_conference_room_data called');
	if (!ws || !ws.ws || ws.ws.readyState !== WebSocket.OPEN) {
		console.log('WebSocket not ready');
		return;
	}

    // conference_id global must be defined in PHP
	console.log('Requesting in_progress for uuid:', conference_id, 'domain:', domain_name);
	ws.request('active.conferences', 'in_progress', {uuid: conference_id, domain_name: domain_name})
		.then(response => {
			console.log('in_progress response:', response);
			const conferences = response.payload || response;
			console.log('Conferences array:', conferences);
			let conf = null;
			if (Array.isArray(conferences)) {
				conf = conferences.find(c => c.conference_name.includes(conference_name) || c.conference_name.includes(conference_id));
				console.log('Found conference:', conf);
			} else if (conferences && (conferences.conference_name || conferences.members)) {
				conf = conferences;
				console.log('Single conference object:', conf);
			}
			render_conference_room(conf);
		})
		.catch(err => {
			console.error('load_conference_room_data error:', err);
		});
}

function render_conference_room(conference) {
	const container = document.getElementById('conference_container');
    if (!container) return;

	// Check if conference was not found in database
	if (!conference || conference.error === 'not_found' || conference.exists_in_database === false) {
		//container.innerHTML = "<div class='center'>" + (text['message-no_conference'] || 'Conference not found') + "</div>";
		conference = {
			members: [],
			member_count: 0,
			locked: false,
			recording: false,
			conference_display_name: '',
			conference_name: conference_name
		}
		return;
	}

	// Conference exists (either active or in database but empty)
	const members = conference.members || [];
	const member_count = conference.member_count || members.length || 0;
	const locked = conference.locked === true;
	const recording = conference.recording === true;
	const display_name = conference.conference_display_name || conference.conference_name || '';

	let mute_all = false;
	let found_unmuted = false;
	let found_non_moderator = false;
	members.forEach(member => {
		const flags = member.flags || {};
		const is_mod = flags.is_moderator;
		if (!is_mod) {
			found_non_moderator = true;
			const speaks = flags.can_speak !== false;
			if (speaks) found_unmuted = true;
		}
	});
	if (found_non_moderator && !found_unmuted) mute_all = true;

	let html = "";
	html += "<div style='float: right;'>\n";

	const rec_icon = recording ? "recording.png" : "not_recording.png";
	const rec_title = recording ? text['label-recording'] : text['label-not-recording'];
	html += "<img src='resources/images/" + rec_icon + "' style='width: 16px; height: 16px; border: none;' align='absmiddle' title='" + escapeHtml(rec_title) + "'>&nbsp;&nbsp;";

	if (user_permissions.lock) {
		if (locked) {
			html += "<button type='button' class='btn btn-default' onclick=\"conference_action('unlock');\" title='" + text['label-unlock'] + "'><span class='fas fa-unlock'></span> <span class='hidden-xs'>" + text['label-unlock'] + "</span></button> ";
		} else {
			html += "<button type='button' class='btn btn-default' onclick=\"conference_action('lock');\" title='" + text['label-lock'] + "'><span class='fas fa-lock'></span> <span class='hidden-xs'>" + text['label-lock'] + "</span></button> ";
		}
	}

	if (user_permissions.mute) {
		if (mute_all) {
			html += "<button type='button' class='btn btn-default' onclick=\"conference_action('unmute_all');\" title='" + text['label-unmute-all'] + "'><span class='fas fa-microphone'></span> <span class='hidden-xs'>" + text['label-unmute-all'] + "</span></button> ";
		} else {
			html += "<button type='button' class='btn btn-default' onclick=\"conference_action('mute_all');\" title='" + text['label-mute-all'] + "'><span class='fas fa-microphone-slash'></span> <span class='hidden-xs'>" + text['label-mute-all'] + "</span></button> ";
		}
	}

	if (user_permissions.kick) {
		html += "<button type='button' class='btn btn-default' onclick=\"conference_action('kick_all');\" title='" + text['label-end-conference'] + "'><span class='fas fa-stop'></span> <span class='hidden-xs'>" + text['label-end-conference'] + "</span></button>";
	}
	html += "</div>\n";

	html += "<strong>" + text['label-members'] + ": " + member_count + "</strong><br /><br />\n";

	html += "<div class='card'>\n";
	html += "<table class='list'>\n";
	html += "<tr class='list-header'>\n";
	html += "<th width='1px'>&nbsp;</th>\n";
	html += "<th class='no-wrap'>" + text['label-cid-name'] + "</th>\n";
	html += "<th class='no-wrap'>" + text['label-cid-num'] + "</th>\n";
	html += "<th class='hide-sm-dn'>" + text['label-joined'] + "</th>\n";
	html += "<th class='hide-xs'>" + text['label-quiet'] + "</th>\n";
	html += "<th class='hide-sm-dn'>" + text['label-floor'] + "</th>\n";
	html += "<th class='hide-sm-dn'>" + text['label-hand_raised'] + "</th>\n";
	html += "<th class='center'>" + text['label-capabilities'] + "</th>\n";
	if (user_permissions.energy) html += "<th class='center'>" + text['label-energy'] + "</th>\n";
	if (user_permissions.volume) html += "<th class='center'>" + text['label-volume'] + "</th>\n";
	if (user_permissions.gain) html += "<th class='center'>" + text['label-gain'] + "</th>\n";
	html += "<th>&nbsp;</th>\n";
	html += "</tr>\n";

	members.forEach(member => {
		const id = member.id;
		const uuid = member.uuid;
		const name = decodeURIComponent(member.caller_id_name || '');
		const num = member.caller_id_number || '';

		const flags = member.flags || {};
		const can_hear = flags.can_hear !== false;
		const can_speak = flags.can_speak !== false;
		const talking = flags.talking === true;
		const has_video = flags.has_video === true;
		const has_floor = flags.has_floor === true;
		const is_moderator = flags.is_moderator === true;
		const hand_raised = false;

		const join_time = member.join_time || 0;
		const last_talking = member.last_talking || 0;

		const format_time_val = (val) => {
			const sec = parseInt(val, 10) || 0;
			const h = Math.floor(sec / 3600);
			const m = Math.floor((sec % 3600) / 60);
			const s = sec % 60;
			return [h,m,s].map(v => v < 10 ? "0" + v : v).join(":");
		};

		const join_formatted = format_time_val(join_time);
		const quiet_formatted = format_time_val(last_talking);

		let row_onclick = "";
		let row_title = "";
		let action_mute = "mute";

		if (user_permissions.mute) {
			action_mute = can_speak ? 'mute' : 'unmute';
			row_onclick = `onclick="conference_action('${action_mute}', '${id}', '${uuid}');"`;
			row_title = `title="${(text['message-click_to_' + action_mute] || action_mute)}"`;
		}

		html += `<tr class='list-row' data-member-id='${id}' data-uuid='${uuid}' data-join-time='${join_time}' data-last-talking='${last_talking}'>\n`;

		html += `<td ${row_onclick} ${row_title}>`;
		if (is_moderator) {
			html += `<i class='fas fa-user-tie fa-fw' title='${text['label-moderator']}'></i>`;
		} else {
			html += `<i class='fas fa-user fa-fw' title='${text['label-participant']}'></i>`;
		}
		html += "</td>\n";

		const talking_vis = talking ? 'visible' : 'hidden';
		const talking_icon = `<span class='talking-icon far fa-comment' style='font-size: 14px; margin: -2px 10px -2px 15px; visibility: ${talking_vis};' align='absmiddle' title='${text['label-talking']}'></span>`;
		html += `<td ${row_onclick} ${row_title} class='no-wrap'>${escapeHtml(name)}${talking_icon}</td>\n`;

		html += `<td ${row_onclick} ${row_title}>${escapeHtml(num)}</td>\n`;
		html += `<td ${row_onclick} ${row_title} class='hide-sm-dn join-time'>${join_formatted}</td>\n`;
		html += `<td ${row_onclick} ${row_title} class='hide-xs quiet-time'>${quiet_formatted}</td>\n`;
		html += `<td ${row_onclick} ${row_title} class='hide-sm-dn'>${has_floor ? text['label-yes'] : text['label-no']}</td>\n`;
		html += `<td ${row_onclick} ${row_title} class='hide-sm-dn'>${hand_raised ? text['label-yes'] : text['label-no']}</td>\n`;

		html += `<td ${row_onclick} ${row_title} class='center'>`;
		html += can_speak ? `<i class='fas fa-microphone fa-fw' title='${text['label-speak']}'></i>` : `<i class='fas fa-microphone-slash fa-fw' title='${text['label-speak']}'></i>`;
		html += can_hear ? `<i class='fas fa-headphones fa-fw' title='${text['label-hear']}' style='margin-left: 10px;'></i>` : `<i class='fas fa-deaf fa-fw' title='${text['label-hear']}' style='margin-left: 10px;'></i>`;
		if (user_permissions.video && has_video) {
			html += `<i class='fas fa-video fa-fw' title='${text['label-video']}' style='margin-left: 10px;'></i>`;
		}
		html += "</td>\n";

		if (user_permissions.energy) {
			html += "<td class='button center'>\n";
			html += `<button type='button' class='btn btn-default btn-xs' title='${text['label-energy']}' onclick="event.stopPropagation(); conference_action('energy', '${id}', '', 'up');"><span class='fas fa-plus'></span></button> `;
			html += `<button type='button' class='btn btn-default btn-xs' title='${text['label-energy']}' onclick="event.stopPropagation(); conference_action('energy', '${id}', '', 'down');"><span class='fas fa-minus'></span></button>`;
			html += "</td>\n";
		}

		if (user_permissions.volume) {
			html += "<td class='button center'>\n";
			html += `<button type='button' class='btn btn-default btn-xs' title='${text['label-volume']}' onclick="event.stopPropagation(); conference_action('volume_in', '${id}', '', 'down');"><span class='fas fa-volume-down'></span></button> `;
			html += `<button type='button' class='btn btn-default btn-xs' title='${text['label-volume']}' onclick="event.stopPropagation(); conference_action('volume_in', '${id}', '', 'up');"><span class='fas fa-volume-up'></span></button>`;
			html += "</td>\n";
		}

		if (user_permissions.gain) {
			html += "<td class='button center'>\n";
			html += `<button type='button' class='btn btn-default btn-xs' title='${text['label-gain']}' onclick="event.stopPropagation(); conference_action('volume_out', '${id}', '', 'down');"><span class='fas fa-sort-amount-down'></span></button> `;
			html += `<button type='button' class='btn btn-default btn-xs' title='${text['label-gain']}' onclick="event.stopPropagation(); conference_action('volume_out', '${id}', '', 'up');"><span class='fas fa-sort-amount-up'></span></button>`;
			html += "</td>\n";
		}

		html += "<td class='button right' style='padding-right: 0;'>\n";
		if (user_permissions.mute) {
			if (action_mute == 'mute') {
				html += `<button type='button' class='btn btn-default btn-xs' title='${text['label-mute']}' onclick="event.stopPropagation(); conference_action('mute', '${id}', '${uuid}');"><span class='fas fa-microphone-slash'></span></button> `;
			} else {
				html += `<button type='button' class='btn btn-default btn-xs' title='${text['label-unmute']}' onclick="event.stopPropagation(); conference_action('unmute', '${id}', '${uuid}');"><span class='fas fa-microphone'></span></button> `;
			}
		}

		if (user_permissions.deaf) {
			if (can_hear) {
				html += `<button type='button' class='btn btn-default btn-xs' title='${text['label-deaf']}' onclick="event.stopPropagation(); conference_action('deaf', '${id}');"><span class='fas fa-deaf'></span></button> `;
			} else {
				html += `<button type='button' class='btn btn-default btn-xs' title='${text['label-undeaf']}' onclick="event.stopPropagation(); conference_action('undeaf', '${id}');"><span class='fas fa-headphones'></span></button> `;
			}
		}

		if (user_permissions.kick) {
			html += `<button type='button' class='btn btn-default btn-xs' title='${text['label-kick']}' onclick="event.stopPropagation(); conference_action('kick', '${id}', '${uuid}');"><span class='fas fa-ban'></span></button>`;
		}
		html += "</td>\n";

		html += "</tr>\n";
	});
	html += "</table>\n";
	html += "</div>\n";

	container.innerHTML = html;

	initialize_timers();
}

function initialize_timers() {
	member_timers = {};
	const rows = document.querySelectorAll('tr[data-member-id]');
	rows.forEach(row => {
		const member_id = row.getAttribute('data-member-id');
		const uuid = row.getAttribute('data-uuid');
		const join_time = parseInt(row.getAttribute('data-join-time'), 10) || 0;
		const last_talking = parseInt(row.getAttribute('data-last-talking'), 10) || 0;

		member_timers[member_id] = {
			uuid: uuid,
			join_time: join_time,
			last_talking: last_talking,
			is_talking: false
		};
	});

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
			timer.join_time++;
			if (!timer.is_talking) {
				timer.last_talking++;
			}

			const join_time_cell = row.querySelector('.join-time');
			const quiet_time_cell = row.querySelector('.quiet-time');

			if (join_time_cell) join_time_cell.textContent = format_time(timer.join_time);
			if (quiet_time_cell) quiet_time_cell.textContent = format_time(timer.last_talking);
		}
	});
}

function format_time(seconds) {
	if (!Number.isFinite(seconds) || seconds < 0) seconds = 0;
	const hrs = Math.floor(seconds / 3600);
	const mins = Math.floor((seconds % 3600) / 60);
	const secs = Math.floor(seconds % 60);
	return String(hrs).padStart(2, '0') + ':' + String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
}

function handle_talking_event(member_id, is_talking) {
	const timer = member_timers[member_id];
	if (timer) {
		timer.is_talking = is_talking;
		if (is_talking) timer.last_talking = 0;
	}

	const row = document.querySelector(`tr[data-member-id="${member_id}"]`);
	if (row) {
		const talking_icon = row.querySelector('.talking-icon');
		if (talking_icon) {
			talking_icon.style.visibility = is_talking ? 'visible' : 'hidden';
		}
	}
}

function send_action(action, options = {}) {
	if (!ws || !ws.ws || ws.ws.readyState !== WebSocket.OPEN) return Promise.reject('Not connected');

	const payload = {
		action: action,
		conference_name: conference_name,
		domain_name: domain_name,
		...options
	};

	console.log('Sending action:', action, payload);

	return ws.request('active.conferences', 'action', payload)
		.then(response => {
			const result = response.payload || response;
			if (!result.success) {
				console.error('Action failed:', result.message);
			}
			// No refresh needed - websocket events will update the UI incrementally
			return result;
		})
		.catch(err => {
			console.error('Action error:', err);
			throw err;
		});
}

function conference_action(action, member_id, uuid, direction) {
	return send_action(action, {
		member_id: member_id || '',
		uuid: uuid || '',
		direction: direction || ''
	});
}
