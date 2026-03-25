class ws_client {
	static REG_TRACE_PREFIX = '[OP_REG_TRACE]';

	_is_debug_enabled() {
		if (typeof window !== 'undefined' && window.OP_DEBUG === true) return true;
		try {
			if (typeof localStorage !== 'undefined' && localStorage.getItem('op_debug') === '1') return true;
		} catch (err) {
			// Ignore storage access errors (private mode / blocked storage).
		}
		return false;
	}

	_is_reg_trace_enabled() {
		if (typeof window !== 'undefined' && window.OP_REG_TRACE_ENABLED === true) return true;
		try {
			if (typeof localStorage !== 'undefined' && localStorage.getItem('op_reg_trace') === '1') return true;
		} catch (err) {
			// Ignore storage access errors (private mode / blocked storage).
		}
		return false;
	}

	_debug(label, data) {
		if (!this._is_debug_enabled()) return;
		if (typeof data === 'undefined') {
			console.debug(`[${this._now()}] ${label}`);
		} else {
			console.debug(`[${this._now()}] ${label}`, data);
		}
	}

	_reg_trace(label, data) {
		if (!this._is_reg_trace_enabled()) return;
		if (typeof data === 'undefined') {
			console.debug(`[${this._now()}] ${ws_client.REG_TRACE_PREFIX} ${label}`);
		} else {
			console.debug(`[${this._now()}] ${ws_client.REG_TRACE_PREFIX} ${label}`, data);
		}
	}

	_now() {
		return new Date().toISOString().replace('T', ' ').replace('Z', '');
	}

	constructor(url, token) {
		this.ws = new WebSocket(url);
		this.ws.addEventListener('message', this._on_message.bind(this));
		this._next_id = 1;
		this._pending = new Map();
		this._event_handlers = new Map();
		// The token is submitted on every request
		this.token = token;
	}

	authenticate() {
		//
		// Authentication is with websockets not the service, so we need to send a special
		//   request for authentication and specify the service that will be handling our
		//   future messages. This means the service is authentication and the topic is the
		//   service that will handle our future messages. This is a special case because we
		//   must authenticate with websockets, not the service. The service is only used to
		//   handle future messages.
		//
		// service = 'authentication'
		// topic = operator_panel_service::get_service_name()
		// payload = token
		//
		this.request('authentication', 'active.operator.panel', { token: this.token });
	}

	// internal message handler called when event occurs on the socket
	_on_message(ev) {
		let message;
		let switch_event;
		try {
			this._debug('[WS][raw] message received', ev.data);
			message = JSON.parse(ev.data);
			if (message && message.topic === 'registration_change') {
				this._reg_trace('[WS][raw] registration_change', message);
			}
			// check for authentication request
			if (message.status_code === 407) {
				console.log('Authentication Required');
				this.authenticate();
				return;
			}
			switch_event = message.payload;
			if (message.topic === 'authenticated') {
				console.log('Authenticated');
				this._dispatch_event('active.operator.panel', {event_name: 'authenticated'});
				return; // Don't process further after authenticated
			}
		} catch (err) {
			console.error('Error parsing JSON data:', err);
			return;
		}

		// Pull out the request_id first
		const rid = message.request_id ?? null;
		this._debug('[WS][route]', {
			request_id: rid,
			service_name: message.service_name || message.service || '',
			topic: message.topic || '',
			has_pending: rid ? this._pending.has(rid) : false,
		});

		// If this is the response to a pending request
		if (rid && this._pending.has(rid)) {
			const {
				service_name = '',
				topic = '',
				status_string: status = 'ok',
				status_code: code = 200,
				payload = {}
			} = message;

			const {resolve, reject} = this._pending.get(rid);
			this._pending.delete(rid);

			if (status === 'ok' && code >= 200 && code < 300) {
				this._debug('[WS][pending-response]', {service_name, topic, code});
				resolve({service_name, topic, payload, code, message});
				// Also dispatch as an event so handlers get notified
				const event_data = (typeof switch_event === 'object' && switch_event !== null)
					? { ...switch_event, event_name: switch_event.event_name || topic }
					: { event_name: topic, data: switch_event };
				this._dispatch_event(service_name, event_data);
			} else {
				this._debug('[WS][pending-error]', {service_name, topic, code, status});
				const err = new Error(message || `Error ${code}`);
				err.code = code;
				reject(err);
			}

			return;
		}

		// Otherwise it's a server-pushed event
		this._debug('[WS][push]', {
			service_name: message.service_name || message.service || '',
			topic: message.topic || '',
			has_payload_object: (typeof switch_event === 'object' && switch_event !== null),
		});
		if (message.topic === 'registration_change') {
			this._reg_trace('[WS][push] registration_change', {
				service_name: message.service_name || message.service || '',
				topic: message.topic || '',
				has_payload_object: (typeof switch_event === 'object' && switch_event !== null),
			});
		}

		// Use service_name, or fall back to service, or default to 'active.operator.panel'
		const service = message.service_name || message.service || 'active.operator.panel';

		// Ensure event has event_name set from topic if not in payload
		const event_data = (typeof switch_event === 'object' && switch_event !== null)
			? { ...switch_event, event_name: switch_event.event_name || message.topic, topic: message.topic }
			: { event_name: message.topic, topic: message.topic, data: switch_event };

		this._debug('[WS][dispatch]', {
			service,
			topic: event_data.topic || event_data.event_name || '',
		});
		this._dispatch_event(service, event_data);
	}

	// Send a request to the websocket server using JSON string
	request(service, topic = null, payload = {}) {
		const request_id = String(this._next_id++);
		const env = {
			request_id: request_id,
			service,
			...(topic !== null ? {topic} : {}),
			token: this.token,
			payload: payload
		};
		const raw = JSON.stringify(env);
		this.ws.send(raw);
		return new Promise((resolve, reject) => {
			this._pending.set(request_id, {resolve, reject});
		});
	}

	subscribe(topic) {
		return this.request('active.operator.panel', topic);
	}

	unsubscribe(topic) {
		return this.request('active.operator.panel', topic);
	}

	// register a callback for server-pushes
	on_event(topic, handler) {
		console.log('registering event listener for ' + topic);
		if (!this._event_handlers.has(topic)) {
			this._event_handlers.set(topic, []);
		}
		this._event_handlers.get(topic).push(handler);
	}

	/**
	 * Dispatch a server-push event envelope to all registered handlers.
	 */
	_dispatch_event(service, env) {
		this._debug('[WS][_dispatch_event] called', { service });

		let event = (typeof env === 'string') ? JSON.parse(env) : env;

		this._debug('[WS][_dispatch_event] handlers', Array.from(this._event_handlers.keys()));

		if (service === 'active.operator.panel') {
			// Prefer the envelope topic (always lowercase, set by the PHP service)
			// and fall back to event_name lowercased (raw FreeSWITCH names are UPPER_CASE).
			const topic = (event.topic || event.event_name || '').toLowerCase();
			this._debug('[WS][_dispatch_event] topic', topic);

			const handlers          = this._event_handlers.get(topic) || [];
			const wildcard_handlers = this._event_handlers.get('*')   || [];

			this._debug('[WS][_dispatch_event] handler counts', { topic, handlers: handlers.length, wildcard: wildcard_handlers.length });

			for (const fn of handlers) {
				try { fn(event); } catch (err) { console.error(`Error in handler for "${topic}":`, err); }
			}
			for (const fn of wildcard_handlers) {
				try { fn(event); } catch (err) { console.error('Error in wildcard handler:', err); }
			}
		} else {
			const handlers = this._event_handlers.get(service) || [];
			for (const fn of handlers) {
				try { fn(event.data, event); } catch (err) { console.error(`Error in handler for "${service}":`, err); }
			}
		}
	}
}
