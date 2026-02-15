<?php
require_once dirname(__DIR__) . '/auth.php';
validate_api_key();

$user_uuid = get_uuid_from_path();
if (!is_uuid($user_uuid)) {
    api_error('VALIDATION_ERROR', 'Invalid user UUID', 'user_uuid');
}

// Get user details (exclude password, salt, and api_key)
$sql = "SELECT user_uuid, username, user_email, user_enabled,
        contact_uuid, user_status, user_language, user_time_zone
        FROM v_users
        WHERE domain_uuid = :domain_uuid AND user_uuid = :user_uuid";

$parameters = [
    'domain_uuid' => $domain_uuid,
    'user_uuid' => $user_uuid
];

$database = new database;
$user = $database->select($sql, $parameters, 'row');

if (empty($user)) {
    api_error('NOT_FOUND', 'User not found', null, 404);
}

// Get user groups
$sql = "SELECT g.group_name, g.group_uuid, g.group_description
        FROM v_user_groups ug
        JOIN v_groups g ON g.group_uuid = ug.group_uuid
        WHERE ug.domain_uuid = :domain_uuid AND ug.user_uuid = :user_uuid
        ORDER BY g.group_name";

$groups = $database->select($sql, $parameters, 'all');
$user['groups'] = $groups ?? [];

// Get contact info if exists
if (!empty($user['contact_uuid'])) {
    $sql = "SELECT contact_uuid, contact_name_given, contact_name_family,
            contact_organization, contact_email, contact_url, contact_nickname
            FROM v_contacts
            WHERE contact_uuid = :contact_uuid AND domain_uuid = :domain_uuid";

    $contact_params = [
        'contact_uuid' => $user['contact_uuid'],
        'domain_uuid' => $domain_uuid
    ];

    $contact = $database->select($sql, $contact_params, 'row');
    if ($contact) {
        $user['contact'] = $contact;
    }
}

api_success($user);
