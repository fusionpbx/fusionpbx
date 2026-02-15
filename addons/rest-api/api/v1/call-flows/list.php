<?php
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/base.php';
validate_api_key();

$params = get_pagination_params();
$page = $params['page'];
$per_page = $params['per_page'];
$offset = ($page - 1) * $per_page;

$sql = "SELECT call_flow_uuid, call_flow_name, call_flow_extension, call_flow_feature_code,
        call_flow_status, call_flow_enabled, call_flow_description
        FROM v_call_flows
        WHERE domain_uuid = :domain_uuid
        ORDER BY call_flow_extension ASC
        LIMIT :limit OFFSET :offset";

$parameters = [
    'domain_uuid' => $domain_uuid,
    'limit' => $per_page,
    'offset' => $offset
];

$database = new database;
$call_flows = $database->select($sql, $parameters, 'all');

$count_sql = "SELECT COUNT(*) FROM v_call_flows WHERE domain_uuid = :domain_uuid";
$total = $database->select($count_sql, ['domain_uuid' => $domain_uuid], 'column');

$pagination = [
    'page' => $page,
    'per_page' => $per_page,
    'total' => (int)$total,
    'total_pages' => ceil($total / $per_page)
];

api_success($call_flows ?? [], null, $pagination);
