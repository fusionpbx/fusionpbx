class ws_client {
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
		// topic = active_conferences_service::get_service_name()
		// payload = token
		//
		this.request('authentication', 'active.conferences', { token: this.token });
	}

	// internal message handler called when event occurs on the socket
	_on_message(ev) {
		let message;
		let switch_event;
		try {
			console.log('Raw message received:', ev.data);
			message = JSON.parse(ev.data);
			// check for authentication request
			if (message.status_code === 407) {
				console.log('Authentication Required');
				this.authenticate();
				return;
			}
			switch_event = message.payload;
			if (message.topic === 'authenticated') {
				console.log('Authenticated');
				this._dispatch_event('active.conferences', {event_name: 'authenticated'});
				return; // Don't process further after authenticated
			}
			//console.log('envelope received: ',env);
		} catch (err) {
			console.error('Error parsing JSON data:', err);
			//console.error('Invalid JSON:', ev.data);
			return;
		}

		// Pull out the request_id first
		const rid = message.request_id ?? null;

		// If this is the response to a pending request
		if (rid && this._pending.has(rid)) {
			// Destructure with defaults in case they're missing
			const {
				service_name = '',
				topic = '',
				status = 'ok',
				code = 200,
				payload = {}
			} = message;

			const {resolve, reject} = this._pending.get(rid);
			this._pending.delete(rid);

			if (status === 'ok' && code >= 200 && code < 300) {
				console.log('Response received:', {service_name, topic, payload, code});
				resolve({service_name, topic, payload, code, message});
				// Also dispatch as an event so handlers get notified
				// Use topic from message as event_name if payload doesn't have one
				const event_data = (typeof switch_event === 'object' && switch_event !== null)
					? { ...switch_event, event_name: switch_event.event_name || topic }
					: { event_name: topic, data: switch_event };
				this._dispatch_event(service_name, event_data);
			} else {
				const err = new Error(message || `Error ${code}`);
				err.code = code;
				reject(err);
			}

			return;
		}

		// Otherwise it's a server‑pushed event…
		// e.g. env.service === 'event' or env.topic is your event name
		console.log('Server-pushed event - service_name:', message.service_name, 'service:', message.service, 'topic:', message.topic, 'payload:', switch_event);

		// Use service_name, or fall back to service, or default to 'active.conferences'
		const service = message.service_name || message.service || 'active.conferences';

		// Ensure event has event_name set from topic if not in payload
		// IMPORTANT: Also preserve the topic as the action since that's what the PHP service sends
		const event_data = (typeof switch_event === 'object' && switch_event !== null)
			? { ...switch_event, event_name: switch_event.event_name || message.topic, topic: message.topic }
			: { event_name: message.topic, topic: message.topic, data: switch_event };

		console.log('Dispatching event to handlers:', event_data);
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
			// TODO: get timeout working to reject if no response in X ms
		});
	}

	subscribe(topic) {
		return this.request('active.conferences', topic);
	}

	unsubscribe(topic) {
		return this.request('active.conferences', topic);
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
	 * Dispatch a server‑push event envelope to all registered handlers.
	 * @param {object} env
	 */
	_dispatch_event(service, env) {
		console.log('_dispatch_event called with service:', service, 'env:', env);

		// if service==='event', topic carries the real event name:
		  let event = (typeof env === 'string')
			? JSON.parse(env)
			: env;

		console.log('Parsed event:', event);
		console.log('Registered handlers:', Array.from(this._event_handlers.keys()));

		// dispatch event handlers
		if (service === 'active.conferences') {
			const topic = event.event_name;
			console.log('Looking for handlers for topic:', topic);

			// Get specific handlers for this topic
			const handlers = this._event_handlers.get(topic) || [];
			// Always get wildcard handlers too
			const wildcard_handlers = this._event_handlers.get('*') || [];

			console.log('Found handlers:', handlers.length, 'wildcard:', wildcard_handlers.length);

			// Call specific handlers
			for (const fn of handlers) {
				try {
					fn(event);
				} catch (err) {
					console.error(`Error in handler for "${topic}":`, err);
				}
			}
			// Always call wildcard handlers for all events
			for (const fn of wildcard_handlers) {
				try {
					fn(event);
				} catch (err) {
					console.error(`Error in wildcard handler:`, err);
				}
			}
		} else {
			const handlers = this._event_handlers.get(service) || [];
			for (const fn of handlers) {
				try {
					if (fn === '*') {
						event(event.data, event);
					} else {
					    fn(event.data, event);
					}
				} catch (err) {
					console.error(`Error in handler for "${service}":`, err);
				}
			}
		}
	}

}
