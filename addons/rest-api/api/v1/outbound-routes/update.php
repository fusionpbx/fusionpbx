<?php
require_once dirname(__DIR__) . '/auth.php';
validate_api_key();

$app_uuid = '8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3'; // Outbound routes

$dialplan_uuid = $_GET['id'] ?? '';
if (!is_uuid($dialplan_uuid)) {
    api_error('VALIDATION_ERROR', 'Valid dialplan_uuid is required', 'id');
}

$request = get_request_data();

// Verify route exists and belongs to this domain
$database = new database;
$sql = "SELECT dialplan_uuid FROM v_dialplans
        WHERE domain_uuid = :domain_uuid AND app_uuid = :app_uuid AND dialplan_uuid = :dialplan_uuid";
$parameters = [
    'domain_uuid' => $domain_uuid,
    'app_uuid' => $app_uuid,
    'dialplan_uuid' => $dialplan_uuid
];
if (!$database->select($sql, $parameters, 'row')) {
    api_error('NOT_FOUND', 'Outbound route not found', null, 404);
}

// Build update array
$array['dialplans'][0]['domain_uuid'] = $domain_uuid;
$array['dialplans'][0]['dialplan_uuid'] = $dialplan_uuid;
$array['dialplans'][0]['app_uuid'] = $app_uuid;

if (isset($request['dialplan_name'])) {
    $array['dialplans'][0]['dialplan_name'] = str_replace([' ', '/'], ['_', ''], $request['dialplan_name']);
}
if (isset($request['dialplan_number'])) {
    $array['dialplans'][0]['dialplan_number'] = $request['dialplan_number'];
}
if (isset($request['dialplan_context'])) {
    $array['dialplans'][0]['dialplan_context'] = $request['dialplan_context'];
}
if (isset($request['dialplan_continue'])) {
    $array['dialplans'][0]['dialplan_continue'] = $request['dialplan_continue'];
}
if (isset($request['dialplan_order'])) {
    $array['dialplans'][0]['dialplan_order'] = intval($request['dialplan_order']);
}
if (isset($request['dialplan_enabled'])) {
    $array['dialplans'][0]['dialplan_enabled'] = $request['dialplan_enabled'];
}
if (isset($request['dialplan_description'])) {
    $array['dialplans'][0]['dialplan_description'] = $request['dialplan_description'];
}
if (isset($request['hostname'])) {
    $array['dialplans'][0]['hostname'] = $request['hostname'];
}

// Update dialplan details if provided
if (isset($request['details']) && is_array($request['details'])) {
    foreach ($request['details'] as $index => $detail) {
        if (!empty($detail['dialplan_detail_tag'])) {
            $detail_uuid = !empty($detail['dialplan_detail_uuid']) && is_uuid($detail['dialplan_detail_uuid'])
                ? $detail['dialplan_detail_uuid']
                : uuid();

            $array['dialplans'][0]['dialplan_details'][$index]['dialplan_detail_uuid'] = $detail_uuid;
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
$p->add('dialplan_edit', 'temp');
$p->add('dialplan_detail_add', 'temp');
$p->add('dialplan_detail_edit', 'temp');

// Save to database
$database = new database;
$database->app_name = 'dialplans';
$database->app_uuid = '8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3';
$database->save($array);

$p->delete('dialplan_edit', 'temp');
$p->delete('dialplan_detail_add', 'temp');
$p->delete('dialplan_detail_edit', 'temp');

// Regenerate dialplan XML from details
require_once dirname(__DIR__) . '/base.php';
api_generate_dialplan_xml($dialplan_uuid);

// Clear dialplan cache
$cache = new cache;
$dialplan_context = $request['dialplan_context'] ?? $domain_name;
$cache->delete('dialplan:' . $dialplan_context);
$cache->delete('dialplan:*');

api_success(['dialplan_uuid' => $dialplan_uuid], 'Outbound route updated successfully');
