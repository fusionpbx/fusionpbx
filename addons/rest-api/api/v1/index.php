<?php
// Load config
$api_config = require __DIR__ . '/config.php';

// HTTPS enforcement
if (!empty($api_config['security']['require_https'])) {
    if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => ['code' => 'HTTPS_REQUIRED', 'message' => 'HTTPS is required']]);
        exit;
    }
}

// IP whitelist enforcement
$allowed_ips = $api_config['security']['allowed_ips'] ?? [];
if (!empty($allowed_ips)) {
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!in_array($client_ip, $allowed_ips)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => ['code' => 'IP_DENIED', 'message' => 'Access denied']]);
        exit;
    }
}

// Handle CORS - validate against whitelist
$allowed_origins = $api_config['security']['allowed_origins'] ?? [];
$request_origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (!empty($request_origin) && !empty($allowed_origins)) {
    if (in_array('*', $allowed_origins) || in_array($request_origin, $allowed_origins)) {
        header('Access-Control-Allow-Origin: ' . $request_origin);
    }
    // Do NOT set Allow-Credentials with wildcard origins
    if (!in_array('*', $allowed_origins) && in_array($request_origin, $allowed_origins)) {
        header('Access-Control-Allow-Credentials: true');
    }
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key, X-Domain');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Cache-Control: no-store, no-cache, must-revalidate');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/response.php';

// Validate API key and domain
validate_api_key();

// Apply rate limiting
if (!empty($api_config['rate_limit']['enabled'])) {
    require_once __DIR__ . '/middleware/rate-limit.php';
    $provided_key = $_SERVER['HTTP_X_API_KEY'] ?? '';
    apply_rate_limit($provided_key);
}

// Parse the request URI
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

// Remove /api/v1/ prefix
$path = preg_replace('#^/api/v1/?#', '', $path);

// Split path into segments
$segments = array_filter(explode('/', $path));
$segments = array_values($segments); // Re-index array

// Define valid endpoints
$valid_endpoints = [
    'extensions',
    'users',
    'gateways',
    'devices',
    'voicemails',
    'destinations',
    'dialplans',
    'inbound-routes',
    'outbound-routes',
    'time-conditions',
    'cdr',
    'dashboard',
    'domains',
    'ring-groups',
    'ivr-menus',
    'call-flows',
    'call-forward',
    'follow-me',
    'conferences',
    'fax',
    'call-centers',
    'call-block',
    'call-recordings',
    'access-controls',
    'active-calls',
    'sip-profiles',
    'number-translations',
    'registrations',
    'queues',
    'logs',
    'conference-centers',
    'monitoring',
    'recordings'
];

// Extract endpoint and UUID
$endpoint = $segments[0] ?? '';
$path_uuid = null;

if (!in_array($endpoint, $valid_endpoints)) {
    api_error('NOT_FOUND', 'Endpoint not found', null, 404);
}

// Handle nested endpoints (e.g., fax/accounts, call-centers/queues, logs/security)
$nested_endpoints = [
    'fax' => ['accounts', 'files', 'logs'],
    'call-centers' => ['queues', 'agents', 'statistics'],
    'logs' => ['security', 'emergency', 'audit', 'freeswitch'],
    'queues' => ['email', 'fax'],
    'conference-centers' => ['centers', 'rooms', 'profiles'],
    'access-controls' => ['nodes'],
    'sip-profiles' => ['settings'],
];

// Determine the HTTP method
$method = $_SERVER['REQUEST_METHOD'];

// Map method to file
$file_map = [
    'GET' => 'list.php',
    'POST' => 'create.php',
    'PUT' => 'update.php',
    'DELETE' => 'delete.php'
];

// Resolve handler path
$handler_path = null;

// Check if this is a nested endpoint
if (isset($nested_endpoints[$endpoint]) && isset($segments[1]) && in_array($segments[1], $nested_endpoints[$endpoint])) {
    $sub_endpoint = $segments[1];
    $base_dir = __DIR__ . '/' . $endpoint . '/' . $sub_endpoint;

    // Check for action after UUID: /endpoint/sub/{uuid}/action
    if (isset($segments[3]) && is_uuid($segments[2] ?? '')) {
        $path_uuid = $segments[2];
        $action_file = $base_dir . '/' . $segments[3] . '.php';
        if (file_exists($action_file)) {
            $handler_path = $action_file;
        }
    }
    // Check for action without UUID: /endpoint/sub/action (e.g., call-centers/statistics/queue-stats)
    elseif (isset($segments[2]) && !is_uuid($segments[2])) {
        $action_file = $base_dir . '/' . $segments[2] . '.php';
        if (file_exists($action_file)) {
            $handler_path = $action_file;
        }
        $path_uuid = $segments[3] ?? null;
    }
    // Standard CRUD for nested endpoint
    else {
        $path_uuid = $segments[2] ?? null;
        if ($method === 'GET' && $path_uuid && is_uuid($path_uuid)) {
            $handler_file = 'get.php';
        } else {
            $handler_file = $file_map[$method] ?? null;
        }
        if (!$handler_file) {
            api_error('METHOD_NOT_ALLOWED', 'HTTP method not allowed', null, 405);
        }
        $handler_path = $base_dir . '/' . $handler_file;
    }
} else {
    $base_dir = __DIR__ . '/' . $endpoint;

    // Check for action after UUID: /endpoint/{uuid}/action
    if (isset($segments[2]) && is_uuid($segments[1] ?? '')) {
        $path_uuid = $segments[1];
        $action_file = $base_dir . '/' . $segments[2] . '.php';
        if (file_exists($action_file)) {
            $handler_path = $action_file;
        }
    }
    // Check for action without UUID: /endpoint/action (e.g., dashboard/stats, fax/send)
    elseif (isset($segments[1]) && !is_uuid($segments[1])) {
        $action_file = $base_dir . '/' . $segments[1] . '.php';
        if (file_exists($action_file)) {
            $handler_path = $action_file;
            $path_uuid = $segments[2] ?? null;
        }
    }

    // Fall back to standard CRUD routing
    if ($handler_path === null) {
        if (isset($segments[1]) && is_uuid($segments[1])) {
            $path_uuid = $segments[1];
        }

        if ($method === 'GET' && $path_uuid && is_uuid($path_uuid)) {
            $handler_file = 'get.php';
        } else {
            $handler_file = $file_map[$method] ?? null;
        }

        if (!$handler_file) {
            api_error('METHOD_NOT_ALLOWED', 'HTTP method not allowed', null, 405);
        }

        $handler_path = $base_dir . '/' . $handler_file;
    }
}

if (!file_exists($handler_path)) {
    api_error('NOT_IMPLEMENTED', 'Endpoint handler not implemented', null, 501);
}

// Include the handler
require_once $handler_path;
