<?php
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/base.php';
validate_api_key();

$ring_group_uuid = get_uuid_from_path();
api_validate_uuid($ring_group_uuid, 'ring_group_uuid');

// Verify ring group exists
$database = new database;
$sql = "SELECT ring_group_uuid FROM v_ring_groups WHERE domain_uuid = :domain_uuid AND ring_group_uuid = :ring_group_uuid";
if (!$database->select($sql, ['domain_uuid' => $domain_uuid, 'ring_group_uuid' => $ring_group_uuid], 'row')) {
    api_not_found('Ring group');
}

// Delete destinations first
$array['ring_group_destinations'][0]['domain_uuid'] = $domain_uuid;
$array['ring_group_destinations'][0]['ring_group_uuid'] = $ring_group_uuid;

// Delete ring group
$array['ring_groups'][0]['domain_uuid'] = $domain_uuid;
$array['ring_groups'][0]['ring_group_uuid'] = $ring_group_uuid;

// Grant permissions
$p = permissions::new();
$p->add('ring_group_delete', 'temp');

$database = new database;
$database->app_name = 'ring_groups';
$database->app_uuid = '1d61fb65-1eec-bc73-a6ee-a6571b4e36de';
$database->delete($array);

$p->delete('ring_group_delete', 'temp');

// Clear dialplan cache
api_clear_dialplan_cache();

// Reload XML
if (class_exists('event_socket')) {
    event_socket::api('reloadxml');
}

api_no_content();
