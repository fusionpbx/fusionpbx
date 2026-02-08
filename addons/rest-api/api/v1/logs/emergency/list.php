<?php
/**
 * FusionPBX REST API - Emergency/E911 Logs List
 *
 * GET /api/v1/logs/emergency/list.php
 *
 * Query Parameters:
 * - page: Page number (default: 1)
 * - per_page: Items per page (default: 50, max: 100)
 * - start_date: Filter by start date (YYYY-MM-DD)
 * - end_date: Filter by end date (YYYY-MM-DD)
 * - extension: Filter by extension
 * - event: Filter by event type
 */

require_once __DIR__ . '/../../base.php';
api_require_method('GET');

// Get pagination parameters
$pagination = get_pagination_params();
$page = $pagination['page'];
$per_page = $pagination['per_page'];

// Build WHERE clause
$where = "WHERE domain_uuid = :domain_uuid";
$parameters = ['domain_uuid' => $domain_uuid];

// Date filters
if (!empty($_GET['start_date'])) {
    $where .= " AND insert_date >= :start_date";
    $parameters['start_date'] = $_GET['start_date'] . ' 00:00:00';
}
if (!empty($_GET['end_date'])) {
    $where .= " AND insert_date <= :end_date";
    $parameters['end_date'] = $_GET['end_date'] . ' 23:59:59';
}

// Extension filter
if (!empty($_GET['extension'])) {
    $where .= " AND extension = :extension";
    $parameters['extension'] = $_GET['extension'];
}

// Event filter
if (!empty($_GET['event'])) {
    $where .= " AND LOWER(event) LIKE :event";
    $parameters['event'] = '%' . strtolower($_GET['event']) . '%';
}

// Build SQL queries
$count_sql = "SELECT COUNT(*) FROM v_emergency_logs " . $where;

$sql = "SELECT
    emergency_log_uuid,
    domain_uuid,
    extension,
    event,
    insert_date,
    insert_user
FROM v_emergency_logs " . $where . "
ORDER BY insert_date DESC";

// Execute paginated query
$result = api_paginate($sql, $count_sql, $parameters, $page, $per_page);

api_success($result['items'], null, $result['pagination']);
