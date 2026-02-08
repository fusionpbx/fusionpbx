<?php
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/base.php';
validate_api_key();

// Pagination
$params = get_pagination_params();
$page = $params['page'];
$per_page = $params['per_page'];

// Build query - access controls can be domain-specific or global (domain_uuid IS NULL)
$sql = "SELECT access_control_uuid, access_control_name, access_control_default, access_control_description, domain_uuid
        FROM v_access_controls
        WHERE domain_uuid = :domain_uuid OR domain_uuid IS NULL
        ORDER BY access_control_name ASC";

$count_sql = "SELECT COUNT(*) FROM v_access_controls WHERE domain_uuid = :domain_uuid OR domain_uuid IS NULL";

$parameters = ['domain_uuid' => $domain_uuid];

$result = api_paginate($sql, $count_sql, $parameters, $page, $per_page);

api_success($result['items'], null, $result['pagination']);
