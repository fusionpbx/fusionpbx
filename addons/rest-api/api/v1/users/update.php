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

$request = get_request_data();

// Build update array
$array['users'][0]['user_uuid'] = $user_uuid;
$array['users'][0]['domain_uuid'] = $domain_uuid;

// Update username if provided
if (isset($request['username'])) {
    // Check uniqueness
    $sql = "SELECT count(*) FROM v_users WHERE domain_uuid = :domain_uuid AND username = :username AND user_uuid != :user_uuid";
    $params = [
        'domain_uuid' => $domain_uuid,
        'username' => $request['username'],
        'user_uuid' => $user_uuid
    ];
    if ($database->select($sql, $params, 'column') > 0) {
        api_error('DUPLICATE_ERROR', 'Username already exists', 'username');
    }
    $array['users'][0]['username'] = $request['username'];
}

// Update password if provided
if (!empty($request['password'])) {
    $array['users'][0]['password'] = password_hash($request['password'], PASSWORD_BCRYPT, ['cost' => 10]);
}

// Update other fields
if (isset($request['user_email'])) {
    $array['users'][0]['user_email'] = $request['user_email'];
}
if (isset($request['user_enabled'])) {
    $array['users'][0]['user_enabled'] = $request['user_enabled'];
}
if (isset($request['user_status'])) {
    $array['users'][0]['user_status'] = $request['user_status'];
}
if (isset($request['user_language'])) {
    $array['users'][0]['user_language'] = $request['user_language'];
}
if (isset($request['user_time_zone'])) {
    $array['users'][0]['user_time_zone'] = $request['user_time_zone'];
}
if (isset($request['api_key'])) {
    $array['users'][0]['api_key'] = $request['api_key'];
}

// Save user updates if any fields changed
if (count($array['users'][0]) > 2) { // More than just uuid and domain_uuid
    $database = new database;
    $database->app_name = 'users';
    $database->app_uuid = '112124b3-95c2-5352-7e9d-d14c0b88f207';
    $database->save($array);
    unset($array);
}

// Update groups if provided
if (isset($request['groups']) && is_array($request['groups'])) {
    // Remove existing groups
    $delete_array['user_groups'][0]['user_uuid'] = $user_uuid;
    $delete_array['user_groups'][0]['domain_uuid'] = $domain_uuid;
    $database = new database;
    $database->delete($delete_array);
    unset($delete_array);

    // Add new groups
    foreach ($request['groups'] as $index => $group_name) {
        $sql = "SELECT group_uuid FROM v_groups WHERE group_name = :group_name AND domain_uuid = :domain_uuid";
        $group_uuid = $database->select($sql, ['group_name' => $group_name, 'domain_uuid' => $domain_uuid], 'column');

        if ($group_uuid) {
            $array['user_groups'][$index]['user_group_uuid'] = uuid();
            $array['user_groups'][$index]['domain_uuid'] = $domain_uuid;
            $array['user_groups'][$index]['group_uuid'] = $group_uuid;
            $array['user_groups'][$index]['user_uuid'] = $user_uuid;
        }
    }
    if (!empty($array)) {
        $database = new database;
        $database->save($array);
        unset($array);
    }
}

api_success(['user_uuid' => $user_uuid], 'User updated successfully');
