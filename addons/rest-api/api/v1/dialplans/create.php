<?php
require_once dirname(__DIR__) . '/auth.php';
validate_api_key();

$request = get_request_data();
if (empty($request['dialplan_name'])) {
    api_error('VALIDATION_ERROR', 'Dialplan name is required', 'dialplan_name');
}

$dialplan_uuid = uuid();
$dialplan_context = $request['dialplan_context'] ?? $domain_name;

$array['dialplans'][0]['domain_uuid'] = $domain_uuid;
$array['dialplans'][0]['dialplan_uuid'] = $dialplan_uuid;
$array['dialplans'][0]['app_uuid'] = $request['app_uuid'] ?? '';
$array['dialplans'][0]['dialplan_name'] = $request['dialplan_name'];
$array['dialplans'][0]['dialplan_number'] = $request['dialplan_number'] ?? '';
$array['dialplans'][0]['dialplan_context'] = $dialplan_context;
$array['dialplans'][0]['dialplan_continue'] = $request['dialplan_continue'] ?? 'false';
$array['dialplans'][0]['dialplan_order'] = $request['dialplan_order'] ?? '100';
$array['dialplans'][0]['dialplan_enabled'] = $request['dialplan_enabled'] ?? 'true';
$array['dialplans'][0]['dialplan_description'] = $request['dialplan_description'] ?? '';

// Add details
if (!empty($request['details']) && is_array($request['details'])) {
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

$database = new database;
$database->app_name = 'dialplans';
$database->app_uuid = '742714e5-8cdf-32fd-462c-cbe7e3d655db';
$database->save($array);

// CRITICAL: Clear dialplan cache
$cache = new cache;
$cache->delete("dialplan:" . $dialplan_context);

api_success(['dialplan_uuid' => $dialplan_uuid], 'Dialplan created successfully');
