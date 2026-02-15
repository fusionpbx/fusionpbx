<?php
require_once dirname(__DIR__) . '/auth.php';
validate_api_key();

$user_uuid = get_uuid_from_path();
if (!is_uuid($user_uuid)) {
    api_error('VALIDATION_ERROR', 'Invalid user UUID', 'user_uuid');
}

// Verify user exists
$database = new database;
$sql = "SELECT user_uuid FROM v_users WHERE domain_uuid = :domain_uuid AND user_uuid = :user_uuid";
$parameters = ['domain_uuid' => $domain_uuid, 'user_uuid' => $user_uuid];
if (!$database->select($sql, $parameters, 'row')) {
    api_error('NOT_FOUND', 'User not found', null, 404);
}

// Check if user is superadmin (prevent deletion)
$superadmin_list = superadmin_list();
if (if_superadmin($superadmin_list, $user_uuid)) {
    api_error('FORBIDDEN', 'Cannot delete superadmin user', null, 403);
}

// Delete user and related records
$array['user_settings'][0]['user_uuid'] = $user_uuid;
$array['user_settings'][0]['domain_uuid'] = $domain_uuid;

$array['user_groups'][0]['user_uuid'] = $user_uuid;
$array['user_groups'][0]['domain_uuid'] = $domain_uuid;

$array['users'][0]['user_uuid'] = $user_uuid;
$array['users'][0]['domain_uuid'] = $domain_uuid;

$database = new database;
$database->delete($array);

api_no_content();
