<?php
/**
 * FusionPBX REST API - Event Guard Security Logs List
 *
 * GET /api/v1/logs/security/list.php
 *
 * Query Parameters:
 * - page: Page number (default: 1)
 * - per_page: Items per page (default: 50, max: 100)
 * - start_date: Filter by start date (YYYY-MM-DD)
 * - end_date: Filter by end date (YYYY-MM-DD)
 * - ip_address: Filter by IP address
 * - log_status: Filter by log status (blocked, allowed)
 * - filter: Filter by event type (sip-auth-ip, sip-auth-fail)
 *
 * WARNING: Security logs (v_event_guard_logs) are system-wide and do not contain
 * domain_uuid. This endpoint returns global data. For production deployments,
 * consider restricting this endpoint to superadmin users only.
 */

require_once __DIR__ . '/../../base.php';
api_require_method('GET');

// Get pagination parameters
$pagination = get_pagination_params();
$page = $pagination['page'];
$per_page = $pagination['per_page'];

// Build WHERE clause - event_guard_logs is global, no domain filtering
$where = "WHERE 1=1";
$parameters = [];

// Date filters
if (!empty($_GET['start_date'])) {
    $where .= " AND log_date >= :start_date";
    $parameters['start_date'] = $_GET['start_date'] . ' 00:00:00';
}
if (!empty($_GET['end_date'])) {
    $where .= " AND log_date <= :end_date";
    $parameters['end_date'] = $_GET['end_date'] . ' 23:59:59';
}

// IP address filter
if (!empty($_GET['ip_address'])) {
    $where .= " AND ip_address = :ip_address";
    $parameters['ip_address'] = $_GET['ip_address'];
}

// Log status filter
if (!empty($_GET['log_status']) && in_array($_GET['log_status'], ['blocked', 'allowed'])) {
    $where .= " AND log_status = :log_status";
    $parameters['log_status'] = $_GET['log_status'];
}

// Event filter type
if (!empty($_GET['filter']) && in_array($_GET['filter'], ['sip-auth-ip', 'sip-auth-fail'])) {
    $where .= " AND filter = :filter";
    $parameters['filter'] = $_GET['filter'];
}

// Extension filter
if (!empty($_GET['extension'])) {
    $where .= " AND extension = :extension";
    $parameters['extension'] = $_GET['extension'];
}

// Build SQL queries
$count_sql = "SELECT COUNT(*) FROM v_event_guard_logs " . $where;

$sql = "SELECT
    event_guard_log_uuid,
    hostname,
    log_date,
    filter,
    ip_address,
    extension,
    user_agent,
    log_status
FROM v_event_guard_logs " . $where . "
ORDER BY log_date DESC";

// Execute paginated query
$result = api_paginate($sql, $count_sql, $parameters, $page, $per_page);

api_success($result['items'], null, $result['pagination']);
