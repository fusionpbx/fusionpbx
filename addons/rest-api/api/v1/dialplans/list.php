<?php
require_once dirname(__DIR__) . '/auth.php';
validate_api_key();

// Pagination parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? min(100, max(1, intval($_GET['per_page']))) : 50;
$offset = ($page - 1) * $per_page;

// Build query with optional app_uuid filter
$sql = "SELECT dialplan_uuid, app_uuid, dialplan_name, dialplan_number, dialplan_context,
        dialplan_continue, dialplan_order, dialplan_enabled, dialplan_description
        FROM v_dialplans
        WHERE domain_uuid = :domain_uuid";

$parameters = ['domain_uuid' => $domain_uuid];

// Add app_uuid filter if provided
if (!empty($_GET['app_uuid']) && is_uuid($_GET['app_uuid'])) {
    $sql .= " AND app_uuid = :app_uuid";
    $parameters['app_uuid'] = $_GET['app_uuid'];
}

$sql .= " ORDER BY dialplan_order ASC, dialplan_name ASC
        LIMIT :limit OFFSET :offset";

$parameters['limit'] = $per_page;
$parameters['offset'] = $offset;

$database = new database;
$dialplans = $database->select($sql, $parameters, 'all');

// Get total count for pagination
$count_sql = "SELECT COUNT(*) FROM v_dialplans WHERE domain_uuid = :domain_uuid";
$count_params = ['domain_uuid' => $domain_uuid];

if (!empty($_GET['app_uuid']) && is_uuid($_GET['app_uuid'])) {
    $count_sql .= " AND app_uuid = :app_uuid";
    $count_params['app_uuid'] = $_GET['app_uuid'];
}

$total = $database->select($count_sql, $count_params, 'column');

$pagination = [
    'page' => $page,
    'per_page' => $per_page,
    'total' => (int)$total,
    'total_pages' => ceil($total / $per_page)
];

api_success($dialplans ?? [], null, $pagination);
