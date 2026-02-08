<?php
require_once __DIR__ . '/../../base.php';
validate_api_key();

$agent_uuid = get_uuid_from_path();
api_validate_uuid($agent_uuid, 'call_center_agent_uuid');

// Get agent details
$agent = api_get_record('v_call_center_agents', 'call_center_agent_uuid', $agent_uuid);

if (!$agent) {
    api_not_found('Call Center Agent');
}

// Get associated queues via tiers
$database = new database;
$sql = "SELECT t.call_center_tier_uuid, t.tier_level, t.tier_position,
        q.call_center_queue_uuid, q.queue_name, q.queue_extension,
        q.queue_strategy, q.queue_enabled
        FROM v_call_center_tiers t
        INNER JOIN v_call_center_queues q ON t.call_center_queue_uuid = q.call_center_queue_uuid
        WHERE t.call_center_agent_uuid = :agent_uuid
        AND t.domain_uuid = :domain_uuid
        ORDER BY t.tier_level ASC, t.tier_position ASC";

$parameters = [
    'agent_uuid' => $agent_uuid,
    'domain_uuid' => $domain_uuid
];

$queues = $database->select($sql, $parameters, 'all');

$agent['queues'] = $queues ?? [];

api_success($agent);
