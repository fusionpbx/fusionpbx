<?php
require_once __DIR__ . '/../../base.php';
validate_api_key();
api_require_method('POST');

$data = get_request_data();

// Validate required fields
$errors = api_validate($data, ['conference_center_name', 'conference_center_extension']);
if (!empty($errors)) {
    api_validation_error($errors);
}

// Check if extension already exists
$database = new database;
$check_sql = "SELECT COUNT(*) FROM v_conference_centers
              WHERE conference_center_extension = :extension AND domain_uuid = :domain_uuid";
$check_params = [
    'extension' => $data['conference_center_extension'],
    'domain_uuid' => $domain_uuid
];

if ($database->select($check_sql, $check_params, 'column') > 0) {
    api_conflict('conference_center_extension', 'Extension already exists');
}

// Generate UUID
$conference_center_uuid = uuid();

// Prepare save array
$array['conference_centers'][0]['domain_uuid'] = $domain_uuid;
$array['conference_centers'][0]['conference_center_uuid'] = $conference_center_uuid;
$array['conference_centers'][0]['conference_center_name'] = $data['conference_center_name'];
$array['conference_centers'][0]['conference_center_extension'] = $data['conference_center_extension'];
$array['conference_centers'][0]['conference_center_pin_length'] = $data['conference_center_pin_length'] ?? '0';
$array['conference_centers'][0]['conference_center_greeting'] = $data['conference_center_greeting'] ?? null;
$array['conference_centers'][0]['conference_center_enabled'] = $data['conference_center_enabled'] ?? 'true';
$array['conference_centers'][0]['conference_center_description'] = $data['conference_center_description'] ?? null;

// Add temporary permission
$p = permissions::new();
$p->add('conference_center_add', 'temp');

// Save record
$database->app_name = 'conference_centers';
$database->app_uuid = '1e46a1a6-0c43-4f35-8a89-67b26d7e1c27';
$database->save($array);
unset($array);

// Remove temporary permission
$p->delete('conference_center_add', 'temp');

// Clear cache
api_clear_dialplan_cache();

api_created(['conference_center_uuid' => $conference_center_uuid], 'Conference Center created successfully');
