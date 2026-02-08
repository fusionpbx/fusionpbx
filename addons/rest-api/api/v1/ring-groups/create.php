<?php
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/base.php';
validate_api_key();

$request = get_request_data();

// Validate required fields
if (empty($request['ring_group_name'])) {
    api_error('VALIDATION_ERROR', 'Ring group name is required', 'ring_group_name');
}
if (empty($request['ring_group_extension'])) {
    api_error('VALIDATION_ERROR', 'Ring group extension is required', 'ring_group_extension');
}

// Check extension uniqueness
$database = new database;
$sql = "SELECT COUNT(*) FROM v_ring_groups WHERE domain_uuid = :domain_uuid AND ring_group_extension = :extension";
if ($database->select($sql, ['domain_uuid' => $domain_uuid, 'extension' => $request['ring_group_extension']], 'column') > 0) {
    api_conflict('ring_group_extension', 'Extension already exists');
}

$ring_group_uuid = uuid();
$ring_group_context = $domain_name;

// Build ring group array
$array['ring_groups'][0]['domain_uuid'] = $domain_uuid;
$array['ring_groups'][0]['ring_group_uuid'] = $ring_group_uuid;
$array['ring_groups'][0]['ring_group_name'] = $request['ring_group_name'];
$array['ring_groups'][0]['ring_group_extension'] = $request['ring_group_extension'];
$array['ring_groups'][0]['ring_group_context'] = $ring_group_context;
$array['ring_groups'][0]['ring_group_strategy'] = $request['ring_group_strategy'] ?? 'simultaneous';
$array['ring_groups'][0]['ring_group_timeout_app'] = $request['ring_group_timeout_app'] ?? 'transfer';
$array['ring_groups'][0]['ring_group_timeout_data'] = $request['ring_group_timeout_data'] ?? '';
$array['ring_groups'][0]['ring_group_cid_name_prefix'] = $request['ring_group_cid_name_prefix'] ?? '';
$array['ring_groups'][0]['ring_group_cid_number_prefix'] = $request['ring_group_cid_number_prefix'] ?? '';
$array['ring_groups'][0]['ring_group_enabled'] = $request['ring_group_enabled'] ?? 'true';
$array['ring_groups'][0]['ring_group_description'] = $request['ring_group_description'] ?? '';

// Grant permissions
$p = permissions::new();
$p->add('ring_group_add', 'temp');

// Save ring group
$database = new database;
$database->app_name = 'ring_groups';
$database->app_uuid = '1d61fb65-1eec-bc73-a6ee-a6571b4e36de';
$database->save($array);
unset($array);

$p->delete('ring_group_add', 'temp');

// Add destinations if provided
if (!empty($request['destinations']) && is_array($request['destinations'])) {
    foreach ($request['destinations'] as $index => $dest) {
        if (empty($dest['destination_number'])) continue;

        $array['ring_group_destinations'][$index]['ring_group_destination_uuid'] = uuid();
        $array['ring_group_destinations'][$index]['domain_uuid'] = $domain_uuid;
        $array['ring_group_destinations'][$index]['ring_group_uuid'] = $ring_group_uuid;
        $array['ring_group_destinations'][$index]['destination_number'] = $dest['destination_number'];
        $array['ring_group_destinations'][$index]['destination_delay'] = $dest['destination_delay'] ?? '0';
        $array['ring_group_destinations'][$index]['destination_timeout'] = $dest['destination_timeout'] ?? '30';
        $array['ring_group_destinations'][$index]['destination_prompt'] = $dest['destination_prompt'] ?? '';
        $array['ring_group_destinations'][$index]['destination_enabled'] = $dest['destination_enabled'] ?? 'true';
    }

    $p = permissions::new();
    $p->add('ring_group_destination_add', 'temp');

    $database = new database;
    $database->app_name = 'ring_groups';
    $database->app_uuid = '1d61fb65-1eec-bc73-a6ee-a6571b4e36de';
    $database->save($array);
    unset($array);

    $p->delete('ring_group_destination_add', 'temp');
}

// Clear dialplan cache
api_clear_dialplan_cache();

// Reload XML
if (class_exists('event_socket')) {
    event_socket::api('reloadxml');
}

api_created(['ring_group_uuid' => $ring_group_uuid], 'Ring group created successfully');
