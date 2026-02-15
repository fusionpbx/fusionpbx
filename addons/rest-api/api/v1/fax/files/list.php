<?php
require_once __DIR__ . '/../../base.php';
api_require_method('GET');

// Pagination
$params = get_pagination_params();
$page = $params['page'];
$per_page = $params['per_page'];

// Filters
$filters = get_filter_params(['fax_uuid', 'fax_mode', 'fax_status']);
$filter_data = api_build_filters($filters, ['fax_uuid', 'fax_mode', 'fax_status']);

// Date range filters
$date_where = '';
$date_params = [];

if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    $date_where .= ' AND fax_epoch >= :date_from';
    $date_params['date_from'] = strtotime($_GET['date_from']);
}

if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    $date_where .= ' AND fax_epoch <= :date_to';
    $date_params['date_to'] = strtotime($_GET['date_to'] . ' 23:59:59');
}

// Build query
$sql = "SELECT fax_file_uuid, fax_uuid, fax_mode, fax_file_type, fax_file_path,
        fax_caller_id_name, fax_caller_id_number, fax_destination,
        fax_date, fax_time, fax_epoch, fax_status, fax_retry_count
        FROM v_fax_files
        WHERE domain_uuid = :domain_uuid" . $filter_data['where'] . $date_where . "
        ORDER BY fax_epoch DESC";

$count_sql = "SELECT COUNT(*) FROM v_fax_files
              WHERE domain_uuid = :domain_uuid" . $filter_data['where'] . $date_where;

$parameters = array_merge(
    ['domain_uuid' => $domain_uuid],
    $filter_data['parameters'],
    $date_params
);

$result = api_paginate($sql, $count_sql, $parameters, $page, $per_page);

api_success($result['items'], null, $result['pagination']);
