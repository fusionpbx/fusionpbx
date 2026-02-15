<?php
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/base.php';
validate_api_key();

$call_flow_uuid = get_uuid_from_path();
if (empty($call_flow_uuid)) {
    api_error('MISSING_UUID', 'Call flow UUID is required');
}

$database = new database;
$sql = "SELECT call_flow_extension FROM v_call_flows WHERE domain_uuid = :domain_uuid AND call_flow_uuid = :call_flow_uuid";
$existing = $database->select($sql, ['domain_uuid' => $domain_uuid, 'call_flow_uuid' => $call_flow_uuid], 'row');

if (empty($existing)) {
    api_not_found('Call flow');
}

$request = get_request_data();

$array['call_flows'][0]['call_flow_uuid'] = $call_flow_uuid;
$array['call_flows'][0]['domain_uuid'] = $domain_uuid;

if (isset($request['call_flow_name'])) $array['call_flows'][0]['call_flow_name'] = $request['call_flow_name'];
if (isset($request['call_flow_extension'])) {
    if ($request['call_flow_extension'] !== $existing['call_flow_extension']) {
        $check_sql = "SELECT COUNT(*) FROM v_call_flows WHERE domain_uuid = :domain_uuid AND call_flow_extension = :extension AND call_flow_uuid != :call_flow_uuid";
        if ($database->select($check_sql, ['domain_uuid' => $domain_uuid, 'extension' => $request['call_flow_extension'], 'call_flow_uuid' => $call_flow_uuid], 'column') > 0) {
            api_conflict('call_flow_extension', 'Extension already exists');
        }
    }
    $array['call_flows'][0]['call_flow_extension'] = $request['call_flow_extension'];
}
if (isset($request['call_flow_feature_code'])) $array['call_flows'][0]['call_flow_feature_code'] = $request['call_flow_feature_code'];
if (isset($request['call_flow_status'])) $array['call_flows'][0]['call_flow_status'] = $request['call_flow_status'];
if (isset($request['call_flow_pin_number'])) $array['call_flows'][0]['call_flow_pin_number'] = $request['call_flow_pin_number'];
if (isset($request['call_flow_label'])) $array['call_flows'][0]['call_flow_label'] = $request['call_flow_label'];
if (isset($request['call_flow_sound'])) $array['call_flows'][0]['call_flow_sound'] = $request['call_flow_sound'];
if (isset($request['call_flow_alternate_sound'])) $array['call_flows'][0]['call_flow_alternate_sound'] = $request['call_flow_alternate_sound'];
if (isset($request['call_flow_app'])) $array['call_flows'][0]['call_flow_app'] = $request['call_flow_app'];
if (isset($request['call_flow_data'])) $array['call_flows'][0]['call_flow_data'] = $request['call_flow_data'];
if (isset($request['call_flow_alternate_app'])) $array['call_flows'][0]['call_flow_alternate_app'] = $request['call_flow_alternate_app'];
if (isset($request['call_flow_alternate_data'])) $array['call_flows'][0]['call_flow_alternate_data'] = $request['call_flow_alternate_data'];
if (isset($request['call_flow_enabled'])) $array['call_flows'][0]['call_flow_enabled'] = $request['call_flow_enabled'];
if (isset($request['call_flow_description'])) $array['call_flows'][0]['call_flow_description'] = $request['call_flow_description'];

// Grant permissions
$p = permissions::new();
$p->add('call_flow_edit', 'temp');

$database = new database;
$database->app_name = 'call_flows';
$database->app_uuid = 'b1b70f85-6b42-429b-8c5a-60c8b02c7d6e';
$database->save($array);

api_clear_dialplan_cache();

// Revoke permissions
$p->delete('call_flow_edit', 'temp');

api_success(['call_flow_uuid' => $call_flow_uuid], 'Call flow updated successfully');
