<?php
require_once __DIR__ . '/../../base.php';
validate_api_key();

// Pagination
$params = get_pagination_params();
$page = $params['page'];
$per_page = $params['per_page'];

// Build query
$sql = "SELECT conference_center_uuid, conference_center_name, conference_center_extension,
        conference_center_pin_length, conference_center_greeting, conference_center_enabled,
        conference_center_description
        FROM v_conference_centers
        WHERE domain_uuid = :domain_uuid
        ORDER BY conference_center_extension ASC";

$count_sql = "SELECT COUNT(*) FROM v_conference_centers WHERE domain_uuid = :domain_uuid";

$parameters = ['domain_uuid' => $domain_uuid];

$result = api_paginate($sql, $count_sql, $parameters, $page, $per_page);

api_success($result['items'], null, $result['pagination']);
