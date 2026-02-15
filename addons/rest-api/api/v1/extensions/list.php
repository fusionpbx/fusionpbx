<?php
require_once dirname(__DIR__) . '/auth.php';
validate_api_key();

// Pagination parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? min(100, max(1, intval($_GET['per_page']))) : 50;
$offset = ($page - 1) * $per_page;

// Build query
$sql = "SELECT extension_uuid, extension, number_alias, effective_caller_id_name, effective_caller_id_number,
        outbound_caller_id_name, outbound_caller_id_number, user_context, enabled, description
        FROM v_extensions
        WHERE domain_uuid = :domain_uuid
        ORDER BY extension ASC
        LIMIT :limit OFFSET :offset";

$parameters = [
    'domain_uuid' => $domain_uuid,
    'limit' => $per_page,
    'offset' => $offset
];

$database = new database;
$extensions = $database->select($sql, $parameters, 'all');

// Get total count for pagination
$count_sql = "SELECT COUNT(*) FROM v_extensions WHERE domain_uuid = :domain_uuid";
$count_params = ['domain_uuid' => $domain_uuid];
$total = $database->select($count_sql, $count_params, 'column');

$pagination = [
    'page' => $page,
    'per_page' => $per_page,
    'total' => (int)$total,
    'total_pages' => ceil($total / $per_page)
];

api_success($extensions ?? [], null, $pagination);
