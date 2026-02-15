<?php
/**
 * Email Queue List API
 * GET /api/v1/queues/email/list.php
 *
 * List email queue items with pagination and filtering
 *
 * Query Parameters:
 * - page: Page number (default: 1)
 * - per_page: Items per page (default: 50, max: 100)
 * - status: Filter by status (pending, sent, failed)
 * - date_from: Start date filter (YYYY-MM-DD)
 * - date_to: End date filter (YYYY-MM-DD)
 * - sort: Sort field (email_date, email_status, email_retry_count)
 * - order: Sort order (ASC, DESC)
 */

require_once __DIR__ . '/../../base.php';

// Only allow GET method
api_require_method('GET');

// Get pagination parameters
$pagination_params = get_pagination_params();
$page = $pagination_params['page'];
$per_page = $pagination_params['per_page'];

// Get filter parameters
$allowed_filters = ['email_status'];
$filters = get_filter_params($allowed_filters);

// Get sort parameters
$sort_params = get_sort_params('email_date', 'DESC', ['email_date', 'email_status', 'email_retry_count']);

// Build WHERE clause
$where_conditions = ['domain_uuid = :domain_uuid'];
$parameters = ['domain_uuid' => $domain_uuid];

// Status filter
if (!empty($filters['email_status'])) {
    $where_conditions[] = 'email_status = :email_status';
    $parameters['email_status'] = $filters['email_status'];
}

// Date range filter
if (!empty($_GET['date_from'])) {
    $where_conditions[] = 'email_date >= :date_from';
    $parameters['date_from'] = $_GET['date_from'] . ' 00:00:00';
}

if (!empty($_GET['date_to'])) {
    $where_conditions[] = 'email_date <= :date_to';
    $parameters['date_to'] = $_GET['date_to'] . ' 23:59:59';
}

$where_clause = implode(' AND ', $where_conditions);

// Build queries
$sql = "SELECT
    email_queue_uuid,
    domain_uuid,
    email_date,
    email_from,
    email_to,
    email_subject,
    email_body,
    email_status,
    email_retry_count
FROM v_email_queue
WHERE {$where_clause}
ORDER BY {$sort_params['field']} {$sort_params['order']}";

$count_sql = "SELECT COUNT(*) FROM v_email_queue WHERE {$where_clause}";

// Execute paginated query
$result = api_paginate($sql, $count_sql, $parameters, $page, $per_page);

// Return success response
api_success($result['items'], null, $result['pagination']);
