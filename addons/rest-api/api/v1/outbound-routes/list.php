<?php
require_once dirname(__DIR__) . '/auth.php';
validate_api_key();

$app_uuid = '8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3'; // Outbound routes

$page = intval($_GET['page'] ?? 1);
$per_page = min(intval($_GET['per_page'] ?? 50), 100);
$offset = ($page - 1) * $per_page;

$database = new database;
$sql = "SELECT count(*) FROM v_dialplans WHERE domain_uuid = :domain_uuid AND app_uuid = :app_uuid";
$parameters = ['domain_uuid' => $domain_uuid, 'app_uuid' => $app_uuid];
$total = $database->select($sql, $parameters, 'column');

$sql = "SELECT dialplan_uuid, dialplan_name, dialplan_number, dialplan_context,
               dialplan_order, dialplan_enabled, dialplan_description
        FROM v_dialplans WHERE domain_uuid = :domain_uuid AND app_uuid = :app_uuid
        ORDER BY dialplan_order ASC LIMIT :limit OFFSET :offset";
$parameters['limit'] = $per_page;
$parameters['offset'] = $offset;
$database = new database;
$routes = $database->select($sql, $parameters, 'all');

api_success($routes ?? [], null, [
    'page' => $page, 'per_page' => $per_page,
    'total' => intval($total), 'total_pages' => ceil($total / $per_page)
]);
