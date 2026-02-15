<?php
require_once dirname(__DIR__) . '/auth.php';
validate_api_key();

$uuid = get_uuid_from_path();
if (empty($uuid) || !is_uuid($uuid)) {
    api_error('VALIDATION_ERROR', 'Invalid domain UUID');
}

// Verify the requested domain matches the authenticated domain
if ($uuid !== $domain_uuid) {
    api_error('FORBIDDEN', 'Access denied to this domain', null, 403);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get domain settings - use authenticated domain_uuid
    $database = new database;
    $sql = "SELECT domain_setting_uuid, domain_setting_category, domain_setting_subcategory,
                   domain_setting_name, domain_setting_value, domain_setting_enabled
            FROM v_domain_settings WHERE domain_uuid = :uuid
            ORDER BY domain_setting_category, domain_setting_subcategory";
    $settings = $database->select($sql, ['uuid' => $domain_uuid], 'all');

    api_success($settings ?? []);

} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Update domain settings
    $request = get_request_data();

    if (empty($request['settings']) || !is_array($request['settings'])) {
        api_error('VALIDATION_ERROR', 'Settings array is required');
    }

    // Whitelist of allowed setting categories that can be modified via API
    $allowed_categories = ['theme', 'time_format', 'voicemail', 'provision', 'fax'];

    foreach ($request['settings'] as $setting) {
        if (empty($setting['category']) || empty($setting['subcategory']) || empty($setting['name'])) {
            continue;
        }

        // Validate category against whitelist
        if (!in_array($setting['category'], $allowed_categories)) {
            api_error('FORBIDDEN', 'Setting category not allowed via API: ' . $setting['category'], 'category', 403);
        }

        // Check if setting exists - use authenticated domain_uuid
        $database = new database;
        $sql = "SELECT domain_setting_uuid FROM v_domain_settings
                WHERE domain_uuid = :uuid AND domain_setting_category = :cat
                AND domain_setting_subcategory = :subcat AND domain_setting_name = :name";
        $params = [
            'uuid' => $domain_uuid,
            'cat' => $setting['category'],
            'subcat' => $setting['subcategory'],
            'name' => $setting['name']
        ];
        $existing_uuid = $database->select($sql, $params, 'column');

        $array['domain_settings'][0]['domain_uuid'] = $domain_uuid;
        $array['domain_settings'][0]['domain_setting_uuid'] = $existing_uuid ?: uuid();
        $array['domain_settings'][0]['domain_setting_category'] = $setting['category'];
        $array['domain_settings'][0]['domain_setting_subcategory'] = $setting['subcategory'];
        $array['domain_settings'][0]['domain_setting_name'] = $setting['name'];
        $array['domain_settings'][0]['domain_setting_value'] = $setting['value'] ?? '';
        $array['domain_settings'][0]['domain_setting_enabled'] = $setting['enabled'] ?? 'true';

        $database = new database;
        $database->save($array);
        unset($array);
    }

    api_success(null, 'Settings updated successfully');
} else {
    api_error('METHOD_NOT_ALLOWED', 'Method not allowed', null, 405);
}
