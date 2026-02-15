<?php
require_once __DIR__ . '/../../base.php';

api_require_method('DELETE');

// Get UUID from path
$sip_profile_setting_uuid = get_uuid_from_path();
api_validate_uuid($sip_profile_setting_uuid, 'sip_profile_setting_uuid');

$database = new database;

// Check if setting exists
$check_sql = "SELECT sip_profile_uuid FROM v_sip_profile_settings
              WHERE sip_profile_setting_uuid = :sip_profile_setting_uuid";
$setting = $database->select($check_sql, ['sip_profile_setting_uuid' => $sip_profile_setting_uuid], 'row');

if (!$setting) {
    api_not_found('SIP Profile setting');
}

// Delete setting
$database->app_name = 'api';
$database->app_uuid = '099d2b17-45dd-416f-b763-6037c47f1a84';

$delete_data = [
    'sip_profile_setting_uuid' => $sip_profile_setting_uuid
];
$database->delete('v_sip_profile_settings', $delete_data);

// Clear cache
api_clear_cache(gethostname() . ":configuration:sofia.conf");

api_no_content();
