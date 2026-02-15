<?php
require_once __DIR__ . '/../../base.php';
api_require_method('GET');

// Pagination
$params = get_pagination_params();
$page = $params['page'];
$per_page = $params['per_page'];

// Filters
$filters = get_filter_params(['fax_uuid', 'fax_success']);
$filter_data = api_build_filters($filters, ['fax_uuid', 'fax_success']);

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
$sql = "SELECT fax_log_uuid, fax_uuid, fax_success, fax_result_code, fax_result_text,
        fax_file, fax_ecm_used, fax_local_station_id, fax_remote_station_id,
        fax_document_transferred_pages, fax_document_total_pages,
        fax_image_resolution, fax_image_size, fax_bad_rows, fax_transfer_rate,
        fax_log_date, fax_epoch
        FROM v_fax_logs
        WHERE domain_uuid = :domain_uuid" . $filter_data['where'] . $date_where . "
        ORDER BY fax_epoch DESC";

$count_sql = "SELECT COUNT(*) FROM v_fax_logs
              WHERE domain_uuid = :domain_uuid" . $filter_data['where'] . $date_where;

$parameters = array_merge(
    ['domain_uuid' => $domain_uuid],
    $filter_data['parameters'],
    $date_params
);

$result = api_paginate($sql, $count_sql, $parameters, $page, $per_page);

api_success($result['items'], null, $result['pagination']);
