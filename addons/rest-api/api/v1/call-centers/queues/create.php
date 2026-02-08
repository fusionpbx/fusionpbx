<?php
require_once __DIR__ . '/../../base.php';
validate_api_key();
api_require_method('POST');

$data = get_request_data();

// Validate required fields
$errors = api_validate($data, ['queue_name', 'queue_extension']);
if (!empty($errors)) {
    api_validation_error($errors);
}

// Check for duplicate extension
$database = new database;
$exists_sql = "SELECT COUNT(*) FROM v_call_center_queues
               WHERE domain_uuid = :domain_uuid AND queue_extension = :queue_extension";
$exists = $database->select($exists_sql, [
    'domain_uuid' => $domain_uuid,
    'queue_extension' => $data['queue_extension']
], 'column');

if ($exists > 0) {
    api_conflict('queue_extension', 'Queue extension already exists');
}

// Create queue record
$queue_uuid = uuid();
$array['call_center_queues'][0]['domain_uuid'] = $domain_uuid;
$array['call_center_queues'][0]['call_center_queue_uuid'] = $queue_uuid;
$array['call_center_queues'][0]['queue_name'] = $data['queue_name'];
$array['call_center_queues'][0]['queue_extension'] = $data['queue_extension'];
$array['call_center_queues'][0]['queue_strategy'] = $data['queue_strategy'] ?? 'round-robin';
$array['call_center_queues'][0]['queue_moh_sound'] = $data['queue_moh_sound'] ?? null;
$array['call_center_queues'][0]['queue_record_template'] = $data['queue_record_template'] ?? null;
$array['call_center_queues'][0]['queue_time_base_score'] = $data['queue_time_base_score'] ?? 'system';
$array['call_center_queues'][0]['queue_max_wait_time'] = $data['queue_max_wait_time'] ?? 0;
$array['call_center_queues'][0]['queue_max_wait_time_with_no_agent'] = $data['queue_max_wait_time_with_no_agent'] ?? 90;
$array['call_center_queues'][0]['queue_tier_rules_apply'] = $data['queue_tier_rules_apply'] ?? 'false';
$array['call_center_queues'][0]['queue_tier_rule_wait_second'] = $data['queue_tier_rule_wait_second'] ?? 300;
$array['call_center_queues'][0]['queue_tier_rule_wait_multiply_level'] = $data['queue_tier_rule_wait_multiply_level'] ?? 'true';
$array['call_center_queues'][0]['queue_tier_rule_no_agent_no_wait'] = $data['queue_tier_rule_no_agent_no_wait'] ?? 'true';
$array['call_center_queues'][0]['queue_discard_abandoned_after'] = $data['queue_discard_abandoned_after'] ?? 900;
$array['call_center_queues'][0]['queue_abandoned_resume_allowed'] = $data['queue_abandoned_resume_allowed'] ?? 'false';
$array['call_center_queues'][0]['queue_announce_sound'] = $data['queue_announce_sound'] ?? null;
$array['call_center_queues'][0]['queue_announce_frequency'] = $data['queue_announce_frequency'] ?? 0;
$array['call_center_queues'][0]['queue_description'] = $data['queue_description'] ?? null;
$array['call_center_queues'][0]['queue_enabled'] = $data['queue_enabled'] ?? 'true';

// Add temporary permission
$p = permissions::new();
$p->add('call_center_queue_add', 'temp');

// Save record
$database->app_name = 'call_centers';
$database->app_uuid = '95788e50-9500-079e-2681-ffe99316da93';
$database->save($array);
unset($array);

// Remove temporary permission
$p->delete('call_center_queue_add', 'temp');

// Clear dialplan cache
api_clear_dialplan_cache();

api_created(['call_center_queue_uuid' => $queue_uuid], 'Call center queue created successfully');
