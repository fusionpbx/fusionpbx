<?php
require_once dirname(__DIR__) . '/base.php';
validate_api_key();

// Pagination
$params = get_pagination_params();
$page = $params['page'];
$per_page = $params['per_page'];

// Build query
$sql = "SELECT conference_uuid, conference_name, conference_extension, conference_pin,
        moderator_pin, wait_mod, announce_name, announce_count, announce_recording,
        mute, sounds, member_type, profile, max_members, record, enabled, description
        FROM v_conferences
        WHERE domain_uuid = :domain_uuid
        ORDER BY conference_extension ASC";

$count_sql = "SELECT COUNT(*) FROM v_conferences WHERE domain_uuid = :domain_uuid";

$parameters = ['domain_uuid' => $domain_uuid];

// Use api_paginate helper
$result = api_paginate($sql, $count_sql, $parameters, $page, $per_page);

api_success($result['items'], null, $result['pagination']);
