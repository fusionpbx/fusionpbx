<?php
require_once __DIR__ . '/../../base.php';
validate_api_key();

// Pagination
$params = get_pagination_params();
$page = $params['page'];
$per_page = $params['per_page'];

// Build query - profiles may be global (no domain_uuid filter if null)
$sql = "SELECT conference_profile_uuid, profile_name, profile_description, enabled
        FROM v_conference_profiles
        WHERE (domain_uuid = :domain_uuid OR domain_uuid IS NULL)
        ORDER BY profile_name ASC";

$count_sql = "SELECT COUNT(*) FROM v_conference_profiles
              WHERE (domain_uuid = :domain_uuid OR domain_uuid IS NULL)";

$parameters = ['domain_uuid' => $domain_uuid];

$result = api_paginate($sql, $count_sql, $parameters, $page, $per_page);

api_success($result['items'], null, $result['pagination']);
