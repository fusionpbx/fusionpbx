<?php
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/base.php';
validate_api_key();

$params = get_pagination_params();
$page = $params['page'];
$per_page = $params['per_page'];
$offset = ($page - 1) * $per_page;

$sql = "SELECT recording_uuid, recording_filename, recording_name,
        recording_description, insert_date
        FROM v_recordings
        WHERE domain_uuid = :domain_uuid
        ORDER BY recording_name ASC
        LIMIT :limit OFFSET :offset";

$parameters = [
    'domain_uuid' => $domain_uuid,
    'limit' => $per_page,
    'offset' => $offset
];

$database = new database;
$recordings = $database->select($sql, $parameters, 'all');

$count_sql = "SELECT COUNT(*) FROM v_recordings WHERE domain_uuid = :domain_uuid";
$total = $database->select($count_sql, ['domain_uuid' => $domain_uuid], 'column');

$pagination = [
    'page' => $page,
    'per_page' => $per_page,
    'total' => (int)$total,
    'total_pages' => ceil($total / $per_page)
];

api_success($recordings ?? [], null, $pagination);