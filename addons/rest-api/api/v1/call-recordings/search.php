<?php
/**
 * Advanced Call Recording Search
 *
 * POST /api/v1/call-recordings/search.php
 *
 * Request Body (JSON):
 * {
 *   "start_date": "2024-01-01",           // Optional: YYYY-MM-DD
 *   "end_date": "2024-12-31",             // Optional: YYYY-MM-DD
 *   "caller_id_number": "555-1234",       // Optional: Caller ID (partial match)
 *   "destination_number": "555-5678",     // Optional: Destination number (partial match)
 *   "caller_id_name": "John",             // Optional: Caller name (partial match)
 *   "min_length": 30,                     // Optional: Minimum length in seconds
 *   "max_length": 300,                    // Optional: Maximum length in seconds
 *   "call_direction": "inbound",          // Optional: inbound/outbound/local
 *   "sort": "call_recording_date",        // Optional: Sort field
 *   "order": "DESC",                      // Optional: ASC/DESC
 *   "page": 1,                            // Optional: Page number
 *   "per_page": 50                        // Optional: Items per page
 * }
 *
 * Returns paginated search results
 */

require_once __DIR__ . '/../base.php';

api_require_method('POST');

// Get request data
$data = get_request_data();

// Get pagination parameters
$page = isset($data['page']) ? max(1, intval($data['page'])) : 1;
$per_page = isset($data['per_page']) ? min(100, max(1, intval($data['per_page']))) : 50;

// Get sort parameters
$allowed_sort_fields = [
    'call_recording_date',
    'call_recording_length',
    'caller_id_number',
    'destination_number',
    'call_direction'
];
$sort_field = isset($data['sort']) && in_array($data['sort'], $allowed_sort_fields)
    ? $data['sort']
    : 'call_recording_date';
$sort_order = isset($data['order']) && strtoupper($data['order']) === 'ASC' ? 'ASC' : 'DESC';

// Build WHERE clause
$where = "WHERE domain_uuid = :domain_uuid";
$parameters = ['domain_uuid' => $domain_uuid];

// Date range filters
if (!empty($data['start_date'])) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['start_date'])) {
        api_error('VALIDATION_ERROR', 'Invalid start_date format. Use YYYY-MM-DD', 'start_date', 400);
    }
    $where .= " AND call_recording_date >= :start_date";
    $parameters['start_date'] = $data['start_date'] . ' 00:00:00';
}

if (!empty($data['end_date'])) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['end_date'])) {
        api_error('VALIDATION_ERROR', 'Invalid end_date format. Use YYYY-MM-DD', 'end_date', 400);
    }
    $where .= " AND call_recording_date <= :end_date";
    $parameters['end_date'] = $data['end_date'] . ' 23:59:59';
}

// Partial match filters (LIKE)
if (!empty($data['caller_id_number'])) {
    $where .= " AND caller_id_number LIKE :caller_id_number";
    $parameters['caller_id_number'] = '%' . $data['caller_id_number'] . '%';
}

if (!empty($data['destination_number'])) {
    $where .= " AND destination_number LIKE :destination_number";
    $parameters['destination_number'] = '%' . $data['destination_number'] . '%';
}

if (!empty($data['caller_id_name'])) {
    $where .= " AND caller_id_name LIKE :caller_id_name";
    $parameters['caller_id_name'] = '%' . $data['caller_id_name'] . '%';
}

// Length filters (duration is stored in call_recording_length)
if (isset($data['min_length']) && is_numeric($data['min_length'])) {
    $where .= " AND call_recording_length >= :min_length";
    $parameters['min_length'] = intval($data['min_length']);
}

if (isset($data['max_length']) && is_numeric($data['max_length'])) {
    $where .= " AND call_recording_length <= :max_length";
    $parameters['max_length'] = intval($data['max_length']);
}

// Call direction filter
if (!empty($data['call_direction'])) {
    if (!in_array($data['call_direction'], ['inbound', 'outbound', 'local'])) {
        api_error('VALIDATION_ERROR', 'Invalid call_direction. Must be "inbound", "outbound", or "local"', 'call_direction', 400);
    }
    $where .= " AND call_direction = :call_direction";
    $parameters['call_direction'] = $data['call_direction'];
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
        ORDER BY {$sort_field} {$sort_order}";

// Build count query
$count_sql = "SELECT COUNT(*) FROM view_call_recordings {$where}";

// Execute paginated query
$result = api_paginate($sql, $count_sql, $parameters, $page, $per_page);

api_success($result['items'], 'Search completed successfully', $result['pagination']);
