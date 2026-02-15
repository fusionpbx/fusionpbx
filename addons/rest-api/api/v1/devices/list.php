<?php
require_once dirname(__DIR__) . '/auth.php';
validate_api_key();

// Pagination parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? min(100, max(1, intval($_GET['per_page']))) : 50;
$offset = ($page - 1) * $per_page;

// Build query
$sql = "SELECT device_uuid, device_address, device_vendor, device_model, device_enabled, device_label, device_description
        FROM v_devices
        WHERE domain_uuid = :domain_uuid
        ORDER BY device_address ASC
        LIMIT :limit OFFSET :offset";

$parameters = [
    'domain_uuid' => $domain_uuid,
    'limit' => $per_page,
    'offset' => $offset
];

$database = new database;
$devices = $database->select($sql, $parameters, 'all');

// Get total count for pagination
$count_sql = "SELECT COUNT(*) FROM v_devices WHERE domain_uuid = :domain_uuid";
$count_params = ['domain_uuid' => $domain_uuid];
$total = $database->select($count_sql, $count_params, 'column');

$pagination = [
    'page' => $page,
    'per_page' => $per_page,
    'total' => (int)$total,
    'total_pages' => ceil($total / $per_page)
];

api_success($devices ?? [], null, $pagination);
