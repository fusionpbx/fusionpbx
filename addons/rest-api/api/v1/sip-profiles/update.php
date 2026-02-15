<?php
require_once __DIR__ . '/../base.php';

api_require_method(['PUT', 'PATCH']);

// SIP profiles are global resources - restrict to prevent multi-tenant issues
// Check if a specific domain setting allows sip_profile management
$settings = new settings(['database' => new database, 'domain_uuid' => $domain_uuid]);
$allow_sip_management = $settings->get('api', 'allow_sip_profile_management', 'false');
if ($allow_sip_management !== 'true') {
    api_error('FORBIDDEN', 'SIP profile management requires explicit permission. Set api > allow_sip_profile_management to true in domain settings.', null, 403);
}

// Get UUID from path
$sip_profile_uuid = get_uuid_from_path();
api_validate_uuid($sip_profile_uuid, 'sip_profile_uuid');

// Get request data
$data = get_request_data();

$database = new database;

// Check if profile exists
$check_sql = "SELECT COUNT(*) FROM v_sip_profiles WHERE sip_profile_uuid = :sip_profile_uuid";
$exists = $database->select($check_sql, ['sip_profile_uuid' => $sip_profile_uuid], 'column');

if (!$exists) {
    api_not_found('SIP Profile');
}

// Check for duplicate name if name is being changed
if (!empty($data['sip_profile_name'])) {
    $dup_sql = "SELECT COUNT(*) FROM v_sip_profiles
                WHERE sip_profile_name = :sip_profile_name
                AND sip_profile_uuid != :sip_profile_uuid";
    $dup_params = [
        'sip_profile_name' => $data['sip_profile_name'],
        'sip_profile_uuid' => $sip_profile_uuid
    ];
    $duplicate = $database->select($dup_sql, $dup_params, 'column');

    if ($duplicate > 0) {
        api_conflict('sip_profile_name', 'SIP Profile with this name already exists');
    }
}

// Build update data
$update_data = ['sip_profile_uuid' => $sip_profile_uuid];
$allowed_fields = ['sip_profile_name', 'sip_profile_hostname', 'sip_profile_enabled', 'sip_profile_description'];

foreach ($allowed_fields as $field) {
    if (array_key_exists($field, $data)) {
        $update_data[$field] = $data[$field];
    }
}

// Update
if (count($update_data) > 1) {
    $database->app_name = 'api';
    $database->app_uuid = '099d2b17-45dd-416f-b763-6037c47f1a84';
    $database->update('v_sip_profiles', $update_data, 'sip_profile_uuid');

    // Clear cache
    api_clear_cache(gethostname() . ":configuration:sofia.conf");
}

// Get updated profile
$sql = "SELECT sip_profile_uuid, sip_profile_name, sip_profile_hostname,
        sip_profile_enabled, sip_profile_description
        FROM v_sip_profiles
        WHERE sip_profile_uuid = :sip_profile_uuid";

$profile = $database->select($sql, ['sip_profile_uuid' => $sip_profile_uuid], 'row');

api_success($profile, 'SIP Profile updated successfully');
