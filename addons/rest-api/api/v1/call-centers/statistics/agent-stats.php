<?php
require_once __DIR__ . '/../../base.php';
validate_api_key();

$agent_uuid = $_GET['agent_uuid'] ?? null;

if ($agent_uuid) {
    api_validate_uuid($agent_uuid, 'agent_uuid');

    // Check if agent exists
    if (!api_record_exists('v_call_center_agents', 'call_center_agent_uuid', $agent_uuid)) {
        api_not_found('Call Center Agent');
    }

    // Get agent name and status
    $agent = api_get_record('v_call_center_agents', 'call_center_agent_uuid', $agent_uuid, 'agent_name, agent_status');

    // Placeholder statistics for specific agent
    $stats = [
        'call_center_agent_uuid' => $agent_uuid,
        'agent_name' => $agent['agent_name'],
        'agent_status' => $agent['agent_status'],
        'calls_answered' => 0,
        'calls_missed' => 0,
        'total_talk_time' => 0,
        'average_talk_time' => 0,
        'wrap_up_time' => 0,
        'logged_in_time' => 0,
        'idle_time' => 0,
        'occupancy_rate' => 0.0,
        'note' => 'Real-time statistics will be integrated with FreeSWITCH ESL in future version'
    ];

    api_success($stats);
} else {
    // Get all agents statistics
    $database = new database;
    $sql = "SELECT call_center_agent_uuid, agent_name, agent_status
            FROM v_call_center_agents
            WHERE domain_uuid = :domain_uuid
            ORDER BY agent_name ASC";

    $agents = $database->select($sql, ['domain_uuid' => $domain_uuid], 'all');

    $stats = [];
    foreach ($agents ?? [] as $agent) {
        $stats[] = [
            'call_center_agent_uuid' => $agent['call_center_agent_uuid'],
            'agent_name' => $agent['agent_name'],
            'agent_status' => $agent['agent_status'],
            'calls_answered' => 0,
            'calls_missed' => 0,
            'total_talk_time' => 0,
            'average_talk_time' => 0,
            'note' => 'Real-time statistics will be integrated with FreeSWITCH ESL in future version'
        ];
    }

    api_success($stats);
}
