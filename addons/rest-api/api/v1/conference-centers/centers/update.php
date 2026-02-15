<?php
require_once __DIR__ . '/../../base.php';
validate_api_key();
api_require_method('PUT');

$conference_center_uuid = get_uuid_from_path();
api_validate_uuid($conference_center_uuid, 'conference_center_uuid');

$data = get_request_data();

// Check if record exists
if (!api_record_exists('v_conference_centers', 'conference_center_uuid', $conference_center_uuid)) {
    api_not_found('Conference Center');
}

// Check extension conflict if changing extension
if (isset($data['conference_center_extension'])) {
    $database = new database;
    $check_sql = "SELECT COUNT(*) FROM v_conference_centers
                  WHERE conference_center_extension = :extension
                  AND domain_uuid = :domain_uuid
                  AND conference_center_uuid != :uuid";
    $check_params = [
        'extension' => $data['conference_center_extension'],
        'domain_uuid' => $domain_uuid,
        'uuid' => $conference_center_uuid
    ];

    if ($database->select($check_sql, $check_params, 'column') > 0) {
        api_conflict('conference_center_extension', 'Extension already exists');
    }
}

// Build update data
$allowed_fields = [
    'conference_center_name',
    'conference_center_extension',
    'conference_center_pin_length',
    'conference_center_greeting',
    'conference_center_enabled',
    'conference_center_description'
];

$update_data = [];
foreach ($allowed_fields as $field) {
    if (isset($data[$field])) {
        $update_data[$field] = $data[$field];
    }
}

if (empty($update_data)) {
    api_error('VALIDATION_ERROR', 'No valid fields to update', null, 400);
}

// Update
$database = new database;
$database->app_name = 'api-conference-centers';
$database->app_uuid = 'a8a12918-69a4-4ece-a1ae-3932e2f8a8a9';
$database->update('v_conference_centers', $update_data, [
    'conference_center_uuid' => $conference_center_uuid,
    'domain_uuid' => $domain_uuid
]);

// Clear cache
api_clear_dialplan_cache();

api_success(['conference_center_uuid' => $conference_center_uuid], 'Conference Center updated successfully');
