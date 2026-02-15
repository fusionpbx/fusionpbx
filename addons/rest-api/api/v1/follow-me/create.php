<?php
require_once dirname(__DIR__) . '/base.php';
validate_api_key();
api_require_method('POST');

// Get request data
$data = get_request_data();

// Validate required fields
$required_fields = ['follow_me_enabled'];
$errors = api_validate($data, $required_fields);
if (!empty($errors)) {
    api_validation_error($errors);
}

// Generate UUID for follow me
$follow_me_uuid = uuid();

// Build array for follow_me
$array['follow_me'][0]['follow_me_uuid'] = $follow_me_uuid;
$array['follow_me'][0]['domain_uuid'] = $domain_uuid;
$array['follow_me'][0]['follow_me_enabled'] = $data['follow_me_enabled'] ?? 'true';
$array['follow_me'][0]['cid_name_prefix'] = $data['cid_name_prefix'] ?? null;
$array['follow_me'][0]['cid_number_prefix'] = $data['cid_number_prefix'] ?? null;
$array['follow_me'][0]['follow_me_caller_id_uuid'] = $data['follow_me_caller_id_uuid'] ?? null;
$array['follow_me'][0]['follow_me_toll_allow'] = $data['follow_me_toll_allow'] ?? null;
$array['follow_me'][0]['follow_me_ringback'] = $data['follow_me_ringback'] ?? null;
$array['follow_me'][0]['follow_me_ignore_busy'] = $data['follow_me_ignore_busy'] ?? 'false';

// Grant permissions
$p = new permissions;
$p->add('follow_me_add', 'temp');

// Save follow_me record
$database = new database;
$database->app_name = 'follow_me';
$database->app_uuid = '93b50fce-256f-4e2f-85a2-393087349764';
$database->save($array);
unset($array);

// Insert destinations if provided
if (!empty($data['destinations']) && is_array($data['destinations'])) {
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
$p->delete('follow_me_add', 'temp');

// Clear dialplan cache
api_clear_dialplan_cache();

// Return created resource
api_created([
    'follow_me_uuid' => $follow_me_uuid
], 'Follow Me configuration created successfully');
