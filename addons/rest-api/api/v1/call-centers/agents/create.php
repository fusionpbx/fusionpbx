<?php
require_once __DIR__ . '/../../base.php';
validate_api_key();
api_require_method('POST');

$data = get_request_data();

// Validate required fields
$errors = api_validate($data, ['agent_name', 'agent_contact']);
if (!empty($errors)) {
    api_validation_error($errors);
}

// Validate user_uuid if provided
if (isset($data['user_uuid'])) {
    api_validate_uuid($data['user_uuid'], 'user_uuid');
    // Check if user exists
    if (!api_record_exists('v_users', 'user_uuid', $data['user_uuid'])) {
        api_error('VALIDATION_ERROR', 'User not found', 'user_uuid', 400);
    }
}

// Create agent record
$agent_uuid = uuid();
$array['call_center_agents'][0]['domain_uuid'] = $domain_uuid;
$array['call_center_agents'][0]['call_center_agent_uuid'] = $agent_uuid;
$array['call_center_agents'][0]['user_uuid'] = $data['user_uuid'] ?? null;
$array['call_center_agents'][0]['agent_name'] = $data['agent_name'];
$array['call_center_agents'][0]['agent_type'] = $data['agent_type'] ?? 'callback';
$array['call_center_agents'][0]['agent_call_timeout'] = $data['agent_call_timeout'] ?? 30;
$array['call_center_agents'][0]['agent_contact'] = $data['agent_contact'];
$array['call_center_agents'][0]['agent_status'] = $data['agent_status'] ?? 'Logged Out';
$array['call_center_agents'][0]['agent_logout'] = $data['agent_logout'] ?? null;
$array['call_center_agents'][0]['agent_max_no_answer'] = $data['agent_max_no_answer'] ?? 3;
$array['call_center_agents'][0]['agent_wrap_up_time'] = $data['agent_wrap_up_time'] ?? 10;
$array['call_center_agents'][0]['agent_reject_delay_time'] = $data['agent_reject_delay_time'] ?? 10;
$array['call_center_agents'][0]['agent_busy_delay_time'] = $data['agent_busy_delay_time'] ?? 60;
$array['call_center_agents'][0]['agent_no_answer_delay_time'] = $data['agent_no_answer_delay_time'] ?? 30;

// Add temporary permission
$p = permissions::new();
$p->add('call_center_agent_add', 'temp');

// Save record
$database = new database;
$database->app_name = 'call_centers';
$database->app_uuid = '95788e50-9500-079e-2681-ffe99316da93';
$database->save($array);
unset($array);

// Remove temporary permission
$p->delete('call_center_agent_add', 'temp');

// Clear dialplan cache
api_clear_dialplan_cache();

api_created(['call_center_agent_uuid' => $agent_uuid], 'Call center agent created successfully');
