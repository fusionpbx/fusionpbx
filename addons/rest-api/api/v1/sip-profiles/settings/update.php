<?php
require_once __DIR__ . '/../../base.php';

api_require_method('POST');

// Get request data
$data = get_request_data();

// Validate required fields
$errors = api_validate($data, ['sip_profile_uuid', 'sip_profile_setting_name', 'sip_profile_setting_value']);
if (!empty($errors)) {
    api_validation_error($errors);
}

api_validate_uuid($data['sip_profile_uuid'], 'sip_profile_uuid');

$database = new database;

// Check if profile exists
$check_sql = "SELECT COUNT(*) FROM v_sip_profiles WHERE sip_profile_uuid = :sip_profile_uuid";
$exists = $database->select($check_sql, ['sip_profile_uuid' => $data['sip_profile_uuid']], 'column');

if (!$exists) {
    api_not_found('SIP Profile');
}

// Check if setting exists
$setting_sql = "SELECT sip_profile_setting_uuid FROM v_sip_profile_settings
                WHERE sip_profile_uuid = :sip_profile_uuid
                AND sip_profile_setting_name = :sip_profile_setting_name";

$setting_params = [
    'sip_profile_uuid' => $data['sip_profile_uuid'],
    'sip_profile_setting_name' => $data['sip_profile_setting_name']
];

$existing_setting = $database->select($setting_sql, $setting_params, 'row');

$database->app_name = 'api';
$database->app_uuid = '099d2b17-45dd-416f-b763-6037c47f1a84';

if ($existing_setting) {
    // Update existing setting
    $array['sip_profile_settings'][0]['sip_profile_setting_uuid'] = $existing_setting['sip_profile_setting_uuid'];
    $array['sip_profile_settings'][0]['sip_profile_setting_value'] = $data['sip_profile_setting_value'];
    $array['sip_profile_settings'][0]['sip_profile_setting_enabled'] = $data['sip_profile_setting_enabled'] ?? 'true';
    $array['sip_profile_settings'][0]['sip_profile_setting_description'] = $data['sip_profile_setting_description'] ?? null;

    // Add temporary permission
    $p = permissions::new();
    $p->add('sip_profile_setting_edit', 'temp');

    // Save update
    $database->save($array);
    unset($array);

    // Remove temporary permission
    $p->delete('sip_profile_setting_edit', 'temp');

    $sip_profile_setting_uuid = $existing_setting['sip_profile_setting_uuid'];
    $message = 'SIP Profile setting updated successfully';
} else {
    // Create new setting
    $sip_profile_setting_uuid = uuid();
    $array['sip_profile_settings'][0]['sip_profile_setting_uuid'] = $sip_profile_setting_uuid;
    $array['sip_profile_settings'][0]['sip_profile_uuid'] = $data['sip_profile_uuid'];
    $array['sip_profile_settings'][0]['sip_profile_setting_name'] = $data['sip_profile_setting_name'];
    $array['sip_profile_settings'][0]['sip_profile_setting_value'] = $data['sip_profile_setting_value'];
    $array['sip_profile_settings'][0]['sip_profile_setting_enabled'] = $data['sip_profile_setting_enabled'] ?? 'true';
    $array['sip_profile_settings'][0]['sip_profile_setting_description'] = $data['sip_profile_setting_description'] ?? null;

    // Add temporary permission
    $p = permissions::new();
    $p->add('sip_profile_setting_add', 'temp');

    // Save record
    $database->save($array);
    unset($array);

    // Remove temporary permission
    $p->delete('sip_profile_setting_add', 'temp');

    $message = 'SIP Profile setting created successfully';
}

// Clear cache
api_clear_cache(gethostname() . ":configuration:sofia.conf");

// Get setting
$get_sql = "SELECT sip_profile_setting_uuid, sip_profile_uuid, sip_profile_setting_name,
            sip_profile_setting_value, sip_profile_setting_enabled, sip_profile_setting_description
            FROM v_sip_profile_settings
            WHERE sip_profile_setting_uuid = :sip_profile_setting_uuid";

$setting = $database->select($get_sql, ['sip_profile_setting_uuid' => $sip_profile_setting_uuid], 'row');

api_success($setting, $message);
