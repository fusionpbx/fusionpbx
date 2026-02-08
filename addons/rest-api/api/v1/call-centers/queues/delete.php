<?php
require_once __DIR__ . '/../../base.php';
validate_api_key();
api_require_method('DELETE');

$queue_uuid = get_uuid_from_path();
api_validate_uuid($queue_uuid, 'call_center_queue_uuid');

// Check if queue exists
if (!api_record_exists('v_call_center_queues', 'call_center_queue_uuid', $queue_uuid)) {
    api_not_found('Call Center Queue');
}

// Delete associated tiers first
$database = new database;
$database->app_name = 'call-centers-api';
$database->app_uuid = 'cc48962a-d75c-4fa8-8b4f-2c3d7da5b123';

$tier_sql = "DELETE FROM v_call_center_tiers
             WHERE call_center_queue_uuid = :queue_uuid AND domain_uuid = :domain_uuid";
$database->execute($tier_sql, [
    'queue_uuid' => $queue_uuid,
    'domain_uuid' => $domain_uuid
]);

// Delete queue
$array = [
    'call_center_queue_uuid' => $queue_uuid
];
$database->delete('v_call_center_queues', $array);

// Clear dialplan cache
api_clear_dialplan_cache();

api_no_content();
