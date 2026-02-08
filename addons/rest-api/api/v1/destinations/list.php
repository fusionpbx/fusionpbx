<?php
require_once dirname(__DIR__) . '/auth.php';
validate_api_key();

// Pagination parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? min(100, max(1, intval($_GET['per_page']))) : 50;
$offset = ($page - 1) * $per_page;

// Get total count
$count_sql = "SELECT COUNT(*) FROM v_destinations WHERE domain_uuid = :domain_uuid";
$count_params = ['domain_uuid' => $domain_uuid];
$database = new database;
$total = $database->select($count_sql, $count_params, 'column');

// Get destinations
$sql = "SELECT destination_uuid, destination_type, destination_number, destination_prefix,
        destination_context, destination_enabled, destination_description,
        destination_order, dialplan_uuid
        FROM v_destinations
        WHERE domain_uuid = :domain_uuid
        ORDER BY destination_number ASC
        LIMIT :limit OFFSET :offset";

$parameters = [
    'domain_uuid' => $domain_uuid,
    'limit' => $per_page,
    'offset' => $offset
];

$destinations = $database->select($sql, $parameters, 'all');

// Convert destination_enabled to boolean
if (!empty($destinations)) {
    foreach ($destinations as &$destination) {
        $destination['destination_enabled'] = ($destination['destination_enabled'] === 'true' || $destination['destination_enabled'] === true);
    }
}

$pagination = [
    'page' => $page,
    'per_page' => $per_page,
    'total' => (int)$total,
    'total_pages' => ceil($total / $per_page)
];

api_success($destinations ?? [], null, $pagination);
