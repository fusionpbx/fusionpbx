class ws_client {
	constructor(url, token) {
		this.ws = new WebSocket(url);
		this.ws.addEventListener('message', this._onMessage.bind(this));
		this._nextId = 1;
		this._pending = new Map();
		this._eventHandlers = new Map();
		// The token is submitted on every request
		this.token = token;
	}

	// internal message handler called when event occurs on the socket
	_onMessage(ev) {
		let message;
		let switch_event;
		try {
			//console.log(ev.data);
			message = JSON.parse(ev.data);
			// check for authentication request
			if (message.status_code === 407) {
				console.log('Authentication Required');
				return;
			}
			switch_event = message.payload;
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
				service,
				topic = '',
				status = 'ok',
				code = 200,
				payload = {}
			} = message;

			const {resolve, reject} = this._pending.get(rid);
			this._pending.delete(rid);

			if (status === 'ok' && code >= 200 && code < 300) {
				resolve({service, topic, payload, code, message});
			} else {
				const err = new Error(message || `Error ${code}`);
				err.code = code;
				reject(err);
			}

			return;
		}

		// Otherwise it's a server‑pushed event…
		// e.g. env.service === 'event' or env.topic is your event name
		this._dispatchEvent(message.service_name, switch_event);
	}

	// Send a request to the websocket server using JSON string
	request(service, topic = null, payload = {}) {
		const request_id = String(this._nextId++);
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
		return this.request('active.calls', topic);
	}

	unsubscribe(topic) {
		return this.request('active.calls', topic);
	}

	// register a callback for server-pushes
	onEvent(topic, handler) {
		console.log('registering event listener for ' + topic);
		if (!this._eventHandlers.has(topic)) {
			this._eventHandlers.set(topic, []);
		}
		this._eventHandlers.get(topic).push(handler);
	}
	/**
	 * Dispatch a server‑push event envelope to all registered handlers.
	 * @param {object} env
	 */
	_dispatchEvent(service, env) {
		// if service==='event', topic carries the real event name:
		  let event = (typeof env === 'string')
			? JSON.parse(env)
			: env;

		// dispatch event handlers
		if (service === 'active.calls') {
			const topic = event.event_name;

			let handlers = this._eventHandlers.get(topic) || [];
			if (handlers.length === 0) {
				handlers = this._eventHandlers.get('*') || [];
			}
			for (const fn of handlers) {
				try {
					fn(event);
				} catch (err) {
					console.error(`Error in handler for "${topic}":`, err);
				}
			}
		} else {
			const handlers = this._eventHandlers.get(service) || [];
			for (const fn of handlers) {
				try {
					fn(event.data, event);
				} catch (err) {
					console.error(`Error in handler for "${service}":`, err);
				}
			}
		}
	}

}
