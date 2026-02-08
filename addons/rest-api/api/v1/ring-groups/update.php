<?php
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/base.php';
validate_api_key();

$ring_group_uuid = get_uuid_from_path();
api_validate_uuid($ring_group_uuid, 'ring_group_uuid');

// Verify ring group exists
$database = new database;
$sql = "SELECT ring_group_extension FROM v_ring_groups WHERE domain_uuid = :domain_uuid AND ring_group_uuid = :ring_group_uuid";
$existing = $database->select($sql, ['domain_uuid' => $domain_uuid, 'ring_group_uuid' => $ring_group_uuid], 'row');

if (empty($existing)) {
    api_not_found('Ring group');
}

$request = get_request_data();

// Build update array
$array['ring_groups'][0]['ring_group_uuid'] = $ring_group_uuid;
$array['ring_groups'][0]['domain_uuid'] = $domain_uuid;

// Only update provided fields
if (isset($request['ring_group_name'])) $array['ring_groups'][0]['ring_group_name'] = $request['ring_group_name'];
if (isset($request['ring_group_extension'])) {
    // Check uniqueness if extension changed
    if ($request['ring_group_extension'] !== $existing['ring_group_extension']) {
        $check_sql = "SELECT COUNT(*) FROM v_ring_groups WHERE domain_uuid = :domain_uuid AND ring_group_extension = :extension AND ring_group_uuid != :ring_group_uuid";
        if ($database->select($check_sql, ['domain_uuid' => $domain_uuid, 'extension' => $request['ring_group_extension'], 'ring_group_uuid' => $ring_group_uuid], 'column') > 0) {
            api_conflict('ring_group_extension', 'Extension already exists');
        }
    }
    $array['ring_groups'][0]['ring_group_extension'] = $request['ring_group_extension'];
}
if (isset($request['ring_group_strategy'])) $array['ring_groups'][0]['ring_group_strategy'] = $request['ring_group_strategy'];
if (isset($request['ring_group_timeout_app'])) $array['ring_groups'][0]['ring_group_timeout_app'] = $request['ring_group_timeout_app'];
if (isset($request['ring_group_timeout_data'])) $array['ring_groups'][0]['ring_group_timeout_data'] = $request['ring_group_timeout_data'];
if (isset($request['ring_group_cid_name_prefix'])) $array['ring_groups'][0]['ring_group_cid_name_prefix'] = $request['ring_group_cid_name_prefix'];
if (isset($request['ring_group_cid_number_prefix'])) $array['ring_groups'][0]['ring_group_cid_number_prefix'] = $request['ring_group_cid_number_prefix'];
if (isset($request['ring_group_enabled'])) $array['ring_groups'][0]['ring_group_enabled'] = $request['ring_group_enabled'];
if (isset($request['ring_group_description'])) $array['ring_groups'][0]['ring_group_description'] = $request['ring_group_description'];

// Grant permissions
$p = permissions::new();
$p->add('ring_group_edit', 'temp');

// Save ring group
$database = new database;
$database->app_name = 'ring_groups';
$database->app_uuid = '1d61fb65-1eec-bc73-a6ee-a6571b4e36de';
$database->save($array);
unset($array);

$p->delete('ring_group_edit', 'temp');

// Update destinations if provided (replace all)
if (isset($request['destinations']) && is_array($request['destinations'])) {
    // Delete existing destinations
    $delete_array['ring_group_destinations'][0]['domain_uuid'] = $domain_uuid;
    $delete_array['ring_group_destinations'][0]['ring_group_uuid'] = $ring_group_uuid;
    $database->delete($delete_array);
    unset($delete_array);

    // Add new destinations
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

    if (!empty($array)) {
        $p = permissions::new();
        $p->add('ring_group_destination_add', 'temp');

        $database = new database;
        $database->app_name = 'ring_groups';
        $database->app_uuid = '1d61fb65-1eec-bc73-a6ee-a6571b4e36de';
        $database->save($array);

        $p->delete('ring_group_destination_add', 'temp');
    }
}

// Clear dialplan cache
api_clear_dialplan_cache();

// Reload XML
if (class_exists('event_socket')) {
    event_socket::api('reloadxml');
}

api_success(['ring_group_uuid' => $ring_group_uuid], 'Ring group updated successfully');
