<?php
require_once dirname(__DIR__, 2) . '/auth.php';
require_once dirname(__DIR__, 2) . '/base.php';
validate_api_key();

api_require_method('POST');

$data = get_request_data();

// Validate required fields
$errors = api_validate($data, ['access_control_uuid', 'node_type', 'node_cidr']);
if (!empty($errors)) {
    api_validation_error($errors);
}

// Validate access_control_uuid
api_validate_uuid($data['access_control_uuid'], 'access_control_uuid');

// Validate node_type
if (!in_array($data['node_type'], ['allow', 'deny'])) {
    api_error('VALIDATION_ERROR', 'node_type must be "allow" or "deny"', 'node_type', 400);
}

// Validate CIDR format
if (!preg_match('/^(\d{1,3}\.){3}\d{1,3}(\/\d{1,2})?$/', $data['node_cidr'])) {
    api_error('VALIDATION_ERROR', 'Invalid CIDR format (e.g., 192.168.1.0/24 or 10.0.0.1)', 'node_cidr', 400);
}

$database = new database;

// Verify access control exists and belongs to the authenticated domain
// Only allow modifying domain-scoped ACLs, not global ones
$check_sql = "SELECT access_control_uuid FROM v_access_controls
              WHERE access_control_uuid = :access_control_uuid
              AND domain_uuid = :domain_uuid";
$exists = $database->select($check_sql, [
    'access_control_uuid' => $data['access_control_uuid'],
    'domain_uuid' => $domain_uuid
], 'row');

if (!$exists) {
    api_not_found('Access Control');
}

// Check for duplicate node
$dup_sql = "SELECT COUNT(*) FROM v_access_control_nodes
            WHERE access_control_uuid = :access_control_uuid
            AND node_cidr = :node_cidr";
$duplicate = $database->select($dup_sql, [
    'access_control_uuid' => $data['access_control_uuid'],
    'node_cidr' => $data['node_cidr']
], 'column');

if ($duplicate > 0) {
    api_conflict('node_cidr', 'Node with this CIDR already exists for this access control');
}

// Generate UUID
$access_control_node_uuid = uuid();

// Prepare data - include domain_uuid for domain-scoped nodes
$array['access_control_nodes'][0] = [
    'access_control_node_uuid' => $access_control_node_uuid,
    'access_control_uuid' => $data['access_control_uuid'],
    'node_type' => $data['node_type'],
    'node_cidr' => $data['node_cidr'],
    'node_domain' => $data['node_domain'] ?? null,
    'node_description' => $data['node_description'] ?? null,
    'domain_uuid' => $domain_uuid
];

// Save to database
$database->app_name = 'api-access-controls';
$database->app_uuid = '5478b10e-d58d-4c83-b5da-24e2e1b4e267';
$database->save($array);

// Clear ACL cache
api_clear_cache("configuration:acl.conf");

$response = [
    'access_control_node_uuid' => $access_control_node_uuid,
    'access_control_uuid' => $data['access_control_uuid'],
    'node_type' => $data['node_type'],
    'node_cidr' => $data['node_cidr'],
    'node_domain' => $array['access_control_nodes'][0]['node_domain'],
    'node_description' => $array['access_control_nodes'][0]['node_description']
];

api_created($response, 'Access control node added successfully');
