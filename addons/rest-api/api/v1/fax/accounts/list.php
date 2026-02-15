<?php
require_once __DIR__ . '/../../base.php';
api_require_method('GET');

// Pagination
$params = get_pagination_params();
$page = $params['page'];
$per_page = $params['per_page'];

// Filters
$filters = get_filter_params(['fax_extension', 'fax_name', 'fax_enabled']);
$filter_data = api_build_filters($filters, ['fax_extension', 'fax_name', 'fax_enabled']);

// Build query
$sql = "SELECT fax_uuid, fax_extension, fax_name, fax_email, fax_pin_number,
        fax_caller_id_name, fax_caller_id_number, fax_forward_number,
        fax_description, fax_send_channels
        FROM v_fax
        WHERE domain_uuid = :domain_uuid" . $filter_data['where'] . "
        ORDER BY fax_extension ASC";

$count_sql = "SELECT COUNT(*) FROM v_fax WHERE domain_uuid = :domain_uuid" . $filter_data['where'];

$parameters = array_merge(['domain_uuid' => $domain_uuid], $filter_data['parameters']);

$result = api_paginate($sql, $count_sql, $parameters, $page, $per_page);

api_success($result['items'], null, $result['pagination']);
