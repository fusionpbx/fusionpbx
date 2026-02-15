<?php
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/base.php';
validate_api_key();

$ring_group_uuid = get_uuid_from_path();
api_validate_uuid($ring_group_uuid, 'ring_group_uuid');

$database = new database;

// Get ring group
$sql = "SELECT * FROM v_ring_groups WHERE domain_uuid = :domain_uuid AND ring_group_uuid = :ring_group_uuid";
$parameters = ['domain_uuid' => $domain_uuid, 'ring_group_uuid' => $ring_group_uuid];
$ring_group = $database->select($sql, $parameters, 'row');

if (empty($ring_group)) {
    api_not_found('Ring group');
}

// Get destinations
$dest_sql = "SELECT ring_group_destination_uuid, destination_number, destination_delay,
             destination_timeout, destination_prompt, destination_enabled
             FROM v_ring_group_destinations
             WHERE domain_uuid = :domain_uuid AND ring_group_uuid = :ring_group_uuid
             ORDER BY destination_delay ASC";
$ring_group['destinations'] = $database->select($dest_sql, $parameters, 'all') ?? [];

api_success($ring_group);
