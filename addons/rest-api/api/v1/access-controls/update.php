<?php
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/base.php';
validate_api_key();

api_require_method(['PUT', 'PATCH']);

$access_control_uuid = get_uuid_from_path();
api_validate_uuid($access_control_uuid, 'access_control_uuid');

$data = get_request_data();

$database = new database;

// Check if access control exists and has access
$check_sql = "SELECT access_control_uuid, domain_uuid FROM v_access_controls
              WHERE access_control_uuid = :access_control_uuid
              AND (domain_uuid = :domain_uuid OR domain_uuid IS NULL)";
$existing = $database->select($check_sql, [
    'access_control_uuid' => $access_control_uuid,
    'domain_uuid' => $domain_uuid
], 'row');

if (!$existing) {
    api_not_found('Access Control');
}

// If name is being changed, check for duplicates
if (!empty($data['access_control_name'])) {
    $dup_sql = "SELECT COUNT(*) FROM v_access_controls
                WHERE access_control_name = :name
                AND access_control_uuid != :uuid
                AND (domain_uuid = :domain_uuid OR domain_uuid IS NULL)";
    $duplicate = $database->select($dup_sql, [
        'name' => $data['access_control_name'],
        'uuid' => $access_control_uuid,
        'domain_uuid' => $domain_uuid
    ], 'column');

    if ($duplicate > 0) {
        api_conflict('access_control_name', 'Access control with this name already exists');
    }
}

// Build update array
$array['access_controls'][0]['access_control_uuid'] = $access_control_uuid;

if (isset($data['access_control_name'])) {
    $array['access_controls'][0]['access_control_name'] = $data['access_control_name'];
}
if (isset($data['access_control_default'])) {
    if (!in_array($data['access_control_default'], ['allow', 'deny'])) {
        api_error('VALIDATION_ERROR', 'access_control_default must be "allow" or "deny"', 'access_control_default', 400);
    }
    $array['access_controls'][0]['access_control_default'] = $data['access_control_default'];
}
if (isset($data['access_control_description'])) {
    $array['access_controls'][0]['access_control_description'] = $data['access_control_description'];
}

// Save to database
$database->app_name = 'api-access-controls';
$database->app_uuid = '5478b10e-d58d-4c83-b5da-24e2e1b4e267';
$database->save($array);

// Clear ACL cache
api_clear_cache("configuration:acl.conf");

// Get updated record
$get_sql = "SELECT access_control_uuid, access_control_name, access_control_default, access_control_description, domain_uuid
            FROM v_access_controls WHERE access_control_uuid = :uuid";
$updated = $database->select($get_sql, ['uuid' => $access_control_uuid], 'row');

api_success($updated, 'Access control updated successfully');
