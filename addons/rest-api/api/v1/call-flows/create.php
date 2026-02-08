<?php
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/base.php';
validate_api_key();

$request = get_request_data();

if (empty($request['call_flow_name'])) {
    api_error('VALIDATION_ERROR', 'Call flow name is required', 'call_flow_name');
}
if (empty($request['call_flow_extension'])) {
    api_error('VALIDATION_ERROR', 'Call flow extension is required', 'call_flow_extension');
}

// Check extension uniqueness
$database = new database;
$sql = "SELECT COUNT(*) FROM v_call_flows WHERE domain_uuid = :domain_uuid AND call_flow_extension = :extension";
if ($database->select($sql, ['domain_uuid' => $domain_uuid, 'extension' => $request['call_flow_extension']], 'column') > 0) {
    api_conflict('call_flow_extension', 'Extension already exists');
}

$call_flow_uuid = uuid();

$array['call_flows'][0]['domain_uuid'] = $domain_uuid;
$array['call_flows'][0]['call_flow_uuid'] = $call_flow_uuid;
$array['call_flows'][0]['call_flow_name'] = $request['call_flow_name'];
$array['call_flows'][0]['call_flow_extension'] = $request['call_flow_extension'];
$array['call_flows'][0]['call_flow_feature_code'] = $request['call_flow_feature_code'] ?? '';
$array['call_flows'][0]['call_flow_status'] = $request['call_flow_status'] ?? 'false';
$array['call_flows'][0]['call_flow_pin_number'] = $request['call_flow_pin_number'] ?? '';
$array['call_flows'][0]['call_flow_label'] = $request['call_flow_label'] ?? '';
$array['call_flows'][0]['call_flow_sound'] = $request['call_flow_sound'] ?? '';
$array['call_flows'][0]['call_flow_alternate_sound'] = $request['call_flow_alternate_sound'] ?? '';
$array['call_flows'][0]['call_flow_app'] = $request['call_flow_app'] ?? 'transfer';
$array['call_flows'][0]['call_flow_data'] = $request['call_flow_data'] ?? '';
$array['call_flows'][0]['call_flow_alternate_app'] = $request['call_flow_alternate_app'] ?? 'transfer';
$array['call_flows'][0]['call_flow_alternate_data'] = $request['call_flow_alternate_data'] ?? '';
$array['call_flows'][0]['call_flow_context'] = $domain_name;
$array['call_flows'][0]['call_flow_enabled'] = $request['call_flow_enabled'] ?? 'true';
$array['call_flows'][0]['call_flow_description'] = $request['call_flow_description'] ?? '';

$database = new database;
$database->app_name = 'call_flows';
$database->app_uuid = 'b1b70f85-6b42-429b-8c5a-60c8b02c7d6e';
$database->save($array);

api_clear_dialplan_cache();

api_created(['call_flow_uuid' => $call_flow_uuid], 'Call flow created successfully');
