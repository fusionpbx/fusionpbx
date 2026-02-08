<?php
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/base.php';
validate_api_key();

$params = get_pagination_params();
$page = $params['page'];
$per_page = $params['per_page'];
$offset = ($page - 1) * $per_page;

$sql = "SELECT ivr_menu_uuid, ivr_menu_name, ivr_menu_extension, ivr_menu_language,
        ivr_menu_timeout, ivr_menu_exit_app, ivr_menu_enabled, ivr_menu_description
        FROM v_ivr_menus
        WHERE domain_uuid = :domain_uuid
        ORDER BY ivr_menu_extension ASC
        LIMIT :limit OFFSET :offset";

$parameters = [
    'domain_uuid' => $domain_uuid,
    'limit' => $per_page,
    'offset' => $offset
];

$database = new database;
$ivr_menus = $database->select($sql, $parameters, 'all');

$count_sql = "SELECT COUNT(*) FROM v_ivr_menus WHERE domain_uuid = :domain_uuid";
$total = $database->select($count_sql, ['domain_uuid' => $domain_uuid], 'column');

$pagination = [
    'page' => $page,
    'per_page' => $per_page,
    'total' => (int)$total,
    'total_pages' => ceil($total / $per_page)
];

api_success($ivr_menus ?? [], null, $pagination);
