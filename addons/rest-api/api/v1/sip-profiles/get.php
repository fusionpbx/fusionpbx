<?php
require_once __DIR__ . '/../base.php';

api_require_method('GET');

// Get UUID from path
$sip_profile_uuid = get_uuid_from_path();
api_validate_uuid($sip_profile_uuid, 'sip_profile_uuid');

$database = new database;

// Get profile
$sql = "SELECT sip_profile_uuid, sip_profile_name, sip_profile_hostname,
        sip_profile_enabled, sip_profile_description
        FROM v_sip_profiles
        WHERE sip_profile_uuid = :sip_profile_uuid";

$parameters = ['sip_profile_uuid' => $sip_profile_uuid];
$profile = $database->select($sql, $parameters, 'row');

if (!$profile) {
    api_not_found('SIP Profile');
}

// Get settings
$settings_sql = "SELECT sip_profile_setting_uuid, sip_profile_setting_name,
        sip_profile_setting_value, sip_profile_setting_enabled,
        sip_profile_setting_description
        FROM v_sip_profile_settings
        WHERE sip_profile_uuid = :sip_profile_uuid
        ORDER BY sip_profile_setting_name ASC";

$settings = $database->select($settings_sql, $parameters, 'all');
$profile['settings'] = $settings ?? [];

api_success($profile);
