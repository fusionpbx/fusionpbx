<?php
require_once __DIR__ . '/../../base.php';
validate_api_key();
api_require_method('PUT');

$agent_uuid = get_uuid_from_path();
api_validate_uuid($agent_uuid, 'call_center_agent_uuid');

// Check if agent exists
if (!api_record_exists('v_call_center_agents', 'call_center_agent_uuid', $agent_uuid)) {
    api_not_found('Call Center Agent');
}

$data = get_request_data();

// Validate user_uuid if provided
if (isset($data['user_uuid']) && !empty($data['user_uuid'])) {
    api_validate_uuid($data['user_uuid'], 'user_uuid');
    // Check if user exists
    if (!api_record_exists('v_users', 'user_uuid', $data['user_uuid'])) {
        api_error('VALIDATION_ERROR', 'User not found', 'user_uuid', 400);
    }
}

// Build update array
$allowed_fields = [
    'user_uuid', 'agent_name', 'agent_type', 'agent_call_timeout',
    'agent_contact', 'agent_status', 'agent_logout', 'agent_max_no_answer',
    'agent_wrap_up_time', 'agent_reject_delay_time', 'agent_busy_delay_time',
    'agent_no_answer_delay_time'
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

$array['call_center_agent_uuid'] = $agent_uuid;

$database = new database;
$database->app_name = 'call-centers-api';
$database->app_uuid = 'cc48962a-d75c-4fa8-8b4f-2c3d7da5b123';
$database->update('v_call_center_agents', $array, 'call_center_agent_uuid');

// Clear dialplan cache
api_clear_dialplan_cache();

api_success(['call_center_agent_uuid' => $agent_uuid], 'Call center agent updated successfully');
