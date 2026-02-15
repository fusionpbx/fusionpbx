<?php
require_once dirname(__DIR__) . '/auth.php';
validate_api_key();

$dialplan_uuid = get_uuid_from_path();
if (empty($dialplan_uuid)) {
    api_error('MISSING_UUID', 'Dialplan UUID is required');
}

// Get dialplan details
$sql = "SELECT * FROM v_dialplans WHERE domain_uuid = :domain_uuid AND dialplan_uuid = :dialplan_uuid";
$parameters = [
    'domain_uuid' => $domain_uuid,
    'dialplan_uuid' => $dialplan_uuid
];

$database = new database;
$dialplan = $database->select($sql, $parameters, 'row');

if (empty($dialplan)) {
    api_error('NOT_FOUND', 'Dialplan not found', null, 404);
}

// Get dialplan details (conditions and actions)
$details_sql = "SELECT dialplan_detail_uuid, dialplan_detail_tag, dialplan_detail_type,
                dialplan_detail_data, dialplan_detail_break, dialplan_detail_inline,
                dialplan_detail_group, dialplan_detail_order
                FROM v_dialplan_details
                WHERE domain_uuid = :domain_uuid AND dialplan_uuid = :dialplan_uuid
                ORDER BY dialplan_detail_order ASC";

$details = $database->select($details_sql, $parameters, 'all');
if (!empty($details)) {
    $dialplan['details'] = $details;
}

api_success($dialplan);
