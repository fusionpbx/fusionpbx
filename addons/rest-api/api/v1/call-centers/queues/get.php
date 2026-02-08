<?php
require_once __DIR__ . '/../../base.php';
validate_api_key();

$queue_uuid = get_uuid_from_path();
api_validate_uuid($queue_uuid, 'call_center_queue_uuid');

// Get queue details
$queue = api_get_record('v_call_center_queues', 'call_center_queue_uuid', $queue_uuid);

if (!$queue) {
    api_not_found('Call Center Queue');
}

// Get associated agents via tiers
$database = new database;
$sql = "SELECT t.call_center_tier_uuid, t.tier_level, t.tier_position,
        a.call_center_agent_uuid, a.agent_name, a.agent_type, a.agent_status,
        a.agent_call_timeout, a.agent_contact, a.agent_max_no_answer,
        a.agent_wrap_up_time, a.agent_reject_delay_time,
        a.agent_busy_delay_time, a.agent_no_answer_delay_time
        FROM v_call_center_tiers t
        INNER JOIN v_call_center_agents a ON t.call_center_agent_uuid = a.call_center_agent_uuid
        WHERE t.call_center_queue_uuid = :queue_uuid
        AND t.domain_uuid = :domain_uuid
        ORDER BY t.tier_level ASC, t.tier_position ASC";

$parameters = [
    'queue_uuid' => $queue_uuid,
    'domain_uuid' => $domain_uuid
];

$agents = $database->select($sql, $parameters, 'all');

$queue['agents'] = $agents ?? [];

api_success($queue);
