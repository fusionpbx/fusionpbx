<?php
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/base.php';
validate_api_key();

api_require_method('POST');

$data = get_request_data();

// Validate required fields
$errors = api_validate($data, ['access_control_name']);
if (!empty($errors)) {
    api_validation_error($errors);
}

// Check for duplicate name
$database = new database;
$check_sql = "SELECT COUNT(*) FROM v_access_controls
              WHERE access_control_name = :name
              AND (domain_uuid = :domain_uuid OR domain_uuid IS NULL)";
$exists = $database->select($check_sql, [
    'name' => $data['access_control_name'],
    'domain_uuid' => $domain_uuid
], 'column');

if ($exists > 0) {
    api_conflict('access_control_name', 'Access control with this name already exists');
}

// Generate UUID
$access_control_uuid = uuid();

// Prepare data
$array['access_controls'][0] = [
    'access_control_uuid' => $access_control_uuid,
    'domain_uuid' => $domain_uuid,
    'access_control_name' => $data['access_control_name'],
    'access_control_default' => $data['access_control_default'] ?? 'deny',
    'access_control_description' => $data['access_control_description'] ?? null
];

// Save to database
$database->app_name = 'api-access-controls';
$database->app_uuid = '5478b10e-d58d-4c83-b5da-24e2e1b4e267';
$database->save($array);

// Clear ACL cache
api_clear_cache("configuration:acl.conf");

$response = [
    'access_control_uuid' => $access_control_uuid,
    'access_control_name' => $data['access_control_name'],
    'access_control_default' => $array['access_controls'][0]['access_control_default'],
    'access_control_description' => $array['access_controls'][0]['access_control_description']
];

api_created($response, 'Access control created successfully');
