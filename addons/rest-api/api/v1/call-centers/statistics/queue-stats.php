<?php
require_once __DIR__ . '/../../base.php';
validate_api_key();

$queue_uuid = $_GET['queue_uuid'] ?? null;

if ($queue_uuid) {
    api_validate_uuid($queue_uuid, 'queue_uuid');

    // Check if queue exists
    if (!api_record_exists('v_call_center_queues', 'call_center_queue_uuid', $queue_uuid)) {
        api_not_found('Call Center Queue');
    }

    // Get queue name
    $queue = api_get_record('v_call_center_queues', 'call_center_queue_uuid', $queue_uuid, 'queue_name, queue_extension');

    // Placeholder statistics for specific queue
    $stats = [
        'call_center_queue_uuid' => $queue_uuid,
        'queue_name' => $queue['queue_name'],
        'queue_extension' => $queue['queue_extension'],
        'calls_waiting' => 0,
        'calls_answered' => 0,
        'calls_abandoned' => 0,
        'average_wait_time' => 0,
        'longest_wait_time' => 0,
        'agents_available' => 0,
        'agents_on_call' => 0,
        'service_level' => 0.0,
        'note' => 'Real-time statistics will be integrated with FreeSWITCH ESL in future version'
    ];

    api_success($stats);
} else {
    // Get all queues statistics
    $database = new database;
    $sql = "SELECT call_center_queue_uuid, queue_name, queue_extension
            FROM v_call_center_queues
            WHERE domain_uuid = :domain_uuid
            ORDER BY queue_name ASC";

    $queues = $database->select($sql, ['domain_uuid' => $domain_uuid], 'all');

    $stats = [];
    foreach ($queues ?? [] as $queue) {
        $stats[] = [
            'call_center_queue_uuid' => $queue['call_center_queue_uuid'],
            'queue_name' => $queue['queue_name'],
            'queue_extension' => $queue['queue_extension'],
            'calls_waiting' => 0,
            'calls_answered' => 0,
            'calls_abandoned' => 0,
            'average_wait_time' => 0,
            'agents_available' => 0,
            'note' => 'Real-time statistics will be integrated with FreeSWITCH ESL in future version'
        ];
    }

    api_success($stats);
}
