<?php
/**
 * List Call Block Rules
 * GET /api/v1/call-block/list.php
 *
 * Query Parameters:
 * - page: Page number (default: 1)
 * - per_page: Items per page (default: 50, max: 100)
 * - enabled: Filter by enabled status (true/false)
 * - search: Search by number or name
 * - sort: Sort field (call_block_name, call_block_number, call_block_count)
 * - order: Sort order (ASC/DESC)
 */

require_once __DIR__ . '/../base.php';

api_require_method('GET');

// Get pagination parameters
$params = get_pagination_params();
$page = $params['page'];
$per_page = $params['per_page'];

// Get sort parameters
$sort_params = get_sort_params('call_block_number', 'ASC', [
    'call_block_name',
    'call_block_number',
    'call_block_count',
    'call_block_enabled'
]);

// Build WHERE clause
$where_conditions = ['domain_uuid = :domain_uuid'];
$parameters = ['domain_uuid' => $domain_uuid];

// Filter by enabled status
if (isset($_GET['enabled']) && $_GET['enabled'] !== '') {
    $enabled_value = filter_var($_GET['enabled'], FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
    $where_conditions[] = "call_block_enabled = :enabled";
    $parameters['enabled'] = $enabled_value;
}

// Search by number or name
if (!empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $where_conditions[] = "(call_block_number LIKE :search OR call_block_name LIKE :search)";
    $parameters['search'] = $search;
}

$where_clause = implode(' AND ', $where_conditions);

// Build queries
$sql = "SELECT call_block_uuid, call_block_name, call_block_number,
        call_block_count, call_block_action, call_block_enabled, call_block_description
        FROM v_call_block
        WHERE {$where_clause}
        ORDER BY {$sort_params['field']} {$sort_params['order']}";

$count_sql = "SELECT COUNT(*) FROM v_call_block WHERE {$where_clause}";

// Execute with pagination
$result = api_paginate($sql, $count_sql, $parameters, $page, $per_page);

// Convert enabled values to boolean for JSON
foreach ($result['items'] as &$item) {
    $item['call_block_enabled'] = $item['call_block_enabled'] === 'true';
}

api_success($result['items'], null, $result['pagination']);
