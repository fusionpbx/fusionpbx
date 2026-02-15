<?php
/**
 * List extensions with call forward settings
 * GET /api/v1/call-forward/list.php
 *
 * Query Parameters:
 * - page: Page number (default: 1)
 * - per_page: Items per page (default: 50, max: 100)
 * - extension: Filter by extension number
 * - extension_uuid: Filter by extension UUID
 */

require_once __DIR__ . '/../base.php';

api_require_method('GET');

// Get pagination parameters
$pagination_params = get_pagination_params();
$page = $pagination_params['page'];
$per_page = $pagination_params['per_page'];

// Get filter parameters
$filters = get_filter_params(['extension', 'extension_uuid']);

// Build WHERE clause
$filter_result = api_build_filters($filters, ['extension', 'extension_uuid']);
$where_clause = $filter_result['where'];
$filter_params = $filter_result['parameters'];

// Build query
$sql = "SELECT
    extension_uuid,
    extension,
    number_alias,
    effective_caller_id_name,
    forward_all_destination,
    forward_all_enabled,
    forward_busy_destination,
    forward_busy_enabled,
    forward_no_answer_destination,
    forward_no_answer_enabled,
    forward_user_not_registered_destination,
    forward_user_not_registered_enabled,
    enabled,
    description
FROM v_extensions
WHERE domain_uuid = :domain_uuid" . $where_clause . "
ORDER BY extension ASC
LIMIT :limit OFFSET :offset";

$count_sql = "SELECT COUNT(*) FROM v_extensions WHERE domain_uuid = :domain_uuid" . $where_clause;

// Merge parameters
$parameters = array_merge(
    ['domain_uuid' => $domain_uuid],
    $filter_params
);

// Execute pagination
$result = api_paginate($sql, $count_sql, $parameters, $page, $per_page);

// Format response data
$extensions = array_map(function($ext) {
    return [
        'extension_uuid' => $ext['extension_uuid'],
        'extension' => $ext['extension'],
        'number_alias' => $ext['number_alias'],
        'caller_id_name' => $ext['effective_caller_id_name'],
        'enabled' => $ext['enabled'] === 'true',
        'description' => $ext['description'],
        'forward_settings' => [
            'all' => [
                'enabled' => $ext['forward_all_enabled'] === 'true',
                'destination' => $ext['forward_all_destination']
            ],
            'busy' => [
                'enabled' => $ext['forward_busy_enabled'] === 'true',
                'destination' => $ext['forward_busy_destination']
            ],
            'no_answer' => [
                'enabled' => $ext['forward_no_answer_enabled'] === 'true',
                'destination' => $ext['forward_no_answer_destination']
            ],
            'user_not_registered' => [
                'enabled' => $ext['forward_user_not_registered_enabled'] === 'true',
                'destination' => $ext['forward_user_not_registered_destination']
            ]
        ]
    ];
}, $result['items']);

api_success($extensions, null, $result['pagination']);
