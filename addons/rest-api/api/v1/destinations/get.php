<?php
require_once dirname(__DIR__) . '/auth.php';
validate_api_key();

$destination_uuid = get_uuid_from_path();
if (empty($destination_uuid)) {
    api_error('MISSING_UUID', 'Destination UUID is required');
}

// Get destination details
$sql = "SELECT * FROM v_destinations
        WHERE domain_uuid = :domain_uuid
        AND destination_uuid = :destination_uuid";
$parameters = [
    'domain_uuid' => $domain_uuid,
    'destination_uuid' => $destination_uuid
];

$database = new database;
$destination = $database->select($sql, $parameters, 'row');

if (empty($destination)) {
    api_error('NOT_FOUND', 'Destination not found', null, 404);
}

// Convert destination_enabled to boolean
$destination['destination_enabled'] = ($destination['destination_enabled'] === 'true' || $destination['destination_enabled'] === true);

// Get destination actions from dialplan_details if dialplan_uuid exists
if (!empty($destination['dialplan_uuid'])) {
    $actions_sql = "SELECT dialplan_detail_uuid, dialplan_detail_tag,
                    dialplan_detail_type as destination_app,
                    dialplan_detail_data as destination_data,
                    dialplan_detail_order
                    FROM v_dialplan_details
                    WHERE dialplan_uuid = :dialplan_uuid
                    AND dialplan_detail_tag = 'action'
                    ORDER BY dialplan_detail_order ASC";
    $actions_params = ['dialplan_uuid' => $destination['dialplan_uuid']];
    $actions = $database->select($actions_sql, $actions_params, 'all');

    if (!empty($actions)) {
        $destination['destination_actions'] = $actions;
    } else {
        $destination['destination_actions'] = [];
    }
} else {
    $destination['destination_actions'] = [];
}

api_success($destination);
