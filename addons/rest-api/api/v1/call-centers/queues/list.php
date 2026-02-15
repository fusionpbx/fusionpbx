<?php
require_once __DIR__ . '/../../base.php';
validate_api_key();

// Pagination
$params = get_pagination_params();
$page = $params['page'];
$per_page = $params['per_page'];

// Filters
$filters = get_filter_params(['queue_enabled', 'queue_strategy']);
$filter_result = api_build_filters($filters, ['queue_enabled', 'queue_strategy']);

// Build query
$sql = "SELECT call_center_queue_uuid, queue_name, queue_extension, queue_strategy,
        queue_moh_sound, queue_record_template, queue_time_base_score,
        queue_max_wait_time, queue_max_wait_time_with_no_agent,
        queue_tier_rules_apply, queue_tier_rule_wait_second,
        queue_tier_rule_wait_multiply_level, queue_tier_rule_no_agent_no_wait,
        queue_discard_abandoned_after, queue_abandoned_resume_allowed,
        queue_announce_sound, queue_announce_frequency, queue_description, queue_enabled
        FROM v_call_center_queues
        WHERE domain_uuid = :domain_uuid" . $filter_result['where'] . "
        ORDER BY queue_extension ASC";

$count_sql = "SELECT COUNT(*) FROM v_call_center_queues
              WHERE domain_uuid = :domain_uuid" . $filter_result['where'];

$parameters = array_merge(['domain_uuid' => $domain_uuid], $filter_result['parameters']);

$result = api_paginate($sql, $count_sql, $parameters, $page, $per_page);

api_success($result['items'], null, $result['pagination']);
