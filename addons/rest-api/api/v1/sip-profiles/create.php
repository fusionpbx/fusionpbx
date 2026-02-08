<?php
/**
 * WARNING: SIP profiles are global infrastructure-level resources that affect
 * all domains. This endpoint should be restricted to superadmin users only.
 * API key authentication may not be sufficient for production deployments.
 */

require_once __DIR__ . '/../base.php';

api_require_method('POST');

// SIP profiles are global resources - restrict to prevent multi-tenant issues
// Check if a specific domain setting allows sip_profile management
$settings = new settings(['database' => new database, 'domain_uuid' => $domain_uuid]);
$allow_sip_management = $settings->get('api', 'allow_sip_profile_management', 'false');
if ($allow_sip_management !== 'true') {
    api_error('FORBIDDEN', 'SIP profile management requires explicit permission. Set api > allow_sip_profile_management to true in domain settings.', null, 403);
}

// Get request data
$data = get_request_data();

// Validate required fields
$errors = api_validate($data, ['sip_profile_name', 'sip_profile_hostname']);
if (!empty($errors)) {
    api_validation_error($errors);
}

// Check for duplicate name
$database = new database;
$check_sql = "SELECT COUNT(*) FROM v_sip_profiles WHERE sip_profile_name = :sip_profile_name";
$exists = $database->select($check_sql, ['sip_profile_name' => $data['sip_profile_name']], 'column');

if ($exists > 0) {
    api_conflict('sip_profile_name', 'SIP Profile with this name already exists');
}

// Generate UUID
$sip_profile_uuid = uuid();

// Prepare save array
$array['sip_profiles'][0]['sip_profile_uuid'] = $sip_profile_uuid;
$array['sip_profiles'][0]['sip_profile_name'] = $data['sip_profile_name'];
$array['sip_profiles'][0]['sip_profile_hostname'] = $data['sip_profile_hostname'];
$array['sip_profiles'][0]['sip_profile_enabled'] = $data['sip_profile_enabled'] ?? 'true';
$array['sip_profiles'][0]['sip_profile_description'] = $data['sip_profile_description'] ?? null;

// Add temporary permission
$p = permissions::new();
$p->add('sip_profile_add', 'temp');

// Save record
$database = new database;
$database->app_name = 'sip_profiles';
$database->app_uuid = 'c3479cfa-1289-4e40-b371-42b3d8e2e665';
$database->save($array);
unset($array);

// Remove temporary permission
$p->delete('sip_profile_add', 'temp');

// Clear cache
api_clear_cache(gethostname() . ":configuration:sofia.conf");

// Return created profile
$response = [
    'sip_profile_uuid' => $sip_profile_uuid,
    'sip_profile_name' => $data['sip_profile_name'],
    'sip_profile_hostname' => $data['sip_profile_hostname'],
    'sip_profile_enabled' => $data['sip_profile_enabled'] ?? 'true',
    'sip_profile_description' => $data['sip_profile_description'] ?? null
];

api_created($response, 'SIP Profile created successfully');
