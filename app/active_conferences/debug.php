<?php

require_once dirname(__DIR__, 2) . '/resources/require.php';

// Create the token
$token = (new token)->create($_SERVER['PHP_SELF']);

// Save the token
subscriber::save_token($token, [active_conferences_service::get_service_name()]);

//break the caching
$version = md5(file_get_contents(__DIR__ . '/resources/javascript/websocket_client.js'));

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebSocket Event Logger</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .websocket-container {
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .websocket-header {
            background-color: #f5f5f5;
            padding: 10px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ddd;
        }

        .websocket-header:hover {
            background-color: #e9e9e9;
        }

        .toggle-icon {
            transition: transform 0.2s;
        }

        .collapsed .toggle-icon {
            transform: rotate(-90deg);
        }

        .websocket-content {
            padding: 15px;
            background-color: #fff;
        }

        .event-log {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #eee;
            padding: 10px;
            background-color: #f9f9f9;
            font-family: monospace;
            font-size: 12px;
        }

        .event-item {
            margin-bottom: 8px;
            padding: 5px;
            border-radius: 3px;
            background-color: #fff;
            border-left: 3px solid #007bff;
        }

        .event-item:hover {
            background-color: #f0f8ff;
        }

        .event-timestamp {
            color: #666;
            font-size: 11px;
        }

        .event-type {
            font-weight: bold;
            color: #007bff;
        }

        .event-data {
            margin-top: 5px;
            word-break: break-all;
        }

        .connection-status {
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 3px;
        }

        .connected {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .disconnected {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .controls {
            margin-bottom: 20px;
        }

        button {
            padding: 8px 15px;
            margin-right: 10px;
            cursor: pointer;
            border: none;
            border-radius: 3px;
        }

        .connect-btn {
            background-color: #28a745;
            color: white;
        }

        .disconnect-btn {
            background-color: #dc3545;
            color: white;
        }

        .clear-btn {
            background-color: #6c757d;
            color: white;
        }
    </style>
    <script>
        const token = {
            name: '<?= $token['name'] ?>',
            hash: '<?= $token['hash'] ?>'
        }
    </script>
    <script src="resources/javascript/websocket_client.js?v=<?= $version ?>"></script>
</head>

<body>
    <h1>WebSocket Event Logger</h1>

    <div class="controls">
        <button class="connect-btn" onclick="connect_websocket()">Connect</button>
        <button class="disconnect-btn" onclick="disconnect_websocket()">Disconnect</button>
        <button class="clear-btn" onclick="clear_log()">Clear Log</button>
    </div>

    <div id="connection-status" class="connection-status disconnected">
        Disconnected
    </div>

    <div class="websocket-container" id="websocket-container">
        <div class="websocket-header" onclick="toggle_collapse()">
            <span>WebSocket Events <span id="event-count">(0 events)</span></span>
            <span class="toggle-icon">▼</span>
        </div>
        <div class="websocket-content" id="websocket-content">
            <div class="event-log" id="event-log">
                <div id="placeholder">No events received yet. Connect to WebSocket to start logging.</div>
            </div>
        </div>
    </div>

    <script>
        let ws = null;
        let event_count = 0;
        let is_collapsed = false;
        let reconnect_attempts = 0;
        const max_reconnect_delay = 30000; // 30 seconds
        const base_reconnect_delay = 1000; // 1 second

        function connect_websocket() {
            // Replace with your WebSocket server URL
            const ws_url = `wss://${window.location.hostname}/websockets/`; // Update this URL

            try {
                ws = new ws_client(ws_url, token);

                ws.on_event('authenticated', authenticated);

                // CONNECTED
                ws.ws.addEventListener("open", () => {
                    console.log('WebSocket connection opened');
                    reconnect_attempts = 0;
                });

                // DISCONNECTED - handle reconnection
                ws.ws.addEventListener("close", (event) => {
                    console.warn('WebSocket disconnected:', event.code, event.reason);
                    update_connection_status('Disconnected - reconnecting...', 'disconnected');
                    
                    // Exponential backoff for reconnection
                    reconnect_attempts++;
                    const delay = Math.min(base_reconnect_delay * Math.pow(2, reconnect_attempts - 1), max_reconnect_delay);
                    console.log(`Reconnecting in ${delay}ms (attempt ${reconnect_attempts})`);
                    
                    setTimeout(() => {
                        connect_websocket();
                    }, delay);
                });

                // ERROR
                ws.ws.addEventListener("error", (error) => {
                    console.error('WebSocket error:', error);
                });

            } catch (error) {
                console.error('Failed to connect to WebSocket:', error);
                update_connection_status('Connection failed: ' + error.message, 'disconnected');
            }
        }

        function authenticated(message) {
            console.log('WebSocket connected');
            update_connection_status('Connected', 'connected');
            
            // Log the authenticated event to the UI
            log_event(JSON.stringify({event_name: 'authenticated', message: message}));
            
            // Register wildcard handler to catch ALL events
            ws.on_event('*', on_any_event);
            
            // Subscribe to all events using wildcard
            ws.subscribe('*');
        }

        function on_any_event(event) {
            console.log('Event received:', event.event_name, event);
            log_event(JSON.stringify(event));
        }

        function disconnect_websocket() {
            if (ws && ws.readyState === WebSocket.OPEN) {
                ws.close();
            }
        }

        function log_event(data) {
            event_count++;
            update_event_count();

            const event_log = document.getElementById('event-log');

            // Remove placeholder if it exists
            const placeholder = document.getElementById('placeholder');
            if (placeholder) {
                placeholder.remove();
            }

            let event_data;
            try {
                event_data = JSON.parse(data);
            } catch (e) {
                event_data = data;
            }

            // Check for payload object (events wrapped in websocket_message)
            const payload = event_data.payload || event_data;
            
            // Get UUID from unique_id field (with fallbacks)
            const event_uuid = payload.unique_id || event_data.unique_id || payload.uuid || event_data.uuid || '';
            
            // Get action from Action field, falling back to event_name
            const event_action = payload.action || event_data.action || event_data.event_name || payload.event_name || 'Unknown Event';

            const event_item = document.createElement('div');
            event_item.className = 'event-item collapsible';

            const timestamp = new Date().toLocaleString();
            
            // Format display: show action, optionally with UUID if present
            const display_text = event_uuid ? `${event_action} (${event_uuid})` : event_action;

            event_item.innerHTML = `
                <div class="event-header" onclick="toggle_event(this)">
                    <span class="event-timestamp">${timestamp}</span>
                    <span class="event-type">${display_text}</span>
                    <span class="toggle-icon">▼</span>
                </div>
                <div class="event-data" style="display: none;">
                    ${format_data(event_data)}
                </div>
            `;

            // Insert at the top of the list (newest first)
            event_log.insertBefore(event_item, event_log.firstChild);
        }

        function toggle_event(element) {
            const event_data = element.nextElementSibling;
            const icon = element.querySelector('.toggle-icon');

            if (event_data.style.display === 'none' || event_data.style.display === '') {
                event_data.style.display = 'block';
                icon.textContent = '▲';
            } else {
                event_data.style.display = 'none';
                icon.textContent = '▼';
            }
        }

        function format_data(data) {
            if (typeof data === 'object') {
                return JSON.stringify(data, null, 2);
            }
            return String(data);
        }

        function update_connection_status(message, status_class) {
            const status_element = document.getElementById('connection-status');
            status_element.textContent = message;
            status_element.className = 'connection-status ' + status_class;
        }

        function update_event_count() {
            document.getElementById('event-count').textContent = `(${event_count} events)`;
        }

        function clear_log() {
            const event_log = document.getElementById('event-log');
            event_log.innerHTML = '<div id="placeholder">No events received yet. Connect to WebSocket to start logging.</div>';
            event_count = 0;
            update_event_count();
        }

        function toggle_collapse() {
            const container = document.getElementById('websocket-container');
            const content = document.getElementById('websocket-content');
            const icon = document.querySelector('.toggle-icon');

            is_collapsed = !is_collapsed;

            if (is_collapsed) {
                container.classList.add('collapsed');
                content.style.display = 'none';
                icon.textContent = '▶';
            } else {
                container.classList.remove('collapsed');
                content.style.display = 'block';
                icon.textContent = '▼';
            }
        }

        // Auto-connect on page load (optional)
        // window.addEventListener('load', connect_websocket);
    </script>
</body>
</html>
