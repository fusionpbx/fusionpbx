<?php
require_once dirname(__DIR__) . '/base.php';

api_require_method('GET');

$database = new database;

// Support filtering
$sql = "SELECT domain_uuid, domain_name, domain_enabled, domain_description FROM v_domains";
$params = [];
$where = [];

if (!empty($_GET['domain_name'])) {
    $where[] = "domain_name LIKE :domain_name";
    $params['domain_name'] = '%' . $_GET['domain_name'] . '%';
}
if (isset($_GET['domain_enabled'])) {
    $where[] = "domain_enabled = :domain_enabled";
    $params['domain_enabled'] = $_GET['domain_enabled'];
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}

$sql .= " ORDER BY domain_name ASC";

// Pagination
$pagination = get_pagination_params();
$count_sql = "SELECT count(*) FROM v_domains";
if (!empty($where)) {
    $count_sql .= " WHERE " . implode(' AND ', $where);
}
$total = (int) $database->select($count_sql, $params, 'column');

$sql .= " LIMIT :limit OFFSET :offset";
$params['limit'] = $pagination['per_page'];
$params['offset'] = ($pagination['page'] - 1) * $pagination['per_page'];

$domains = $database->select($sql, $params, 'all') ?: [];

api_success($domains, null, [
    'page' => $pagination['page'],
    'per_page' => $pagination['per_page'],
    'total' => $total,
    'total_pages' => ceil($total / $pagination['per_page'])
]);
