<?php
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/base.php';
validate_api_key();

$call_flow_uuid = get_uuid_from_path();
if (empty($call_flow_uuid)) {
    api_error('MISSING_UUID', 'Call flow UUID is required');
}

$database = new database;

$sql = "SELECT * FROM v_call_flows WHERE domain_uuid = :domain_uuid AND call_flow_uuid = :call_flow_uuid";
$parameters = ['domain_uuid' => $domain_uuid, 'call_flow_uuid' => $call_flow_uuid];
$call_flow = $database->select($sql, $parameters, 'row');

if (empty($call_flow)) {
    api_not_found('Call flow');
}

api_success($call_flow);
