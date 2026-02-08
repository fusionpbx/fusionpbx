<?php
/**
 * FusionPBX REST API - Database Audit Transactions List
 *
 * GET /api/v1/logs/audit/list.php
 *
 * Query Parameters:
 * - page: Page number (default: 1)
 * - per_page: Items per page (default: 50, max: 100)
 * - start_date: Filter by start date (YYYY-MM-DD)
 * - end_date: Filter by end date (YYYY-MM-DD)
 * - app_name: Filter by application name
 * - transaction_type: Filter by transaction type
 * - user_uuid: Filter by user UUID
 */

require_once __DIR__ . '/../../base.php';
api_require_method('GET');

// Get pagination parameters
$pagination = get_pagination_params();
$page = $pagination['page'];
$per_page = $pagination['per_page'];

// Build WHERE clause
$where = "WHERE (domain_uuid = :domain_uuid OR domain_uuid IS NULL)";
$parameters = ['domain_uuid' => $domain_uuid];

// Date filters
if (!empty($_GET['start_date'])) {
    $where .= " AND transaction_date >= :start_date";
    $parameters['start_date'] = $_GET['start_date'] . ' 00:00:00';
}
if (!empty($_GET['end_date'])) {
    $where .= " AND transaction_date <= :end_date";
    $parameters['end_date'] = $_GET['end_date'] . ' 23:59:59';
}

// Application name filter
if (!empty($_GET['app_name'])) {
    $where .= " AND app_name = :app_name";
    $parameters['app_name'] = $_GET['app_name'];
}

// Transaction type filter
if (!empty($_GET['transaction_type'])) {
    $where .= " AND transaction_type = :transaction_type";
    $parameters['transaction_type'] = $_GET['transaction_type'];
}

// User UUID filter
if (!empty($_GET['user_uuid'])) {
    $where .= " AND user_uuid = :user_uuid";
    $parameters['user_uuid'] = $_GET['user_uuid'];
}

// Build SQL queries
$count_sql = "SELECT COUNT(*) FROM v_database_transactions " . $where;

$sql = "SELECT
    database_transaction_uuid,
    domain_uuid,
    user_uuid,
    app_name,
    app_uuid,
    transaction_code,
    transaction_address,
    transaction_type,
    transaction_date,
    transaction_result
FROM v_database_transactions " . $where . "
ORDER BY transaction_date DESC";

// Execute paginated query
$result = api_paginate($sql, $count_sql, $parameters, $page, $per_page);

api_success($result['items'], null, $result['pagination']);
