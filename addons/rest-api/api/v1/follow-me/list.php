<?php
require_once dirname(__DIR__) . '/base.php';
validate_api_key();
api_require_method('GET');

// Get pagination parameters
$params = get_pagination_params();
$page = $params['page'];
$per_page = $params['per_page'];

// Build query
$sql = "SELECT follow_me_uuid, follow_me_enabled, cid_name_prefix, cid_number_prefix,
        follow_me_caller_id_uuid, follow_me_toll_allow, follow_me_ringback,
        follow_me_ignore_busy
        FROM v_follow_me
        WHERE domain_uuid = :domain_uuid
        ORDER BY follow_me_uuid ASC";

$count_sql = "SELECT COUNT(*) FROM v_follow_me WHERE domain_uuid = :domain_uuid";

$parameters = ['domain_uuid' => $domain_uuid];

// Use api_paginate helper
$result = api_paginate($sql, $count_sql, $parameters, $page, $per_page);

api_success($result['items'], null, $result['pagination']);
