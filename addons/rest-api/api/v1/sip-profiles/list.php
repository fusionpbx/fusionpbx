<?php
require_once __DIR__ . '/../base.php';

// Pagination
$params = get_pagination_params();
$page = $params['page'];
$per_page = $params['per_page'];

// Filters
$filters = get_filter_params(['sip_profile_enabled']);
$filter_data = api_build_filters($filters, ['sip_profile_enabled']);

// Sort
$sort = get_sort_params('sip_profile_name', 'ASC', ['sip_profile_name', 'sip_profile_hostname']);

// Build query
$sql = "SELECT sip_profile_uuid, sip_profile_name, sip_profile_hostname,
        sip_profile_enabled, sip_profile_description
        FROM v_sip_profiles
        WHERE 1=1" . $filter_data['where'] . "
        ORDER BY {$sort['field']} {$sort['order']}";

$count_sql = "SELECT COUNT(*) FROM v_sip_profiles WHERE 1=1" . $filter_data['where'];

// Execute query with pagination
$result = api_paginate($sql, $count_sql, $filter_data['parameters'], $page, $per_page);

api_success($result['items'], null, $result['pagination']);
