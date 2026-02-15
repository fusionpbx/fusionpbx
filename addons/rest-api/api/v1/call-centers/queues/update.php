<?php
require_once __DIR__ . '/../../base.php';
validate_api_key();
api_require_method('PUT');

$queue_uuid = get_uuid_from_path();
api_validate_uuid($queue_uuid, 'call_center_queue_uuid');

// Check if queue exists
if (!api_record_exists('v_call_center_queues', 'call_center_queue_uuid', $queue_uuid)) {
    api_not_found('Call Center Queue');
}

$data = get_request_data();

// Check for duplicate extension if being changed
if (isset($data['queue_extension'])) {
    $database = new database;
    $exists_sql = "SELECT COUNT(*) FROM v_call_center_queues
                   WHERE domain_uuid = :domain_uuid
                   AND queue_extension = :queue_extension
                   AND call_center_queue_uuid != :queue_uuid";
    $exists = $database->select($exists_sql, [
        'domain_uuid' => $domain_uuid,
        'queue_extension' => $data['queue_extension'],
        'queue_uuid' => $queue_uuid
    ], 'column');

    if ($exists > 0) {
        api_conflict('queue_extension', 'Queue extension already exists');
    }
}

// Build update array
$allowed_fields = [
    'queue_name', 'queue_extension', 'queue_strategy', 'queue_moh_sound',
    'queue_record_template', 'queue_time_base_score', 'queue_max_wait_time',
    'queue_max_wait_time_with_no_agent', 'queue_tier_rules_apply',
    'queue_tier_rule_wait_second', 'queue_tier_rule_wait_multiply_level',
    'queue_tier_rule_no_agent_no_wait', 'queue_discard_abandoned_after',
    'queue_abandoned_resume_allowed', 'queue_announce_sound',
    'queue_announce_frequency', 'queue_description', 'queue_enabled'
];

$array = [];
foreach ($allowed_fields as $field) {
    if (isset($data[$field])) {
        $array[$field] = $data[$field];
    }
}

if (empty($array)) {
    api_error('VALIDATION_ERROR', 'No valid fields to update', null, 400);
}

$array['call_center_queue_uuid'] = $queue_uuid;

$database = new database;
$database->app_name = 'call-centers-api';
$database->app_uuid = 'cc48962a-d75c-4fa8-8b4f-2c3d7da5b123';
$database->update('v_call_center_queues', $array, 'call_center_queue_uuid');

// Clear dialplan cache
api_clear_dialplan_cache();

api_success(['call_center_queue_uuid' => $queue_uuid], 'Call center queue updated successfully');
