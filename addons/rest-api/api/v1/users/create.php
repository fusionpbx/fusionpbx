<?php
require_once dirname(__DIR__) . '/base.php';

$request = get_request_data();

// Validate required fields
if (empty($request['username'])) {
    api_error('VALIDATION_ERROR', 'Username is required', 'username');
}
if (empty($request['password'])) {
    api_error('VALIDATION_ERROR', 'Password is required', 'password');
}

// Check username uniqueness
$database = new database;
$sql = "SELECT count(*) FROM v_users WHERE domain_uuid = :domain_uuid AND username = :username";
$parameters = ['domain_uuid' => $domain_uuid, 'username' => $request['username']];
if ($database->select($sql, $parameters, 'column') > 0) {
    api_error('DUPLICATE_ERROR', 'Username already exists', 'username');
}

$user_uuid = uuid();

// Build user array
$array['users'][0]['domain_uuid'] = $domain_uuid;
$array['users'][0]['user_uuid'] = $user_uuid;
$array['users'][0]['username'] = $request['username'];
$array['users'][0]['password'] = password_hash($request['password'], PASSWORD_BCRYPT, ['cost' => 10]);
$array['users'][0]['user_email'] = $request['user_email'] ?? '';
$array['users'][0]['user_enabled'] = $request['user_enabled'] ?? 'true';

// Optional fields
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

// Grant permissions
$p = permissions::new();
$p->add('user_add', 'temp');
$p->add('user_group_add', 'temp');
$p->add('contact_add', 'temp');

$database = new database;
$database->app_name = 'users';
$database->app_uuid = '112124b3-95c2-5352-7e9d-d14c0b88f207';
$database->save($array);
unset($array);

// Add to groups
if (!empty($request['groups']) && is_array($request['groups'])) {
    foreach ($request['groups'] as $index => $group_name) {
        $database = new database;
        $sql = "SELECT group_uuid FROM v_groups WHERE group_name = :group_name AND (domain_uuid = :domain_uuid OR domain_uuid IS NULL) LIMIT 1";
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

// Create contact if name provided
if (!empty($request['contact_name_given']) || !empty($request['contact_name_family'])) {
    $contact_uuid = uuid();
    $array['contacts'][0]['domain_uuid'] = $domain_uuid;
    $array['contacts'][0]['contact_uuid'] = $contact_uuid;
    $array['contacts'][0]['contact_type'] = 'user';
    $array['contacts'][0]['contact_name_given'] = $request['contact_name_given'] ?? '';
    $array['contacts'][0]['contact_name_family'] = $request['contact_name_family'] ?? '';

    // Optional contact fields
    if (!empty($request['contact_organization'])) {
        $array['contacts'][0]['contact_organization'] = $request['contact_organization'];
    }
    if (!empty($request['contact_email'])) {
        $array['contacts'][0]['contact_email'] = $request['contact_email'];
    }
    if (!empty($request['contact_url'])) {
        $array['contacts'][0]['contact_url'] = $request['contact_url'];
    }
    if (!empty($request['contact_nickname'])) {
        $array['contacts'][0]['contact_nickname'] = $request['contact_nickname'];
    }

    $database = new database;
    $database->save($array);
    unset($array);

    // Link contact to user
    $update_array['users'][0]['user_uuid'] = $user_uuid;
    $update_array['users'][0]['domain_uuid'] = $domain_uuid;
    $update_array['users'][0]['contact_uuid'] = $contact_uuid;
    $database = new database;
    $database->save($update_array);
}

// Revoke permissions
$p->delete('user_add', 'temp');
$p->delete('user_group_add', 'temp');
$p->delete('contact_add', 'temp');

api_success(['user_uuid' => $user_uuid], 'User created successfully');
