<?php
require_once __DIR__ . '/../../base.php';
validate_api_key();

$conference_profile_uuid = get_uuid_from_path();
api_validate_uuid($conference_profile_uuid, 'conference_profile_uuid');

// Get conference profile - may be global (domain_uuid IS NULL)
$database = new database;
$sql = "SELECT conference_profile_uuid, profile_name, profile_description, enabled
        FROM v_conference_profiles
        WHERE conference_profile_uuid = :uuid
        AND (domain_uuid = :domain_uuid OR domain_uuid IS NULL)";

$parameters = [
    'uuid' => $conference_profile_uuid,
    'domain_uuid' => $domain_uuid
];

$profile = $database->select($sql, $parameters, 'row');

if (!$profile) {
    api_not_found('Conference Profile');
}

api_success($profile);
