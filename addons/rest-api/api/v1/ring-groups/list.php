<?php
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/base.php';
validate_api_key();

// Pagination
$params = get_pagination_params();
$page = $params['page'];
$per_page = $params['per_page'];
$offset = ($page - 1) * $per_page;

// Build query
$sql = "SELECT ring_group_uuid, ring_group_name, ring_group_extension, ring_group_strategy,
        ring_group_timeout_app, ring_group_timeout_data, ring_group_cid_name_prefix,
        ring_group_cid_number_prefix, ring_group_enabled, ring_group_description
        FROM v_ring_groups
        WHERE domain_uuid = :domain_uuid
        ORDER BY ring_group_extension ASC
        LIMIT :limit OFFSET :offset";

$parameters = [
    'domain_uuid' => $domain_uuid,
    'limit' => $per_page,
    'offset' => $offset
];

$database = new database;
$ring_groups = $database->select($sql, $parameters, 'all');

// Get total count
$count_sql = "SELECT COUNT(*) FROM v_ring_groups WHERE domain_uuid = :domain_uuid";
$total = $database->select($count_sql, ['domain_uuid' => $domain_uuid], 'column');

$pagination = [
    'page' => $page,
    'per_page' => $per_page,
    'total' => (int)$total,
    'total_pages' => ceil($total / $per_page)
];

api_success($ring_groups ?? [], null, $pagination);
