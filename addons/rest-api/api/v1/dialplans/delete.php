<?php
require_once dirname(__DIR__) . '/auth.php';
validate_api_key();

$dialplan_uuid = get_uuid_from_path();
if (empty($dialplan_uuid)) {
    api_error('MISSING_UUID', 'Dialplan UUID is required');
}

// Verify dialplan exists and get context
$database = new database;
$sql = "SELECT dialplan_context FROM v_dialplans WHERE domain_uuid = :domain_uuid AND dialplan_uuid = :dialplan_uuid";
$parameters = [
    'domain_uuid' => $domain_uuid,
    'dialplan_uuid' => $dialplan_uuid
];
$dialplan = $database->select($sql, $parameters, 'row');

if (empty($dialplan)) {
    api_error('NOT_FOUND', 'Dialplan not found', null, 404);
}

$dialplan_context = $dialplan['dialplan_context'];

// Delete dialplan and its details (cascade)
$array['dialplans'][0]['domain_uuid'] = $domain_uuid;
$array['dialplans'][0]['dialplan_uuid'] = $dialplan_uuid;

$database = new database;
$database->app_name = 'dialplans';
$database->app_uuid = '742714e5-8cdf-32fd-462c-cbe7e3d655db';
$database->delete($array);

// CRITICAL: Clear dialplan cache
$cache = new cache;
$cache->delete("dialplan:" . $dialplan_context);

api_no_content();
