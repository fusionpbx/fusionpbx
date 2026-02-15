<?php
require_once dirname(__DIR__) . '/auth.php';
validate_api_key();

$dialplan_uuid = get_uuid_from_path();
if (empty($dialplan_uuid)) {
    api_error('MISSING_UUID', 'Dialplan UUID is required');
}

$request = get_request_data();

// Verify dialplan exists
$database = new database;
$sql = "SELECT dialplan_context FROM v_dialplans WHERE domain_uuid = :domain_uuid AND dialplan_uuid = :dialplan_uuid";
$parameters = [
    'domain_uuid' => $domain_uuid,
    'dialplan_uuid' => $dialplan_uuid
];
$existing_dialplan = $database->select($sql, $parameters, 'row');

if (empty($existing_dialplan)) {
    api_error('NOT_FOUND', 'Dialplan not found', null, 404);
}

$dialplan_context = $existing_dialplan['dialplan_context'];

// Build update array
$array['dialplans'][0]['domain_uuid'] = $domain_uuid;
$array['dialplans'][0]['dialplan_uuid'] = $dialplan_uuid;

if (isset($request['app_uuid'])) {
    $array['dialplans'][0]['app_uuid'] = $request['app_uuid'];
}
if (isset($request['dialplan_name'])) {
    $array['dialplans'][0]['dialplan_name'] = $request['dialplan_name'];
}
if (isset($request['dialplan_number'])) {
    $array['dialplans'][0]['dialplan_number'] = $request['dialplan_number'];
}
if (isset($request['dialplan_context'])) {
    $array['dialplans'][0]['dialplan_context'] = $request['dialplan_context'];
    $dialplan_context = $request['dialplan_context'];
}
if (isset($request['dialplan_continue'])) {
    $array['dialplans'][0]['dialplan_continue'] = $request['dialplan_continue'];
}
if (isset($request['dialplan_order'])) {
    $array['dialplans'][0]['dialplan_order'] = $request['dialplan_order'];
}
if (isset($request['dialplan_enabled'])) {
    $array['dialplans'][0]['dialplan_enabled'] = $request['dialplan_enabled'];
}
if (isset($request['dialplan_description'])) {
    $array['dialplans'][0]['dialplan_description'] = $request['dialplan_description'];
}

// Handle details update if provided
if (isset($request['details']) && is_array($request['details'])) {
    // Delete existing details
    $delete_sql = "DELETE FROM v_dialplan_details WHERE domain_uuid = :domain_uuid AND dialplan_uuid = :dialplan_uuid";
    $database->execute($delete_sql, $parameters);

    // Add new details
    foreach ($request['details'] as $index => $detail) {
        $array['dialplans'][0]['dialplan_details'][$index]['dialplan_detail_uuid'] = uuid();
        $array['dialplans'][0]['dialplan_details'][$index]['domain_uuid'] = $domain_uuid;
        $array['dialplans'][0]['dialplan_details'][$index]['dialplan_uuid'] = $dialplan_uuid;
        $array['dialplans'][0]['dialplan_details'][$index]['dialplan_detail_tag'] = $detail['tag'];
        $array['dialplans'][0]['dialplan_details'][$index]['dialplan_detail_type'] = $detail['type'];
        $array['dialplans'][0]['dialplan_details'][$index]['dialplan_detail_data'] = $detail['data'];
        $array['dialplans'][0]['dialplan_details'][$index]['dialplan_detail_break'] = $detail['break'] ?? '';
        $array['dialplans'][0]['dialplan_details'][$index]['dialplan_detail_inline'] = $detail['inline'] ?? '';
        $array['dialplans'][0]['dialplan_details'][$index]['dialplan_detail_group'] = $detail['group'] ?? '0';
        $array['dialplans'][0]['dialplan_details'][$index]['dialplan_detail_order'] = $detail['order'] ?? (($index + 1) * 10);
    }
}

// Grant permissions
$p = permissions::new();
$p->add('dialplan_edit', 'temp');

$database = new database;
$database->app_name = 'dialplans';
$database->app_uuid = '742714e5-8cdf-32fd-462c-cbe7e3d655db';
$database->save($array);

// CRITICAL: Clear dialplan cache
$cache = new cache;
$cache->delete("dialplan:" . $dialplan_context);

// Revoke permissions
$p->delete('dialplan_edit', 'temp');

api_success(['dialplan_uuid' => $dialplan_uuid], 'Dialplan updated successfully');
