<?php
require_once __DIR__ . '/../../base.php';
validate_api_key();

// Pagination
$params = get_pagination_params();
$page = $params['page'];
$per_page = $params['per_page'];

// Filters
$filters = get_filter_params(['agent_status', 'agent_type']);
$filter_result = api_build_filters($filters, ['agent_status', 'agent_type']);

// Build query
$sql = "SELECT call_center_agent_uuid, user_uuid, agent_name, agent_type,
        agent_call_timeout, agent_contact, agent_status, agent_logout,
        agent_max_no_answer, agent_wrap_up_time, agent_reject_delay_time,
        agent_busy_delay_time, agent_no_answer_delay_time
        FROM v_call_center_agents
        WHERE domain_uuid = :domain_uuid" . $filter_result['where'] . "
        ORDER BY agent_name ASC";

$count_sql = "SELECT COUNT(*) FROM v_call_center_agents
              WHERE domain_uuid = :domain_uuid" . $filter_result['where'];

$parameters = array_merge(['domain_uuid' => $domain_uuid], $filter_result['parameters']);

$result = api_paginate($sql, $count_sql, $parameters, $page, $per_page);

api_success($result['items'], null, $result['pagination']);
