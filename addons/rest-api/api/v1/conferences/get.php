<?php
require_once dirname(__DIR__) . '/base.php';
validate_api_key();

api_require_method('GET');

// Get conference UUID from path
$conference_uuid = get_uuid_from_path();
api_validate_uuid($conference_uuid, 'conference_uuid');

// Get conference record
$conference = api_get_record('v_conferences', 'conference_uuid', $conference_uuid);

if (!$conference) {
    api_not_found('Conference');
}

// Get conference users
$database = new database;
$sql = "SELECT cu.conference_user_uuid, cu.user_uuid, u.username
        FROM v_conference_users cu
        LEFT JOIN v_users u ON cu.user_uuid = u.user_uuid
        WHERE cu.conference_uuid = :conference_uuid
        AND cu.domain_uuid = :domain_uuid
        ORDER BY u.username ASC";

$parameters = [
    'conference_uuid' => $conference_uuid,
    'domain_uuid' => $domain_uuid
];

$users = $database->select($sql, $parameters, 'all');

// Add users to conference object
$conference['users'] = $users ?? [];

api_success($conference);
