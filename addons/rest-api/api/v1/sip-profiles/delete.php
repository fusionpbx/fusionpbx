<?php
require_once __DIR__ . '/../base.php';

api_require_method('DELETE');

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

$database = new database;

// Check if profile exists
$check_sql = "SELECT COUNT(*) FROM v_sip_profiles WHERE sip_profile_uuid = :sip_profile_uuid";
$exists = $database->select($check_sql, ['sip_profile_uuid' => $sip_profile_uuid], 'column');

if (!$exists) {
    api_not_found('SIP Profile');
}

// Delete associated settings first
$database->app_name = 'api';
$database->app_uuid = '099d2b17-45dd-416f-b763-6037c47f1a84';

$delete_settings = [
    'sip_profile_uuid' => $sip_profile_uuid
];
$database->delete('v_sip_profile_settings', $delete_settings);

// Delete profile
$delete_data = [
    'sip_profile_uuid' => $sip_profile_uuid
];
$database->delete('v_sip_profiles', $delete_data);

// Clear cache
api_clear_cache(gethostname() . ":configuration:sofia.conf");

api_no_content();
