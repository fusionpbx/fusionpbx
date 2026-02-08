<?php
require_once dirname(__DIR__) . '/base.php';
validate_api_key();
api_require_method('GET');

// Get UUID from path
$follow_me_uuid = get_uuid_from_path();
api_validate_uuid($follow_me_uuid, 'follow_me_uuid');

// Get follow me configuration
$follow_me = api_get_record('v_follow_me', 'follow_me_uuid', $follow_me_uuid);

if (!$follow_me) {
    api_not_found('Follow Me configuration');
}

// Get destinations for this follow me configuration
$database = new database;
$dest_sql = "SELECT follow_me_destination_uuid, follow_me_destination, follow_me_delay,
             follow_me_timeout, follow_me_prompt
             FROM v_follow_me_destinations
             WHERE follow_me_uuid = :follow_me_uuid
             AND domain_uuid = :domain_uuid
             ORDER BY follow_me_delay ASC, follow_me_destination_uuid ASC";

$dest_params = [
    'follow_me_uuid' => $follow_me_uuid,
    'domain_uuid' => $domain_uuid
];

$destinations = $database->select($dest_sql, $dest_params, 'all');

// Add destinations to follow me data
$follow_me['destinations'] = $destinations ?? [];

api_success($follow_me);
