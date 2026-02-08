<?php
/**
 * FusionPBX REST API Base Controller
 * Provides common functionality for all API endpoints
 *
 * This file contains reusable helper functions for API endpoints including:
 * - Pagination
 * - Validation
 * - Record lookup
 * - Cache management
 * - XML regeneration
 * - Query parameter handling
 */

// Locate the FusionPBX bootstrap no matter if the API is symlinked or copied
$bootstrap_path = null;
$search_dir = __DIR__;
for ($i = 0; $i < 6; $i++) {
    $candidate = $search_dir . '/resources/require.php';
    if (file_exists($candidate)) {
        $bootstrap_path = $candidate;
        break;
    }
    $parent = dirname($search_dir);
    if ($parent === $search_dir) {
        break; // reached filesystem root
    }
    $search_dir = $parent;
}

if ($bootstrap_path === null) {
    http_response_code(500);
    echo 'FusionPBX bootstrap (resources/require.php) not found.';
    exit;
}

require_once $bootstrap_path;
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/response.php';
$api_config = require __DIR__ . '/config.php';

/**
 * Paginate database query results
 *
 * @param string $sql Main query SQL
 * @param string $count_sql Count query SQL
 * @param array $parameters Query parameters
 * @param int $page Page number (1-indexed)
 * @param int $per_page Items per page (max 100)
 * @return array ['items' => array, 'pagination' => array]
 */
function api_paginate($sql, $count_sql, $parameters, $page = 1, $per_page = 50) {
    global $domain_uuid;

    $page = max(1, intval($page));
    $per_page = min(100, max(1, intval($per_page)));
    $offset = ($page - 1) * $per_page;

    $database = new database;

    // Get total count
    $total = $database->select($count_sql, $parameters, 'column');

    // Add pagination to query
    $parameters['limit'] = $per_page;
    $parameters['offset'] = $offset;

    $items = $database->select($sql . " LIMIT :limit OFFSET :offset", $parameters, 'all');

    $pagination = [
        'page' => $page,
        'per_page' => $per_page,
        'total' => (int)$total,
        'total_pages' => ceil($total / $per_page)
    ];

    return ['items' => $items ?? [], 'pagination' => $pagination];
}

/**
 * Validate required fields in request data
 *
 * @param array $data Request data to validate
 * @param array $required_fields List of required field names
 * @return array List of validation errors (empty if valid)
 */
function api_validate($data, $required_fields) {
    $errors = [];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            $errors[] = ['field' => $field, 'message' => "{$field} is required"];
        }
    }
    return $errors;
}

/**
 * Validate UUID format and return it or exit with error
 *
 * @param string $uuid UUID to validate
 * @param string $field_name Field name for error message
 * @return string Validated UUID
 */
function api_validate_uuid($uuid, $field_name = 'uuid') {
    if (empty($uuid) || !is_uuid($uuid)) {
        api_error('VALIDATION_ERROR', "Invalid {$field_name}", $field_name, 400);
    }
    return $uuid;
}

/**
 * Check if a record exists in the specified table
 *
 * @param string $table Table name
 * @param string $uuid_field UUID field name
 * @param string $uuid UUID value
 * @param string|null $domain_uuid Domain UUID (uses global if null)
 * @return bool True if record exists
 */
function api_record_exists($table, $uuid_field, $uuid, $check_domain_uuid = null) {
    global $domain_uuid;
    $check_domain_uuid = $check_domain_uuid ?? $domain_uuid;

    $database = new database;
    $sql = "SELECT COUNT(*) FROM {$table} WHERE {$uuid_field} = :uuid AND domain_uuid = :domain_uuid";
    $parameters = ['uuid' => $uuid, 'domain_uuid' => $check_domain_uuid];
    return $database->select($sql, $parameters, 'column') > 0;
}

/**
 * Get a single record by UUID
 *
 * @param string $table Table name
 * @param string $uuid_field UUID field name
 * @param string $uuid UUID value
 * @param string $fields Fields to select (default: *)
 * @return array|false Record data or false if not found
 */
function api_get_record($table, $uuid_field, $uuid, $fields = '*') {
    global $domain_uuid;

    $database = new database;
    $sql = "SELECT {$fields} FROM {$table} WHERE {$uuid_field} = :uuid AND domain_uuid = :domain_uuid";
    $parameters = ['uuid' => $uuid, 'domain_uuid' => $domain_uuid];
    return $database->select($sql, $parameters, 'row');
}

/**
 * Clear FusionPBX cache entry
 *
 * @param string $key Cache key to clear
 */
function api_clear_cache($key) {
    $cache = new cache;
    $cache->delete($key);
}

/**
 * Clear dialplan cache for domain
 *
 * @param string|null $context Context name (uses domain_name if null)
 */
function api_clear_dialplan_cache($context = null) {
    global $domain_name;
    $context = $context ?? $domain_name;
    $cache = new cache;
    $cache->delete("dialplan:" . $context);
}

/**
 * Log API action for audit trail
 *
 * @param string $action Action performed
 * @param string $entity_type Entity type (extension, gateway, etc.)
 * @param string $entity_uuid Entity UUID
 * @param array $details Additional details
 */
function api_log($action, $entity_type, $entity_uuid, $details = []) {
    // Optional: Log API actions for audit trail
    // Can be extended to write to v_database_transactions
    // For now, this is a placeholder for future implementation
}

/**
 * Trigger FreeSWITCH XML regeneration
 *
 * @param string $type Type of XML to regenerate (extension, dialplan, gateway)
 * @param string|null $domain_uuid Domain UUID
 * @param string|null $domain_name Domain name
 */
function api_regenerate_xml($type, $regen_domain_uuid = null, $regen_domain_name = null) {
    global $domain_uuid;
    global $domain_name;

    $regen_domain_uuid = $regen_domain_uuid ?? $domain_uuid;
    $regen_domain_name = $regen_domain_name ?? $domain_name;

    switch ($type) {
        case 'extension':
            if (class_exists('extension')) {
                $ext = new extension;
                $ext->domain_uuid = $regen_domain_uuid;
                $ext->domain_name = $regen_domain_name;
                $ext->xml();
            }
            break;
        case 'dialplan':
            api_clear_dialplan_cache();
            break;
        case 'gateway':
            if (function_exists('save_gateway_xml')) {
                save_gateway_xml();
            }
            $cache = new cache;
            $cache->delete(gethostname() . ":configuration:sofia.conf");
            break;
    }
}

/**
 * Get query parameters with default values
 *
 * @param array $defaults Associative array of parameter => default value
 * @return array Query parameters
 */
function api_get_query_params($defaults = []) {
    $params = [];
    foreach ($defaults as $key => $default) {
        $params[$key] = $_GET[$key] ?? $default;
    }
    return $params;
}

/**
 * Build WHERE clause and parameters from filters
 *
 * @param array $filters Filter values (field => value)
 * @param array $allowed_fields List of allowed filter fields
 * @return array ['where' => string, 'parameters' => array]
 */
function api_build_filters($filters, $allowed_fields) {
    $where = [];
    $parameters = [];

    foreach ($filters as $field => $value) {
        if (in_array($field, $allowed_fields) && !empty($value)) {
            $where[] = "{$field} = :{$field}";
            $parameters[$field] = $value;
        }
    }

    return [
        'where' => count($where) > 0 ? ' AND ' . implode(' AND ', $where) : '',
        'parameters' => $parameters
    ];
}

// Auto-authenticate when base.php is loaded
validate_api_key();
