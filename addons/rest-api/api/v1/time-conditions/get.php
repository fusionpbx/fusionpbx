<?php
require_once dirname(__DIR__) . '/auth.php';
validate_api_key();

$app_uuid = '4b821450-926b-175a-af93-a03c441f8c30'; // Time conditions
$dialplan_uuid = $_GET['id'] ?? '';

if (!is_uuid($dialplan_uuid)) {
    api_error('VALIDATION_ERROR', 'Valid dialplan_uuid is required', 'id');
}

$database = new database;
$sql = "SELECT dialplan_uuid, dialplan_name, dialplan_number, dialplan_context,
               dialplan_continue, dialplan_order, dialplan_enabled, dialplan_description,
               dialplan_xml, hostname
        FROM v_dialplans
        WHERE domain_uuid = :domain_uuid AND app_uuid = :app_uuid AND dialplan_uuid = :dialplan_uuid";
$parameters = [
    'domain_uuid' => $domain_uuid,
    'app_uuid' => $app_uuid,
    'dialplan_uuid' => $dialplan_uuid
];
$route = $database->select($sql, $parameters, 'row');

if (!$route) {
    api_error('NOT_FOUND', 'Time condition not found', null, 404);
}

// Get dialplan details
$sql = "SELECT dialplan_detail_uuid, dialplan_detail_tag, dialplan_detail_type,
               dialplan_detail_data, dialplan_detail_break, dialplan_detail_inline,
               dialplan_detail_group, dialplan_detail_order, dialplan_detail_enabled
        FROM v_dialplan_details
        WHERE dialplan_uuid = :dialplan_uuid
        ORDER BY dialplan_detail_order ASC";
$parameters = ['dialplan_uuid' => $dialplan_uuid];
$route['details'] = $database->select($sql, $parameters, 'all') ?? [];

api_success($route);
