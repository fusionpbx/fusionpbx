<?php
function api_success($data, $message = null, $pagination = null) {
    header('Content-Type: application/json');
    $response = ['success' => true];
    if ($data !== null) $response['data'] = $data;
    if ($message !== null) $response['message'] = $message;
    if ($pagination !== null) $response['pagination'] = $pagination;
    echo json_encode($response);
    exit;
}

function api_error($code, $message, $field = null, $http_code = 400) {
    http_response_code($http_code);
    header('Content-Type: application/json');
    $error = ['code' => $code, 'message' => $message];
    if ($field !== null) $error['field'] = $field;
    echo json_encode(['success' => false, 'error' => $error]);
    exit;
}

function api_generate_password($length = 16) {
    return bin2hex(random_bytes($length / 2));
}

function generate_numeric_password($length = 6) {
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= random_int(0, 9);
    }
    return $password;
}

function get_request_data() {
    $json = file_get_contents('php://input');
    return json_decode($json, true) ?? [];
}

function get_uuid_from_path() {
    global $path_uuid;
    return $path_uuid ?? null;
}

/**
 * Return validation errors response
 * @param array $errors Array of validation errors
 */
function api_validation_error($errors) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => [
            'code' => 'VALIDATION_ERROR',
            'message' => 'Validation failed',
            'errors' => $errors
        ]
    ]);
    exit;
}

/**
 * Return created response (201)
 * @param array $data Created resource data
 * @param string $message Success message
 */
function api_created($data, $message = 'Resource created successfully') {
    http_response_code(201);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => $data,
        'message' => $message
    ]);
    exit;
}

/**
 * Return no content response (204)
 * Used for successful DELETE operations
 */
function api_no_content() {
    http_response_code(204);
    exit;
}

/**
 * Return not found error
 * @param string $resource Resource type (e.g., 'Ring Group')
 */
function api_not_found($resource = 'Resource') {
    api_error('NOT_FOUND', "{$resource} not found", null, 404);
}

/**
 * Return forbidden error
 * @param string $message Error message
 */
function api_forbidden($message = 'Access denied') {
    api_error('FORBIDDEN', $message, null, 403);
}

/**
 * Return conflict error (duplicate)
 * @param string $field Field that caused conflict
 * @param string $message Error message
 */
function api_conflict($field, $message = 'Resource already exists') {
    api_error('DUPLICATE_ERROR', $message, $field, 409);
}

/**
 * Get pagination parameters from query string
 * @return array ['page' => int, 'per_page' => int]
 */
function get_pagination_params() {
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $per_page = isset($_GET['per_page']) ? min(100, max(1, intval($_GET['per_page']))) : 50;
    return ['page' => $page, 'per_page' => $per_page];
}

/**
 * Get filter parameters from query string
 * @param array $allowed_filters List of allowed filter field names
 * @return array Filtered parameters
 */
function get_filter_params($allowed_filters = []) {
    $filters = [];
    foreach ($allowed_filters as $field) {
        if (isset($_GET[$field]) && $_GET[$field] !== '') {
            $filters[$field] = $_GET[$field];
        }
    }
    return $filters;
}

/**
 * Get sort parameters from query string
 * @param string $default_field Default sort field
 * @param string $default_order Default sort order (ASC/DESC)
 * @param array $allowed_fields Allowed sort fields
 * @return array ['field' => string, 'order' => string]
 */
function get_sort_params($default_field = 'created_at', $default_order = 'DESC', $allowed_fields = []) {
    $field = $_GET['sort'] ?? $default_field;
    $order = strtoupper($_GET['order'] ?? $default_order);

    // Validate sort field if allowed_fields specified
    if (!empty($allowed_fields) && !in_array($field, $allowed_fields)) {
        $field = $default_field;
    }

    // Validate order
    if (!in_array($order, ['ASC', 'DESC'])) {
        $order = $default_order;
    }

    return ['field' => $field, 'order' => $order];
}

/**
 * Require specific HTTP method(s)
 * @param string|array $methods Allowed HTTP method(s)
 */
function api_require_method($methods) {
    $methods = is_array($methods) ? $methods : [$methods];
    $current_method = $_SERVER['REQUEST_METHOD'];

    if (!in_array($current_method, $methods)) {
        http_response_code(405);
        header('Content-Type: application/json');
        header('Allow: ' . implode(', ', $methods));
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => 'METHOD_NOT_ALLOWED',
                'message' => 'HTTP method not allowed'
            ]
        ]);
        exit;
    }
}
