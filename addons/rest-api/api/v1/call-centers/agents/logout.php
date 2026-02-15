<?php
require_once __DIR__ . '/../../base.php';
validate_api_key();
api_require_method('POST');

$agent_uuid = get_uuid_from_path();
api_validate_uuid($agent_uuid, 'call_center_agent_uuid');

// Check if agent exists
if (!api_record_exists('v_call_center_agents', 'call_center_agent_uuid', $agent_uuid)) {
    api_not_found('Call Center Agent');
}

// Update agent status to Logged Out
$array['call_center_agents'][0]['call_center_agent_uuid'] = $agent_uuid;
$array['call_center_agents'][0]['domain_uuid'] = $domain_uuid;
$array['call_center_agents'][0]['agent_status'] = 'Logged Out';
$array['call_center_agents'][0]['agent_logout'] = date('Y-m-d H:i:s');

$database = new database;
$database->app_name = 'call_centers';
$database->app_uuid = '95788e50-9500-079e-2807-fd530b0ea370';
$database->save($array);

// Clear dialplan cache
api_clear_dialplan_cache();

api_success([
    'call_center_agent_uuid' => $agent_uuid,
    'agent_status' => 'Logged Out'
], 'Agent logged out successfully');
