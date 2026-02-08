<?php
require_once dirname(__DIR__) . '/auth.php';
validate_api_key();

$destination_uuid = get_uuid_from_path();
if (empty($destination_uuid)) {
    api_error('MISSING_UUID', 'Destination UUID is required');
}

// Get existing destination
$database = new database;
$sql = "SELECT dialplan_uuid, destination_context FROM v_destinations
        WHERE domain_uuid = :domain_uuid
        AND destination_uuid = :destination_uuid";
$parameters = [
    'domain_uuid' => $domain_uuid,
    'destination_uuid' => $destination_uuid
];
$destination = $database->select($sql, $parameters, 'row');

if (empty($destination)) {
    api_error('NOT_FOUND', 'Destination not found', null, 404);
}

// Build delete array
$array['destinations'][0]['destination_uuid'] = $destination_uuid;

// Include dialplan if exists
if (!empty($destination['dialplan_uuid']) && is_uuid($destination['dialplan_uuid'])) {
    $array['dialplan_details'][0]['dialplan_uuid'] = $destination['dialplan_uuid'];
    $array['dialplans'][0]['dialplan_uuid'] = $destination['dialplan_uuid'];
}

// Grant temporary permissions
$p = permissions::new();
$p->add('destination_delete', 'temp');
$p->add('dialplan_delete', 'temp');
$p->add('dialplan_detail_delete', 'temp');

// Delete from database
$database->app_name = 'destinations';
$database->app_uuid = '5ec89622-b19c-3559-64f0-afde802ab139';
$database->delete($array);

// Revoke temporary permissions
$p->delete('destination_delete', 'temp');
$p->delete('dialplan_delete', 'temp');
$p->delete('dialplan_detail_delete', 'temp');

// Clear cache
if (!empty($destination['destination_context'])) {
    $cache = new cache;
    $cache->delete("dialplan:" . $destination['destination_context']);
}

api_no_content();
