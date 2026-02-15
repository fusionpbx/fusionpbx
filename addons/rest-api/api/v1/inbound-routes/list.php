<?php
require_once dirname(__DIR__) . '/auth.php';
validate_api_key();

$app_uuid = 'c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4'; // Inbound routes

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
