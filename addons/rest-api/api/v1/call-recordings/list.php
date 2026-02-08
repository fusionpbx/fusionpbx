<?php
/**
 * List Call Recordings with Pagination and Filtering
 *
 * GET /api/v1/call-recordings/list.php
 *
 * Query Parameters:
 * - page: Page number (default: 1)
 * - per_page: Items per page (default: 50, max: 100)
 * - start_date: Filter by start date (YYYY-MM-DD)
 * - end_date: Filter by end date (YYYY-MM-DD)
 * - caller_id_number: Filter by caller ID number
 * - destination_number: Filter by destination number
 * - sort: Sort field (call_recording_date, call_recording_length, caller_id_number, destination_number)
 * - order: Sort order (ASC/DESC, default: DESC)
 */

require_once __DIR__ . '/../base.php';

api_require_method('GET');

// Get pagination parameters
$pagination = get_pagination_params();

// Get filter parameters
$allowed_filters = [
    'caller_id_number',
    'destination_number'
];
$filters = get_filter_params($allowed_filters);

// Get sort parameters
$allowed_sort_fields = [
    'call_recording_date',
    'call_recording_length',
    'caller_id_number',
    'destination_number'
];
$sort = get_sort_params('call_recording_date', 'DESC', $allowed_sort_fields);

// Build WHERE clause
$where = "WHERE domain_uuid = :domain_uuid";
$parameters = ['domain_uuid' => $domain_uuid];

// Date range filters
if (!empty($_GET['start_date'])) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['start_date'])) {
        api_error('VALIDATION_ERROR', 'Invalid start_date format. Use YYYY-MM-DD', 'start_date', 400);
    }
    $where .= " AND call_recording_date >= :start_date";
    $parameters['start_date'] = $_GET['start_date'] . ' 00:00:00';
}

if (!empty($_GET['end_date'])) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['end_date'])) {
        api_error('VALIDATION_ERROR', 'Invalid end_date format. Use YYYY-MM-DD', 'end_date', 400);
    }
    $where .= " AND call_recording_date <= :end_date";
    $parameters['end_date'] = $_GET['end_date'] . ' 23:59:59';
}

// Apply filters
foreach ($filters as $field => $value) {
    $where .= " AND {$field} = :{$field}";
    $parameters[$field] = $value;
}

// Build main query
$sql = "SELECT
            call_recording_uuid,
            call_recording_name,
            call_recording_date,
            call_direction,
            caller_id_name,
            caller_id_number,
            caller_destination,
            destination_number,
            call_recording_length
        FROM view_call_recordings
        {$where}
        ORDER BY {$sort['field']} {$sort['order']}";

// Build count query
$count_sql = "SELECT COUNT(*) FROM view_call_recordings {$where}";

// Execute paginated query
$result = api_paginate($sql, $count_sql, $parameters, $pagination['page'], $pagination['per_page']);

api_success($result['items'], null, $result['pagination']);
