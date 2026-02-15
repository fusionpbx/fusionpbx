<?php
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/base.php';
validate_api_key();

$call_flow_uuid = get_uuid_from_path();
if (empty($call_flow_uuid)) {
    api_error('MISSING_UUID', 'Call flow UUID is required');
}

$database = new database;
$sql = "SELECT call_flow_uuid FROM v_call_flows WHERE domain_uuid = :domain_uuid AND call_flow_uuid = :call_flow_uuid";
if (!$database->select($sql, ['domain_uuid' => $domain_uuid, 'call_flow_uuid' => $call_flow_uuid], 'row')) {
    api_not_found('Call flow');
}

$array['call_flows'][0]['domain_uuid'] = $domain_uuid;
$array['call_flows'][0]['call_flow_uuid'] = $call_flow_uuid;

$database = new database;
$database->app_name = 'call_flows';
$database->app_uuid = 'b1b70f85-6b42-429b-8c5a-60c8b02c7d6e';
$database->delete($array);

api_clear_dialplan_cache();

api_no_content();
