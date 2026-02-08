<?php
require_once dirname(__DIR__) . '/base.php';
validate_api_key();
api_require_method('PUT');

// Get UUID from path
$follow_me_uuid = get_uuid_from_path();
api_validate_uuid($follow_me_uuid, 'follow_me_uuid');

// Check if record exists
if (!api_record_exists('v_follow_me', 'follow_me_uuid', $follow_me_uuid)) {
    api_not_found('Follow Me configuration');
}

// Get request data
$data = get_request_data();

// Build array for follow_me (only update provided fields)
$array['follow_me'][0]['follow_me_uuid'] = $follow_me_uuid;
$array['follow_me'][0]['domain_uuid'] = $domain_uuid;

if (isset($data['follow_me_enabled'])) {
    $array['follow_me'][0]['follow_me_enabled'] = $data['follow_me_enabled'];
}
if (isset($data['cid_name_prefix'])) {
    $array['follow_me'][0]['cid_name_prefix'] = $data['cid_name_prefix'];
}
if (isset($data['cid_number_prefix'])) {
    $array['follow_me'][0]['cid_number_prefix'] = $data['cid_number_prefix'];
}
if (isset($data['follow_me_caller_id_uuid'])) {
    $array['follow_me'][0]['follow_me_caller_id_uuid'] = $data['follow_me_caller_id_uuid'];
}
if (isset($data['follow_me_toll_allow'])) {
    $array['follow_me'][0]['follow_me_toll_allow'] = $data['follow_me_toll_allow'];
}
if (isset($data['follow_me_ringback'])) {
    $array['follow_me'][0]['follow_me_ringback'] = $data['follow_me_ringback'];
}
if (isset($data['follow_me_ignore_busy'])) {
    $array['follow_me'][0]['follow_me_ignore_busy'] = $data['follow_me_ignore_busy'];
}

// Grant permissions
$p = new permissions;
$p->add('follow_me_edit', 'temp');

// Save follow_me record
$database = new database;
$database->app_name = 'follow_me';
$database->app_uuid = '93b50fce-256f-4e2f-85a2-393087349764';
$database->save($array);
unset($array);

// Handle destinations if provided
if (isset($data['destinations']) && is_array($data['destinations'])) {
    // Delete existing destinations
    $array['follow_me_destinations'][0]['follow_me_uuid'] = $follow_me_uuid;
    $array['follow_me_destinations'][0]['domain_uuid'] = $domain_uuid;

    $database = new database;
    $database->app_name = 'follow_me';
    $database->app_uuid = '93b50fce-256f-4e2f-85a2-393087349764';
    $database->delete($array);
    unset($array);

    // Insert new destinations
    $i = 0;
    foreach ($data['destinations'] as $destination) {
        if (empty($destination['follow_me_destination'])) {
            continue; // Skip empty destinations
        }

        $array['follow_me_destinations'][$i]['follow_me_destination_uuid'] = uuid();
        $array['follow_me_destinations'][$i]['domain_uuid'] = $domain_uuid;
        $array['follow_me_destinations'][$i]['follow_me_uuid'] = $follow_me_uuid;
        $array['follow_me_destinations'][$i]['follow_me_destination'] = $destination['follow_me_destination'];
        $array['follow_me_destinations'][$i]['follow_me_delay'] = $destination['follow_me_delay'] ?? 0;
        $array['follow_me_destinations'][$i]['follow_me_timeout'] = $destination['follow_me_timeout'] ?? 30;
        $array['follow_me_destinations'][$i]['follow_me_prompt'] = $destination['follow_me_prompt'] ?? null;
        $i++;
    }

    if (!empty($array)) {
        $database = new database;
        $database->app_name = 'follow_me';
        $database->app_uuid = '93b50fce-256f-4e2f-85a2-393087349764';
        $database->save($array);
        unset($array);
    }
}

// Delete permissions
$p->delete('follow_me_edit', 'temp');

// Clear dialplan cache
api_clear_dialplan_cache();

// Return success
api_success([
    'follow_me_uuid' => $follow_me_uuid
], 'Follow Me configuration updated successfully');
