<?php
require_once dirname(__DIR__) . '/auth.php';
validate_api_key();

$app_uuid = '8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3'; // Outbound routes

$request = get_request_data();

// Validate required fields
if (empty($request['dialplan_name'])) {
    api_error('VALIDATION_ERROR', 'dialplan_name is required', 'dialplan_name');
}
if (empty($request['dialplan_order'])) {
    api_error('VALIDATION_ERROR', 'dialplan_order is required', 'dialplan_order');
}

// Generate UUID
$dialplan_uuid = uuid();

// Build dialplan array
$array['dialplans'][0]['domain_uuid'] = $domain_uuid;
$array['dialplans'][0]['dialplan_uuid'] = $dialplan_uuid;
$array['dialplans'][0]['app_uuid'] = $app_uuid;
$array['dialplans'][0]['dialplan_name'] = str_replace([' ', '/'], ['_', ''], $request['dialplan_name']);
$array['dialplans'][0]['dialplan_number'] = $request['dialplan_number'] ?? '';
$array['dialplans'][0]['dialplan_context'] = $request['dialplan_context'] ?? '${domain_name}';
$array['dialplans'][0]['dialplan_continue'] = $request['dialplan_continue'] ?? 'false';
$array['dialplans'][0]['dialplan_order'] = intval($request['dialplan_order']);
$array['dialplans'][0]['dialplan_enabled'] = $request['dialplan_enabled'] ?? 'true';
$array['dialplans'][0]['dialplan_description'] = $request['dialplan_description'] ?? '';
$array['dialplans'][0]['hostname'] = $request['hostname'] ?? '';

// Add dialplan details if provided
if (!empty($request['details']) && is_array($request['details'])) {
    foreach ($request['details'] as $index => $detail) {
        if (!empty($detail['dialplan_detail_tag'])) {
            $array['dialplans'][0]['dialplan_details'][$index]['dialplan_detail_uuid'] = uuid();
            $array['dialplans'][0]['dialplan_details'][$index]['domain_uuid'] = $domain_uuid;
            $array['dialplans'][0]['dialplan_details'][$index]['dialplan_uuid'] = $dialplan_uuid;
            $array['dialplans'][0]['dialplan_details'][$index]['dialplan_detail_tag'] = $detail['dialplan_detail_tag'];
            $array['dialplans'][0]['dialplan_details'][$index]['dialplan_detail_type'] = $detail['dialplan_detail_type'] ?? '';
            $array['dialplans'][0]['dialplan_details'][$index]['dialplan_detail_data'] = $detail['dialplan_detail_data'] ?? '';
            $array['dialplans'][0]['dialplan_details'][$index]['dialplan_detail_break'] = $detail['dialplan_detail_break'] ?? '';
            $array['dialplans'][0]['dialplan_details'][$index]['dialplan_detail_inline'] = $detail['dialplan_detail_inline'] ?? '';
            $array['dialplans'][0]['dialplan_details'][$index]['dialplan_detail_group'] = $detail['dialplan_detail_group'] ?? '0';
            $array['dialplans'][0]['dialplan_details'][$index]['dialplan_detail_order'] = $detail['dialplan_detail_order'] ?? $index;
            $array['dialplans'][0]['dialplan_details'][$index]['dialplan_detail_enabled'] = $detail['dialplan_detail_enabled'] ?? 'true';
        }
    }
}

// Grant permissions
$p = permissions::new();
$p->add('dialplan_add', 'temp');
$p->add('dialplan_detail_add', 'temp');

// Save to database
$database = new database;
$database->app_name = 'dialplans';
$database->app_uuid = '8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3';
$database->save($array);

$p->delete('dialplan_add', 'temp');
$p->delete('dialplan_detail_add', 'temp');

// Regenerate dialplan XML from details
require_once dirname(__DIR__) . '/base.php';
api_generate_dialplan_xml($dialplan_uuid);

// Clear dialplan cache
$cache = new cache;
$dialplan_context = $request['dialplan_context'] ?? $domain_name;
$cache->delete('dialplan:' . $dialplan_context);
$cache->delete('dialplan:*');

api_created(['dialplan_uuid' => $dialplan_uuid], 'Outbound route created successfully');
